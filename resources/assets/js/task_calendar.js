/**
 * 出題カレンダー javascript
 */
AlueIntegOffice.TaskCalendarUtils = {

    URL_GET_TASK_ASSIGN_LIST       : '/api/get_task_assign_list',

    URL_CHANGE_TASK_TYPE           : '/api/change_task_type',
    URL_MODAL_CHANGE_TASK          : '/api/modal_change_task',
    URL_REDIRECT_TASK_CALENDAR_TOP : '/task_calendar/', // '/task_calendar/{userId}'

    URL_MODAL_SEARCH_TASK_MASTER   : '/api/modal_search_task_master',
    URL_MODAL_CHANGE_TASK_MASTER   : '/api/modal_change_task_master',

    /**
     * Grammar/Vocabulary出題予定モーダル：タスク種別変更
     * @returns {undefined}
     */
    onChangeTaskToReserveTask : function(e) {
        var qtype = $(e.target).data('qtype');
        var userTaskPeriodId = $(e.target).closest('td[name="new-question"]').data('user-task-period-id');
        var newTaskId = $(e.target).find('option:selected').val();
        var orgTaskId = $(e.target).closest('tr').find('td[name="org-question"]').data('org-task-id');

        AlueIntegOffice.Utils.dispOnLoadingIcon(true);

        var postData = {
                "qType"      : qtype,
                "userTaskPeriodId" : userTaskPeriodId,
                "orgTaskId"  : orgTaskId,
                "newTaskId"  : newTaskId
            };

        $.ajax({
            type : 'POST',
            url  : this.URL_MODAL_CHANGE_TASK,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            //AlueIntegOffice.Utils.dispOnLoadingIcon(false);

            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueIntegOffice.AjaxUtils.userErrorHandle(response);
                return false;
            }

            //モーダル閉じる
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            if(qtype == 'Grammar'){
                $('#grammarModal').modal('hide');
            }else if(qtype == 'Vocabulary'){
                $('#vocabularyModal').modal('hide');
            }

            //タスクカレンダー画面を再表示する
            var userId = $(document).find('input[name="userId"]').val();
            var url = AlueIntegOffice.TaskCalendarUtils.URL_REDIRECT_TASK_CALENDAR_TOP + userId;
            AlueIntegOffice.AjaxUtils.submitGetForm(url, {});
            return false;

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);
            //AlueIntegOffice.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    /**
     * Grammar/Vocabulary出題予定モーダル：タスク種別変更：マスタから切り替えモーダル開く
     * @returns {undefined}
     */
    openModalToChangeMasterTask : function(e) {

        var userId = $(e.target).closest('table').data('user-id');
        var parentObj = $(e.target).closest('tr');
        var orgTaskId = $(parentObj).find('td[name="org-question"]').data('org-task-id');
        var userTaskPeriodId = $(parentObj).find('td[name="mst-question"]').data('user-task-period-id');

        //前回の検索条件をクリア
        $('div.c-master-modal-search input').val('');
        //前回の検索結果をクリア
        $('div.c-master-modal-search-result').empty();

        //モーダルにデータをセット
        var qtype = $(e.target).data('qtype');
        var panelObj = $('#cMasterModal').find('div.modal-body');
        //タイトル
        var title = '';
        if(qtype == 'Grammar'){
            title = 'Grammar問題切り替え';
        }else if(qtype == 'Vocabulary'){
            title = 'Vocabulary問題切り替え';
        }
        $(panelObj).find('span[name="title"]').html(title);
        $(panelObj).find('input[name="qtype"]').val(qtype);
        $(panelObj).find('input[name="userId"]').val(userId);

        //変更対象タスクの各情報
        $(panelObj).find('input[name="orgTaskId"]').val(orgTaskId);
        $(panelObj).find('input[name="userTaskPeriodId"]').val(userTaskPeriodId);

        //モーダル開く
        $('#cMasterModal').modal();
    },
    /**
     * G/Vマスタ検索
     * @param {type} e
     * @returns {undefined}
     */
    searchGVmaster : function(e) {
        var searchPanel = $('#cMasterModal div.modal-body div.c-master-modal-search');
        var search_qtype      = $(searchPanel).find('input[name="qtype"]').val();
        var search_module     = $(searchPanel).find('input[name="module"]').val();
        var search_grade      = $(searchPanel).find('input[name="grade"]').val();
        var search_stage      = $(searchPanel).find('input[name="stage"]').val();
       // var search_tips       = $(searchPanel).find('input[name="tips"]').val();
        var search_focus      = $(searchPanel).find('input[name="focus"]').val();
        var search_freeword   = $(searchPanel).find('input[name="freeword"]').val();

        AlueIntegOffice.Utils.dispOnLoadingIcon(true);

        var postData = {
            "search_qtype"    : search_qtype,
            "search_module"	  : search_module,
            "search_grade"    : search_grade,
            "search_stage"    : search_stage,
            //"search_tips"     : search_tips,
            "search_focus"    : search_focus,
            "search_freeword" : search_freeword
        };

        $.ajax({
            type : 'POST',
            url  : this.URL_MODAL_SEARCH_TASK_MASTER,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);

            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueIntegOffice.AjaxUtils.userErrorHandle(response);
                return false;
            }

            if(response.data.count < 1){
                var resultMsg = '<div class="modal-guidance-message">'
                               + '該当データがありません。'
                               + '</div>';
                $('div.c-master-modal-search-result').empty();
                $('div.c-master-modal-search-result').html(resultMsg);
                return false;
            }

            //一覧リストのDOMを生成する
            var taskList = response.data.taskMasters;
            var taskListHtml = '<table class="table table-sm table-hover task-list">';
            $.each(taskList, function(idx, row){
                taskListHtml += AlueIntegOffice.TaskCalendarUtils.createSearchResultRowHtml(idx, row);
            });
            taskListHtml += '</tbody></table>';

            //一覧リストのDOMを描画する
            $('div.c-master-modal-search-result').empty();
            $('div.c-master-modal-search-result').html(taskListHtml);

            //生成したDOMにイベントを付与する
            AlueIntegOffice.TaskCalendarUtils.setEventHandlerAfterMasterSearch();

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);
            //AlueIntegOffice.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    createSearchResultRowHtml : function(idx, row){
        var listRowHtml = '';
        if(idx < 1){
            //先頭行の場合は見出し行を生成
            listRowHtml += '<thead class="thead-default"><tr>'
                + '<th class="font-weight-bold">No</th>'
                + '<th class="font-weight-bold">Code</th>'
                + '<th class="font-weight-bold">Grade</th>'
                + '<th class="font-weight-bold">Stage</th>'
                + '<th class="font-weight-bold">Focus</th>'
                //+ '<th class="font-weight-bold">Tips</th>'
                + '<th class="font-weight-bold">Question</th>'
                + '<th class="font-weight-bold">切り替え</th>'
                + '</tr></thead>'
                + '<tbody>';
        }

        var changeButtonHtml = '<button name="btn-change-task" class="btn btn-domain">切り替える</button>';

        listRowHtml += '<tr class="table-row"'
                     + ' data-task-master-id="' + row.id + '">'
            + '<td>' + (idx+1) + '</td>'
            + '<td>' + row.code + '</td>'
            + '<td>' + row.grade + '</td>'
            + '<td>' + row.stage + '</td>'
            + '<td>' + row.focus + '</td>'
            //+ '<td>' + row.tips + '</td>'
            + '<td>' + row.question + '</td>'
            + '<td>' + changeButtonHtml + '</td>'
            + '</tr>';

        return listRowHtml;
    },
    setEventHandlerAfterMasterSearch : function() {

        $('div.c-master-modal-search-result table.task-list button[name="btn-change-task"]')
        .on('click', function(e){ //問題切り替えボタンクリック
            var taskMasterId = $(e.target).closest('tr').data('task-master-id');
            var orgTaskId = $('div.c-master-modal-search input[name="orgTaskId"]').val();
            var userTaskPeriodId = $('div.c-master-modal-search input[name="userTaskPeriodId"]').val();
            var userId = $('div.c-master-modal-search input[name="userId"]').val();
            var qtype = $('div.c-master-modal-search input[name="qtype"]').val();

            //問題をマスタから切り替える処理を実行
            AlueIntegOffice.TaskCalendarUtils.changeTaskToNewMasterData(qtype, userId, userTaskPeriodId, orgTaskId, taskMasterId);
        });
    },
    //問題をマスタから切り替える処理
    changeTaskToNewMasterData : function(qtype, userId, userTaskPeriodId, orgTaskId, taskMasterId) {
        AlueIntegOffice.Utils.dispOnLoadingIcon(true);

        var postData = {
            "qtype"            : qtype,
            "userId"           : userId,
            "userTaskPeriodId" : userTaskPeriodId,
            "orgTaskId"        : orgTaskId,
            "taskMasterId"     : taskMasterId
        };

        $.ajax({
            type : 'POST',
            url  : this.URL_MODAL_CHANGE_TASK_MASTER,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {

            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueIntegOffice.Utils.dispOnLoadingIcon(false);
                AlueIntegOffice.AjaxUtils.userErrorHandle(response);
                return false;
            }

            //モーダル閉じる
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $('#cMasterModal').modal('hide');
            $('#grammarModal').modal('hide');
            $('#vocabularyModal').modal('hide');

            //タスクカレンダー画面を再表示する
            var url = AlueIntegOffice.TaskCalendarUtils.URL_REDIRECT_TASK_CALENDAR_TOP + userId;
            AlueIntegOffice.AjaxUtils.submitGetForm(url, {});
            return false;

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);
            //AlueIntegOffice.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    /**
     * G/V出題予定モーダルへデータをセットする
     * @param {type} selectedTaskList
     * @param {type} reservedTaskList
     * @returns {undefined}
     */
    setDataToTaskAssignModal : function(qType, userId, selectedTaskList, reservedTaskList) {

        //モーダル画面設定
        var panelObj = null;
        if(qType == 'Grammar'){
            panelObj = $('#grammarModal').find('div.modal-body');
        }else if(qType == 'Vocabulary'){
            panelObj = $('#vocabularyModal').find('div.modal-body');
        }

        //予備問題選択リストプルダウン
        var reservedTaskSelectHtml = '<select class="select-reserve-task select2"'
                                   + ' data-qtype="' + qType + '">'
                                   + '<option value="0">予備問題と切替</option>';
        $.each(reservedTaskList, function(idx, reservedTask) {
            reservedTaskSelectHtml += '<option value="' + reservedTask.taskId + '"'
                                   + ' data-old-task-id="' + reservedTask.taskId + '">'
                                   + reservedTask.question
                                   + '</option>';
        });
        reservedTaskSelectHtml += '</select>';

        //マスタから切り替えボタン
        var changeTaskButtonHtml = '<button class="btn btn-mst-change-task"'
                                 + ' data-qtype="' + qType + '">'
                                 + 'マスタから切替</button>';

        //出題問題リスト
        var htmlText = '<table class="task-assign-table"'
                    + ' data-user-id="' + userId + '">'
                    + '<tr>'
                    + '<th>番号</th>'
                    + '<th>曜日</th>'
                    + '<th>出題枠</th>'
                    + '<th>No</th>'
                    + '<th>内容</th>'
                    + '<th>予備問題から切替</th>'
                    + '<th>マスタから切替</th>'
                    + '</tr>';

        var seqNo = 1;
        var dayNumberBreak = 0;
        var cellNumberBreak = 0;

        $.each(selectedTaskList, function(idxDay, taskListPerDay) {
            var dayNumber = taskListPerDay.dayNumber;
            var rowCountPerDay = taskListPerDay.taskCount;

            $.each(taskListPerDay.taskList, function(idxCell, taskListPerCell) {
                var cellNumber = taskListPerCell.cellNumber;
                var rowCountPerCell = taskListPerCell.taskCount;

                $.each(taskListPerCell.taskList, function(idxQuestion, taskListPerQuestion) {

                    var userTaskPeriodId = taskListPerQuestion.userTaskPeriodId;
                    var orgTaskId = taskListPerQuestion.taskId;

                    htmlText += '<tr>'
                            + '<td class="td-center">' + seqNo + '</td>';

                    if(dayNumberBreak != dayNumber){
                        dayNumberBreak = dayNumber;
                        cellNumberBreak = 0;
                        htmlText += '<td rowspan="' + rowCountPerDay + '"  class="td-center">'
                                 + AlueIntegOffice.Utils.getDayDisplay(dayNumber)
                                 + '</td>';
                    }

                    if(cellNumberBreak != cellNumber){
                        cellNumberBreak = cellNumber;
                        htmlText += '<td rowspan="' + rowCountPerCell + '"  class="td-center">'
                                 + '出題' + cellNumber
                                 + '</td>';
                    }

                    htmlText += '<td class="td-center">' + (idxQuestion+1) + '</td>'
                            + '<td name="org-question" data-org-task-id="' + orgTaskId + '">'
                                + taskListPerQuestion.question
                            + '</td>'

                            + '<td name="new-question" class="td-center td-pad5"'
                            +' data-user-task-period-id="' + userTaskPeriodId + '">'
                                + reservedTaskSelectHtml
                            + '</td>'

                            + '<td name="mst-question" class="td-center td-pad5"'
                            +' data-user-task-period-id="' + userTaskPeriodId + '">'
                                + changeTaskButtonHtml
                            + '</td>'

                            + '</tr>';
                    seqNo++;
                });
            });
        });

        htmlText += '</table>';
        $(panelObj).find('div[name="task-list"]').html(htmlText);

        /*
         * 追加したDOMにイベント設定
         */
        $('table.task-assign-table select.select-reserve-task').on('change', function(e){
            AlueIntegOffice.TaskCalendarUtils.onChangeTaskToReserveTask(e);
        });
        $('table.task-assign-table button.btn-mst-change-task').on('click', function(e){
            AlueIntegOffice.TaskCalendarUtils.openModalToChangeMasterTask(e);
        });
    },

    //詳細モーダル開く
    openDetailModal : function(dayNumber, cellNumber, taskTypeName, jsonQuestion){

        var title = AlueIntegOffice.Utils.getDayDisplay(dayNumber) + '曜日の '
                    + taskTypeName + ' 問題詳細';

        //モーダル画面設定
        var panelObj = $('#calendarDetailModal').find('div.modal-body');
        $(panelObj).find('span[name="title"]').text(title);

        var itemHtml = '';
        $.each(jsonQuestion, function(idx, val) {
            itemHtml += '<div class="hero-form-item">'
                      + '<span>' + (idx+1) + '</span>'
                      + '<div class="form-group question-text">'
                      + val
                      + '</div>'
                      + '</div>';
        });
        $(panelObj).find('div[name="question-detail"]').html(itemHtml);

        $('#calendarDetailModal').modal();
    },

    //G/V出題予定問題一覧モーダル開く
    openTaskAssignModal : function(userId, qType){

        AlueIntegOffice.Utils.dispOnLoadingIcon(true);

        var postData = {
                "userId" : userId,
                "qType"  : qType
            };

        $.ajax({
            type : 'GET',
            url  : this.URL_GET_TASK_ASSIGN_LIST,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);

            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueIntegOffice.AjaxUtils.userErrorHandle(response);
                return false;
            }

            // 選択済みの出題リスト
            var selectedTaskList = response.data[0].selectedTaskList;
            // 未選択の出題リスト
            var reservedTaskList = response.data[0].reservedTaskList;

            //モーダルにデータをセット
            AlueIntegOffice.TaskCalendarUtils.setDataToTaskAssignModal(qType, userId, selectedTaskList, reservedTaskList);

            //モーダル開く
            if(qType == 'Grammar'){
                $('#grammarModal').modal();
            }else if(qType == 'Vocabulary'){
                $('#vocabularyModal').modal();
            }

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);
            //AlueIntegOffice.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    //タスクカレンダー：メイン画面：タスク種別変更
    changeTaskType : function(userId, dayNumber, cellNumber, newTaskType){
        AlueIntegOffice.Utils.dispOnLoadingIcon(true);

        var postData = {
                "userId"       : userId,
                "dayNumber"    : dayNumber,
                "cellNumber"   : cellNumber,
                "newTaskType"  : newTaskType
            };

        $.ajax({
            type : 'POST',
            url  : this.URL_CHANGE_TASK_TYPE,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);

            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueIntegOffice.AjaxUtils.userErrorHandle(response);
                return false;
            }

            //タスクカレンダー画面を再表示する
            //var url = AlueIntegOffice.TaskCalendarUtils.URL_REDIRECT_TASK_CALENDAR_TOP + userId;
            //AlueIntegOffice.AjaxUtils.submitGetForm(url, {});
            //再表示せずに当該項目をblinkする
            var selectName = 'select-task-type-' + dayNumber + '-' + cellNumber;
            var selectObj = $('select[name="' + selectName + '"]').parent('div.dropdown');
            AlueIntegOffice.Utils.objectBlink(selectObj);

            return false;

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);
            //AlueIntegOffice.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },


    //イベント登録
    registEventHandler : function(){
        //詳細表示ボタン
        $('div[name^="div-btn-cal-detail"]').on('click', function(e){
            var dayNumber = $(e.target).data('day-number');
            var cellNumber = $(e.target).data('cell-number');
            var taskType = $(e.target).data('cell-task-type');
            var taskTypeName = $(e.target).data('cell-task-type-name');
            var jsonQuestion = $(e.target).data('cell-question');
            if(taskType < 1){
                return false;
            }
            AlueIntegOffice.TaskCalendarUtils.openDetailModal(dayNumber, cellNumber, taskTypeName, jsonQuestion);
            return false;
        });

        //Grammar問題一覧表示ボタン
        $('div[name="div-btn-modal-grammar"]').on('click', function(e){
            var userId = $(document).find('input[name="userId"]').val();
            var qType = 'Grammar';
            AlueIntegOffice.TaskCalendarUtils.openTaskAssignModal(userId, qType);
            return false;
        });
        //Vocabulary問題一覧表示ボタン
        $('div[name="div-btn-modal-vocabulary"]').on('click', function(e){
            var userId = $(document).find('input[name="userId"]').val();
            var qType = 'Vocabulary';
            AlueIntegOffice.TaskCalendarUtils.openTaskAssignModal(userId, qType);
            return false;
        });

        //タスクカレンダー：メイン画面：タスク種別変更
        $('select[name^="select-task-type"]').on('change', function(e){
            var userId = $(document).find('input[name="userId"]').val();
            var dayNumber = $(e.target).data('day-number');
            var cellNumber = $(e.target).data('cell-number');
            var newTaskType = $(e.target).val();
            AlueIntegOffice.TaskCalendarUtils.changeTaskType(userId, dayNumber, cellNumber, newTaskType);
            return false;
        });

        //G/V出題予定モーダル：マスタから切り替えモーダル：検索
        $('#cMasterModal div.modal-body div.c-master-modal-search .btn-search').on('click', function(e){
            AlueIntegOffice.TaskCalendarUtils.searchGVmaster();
            return false;
        });

    },

    //初期化
    init : function(){
        //セレクトボックスの生成
        $('.select2').select2({
            language: 'ja',
            width: '100%'
        });

        //イベント登録
        this.registEventHandler();
    }
};

$(document).ready( function(){
    //初期化
    AlueIntegOffice.TaskCalendarUtils.init();
});

