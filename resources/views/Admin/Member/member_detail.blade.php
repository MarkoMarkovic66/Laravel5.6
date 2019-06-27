@extends('layouts.Admin.master')

@section('styles')
@endsection

<?php
$memberDetail = $view_params['memberDetail'];
$userBasicInfo = $memberDetail->userBasicInfo;
$userLearningPolicies = $memberDetail->userLearningPolicies;
$userLearningPolicyLogs = $memberDetail->userLearningPolicyLogs;

$userGoals = $memberDetail->userGoals;
if (!empty($userGoals) && count($userGoals) > 0) {
    $userGoal = $memberDetail->userGoals[0];
    $lastUpdatedAt = $userGoal->updated_at->format('Y/m/d');
    $userGoalTarget = $userGoal->target;
    $userGoalLimit = $userGoal->goal_limit;
    $userGoalScene = $userGoal->scene;
} else {
    $lastUpdatedAt = '';
    $userGoalTarget = '';
    $userGoalLimit = '';
    $userGoalScene = '';
}

$userAssessmentGoals = $memberDetail->userAssessmentGoals;
$userAssessmentLogs  = $memberDetail->userAssessmentLogs;

//会員レポート発行日付けリスト
$memberReportIssueDates = $memberDetail->memberReportIssueDates;

//会員基本情報
$userId = $userBasicInfo->userId;
$memberName   = $userBasicInfo->lastName . ' ' . $userBasicInfo->firstName;
$memberNameEn = $userBasicInfo->firstNameEn . ' ' . $userBasicInfo->lastNameEn;
$arugoUserId  = $userBasicInfo->arugoUserId;
?>

@section('body-contents')
    <div class="overall">
        <div class="hero-section">
            <div class="secondary-item">
                <span>{{ $memberName }}さんの詳細</span>
            </div>
            <div class="detail-hero">
                <div class="detail-primary-nav">
                    <a href="/member/back">
                        <button class="btn btn-domain">一覧へ戻る</button>
                    </a>
                    <div class="detail-secondary-nav">

                        <div class="member-report-issue-select">
                          <select name="selectMemberReportIssue" class="select2" title="発行日を選択してください">
                            @foreach($memberReportIssueDates as $issueDate => $issueNumber)
                                <option value="{{ $issueDate }}">{{ $issueNumber }}</option>
                            @endforeach
                          </select>
                        </div>

                    @if( count($memberReportIssueDates) > 0 )
                        <a href="#" onclick="AlueIntegOffice.MemberDetailUtils.lessonReport('{{ $userId }}');">
                            <button name=" btn-putpdf" class="btn btn-domain">レッスン・レポート出力</button>
                        </a>
                    @else
                        <button name=" btn-putpdf" class="btn btn-disabled disabled" disabled="disabled">レッスン・レポート出力</button>
                    @endif

                        <a href="https://www.alue.co.jp/" target="lessonReserveList">
                            <button class="btn btn-domain">レッスンの予約状況を確認する</button>
                        </a>
                        <a href="#" onclick="AlueIntegOffice.MemberDetailUtils.pageReload();">
                            <i class="fas fa-sync-alt page-reload"></i>
                        </a>
                    </div>
                </div>
                <div class="detail-main">
                    <div class="detail-box row">
                        <div class="detail-user-basic col-md-5">
                            <h3>受講生基礎情報
                                <!-- <button class="btn btn-domain btn-user-basic-update">更新</button> -->
                            </h3>
                            <div class="detail-info-box">
                                <table border="1" bordercolor="#dee2e6" name="user-basic-info">
                                    <tr>
                                        <td>ID</td>
                                        <td>{{$userBasicInfo->arugoUserId}}<input type="hidden" name="userId" value="{{ $userId }}"></td>
                                    </tr>
                                    <tr>
                                        <td>氏名</td>
                                        <td>{{$memberName.'('.$memberNameEn.')'}}</td>
                                    </tr>
                                    <tr>
                                        <td>Mail</td>
                                        <td>{{$userBasicInfo->mail}}</td>
                                    </tr>
                                    <tr>
                                        <td>PhoneNumber</td>
                                        <td>{{$userBasicInfo->phoneNumber}}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="detail-learning-policy col-md-7">
                            <h3>現在の学習方針
                                <button class="btn btn-domain" data-toggle="modal" data-target="#newPolicyModal">
                                    新しい方針を登録
                                </button>
                            </h3>
                            <div class="detail-info-box">
                                <table class="table table-hover core-tbl" name="latestLpDatas">
                                  <thead>
                                    <tr>
                                      <th>カテゴリ大</th>
                                      <th>タグ</th>
                                      <th>学習方針</th>
                                      <th>投稿日</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                @foreach($userLearningPolicies as $row)
                                    <tr>
                                        <td>{{$row->lpCategory1Name}}</td>
                                        <td>{{$row->lpTagName}}</td>
                                        <td>{{$row->policy}}</td>
                                        <td>{{$row->posted_at}}</td>
                                    </tr>
                                    @endforeach
                                  </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="detail-history-info detail-box">
                        <h3>受講生に対する伴走履歴情報（※カウンセラーと受講生とのやりとりを内部共有用に書き留めたもの）</h3>
                        <div class="detail-history-box">
                          <table class="table table-hover core-tbl" name="allLpDatas">
                            <thead>
                              <tr>
                                <th>No</th>
                                <th>投稿日</th>
                                <th>カテゴリ大</th>
                                <th>カテゴリ小</th>
                                <th>タグ</th>
                                <th>学習方針</th>
                                <th>備考</th>
                              </tr>
                            </thead>
                            <tbody>
                              @foreach($userLearningPolicyLogs as $row)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$row->posted_at}}</td>
                                    <td>{{$row->lpCategory1Name}}</td>
                                    <td>{{$row->lpCategory2Name}}</td>
                                    <td>{{$row->lpTagName}}</td>
                                    <td>{{$row->policy}}</td>
                                    <td>{{$row->comment}}</td>
                                </tr>
                              @endforeach
                            </tbody>
                          </table>
                        </div>
                    </div>

                    <div class="detail-goal-info detail-box">
                        <h3>受講後の目標（最新更新日 {{$lastUpdatedAt}}）</h3>
                        <div class="detail-goal-box">
                            <table class="table table-hover core-tbl">
                                <tr>
                                    <td>目標</td>
                                    <td>{{$userGoalTarget}}</td>
                                </tr>
                                <tr>
                                    <td>期限</td>
                                    <td>{{$userGoalLimit}}</td>
                                </tr>
                                <tr>
                                    <td>シーン</td>
                                    <td>{{$userGoalScene}}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-main">

                    <div class="detail-box">
                        <h3>アセスメント目標</h3>
                        <div class="accessment-goal">
                          <table class="table table-hover core-tbl">
                            <thead>
                              <tr>
                                <th class="number-col">No.</th>
                                <th>予定日</th>
                                <th>レベル</th>
                                @if($userAssessmentGoals && count($userAssessmentGoals) > 0)
                                    @foreach($userAssessmentGoals[0]->grade_array as $each)
                                        <th>{{$each['group']}}</th>
                                    @endforeach
                                @endif
                              </tr>
                            </thead>
                            <tbody>
                              @if($userAssessmentGoals && count($userAssessmentGoals) > 0)
                                  @foreach($userAssessmentGoals as $row)
                                      <tr>
                                          <td>{{$loop->iteration}}</td>
                                          <td>{{$row->goal_limit}}</td>
                                          <td>{{$row->level}}</td>
                                          @foreach($row->grade_array as $each)
                                              <td>{{$each['grade']}}</td>
                                          @endforeach
                                      </tr>
                                  @endforeach
                              @endif
                            </tbody>
                          </table>

                        </div>
                    </div>

                    <div class="detail-box">
                        <h3>アセスメント受験履歴</h3>
                        <div class="accessment-goal-history">
                          <table class="table table-hover core-tbl">
                            <thead>
                              <tr>
                                <th class="number-col">No.</th>
                                <th>受験予定日</th>
                                <th>タイプ</th>
                                <th>レベル</th>
                                @if($userAssessmentLogs && count($userAssessmentLogs) > 0)
                                    @foreach($userAssessmentLogs[0]->result_array as $each)
                                        <th colspan="2">{{$each['category']}}</th>
                                    @endforeach
                                @endif
                              </tr>
                            </thead>
                            <tbody>
                              @if($userAssessmentLogs && count($userAssessmentLogs) > 0)
                                  @foreach($userAssessmentLogs as $row)
                                      <tr>
                                          <td>{{$loop->iteration}}</td>
                                          <td>{{$row->assessment_started_at}}</td>
                                          <td><br/></td>
                                          <td>{{$row->level}}</td>
                                          @foreach($row->result_array as $each)
                                              <td>{{$each['grade']}}</td>
                                              <td>({{$each['score']}})</td>
                                          @endforeach
                                      </tr>
                                  @endforeach
                              @endif
                            </tbody>
                          </table>
                        </div>
                    </div>

                    <div class="detail-box">
                        <h3 style="display:inline;">OUTPUT履歴</h3><span id="userOutputLogsTotalCount" class="part-data-total-count"></span>
                        <div id="userOutputLogsDiv" class="ouput-history detail-history-box">
                          <table id="userOutputLogsTable" class="table table-hover core-tbl"
                                 data-total-count="0" data-current-count="0">
                            <thead>
                              <tr>
                                <th class="number-col">No.</th>
                                <th>ID</th>
                                <th>Lesson Date</th>
                                <th>Session ID</th>
                                <th>Student ID</th>
                                <th>Sentence</th>
                            </thead>
                            <tbody>
                            </tbody>
                          </table>
                        </div>
                    </div>

                    <div class="detail-box">
                        <h3 style="display:inline;">レッスン受講状況</h3><span id="userLessonLogsTotalCount" class="part-data-total-count"></span>
                        <div id="userLessonLogsDiv" class="lesson-history">
                          <table id="userLessonLogsTable" class="table table-hover core-tbl"
                                 data-total-count="0" data-current-count="0">
                            <thead>
                              <tr>
                                <th class="number-col">No.</th>
                                <th>開始日時</th>
                                <th>終了日時</th>
                                <th>レッスン種別</th>
                                <th>担当講師</th>
                                <th>レッスン評価</th>
                            </thead>
                            <tbody>
                            </tbody>
                          </table>
                        </div>
                    </div>

                    <div class="detail-box">
                        <h3 style="display:inline;">宿題実施状況</h3><span id="userTaskLogsTotalCount" class="part-data-total-count"></span>
                            <a href="/task_calendar/{{$userId}}" target="task_calendar">
                                <button class="btn btn-domain">出題カレンダーを開く</button>
                            </a>
                        <div id="userTaskLogsDiv" class="detail-task-box">
                          <table id="userTaskLogsTable" class="table table-hover core-tbl"
                                 data-total-count="0" data-current-count="0">
                            <thead>
                              <tr>
                                <th class="number-col">No.</th>
                                <th>出題日 / 期限日</th>
                                <th>what</th>
                                <th>完了日時</th>
                                <th style="border-right: none;">フィードバック返却日</th>
                                <th></th>
                            </thead>
                            <tbody>
                            </tbody>
                          </table>
                        </div>
                    </div>

                {{--
                    <div class="detail-box">
                        <h3 style="display:inline;">メッセージ送受信履歴</h3><span id="userMessageLogsTotalCount" class="part-data-total-count"></span>
                        <div id="userMessageLogsDiv" class="message-log-box">
                          <table id="userMessageLogsTable" class="table table-hover core-tbl"
                                 data-total-count="0" data-current-count="0">
                            <thead>
                              <tr>
                                <th>No.</th>
                                <th>送受信</th>
                                <th>日時</th>
                                <th>カテゴリ</th>
                                <th>送信者</th>
                                <th>送受信メッセージ</th>
                            </thead>
                            <tbody>
                            </tbody>
                          </table>
                        </div>
                    </div>
                --}}

                </div><!-- <div class="detail-main"> -->
            </div><!-- <div class="detail-section"> -->

        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="newPolicyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="hero-section new-policy-section">
                        <div class="secondary-item">
                            <span>学習方針新規登録</span>
                        </div>
                        <div class="hero-form row">
                            <div class="col-md-6">
                                <span>会員ID</span>
                                <input type="text" class="form-control" name="lp-alugo-user-id"
                                       value="{{$memberDetail->userBasicInfo->arugoUserId}}"
                                       readonly />
                                <input type="hidden" class="form-control" name="lp-user-id"
                                       value="{{$memberDetail->userBasicInfo->userId}}" />
                            </div>

                            <div class="col-md-6">
                                <span>投稿日</span>
                                <input type="text" class="form-control" name="lp-created_at"
                                       value="{{date('Y-m-d H:i:s')}}"
                                       readonly />
                            </div>

                            <div class="col-md-6">
                                <span>カテゴリ大</span>
                                <select class="form-control" name="lp-category1">
                                        <option value=""></option>
                                    @foreach($lp_category1s as $key => $val)
                                        <option value="{{$val->id}}">
                                            {{$val->category_name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <span>カテゴリ小</span>
                                <select class="form-control" name="lp-category2">
                                    <option value=""></option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <span>タグ</span>
                                <input type="text" class="form-control" name="lp-tag" value="" />
                            </div>

                            <div class="col-md-12">
                                <span>学習方針</span>
                                <input type="text" class="form-control" name="lp-policy" value="" />
                            </div>

                            <div class="col-md-12">
                                <span>備考</span>
                                <input type="text" class="form-control" name="lp-remark" value="" />
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" name="create-lp-btn" class="btn btn-register">登録する</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="lessonDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="hero-section new-policy-section">
                        <div class="secondary-item">
                            <span>レッスン詳細（No.XXXX）</span>
                        </div>
                        <div class="hero-form">
                            <div class="hero-form-item">
                                <span>開始日時</span>
                                <div class="form-group" id="datepicker-daterange">
                                    <div class="form-inline">
                                        <div class="input-daterange input-group new-policy-date" id="datepicker">
                                            <input type="text" class="input-md time-input form-control" name="start"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="hero-form-item">
                                <span>終了日時</span>
                                <div class="form-group" id="datepicker-daterange">
                                    <div class="form-inline">
                                        <div class="input-daterange input-group new-policy-date" id="datepicker">
                                            <input type="text" class="input-md time-input form-control" name="start"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="hero-form-item">
                                <span>レッスン種別</span>
                                <input class="form-control"/>
                            </div>
                            <div class="hero-form-item">
                                <span>担当講師</span>
                                <input class="form-control"/>
                            </div>
                            <div class="hero-form-item">
                                <span>アンケート結果</span>
                                <input class="form-control"/>
                            </div>
                            <div class="hero-form-item">
                                <span>自由テキスト</span>
                                <textarea class="form-control" rows="5"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-register">登録する</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="taskDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="hero-section">
                        <div class="secondary-item">
                            <span name="title"></span>
                        </div>
                        <div class="hero-form">
                            <div class="hero-form-item">
                                <span>生徒名</span>
                                <input name="memberName" class="form-control" readonly />
                            </div>
                            <div class="hero-form-item">
                                <span>オペレータ名</span>
                                <input name="operatorName" class="form-control" readonly />
                            </div>
                            <div class="hero-form-item">
                                <span>出題カテゴリ</span>
                                <input name="taskTypeName" class="form-control" readonly />
                            </div>
                            <div class="hero-form-separate">
                                <div class="hero-form-item">
                                    <span>出題日時</span>
                                    <input name="questionSetDate" class="form-control" readonly />
                                </div>
                                <div class="hero-form-item hero-form-second-item">
                                    <span>期限日</span>
                                    <input name="answerDeadlineDate" class="form-control" readonly />
                                </div>
                            </div>
                            <div class="hero-form-separate">
                                <div class="hero-form-item">
                                    <span>回答日時</span>
                                    <input name="answeredDate" class="form-control" readonly />
                                </div>
                                <div class="hero-form-item" >
                                    <span>フィードバック日時</span>
                                    <input name="feedbackDate" class="form-control" readonly />
                                </div>
                            </div>

                            <div class="hero-form-item">
                                <span>回答結果</span>
                                <button name="modal-btn-answer-download"
                                        class="form-control btn btn-domain modal-btn-voice-dl"
                                        data-answer-url=""
                                        disabled="disabled">音声DL</button>
                            </div>

                            <div class="hero-form-item">
                                <span>フィードバック内容</span>
                                <textarea name="feedbackComment" class="form-control" rows="5" readonly></textarea>
                            </div>
                            <!--
                            <div class="hero-form-item">
                                <span>生徒アセスメント情報</span>
                                <textarea class="form-control" rows="5"></textarea>
                            </div>-->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" name="closeTaskDetailModal" class="btn btn-close">閉じる</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="hero-section new-policy-section">
                        <div class="secondary-item">
                            <span>メッセージ詳細（No. XXXX）</span>
                        </div>
                        <div class="hero-form">
                            <div class="hero-form-item">
                                <span>ID</span>
                                <div class="form-group">
                                    <input class="form-control"/>
                                </div>
                            </div>
                            <div class="hero-form-item">
                                <span>送信/受信</span>
                                <div class="form-group">
                                    <input class="form-control"/>
                                </div>
                            </div>
                            <div class="hero-form-item">
                                <span>レッスン日時</span>
                                <div class="form-group" id="datepicker-daterange">
                                    <div class="form-inline">
                                        <div class="input-daterange input-group new-policy-date" id="datepicker">
                                            <input type="text" class="input-md time-input form-control" name="start"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="hero-form-item">
                                <span>カテゴリ</span>
                                <div class="dropdown">
                                    <button class="btn btn-default btn-md dropdown-toggle" type="button"
                                            id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="true">
                                        カテゴリ
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                        <li><a href="#">カテゴリ</a></li>
                                        <li><a href="#">カテゴリ</a></li>
                                        <li><a href="#">カテゴリ</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="hero-form-item">
                                <span>カウンセラー担当者</span>
                                <input class="form-control" style="width: 320px !important;"/>
                                <div class="btn btn-domain" style="margin: 0 10px;"> 担当カウンセラー一覧</div>
                            </div>
                            <div class="hero-form-item">
                                <span>学習方針</span>
                                <textarea class="form-control" rows="5"></textarea>
                            </div>
                            <div class="hero-form-item">
                                <span>自由テキスト</span>
                                <textarea class="form-control" rows="5"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-register">登録する</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="editPolicyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="hero-section new-policy-section">
                        <div class="secondary-item">
                            <span>学習方針詳細（No.XXXX）</span>
                        </div>
                        <div class="hero-form">
                            <div class="hero-form-item">
                                <span>ID</span>
                                <input class="form-control"/>
                            </div>
                            <div class="hero-form-item">
                                <span>投稿日</span>
                                <div class="form-group" id="datepicker-daterange">
                                    <div class="form-inline">
                                        <div class="input-daterange input-group new-policy-date" id="datepicker">
                                            <input type="text" class="input-md time-input form-control" name="start"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="hero-form-item">
                                <span>投稿カテゴリ</span>
                                <div class="dropdown">
                                    <button class="btn btn-default btn-md dropdown-toggle" type="button"
                                            id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="true">
                                        投稿カテゴリ
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                        <li><a href="#">投稿カテゴリ</a></li>
                                        <li><a href="#">投稿カテゴリ</a></li>
                                        <li><a href="#">投稿カテゴリ</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="hero-form-item">
                                <span>学習方針</span>
                                <textarea class="form-control" rows="5"></textarea>
                            </div>
                            <div class="hero-form-item">
                                <span>自由テキスト</span>
                                <textarea class="form-control" rows="5"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-register">登録する</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ mix('/js/member_detail.js') }}"></script>
@endsection
