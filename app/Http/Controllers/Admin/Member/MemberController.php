<?php
namespace App\Http\Controllers\Admin\Member;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

use App\Http\Controllers\BaseViewController;
use App\Exceptions\AppException;

use App\Enums\MenuNameType;
use App\Enums\SessionValiableType;
use App\Enums\AccountKindType;
use App\Enums\IsDeletedType;

use App\Models\LpCategory;
use App\Models\UserLearningPolicy;
use App\Models\MemberReport;

use Carbon\Carbon;

use MemberService;
use S3Service;
use SnappyPdf;

/**
 * MemberController
 */
class MemberController extends BaseViewController {

    public function index($backToList=false)
	{
        $this->view_params->put('menuName', MenuNameType::MENU_MEMBER);
        if($backToList == 'back'){
            $this->view_params->put('backToList', true);
            //前回検索条件をセッションから取得
            $params = unserialize(session()->get(SessionValiableType::SEARCH_CONDITION_MEMBER));
            $this->view_params->put('searchCondition', $params);
        }else{
            $this->view_params->put('backToList', false);
            $this->view_params->put('searchCondition', null);
        }
		return view('Admin.Member.index')->with('view_params', $this->view_params);
	}

    /**
     * 会員詳細:Top
     */
    public function memberDetail(Request $request)
    {
        $this->view_params->put('menuName', MenuNameType::MENU_MEMBER);
        $userId = $request->get('userId');
        $params = ['userId' => $userId];
        $memberDetail = MemberService::getMemberDetail($params);
        $this->view_params->put('memberDetail', $memberDetail);
        session([SessionValiableType::MEMBER_DETAIL_INFO => $memberDetail]);

        //学習方針関連
        //親カテゴリ取得
        $lpCategory1s = MemberService::getUserLearningPolicyCategory();
        $this->view_params->put('lp_category1s', $lpCategory1s);

		return view('Admin.Member.member_detail', $this->view_params)->with('view_params', $this->view_params);
    }
    /**
     * 会員詳細:学習方針履歴
     */
    public function learningPolicy($arugoUserId)
    {
        $this->view_params->put('menuName', MenuNameType::MENU_MEMBER);
		return view('Admin.Member.learning_policy')->with('view_params', $this->view_params);
    }
    /**
     * 会員詳細:OUTPUT履歴
     */
    public function memberOutput($arugoUserId)
    {
        $this->view_params->put('menuName', MenuNameType::MENU_MEMBER);
		return view('Admin.Member.member_output')->with('view_params', $this->view_params);
    }
    /**
     * 会員詳細:出題カレンダー
     */
    public function taskCalendar($userId)
    {
        $this->view_params->put('menuName', MenuNameType::MENU_MEMBER);

        //アクセス許可を判定
        $loginUser = parent::getLoginUser();
        $loginUserAccountKindId = $loginUser['account_kind_id'];
        if( $loginUserAccountKindId == AccountKindType::ACCOUNT_COUNSELOR_ID ||
            $loginUserAccountKindId == AccountKindType::ACCOUNT_OPERATOR_ID ){
            //権限チェック
            $params = [
                'loginUser' => $loginUser,
                'userId' => $userId
            ];
            $isAccessible = MemberService::checkPermissionForMember($params);
            if(!$isAccessible){
                throw new AppException('アクセスが許可されていません。', 403);
            }
        }

        //指定された会員の基本情報を返却
        $params = ['userId' => $userId];
        $memberDetail = MemberService::getMemberBasic($params);
        $this->view_params->put('memberBasic', $memberDetail);
        session([SessionValiableType::MEMBER_BASIC_INFO => $memberDetail]);

        //指定された会員の出題カレンダー情報を返却
        $taskCalendar = MemberService::getTaskCalendar($params);
        $this->view_params->put('taskCalendar', $taskCalendar);

		return view('Admin.Member.task_calendar')->with('view_params', $this->view_params);
    }
    /**
     * @deprecated
     * 会員詳細:メッセージ履歴
     */
    public function messageHistory($arugoUserId)
    {
        $this->view_params->put('menuName', MenuNameType::MENU_MEMBER);
		return view('Member.message_history')->with('view_params', $this->view_params);
    }

    /**
     * 会員レポート
     */
    public function memberReport(Request $request)
    {
        $userId = $request->get('userId');
        $memberReportIssue = $request->get('memberReportIssue');

        //アクセス許可を判定
        $loginUser = parent::getLoginUser();
        $loginUserAccountKindId = $loginUser['account_kind_id'];
        if( $loginUserAccountKindId == AccountKindType::ACCOUNT_COUNSELOR_ID ||
            $loginUserAccountKindId == AccountKindType::ACCOUNT_OPERATOR_ID ){
            //権限チェック
            $params = [
                'loginUser' => $loginUser,
                'userId' => $userId
            ];
            $isAccessible = MemberService::checkPermissionForMember($params);
            if(!$isAccessible){
                throw new AppException('アクセスが許可されていません。', 403);
            }
        }

        //指定された会員の基本情報
        $params = [
            'userId' => $userId,
            'memberReportIssue' => $memberReportIssue
        ];
        //指定された発行号数のレポート情報取得
        $memberReportInfo = MemberService::getMemberReportInfo($params);
        $this->view_params->put('memberReportInfo', $memberReportInfo);

		return view('Admin.Member.member_report')->with('view_params', $this->view_params);
//		return view('Member.member_report_pdf')->with('view_params', $this->view_params);
    }

    /**
     * 会員レポートPdf
     */
    public function memberReportPdf($userId, $issueDate='')
    {
        //アクセス許可を判定
        $loginUser = parent::getLoginUser();
        $loginUserAccountKindId = $loginUser['account_kind_id'];
        if( $loginUserAccountKindId == AccountKindType::ACCOUNT_COUNSELOR_ID ||
            $loginUserAccountKindId == AccountKindType::ACCOUNT_OPERATOR_ID ){
            //権限チェック
            $params = [
                'loginUser' => $loginUser,
                'userId' => $userId
            ];
            $isAccessible = MemberService::checkPermissionForMember($params);
            if(!$isAccessible){
                throw new AppException('アクセスが許可されていません。', 403);
            }
        }

        //指定された会員＋発行日のレポートPDFを取得
        $memberReport = MemberReport::where('is_deleted', IsDeletedType::ACTIVE)
                                    ->where('user_id', $userId)
                                    ->whereDate('report_created_at', $issueDate)
                                    ->first();
        if(!empty($memberReport)){
            $arugoUserId = $memberReport->arugo_user_id;
            $reportName = $memberReport->report_name;

            $s3Key = $memberReport->report_s3_key;
            $pdfObj = S3Service::getObject($s3Key);
            $pdfData = $pdfObj['Body'];

            $filename = $arugoUserId . '_' . '会員レポート_' . $reportName.'.pdf';
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ];
            return Response::make($pdfData, 200, $headers);
        }
    }

}
