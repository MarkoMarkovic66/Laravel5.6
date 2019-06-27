<?php
namespace App\Http\Controllers\Admin\Task;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Enums\SessionValiableType;
use App\Http\Controllers\AjaxBaseController;
use App\Utils\DateUtils;

use TaskService;
use View;

/**
 * AjaxController
 */
class AjaxController extends AjaxBaseController {

    public function __construct() {
        parent::__construct();
    }

    public function taskSearch(Request $request) {

        $loginAccountInfo = parent::getLoginUser();
        $params = collect();
        $errorMessages = [];

        if( ! $this->validateSearchConditions($request, $params, $errorMessages)){
            //検索条件バリデーションエラー
            return parent::responseJsonStatus(false, $errorMessages);
        }

        //検索条件をセッションに保持
        session([SessionValiableType::SEARCH_CONDITION_TASK => serialize($params)]);

        //宿題一覧リスト取得
        $searchResult = TaskService::doTaskSearch($loginAccountInfo, $params);
        $taskList = $searchResult['taskList'];
        $totalRowCount  = $searchResult['totalRowCount'];
        $totalPageCount = $searchResult['totalPageCount'];
        $currentPageNo  = $params->get('pageNo');

        //ページネーション表示
        $pageBlade = View::make('layouts.pagination',
                [
                    'totalRowCount' => number_format($totalRowCount),
                    'totalPageCount' => $totalPageCount,
                    'currentPageNo' => $currentPageNo,
                ])->render();

        //宿題一覧リスト内に表示するためのオペレータリストも返却する
        $operatorList = TaskService::getOperatorList($loginAccountInfo);


        //宿題一覧リストhtml生成  2018-04-24 処理速度向上のため、サーバー側でhtmlを生成する
        $taskListBlade = View::make('Admin.Task.taskList',
                [
                    'loginAccountInfo' => $loginAccountInfo,
                    'taskList' => $taskList,
                    'operatorList' => $operatorList
                ])->render();


        $result = [
            'totalRowCount'  => $totalRowCount,
            'totalPageCount' => $totalPageCount,
            'currentPageNo'  => $currentPageNo,
            'pageBlade' => $pageBlade,
            'count' => count($taskList),
            'taskList' => $taskList,
            'taskListBlade' => $taskListBlade,
            'operatorList' => $operatorList
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

        $questionSetDateSince    = empty($request->get('questionSetDateSince'))    ? '' : $request->get('questionSetDateSince');
        $questionSetDateUntil    = empty($request->get('questionSetDateUntil'))    ? '' : $request->get('questionSetDateUntil');
        $answerDeadlineDateSince = empty($request->get('answerDeadlineDateSince')) ? '' : $request->get('answerDeadlineDateSince');
        $answerDeadlineDateUntil = empty($request->get('answerDeadlineDateUntil')) ? '' : $request->get('answerDeadlineDateUntil');
        $answeredDateSince       = empty($request->get('answeredDateSince'))       ? '' : $request->get('answeredDateSince');
        $answeredDateUntil       = empty($request->get('answeredDateUntil'))       ? '' : $request->get('answeredDateUntil');

        $selectTaskType = empty($request->get('selectTaskType'))  ? '' : $request->get('selectTaskType');
        $selectStudent  = empty($request->get('selectStudent'))   ? '' : $request->get('selectStudent');
        $selectStatus   = empty($request->get('selectStatus'))    ? '' : $request->get('selectStatus');
        $selectOperator = empty($request->get('selectOperator'))  ? '' : $request->get('selectOperator');
        $freeword       = empty($request->get('freeword'))        ? '' : $request->get('freeword');

        $pageNo = empty($request->get('pageNo')) ? 1: $request->get('pageNo');

        $isOnlyNoAssignOperator = false;
        if(!empty($request->get('isOnlyNoAssignOperator') && $request->get('isOnlyNoAssignOperator') == 'true')){
            $isOnlyNoAssignOperator = true;
        }

        $isValid = true;

        if(! DateUtils::validateDateString($questionSetDateSince, true)){
            $isValid = false;
            $errorMessage[] = '出題日(自)が不正です。';
        }
        if(! DateUtils::validateDateString($questionSetDateUntil, true)){
            $isValid = false;
            $errorMessage[] = '出題日(至)が不正です。';
        }
        if(! DateUtils::validateDateString($answerDeadlineDateSince, true)){
            $isValid = false;
            $errorMessage[] = '回答期限日(自)が不正です。';
        }
        if(! DateUtils::validateDateString($answerDeadlineDateUntil, true)){
            $isValid = false;
            $errorMessage[] = '回答期限日(至)が不正です。';
        }
        if(! DateUtils::validateDateString($answeredDateSince, true)){
            $isValid = false;
            $errorMessage[] = '回答日(自)が不正です。';
        }
        if(! DateUtils::validateDateString($answeredDateUntil, true)){
            $isValid = false;
            $errorMessage[] = '回答日(至)が不正です。';
        }

        //日付時刻文字の整形
        if(!empty($questionSetDateSince)){
            $questionSetDateSince = DateUtils::shortenToYYMMDD_SlashDelimited($questionSetDateSince) . ' 00:00:00';
        }
        if(!empty($questionSetDateUntil)){
            $questionSetDateUntil = DateUtils::shortenToYYMMDD_SlashDelimited($questionSetDateUntil) . ' 23:59:59';
        }
        if(!empty($answerDeadlineDateSince)){
            $answerDeadlineDateSince = DateUtils::shortenToYYMMDD_SlashDelimited($answerDeadlineDateSince) . ' 00:00:00';
        }
        if(!empty($answerDeadlineDateUntil)){
            $answerDeadlineDateUntil = DateUtils::shortenToYYMMDD_SlashDelimited($answerDeadlineDateUntil) . ' 23:59:59';
        }
        if(!empty($answeredDateSince)){
            $answeredDateSince = DateUtils::shortenToYYMMDD_SlashDelimited($answeredDateSince) . ' 00:00:00';
        }
        if(!empty($answeredDateUntil)){
            $answeredDateUntil = DateUtils::shortenToYYMMDD_SlashDelimited($answeredDateUntil) . ' 23:59:59';
        }

        $params->put('questionSetDateSince',	$questionSetDateSince);
        $params->put('questionSetDateUntil',    $questionSetDateUntil);
        $params->put('answerDeadlineDateSince', $answerDeadlineDateSince);
        $params->put('answerDeadlineDateUntil', $answerDeadlineDateUntil);
        $params->put('answeredDateSince',       $answeredDateSince);
        $params->put('answeredDateUntil',       $answeredDateUntil);

        $params->put('selectTaskType',  $selectTaskType);
        $params->put('selectStudent',   $selectStudent);
        $params->put('selectStatus',    $selectStatus);
        $params->put('selectOperator',  $selectOperator);
        $params->put('freeword',        $freeword);
        $params->put('isOnlyNoAssignOperator', $isOnlyNoAssignOperator);
        $params->put('pageNo', $pageNo);

        return $isValid;
    }

    /**
     * ステータス更新
     * @param Request $request
     */
    public function changeStatus(Request $request) {
        $params = collect();
        $userId = empty($request->get('userId')) ? '' : $request->get('userId');
        $userTaskId = empty($request->get('userTaskId')) ? '' : $request->get('userTaskId');
        $selectedStatusId = empty($request->get('selectedStatusId')) ? '' : $request->get('selectedStatusId');

        $params->put('userId', $userId);
        $params->put('userTaskId', $userTaskId);
        $params->put('selectedStatusId', $selectedStatusId);
        $ret = TaskService::doChangeStatus($params);
        return parent::responseJsonStatus($ret);
    }

    /**
     * オペレータアサイン
     * @param Request $request
     */
    public function assignOperator(Request $request) {
        $params = collect();

        $minUserTaskId = empty($request->get('minUserTaskId')) ? '' : $request->get('minUserTaskId');
        $maxUserTaskId = empty($request->get('maxUserTaskId')) ? '' : $request->get('maxUserTaskId');
        $userId = empty($request->get('userId')) ? '' : $request->get('userId');
        $selectedOperatorId = empty($request->get('selectedOperatorId')) ? '' : $request->get('selectedOperatorId');

        $params->put('minUserTaskId', $minUserTaskId);
        $params->put('maxUserTaskId', $maxUserTaskId);
        $params->put('userId', $userId);
        $params->put('selectedOperatorId', $selectedOperatorId);

        TaskService::doAssignOperator($params);

        return parent::responseJsonStatus(true);
    }

    /**
     * フィードバック登録
     * @param Request $request
     */
    public function registFeedback(Request $request) {
        $loginAccountInfo = parent::getLoginUser();
        $loginAccountId = $loginAccountInfo['id'];
        $userId       = empty($request->get('userId'))       ? '' : $request->get('userId');
        $userTaskId   = empty($request->get('userTaskId'))   ? '' : $request->get('userTaskId');
        $feedbackText = empty($request->get('feedbackText')) ? '' : $request->get('feedbackText');

        $params = collect();
        $params->put('loginAccountId', $loginAccountId);
        $params->put('userId', $userId);
        $params->put('userTaskId', $userTaskId);
        $params->put('feedbackText', $feedbackText);

        TaskService::doRegistFeedback($params);

        return parent::responseJsonStatus(true);
    }
    /**
     * フィードバック承認
     * @param Request $request
     */
    public function approveFeedback(Request $request) {
        $loginAccountInfo = parent::getLoginUser();
        $loginAccountId = $loginAccountInfo['id'];
        $userId       = empty($request->get('userId'))       ? '' : $request->get('userId');
        $userTaskId   = empty($request->get('userTaskId'))   ? '' : $request->get('userTaskId');

        $params = collect();
        $params->put('loginAccountId', $loginAccountId);
        $params->put('userId', $userId);
        $params->put('userTaskId', $userTaskId);

        TaskService::doApproveFeedback($params);

        return parent::responseJsonStatus(true);
    }
    /**
     * フィードバック差し戻し
     * @param Request $request
     */
    public function rejectFeedback(Request $request) {
        $loginAccountInfo = parent::getLoginUser();
        $loginAccountId = $loginAccountInfo['id'];
        $userId       = empty($request->get('userId'))       ? '' : $request->get('userId');
        $userTaskId   = empty($request->get('userTaskId'))   ? '' : $request->get('userTaskId');

        $params = collect();
        $params->put('loginAccountId', $loginAccountId);
        $params->put('userId', $userId);
        $params->put('userTaskId', $userTaskId);

        TaskService::doRejectFeedback($params);

        return parent::responseJsonStatus(true);
    }



    /**
     * @deprecated
     * 回答一覧取得
     * @param Request $request
     */
    public function getAnswerList(Request $request) {
        $answerList = TaskService::getAnswerList();
        return parent::responseJsonData(true, $answerList);
    }

    /**
     * @deprecated
     * 回答紐付け登録
     * @param Request $request
     */
    public function registAnswerLink(Request $request) {
        $userId = empty($request->get('userId')) ? '' : $request->get('userId');
        $userTaskId = empty($request->get('userTaskId')) ? '' : $request->get('userTaskId');
        $userTaskQuestionSetDate = empty($request->get('userTaskQuestionSetDate')) ? '' : DateUtils::convertToYYMMDD_HyphenDelimited($request->get('userTaskQuestionSetDate'));
        $userMessageLogId = empty($request->get('userMessageLogId')) ? '' : $request->get('userMessageLogId');
        $postedContext = empty($request->get('postedContext')) ? '' : $request->get('postedContext');

        $params = collect();
        $params->put('userId', $userId);
        $params->put('userTaskId', $userTaskId);
        $params->put('userTaskQuestionSetDate', $userTaskQuestionSetDate);
        $params->put('userMessageLogId', $userMessageLogId);
        $params->put('postedContext', $postedContext);

        TaskService::doRegistAnswerLink($params);

        return parent::responseJsonStatus(true);
    }
    /**
     * 回答紐付け解除
     * @param Request $request
     */
    public function releaseAnswerLink(Request $request) {
        $userId = empty($request->get('userId')) ? '' : $request->get('userId');
        $userTaskId = empty($request->get('userTaskId')) ? '' : $request->get('userTaskId');
        $userTaskQuestionSetDate = empty($request->get('userTaskQuestionSetDate')) ? '' : DateUtils::convertToYYMMDD_HyphenDelimited($request->get('userTaskQuestionSetDate'));
        $userMessageLogId = empty($request->get('userMessageLogId')) ? '' : $request->get('userMessageLogId');

        $params = collect();
        $params->put('userId', $userId);
        $params->put('userTaskId', $userTaskId);
        $params->put('userTaskQuestionSetDate', $userTaskQuestionSetDate);
        $params->put('userMessageLogId', $userMessageLogId);

        TaskService::doReleaseAnswerLink($params);

        return parent::responseJsonStatus(true);
    }

    /**
     * Chatwork一括投稿実行
     * @param Request $request
     */
    public function doChatworkContribute(Request $request) {
        $loginAccountInfo = parent::getLoginUser();
        $loginAccountId = $loginAccountInfo['id'];
        $contents = empty($request->get('contents')) ? '' : $request->get('contents');
        TaskService::doChatworkContribute($loginAccountId, $contents);
        return parent::responseJsonStatus(true);
    }

    /**
     * 宿題回答およびFB情報取得
     * @param Request $request
     * @return json
     */
    public function getTaskAnswerFb(Request $request) {
        $userId 	   = $request->get('userId');
        $taskType 	   = $request->get('taskType');
        $taskStatus    = $request->get('taskStatus');
        $minUserTaskId = $request->get('minUserTaskId');
        $maxUserTaskId = $request->get('maxUserTaskId');

        $params = collect();
        $params->put('userId',        $userId);
        $params->put('taskType',      $taskType);
        $params->put('taskStatus',    $taskStatus);
        $params->put('minUserTaskId', $minUserTaskId);
        $params->put('maxUserTaskId', $maxUserTaskId);

        $taskAnswerFb = TaskService::getTaskAnswerFb($params);
        if(!$taskAnswerFb){
            return parent::responseJsonStatus(false, '回答フィードバック取得エラー');
        }
        return parent::responseJsonData(true, [$taskAnswerFb]);
    }


}

