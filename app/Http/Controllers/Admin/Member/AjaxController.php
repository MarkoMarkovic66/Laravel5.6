<?php
namespace App\Http\Controllers\Admin\Member;

use Illuminate\Http\Request;
use App\Exceptions\AppException;
use App\Http\Controllers\AjaxBaseController;
use App\Enums\SessionValiableType;

use App\Utils\DateUtils;

use MemberService;
use View;

/**
 * AjaxController
 */
class AjaxController extends AjaxBaseController {
    public function __construct() {
        parent::__construct();
    }

    public function memberSearch(Request $request) {

        $loginAccountInfo = parent::getLoginUser();
        $params = collect();
        $errorMessages = [];

        if( ! $this->validateSearchConditions($request, $params, $errorMessages)){
            //検索条件バリデーションエラー
            return parent::responseJsonStatus(false, $errorMessages);
        }

        //検索条件をセッションに保持
        session([SessionValiableType::SEARCH_CONDITION_MEMBER => serialize($params)]);

        //会員一覧リスト取得
        $searchResult = MemberService::doMemberSearch($loginAccountInfo, $params);
        $memberList = $searchResult['memberList'];
        $totalRowCount  = $searchResult['totalRowCount'];
        $totalPageCount = $searchResult['totalPageCount'];
        $currentPageNo  = $params->get('pageNo');

        //ページネーション表示
        $pageBlade = View::make('layouts.member-paging',
                [
                    'totalRowCount' => number_format($totalRowCount),
                    'totalPageCount' => $totalPageCount,
                    'currentPageNo' => $currentPageNo,
                ])->render();

        $result = [
            'totalRowCount'  => $totalRowCount,
            'totalPageCount' => $totalPageCount,
            'currentPageNo'  => $currentPageNo,
            'pageBlade' => $pageBlade,
            'count' => count($memberList),
            'memberList' => $memberList,
        ];
        return parent::responseJsonData(true, $result);
    }

    /**
     * 検索条件バリデーション
     *
     * @param Request $request
     * @param collection $params
     * @param array $errorMessage
     * @return boolean
     */
    private function validateSearchConditions($request, &$params, &$errorMessage){

        $memberId        = empty($request->get('memberId'))        ? '': $request->get('memberId');
        $memberSrId      = empty($request->get('memberSrId'))      ? '': $request->get('memberSrId');
        $memberName      = empty($request->get('memberName'))      ? '': $request->get('memberName');
        $cwRoomId        = empty($request->get('cwRoomId'))        ? '': $request->get('cwRoomId');
        $cwRoomName      = empty($request->get('cwRoomName'))      ? '': $request->get('cwRoomName');
        $mail            = empty($request->get('mail'))            ? '': $request->get('mail');
        $phoneNumber     = empty($request->get('phoneNumber'))     ? '': $request->get('phoneNumber');
        $pageNo          = empty($request->get('pageNo'))          ?  1: $request->get('pageNo');

        $hasMemberReport = false;
        if(!empty($request->get('hasMemberReport') && $request->get('hasMemberReport') == 'true')){
            $hasMemberReport = true;
        }

        $isValid = true;

        $params->put('memberId', $memberId);
        $params->put('memberSrId', $memberSrId);
        $params->put('memberName', $memberName);
        $params->put('cwRoomId', $cwRoomId);
        $params->put('cwRoomName', $cwRoomName);
        $params->put('mail', $mail);
        $params->put('phoneNumber', $phoneNumber);
        $params->put('hasMemberReport', $hasMemberReport);
        $params->put('pageNo', $pageNo);

        return $isValid;
    }

    /**
     * @deprecated
     * 会員詳細：基本情報更新
     * @param Request $request
     * @return type
     */
    public function memberBasicinfoUpdate(Request $request) {
        $userId      = empty($request->get('userId'))      ? '': $request->get('userId');
        $cwRoomId    = empty($request->get('cwRoomId'))    ? '': $request->get('cwRoomId');
        $cwAccountId = empty($request->get('cwAccountId')) ? '': $request->get('cwAccountId');

        if(strlen($cwRoomId) > 0 && !is_numeric($cwRoomId)){
            $errorMessages[] = 'CWルームIDは数値で指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }
        if(strlen($cwAccountId) > 0 && !is_numeric($cwAccountId)){
            $errorMessages[] = 'CW担当者IDは数値で指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }

        $params = collect();
        $params->put('userId', $userId);
        $params->put('cwRoomId', $cwRoomId);
        $params->put('cwAccountId', $cwAccountId);
        $errorMessages = [];
        $result = MemberService::updateMemberBasicInfo($params, $errorMessages);
        if($result['status']){
            return parent::responseJsonData(true, $result);
        }else{
            return parent::responseJsonStatus(false, $errorMessages);
        }
    }

    /**
     * 会員詳細：学習方針新規登録
     * @param Request $request
     * @return type
     *
     */
    public function memberLearningpolicyRegist(Request $request) {

        $userId = empty($request->get('userId')) ? '': $request->get('userId');
        $lpCategory1Id = empty($request->get('lpCategory1Id')) ? '': $request->get('lpCategory1Id');
        $lpCategory2Id = empty($request->get('lpCategory2Id')) ? '': $request->get('lpCategory2Id');
        $lpTag = empty($request->get('lpTag')) ? '': $request->get('lpTag');
        $policy = empty($request->get('policy')) ? '': $request->get('policy');
        $remark = empty($request->get('remark')) ? '': $request->get('remark');

        //タグの整形
        $tmp1 = mb_ereg_replace('　+', ' ', $lpTag); //全角スペースを半角スペースに
        $tmp2 = mb_ereg_replace('\s+', ' ', $tmp1);  //複数半角スペースを単一半角スペースに
        $tmp3 = trim($tmp2);
        $lpTagFormed = (strlen($tmp3) > 0) ? $tmp3: '';

        $params = collect();
        $params->put('userId', $userId);
        $params->put('lpCategory1Id', $lpCategory1Id);
        $params->put('lpCategory2Id', $lpCategory2Id);
        $params->put('lpTag', $lpTagFormed);
        $params->put('policy', $policy);
        $params->put('remark', $remark);

        $errorMessages = [];
        $result = MemberService::registMemberLearningPolicy($params, $errorMessages);
        if($result['status']){
            return parent::responseJsonData(true, $result);
        }else{
            return parent::responseJsonStatus(false, $errorMessages);
        }
    }

    /**
     * Grammar/Vocabulary出題予定リスト
     * @param Request $request
     * @return type
     */
    public function getTaskAssignList(Request $request) {
        $userId = empty($request->get('userId')) ? '': $request->get('userId');
        if(empty($userId)){
            $errorMessages[] = 'user_idを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }
        $qType = empty($request->get('qType')) ? '': $request->get('qType');
        if(empty($qType)){
            $errorMessages[] = 'qTypeを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }

        $params = ['userId'=>$userId, 'qType'=>$qType];
        $taskAssignDto = MemberService::getTaskAssignList($params);
        return parent::responseJsonData(true, [$taskAssignDto]);
    }

    /**
     * タスク種別変更
     * @param Request $request
     * @return type
     */
    public function changeTaskType(Request $request) {
        $userId = empty($request->get('userId')) ? '': $request->get('userId');
        if(empty($userId)){
            $errorMessages[] = 'user_idを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }
        $dayNumber = empty($request->get('dayNumber')) ? '': $request->get('dayNumber');
        if(empty($dayNumber)){
            $errorMessages[] = 'dayNumberを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }
        $cellNumber = empty($request->get('cellNumber')) ? '': $request->get('cellNumber');
        if(empty($cellNumber)){
            $errorMessages[] = 'cellNumberを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }
        $newTaskType = $request->get('newTaskType');
        if(strlen($newTaskType) < 1){ //「なし」が指定された場合は '0' がセットされるため空欄はエラーである
            $errorMessages[] = 'newTaskTypeを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }

        $params = [
            'userId'=>$userId,
            'dayNumber'=>$dayNumber,
            'cellNumber'=>$cellNumber,
            'newTaskType'=>$newTaskType,
        ];

        MemberService::changeTaskType($params);
        return parent::responseJsonData(true, []);
    }

    /**
     * G/Vモーダル上でのタスク変更
     * @param Request $request
     * @return type
     */
    public function modalChangeTask(Request $request) {

        $qType = empty($request->get('qType')) ? '': $request->get('qType');
        if(empty($qType)){
            $errorMessages[] = 'qTypeを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }
        $userTaskPeriodId = empty($request->get('userTaskPeriodId')) ? '': $request->get('userTaskPeriodId');
        if(empty($userTaskPeriodId)){
            $errorMessages[] = 'userTaskPeriodIdを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }
        $orgTaskId = empty($request->get('orgTaskId')) ? '': $request->get('orgTaskId');
        if(empty($orgTaskId)){
            $errorMessages[] = 'orgTaskIdを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }
        $newTaskId = empty($request->get('newTaskId')) ? '': $request->get('newTaskId');
        if(empty($newTaskId)){
            $errorMessages[] = 'newTaskIdを指定してください';
            return parent::responseJsonStatus(false, $errorMessages);
        }
        $params = [
            'qType' => $qType,
            'userTaskPeriodId' => $userTaskPeriodId,
            'orgTaskId' => $orgTaskId,
            'newTaskId' => $newTaskId
        ];

        $ret = MemberService::modalChangeTask($params);
        if($ret){
            return parent::responseJsonStatus(true, []);
        }else{
            $err = 'G/Vタスク変更エラー';
            return parent::responseJsonStatus(false, $err);
        }
    }

    /**
     * モーダル：マスタから切り替える：マスタ検索
     * @param Request $request
     * @return type
     */
    public function modalSearchTaskMaster(Request $request) {
        $search_qtype	  = empty($request->get('search_qtype'))    ? '': $request->get('search_qtype');
        $search_module	  = empty($request->get('search_module'))   ? '': $request->get('search_module');
        $search_grade     = empty($request->get('search_grade'))    ? '': $request->get('search_grade');
        $search_stage     = empty($request->get('search_stage'))    ? '': $request->get('search_stage');
        //$search_tips      = empty($request->get('search_tips'))     ? '': $request->get('search_tips');
        $search_focus     = empty($request->get('search_focus'))    ? '': $request->get('search_focus');
        $search_freeword  = empty($request->get('search_freeword')) ? '': $request->get('search_freeword');

        $params = [
            'search_qtype'	  => $search_qtype,
            'search_module'   => $search_module,
            'search_grade'    => $search_grade,
            'search_stage'    => $search_stage,
            //'search_tips'     => $search_tips,
            'search_focus'    => $search_focus,
            'search_freeword' => $search_freeword,
        ];

        $taskMasters = MemberService::searchTaskMaster($params);
        $result = [
            'taskMasters' => $taskMasters,
            'count' => count($taskMasters)
        ];
        return parent::responseJsonData(true, $result);
    }

    /**
     * モーダル：マスタから切り替える：タスク切り替え実行
     * @param Request $request
     */
    public function modalChangeTaskMaster(Request $request) {

        $qtype = empty($request->get('qtype')) ? '': $request->get('qtype');
        $userId = empty($request->get('userId')) ? '': $request->get('userId');
        $userTaskPeriodId = empty($request->get('userTaskPeriodId')) ? '': $request->get('userTaskPeriodId');
        $orgTaskId = empty($request->get('orgTaskId')) ? '': $request->get('orgTaskId');
        $taskMasterId = empty($request->get('taskMasterId')) ? '': $request->get('taskMasterId');

        $params = [
            'qtype' => $qtype,
            'userId' => $userId,
            'userTaskPeriodId' => $userTaskPeriodId,
            'orgTaskId' => $orgTaskId,
            'taskMasterId' => $taskMasterId,
        ];

        $ret = MemberService::changeTaskMaster($params);
        if($ret){
            return parent::responseJsonStatus(true, []);
        }else{
            $err = 'G/Vマスタからのタスク変更エラー';
            return parent::responseJsonStatus(false, $err);
        }
    }

    public function modalPostChatworkMessage(Request $request) {
        $userId = empty($request->get('userId')) ? '': $request->get('userId');
        $postText = empty($request->get('postText')) ? '': $request->get('postText');
        $params = [
            'userId' => $userId,
            'postText' => $postText
        ];
        MemberService::postChatworkMessage($params);
        return parent::responseJsonStatus(true, []);
    }

    public function modalGetCounselorInfo(Request $request) {
        $userId = empty($request->get('userId')) ? '': $request->get('userId');
        $params = [
            'userId' => $userId,
        ];
        $counselorInfo = MemberService::getCounselorInfo($params);
        return parent::responseJsonData(true, $counselorInfo);
    }

    public function modalRegistCounselor(Request $request) {
        $userId = empty($request->get('userId')) ? '': $request->get('userId');
        $accountDatas = empty($request->get('accountDatas')) ? '': $request->get('accountDatas');
        $params = [
            'userId' => $userId,
            'accountDatas' => $accountDatas,
        ];
        MemberService::putCounselorInfo($params);
        return parent::responseJsonStatus(true);
    }

    /**
     * 2018-06-07 追加
     * 会員詳細:データの動的取得
     *
     * 会員詳細画面の表示が遅いため、
     * 処理に時間を要する下記のデータは初期表示時には表示せず、
     * ページ描画後にajaxにて動的に取得するように変更する。
     *
     * また各データについて「ページ送り対応」とすることで、
     * 1回に取得/表示するデータ量を制限し、
     * これによっても処理に要する時間を短縮するものである。
     *
     * -- ajax化するデータ種別は下記：
     *  ① OUTPUT履歴
     *  ② レッスン受講状況
     *  ③ 宿題実施状況
     *  ④ メッセージ送受信履歴
     *
     * @param Request $request
     */
    public function getMemberDetailPartData(Request $request) {
        $userId = $request->get('userId');
        $dataType = $request->get('dataType');
        $skip = $request->get('skip');
        $params = [
            'userId' => $userId,
            'dataType' => $dataType,
            'skip' => $skip,
        ];
        $result = MemberService::getMemberDetailPartData($params);
        return parent::responseJsonData(true, $result);
    }


    public function getLpCategory(Request $request) {
        $category1Id = $request->get('category1Id');
        $params = [
            'category1Id' => $category1Id
        ];
        $userLpCategories = MemberService::getUserLearningPolicyCategory($params);

        return parent::responseJsonData(true, $userLpCategories);
    }

}

