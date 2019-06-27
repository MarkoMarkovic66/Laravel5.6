@extends('layouts.Admin.master')

@section('styles')
@endsection

<?php
use App\Enums\AccountKindType;

$dashBoardData = $view_params->get('dashBoardData');
$accountKindId = $dashBoardData['accountKindId'];

if( $accountKindId == AccountKindType::ACCOUNT_MANAGER_ID ||
    $accountKindId == AccountKindType::ACCOUNT_SYSTEM_OPERATOR_ID ){
    $taskList = $dashBoardData['taskList'];
    $answerList = $dashBoardData['answerList'];

}elseif( $accountKindId == AccountKindType::ACCOUNT_COUNSELOR_ID){
    $memberList = $dashBoardData['memberList'];

}elseif( $accountKindId == AccountKindType::ACCOUNT_OPERATOR_ID){
    $answerList = $dashBoardData['answerList'];
}
?>

@section('body-contents')

@if( $accountKindId == AccountKindType::ACCOUNT_MANAGER_ID ||
     $accountKindId == AccountKindType::ACCOUNT_SYSTEM_OPERATOR_ID )
        <div class="mainbar">
          <div class="primary-item">
            <span>ダッシュボード</span>
          </div>
          <div class="hero-section">
            <div class="secondary-item">
              <span>講師未割当タスク一覧</span>
            </div>

            <div class="secondary-box">
              <table class="table table-hover core-tbl">
                <thead>
                  <tr>
                    <th scope="col">No</th>
                    <th scope="col">生徒名</th>
                    <th scope="col">出題種別</th>
                    <th scope="col">ステータス</th>
                    <th scope="col">出題日</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($taskList as $task)
                  <?php
                  /***
                  $questionContext = '';
                  if($task->taskInfo->questionType == '1' || $task->taskInfo->questionType == '2'){
                      $questionContext = $task->taskInfo->question;
                  }elseif($task->taskInfo->questionType == '3'){
                      $questionContext = $task->taskInfo->srContext;
                  }elseif($task->taskInfo->questionType == '4'){
                      $questionContext = $task->taskInfo->reviewContext;
                  }else{
                      $questionContext = $task->taskInfo->otherTaskContext;
                  }***/
                  ?>
                      <tr>
                          <td>{{$loop->iteration}}</td>
                          <td>{{$task->userInfo->lastName.' '.$task->userInfo->firstName}}</td>
                          <td>{{$task->taskInfo->taskTypeName}}</td>
                          <td>{{$task->userTaskStatusName}}</td>
                          <td>{{$task->questionSetDate}}</td>
                      </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

              <div class="hero-list-all">
                <a href="#">
                  <button name="goToTaskWithNoAssign" class="btn btn-hero">宿題管理へ</button>
                </a>
              </div>
          </div>


        {{--
          <div class="hero-section">
            <div class="secondary-item">
              <span>生徒からの新着回答一覧</span>
            </div>

            <div class="secondary-box">
              <table class="table table-hover core-tbl">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>生徒名</th>
                    <th>回答日</th>
                    <th>回答内容</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($answerList as $answer)
                      <tr>
                          <td>{{$loop->iteration}}</td>
                          <td>{{$answer->postedName}}</td>
                          <td>{{$answer->postedAt}}</td>
                          <td>{!! $answer->postedContext !!}</td>
                      </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <!-- <div class="secondary-box">
              <div class="secondary-box">

                <table class="table table-sm table-hover top-answer-list">
                <thead class="thead-default">
                <tr>
                    <th>No</th>
                    <th>生徒名</th>
                    <th>回答日</th>
                    <th>回答内容</th>
                </tr>
                </thead>
                <tbody>
            @foreach($answerList as $answer)
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$answer->postedName}}</td>
                    <td>{{$answer->postedAt}}</td>
                    <td>{{$answer->postedContext}}</td>
                </tr>
            @endforeach
                </tbody>
                </table>
            </div>
 -->
              <div class="hero-list-all">
                <a href="#">
                  <button name="goToTask" class="btn btn-hero">宿題管理へ</button>
                </a>
              </div>
            </div>
        --}}

        </div>


@elseif( $accountKindId == AccountKindType::ACCOUNT_COUNSELOR_ID )
        <div class="mainbar">
          <div class="primary-item">
            <span>ダッシュボード</span>
          </div>
          <div class="hero-section">
            <div class="secondary-item">
              <span>担当会員一覧</span>
            </div>
            <div class="secondary-box">

                <!-- <table class="table table-sm table-hover top-member-list core-tbl"> -->
                <table class="table table-sm table-hover core-tbl">
                <thead class="thead-default">
                <tr>
                    <th>No</th>
                    <th>Arugo User Id</th>
                    <th>SR User Id</th>
                    <th>生徒名</th>
                    <th>Mail</th>
                </tr>
                </thead>
                <tbody>
            @foreach($memberList as $member)
            <?php
            $memberName = $member->lastName . ' '. $member->firstName
                        . ' ('. $member->firstNameEn . ' ' . $member->lastName .')';
            ?>
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$member->arugoUserId}}</td>
                    <td>{{$member->srUserId}}</td>
                    <td>{{$memberName}}</td>
                    <td>{{$member->mail}}</td>
                </tr>
            @endforeach
                </tbody>
                </table>
              </div>

                <div class="hero-list-all">
                  <a href="#">
                    <button name="goToMember" class="btn btn-hero">会員管理へ</button>
                  </a>
                </div>

          </div>
        </div>

@elseif( $accountKindId == AccountKindType::ACCOUNT_OPERATOR_ID )
        <div class="mainbar">
          <div class="primary-item">
            <span>ダッシュボード</span>
          </div>

          <div class="hero-section">
            <div class="secondary-item">
              <span>担当生徒からの新着回答一覧</span>
            </div>
            <div class="secondary-box">
              <div class="secondary-box">

                <!-- <table class="table table-sm table-hover top-answer-list"> -->
                <table class="table table-sm table-hover core-tbl">
                <thead class="thead-default">
                <tr>
                    <th>No</th>
                    <th>生徒名</th>
                    <th>回答日</th>
                    <th>回答内容</th>
                </tr>
                </thead>
                <tbody>
            @foreach($answerList as $answer)
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$answer->postedName}}</td>
                    <td>{{$answer->postedAt}}</td>
                    <td>{{$answer->postedContext}}</td>
                </tr>
            @endforeach
                </tbody>
                </table>
            </div>

              <div class="hero-list-all">
                <a href="#">
                  <button name="goToTask" class="btn btn-hero">宿題管理へ</button>
                </a>
              </div>
            </div>

          </div>

        </div>
@endif

@endsection

@section('scripts')
<script src="{{ mix('/js/dashboard.js') }}"></script>
@endsection
