<?php
namespace App\Services\AlueCommandService;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use App\Traits\CsvDataValidateTrait;

use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserScore;
use App\Models\UserGoal;
use App\Models\UserLessonLog;
use App\Models\UserQuestionnarie;
use App\Models\UserSession;
use App\Models\UserOutput;
use App\Models\SrCategory;
use App\Models\SpeakingRally;
use App\Models\Package;

use App\Enums\IsDeletedType;
use App\Utils\CommonUtils;
use App\Utils\DateUtils;
use \SplFileObject;
use Carbon\Carbon;
use Exception;
use DB;
use Hash;


/**
 * AlueIntegDataImportService
 * コマンドバッチ関連サービス
 */
class AlueIntegDataImportService {

    use AlueCommandLogTrait;
    use CsvDataValidateTrait;

    protected $commandLogger;
    protected $signature;
    protected $commandName;

    protected $userPlanDataHeader;
    protected $userPackageDataHeader;   //package.csvヘッダ項目
    protected $userDataHeader;          //users.csvヘッダ項目
    protected $personalDataHeader;      //personal.csvヘッダ項目
    protected $userItemProperties;      //usersテーブル項目属性
    protected $personalItemProperties;  //personal項目属性

    protected $userScoreDataHeader;
    protected $userGoalDataHeader;
    protected $userLessonDataHeader;
    protected $questionnaireDataHeader;
    protected $ccSessionDetailDataHeader;
    protected $srAnswersDataHeader;
    protected $srTopicsDataHeader;
    protected $srUsersDataHeader;

    public function __construct()
    {
        $this->signature = 'AlueIntegDataImportService';
        $this->commandName = 'AlueIntegDataImportService';
        $this->initAlueCommandLogTrait('console/importData.log');
        $this->initCsvDataValidateTrait();
    }

    /**
     * Main
     */
    public function exec(&$resultMessage){

        $this->logwrite('info', 'AlueIntegDataImportService start');

        DB::beginTransaction();
        try {

            //データ移行設定の取得
            $dataFolderPath = env('BATCH_IMPORT_DATA_FOLDER');//受け取り用フォルダ

            //対象ファイル取得
            $this->loadDataFile($dataFolderPath);

            //ユーザー関連
            $this->importUser($dataFolderPath);

            //ユーザーPlan関連
            $this->importUserPlan($dataFolderPath);

            //ユーザーPackage関連(2019-03-19)
            $this->importPackage($dataFolderPath);

            //ユーザースコア関連
            $this->importUserScore($dataFolderPath);

            //ユーザーゴール関連
            $this->importUserGoal($dataFolderPath);

            //レッスンログ関連
            $this->importUserLessonLog($dataFolderPath);

            //questionnaire関連
            $this->importQuestionnaire($dataFolderPath);

            //cc_session_detail関連
            $this->importCcSessionDetail($dataFolderPath);

            //sr_topics関連
            $this->importSrTopics($dataFolderPath);

            /*
             * sr_users関連
             * sr_usersよりsr_user_idを取得してusersテーブルに設定する
             */
            $this->importSrUsers($dataFolderPath);

            /*
             * user_outputs関連
             * user_outoputsテーブルは合成データであるため、
             * 1. sr_answers.csvを移行
             * 2. user_lesson.csvを移行
             * の2段階で行う。
             */
            $this->importToUserOutputs($dataFolderPath);

            /*
             * 2018-10-22
             * データ移行デバッグモードの処理
             * users.mail をデバッグ用にダミーにする
             */
            $debugMode = env('BATCH_IMPORT_MAIL_DEBUG');
            if($debugMode == '1'){
                DB::update('update users set mail = concat(lpad(id, 4, "0"), "@integ.com")');
            }

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', $ex->getMessage());
            return false;
        }

        $this->logwrite('info', 'AlueIntegDataImportService finish');
        return true;
    }

    /**
     * CSVデータファイル取得
     */
    private function loadDataFile($dataFolderPath){

        /*
         * データ操作Shellの設定
         * 実行時ユーザを取得し、該当ユーザに応じて使用するshellを切り替える。
         * 理由：
         * shell内では秘密鍵を取り扱うが、秘密鍵は権限が制限されており
         * これは実行時ユーザに影響を受ける。
         * このため、実行ユーザにあったshellを実行するためである。
         */
        $runInfo = posix_getpwuid(posix_geteuid());
        $runUserName = $runInfo['name'];
        $this->logwrite('info', '実行時ユーザは '.$runUserName . ' です');
        if($runUserName == 'apache'){
            //画面からの手動実行
            $shellGetData     = 'sh '. env('BATCH_SHELL_GET_DATA')    . ' man';//データ取得シェル
            $shellGetSrData   = 'sh '. env('BATCH_SHELL_GET_SR_DATA') . ' man';//SRデータ取得シェル

        }elseif($runUserName == 'root'){
            //自動実行
            $shellGetData     = 'sh '. env('BATCH_SHELL_GET_DATA')    . ' auto';//データ取得シェル
            $shellGetSrData   = 'sh '. env('BATCH_SHELL_GET_SR_DATA') . ' auto';//SRデータ取得シェル

        }elseif($runUserName == 'ec2-user'){
            //端末から手動実行：疑似的な自動実行
            $shellGetData     = 'sh '. env('BATCH_SHELL_GET_DATA')    . ' ec2';//データ取得シェル
            $shellGetSrData   = 'sh '. env('BATCH_SHELL_GET_SR_DATA') . ' ec2';//SRデータ取得シェル

        }else{
            //Unknown User
            throw new Exception('想定外の実行ユーザです');
        }

        //ユーザー共通shell
        $shellBackupData  = 'sh '. env('BATCH_SHELL_BACKUP_DATA');//データバックアップシェル
        $shellCleanUpData = 'sh '. env('BATCH_SHELL_CLEANUP_DATA');//データクリーンアップシェル

        $this->logwrite('debug', 'shellBackupData : '.$shellBackupData);
        $this->logwrite('debug', 'shellCleanUpData : '.$shellCleanUpData);
        $this->logwrite('debug', 'shellGetData : '.$shellGetData);
        $this->logwrite('debug', 'shellGetSrData : '.$shellGetSrData);

        /*
         * 1.過去データのバックアップ
         * 過去のファイル群を保存用フォルダに移動しておく。
         * バックアップ用フォルダに本日の日付時刻(YYYYMMDDhhmmss)で
         * サブフォルダを掘り、そこに格納する。
         */
        $outMsg = [];
        $retVal = 0;
        $msg = '';
        exec($shellBackupData, $outMsg, $retVal);
        foreach($outMsg as $row){
            $msg .= $row;
        }
        if($retVal != 0){
            throw new Exception('過去データバックアップエラー : 過去データの保存ができません : '.$msg . ' : '.$retVal);
        }
        $this->logwrite('info', '過去データバックアップ完了 : '.$msg . ' : '.$retVal);


        /*
         * 2.過去データのクリーンアップ
         * 過去のファイル群を削除する(*.dat , *.csv)
         */
        $outMsg = [];
        $retVal = 0;
        $msg = '';
        exec($shellCleanUpData, $outMsg, $retVal);
        foreach($outMsg as $row){
            $msg .= $row;
        }
        if($retVal != 0){
            throw new Exception('データクリーンアップエラー : データのクリーンアップができません : '.$msg . ' : '.$retVal);
        }
        $this->logwrite('info', 'データクリーンアップ完了 : '.$msg . ' : '.$retVal);


        /*
         * 3.各ファイルをscpで取得する
         * 3-1.SR以外のファイル
         */
        $outMsg = [];
        $retVal = 0;
        $msg = '';
        exec($shellGetData, $outMsg, $retVal);
        if($retVal != 0){
            throw new Exception('最新データ取得エラー : 最新データの取得ができません : '.$retVal);
        }
        foreach($outMsg as $row){
            $msg .= $row;
        }
        $this->logwrite('info', '最新データ取得完了 : '.$msg . ' : '.$retVal);


        /*
         * 3-2.SRデータのファイル
         */
        $outMsg = [];
        $retVal = 0;
        $msg = '';
        exec($shellGetSrData, $outMsg, $retVal);
        if($retVal != 0){
            throw new Exception('最新SRデータ取得エラー : 最新SRデータの取得ができません : '.$retVal);
        }
        foreach($outMsg as $row){
            $msg .= $row;
        }
        $this->logwrite('info', '最新SRデータ取得完了 : '.$msg . ' : '.$retVal);


        /*
         * 4.zip解凍 & ファイル名変更
         */
        $zipFileInfos = [];
        $zipFileInfos[] = [
          'zipFileName'   => 'cc_session_detail.MEC.7z',
          'zipPassword'   => '',
          'unzipFileName' => 'cc_session_detail.MEC.dat',
          'csvFileName'   => 'cc_session_detail.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'personal.MEC.7z',
          'zipPassword'   => 'alue',
          'unzipFileName' => 'personal.MEC.dat',
          'csvFileName'   => 'personal.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'questionnaire.MEC.7z',
          'zipPassword'   => '',
          'unzipFileName' => 'questionnaire.MEC.dat',
          'csvFileName'   => 'questionnaire.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'users.MEC.7z',
          'zipPassword'   => '',
          'unzipFileName' => 'users.MEC.dat',
          'csvFileName'   => 'users.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'goals.MEC.7z',
          'zipPassword'   => '',
          'unzipFileName' => 'goals.MEC.dat',
          'csvFileName'   => 'user_goals.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'lessons.MEC.7z',
          'zipPassword'   => '',
          'unzipFileName' => 'lessons.MEC.dat',
          'csvFileName'   => 'user_lesson.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'user_scores.MEC.7z',
          'zipPassword'   => '',
          'unzipFileName' => 'user_scores.MEC.dat',
          'csvFileName'   => 'user_scores.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'plan.MEC.7z',
          'zipPassword'   => '',
          'unzipFileName' => 'plan.MEC.dat',
          'csvFileName'   => 'user_plan.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'package.MEC.7z',
          'zipPassword'   => '',
          'unzipFileName' => 'package.MEC.dat',
          'csvFileName'   => 'package.csv'
        ];

        //SR関連
        $zipFileInfos[] = [
          'zipFileName'   => 'answers.SR.dat',
          'zipPassword'   => '',
          'unzipFileName' => 'answers.SR.csv',
          'csvFileName'   => 'sr_answers.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'topics.SR.dat',
          'zipPassword'   => '',
          'unzipFileName' => 'topics.SR.csv',
          'csvFileName'   => 'sr_topics.csv'
        ];
        $zipFileInfos[] = [
          'zipFileName'   => 'users.SR.dat',
          'zipPassword'   => 'alue',
          'unzipFileName' => 'users.SR.csv',
          'csvFileName'   => 'sr_users.csv'
        ];

        //ファイルの解凍とリネーム
        foreach($zipFileInfos as $zipFileInfo){
            $this->unzipDataFile($zipFileInfo);
        }

        /*
         * テスト用（windows専用）
         * for TEST  ./original よりコピーして戻す
         * /
        $srcFilePath = $dataFolderPath.'/original';
        $dstFilePath = $dataFolderPath;

        if ($handle = opendir($srcFilePath)) {
            //オープンしたディレクトリにファイルが存在すればループで取り出していく
            while(false !== ($entry = readdir($handle))) {
                //ファイル名が「.」「..」以外の場合に処理を実行
                if ($entry != "." && $entry != ".." && $entry != "backup" && $entry != "original") {
                    //ファイルを指定したディレクトリに移動させる
                    copy( $srcFilePath.'/'.$entry,
                          $dstFilePath.'/'.$entry);
                }
            }
            //オープンしたディレクトリのハンドルをクローズする
            closedir($handle);
        }
        / *
         * for TEST  ./original よりコピーして戻す
         */

    }

    /**
     * ZIP解凍
     * @param type $zipFileInfo
     */
    private function unzipDataFile($zipFileInfo){

        try {
            // ZIPファイルのパス
            $zipFileName   = $zipFileInfo['zipFileName'];
            $zipPassword   = $zipFileInfo['zipPassword'];
            $unzipFileName = $zipFileInfo['unzipFileName'];
            $csvFileName   = $zipFileInfo['csvFileName'];

            $this->logwrite('info', 'unzipDataFile : zipFileName: '.$zipFileName);

            //解凍対象の7zファイル
            $zipFolder = env('BATCH_IMPORT_DATA_FOLDER');
            $zipPath = $zipFolder.'/'.$zipFileName;
            //解凍後のdatファイル
            $dstDatFileName = $unzipFileName;
            $dstDatFilePath = $zipFolder.'/'.$dstDatFileName;
            //最終的なcsvファイル
            $dstCsvFileName = $csvFileName;
            $dstCsvFilePath = $zipFolder.'/'.$dstCsvFileName;

            //対象ファイル存在チェック
            if(!file_exists($zipPath)){
                $this->logwrite('warn', '7Za 解凍エラー : 対象ファイルが存在しません :'.$zipPath);
                return;
            }

            $this->logwrite('info', 'UnZip : datFileName: '.$dstDatFileName);
            $this->logwrite('info', 'UnZip : csvFileName: '.$dstCsvFileName);

            if(!empty($zipPassword)){
                $unzipCmd = '/usr/local/bin/7za e '.$zipPath .' -p'.$zipPassword . ' -o'.$zipFolder;
            }else{
                $unzipCmd = '/usr/local/bin/7za e '.$zipPath . ' -o'.$zipFolder;
            }
            $this->logwrite('debug', '7Zaコマンド : '.$unzipCmd);

            $outMsg = [];
            $retVal = 0;
            $msg = '';
            exec($unzipCmd, $outMsg, $retVal);
            foreach($outMsg as $row){
                $msg .= $row;
            }
            if($retVal != 0){
                $this->logwrite('error', '7Za zip解凍エラー : '.$msg.' : '.$retVal);
                throw new Exception('7Za zip解凍エラー : zipファイルが解凍できません : '.$msg.' : '.$retVal);
            }
            $this->logwrite('info', '7Za zip解凍完了 : '.$msg.' : '.$retVal);

            /*
             * 読み込んだSJISのデータをUTF-8に変換して保存
             */
            file_put_contents($dstCsvFilePath, mb_convert_encoding(file_get_contents($dstDatFilePath), 'UTF-8', 'auto'));

        } catch (\Exception $ex) {
            $this->logwrite('error', 'unzip error: '.$ex->getMessage());
            throw $ex;
        }

    }


    /**
     * CSVデータファイル取り込み
     * @param type $csvDataFile
     * @param type $dataHeader
     * @return type
     */
    private function loadCsvData($csvDataFile, $dataHeader){

        $csvDatas = collect();
        $fobj = new SplFileObject($csvDataFile, 'r');
        $fobj->setFlags(\SplFileObject::READ_CSV
            | \SplFileObject::READ_AHEAD             //先読み・巻き戻しで読み出す。
            | \SplFileObject::SKIP_EMPTY             //ファイルの空行を読み飛ばす。read_aheadとセットで使わないとダメ。
            | \SplFileObject::DROP_NEW_LINE          //行末の改行を読み飛ばす。
        );

        if( $fobj !== FALSE) {
            $rowCnt = 0;
            while (!$fobj->eof() && (( $row = $fobj->fgetcsv(',', '"', '|') ) !== FALSE )) {
                if($rowCnt==0){
                    $rowCnt++;
                    continue;
                }

                if(empty($row) || !is_array($row) || count($row) < 1 ){
                    continue;//空行読み飛ばし
                }

                $dataRow = collect($row);
                $this->trimDblQuote($dataRow);

                $dataBody = collect();
                $dataHeader->each(function($columnName, $columnNo) use($dataRow, &$dataBody){
                    $dataBody->put($columnName, $dataRow->get($columnNo) );
                });
                $dataBody->put('validStatus', true);//判定用項目を追加しておく
                $csvDatas->push($dataBody);
                $rowCnt++;
            }
            unset($fobj);
        }
        return $csvDatas;
    }


    /**
     * user_plan.csvをインポートしてuser_plansに格納する
     * @param type $dataFolderPath
     */
    private function importUserPlan($dataFolderPath){
        $this->logwrite('info', 'user_plan.csvファイル:インポート開始');

        // user_plan.csvファイル読み込み
        $csvDataFile = $dataFolderPath.'/user_plan.csv';
        $userPlanDataHeader = $this->getUserPlanDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $userPlanDataHeader);
        $this->logwrite('info', 'user_plan.csvファイル:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'user_plan.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();
        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {
            //-- student_idの検証
            //alugo_user_idの取得
            $alugo_user_id = $userData->get('student_id');
            if(empty($alugo_user_id)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $alugo_user_id,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //alugo_user_id、本システムのuser_idを取得
            $userId = '';
            $userRec = User::where('arugo_user_id', $alugo_user_id)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $userId = $userRec->id; //users.id：UsersのPK
                $userData->put('userId', $userId);  //本システムのuser_id
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $alugo_user_id,
                    'reason' => 'NOT RELATED WITH USERS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }
            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'user_plan.csvファイル:データ検証終了');

        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'user_plan.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            /*
             * user_plansの登録済みレコードを取得
             * user_idではなく移行データのpkそのもので取得/更新する
             */
            $pkid = $userData->get('id'); //pkで取得する
            $targetRec = UserPlan::find($pkid);
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new UserPlan();//該当レコードが無い場合は新規作成
            }

            $targetRec->id                = $userData->get('id');
            $targetRec->user_id           = $userData->get('userId');
            $targetRec->alugo_user_id     = CommonUtils::treatInteger($userData->get('student_id'));
            $targetRec->goods_id          = CommonUtils::treatInteger($userData->get('goods_id'));
            $targetRec->package_type      = CommonUtils::treatInteger($userData->get('package_type'));
            $targetRec->package_start_at  = DateUtils::wellFormToDateTime($userData->get('package_start_at'));
            $targetRec->package_end_at    = DateUtils::wellFormToDateTime($userData->get('package_end_at'));
            $targetRec->lesson_start_at   = DateUtils::wellFormToDateTime($userData->get('lesson_start_at'));
            $targetRec->lesson_end_at     = DateUtils::wellFormToDateTime($userData->get('lesson_end_at'));
            $targetRec->lesson_ticket     = CommonUtils::treatInteger($userData->get('lesson_ticket'));
            $targetRec->assessment_ticket = CommonUtils::treatInteger($userData->get('assessment_ticket'));
            $targetRec->counseling_ticket = CommonUtils::treatInteger($userData->get('counseling_ticket'));
            $targetRec->option_goal       = CommonUtils::treatInteger($userData->get('option_goal'));
            $targetRec->option_review     = CommonUtils::treatInteger($userData->get('option_review'));
            $targetRec->counseling_wday   = CommonUtils::treatInteger($userData->get('counseling_wday'));
            $targetRec->counseling_hour   = CommonUtils::removeEscapeString($userData->get('counseling_hour'));
            $targetRec->counseler_id      = CommonUtils::treatInteger($userData->get('counseler_id'));
            $targetRec->schedule          = CommonUtils::removeEscapeString($userData->get('schedule'));
            $targetRec->purchase_id       = CommonUtils::treatInteger($userData->get('purchase_id'));
            $targetRec->is_deleted        = CommonUtils::treatInteger($userData->get('deleted'));
            $targetRec->created_at        = DateUtils::wellFormToDateTime($userData->get('created_at'));
            $targetRec->updated_at        = DateUtils::wellFormToDateTime($userData->get('updated_at'));
            $targetRec->deleted_at        = DateUtils::wellFormToDateTime($userData->get('deleted_at'));

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });
        $this->logwrite('info', 'user_plan.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'user_plan.csvファイル:インポート終了');

        /*
         * 契約期間範囲の設定
         * user_planにある lesson_start_at、lesson_end_atが 契約開始日/終了日 であるため
         * この値をusersの opened_at、closed_at に設定する。
         *
         * ※users.updated_at は、連携されたusers.MEC内の更新日をセットするため、
         * ここでは更新しない。
         */
        /*
         * 2018-08-31 修正
         * ①planMECレコードは同一ユーザに複数存在する場合がある。
         * ②必ず後の方のレコードが<<現在の契約状況>>を表しているものとして、
         * 契約期間を設定する。
         */

        /*
         * 2018-10-24
         * user_plans.lesson_start_at, lesson_end_at で
         * users.opened_at, closed_at を上書きすることを中止する
         *
        $this->logwrite('info', '会員：契約期間範囲：設定開始');

        $userPlans = UserPlan::where('is_deleted', IsDeletedType::ACTIVE)
                            ->orderby('user_id')
                            ->orderby('id')
                            ->get();
        if(!empty($userPlans) && $userPlans->count() > 0){
            foreach($userPlans as $userPlan){
                User::where('id', $userPlan->user_id)
                    ->update([
                        'opened_at' => $userPlan->lesson_start_at,
                        'closed_at' => $userPlan->lesson_end_at
                    ]);

                $this->logwrite('info', 'user_id:'.$userPlan->user_id
                            .' 契約期間範囲start：'.$userPlan->lesson_start_at
                            .' 契約期間範囲end：'.$userPlan->lesson_end_at
                    );
            }
        }

        $this->logwrite('info', '会員：契約期間範囲：設定終了');
         *
         */
    }

    /**
     * 2019-03-19
     * package.csvをインポートしてpackagesに格納する
     * @param type $dataFolderPath
     */
    private function importPackage($dataFolderPath){
        $this->logwrite('info', 'package.csvファイル:インポート開始');

        // package.csvファイル読み込み
        $csvDataFile = $dataFolderPath.'/package.csv';
        $userPackageDataHeader = $this->getUserPackageDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $userPackageDataHeader);
        $this->logwrite('info', 'package.csvファイル:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'package.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();
        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {
            //検証は不要なのでそのまま返却する
            $userData->put('validStatus', true); //ok
            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'package.csvファイル:データ検証終了');

        //データ出力（全件を出力する）
        $dataCount = 0;
        $this->logwrite('info', 'package.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            /*
             * packagesの登録済みレコードを取得
             * 移行データのpkそのもので取得/更新する
             */
            $pkid = $userData->get('id'); //pkで取得する
            $targetRec = Package::find($pkid);
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new Package();//該当レコードが無い場合は新規作成
            }

            $targetRec->id                      = $userData->get('id');
            $targetRec->package_type            = CommonUtils::treatInteger($userData->get('package_type'));
            //$targetRec->package_name            = CommonUtils::removeEscapeString($userData->get('package_name'));
            $targetRec->package_name            = trim($userData->get('package_name'));
            $targetRec->price                   = CommonUtils::treatInteger($userData->get('price'));
            $targetRec->status                  = CommonUtils::treatInteger($userData->get('status'));
            $targetRec->package_duration        = CommonUtils::treatInteger($userData->get('package_duration'));
            $targetRec->lesson_ticket           = CommonUtils::treatInteger($userData->get('lesson_ticket'));
            $targetRec->assessment_ticket       = CommonUtils::treatInteger($userData->get('assessment_ticket'));
            $targetRec->assessment_only_tciket  = CommonUtils::treatInteger($userData->get('assessment_only_tciket'));
            $targetRec->counseling_ticket       = CommonUtils::treatInteger($userData->get('counseling_ticket'));
            $targetRec->option_goal             = CommonUtils::treatInteger($userData->get('option_goal'));
            $targetRec->option_review           = CommonUtils::treatInteger($userData->get('option_review'));
            $targetRec->in_store_at             = DateUtils::wellFormToDateTime($userData->get('in_store_at'));
            $targetRec->out_store_at            = DateUtils::wellFormToDateTime($userData->get('out_store_at'));
            $targetRec->attention               = CommonUtils::removeEscapeString($userData->get('attention'));
            $targetRec->campaign                = CommonUtils::treatInteger($userData->get('campaign'));
            $targetRec->auto_assessment         = CommonUtils::treatInteger($userData->get('auto_assessment'));
            $targetRec->is_deleted              = CommonUtils::treatInteger($userData->get('deleted'));
            $targetRec->created_at              = DateUtils::wellFormToDateTime($userData->get('created_at'));
            $targetRec->updated_at              = DateUtils::wellFormToDateTime($userData->get('updated_at'));
            $targetRec->deleted_at              = DateUtils::wellFormToDateTime($userData->get('deleted_at'));

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });
        $this->logwrite('info', 'package.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'package.csvファイル:インポート終了');
    }


    /**
     * users.csv, personal.csvをインポートしてusersに格納する
     * @param type $dataFolderPath
     */
    private function importUser($dataFolderPath){
        $this->logwrite('info', 'users.csv, personal.csvファイル:インポート開始');

        // users.csvファイル読み込み
        $userCsvDataFile = $dataFolderPath.'/users.csv';
        $userDataHeader = $this->getUserDataHeader();
        $userCsvDatas = $this->loadCsvData($userCsvDataFile, $userDataHeader);
        $this->logwrite('info', 'users.csvファイル:'.$userCsvDatas->count().'行 読み込み完了');

        // Personalファイル読み込み
        $personalCsvDataFile = $dataFolderPath.'/personal.csv';
        $personalDataHeader = $this->getPersonalDataHeader();
        $personalCsvDatas = $this->loadCsvData($personalCsvDataFile, $personalDataHeader);
        $this->logwrite('info', 'personal.csvファイル:'.$personalCsvDatas->count().'行 読み込み完了');

        //personalデータ検証
        $this->logwrite('info', 'personal.csvファイル:データ検証開始');
        $validPersonalDatas = $this->validateUserData($personalCsvDatas, $this->personalItemProperties);
        $this->logwrite('info', 'personal.csvファイル:データ検証終了');

        //usersデータ検証
        $this->logwrite('info', 'users.csvファイル:データ検証開始');
        $validUserDatas = $this->validateUserData($userCsvDatas, $this->userItemProperties);
        $this->logwrite('info', 'users.csvファイル:データ検証終了');

        //相互検証
        $this->logwrite('info', 'users.csv, personal.csvファイル:相互検証開始');
        $isValidItem = true;
        $validResult = collect();
        $validDatas = $validUserDatas->map(function($userDataRow, $idx) use($validPersonalDatas, &$isValidItem, &$validResult) {

            if(!$userDataRow->get('validStatus')){//OKのもののみ処理を行う。NGはskipする
                return $userDataRow;
            }

            //arugo_user_idの取得
            $arugo_user_id = $userDataRow->get('id');
            if(empty($arugo_user_id)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'arugo_user_id',
                    'value' => $arugo_user_id,
                    'reason' => 'NOT NULL',
                    'data' => $userDataRow
                ];
                $validResult->push($res);
                $userDataRow->put('validStatus', false);
            }
            //$personalDatasを検索して取得
            $personalDataRow = $validPersonalDatas->firstWhere('id', $arugo_user_id);
            if(empty($personalDataRow)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'arugo_user_id',
                    'value' => $arugo_user_id,
                    'reason' => 'NOT RELATED WITH PERSONAL',
                    'data' => $userDataRow
                ];
                $validResult->push($res);
                $userDataRow->put('validStatus', false);
            }else{
                //個人データセット
                $userDataRow->put('first_name',    $personalDataRow->get('first_name'));
                $userDataRow->put('last_name',     $personalDataRow->get('last_name'));
                $userDataRow->put('first_name_en', $personalDataRow->get('first_name_en'));
                $userDataRow->put('last_name_en',  $personalDataRow->get('last_name_en'));
                $userDataRow->put('mail',          $personalDataRow->get('mailaddress'));
                $userDataRow->put('phone_number',  $personalDataRow->get('phone_number'));
                $userDataRow->put('validStatus', true);
            }

            return $userDataRow;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'users.csv, personal.csvファイル:相互検証終了');


        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'users.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            //arugo_user_idの取得
            $arugo_user_id = $userData->get('id');
            //usersの登録済みレコードを取得
            $targetRec = User::where('arugo_user_id', $arugo_user_id)->first();
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new User();//新規
                $defaultPassword = env('NEW_MEMBER_DEFAULT_PASSWORD');
                $targetRec->password = Hash::make($defaultPassword);

            }else{
                $pk = $targetRec->id;
                $targetRec = User::find($pk);//更新
            }

            $targetRec->arugo_user_id   = $userData->get('id');
            //$targetRec->sr_user_id
            //$targetRec->cw_room_id
            //$targetRec->cw_room_name
            $targetRec->first_name    = $userData->get('first_name');
            $targetRec->last_name     = $userData->get('last_name');
            $targetRec->first_name_en = $userData->get('first_name_en');
            $targetRec->last_name_en  = $userData->get('last_name_en');
            $targetRec->mail          = $userData->get('mail');
            $targetRec->phone_number  = $userData->get('phone_number');

            $targetRec->curriculum_id = CommonUtils::treatInteger($userData->get('curriculum_id'));
            $targetRec->study_sec     = CommonUtils::treatInteger($userData->get('study_sec'));
            $targetRec->status        = CommonUtils::treatInteger($userData->get('status'));
            $targetRec->matrix        = CommonUtils::removeEscapeString($userData->get('matrix'));
            $targetRec->opened_at     = DateUtils::wellFormToDateTime($userData->get('open_at'));
            $targetRec->closed_at     = DateUtils::wellFormToDateTime($userData->get('close_at'));
            $targetRec->resigned_at   = DateUtils::wellFormToDateTime($userData->get('resigned_at'));
            $targetRec->user_type     = CommonUtils::treatInteger($userData->get('user_type'));
            //$targetRec->activated
            //$targetRec->quitted
            $targetRec->is_deleted    = $userData->get('deleted');
            $targetRec->created_at    = DateUtils::wellFormToDateTime($userData->get('created_at'));
            $targetRec->updated_at    = DateUtils::wellFormToDateTime($userData->get('updated_at'));
            $targetRec->deleted_at    = DateUtils::wellFormToDateTime($userData->get('deleted_at'));

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });
        $this->logwrite('info', 'users.csvファイル:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'users.csv, personal.csvファイル:インポート終了');
    }

    /**
     * user_scores.csvインポート
     * @param type $dataFolderPath
     */
    private function importUserScore($dataFolderPath){
        $this->logwrite('info', 'user_scores.csvファイル:インポート開始');

        //ファイル読み込み
        $csvDataFile = $dataFolderPath.'/user_scores.csv';
        $dataHeader = $this->getUserScoreDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $dataHeader);
        $this->logwrite('info', 'user_scores.csv:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'user_scores.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();
        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {
            //-- student_idの検証
            //arugo_user_idの取得
            $arugo_user_id = $userData->get('student_id');
            if(empty($arugo_user_id)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //arugo_user_idから、本システムのuser_idを取得
            $userId = '';
            $userRec = User::where('arugo_user_id', $arugo_user_id)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $userId = $userRec->id;
                $userData->put('userId', $userId);  //本システムのuser_id
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'NOT RELATED WITH USERS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }
            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'user_scores.csvファイル:データ検証終了');

        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'user_scores.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            /*
             * user_scoresの登録済みレコードを取得
             *
             * 2018-05-29 修正
             * user_idではなく移行データのpkそのもので取得する
             * したがって、同一ユーザについてのレコードが
             * 履歴的に蓄積されることになる。
             *
             * $userId = $userData->get('userId');
             * $targetRec = UserScore::where('user_id', $userId)->first();
             *
             * if(empty($targetRec) || $targetRec->count() < 1){
             *     $targetRec = new UserScore();//新規
             * }else{
             *     $pk = $targetRec->id;
             *     $targetRec = UserScore::find($pk);//更新
             * }
             *
             */
            $pkid = $userData->get('id'); //pkで取得する
            $targetRec = UserScore::find($pkid);
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new UserScore();//新規
            }
            $targetRec->id                         = $userData->get('id');
            $targetRec->user_id                    = $userData->get('userId');
            $targetRec->alugo_user_id              = CommonUtils::treatInteger($userData->get('student_id'));
            $targetRec->assessment_alugo_stuff_id  = CommonUtils::treatInteger($userData->get('assessment_teacher_id'));
            $targetRec->feedback_alugo_stuff_id    = CommonUtils::treatInteger($userData->get('feedback_teacher_id'));
            $targetRec->count                      = CommonUtils::treatInteger($userData->get('count'));
            $targetRec->level                      = CommonUtils::treatInteger($userData->get('level'));
            $targetRec->report                     = CommonUtils::removeEscapeString($userData->get('report'));
            $targetRec->result                     = CommonUtils::removeEscapeString($userData->get('result'));
            $targetRec->assessment_started_at      = DateUtils::wellFormToDateTime($userData->get('assessment_start_at'));
            $targetRec->assessment_ended_at        = DateUtils::wellFormToDateTime($userData->get('assessment_end_at'));
            $targetRec->feedback_started_at        = DateUtils::wellFormToDateTime($userData->get('feedback_start_at'));
            $targetRec->feedback_ended_at          = DateUtils::wellFormToDateTime($userData->get('feedback_end_at'));
            $targetRec->comment                    = '';
            $targetRec->is_deleted                 = $userData->get('deleted');
            $targetRec->created_at                 = DateUtils::wellFormToDateTime($userData->get('created_at'));
            $targetRec->updated_at                 = DateUtils::wellFormToDateTime($userData->get('updated_at'));
            $targetRec->deleted_at                 = DateUtils::wellFormToDateTime($userData->get('deleted_at'));

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });
        $this->logwrite('info', 'user_scores.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'user_scores.csvファイル:インポート終了');
    }

    /**
     * ユーザーゴール関連
     * @param type $dataFolderPath
     */
    private function importUserGoal($dataFolderPath){
        $this->logwrite('info', 'user_goals.csvファイル:インポート開始');

        //ファイル読み込み
        $csvDataFile = $dataFolderPath.'/user_goals.csv';
        $dataHeader = $this->getUserGoalDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $dataHeader);
        $this->logwrite('info', 'user_goals.csvファイル:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'user_goals.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();
        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {
            //-- student_idの検証
            //arugo_user_idの取得
            $arugo_user_id = $userData->get('student_id');
            if(empty($arugo_user_id)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //arugo_user_idから、本システムのuser_idを取得
            $userId = '';
            $userRec = User::where('arugo_user_id', $arugo_user_id)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $userId = $userRec->id;
                $userData->put('userId', $userId);  //本システムのuser_id
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'NOT RELATED WITH USERS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }
            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'user_goals.csvファイル:データ検証終了');

        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'user_goalsファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            //user_goalsの登録済みレコードを取得
            $pkid = $userData->get('id'); //pkで取得する
            $targetRec = UserGoal::find($pkid);
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new UserGoal();//新規
            }

            $targetRec->id          = $userData->get('id');
            $targetRec->user_id     = $userData->get('userId');
            $targetRec->target      = CommonUtils::removeEscapeString($userData->get('target'));
            $targetRec->level       = CommonUtils::treatInteger($userData->get('level'));
            $targetRec->grade       = CommonUtils::removeEscapeString($userData->get('grade'));
            $targetRec->goal_limit  = CommonUtils::removeEscapeString($userData->get('goal_limit'));
            $targetRec->scene       = CommonUtils::removeEscapeString($userData->get('scene'));
            $targetRec->is_deleted  = $userData->get('deleted');
            $targetRec->created_at  = DateUtils::wellFormToDateTime($userData->get('created_at'));
            $targetRec->updated_at  = DateUtils::wellFormToDateTime($userData->get('updated_at'));
            $targetRec->deleted_at  = DateUtils::wellFormToDateTime($userData->get('deleted_at'));

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });
        $this->logwrite('info', 'user_goals.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'user_goals.csvファイル:インポート終了');
    }

    /**
     * レッスンログ関連
     * @param type $dataFolderPath
     */
    private function importUserLessonLog($dataFolderPath){
        $this->logwrite('info', 'user_lesson.csvファイル:インポート開始');

        //ファイル読み込み
        $csvDataFile = $dataFolderPath.'/user_lesson.csv';
        $dataHeader = $this->getUserLessonDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $dataHeader);
        $this->logwrite('info', 'user_lesson.csv:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'user_lesson.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();
        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {

            //-- student_idの検証
            //arugo_user_idの取得
            $arugo_user_id = $userData->get('student_id');
            //$this->logwrite('debug', 'user_lesson.csvファイル:データ検証: '. ($idx + 1) . ' student_id:'. $arugo_user_id);
            if(empty($arugo_user_id)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //arugo_user_idから、本システムのuser_idを取得
            $userId = '';
            $userRec = User::where('arugo_user_id', $arugo_user_id)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $userId = $userRec->id;
                $userData->put('userId', $userId);  //本システムのuser_id
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'NOT RELATED WITH USERS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }

/*** 実施しない
            //-- teacher_idの検証 ('\N'もありうる)
            $arugo_teacher_id = $userData->get('teacher_id');
            if(empty($arugo_teacher_id)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'teacher_id',
                    'value' => $arugo_teacher_id,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //$arugo_teacher_id、本システムのuaccount_idを取得
            $accountId = '';
            $userRec = UserXXXXX::where('arugo_user_id', $arugo_teacher_id)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $accountId = $userRec->id;
                $userData->put('accountId', $accountId);  //本システムのaccountId
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'teacher_id',
                    'value' => $arugo_teacher_id,
                    'reason' => 'NOT RELATED WITH ACCOUNTS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }
***/
            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'user_lesson.csvファイル:データ検証終了');


        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'user_lesson.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            //user_lesson_logsの登録済みレコードを取得
            $pkid = $userData->get('id'); //pkで取得
            //$this->logwrite('debug', 'user_lesson.csvファイル: idx:'.$idx . ' id:'.$pkid);
            $targetRec = UserLessonLog::find($pkid);
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new UserLessonLog();//新規
                $this->logwrite('info', '新規');
            }

            $targetRec->id                = $userData->get('id');
            $targetRec->user_id           = $userData->get('userId');
            //$targetRec->account_id        = $userData->get('accountId'); //必要？？？
            $targetRec->alugo_teacher_id  = CommonUtils::treatInteger($userData->get('teacher_id'));
            $targetRec->alugo_user_id     = CommonUtils::treatInteger($userData->get('student_id'));
            $targetRec->start_at          = DateUtils::wellFormToDateTime($userData->get('start_at'));
            $targetRec->end_at            = DateUtils::wellFormToDateTime($userData->get('end_at'));
            $targetRec->report            = CommonUtils::removeEscapeString($userData->get('report'));
            $targetRec->lesson_id         = CommonUtils::treatInteger($userData->get('lesson_id'));
            $targetRec->today_feedback    = CommonUtils::removeEscapeString($userData->get('today_feedback'));
            $targetRec->message_user      = CommonUtils::removeEscapeString($userData->get('message_user'));
            $targetRec->share_coach       = CommonUtils::removeEscapeString($userData->get('share_coach'));
            $targetRec->approved_at       = DateUtils::wellFormToDateTime($userData->get('approved_at'));
            $targetRec->lesson_type       = CommonUtils::treatInteger($userData->get('lesson_type'));
            $targetRec->canceled          = CommonUtils::treatInteger($userData->get('canceled'));
            $targetRec->canceled_sec      = CommonUtils::treatInteger($userData->get('canceled_sec'));
            $targetRec->request_sec       = CommonUtils::treatInteger($userData->get('request_sec'));
            $targetRec->is_deleted        = $userData->get('deleted');
            $targetRec->created_at        = DateUtils::wellFormToDateTime($userData->get('created_at'));
            $targetRec->updated_at        = DateUtils::wellFormToDateTime($userData->get('updated_at'));
            $targetRec->deleted_at        = DateUtils::wellFormToDateTime($userData->get('deleted_at'));

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });
        $this->logwrite('info', 'user_lesson.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'user_lesson.csvファイル:インポート終了');
    }

    /**
     * questionnaire関連
     * @param type $dataFolderPath
     */
    private function importQuestionnaire($dataFolderPath){
        $this->logwrite('info', 'questionnaire.csvファイル:インポート開始');

        //ファイル読み込み
        $csvDataFile = $dataFolderPath.'/questionnaire.csv';
        $dataHeader = $this->getQuestionnaireDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $dataHeader);
        $this->logwrite('info', 'questionnaire.csv:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'questionnaire.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();
        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {

            //-- lesson_idの検証

            //-- student_idの検証
            //arugo_user_idの取得
            $arugo_user_id = $userData->get('student_id');
            //$this->logwrite('debug', 'questionnaire.csvファイル:データ検証 : student_id:' . $arugo_user_id);
            if(empty($arugo_user_id)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //arugo_user_idから、本システムのuser_idを取得
            $userId = '';
            $userRec = User::where('arugo_user_id', $arugo_user_id)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $userId = $userRec->id;
                $userData->put('userId', $userId);  //本システムのuser_id
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'NOT RELATED WITH USERS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }
/***
            //-- teacher_idの検証 ('\N'もありうる)
            $arugo_teacher_id = $userData->get('teacher_id');
            if(empty($arugo_teacher_id)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'teacher_id',
                    'value' => $arugo_teacher_id,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //$arugo_teacher_id、本システムのuaccount_idを取得
            $accountId = '';
            $userRec = UserXXXXX::where('arugo_user_id', $arugo_teacher_id)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $accountId = $userRec->id;
                $userData->put('accountId', $accountId);  //本システムのaccountId
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'teacher_id',
                    'value' => $arugo_teacher_id,
                    'reason' => 'NOT RELATED WITH ACCOUNTS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }
***/
            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'questionnaire.csvファイル:データ検証終了');


        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'questionnaire.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            //user_questionnairesの登録済みレコードを取得
            $pkid = $userData->get('id'); //pkで取得
            $targetRec = UserQuestionnarie::find($pkid);
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new UserQuestionnarie();//新規
            }


            $targetRec->id                = $userData->get('id');
            $targetRec->user_id           = $userData->get('userId');
            //$targetRec->account_id        = $userData->get('accountId');//必要？？？
            $targetRec->alugo_teacher_id  = CommonUtils::treatInteger($userData->get('teacher_id'));
            $targetRec->alugo_user_id     = CommonUtils::treatInteger($userData->get('student_id'));
            $targetRec->lesson_id         = CommonUtils::treatInteger($userData->get('lesson_id'));
            $targetRec->evaluation        = CommonUtils::treatInteger($userData->get('evaluation'));
            $targetRec->comment           = CommonUtils::removeEscapeString($userData->get('comment'));
            $targetRec->is_deleted        = 0;
            $targetRec->created_at        = DateUtils::wellFormToDateTime($userData->get('created_at'));
            $targetRec->updated_at        = null;
            $targetRec->deleted_at        = null;

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });

        $this->logwrite('info', 'questionnaire.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'questionnaire.csvファイル:インポート終了');
    }

    /**
     * cc_session_detail関連
     * @param type $dataFolderPath
     */
    private function importCcSessionDetail($dataFolderPath){
        $this->logwrite('info', 'cc_session_detail.csvファイル:インポート開始');

        //ファイル読み込み
        $csvDataFile = $dataFolderPath.'/cc_session_detail.csv';
        $dataHeader = $this->getCcSessionDetailDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $dataHeader);
        $this->logwrite('info', 'cc_session_detail.csv:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'cc_session_detail.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();
        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {
            //-- session_id
            //-- lesson_id
            //-- module_id
            //-- stage_id
            //-- unit_id
            //-- question_id

            // feedback_input 内のjsonから英文を抜き出す
            $feedbackInputText = '';
            $feedbackInputJsonText = $userData->get('feedback_input');
            if(!empty($feedbackInputJsonText)){
                //特殊文字を除去する(json_decodeの前に行う)
                $feedbackInputJsonText = CommonUtils::removeEscapeString($feedbackInputJsonText);
                $feedbackJsonObj = json_decode($feedbackInputJsonText, true);
                //2019-02-12 improved_version から your_sentence に変更
                if( ! empty($feedbackJsonObj) &&
                    is_array($feedbackJsonObj) &&
                    array_key_exists('your_sentence', $feedbackJsonObj)){
                    $feedbackInputText = $feedbackJsonObj['your_sentence'];
                }
            }
            $userData->put('feedbackInputText', $feedbackInputText);

            $userData->put('validStatus', true); //ok

            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'cc_session_detail.csvファイル:データ検証終了');


        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'cc_session_detail.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            //user_sessionsの登録済みレコードを取得
            $pkid = $userData->get('id'); //pkで取得
            $targetRec = UserSession::find($pkid);
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new UserSession();//新規
            }

            $targetRec->id              = $userData->get('id');
            $targetRec->session_id      = CommonUtils::treatInteger($userData->get('session_id'));
            $targetRec->lesson_id       = CommonUtils::treatInteger($userData->get('lesson_id'));
            $targetRec->module_id       = CommonUtils::treatInteger($userData->get('module_id'));
            $targetRec->stage_id        = CommonUtils::treatInteger($userData->get('stage_id'));
            $targetRec->unit_id         = CommonUtils::treatInteger($userData->get('unit_id'));
            $targetRec->question_id     = CommonUtils::treatInteger($userData->get('question_id'));
            $targetRec->result          = CommonUtils::treatInteger($userData->get('result'));
            $targetRec->unit_order      = CommonUtils::treatInteger($userData->get('unit_order'));
            $targetRec->subject_id      = CommonUtils::treatInteger($userData->get('subject_id'));
            $targetRec->feedback_input  = CommonUtils::removeEscapeString($userData->get('feedbackInputText'));
            $targetRec->unit_type       = CommonUtils::treatInteger($userData->get('unit_type'));
            $targetRec->hearing_id      = CommonUtils::treatInteger($userData->get('hearing_id'));
            $targetRec->is_deleted      = $userData->get('deleted');
            $targetRec->created_at      = DateUtils::wellFormToDateTime($userData->get('created_at'));
            $targetRec->updated_at      = DateUtils::wellFormToDateTime($userData->get('updated_at'));
            $targetRec->deleted_at      = DateUtils::wellFormToDateTime($userData->get('deleted_at'));

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });

        $this->logwrite('info', 'cc_session_detail.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'cc_session_detail.csvファイル:インポート終了');
    }


    /**
     * sr_topics関連
     * @param type $dataFolderPath
     */
    private function importSrTopics($dataFolderPath){
        $this->logwrite('info', 'sr_topics.csvファイル:インポート開始');

        //ファイル読み込み
        $csvDataFile = $dataFolderPath.'/sr_topics.csv';
        if(!file_exists($csvDataFile)){
            $this->logwrite('warn', 'sr_topics.csvファイル無し');
            return;
        }

        $dataHeader = $this->getSrTopicsDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $dataHeader);
        $this->logwrite('info', 'sr_topics.csv:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'sr_topics.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();

        //sr_categoriesは件数が少ないため、動作速度向上のためにメモリ中に保持しておく。
        $srCategoryMaster = collect();
        $srCategories = SrCategory::orderby('id')->get();
        foreach($srCategories as $srCategory){
            $srCategoryMaster->put($srCategory->category_name, $srCategory->id);
        }

        $validDatas = $csvDatas->map(function($userData, $idx) use($srCategoryMaster, &$isValidItem, &$validResult) {
            //-- category
            //category文言から、本システムのsr_category_idを取得する
            $categoryName = $userData->get('category');
            if(empty($categoryName)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'category',
                    'value' => $categoryName,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }

            //メモリ内でのサーチ
            $categoryId = $srCategoryMaster->get($categoryName);
            if(!empty($categoryId)){
                $userData->put('categoryId', $categoryId);  //本システムのsr_category_id
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'category',
                    'value' => $categoryName,
                    'reason' => 'NOT RELATED WITH SR_CATEGORIES',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }
            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'sr_topics.csvファイル:データ検証終了');


        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'sr_topics.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            //speaking_ralliesの登録済みレコードを取得
            $pkid = $userData->get('id'); //pkで取得
            $targetRec = SpeakingRally::find($pkid);
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new SpeakingRally();//新規
            }

            $targetRec->id                = $userData->get('id');
            $targetRec->sr_category_id    = CommonUtils::treatInteger($userData->get('categoryId'));
            $targetRec->context           = CommonUtils::removeEscapeString($userData->get('text'));
            $targetRec->created_stuff_id  = CommonUtils::treatInteger($userData->get('created_staff_id'));
            $targetRec->opened            = CommonUtils::treatInteger($userData->get('opened'));
            $targetRec->is_deleted        = $userData->get('deleted');
            $targetRec->created_at        = DateUtils::wellFormToDateTime($userData->get('created'));
            $targetRec->updated_at        = DateUtils::wellFormToDateTime($userData->get('modified'));
            $targetRec->deleted_at        = null;

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });

        $this->logwrite('info', 'sr_topics.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'sr_topics.csvファイル:インポート終了');
    }

    /**
     * sr_users関連
     * sr_usersよりsr_user_idを取得してusersに設定する
     * @param type $dataFolderPath
     */
    private function importSrUsers($dataFolderPath){
        $this->logwrite('info', 'sr_users.csvファイル:インポート開始');

        //ファイル読み込み
        $csvDataFile = $dataFolderPath.'/sr_users.csv';
        if(!file_exists($csvDataFile)){
            $this->logwrite('warn', 'sr_users.csvファイル無し');
            return;
        }

        $dataHeader = $this->getSrUsersDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $dataHeader);
        $this->logwrite('info', 'sr_users.csv:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'sr_users.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();

        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {
            //-- mailアドレスでの連携検証
            //mailアドレス取得
            $mail = $userData->get('mail');
            if(empty($mail)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'mail',
                    'value' => $mail,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //mailから、本システムのuser_idを特定する
            $userId = '';
            $userRec = User::where('mail', $mail)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $userId = $userRec->id;
                $userData->put('userId', $userId);  //本システムのuser_id
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'mail',
                    'value' => $mail,
                    'reason' => 'NOT RELATED WITH USERS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }
            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'sr_users.csvファイル:データ検証終了');


        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'sr_users.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            /*
             * 本件ではレコード追加ではなく、
             * 該当するusersテーブルのsr_user_idを更新するものである。
             */
            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            //usersの登録済みレコードを取得
            $pkid = $userData->get('userId'); //pkで取得
            $targetRec = User::find($pkid);
            if(empty($targetRec) || $targetRec->count() < 1){
                return true; // continue
            }

            $targetRec->sr_user_id = CommonUtils::treatInteger($userData->get('id'));//sr_user_id
            $targetRec->save();//updated_atは自動更新
            $dataCount++;
        });

        $this->logwrite('info', 'sr_users.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'sr_users.csvファイル:インポート終了');
    }

    /**
     * user_outputs関連
     * user_outputsテーブルは合成データであるため、
     * 1. sr_answers.csvを移行
     * 2. user_lesson.csvを移行
     * の2段階で行う。
     */
    private function importToUserOutputs($dataFolderPath){
        $this->logwrite('info', 'user_outputs関連:インポート開始');

        /*
         * 1. sr_answers.csvを移行
         */
        /*
         *  2018-11-02 sr_answers.csvは移行不要のためコメントアウト
         *
        $this->logwrite('info', 'user_outputs関連:sr_answers.csvファイル:インポート開始');

        //ファイル読み込み
        $csvDataFile = $dataFolderPath.'/sr_answers.csv';
        if(!file_exists($csvDataFile)){
            $this->logwrite('warn', 'sr_answers.csvファイル無し');
            return;
        }

        $dataHeader = $this->getSrAnswersDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $dataHeader);
        $this->logwrite('info', 'sr_answers.csv:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'sr_answers.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();

        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {
            //-- sr_user_idの連携検証
            //sr_user_id取得
            $srUserId = $userData->get('user_id');
            if(empty($srUserId)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'user_id',
                    'value' => $srUserId,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //sr_user_idから、本システムのuser_idを特定する
            $userId = '';
            $userRec = User::where('sr_user_id', $srUserId)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $userId = $userRec->id;
                $userData->put('userId', $userId);  //本システムのuser_id
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'sr_user_id',
                    'value' => $srUserId,
                    'reason' => 'NOT RELATED WITH USERS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }
            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'sr_answers.csvファイル:データ検証終了');


        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'sr_answers.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            //approved_dateを整形する
            $approvedDate = null;
            $d = $userData->get('approved_date');
            if(!empty($d) && DateUtils::validateDateTimeString($d)){
                $approvedDate = DateUtils::convertToYYMMDDHHMMSS_HyphenDelimited($d);
                if(empty($approvedDate)){
                    $approvedDate = null;
                }
            }

            //user_outputsの登録済みレコードを取得
            $userId = $userData->get('userId'); //userIdで取得
            $targetRec = UserOutput::where('data_type', 1)
                                    ->where('user_id', $userId)
                                    ->first();
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new UserOutput();//新規
            }

            //$targetRec->id			= $userData->get('id');// id(pk)は引き継がない
            $targetRec->data_type		= 1;
            $targetRec->user_id			= CommonUtils::treatInteger($userData->get('userId'));
            $targetRec->activated_at    = $approvedDate;
            $targetRec->category        = CommonUtils::removeEscapeString($userData->get('topic_category'));
            $targetRec->topic           = CommonUtils::removeEscapeString($userData->get('free_topic'));
            $targetRec->original_answer = CommonUtils::removeEscapeString($userData->get('original_answer'));
            $targetRec->revised_answer  = CommonUtils::removeEscapeString($userData->get('revised_answer'));
            $targetRec->result          = CommonUtils::removeEscapeString($userData->get('coach_answer'));
            $targetRec->comment         = CommonUtils::removeEscapeString($userData->get('coach_message'));
            $targetRec->is_deleted      = $userData->get('deleted');
            $targetRec->created_at      = DateUtils::wellFormToDateTime($userData->get('created'));
            $targetRec->updated_at      = DateUtils::wellFormToDateTime($userData->get('modified'));
            $targetRec->deleted_at      = null;

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });

        $this->logwrite('info', 'sr_answers.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'user_outputs関連:sr_answers.csvファイル:インポート終了');
         *
         *  2018-11-02 sr_answers.csvは移行不要のためコメントアウト
         */


        /*
         * 2. user_lesson.csvを移行
         */
        $this->logwrite('info', 'user_outputs関連:user_lesson.csvファイル:インポート開始');

        //ファイル読み込み
        $csvDataFile = $dataFolderPath.'/user_lesson.csv';
        if(!file_exists($csvDataFile)){
            $this->logwrite('warn', 'user_lesson.csvファイル無し');
            return;
        }

        $dataHeader = $this->getUserLessonDataHeader();
        $csvDatas = $this->loadCsvData($csvDataFile, $dataHeader);
        $this->logwrite('info', 'user_lesson.csv:'.$csvDatas->count().'行 読み込み完了');

        //検証
        $this->logwrite('info', 'user_lesson.csvファイル:データ検証開始');
        $isValidItem = true;
        $validResult = collect();
        $validDatas = $csvDatas->map(function($userData, $idx) use(&$isValidItem, &$validResult) {
            //-- student_idの検証
            //arugo_user_idの取得
            $arugo_user_id = $userData->get('student_id');
            if(empty($arugo_user_id)){
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'EMPTY',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false);
                return $userData;
            }
            //arugo_user_idから、本システムのuser_idを取得
            $userId = '';
            $userRec = User::where('arugo_user_id', $arugo_user_id)->first();
            if(!empty($userRec) && $userRec->count() > 0){
                $userId = $userRec->id;
                $userData->put('userId', $userId);  //本システムのuser_id
                $userData->put('validStatus', true); //ok
            }else{
                $isValidItem = false;//ng
                $res = [
                    'line' => $idx + 1,
                    'name' => 'student_id',
                    'value' => $arugo_user_id,
                    'reason' => 'NOT RELATED WITH USERS',
                    'data' => $userData
                ];
                $validResult->push($res);
                $userData->put('validStatus', false); //ng
            }

            return $userData;
        });
        if(!$isValidItem){//エラーあり
            $this->putValidResultLog($validResult);
        }
        $this->logwrite('info', 'user_lesson.csvファイル:データ検証終了');


        //データ出力（$userData->validStatus = trueのもののみを出力する）
        $dataCount = 0;
        $this->logwrite('info', 'user_lesson.csvファイル:データ出力開始');
        $validDatas->each(function($validData, $idx) use(&$dataCount){

            if(!$validData->get('validStatus')){
                return true; // continue
            }

            //'\N'をnullに整形する
            $userData = $this->wellFormedNull($validData);

            //approved_dateを整形する
            $approvedDate = null;
            $d = $userData->get('approved_date');
            if(!empty($d) && DateUtils::validateDateTimeString($d)){
                $approvedDate = DateUtils::convertToYYMMDDHHMMSS_HyphenDelimited($d);
                if(empty($approvedDate)){
                    $approvedDate = null;
                }
            }

            //user_outputsの登録済みレコードを取得
            $userId = $userData->get('userId'); //userIdで取得
            $targetRec = UserOutput::where('data_type', 2)
                                    ->where('user_id', $userId)
                                    ->first();
            if(empty($targetRec) || $targetRec->count() < 1){
                $targetRec = new UserOutput();//新規
            }

            //$targetRec->id			= $userData->get('id');// id(pk)は引き継がない
            $targetRec->data_type		= 2;
            $targetRec->user_id			= CommonUtils::treatInteger($userData->get('userId'));
            $targetRec->activated_at    = $approvedDate;
            $targetRec->category        = null;
            $targetRec->topic           = null;
            $targetRec->original_answer = CommonUtils::removeEscapeString($userData->get('today_feedback'));
            $targetRec->revised_answer  = null;
            $targetRec->result          = null;
            $targetRec->comment         = null;
            $targetRec->is_deleted      = $userData->get('deleted');
            $targetRec->created_at      = DateUtils::wellFormToDateTime($userData->get('created'));
            $targetRec->updated_at      = DateUtils::wellFormToDateTime($userData->get('modified'));
            $targetRec->deleted_at      = null;

            $targetRec->timestamps = false; //updated_atを自動更新しない
            $targetRec->save();
            $dataCount++;
        });
        $this->logwrite('info', 'user_lesson.csv:データ出力終了: '.$dataCount.'件');
        $this->logwrite('info', 'user_outputs関連:user_lesson.csvファイル:インポート終了');
        $this->logwrite('info', 'user_outputs関連:インポート終了');
    }


}
