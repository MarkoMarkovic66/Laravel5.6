@extends('layouts.Admin.master')

@section('styles')
@endsection

@section('body-contents')

<?php
use App\Enums\SessionValiableType;
use App\Enums\AccountKindType;

$accountInfo = Session::get(SessionValiableType::LOGIN_ACCOUNT_INFO);
$accountKindId = $accountInfo['account_kind_id'];
$searchCondOption_Status = $view_params['searchCondOption_Status'];
$searchCondOption_Member = $view_params['searchCondOption_Member'];
$searchCondOption_Operator = $view_params['searchCondOption_Operator'];
$isOnlyNoAssingnOperatorTask = $view_params['isOnlyNoAssingnOperatorTask'];

$backToList = $view_params->get('backToList');
if($backToList){
    //前回検索条件を取得
    $params = $view_params->get('searchCondition');
    $questionSetDateSince    = $params->get('questionSetDateSince');
    $questionSetDateUntil    = $params->get('questionSetDateUntil');
    $answerDeadlineDateSince = $params->get('answerDeadlineDateSince');
    $answerDeadlineDateUntil = $params->get('answerDeadlineDateUntil');
    $answeredDateSince       = $params->get('answeredDateSince');
    $answeredDateUntil       = $params->get('answeredDateUntil');
    //$freeword = $params->get('freeword');
    $selectStudent = $params->get('selectStudent');
    $selectStatus = $params->get('selectStatus');
    $selectOperator = $params->get('selectOperator');
    $pageNo = $params->get('pageNo');
}else{
    $params = '';
    $questionSetDateSince    = '';
    $questionSetDateUntil    = '';
    $answerDeadlineDateSince = '';
    $answerDeadlineDateUntil = '';
    $answeredDateSince       = '';
    $answeredDateUntil       = '';
    //$freeword = '';
    $selectStudent = '';
    $selectStatus = '';
    $selectOperator = '';
    $pageNo = '';
    //オペレータログイン時
    if($accountKindId == AccountKindType::ACCOUNT_OPERATOR_ID){
        $selectOperator = $accountInfo['id'];
    }
}

?>

<div class="mainbar">
  <div class="primary-item">
      <span>宿題管理</span>
  </div>

  <div class="row">

    <!-- 左パネル -->
    <input type="hidden" id="accountKindId" value="{{ $accountKindId }}" />
    @if( $accountKindId == 1 || $accountKindId == 2 )
    <div class="task-left-panel" style="width:100%;">
    @else
    <div class="task-left-panel-staff" style="width:100%;">
    @endif

        <!-- 左パネルのスクロール設定 -->
        <!-- <div class="task-left-panel-scroll"> -->
        <div class="task-left-panel-inner">

            <div class="hero-section">
              <div class="secondary-item">
                <span>検索項目</span>
                <input type="hidden" name="backToList" value="{{$backToList}}" />
                <input type="hidden" name="searchCondition" value="{{$params}}" />
              </div>

              <div class="secondary-box">

                <!-- serach form updated -->
                <div class="search-container">

                  <div class="search-line row">

                    <div class="search-item col-md-4">
                      <div class="form-group">
                        <div class="form-inline">
                          <label>宿題種別</label>
                          <div class="dropdown" style="width: 60%">
                            <select name="selectTaskType" class="form-control select2" plaseholder="選択してください">
                              <option value="">指定なし</option>
                              <option value="1" >瞬間口頭英作 文法</option>
                              <option value="2" >瞬間口頭英作 語彙</option>
                              <option value="3" >Speaking Rally</option>
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="search-item col-md-4">
                      <div class="form-group">
                        <div class="form-inline">
                          <label>生徒</label>
                          <div class="dropdown" style="width: 70%">
                            <select name="selectStudent" class="form-control select2" plaseholder="選択してください">
                              <option value="">指定なし</option>
                              @foreach($searchCondOption_Member as $userId => $value)
                                @if($userId == $selectStudent)
                                  <option value="{{ $userId }}" selected>{{ $value['alugoUserId'].' '.$value['userName'] }}</option>
                                @else
                                  <option value="{{ $userId }}">{{ $value['alugoUserId'].' '.$value['userName'] }}</option>
                                @endif
                              @endforeach
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>

                  <div class="search-line row">
                    <!-- <div class="search-item col-md-6 col-sm-12"> -->
                    <div class="search-item col-md-5">
                      <div class="form-group" id="datepicker-daterange">
                        <div class="form-inline">
                          <label>出題日</label>
                          <div class="input-daterange input-group" id="datepicker" style="width: 70%;">
                            <input type="text" class="input-md time-input form-control" name="questionSetDateSince" value="{{$questionSetDateSince}}" />
                            <span class="input-group-addon">〜</span>
                            <input type="text" class="input-md time-input form-control" name="questionSetDateUntil" value="{{$questionSetDateUntil}}" />
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- <div class="search-item col-md-6 col-sm-12"> -->
                    <div class="search-item col-md-5">
                      <div class="form-group" id="datepicker-daterange">
                        <div class="form-inline">
                          <label>回答期限日</label>
                          <div class="input-daterange input-group" id="datepicker" style="width: 70%;">
                            <input type="text" class="input-md time-input form-control" name="answerDeadlineDateSince" value="{{$answerDeadlineDateSince}}" />
                            <span class="input-group-addon">〜</span>
                            <input type="text" class="input-md time-input form-control" name="answerDeadlineDateUntil" value="{{$answerDeadlineDateUntil}}" />
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>

                  <div class="search-line row">
                    <!-- <div class="search-item col-md-6 col-sm-12"> -->

                    <div class="search-item col-md-5">
                      <div class="form-group" id="datepicker-daterange">
                        <div class="form-inline">
                          <label>回答日</label>
                          <div class="input-daterange input-group" id="datepicker" style="width: 70%;">
                            <input type="text" class="input-md time-input form-control" name="answeredDateSince" value="{{$answeredDateSince}}" />
                            <span class="input-group-addon">〜</span>
                            <input type="text" class="input-md time-input form-control" name="answeredDateUntil" value="{{$answeredDateUntil}}" />
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="search-item col-md-5">
                      <div class="form-group">
                        <div class="form-inline">
                          <label>ステータス</label>
                          <div class="dropdown" style="width: 60%">
                            <select name="selectStatus" class="form-control select2" plaseholder="選択してください">
                              <option value="">指定なし</option>
                              @foreach($searchCondOption_Status as $key => $value)
                                @if($key == $selectStatus)
                                  <option value="{{ $key }}" selected>{{ $value }}</option>
                                @else
                                  <option value="{{ $key }}">{{ $value }}</option>
                                @endif
                              @endforeach
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>

                  <div class="search-line row">

@if($accountKindId != AccountKindType::ACCOUNT_OPERATOR_ID)
                    <div class="search-item col-md-4">
                      <div class="form-group">
                        <div class="form-inline">
                          <label>オペレータ</label>
                  @if($isOnlyNoAssingnOperatorTask)
                          <div class="dropdown" style="width: 70%">
                            <select name="searchSelectOperator" class="form-control select2" plaseholder="選択してください" disabled="disabled" >
                              <option value="">指定なし</option>
                              @foreach($searchCondOption_Operator as $key => $value)
                                @if($key == $selectOperator)
                                  <option value="{{ $key }}" selected>{{ $value }}</option>
                                @else
                                  <option value="{{ $key }}">{{ $value }}</option>
                                @endif
                              @endforeach
                            </select>
                          </div>
                  @else
                          <div class="dropdown" style="width: 70%">
                            <select name="searchSelectOperator" class="form-control select2" plaseholder="選択してください">
                              <option value="">指定なし</option>
                              @foreach($searchCondOption_Operator as $key => $value)
                                @if($key == $selectOperator)
                                  <option value="{{ $key }}" selected>{{ $value }}</option>
                                @else
                                  <option value="{{ $key }}">{{ $value }}</option>
                                @endif
                              @endforeach
                            </select>
                          </div>
                  @endif
                        </div><!-- <div class="form-inline"> -->
                      </div><!-- <div class="form-group"> -->
                    </div><!-- <div class="search-item col-md-4"> -->

                    <div class="search-item col-md-4">
                      <div class="form-group">
                        <div class="form-inline" style="margin-top: 6px;">
                  @if($isOnlyNoAssingnOperatorTask)
                          <span style="margin-top:1px; margin-left:15px;"><input type="checkbox" name="onlyNoAssignOperator" id="onlyNoAssignOperator" style="height:auto" value="1" checked></span>
                  @else
                          <span style="margin-top:1px; margin-left:15px;"><input type="checkbox" name="onlyNoAssignOperator" id="onlyNoAssignOperator" style="height:auto" value="1"></span>
                  @endif
                          <label class="form-check-label" for="onlyNoAssignOperator" style="margin-left: 10px;">オペレータ未設定タスクに限定</label>
                        </div><!-- <div class="form-inline"> -->
                      </div><!-- <div class="form-group"> -->
                    </div><!-- <div class="search-item col-md-4"> -->
@else
                  <input type="hidden" name="onlyNoAssignOperator" id="onlyNoAssignOperator" value="1">
@endif
                  </div><!-- <div class="search-line row"> -->

                </div><!-- <div class="search-container"> -->
                <!-- /serach form updated -->

<!--
                    <div class="search-secondary-box">
                      <div class="search-item">
                        <div class="form-group">
                          <div class="form-inline">
                              <label>フリーワード</label>
                              <input type="text" class="form-control input-md" name="freeword" placeholder="検索する" style="width:600px;" />
                          </div>
                        </div>
                      </div>
                    </div>
-->
                    <div class="search-button-box">
                      <div class="search-btn-item">
                        <button class="btn btn-clear">クリア</button>
                      </div>
                      <div class="search-btn-item">
                        <button class="btn btn-search">検索する</button>
                      </div>
                    </div>

                  </div><!-- <div class="secondary-box"> -->
            </div><!-- hero-section -->

            <div class="hero-section">
              <div class="secondary-item">
                <span>宿題一覧</span>
              </div>


            <!-- <div class="secondary-box" style="height:1063px; overflow:scroll;"> -->
            <div class="secondary-box task-list-wrapper">
                <div id="task-list"></div>
            </div>
            <div id="task-list-pagination"></div>
          </div><!-- hero-section -->

      </div><!-- task-left-panel-inner -->

    </div><!-- task-left-panel -->

  </div>
</div><!-- main bar -->


    <!-- feedbackModal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <span style="font-weight:bold;">出題・回答確認/フィードバック</span>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="hero-section">
              <div class="secondary-item">
                <span name="header-title"></span>
              </div>

              <div class="hero-form">
                <div class="hero-form-separate">
                  <div class="hero-form-item">
                    <span>宿題種別</span>
                    <input name="task-type-name" class="form-control" readonly />
                  </div>
                  <div class="hero-form-item hero-form-second-item">
                    <span>ステータス</span>
                    <input name="task-status-name" class="form-control" readonly />
                  </div>
                </div>

                <div class="hero-form-separate">
                  <div class="hero-form-item">
                    <span>生徒名</span>
                    <input name="user-name" class="form-control" readonly />
                    <input type="hidden" name="user-id" value="" />
                    <input type="hidden" name="user-task-id" value="" />
                  </div>
                  <div class="hero-form-item hero-form-second-item">
                  <span>オペレーター名</span>
                  <input name="operator-name" class="form-control" readonly />
                  <input type="hidden" name="operator-id" class="form-control" readonly />
                  </div>
                </div>

                <div class="hero-form-separate">
                  <div class="hero-form-item">
                    <span>出題日時</span>
                    <input name="question-set-date" class="form-control" readonly />
                  </div>
                  <div class="hero-form-item hero-form-second-item">
                    <span>回答期限日</span>
                    <input name="answer-deadline-date" class="form-control" readonly />
                  </div>
                </div>

                <div class="hero-form-separate">
                  <div class="hero-form-item">
                    <span>回答日時</span>
                    <input name="answered-date" class="form-control" readonly />
                  </div>

                  <div class="hero-form-item hero-form-second-item">
                    <span>回答音声</span>
                    <input type="hidden" id="answer-url" value="" />
                    <button name="modal-btn-answer-download"
                          class="form-control btn btn-default modal-btn-voice-dl"
                          disabled="disabled">音声DL</button>
                  </div>
                </div>

                <!-- 瞬間口頭英作GV用 -->
                <div class="task-gv hero-form-item" style="">
                  <span style="width:154px;">出題内容</span>
                  <textarea name="task-question-gv" class="form-control" rows="5" readonly></textarea>
                </div>

                <!-- SR用 -->
                <div class="task-sr hero-form-item" style="display:none;">
                  <span style="width:154px;">SR出題テーマ</span>
                  <textarea name="task-question-sr" class="form-control" rows="1" readonly></textarea>
                </div>
                <div class="task-sr-draft hero-form-item" style="display:none;">
                  <span style="width:154px;">SR生徒下書き</span>
                  <textarea name="task-draft-sr" class="form-control" rows="3" readonly></textarea>
                </div>

                <div class="task-feedback hero-form-item">
                  <span style="width:154px;">フィードバック</span>
                  <textarea name="feedback-text" class="form-control" rows="5"></textarea>
                </div>

                <div class="task-feedback hero-form-separate">
                    <div class="hero-form-item">
                      <span>フィードバック日時</span>
                      <input name="feedback-date" class="form-control" readonly />
                    </div>
                    <div class="hero-form-item hero-form-second-item">
                      <span>フィードバック者</span>
                      <input name="feedback-by" class="form-control" readonly />
                    </div>
                </div>
                <div class="task-feedback hero-form-separate">
                    <div class="hero-form-item">
                      <span>差し戻し/承認日時</span>
                      <input name="approved-date" class="form-control" readonly />
                    </div>
                    <div class="hero-form-item hero-form-second-item">
                      <span>承認者</span>
                      <input name="approved-by" class="form-control" readonly />
                    </div>
                </div>

              </div>
            </div>
          </div>
          <div class="modal-footer">
            <span class="task-confirm" style="display:none;">
              <button type="button"
                      name="modal-btn-confirm"
                      class="btn btn-info"
                      onclick="AlueIntegOffice.TaskUtils.fbModalBtn('confirm');return false;">確認</button>
            </span>
            <span class="task-feedback" style="display:none;">
              <button type="button"
                      name="modal-btn-feedback-regist"
                      class="btn btn-register"
                      onclick="AlueIntegOffice.TaskUtils.fbModalBtn('regist');return false;">登録する</button>
            </span>

        @if($accountKindId == AccountKindType::ACCOUNT_OPERATOR_ID)
            <span class="task-approve" style="display:none;">
              <button type="button"
                      name="modal-btn-feedback-regist"
                      class="btn btn-success"
                      onclick="AlueIntegOffice.TaskUtils.fbModalBtn('regist');return false;">編集する</button>
            </span>

        @else
            <span class="task-approve" style="display:none;">
              <button type="button"
                      name="modal-btn-feedback-regist"
                      class="btn btn-success"
                      onclick="AlueIntegOffice.TaskUtils.fbModalBtn('regist');return false;">編集する</button>
              <button type="button"
                      name="modal-btn-feedback-reject"
                      class="btn btn-warning"
                      onclick="AlueIntegOffice.TaskUtils.fbModalBtn('reject');return false;">差し戻す</button>
              <button type="button"
                      name="modal-btn-feedback-approve"
                      class="btn btn-register"
                      onclick="AlueIntegOffice.TaskUtils.fbModalBtn('approve');return false;">承認する</button>
            </span>
        @endif
          </div>
        </div>
      </div>
    </div>

    <!-- answerDetailModal -->
    <!--
    <div class="modal fade" id="answerDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                <span name="header-title"></span>
              </div>
              <div class="hero-form">
                <div class="hero-form-item row">
                  <span class="col-md-2">生徒名</span>
                  <input name="member-name" class="form-control col-md-10" disabled="disabled" />
                </div>
                <div class="hero-form-item row">
                  <span class="col-md-2">オペレーター名</span>
                  <input name="operator-name" class="form-control col-md-10" disabled="disabled" />
                  <input type="hidden" name="operator-id" class="form-control col-md-10" disabled="disabled" />
                </div>
                <div class="hero-form-item row">
                  <span class="col-md-2">出題カテゴリ</span>
                  <input name="question-category" class="form-control col-md-10" disabled="disabled" />
                </div>

                <div class="hero-form-item row">
                  <span class="col-md-2">ステータス</span>
                  <input name="task-status-name" class="form-control col-md-10" disabled="disabled" />
                </div>

                <div class="hero-form-item row">
                  <span class="col-md-2">出題内容</span>
                  <input name="question-contents" class="form-control col-md-10" disabled="disabled" />
                </div>

                <div class="hero-form-item row">
                  <span class="col-md-2">出題日時</span>
                  <input name="question-set-date" class="form-control col-md-4" disabled="disabled" />
                  <span class="col-md-2">回答期限日</span>
                  <input name="answer-deadline-date" class="form-control col-md-4" disabled="disabled" />
                </div>

                <div class="hero-form-item row">
                  <span class="col-md-2">回答日時</span>
                  <input name="answered-date" class="form-control col-md-4" disabled="disabled" />
                  <span class="col-md-2">フィードバック日時</span>
                  <input name="feedback-date" class="form-control col-md-4" disabled="disabled" />
                </div>

                <div class="hero-form-item row">
                  <span class="col-md-2">回答結果</span>
                  <button name="modal-btn-answer-download"
                          class="form-control btn btn-answer modal-btn-voice-dl col-md-10"
                          data-answer-url="">音声DL</button>
                </div>

                <div class="hero-form-item row">
                  <span class="col-md-2">フィードバック内容</span>
                  <textarea name="feedback-text" class="form-control col-md-10" rows="5"  disabled="disabled" ></textarea>
                </div>
                <!--
                <div class="hero-form-item">
                  <span>生徒アセスメント情報</span>
                  <textarea class="form-control" rows="5"></textarea>
                </div>
                -- >
              </div>
            </div>
          </div>
          <!-- <div class="modal-footer">
            <button type="button" class="btn btn-domain" data-dismiss="modal">OK</button>
          </div> -- >
        </div>
      </div>
    </div>
    -->

    <!-- 紐付け登録confirmModal -->
    <!--
    <div class="modal fade" id="answerLinkConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="hero-section">
              <div class="hero-form">
                <div class="hero-form-item">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" name="modal-btn-answer-link-regist" class="btn btn-register">登録する</button>
          </div>
        </div>
      </div>
    </div>

    <!-- 紐付け解除confirmModal -- >
    <div class="modal fade" id="answerReleaseConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="hero-section">
              <div class="hero-form">
                <div class="hero-form-item">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" name="modal-btn-answer-release" class="btn btn-register">解除する</button>
          </div>
        </div>
      </div>
    </div>
    -->

    <!-- オペレータ変更完了Modal -- >
    <!--
    <div class="modal fade" id="operatorChangedNotifyModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="hero-section">
              <div class="hero-form">
                <div class="hero-form-item">
                    <sapn>オペレーターを変更しました。</sapn>
                </div>
              </div>
            </div>
          </div>
          <!--
          <div class="modal-footer">
          </div> -- >
        </div>
      </div>
    </div>
    -->

@endsection

@section('scripts')
<script src="{{ mix('/js/task.js') }}"></script>
@endsection
