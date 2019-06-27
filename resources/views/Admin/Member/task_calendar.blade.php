@extends('layouts.Admin.master')

@section('styles')
@endsection
<?php
use App\Dtos\TaskListPerCellDto;

$memberBasic = $view_params->get('memberBasic');
if(!empty($memberBasic)){
    $userBasicInfo = $memberBasic->userBasicInfo;
    $userId = $userBasicInfo->userId;
    $memberName = $userBasicInfo->lastName.' '.$userBasicInfo->firstName;
    $memberNameEn = $userBasicInfo->firstNameEn.' '.$userBasicInfo->lastNameEn;
}else{
    $userId = '';
    $memberName = '';
    $memberNameEn = '';
}

//1週間分のタスクカレンダー情報を取得
$taskListPerWeekDto = $view_params->get('taskCalendar');
?>

@section('body-contents')
    <div class="overall">
      <div class="hero-section">
        <div class="secondary-item">
          <span>{{$memberName}}さんの出題カレンダー</span>
          <input type="hidden" name="userId" value="{{$userId}}">
        </div>
        <div class="middle-list-section">
          {{--
          <div class="middle-list-left">
            <div class="btn btn-domain" name="div-btn-modal-grammar">出題予定のGrammar問題一覧</div>
            <div class="btn btn-domain" name="div-btn-modal-vocabulary">出題予定のVocabulary問題一覧</div>
          </div>
          --}}
          {{--
          <div class="middle-list-right">
            <div class="btn btn-domain">クリア</div>
            <div class="btn btn-register">変更を保存する</div>
          </div>
          --}}
        </div>

        <div class="overall">
          <div class="calendar">
            <div class="cal-row-head-item">
            </div>
            <div class="cal-item day-item">
              月
            </div>
            <div class="cal-item day-item">
              火
            </div>
            <div class="cal-item day-item">
              水
            </div>
            <div class="cal-item day-item">
              木
            </div>
            <div class="cal-item day-item">
              金
            </div>
            <div class="cal-item day-item">
              土
            </div>
            <div class="cal-item day-item">
              日
            </div>
          </div>

<?php
//【注意】出題情報を「コマ単位で曜日方向に」取得する
for($cellNumber = 1; $cellNumber < 6; $cellNumber++){
?>
        {{-- 「コマ単位で曜日方向」のブロック 開始 --}}
        <div class="calendar">
            <div class="cal-row-head-item text-title-item">
              出題{{$cellNumber}}
            </div>
<?php
    for($dayNumber = 1; $dayNumber < 8; $dayNumber++){
        //該当曜日＋コマのデータを取得
        $taskListPerCellDto = $taskListPerWeekDto->getTargetTaskCell($dayNumber, $cellNumber);
        if($taskListPerCellDto){//該当「曜日＋コマ」のデータ有り
            //選択されたタスク種別
            $selectedTaskType = $taskListPerCellDto->taskType;
            $selectedTaskTypeName = $taskListPerCellDto->taskTypeName;
            //当該「曜日＋コマ」のタスク種別選択肢情報を取得する
            $taskCategories = $taskListPerCellDto->getTaskCategories();

            //当該コマの問題一覧取得(口頭英作文V、Gの場合は複数あり)
            $questionList = [];
            $taskList = $taskListPerCellDto->taskList;
            foreach($taskList as $eachTask){
                $questionList[] = $eachTask->question;
            }
            $jsonQuestion = json_encode($questionList);//詳細表示モーダル用のデータ
?>
            <div class="cal-item text-item">{{-- 「曜日方向」のブロック 開始 --}}
              <div class="dropdown">
                <select name="select-task-type-{{$dayNumber}}-{{$cellNumber}}"
                        class="select2 select-task-type"
                        data-day-number="{{$dayNumber}}"
                        data-cell-number="{{$cellNumber}}">
                  @foreach($taskCategories as $key => $taskCategoryName)
                    @if($selectedTaskType == $key)
                        <option value="{{$key}}" selected>{{$taskCategoryName}}</option>
                    @else
                        <option value="{{$key}}">{{$taskCategoryName}}</option>
                    @endif
                  @endforeach
                </select>
              </div>

              <!-- 2018-12-24 詳細ボタンは不要
              <div name="div-btn-cal-detail-{{$dayNumber}}-{{$cellNumber}}"
                   class="btn btn-cal"
                   data-day-number="{{$dayNumber}}"
                   data-cell-number="{{$cellNumber}}"
                   data-cell-task-type="{{$selectedTaskType}}"
                   data-cell-task-type-name="{{$selectedTaskTypeName}}"
                   data-cell-question='{{$jsonQuestion}}'>詳細</div>
                -->

            </div>{{-- 「曜日方向」のブロック 終了 --}}
<?php
        }else{//該当「曜日＋コマ」のデータ無し
            //空の「曜日＋コマ」のタスク種別選択肢情報を生成する
            $taskListPerCellDto = new TaskListPerCellDto();
            //当該「曜日＋コマ」のタスク種別選択肢情報を取得する
            $taskCategories = $taskListPerCellDto->getTaskCategories();
?>
            <div class="cal-item text-item">{{-- 「曜日方向」のブロック 開始 --}}
              <div class="dropdown">
                <select name="select-task-type-{{$dayNumber}}-{{$cellNumber}}"
                        class="select2 select-task-type"
                        data-day-number="{{$dayNumber}}"
                        data-cell-number="{{$cellNumber}}">
                  @foreach($taskCategories as $key => $taskCategoryName)
                    @if($key == '0')
                        <option value="{{$key}}" selected>{{$taskCategoryName}}</option>
                    @else
                        <option value="{{$key}}">{{$taskCategoryName}}</option>
                    @endif
                  @endforeach
                </select>
              </div>

              <!-- 2018-12-24 詳細ボタンは不要
              <div name="div-btn-cal-detail-{{$dayNumber}}-{{$cellNumber}}"
                   class="btn btn-cal btn-no-detail-data"
                   data-day-number="{{$dayNumber}}"
                   data-cell-number="{{$cellNumber}}"
                   data-cell-task-type="0"
                   data-cell-task-type-name=""
                   data-cell-question="">詳細</div>
              -->

            </div>{{-- 「曜日方向」のブロック 終了 --}}
<?php
        }
    }
?>
        </div>
        {{-- 「コマ単位で曜日方向」のブロック 終了 --}}
<?php
}
?>
            <div style="margin-left:74px; color:red;">※出題カレンダーの変更は翌週より有効となります。</div>
        </div>
      </div>
      <!--
      <div class="hero-section">
        <div class="secondary-item">
          <span>出題予定の英作文一覧</span>
        </div>
        <div class="overall">
          <div class="cal-g-box">
            <div class="cal-g-id cal-g-item">
              1(G)
            </div>
            <div class="cal-g-text cal-g-item">
            </div>
            <div class="cal-g-select cal-g-item">
              <div class="dropdown">
                <button class="btn btn-default btn-md dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                  予備問題と切り替える
                  <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                  <li><a href="#">予備問題と切り替える</a></li>
                  <li><a href="#">予備問題と切り替える</a></li>
                  <li><a href="#">予備問題と切り替える</a></li>
                </ul>
              </div>
            </div>
            <div class="cal-g-button cal-g-item">
              <div class="btn btn-domain" data-toggle="modal" data-target="#cMasterModal">フィードバック登録</div>
            </div>
          </div>
        </div>
      </div>
      -->
    </div>

    <!-- Modal -->
    <div class="modal fade modal-change-task-master" id="cMasterModal" tabindex="-1" role="dialog" aria-hidden="true">
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

              <div class="c-master-modal-search">
                  <input type="hidden" name="qtype" value="" />
                  <input type="hidden" name="userId" value="" />
                  <input type="hidden" name="orgTaskId" value="" />
                  <input type="hidden" name="userTaskPeriodId" value="" />

                <div class="c-master-modal-input">
                    <label>Module</label>
                    <input class="form-control" name="module" />
                </div>
                <div class="c-master-modal-input">
                    <label>Grade</label>
                    <input class="form-control" name="grade" />
                </div>
                <div class="c-master-modal-input">
                    <label>Stage</label>
                    <input class="form-control" name="stage" />
                </div>
                {{--
                <div class="c-master-modal-input">
                    <label>TIPS</label>
                    <input class="form-control" name="tips" />
                </div>
                 --}}
                <div class="c-master-modal-input">
                    <label>Focus</label>
                    <input class="form-control" name="focus" />
                </div>
                <div class="c-master-modal-input">
                    <label>フリーワード</label>
                    <input class="form-control" name="freeword" />
                </div>

                <div class="c-master-modal-search">
                  <button type="button" class="btn btn-search">検索する</button>
                </div>
              </div>

              <div class="c-master-modal-search-result-scroll">
                <div class="c-master-modal-search-result"></div>
              </div>

            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="calendarDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
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

              <div name="question-detail" class="hero-form calendar-form-item question-detail"></div>

            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">閉じる</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade modal-change-task" id="grammarModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                <span>出題予定のGrammar問題一覧</span>
              </div>

                <div name="task-list-scroll" class="task-list-scroll">
                  <div name="task-list" class="hero-form problem-form-item task-list"></div>
                </div>

            </div>
          </div>
          {{--
          <div class="modal-footer">
            <button type="button" name="btn-grammar-modal-regist" class="btn btn-register">登録する</button>
          </div>
          --}}
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade modal-change-task" id="vocabularyModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                <span>出題予定のVocabulary問題一覧</span>
              </div>

                <div name="task-list-scroll" class="task-list-scroll">
                  <div name="task-list" class="hero-form problem-form-item task-list"></div>
                </div>

            </div>
          </div>
          {{--
          <div class="modal-footer">
            <button type="button" name="btn-vocabulary-modal-regist" class="btn btn-register">登録する</button>
          </div>
          --}}
        </div>
      </div>
    </div>

@endsection

@section('scripts')
<script src="{{ mix('/js/task_calendar.js') }}"></script>
@endsection

