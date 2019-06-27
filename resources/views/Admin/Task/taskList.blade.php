<?php
use App\Enums\AccountKindType;
use App\Enums\UserTaskStatusType;

$loginUserAccountType = $loginAccountInfo['account_kind_id'];

?>

<table class="table table-sm core-tbl table-hover task-list">
@if( $loginUserAccountType == AccountKindType::ACCOUNT_MANAGER_ID ||
     $loginUserAccountType == AccountKindType::ACCOUNT_SYSTEM_OPERATOR_ID )
    <thead class="thead-default">
    <tr>
        <th class="font-weight-bold">宿題種別</th>
        <th class="font-weight-bold">生徒名</th>
        <th class="font-weight-bold">出題日</th>
        <th class="font-weight-bold">期限日</th>
        <th class="font-weight-bold">回答日</th>
        <th class="font-weight-bold">ステータス</th>
        <th class="font-weight-bold">オペレータ</th>
        <th class="font-weight-bold">出題・回答確認<br />フィードバック</th>
    </tr>
    </thead>
@else
    <thead class="thead-default">
    <tr>
        <th class="font-weight-bold">宿題種別</th>
        <th class="font-weight-bold">生徒名</th>
        <th class="font-weight-bold">出題日</th>
        <th class="font-weight-bold">期限日</th>
        <th class="font-weight-bold">回答日</th>
        <th class="font-weight-bold">ステータス</th>
        <th class="font-weight-bold">オペレータ</th>
        <th class="font-weight-bold">出題・回答確認<br />フィードバック</th>
    </tr>
    </thead>
@endif

    <tbody>

<?php

foreach($taskList as $rowData){

    //会員名
    $memberName = $rowData->userInfo->arugoUserId . ' ' .
                  $rowData->userInfo->lastName . ' ' . $rowData->userInfo->firstName;

    //ステータスの取得
    $taskStatus = $rowData->userTaskStatus;
    $taskStatusName = $rowData->userTaskStatusName;

    /*
     * 参考：ステータス値
     * 0: 未出題
     * 1: 出題済み
     * 2: ユーザ回答済み
     * 3: ユーザ回答差し戻し
     * 4: 未提出
     * 5: フィードバック済み
     * 6: フィードバック差し戻し
     * 7: 投稿済み（完了）
     */
/************
    $taskStatusSelectOptions = '';
    if($taskStatus == '0'){//0: 未出題
        $taskStatusSelectOptions = '<option value="0" selected>未出題</option>';

    }else if($taskStatus == '1'){//1: 出題済み
        $taskStatusSelectOptions = '<option value="1" selected>出題済み</option>';

    }else if($taskStatus == '2'){//2: ユーザ回答済み
        $taskStatusSelectOptions = '<option value="2" selected>回答済み</option>'
                                 . '<option value="3">回答差戻し</option>';

    }else if($taskStatus == '3'){//3: ユーザ回答差し戻し
        $taskStatusSelectOptions = '<option value="3" selected>回答差戻し</option>';

    }else if($taskStatus == '4'){//4: 未提出

    }else if($taskStatus == '5'){//5: フィードバック済み
        $taskStatusSelectOptions = '<option value="5" selected>フィードバック済み</option>'
                                 . '<option value="6">フィードバック差戻し</option>';

    }else if($taskStatus == '6'){//6: フィードバック差戻し
        $taskStatusSelectOptions = '<option value="5">フィードバック済み</option>'
                                 . '<option value="6" selected>フィードバック差戻し</option>';

    }else if($taskStatus == '7'){//7: 投稿済み（完了）
        $taskStatusSelectOptions = '<option value="7" selected>フィードバック投稿済み</option>';
    }

    $taskStatusDisp = '<select name="selectStatus" class="form-control select2">'
                    . $taskStatusSelectOptions
                    . '</select>';
************/

    //出題日、期限日、回答日
    $questionSetDate = $rowData->questionSetDate;//出題日
    $answerDeadlineDate = $rowData->answerDeadlineDate;//回答期限日
    $answeredDate = $rowData->answeredDate;//回答日
    /****
    $dateSetDead = '';
    $dateAnswer = '';
    if(!empty($questionSetDate)){
        $dateSetDead = $questionSetDate;
    }
    if(!empty($answerDeadlineDate)){
        $dateSetDead .= '<br />' . $answerDeadlineDate;
    }
    if(!empty($answeredDate)){
        $dateAnswer = $answeredDate;
    }
    ****/

    //宿題種別
    $taskType = $rowData->userTaskType;
    $taskTypeName = $rowData->userTaskTypeName;

    //回答内容 (回答URL)
    $answerUrl = $rowData->originalAnswer;

    //回答レコードid
    //$userMessageLogId = $rowData->userMessageLogId;

    //オペレータ選択肢の生成
    $selectOperator = '';
    $selectedOperatorAccountId = '';

    $operatorInfo = $rowData->operatorInfo;
    if(!empty($operatorInfo) && property_exists($operatorInfo, 'accountId')){
        $selectedOperatorAccountId = $operatorInfo->accountId;
    }

    if( $loginUserAccountType == AccountKindType::ACCOUNT_OPERATOR_ID){//オペレータ
        $selectOperator =
            '<select name="selectOperator" class="form-control select2" plaseholder="選択してください" disabled="disabled">';
    }else{
        $selectOperator =
            '<select name="selectOperator" class="form-control select2" plaseholder="選択してください">';
    }
    $selectOperator .= '<option value="">未設定</option>';

    foreach($operatorList as $key => $operatorRow){
        if($key == $selectedOperatorAccountId){
            $selectOperator .= '<option value="' . $key . '" selected>' . $operatorRow . '</option>';
        }else{
            $selectOperator .= '<option value="' . $key . '">' . $operatorRow . '</option>';
        }
    }
    $selectOperator .= '</select>';

    /****
    //音声DLボタン (ステータス値に応じた表示となる)
    $answerDownloadButton = '';
    if( ($taskStatus >= UserTaskStatusType::TASK_STATUS_VAL_ANSWERED) &&
        (!empty($answerUrl) && strlen($answerUrl) > 0)
    ){
        $answerDownloadButton = '<button name="btn-download" class="btn btn-domain" data-answer-url="' . $answerUrl . '">音声DL</button>';
    }else{
        $answerDownloadButton = '<button name="btn-download" class="btn btn-default" data-answer-url="' . $answerUrl . '" disabled="disabled">音声DL</button>';
    }
    ****/

    //フィードバック登録ボタン (ステータス値に応じた表示となる)
    /*** 設問内容閲覧のため常に表示する
    $feedbackButton = '';
    if( $taskStatus == UserTaskStatusType::TASK_STATUS_VAL_ANSWERED ||         // 2: ユーザ回答済み
        $taskStatus == UserTaskStatusType::TASK_STATUS_VAL_FEEDBACKED ||       // 5: フィードバック済み
        $taskStatus == UserTaskStatusType::TASK_STATUS_VAL_FEEDBACK_REJECT ||  // 6: フィードバック差し戻し
        $taskStatus == UserTaskStatusType::TASK_STATUS_VAL_RESPONSED           // 7: フィードバック完了
    ){
        $feedbackButton = '<button name="btn-feedback" class="btn btn-domain">回答確認/FB</button>';
    }else{
        $feedbackButton = '<button name="btn-feedback" class="btn btn-default" disabled="disabled">回答確認/FB</button>';
    } ***/
    $feedbackButton = '<button name="btn-feedback" class="btn btn-domain">出題・回答確認/FB</button>';

?>

@if( $loginUserAccountType == AccountKindType::ACCOUNT_MANAGER_ID ||
     $loginUserAccountType == AccountKindType::ACCOUNT_SYSTEM_OPERATOR_ID )

    <tr class="table-row"
        data-min-user-task-id="{{ $rowData->minUserTaskId }}"
        data-max-user-task-id="{{ $rowData->maxUserTaskId }}"
        data-user-id="{{ $rowData->userId }}"
        data-task-type="{{ $taskType }}"
        data-task-status="{{ $taskStatus }}">
        <td>{!! $taskTypeName !!}</td>
        <td>{!! $memberName !!}</td>
        <td>{!! $questionSetDate !!}</td>
        <td>{!! $answerDeadlineDate !!}</td>
        <td>{!! $answeredDate !!}</td>
        <td>{!! $taskStatusName !!}</td>
        <td>{!! $selectOperator !!}</td>
        <td>{!! $feedbackButton !!}</td>
    </tr>

@else
    <tr class="table-row"
        data-min-user-task-id="{{ $rowData->minUserTaskId }}"
        data-max-user-task-id="{{ $rowData->maxUserTaskId }}"
        data-user-id="{{ $rowData->userId }}"
        data-task-type="{{ $taskType }}"
        data-task-status="{{ $taskStatus }}">
        <td>{!! $taskTypeName !!}</td>
        <td>{!! $memberName !!}</td>
        <td>{!! $questionSetDate !!}</td>
        <td>{!! $answerDeadlineDate !!}</td>
        <td>{!! $answeredDate !!}</td>
        <td>{!! $taskStatusName !!}</td>
        <td>{!! $selectOperator !!}</td>
        <td>{!! $feedbackButton !!}</td>
    </tr>
@endif

<?php
}//foreach
?>

    </tbody>
</table>

