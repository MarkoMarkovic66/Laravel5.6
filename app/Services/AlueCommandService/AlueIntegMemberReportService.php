<?php
namespace App\Services\AlueCommandService;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;

use App\Enums\IsDeletedType;
use App\Enums\S3KeyPrefixType;
use App\Enums\MemberReportChartType;
use App\Enums\ChatworkMessageCaptionType;

use App\Dtos\MemberReportDto;

use App\Models\MemberReport;
use App\Models\MemberReportChart;

use App\Utils\DateUtils;
use Carbon\Carbon;

use ChatworkService;
use MemberService;
use SnappyPdf;
use S3Service;
use DB;

/**
 * AlueIntegMemberReportService
 * 会員レポート関連サービス
 */
class AlueIntegMemberReportService {

    use AlueCommandLogTrait;

    protected $commandLogger;
    protected $signature;
    protected $commandName;

    public function __construct()
    {
        $this->signature = 'AlueIntegMemberReportService';
        $this->commandName = 'AlueIntegMemberReportService';
        $this->initAlueCommandLogTrait('console/memberReport.log');
    }

    /**
     * 会員レポート生成
     * @return boolean
     */
    public function createMemberReport(&$resultMessage){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        try{
            $this->doCreateMemberReport($resultMessage);

        } catch (\Exception $ex) {
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', $ex->getMessage());
            return false;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' done');
        return true;
    }

    /**
     * 会員レポートPDFを生成する
     */
	private function doCreateMemberReport(&$resultMessage) {
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        //レポート発行日情報を取得する
        $issueDate = DateUtils::shortenToYYMMDD(Carbon::now()); //(ex) 2018-05-10
        $issueNumber = DateUtils::convertToYYMMDD_Japanese($issueDate).'号'; //(ex) 2018年05月10日号

        /*
         * Pythonモジュール（出題Python、および予測Python）、
         * および「レッスン/タスク実績集計バッチ」により生成されたグラフ画像一覧を取得する
         *
         * ファイル名は以下の書式となる。
         *   nnnnn_tt_yyyymmdd_hhmmss.png
         *
         *   nnnnn : user_id (usersのpk)
         *      tt : チャートタイプ (01 - 07, 10, 20, 21)
         */
        $chartFileInfos = [];
        $chartFolderPath = env('BATCH_MEMBER_REPORT_CHART_FOLDER');
        $filePaths = glob($chartFolderPath.'/*.png');
        if($filePaths && count($filePaths) > 0){
            foreach ($filePaths as $filePath) {
                $pathInfo = pathinfo($filePath);
                $basename = $pathInfo['basename'];//拡張子付き
                $filename = $pathInfo['filename'];//拡張子無し
                if(empty($filename)){
                    continue;
                }

                try {
                    $fileProperties = explode('_', $filename);
                    $userId    = $fileProperties[0];
                    $chartType = $fileProperties[1];
                    $chartDate = $fileProperties[2];
                    $chartTime = $fileProperties[3];
                } catch (\Exception $ex) {
                    /*
                     * 不正なファイル名の場合はエラーログを出力して続行する
                     */
                    $errmsg = $ex->getMessage() . ': Chartファイル名が不正です: '.$filename;
                    $this->logwrite('error', __CLASS__.':'.__FUNCTION__.' '.$errmsg);
                    continue;//続行
                }

                $chartCaption = $this->getChartCaption($chartType);
                $chartTitle   = $chartCaption['chartTitle'];
                $chartRemark  = $chartCaption['chartRemark'];
                $chartMessage = $chartCaption['chartMessage'];

                $chartFileInfos[$userId][$chartType] = [
                    'userId'        => $userId,
                    'chartType'     => $chartType,
                    'chartDate'     => $chartDate,
                    'chartTime'     => $chartTime,
                    'chartTitle'    => $chartTitle,
                    'chartRemark'   => $chartRemark,
                    'chartMessage'  => $chartMessage,
                    'chartFileName' => $basename,
                    'chartFilePath' => $filePath,
                ];

                $this->logwrite('info', 'detected file: userId: '. $userId
                                        . ' file num: ' . count($chartFileInfos[$userId]) );
            }//foreach
        }//end of if

        /*
         * 各ユーザー毎の処理
         */
        $countSuccess = 0;
        $countError = 0;
        $errorMessages = [];

        foreach ($chartFileInfos as $userId => $userChartInfos) {

            //当該会員のレポート情報の取得
            $memberReportDto = new MemberReportDto();

            /*
             * 会員基本情報(最新情報)の取得
             * <MemberDetailDto> $memberDetail
             */
            $params['userId'] = $userId;
            $memberDetail = MemberService::getMemberBasic($params);

            if( !empty($memberDetail) && !empty($memberDetail->userBasicInfo) && !empty($memberDetail->userBasicInfo->userId)){
                $userDto = $memberDetail->userBasicInfo;
                $memberReportDto->userId              = $userDto->userId;
                $memberReportDto->arugoUserId         = $userDto->arugoUserId;
                $memberReportDto->memberName          = $userDto->lastName . ' ' . $userDto->firstName;
                $memberReportDto->memberNameEn        = $userDto->firstNameEn . ' ' . $userDto->lastNameEn;
                $memberReportDto->contractPeriodSince = DateUtils::shortenToYYMMDD($userDto->openedAt);
                $memberReportDto->contractPeriodUntil = DateUtils::shortenToYYMMDD($userDto->closedAt);
                $memberReportDto->lessonPeriodSince   = DateUtils::shortenToYYMMDD($userDto->lessonPeriodSince);
                $memberReportDto->lessonPeriodUntil   = DateUtils::shortenToYYMMDD($userDto->lessonPeriodUntil);
                $memberReportDto->reportCreatedAt     = $issueDate;
                $memberReportDto->reportName          = $issueNumber;
                $memberReportDto->remark              = '';
                $memberReportDto->isPosted            = false;
                $memberReportDto->postedAt            = null;
                $memberReportDto->chartList           = $userChartInfos;//レポートグラフ画像情報

                //grammarRate用表データ設定
                $memberReportDto->grammarRates = MemberService::getGrammarRateData($userId, $issueDate);

                //累積レッスンチケット消化データ
                $memberReportDto->accumulatedLessonTicketsDatas = MemberService::getAccumulatedLessonTicketsData($userId, $issueDate);
                //累積タスク完了数データ取得
                $memberReportDto->accumulatedTaskCompleteRates = MemberService::getAccumulatedTaskCompleteRate($userId, $issueDate);

                //当該会員のレポート情報を登録する
                $errorMessage = '';
                if( $this->registMemberReportInfo($memberReportDto, $errorMessage) ){
                    //OK
                    $countSuccess ++;
                }else{
                    //NG
                    $countError ++;
                    $errorMessages[] = 'user_id: '.$userId . ' error: '.$errorMessage;
                }
            }//end of if

        }//foreach

        //後処理 ※グラフ画像ファイル、生成されたPDFファイルを保存用フォルダに移動しておく
        $this->backupChartAndPdf();


        //終了メッセージ作成
        $resultMessage = '会員レポート生成成功: '.$countSuccess.'件 '
                       . '会員レポート生成失敗: '.$countError.'件 ';
        if(count($errorMessages) > 0){
            foreach($errorMessages as $errorMessage){
                $resultMessage .= "\n".$errorMessage;
            }
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' done');
    }

    /**
     * 当該会員のレポート情報を登録する
     * @param array $memberReportDto
     * @param string $errorMessage
     * @return boolean
     */
    private function registMemberReportInfo($memberReportDto, &$errorMessage){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.': user_id:'.$memberReportDto->userId);

        DB::beginTransaction();//ユーザー毎にTransactionを掛ける
        try {

            //当該会員のレポート情報：グラフ画像情報のS3転送 → グラフ画像管理レコードを登録する
            $this->registMemberReportChart($memberReportDto);

            //当該会員のレポート情報：PDF生成 → S3転送 → 会員レポート管理レコードを登録する
            $this->registMemberReportPdf($memberReportDto);

            DB::commit();
            $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' done');
            return true;

        } catch (\Exception $ex) {
            DB::rollback();
            $errorMessage = $ex->getMessage();
            $this->logwrite('error', 'user_id:'.$memberReportDto->userId.' :'.$ex->getMessage());
            return false;
        }
    }

    /**
     * 当該会員のレポート情報：グラフ画像情報のS3転送 → 管理テーブル登録
     * @param type $memberReportDto
     */
    private function registMemberReportChart($memberReportDto){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        $chartOrder = 1;
        $reportCharts = $memberReportDto->chartList;//レポートグラフ画像情報
        foreach($reportCharts as $reportChart){

            //S3へ転送の準備
            $fileName = $reportChart['chartFileName'];
            $filePath = $reportChart['chartFilePath'];

            //S3Key生成 ( member_report/chart/{user_id}/{発行日}/{ファイル名} )
            $s3Key = S3KeyPrefixType::S3KEY_PREFIX_MEMBER_REPORT_CHART
                   . '/'.$memberReportDto->userId
                   . '/'.$memberReportDto->reportCreatedAt
                   . '/'.$fileName;

            //S3へ転送
            S3Service::postObject($s3Key, $filePath);
            $this->logwrite('info', 'S3 Send Success: s3Key:'.$s3Key);

            //グラフ画像管理レコードを登録する
            $memberReportChart = new MemberReportChart();
            $memberReportChart->user_id					= $memberReportDto->userId;
            $memberReportChart->report_created_at       = $memberReportDto->reportCreatedAt;
            $memberReportChart->chart_type              = $reportChart['chartType'];
            $memberReportChart->chart_order             = $chartOrder;
            $memberReportChart->chart_title             = $reportChart['chartTitle'];
            $memberReportChart->chart_s3_key            = $s3Key;
            $memberReportChart->chart_remark            = $reportChart['chartRemark'];
            $memberReportChart->chart_message           = $reportChart['chartMessage'];
            $memberReportChart->is_deleted              = IsDeletedType::ACTIVE;
            $memberReportChart->created_at              = Carbon::now();
            $memberReportChart->save();

            $chartOrder ++;
        }//foreach

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' done');
    }

    /**
     * グラフ画像キャプション取得
     * @param type $chartType
     * @return type
     */
    private function getChartCaption($chartType){
        $chartTitle   = '';
        $chartRemark  = null;
        $chartMessage = null;

        if(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_01){
            $chartTitle   = trans('member_report_chart.chart01_title_ja');
            //$chartRemark  = trans('member_report_chart.chart01_remark');  //未使用
            //$chartMessage = trans('member_report_chart.chart01_message'); //未使用

        }elseif(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_02){
            $chartTitle   = trans('member_report_chart.chart02_title_ja');
            //$chartRemark  = trans('member_report_chart.chart02_remark');  //未使用
            //$chartMessage = trans('member_report_chart.chart02_message'); //未使用

        }elseif(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_03){
            $chartTitle   = trans('member_report_chart.chart03_title_ja');
            //$chartRemark  = trans('member_report_chart.chart03_remark');  //未使用
            //$chartMessage = trans('member_report_chart.chart03_message'); //未使用

        }elseif(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_04){
            $chartTitle   = trans('member_report_chart.chart04_title_ja');
            //$chartRemark  = trans('member_report_chart.chart04_remark');  //未使用
            //$chartMessage = trans('member_report_chart.chart04_message'); //未使用

        }elseif(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_05){
            $chartTitle   = trans('member_report_chart.chart05_title_ja');
            //$chartRemark  = trans('member_report_chart.chart05_remark');  //未使用
            //$chartMessage = trans('member_report_chart.chart05_message'); //未使用

        }elseif(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_06){
            $chartTitle   = trans('member_report_chart.chart06_title_ja');
            //$chartRemark  = trans('member_report_chart.chart06_remark');  //未使用
            //$chartMessage = trans('member_report_chart.chart06_message'); //未使用

        }elseif(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_07){
            $chartTitle   = trans('member_report_chart.chart07_title_ja');
            //$chartRemark  = trans('member_report_chart.chart07_remark');  //未使用
            //$chartMessage = trans('member_report_chart.chart07_message'); //未使用

        }elseif(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_10){
            //予測グラフ
            $chartTitle   = trans('member_report_chart.chart10_title_ja');

        }elseif(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_20){
            //統計グラフ：セッション数
            $chartTitle   = trans('member_report_chart.chart20_title_ja');
        }elseif(!empty($chartType) && $chartType == MemberReportChartType::MEMBER_REPORT_CHART_TYPE_21){
            //統計グラフ：タスク完了率
            $chartTitle   = trans('member_report_chart.chart21_title_ja');
        }

        return [
            'chartTitle'   => $chartTitle,
            'chartRemark'  => $chartRemark,
            'chartMessage' => $chartMessage
        ];
    }


    /**
     * 当該会員のレポート情報：PDF生成 → S3転送 → 管理テーブル登録
     * @param type $memberReportDto
     */
    private function registMemberReportPdf($memberReportDto){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        /*
         * 指定された会員のレポートPDF出力
         */
        //レポートPDFファイル名の生成
        $userId = $memberReportDto->userId;
        $pdfFileDateTime = Carbon::now()->format('Ymd_His');
        $pdfFileName = 'member_report_' . $userId . '_' . $pdfFileDateTime . '.pdf';
        $pdfFilePath = env('BATCH_MEMBER_REPORT_PDF_FOLDER').'/'.$pdfFileName;

        //レポートPDFファイル出力
        /*
        $footerText = '<div class="pdf-footer">'
                    . '<span>© 2018, Alue Co., Ltd. All Rights Reserved.</span>'
                    . '</div>';
         */
        $footerUrl = config_path('../resources/views/Member/member_report_pdf_footer.html');

        $viewParams['memberReportInfo'] = $memberReportDto;
        $pdf = SnappyPdf::loadView('Member.member_report_pdf', ['view_params' => $viewParams]);
        $pdf->setOption('page-size', 'A4')                  //用紙サイズ
            ->setOption('encoding', 'utf-8')                // Encoding
            ->setOption('margin-top',    5)                 // 上マージン
            ->setOption('margin-bottom', 15)                // 下マージン
            ->setOption('margin-left',   5)                 // 左マージン
            ->setOption('margin-right',  5)                 // 右マージン
            ->setOption('orientation', 'Portrait')          // 縦向き
            ->setOption('footer-html', $footerUrl)          // フッター
            ->setOption('user-style-sheet', public_path() . '/lib/bootstrap-4.0.0/css/bootstrap-reboot.min.css')
            ->setOption('user-style-sheet', public_path() . '/lib/bootstrap-4.0.0/css/bootstrap.min.css')
            ->setOption('user-style-sheet', public_path() . '/css/style.css');

            //->setOption('footer-center', 'page [page]');  // ページ番号(非表示)

        $pdf->save($pdfFilePath);

        $this->logwrite('info', 'Create Pdf Success: pdfFilePath: ' . $pdfFilePath);

        /*
         * 生成したレポートをS3に転送
         */
        //S3Key生成 ( member_report/pdf/{user_id}/{発行日}/{ファイル名} )
        $s3Key = S3KeyPrefixType::S3KEY_PREFIX_MEMBER_REPORT_PDF
               . '/'.$memberReportDto->userId
               . '/'.$memberReportDto->reportCreatedAt
               . '/'.$pdfFileName;

        //S3へ転送
        S3Service::postObject($s3Key, $pdfFilePath);
        $this->logwrite('info', 'S3 Send Success: s3Key:'.$s3Key);


        //会員レポート管理レコード登録
        $memberReport = new MemberReport();
        $memberReport->user_id                = $memberReportDto->userId;
        $memberReport->report_created_at      = $memberReportDto->reportCreatedAt;
        $memberReport->arugo_user_id          = $memberReportDto->arugoUserId;
        $memberReport->contract_period_since  = empty($memberReportDto->contractPeriodSince) ? null: $memberReportDto->contractPeriodSince;
        $memberReport->contract_period_until  = empty($memberReportDto->contractPeriodUntil) ? null: $memberReportDto->contractPeriodUntil;
        $memberReport->lesson_period_since    = empty($memberReportDto->lessonPeriodSince)   ? null: $memberReportDto->lessonPeriodSince;
        $memberReport->lesson_period_until    = empty($memberReportDto->lessonPeriodUntil)   ? null: $memberReportDto->lessonPeriodUntil;
        $memberReport->report_name            = $memberReportDto->reportName;
        $memberReport->report_s3_key          = $s3Key;
        $memberReport->report_remark          = null;
        $memberReport->is_posted              = 0;
        $memberReport->posted_at              = null;
        $memberReport->is_deleted             = IsDeletedType::ACTIVE;
        $memberReport->created_at             = Carbon::now();
        $memberReport->save();

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' done');
    }

    /**
     * 後処理
     * グラフ画像ファイル、生成されたPDFファイルを保存用フォルダに移動しておく
     */
    private function backupChartAndPdf(){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        //グラフ画像生成フォルダパス
        $batchMemberReportChartFolder = env('BATCH_MEMBER_REPORT_CHART_FOLDER') . '/';
        //レポートpdf生成フォルダパス
        $batchMemberReportPdfFolder = env('BATCH_MEMBER_REPORT_PDF_FOLDER') . '/';
        //グラフ画像バックアップフォルダパス
        $batchMemberReportChartBackupFolder = env('BATCH_MEMBER_REPORT_CHART_BACKUP_FOLDER') . '/';
        //レポートpdfバックアップフォルダパス
        $batchMemberReportPdfBackupFolder = env('BATCH_MEMBER_REPORT_PDF_BACKUP_FOLDER') . '/';

        $handle = null;
        try {
            //バックアップフォルダの生成
            $backupDateTime = Carbon::now()->format('YmdHis');
            $backupChartFolderPath = $batchMemberReportChartBackupFolder . $backupDateTime;
            $backupPdfFolderPath   = $batchMemberReportPdfBackupFolder . $backupDateTime;

            if( !file_exists($backupChartFolderPath) ){
                mkdir($backupChartFolderPath, 0755, true);
            }
            if( !file_exists($backupPdfFolderPath) ){
                mkdir($backupPdfFolderPath, 0755, true);
            }

            //グラフ画像をバックアップする...
            $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' chart backup start');
            if($handle = opendir($batchMemberReportChartFolder)) {
                while(false !== ($entry = readdir($handle))) {
                    //ファイル名が「.」「..」じゃなければ処理を実行
                    if ($entry != "." && $entry != "..") {
                        //ファイルを指定したディレクトリに移動させる
                        $srcFilePath = $batchMemberReportChartFolder.$entry;
                        $dstFilePath = $backupChartFolderPath.'/'.$entry;
                        rename($srcFilePath, $dstFilePath);
                    }
                }
                //オープンしたディレクトリのハンドルをクローズする
                closedir($handle);
            }
            $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' chart backup done');

            //レポートpdfをバックアップする...
            $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' pdf backup start');
            if($handle = opendir($batchMemberReportPdfFolder)) {
                while(false !== ($entry = readdir($handle))) {
                    //ファイル名が「.」「..」じゃなければ処理を実行
                    if ($entry != "." && $entry != "..") {
                        //ファイルを指定したディレクトリに移動させる
                        $srcFilePath = $batchMemberReportPdfFolder.$entry;
                        $dstFilePath = $backupPdfFolderPath.'/'.$entry;
                        rename($srcFilePath, $dstFilePath);
                    }
                }
                //オープンしたディレクトリのハンドルをクローズする
                closedir($handle);
            }
            $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' pdf backup done');

        } catch (\Exception $ex) {
            if($handle){
                closedir($handle);
            }
            $this->logwrite('error', 'グラフ画像/レポートPDFバックアップエラー: ' . $ex->getMessage());
            return false;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' done');
    }


    /**
     * @deprecated
     * 会員レポート送信
     * @return boolean
     */
    public function pushMemberReport(&$resultMessage){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        try{
            $this->doPushMemberReport($resultMessage);

        } catch (\Exception $ex) {
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', $ex->getMessage());
            return false;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' done');
        return true;
    }
    /**
     * @deprecated
     * 会員レポート送信実行
     */
	private function doPushMemberReport(&$resultMessage) {
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        $targetDate = Carbon::now()->toDateString(); //当日分のレポートのみを送信する
        $this->logwrite('info', 'targetDate: ' . $targetDate);

        $memberReports = MemberReport::from('member_reports as rep')
                                ->join('users as usr', 'rep.user_id', 'usr.id')
                                ->where('rep.is_deleted', IsDeletedType::ACTIVE)
                                ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                                ->where('rep.is_posted', 0) //未投稿
                                ->whereDate('rep.report_created_at', $targetDate) //送信対象日の指定
                                ->select([
                                        'rep.*',
                                        'usr.id as userId',
                                        'usr.arugo_user_id as alugoUserId',
                                        'usr.cw_room_id',
                                        'usr.first_name',
                                        'usr.last_name',
                                        'usr.first_name_en',
                                        'usr.last_name_en',
                                    ])
                                ->orderby('rep.user_id')
                                ->orderby('rep.report_created_at')
                                ->get();
        $cnt = 0;
        foreach($memberReports as $memberReport){
            $memberReportId     = $memberReport->id;
            $memberReportName   = $memberReport->report_name;
            $memberReportS3Key  = $memberReport->report_s3_key;
            $userId             = $memberReport->userId;
            $alugoUserId        = $memberReport->alugoUserId;
            $cwRoomId           = $memberReport->cw_room_id;
            $memberName         = $memberReport->last_name.' '.$memberReport->first_name;
            $memberNameEn       = $memberReport->first_name_en.' '.$memberReport->last_name_en;

            if(empty($cwRoomId) || empty($memberReportS3Key)){
                continue;
            }

            //会員レポートのURL取得
            $preSignedUrl = S3Service::getPreSignedUrlByS3Key($memberReportS3Key);
            $reportCaption = trans(
                                ChatworkMessageCaptionType::CW_MEMBER_REPORT_CAPTION,
                                [
                                    'memberName'=>$memberName,
                                    'reportName'=>$memberReportName,
                                    'linkUrl'=>$preSignedUrl,
                                ]
                            );
            $cwMessage = $reportCaption;

            $logMessage = ' userId:'      . $userId
                        . ' alugoUserId:' . $alugoUserId
                        . ' memberName:'  . $memberName
                        . ' reportName:'  . $memberReportName
                        . ' linkUrl:'     . $preSignedUrl;
            $this->logwrite('info', $logMessage);

            //当該会員のchatworkに会員レポートのURLを投稿する
            $params = [];
            $params['roomid']  = $cwRoomId;
            $params['title']   = '';
            $params['message'] = $cwMessage;
            ChatworkService::sendChatworkMessage($params);
            $this->logwrite('info', 'sendChatworkMessage ok');

            //当該会員について、会員レポート投稿済みを登録する
            MemberReport::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('id', $memberReportId)
                            ->update([
                                'is_posted' => 1,
                                'posted_at' => Carbon::now()
                            ]);
            $this->logwrite('info', 'MemberReport updated');
            $cnt ++;
        }//foreach

        $resultMessage = '会員レポートを '.$cnt.'件投稿しました。';
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' done');
        return true;
    }


}
