/**
 * 宿題管理 javascript
 */
AlueSubsys.TaskUtils = {

    URL_TASK_SEARCH         : '/api/task_search',
    URL_CHANGE_STATUS       : '/api/change_status',
    URL_ASSIGN_OPERATOR     : '/api/assign_operator',
    URL_REGIST_FEEDBACK     : '/api/regist_feedback',
    URL_GET_ANSWER_LIST     : '/api/get_answer_list',
    URL_REGIST_ANSWER_LINK  : '/api/regist_answer_link',
    URL_RELEASE_ANSWER_LINK : '/api/release_answer_link',
    URL_CHATWORK_CONTRIBUTE : '/task/chatwork_contribute',

    //ログインアカウント種別
    accountKindId : '',
    //一覧データの保持
    taskListData : '',
    //オペレータ一覧データの保持
    operatorListData : '',
    //検索条件の保持
    searchCondition : null,
    //ページNoの保持
    searchPageNo : 1,
    totalPageNo : 0,

    //一覧リスト検索
    doSearch : function(reSearch){

        AlueSubsys.Utils.dispOnLoadingIcon(true);

        if(reSearch){
            //再検索(前回検索条件で再度検索する)
            var postData = AlueSubsys.TaskUtils.searchCondition;
            postData.pageNo = AlueSubsys.TaskUtils.searchPageNo;

        }else{
            //初回検索
            var questionSetDateSince = $('input[name="questionSetDateSince"]').val();
            var questionSetDateUntil = $('input[name="questionSetDateUntil"]').val();
            var answerDeadlineDateSince = $('input[name="answerDeadlineDateSince"]').val();
            var answerDeadlineDateUntil = $('input[name="answerDeadlineDateUntil"]').val();
            //var freeword = $('input[name="freeword"]').val();
            var selectStudent = $('select[name="selectStudent"]').val();
            var selectStatus = $('select[name="selectStatus"]').val();
            var selectOperator = $('select[name="searchSelectOperator"]').val();
            var isOnlyNoAssignOperator = $('input[name="onlyNoAssignOperator"]').is(':checked');
            var pageNo = 1;

            var postData = {
                    "questionSetDateSince"    : questionSetDateSince,
                    "questionSetDateUntil"    : questionSetDateUntil,
                    "answerDeadlineDateSince" : answerDeadlineDateSince,
                    "answerDeadlineDateUntil" : answerDeadlineDateUntil,
                    //"freeword"                : freeword,
                    "selectStudent"           : selectStudent,
                    "selectStatus"            : selectStatus,
                    "selectOperator"          : selectOperator,
                    "isOnlyNoAssignOperator"  : isOnlyNoAssignOperator,
                    "pageNo"                  : pageNo
                };
        }
        //検索条件の保持
        AlueSubsys.TaskUtils.searchCondition = postData;


        $.ajax({
            type : 'POST',
            url  : this.URL_TASK_SEARCH,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);

            if(!response){
                return false;
            }

            //バリデーション・エラー判定
            if(response.status !== 'OK'){
                AlueSubsys.AjaxUtils.validationErrorHandle(response);
                return false;
            }

            //ページネーション表示をいったん消去する
            $('div#task-list-pagination').empty();

            if(response.data.count < 1){
                var resultMsg = '<div class="guidance-message">'
                               + '該当データがありません。'
                               + '</div>';
                $('div#task-list').empty();
                $('div#task-list').html(resultMsg);
                return false;
            }

            //総件数の保持
            var totalRowCount  = response.data.totalRowCount;
            var totalPageCount = response.data.totalPageCount;
            AlueSubsys.TaskUtils.totalPageNo = totalPageCount;

            //一覧データの保持
            var taskList = response.data.taskList;
            AlueSubsys.TaskUtils.taskListData = taskList;
            //オペレータ一覧データの保持
            AlueSubsys.TaskUtils.operatorListData = response.data.operatorList;


            /*****
             * 2018-04-24 処理速度向上のため、サーバー側でhtmlを生成する
            //一覧リストのDOMを生成する
            var taskListHtml = '<table class="table table-sm core-tbl table-hover task-list">';
            $.each(taskList, function(idx, rowData){
                var listRowHtml = AlueSubsys.TaskUtils.getListRowHtml(idx, rowData);
                taskListHtml += listRowHtml;
            });
            taskListHtml += '</tbody></table>';

            //一覧リストのDOMを描画する
            $('div#task-list').empty();
            $('div#task-list').html(taskListHtml);
            ****/
            //一覧リストのDOMを描画する
            var taskListBlade = response.data.taskListBlade;
            $('div#task-list').empty();
            $('div#task-list').html(taskListBlade);


            //生成したDOMにイベントを付与する
            AlueSubsys.TaskUtils.setEventHandlerAfterDomCreated();

            //ページネーション表示
            var pageBlade = response.data.pageBlade;
            $('div#task-list-pagination').empty();
            $('div#task-list-pagination').html(pageBlade);

            //再表示などの場合に「全体チェック・チェックボックス」をリセットしておく
            $('input[name="allCheck"]').prop('checked', false);

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);
            AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    /**
     * 一覧表示の1行のHtmlを取得する
     * @param int idx
     * @param object rowData
     * @returns String
     */
    getListRowHtml : function(idx, rowData){

        //ログインアカウント種別 (layouts/header より取得)
        var loginUserAccountType = $('input[name="loginUserAccountType"]').val();

        var listRowHtml = '';
        if(idx < 1){
            //先頭行の場合は見出し行を生成

            if(AlueSubsys.TaskUtils.accountKindId == 1 ||
               AlueSubsys.TaskUtils.accountKindId == 2){
               //管理者、運用者
                listRowHtml += '<thead class="thead-default"><tr>'
                    + '<th class="font-weight-bold">選択</th>'
                    + '<th class="font-weight-bold">生徒名</th>'
                    + '<th class="font-weight-bold">宿題</th>'
                    + '<th class="font-weight-bold">ステータス</th>'
                    + '<th class="font-weight-bold">出題日<br />回答日</th>'
                    + '<th class="font-weight-bold">回答内容</th>'
                    + '<th class="font-weight-bold">オペレータ指定</th>'
                    + '<th class="font-weight-bold">出題紐付け</th>'
                    + '<th class="font-weight-bold">フィードバック</th>'
                    + '</tr></thead>'
                    + '<tbody>';
            }else{
               //カウンセラー、オペレータ
                listRowHtml += '<thead class="thead-default"><tr>'
                    + '<th class="font-weight-bold">生徒名</th>'
                    + '<th class="font-weight-bold">宿題</th>'
                    + '<th class="font-weight-bold">ステータス</th>'
                    + '<th class="font-weight-bold">出題日<br />回答日</th>'
                    + '<th class="font-weight-bold">回答内容</th>'
                    + '<th class="font-weight-bold">オペレータ指定</th>'
                    + '<th class="font-weight-bold">フィードバック</th>'
                    + '</tr></thead>'
                    + '<tbody>';
            }
        }

        //会員名
        var memberName = rowData.userInfo.lastName + ' ' + rowData.userInfo.firstName;

        //ステータスの取得
        var taskStatus = rowData.userTaskStatus;
        var taskStatusName = rowData.userTaskStatusName;
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
        var taskStatusSelectOptions = '';
        if(taskStatus == '0'){//0: 未出題
            taskStatusSelectOptions = '<option value="0" selected>未出題</option>';

        }else if(taskStatus == '1'){//1: 出題済み
            taskStatusSelectOptions = '<option value="1" selected>出題済み</option>';

        }else if(taskStatus == '2'){//2: ユーザ回答済み
            taskStatusSelectOptions = '<option value="2" selected>回答済み</option>'
                                    + '<option value="3">回答差戻し</option>';

        }else if(taskStatus == '3'){//3: ユーザ回答差し戻し
            taskStatusSelectOptions = '<option value="3" selected>回答差戻し</option>';

        }else if(taskStatus == '4'){//4: 未提出

        }else if(taskStatus == '5'){//5: フィードバック済み
            taskStatusSelectOptions = '<option value="5" selected>フィードバック済み</option>'
                                    + '<option value="6">フィードバック差戻し</option>';

        }else if(taskStatus == '6'){//6: フィードバック差戻し
            taskStatusSelectOptions = '<option value="5">フィードバック済み</option>'
                                    + '<option value="6" selected>フィードバック差戻し</option>';

        }else if(taskStatus == '7'){//7: 投稿済み（完了）
            taskStatusSelectOptions = '<option value="7" selected>フィードバック投稿済み</option>';
        }

        var taskStatusDisp = '<select name="selectStatus" class="form-control select2">'
                           + taskStatusSelectOptions
                           + '</select>';

        //出題日、回答日
        var dateItem = '';
        var questionSetDate = rowData.questionSetDate;//出題日
        var answerDeadlineDate = rowData.answerDeadlineDate;//回答期限日
        var answeredDate = rowData.answeredDate;//回答日
        if(questionSetDate){
            dateItem = questionSetDate + '<br />';
        }
        if(answeredDate){
            dateItem += answeredDate;
        }

        //出題内容
        var questionText = '';
        var questionType = rowData.taskInfo.taskDetail.questionType;
        if(questionType == '1'){
            questionText = rowData.taskInfo.taskDetail.question;
        }else if(questionType == '2'){
            questionText = rowData.taskInfo.taskDetail.question;
        }else if(questionType == '3'){
            questionText = rowData.taskInfo.taskDetail.srContext;
        }else if(questionType == '4'){
            questionText = rowData.taskInfo.taskDetail.reviewContext;
        }else if(questionType == '5'){
            questionText = rowData.taskInfo.taskDetail.otherTaskContext;
        }

        //回答内容 (回答URL)
        var answerUrl = rowData.originalAnswer;

        //回答レコードid
        var userMessageLogId = rowData.userMessageLogId;

        //オペレータ選択肢の生成
        var selectOperator = '';
        var selectedOperatorAccountId = '';

        var operatorInfo = rowData.operatorInfo;
        if(operatorInfo && operatorInfo.length > 0){
            selectedOperatorAccountId = rowData.operatorInfo[0].accountId;
        }

        if(loginUserAccountType == '4'){ //オペレータ
            selectOperator =
                '<select name="selectOperator" class="form-control select2" plaseholder="選択してください" disabled="disabled">';
        }else{
            selectOperator =
                '<select name="selectOperator" class="form-control select2" plaseholder="選択してください">';
        }
        selectOperator += '<option value=""></option>';
        $.each(AlueSubsys.TaskUtils.operatorListData, function(idx, rowData){
            if(idx == selectedOperatorAccountId){
                selectOperator += '<option value="' + idx + '" selected>' + rowData + '</option>';
            }else{
                selectOperator += '<option value="' + idx + '">' + rowData + '</option>';
            }
        });
        selectOperator += '</select>';

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
        //選択チェックボックス (ステータス値に応じた表示となる)
        var selectCheckBox = '';
        if( taskStatus == '5' ){
            selectCheckBox = '<input type="checkbox" value="' + rowData.userTaskId + '">';
        }else{
            selectCheckBox = '<input type="checkbox" value="' + rowData.userTaskId + '" disabled="disabled">';
        }

        //音声DLボタン (ステータス値に応じた表示となる)
        var answerDownloadButton = '';
        if((taskStatus == '2' ||
            taskStatus == '5' || taskStatus == '6' || taskStatus == '7') &&
           ((answerUrl) && (answerUrl.length > 0))
        ){
            answerDownloadButton = '<button name="btn-download" class="btn btn-domain" data-answer-url="' + answerUrl + '">音声DL</button>';
        }else{
            answerDownloadButton = '<button name="btn-download" class="btn btn-default" data-answer-url="' + answerUrl + '" disabled="disabled">音声DL</button>';
        }

        //紐付け解除ボタン (ステータス値に応じた表示となる)
        var answerReleaseButton = '';
        if((taskStatus == '2') &&
           ((answerUrl) && (answerUrl.length > 0))
        ){
            answerReleaseButton = '<button name="btn-release" class="btn btn-domain" data-user-message-log-id="' + userMessageLogId + '">紐付け解除</button>';
        }else{
            answerReleaseButton = '<button name="btn-release" class="btn btn-default" data-user-message-log-id="' + userMessageLogId + '" disabled="disabled">紐付け解除</button>';
        }

        //フィードバック登録ボタン (ステータス値に応じた表示となる)
        var feedbackButton = '';
        if((taskStatus == '2' ||
            taskStatus == '5' || taskStatus == '6') &&
           ((answerUrl) && (answerUrl.length > 0))
        ){
            feedbackButton = '<button name="btn-feedback" class="btn btn-domain">フィードバック登録</button>';
        }else{
            feedbackButton = '<button name="btn-feedback" class="btn btn-default" disabled="disabled">フィードバック登録</button>';
        }

        /*
         * タスクリスト・行データ生成
         * ※ログインアカウント種別に応じて表示項目が変化する
         */
        if(AlueSubsys.TaskUtils.accountKindId == 1 ||
           AlueSubsys.TaskUtils.accountKindId == 2){
            //管理者、運用者
            listRowHtml += '<tr class="table-row"'
                         + ' data-user-task-id="' + rowData.userTaskId + '"'
                         + ' data-user-id="' + rowData.userId + '"'
                         + ' data-task-status="' + taskStatus + '">'
                + '<td>' + selectCheckBox + '</td>'
                + '<td>' + memberName + '</td>'
                + '<td>' + questionText + '</td>'
                + '<td>' + taskStatusDisp + '</td>'
                + '<td>' + dateItem + '</td>'
                + '<td>' + answerDownloadButton + '</td>'
                + '<td>' + selectOperator + '</td>'
                + '<td>' + answerReleaseButton + '</td>'
                + '<td>' + feedbackButton + '</td>'
                + '</tr>';
        }else{
            //カウンセラー、オペレータ
            listRowHtml += '<tr class="table-row"'
                         + ' data-user-task-id="' + rowData.userTaskId + '"'
                         + ' data-user-id="' + rowData.userId + '">'
                + '<td>' + memberName + '</td>'
                + '<td>' + questionText + '</td>'
                + '<td>' + taskStatusDisp + '</td>'
                + '<td>' + dateItem + '</td>'
                + '<td>' + answerDownloadButton + '</td>'
                + '<td>' + selectOperator + '</td>'
                + '<td>' + feedbackButton + '</td>'
                + '</tr>';
        }

        return listRowHtml;
    },

    /**
     * DOM描画後のイベントハンドラ再設定
     */
    setEventHandlerAfterDomCreated : function(){

        //セレクトボックスの生成
        $('.select2').select2({
            language: 'ja',
            width: '100%'
        });
        //セレクトボックスのイベント設定
        $('table.task-list select[name="selectStatus"]').on('change', function(e){
            //ステータス変更
            AlueSubsys.TaskUtils.changeStatus(e);
            return true;
        });
        $('table.task-list select[name="selectOperator"]').on('change', function(e){
            //オペレータアサイン
            AlueSubsys.TaskUtils.assignOperator(e);
            return true;
        });

        //行クリック・イベントハンドラ
        $('table.task-list tr.table-row').on('click', function(e){
            //各クリック
            if ($(e.target).is('input[type="checkbox"]')) {
                //個別選択チェックボックス
                return true;

            }else if ($(e.target).is('button[name="btn-download"]')) {
                //音声DLボタン
                var answerUrl = $(e.target).data('answer-url');
                AlueSubsys.TaskUtils.voiceDataDownload(answerUrl);
                return false;

            }else if ($(e.target).is('button[name="btn-release"]')) {
                //紐付け解除ボタン
                var userId = $(this).data('user-id');
                var userTaskId = $(this).data('user-task-id');
                var userMessageLogId = $(e.target).data('user-message-log-id');
                AlueSubsys.TaskUtils.openReleaseConfirmModal(userId, userTaskId, userMessageLogId);//解除確認モーダル
                return false;

            }else if ($(e.target).is('button[name="btn-feedback"]')) {
                //フィードバックボタン
                var userTaskId = $(this).data('user-task-id');
                AlueSubsys.TaskUtils.openFeedbackModal(userTaskId);//フィードバックモーダル
                return false;

            }else if ($(e.target).is('span.select2-selection') ||
                      $(e.target).is('span.select2-selection__rendered') ){
                //ステータス選択肢
                //オペレータプルダウン
                return true;

            }else{
                //行クリックで回答詳細モーダルを開く
                var userTaskId = $(this).data('user-task-id');
                AlueSubsys.TaskUtils.openAnswerDetailModal(userTaskId);//回答詳細モーダル
                return false;
            }
        });

        //Droppable設定
        $('div#task-list table.task-list tbody tr').droppable({
            accept: 'div.draggable',
            tolerance: 'pointer',
            greedy: true,

            over( event, ui ) {
                //userMessageLog情報を取得
                var userMessageLogId = $(ui.draggable).find('input[name="userMessageLogId"]').val();
                var userId = $(ui.draggable).find('input[name="userId"]').val();

                //userTask情報を取得
                var userTaskObj = $(this);
                var userTaskId = $(userTaskObj).data('user-task-id');
                var userTaskUserId = $(userTaskObj).data('user-id');
                var userTaskStatus = $(userTaskObj).data('task-status');

                //ステータス判定
                if(
                   (userTaskStatus == '1' ||
                    userTaskStatus == '3' ||
                    userTaskStatus == '4') &&
                   (userTaskUserId == userId)
                ){
                    $(this).addClass('droppable-active');
                }
            },
            out( event, ui ) {
                $(this).removeClass('droppable-active');
            },
            drop: function( event, ui ) {
                //userMessageLog情報を取得
                var userMessageLogId = $(ui.draggable).find('input[name="userMessageLogId"]').val();
                var userId = $(ui.draggable).find('input[name="userId"]').val();
                var postedAt = $(ui.draggable).find('input[name="postedAt"]').val();
                var postedContext = $(ui.draggable).find('input[name="postedContext"]').val();

                //userTask情報を取得
                var userTaskObj = $(this);
                var userTaskId = $(userTaskObj).data('user-task-id');
                var userTaskUserId = $(userTaskObj).data('user-id');
                var userTaskStatus = $(userTaskObj).data('task-status');

                if( userTaskUserId != userId ){
                    return false;
                }

                //ハイライト消去
                $(this).removeClass('droppable-active');

                //ステータス判定
                if( userTaskStatus == '1' ||
                    userTaskStatus == '3' ||
                    userTaskStatus == '4' ){
                    /*
                     * 1: 出題済み
                     * 2: ユーザ回答済み
                     * 3: ユーザ回答差し戻し
                     * 4: 未提出（宿題がタスク期日までに終わっていない場合のステータス） ※未使用
                     * 5: フィードバック済み
                     * 6: フィードバック差し戻し（運用担当がカウンセラーの添削に対して差し戻し））
                     * 7: 投稿済み（完了）
                     * 上記のうち、1, 3, 4 は許可するが、それ以外は許可しない。
                     *
                     * 2018-03-12
                     * タスクは個別に分かれているが、出題、回答は1件にまとまっているので
                     * 回答紐付けは出題グループ単位で行う。
                     */
                }else{
                    return false;
                }

                //確認メッセージ作成
                var userTaskInfo = AlueSubsys.TaskUtils.getUserTaskInfo(userTaskId);
                var userName = userTaskInfo.userInfo.lastName + ' '
                             + userTaskInfo.userInfo.firstName;
                var questionSetDate = userTaskInfo.questionSetDate;

                var confirmModalHtml = '<div>'
                    + userName + ' さんへの '
                    + questionSetDate + ' 出題の宿題に<br />'
                    + '投稿日:' + postedAt + ' の<br />'
                    + '回答:' + postedContext + '<br />'
                    + 'を紐付けします。<br /><br />'
                    + 'よろしいですか？<br />'
                    + '<input type="hidden" name="userId" value="' + userTaskUserId + '">'
                    + '<input type="hidden" name="userTaskId" value="' + userTaskId + '">'
                    + '<input type="hidden" name="userTaskQuestionSetDate" value="' + questionSetDate + '">'
                    + '<input type="hidden" name="userMessageLogId" value="' + userMessageLogId + '">'
                    + '<input type="hidden" name="postedContext" value="' + postedContext + '">'
                    + '</div>';

                $('#answerLinkConfirmModal').find('div.hero-form-item').html(confirmModalHtml);
                $('#answerLinkConfirmModal').modal();
            }
        });
    },

    /**
     * 内部保持している情報から指定した行のUserTask情報を取得する
     * @param userTaskId
     * @returns userTaskInfo
     */
    getUserTaskInfo : function(userTaskId){
        var targetUserTask = null;
        $(AlueSubsys.TaskUtils.taskListData).each(function(idx, obj){
            if(obj.userTaskId == userTaskId){
                targetUserTask = obj;
                return false;
            }
        });
        return targetUserTask;
    },

    /**
     * 回答詳細モーダル開く
     */
    openAnswerDetailModal : function(userTaskId){
        var targetUserTask = null;
        $(AlueSubsys.TaskUtils.taskListData).each(function(idx, obj){
            if(obj.userTaskId == userTaskId){
                targetUserTask = obj;
                return false;
            }
        });

        //ステータスの取得
        var taskStatus = targetUserTask.userTaskStatus;

        var operatorId   = '';
        var operatorName = '';
        if(targetUserTask.operatorInfo.length > 0){
            operatorId   = targetUserTask.operatorInfo[0].accountId;
            operatorName = targetUserTask.operatorInfo[0].staffName;
        }

        var userInfo = targetUserTask.userInfo;
        var taskInfo = targetUserTask.taskInfo;
        var feedbackInfo = targetUserTask.feedbackInfo;

        var userId = userInfo.userId;
        var memberName = userInfo.lastName + ' ' + userInfo.firstName;
        var headerTitle = memberName + 'さんの宿題回答詳細';
        var taskStatusName = targetUserTask.userTaskStatusName;

        var questionSetDate    = (targetUserTask.questionSetDate)    ? targetUserTask.questionSetDate    : '';
        var answerDeadlineDate = (targetUserTask.answerDeadlineDate) ? targetUserTask.answerDeadlineDate : '';
        var answeredDate       = (targetUserTask.answeredDate)       ? targetUserTask.answeredDate       : '';
        var answerUrl          = (targetUserTask.originalAnswer)     ? targetUserTask.originalAnswer     : '';
        var feedbackDate       = (feedbackInfo.feedbackDate)         ? feedbackInfo.feedbackDate         : '';
        var feedbackText       = (feedbackInfo.feedbackComment)      ? feedbackInfo.feedbackComment      : '';

        var taskContents = '';
        if(taskInfo.taskType == '1'){
            taskContents = taskInfo.taskDetail.question;

        }else if(taskInfo.taskType == '2'){
            taskContents = taskInfo.taskDetail.question;

        }else if(taskInfo.taskType == '3'){
            taskContents = taskInfo.taskDetail.srContext;

        }else if(taskInfo.taskType == '4'){
            taskContents = taskInfo.taskDetail.reviewContext;

        }else if(taskInfo.taskType == '5'){
            taskContents = taskInfo.taskDetail.otherTaskContext;
        }

        //モーダル画面設定
        $('#answerDetailModal').find('div.modal-body')
                .find('span[name="header-title"]').html(headerTitle);

        var panelObj = $('#answerDetailModal').find('div.hero-form');
        $(panelObj).find('input[name="member-name"]').val(memberName);
        $(panelObj).find('input[name="operator-name"]').val(operatorName);
        $(panelObj).find('input[name="operator-id"]').val(operatorId);
        $(panelObj).find('input[name="question-category"]').val(taskInfo.taskTypeName);
        $(panelObj).find('input[name="task-status-name"]').val(taskStatusName);
        $(panelObj).find('input[name="question-contents"]').val(taskContents);
        $(panelObj).find('input[name="question-set-date"]').val(questionSetDate);
        $(panelObj).find('input[name="answer-deadline-date"]').val(answerDeadlineDate);
        $(panelObj).find('input[name="answered-date"]').val(answeredDate);
        $(panelObj).find('button[name="modal-btn-answer-download"]').data('answer-url', answerUrl);
        $(panelObj).find('button[name="modal-btn-answer-download"]').attr('data-answer-url', answerUrl);
        $(panelObj).find('input[name="feedback-date"]').val(feedbackDate);
        $(panelObj).find('textarea[name="feedback-text"]').val(feedbackText);

        //音声DLボタン (ステータス値に応じた表示となる)
        if((taskStatus == '2' || taskStatus == '3' ||
            taskStatus == '5' || taskStatus == '6' || taskStatus == '7') &&
           (answerUrl.length > 0)
        ){
            $(panelObj).find('button[name="modal-btn-answer-download"]').attr('disabled', false);
        }else{
            $(panelObj).find('button[name="modal-btn-answer-download"]').attr('disabled', true);
        }

        $('#answerDetailModal').modal();
    },

    /**
     * 紐付け解除確認モーダル開く
     */
    openReleaseConfirmModal : function(userId, userTaskId, userMessageLogId){
        //userTask情報を取得
        var userTaskInfo = AlueSubsys.TaskUtils.getUserTaskInfo(userTaskId);
        //確認メッセージ作成
        var userName = userTaskInfo.userInfo.lastName + ' '
                     + userTaskInfo.userInfo.firstName;
        var questionSetDate = userTaskInfo.questionSetDate;

        var confirmModalHtml = '<div>'
            + userName + ' さんへの<br />'
            + questionSetDate + ' 出題の宿題に対する回答紐付けを解除します。<br /><br />'
            + 'よろしいですか？<br />'
            + '<input type="hidden" name="userId" value="' + userId + '">'
            + '<input type="hidden" name="userTaskId" value="' + userTaskId + '">'
            + '<input type="hidden" name="userTaskQuestionSetDate" value="' + questionSetDate + '">'
            + '<input type="hidden" name="userMessageLogId" value="' + userMessageLogId + '">'
            + '</div>';

        $('#answerReleaseConfirmModal').find('div.hero-form-item').html(confirmModalHtml);
        $('#answerReleaseConfirmModal').modal();
    },

    /**
     * Feedbackモーダル開く
     */
    openFeedbackModal : function(userTaskId){
        var targetUserTask = null;
        $(AlueSubsys.TaskUtils.taskListData).each(function(idx, obj){
            if(obj.userTaskId == userTaskId){
                targetUserTask = obj;
                return false;
            }
        });

        //ステータスの取得
        var taskStatus = targetUserTask.userTaskStatus;

        var operatorId   = '';
        var operatorName = '';
        if(targetUserTask.operatorInfo.length > 0){
            operatorId   = targetUserTask.operatorInfo[0].accountId;
            operatorName = targetUserTask.operatorInfo[0].staffName;
        }

        var userInfo = targetUserTask.userInfo;
        var taskInfo = targetUserTask.taskInfo;
        var feedbackInfo = targetUserTask.feedbackInfo;

        var userId = userInfo.userId;
        var memberName = userInfo.lastName + ' ' + userInfo.firstName;
        var headerTitle = memberName + 'さんへのフィードバック登録';
        var taskStatusName = targetUserTask.userTaskStatusName;

        var questionSetDate    = (targetUserTask.questionSetDate)    ? targetUserTask.questionSetDate    : '';
        var answerDeadlineDate = (targetUserTask.answerDeadlineDate) ? targetUserTask.answerDeadlineDate : '';
        var answeredDate       = (targetUserTask.answeredDate)       ? targetUserTask.answeredDate       : '';
        var feedbackDate       = (targetUserTask.answeredDate)       ? targetUserTask.answeredDate       : '';
        var answerUrl          = (targetUserTask.originalAnswer)     ? targetUserTask.originalAnswer     : '';
        var feedbackDate       = (feedbackInfo.feedbackDate)         ? feedbackInfo.feedbackDate         : '';
        var feedbackText       = (feedbackInfo.feedbackComment)      ? feedbackInfo.feedbackComment      : '';

        var taskContents = '';
        if(taskInfo.taskType == '1'){
            taskContents = taskInfo.taskDetail.question;

        }else if(taskInfo.taskType == '2'){
            taskContents = taskInfo.taskDetail.question;

        }else if(taskInfo.taskType == '3'){
            taskContents = taskInfo.taskDetail.srContext;

        }else if(taskInfo.taskType == '4'){
            taskContents = taskInfo.taskDetail.reviewContext;

        }else if(taskInfo.taskType == '5'){
            taskContents = taskInfo.taskDetail.otherTaskContext;
        }

        $('#feedbackModal').find('div.modal-body')
                .find('span[name="header-title"]').html(headerTitle);

        //モーダル画面設定
        var panelObj = $('#feedbackModal').find('div.hero-form');
        $(panelObj).find('input[name="member-name"]').val(memberName);
        $(panelObj).find('input[name="operator-name"]').val(operatorName);
        $(panelObj).find('input[name="operator-id"]').val(operatorId);
        $(panelObj).find('input[name="question-category"]').val(taskInfo.taskTypeName);
        $(panelObj).find('input[name="task-status-name"]').val(taskStatusName);
        $(panelObj).find('input[name="question-contents"]').val(taskContents);
        $(panelObj).find('input[name="question-set-date"]').val(questionSetDate);
        $(panelObj).find('input[name="answer-deadline-date"]').val(answerDeadlineDate);
        $(panelObj).find('input[name="answered-date"]').val(answeredDate);
        $(panelObj).find('button[name="modal-btn-answer-download"]').data('answer-url', answerUrl);
        $(panelObj).find('button[name="modal-btn-answer-download"]').attr('data-answer-url', answerUrl);
        $(panelObj).find('input[name="feedback-user-id"]').val(userId);
        $(panelObj).find('input[name="feedback-user-task-id"]').val(userTaskId);
        $(panelObj).find('input[name="feedback-date"]').val(feedbackDate);
        $(panelObj).find('textarea[name="feedback-text"]').val(feedbackText);

        $('#feedbackModal').modal();
    },

    /**
     * 回答音声データ・ダウンロード
     */
    voiceDataDownload : function(answerUrl){
        var windowOption = 'width=500, height=100, top=0,left=0, menubar=no, toolbar=no, scrollbars=no';
        window.open(answerUrl, 'alue_voice', windowOption);
        return false;
    },

    /**
     * フィードバック登録
     */
    registFeedback : function(userId, userTaskId, feedbackText){

        AlueSubsys.TaskUtils.closeFeedbackModal();
        AlueSubsys.Utils.dispOnLoadingIcon(true);

        var postData = {
                "userId"       : userId,
                "userTaskId"   : userTaskId,
                "feedbackText" : feedbackText
            };

        $.ajax({
            type : 'POST',
            url  : this.URL_REGIST_FEEDBACK,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);

            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueSubsys.AjaxUtils.userErrorHandle(response);
                return false;
            }

            //登録OK (前回検索条件で再度検索表示する)
            AlueSubsys.TaskUtils.doSearch(true);

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);
            AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },
    /**
     * Feedbackモーダル閉じる
     */
    closeFeedbackModal : function(){
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
        $('#feedbackModal').modal('hide');
    },

    /**
     * 回答紐付けを登録する
     * @param string userTaskId
     * @param string userMessageLogId
     */
    registAnswerLink : function(userId, userTaskId, userTaskQuestionSetDate, userMessageLogId, postedContext){
        AlueSubsys.TaskUtils.closeAnswerLinkConfirmModal();
        AlueSubsys.Utils.dispOnLoadingIcon(true);

        var postData = {
                "userId"            : userId,
                "userTaskId"        : userTaskId,
                "userTaskQuestionSetDate" : userTaskQuestionSetDate,
                "userMessageLogId"  : userMessageLogId,
                "postedContext"     : postedContext
            };

        $.ajax({
            type : 'POST',
            url  : this.URL_REGIST_ANSWER_LINK,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);

            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueSubsys.AjaxUtils.userErrorHandle(response);
                return false;
            }

            //登録OK (右サイド回答一覧を再表示する)
            AlueSubsys.TaskUtils.getAnswerList();

            //登録OK (前回検索条件で再度検索表示する)
            AlueSubsys.TaskUtils.doSearch(true);

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);
            AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },
    /**
     * 紐付け登録確認モーダル閉じる
     */
    closeAnswerLinkConfirmModal : function(){
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
        $('#answerLinkConfirmModal').modal('hide');
    },
    /**
     * 回答紐付けを解除する
     * @param string userTaskId
     * @param string userMessageLogId
     */
    releaseAnswerLink : function(userId, userTaskId, userTaskQuestionSetDate, userMessageLogId){
        AlueSubsys.TaskUtils.closeAnswerReleaseConfirmModal();
        AlueSubsys.Utils.dispOnLoadingIcon(true);

        var postData = {
                "userId"            : userId,
                "userTaskId"        : userTaskId,
                "userTaskQuestionSetDate" : userTaskQuestionSetDate,
                "userMessageLogId"  : userMessageLogId
            };

        $.ajax({
            type : 'POST',
            url  : this.URL_RELEASE_ANSWER_LINK,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);

            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueSubsys.AjaxUtils.userErrorHandle(response);
                return false;
            }

            //登録OK (右サイド回答一覧を再表示する)
            AlueSubsys.TaskUtils.getAnswerList();

            //登録OK (前回検索条件で再度検索表示する)
            AlueSubsys.TaskUtils.doSearch(true);

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);
            AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },
    /**
     * 紐付け解除確認モーダル閉じる
     */
    closeAnswerReleaseConfirmModal : function(){
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
        $('#answerReleaseConfirmModal').modal('hide');
    },

    /**
     * ステータス変更
     */
    changeStatus : function(e){
        var selectedStatusId = $(e.target).val();
        var selectedStatusName = $(e.target).find(':selected').text();
        var userId = $(e.target).parents('tr').data('user-id');
        var userTaskId = $(e.target).parents('tr').data('user-task-id');

        var postData = {
                "userId"     : userId,
                "userTaskId" : userTaskId,
                "selectedStatusId"   : selectedStatusId,
                "selectedStatusName" : selectedStatusName //内部データ更新でのみ使用
            };

        AlueSubsys.Utils.dispOnLoadingIcon(true);

        $.ajax({
            type : 'POST',
            url  : this.URL_CHANGE_STATUS,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);

            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueSubsys.AjaxUtils.userErrorHandle(response);
                return false;
            }

            //登録OK (右サイド回答一覧を再表示する)
            AlueSubsys.TaskUtils.getAnswerList();

            //登録OK (前回検索条件で再度検索表示する)
            AlueSubsys.TaskUtils.doSearch(true);

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);
            AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    /**
     * オペレータアサイン
     */
    assignOperator : function(e){
        var selectedOperatorId = $(e.target).val();
        var selectedOperatorName = $(e.target).find(':selected').text();
        var userTaskId = $(e.target).parents('tr').data('user-task-id');
        var userId = $(e.target).parents('tr').data('user-id');

        var postData = {
                "userTaskId" : userTaskId,
                "userId"     : userId,
                "selectedOperatorId"   : selectedOperatorId,
                "selectedOperatorName" : selectedOperatorName //内部データ更新でのみ使用
            };

        $.ajax({
            type : 'POST',
            url  : this.URL_ASSIGN_OPERATOR,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueSubsys.AjaxUtils.userErrorHandle(response);
                return false;
            }

            //内部で保持しているtaskListData情報を更新しておく
            AlueSubsys.TaskUtils.updateTaskListData('operator', postData);

            //登録OK
            var obj = $(e.target).siblings('span.select2-container');
            //AlueSubsys.Utils.objectBlink(obj);
            $('#operatorChangedNotifyModal').modal();

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    //全体チェック指定
    onChangeAllCheck : function(obj){
        var isChecked = $(obj).is(':checked');
        var targetCheckBoxes = $('table.task-list tr.table-row input[type="checkbox"]');
        $.each(targetCheckBoxes, function(idx, obj){
            if( ! $(obj).prop('disabled')){
                if(isChecked){
                    $(obj).prop("checked", true);
                }else{
                    $(obj).prop("checked", false);
                }
            }
        });
    },

    onChangeNoAssignOperator : function(obj){
        var isChecked = $(obj).is(':checked');
        var selectOperatorObj = $('select[name="searchSelectOperator"]');
        if(isChecked){
            $(selectOperatorObj).val('').trigger('change');
            $(selectOperatorObj).prop("disabled", true);
        }else{
            $(selectOperatorObj).prop("disabled", false);
            $(selectOperatorObj).val('').trigger('change');
        }
    },

    //内部で保持しているtaskListData情報を更新する
    updateTaskListData : function (item, data) {
        $.each(AlueSubsys.TaskUtils.taskListData, function(idx, row){
            if(row.userTaskId === data.userTaskId){
                if(item === 'operator'){
                    if(row.operatorInfo && row.operatorInfo.length < 1){
                        row.operatorInfo = [];
                        row.operatorInfo.push({'accountId':0,'staffName':'','staffType':2});
                    }
                    row.operatorInfo[0]['accountId'] = data.selectedOperatorId;
                    row.operatorInfo[0]['staffName'] = data.selectedOperatorName;

                }else if(item === 'feedback'){
                    //feedbackは再検索とするので不要
                }
                return false;//break
            }
        });
    },

    /**
     * 回答一覧の取得と描画
     * 紐付けられていない回答の一覧を取得して右サイドバーに表示する
     */
    getAnswerList : function(){

        $.ajax({
            type : 'GET',
            url  : this.URL_GET_ANSWER_LIST,
            dataType : 'JSON',
            data : {}
        }).done(function (response)
        {
            if(!response.data){
                return false;
            }
            if(response.status !== 'OK'){
                AlueSubsys.AjaxUtils.userErrorHandle(response);
                return false;
            }

            //いったん全クリアする
            $('div.task-right-panel-scroll div[name="answer-list"]').empty();

            var answerListHtml = '';
            $.each(response.data, function(idx, item){
                answerListHtml += '<div class="new-post draggable">';
                answerListHtml += '<div>投稿日：' + item.postedAt + '</div>'
                                + '<div>' + item.userName + '</div>'
                                //+ '<div>' + item.postedContext + '</div>' //表示不要とする
                                + '<input type="hidden" name="userMessageLogId" value="' + item.userMessageLogId + '">'
                                + '<input type="hidden" name="userId" value="' + item.userId + '">'
                                + '<input type="hidden" name="postedAt" value="' + item.postedAt + '">'
                                + '<input type="hidden" name="postedContext" value="' + item.postedContext + '">';
                answerListHtml += '</div>';
            });

            //回答一覧を再描画
            $('div.task-right-panel-scroll div[name="answer-list"]').html(answerListHtml);

            //Draggable設定
            $('div.task-right-panel-scroll div[name="answer-list"] div.draggable').draggable({
                helper: 'clone',
                opacity: 0.9,
                revert: 'invalid',
                cursor: 'pointer',
                scroll: true,
                start:function(e, ui){
                    $('div.task-right-panel-scroll').css('overflow', 'visible');//親から脱出できない対策
                    $(this).draggable('instance').offset.click = { //カーソル位置ずれ対策
                        left: 20,
                        top: 60
                    };
                },
                drag: function(e, ui){
                    $('div.task-right-panel-scroll').css('overflow', 'auto');//脱出対策をもとに戻す
                    $(ui.helper).addClass('draggable-active'); //css({"border":"4px solid orange", "background-color":"white"});
                },
                stop: function(e, ui){
                    $('div.task-right-panel-scroll').css('overflow', 'auto');//脱出対策をもとに戻す
                }
            });

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    /**
     * Chatwork一括投稿
     */
    chatworkContribute : function(){
        var userTaskIds = [];
        var taskListRows = $('div#task-list table.task-list tr');
        $.each(taskListRows, function(idx, row){
            var chkbox = $(row).find('input[type="checkbox"]');
            if( $(chkbox).is(':checked') ){
                userTaskIds.push($(chkbox).val());
            }
        });

        if(userTaskIds.length < 1){
            return false;
        }

        AlueSubsys.Utils.dispOnLoadingIcon(true);
        var postData = {
                "userTaskIds" : userTaskIds
            };
        //FB一括投稿画面へ遷移する
        AlueSubsys.AjaxUtils.postForm(this.URL_CHATWORK_CONTRIBUTE, postData);

    },

    //先頭ページ
    firstPage : function(){
        AlueSubsys.TaskUtils.searchPageNo = 1;
        AlueSubsys.TaskUtils.doSearch(true);
    },
    //前ページ
    previousPage : function(){
        if(AlueSubsys.TaskUtils.searchPageNo > 1){
            AlueSubsys.TaskUtils.searchPageNo--;
        }
        AlueSubsys.TaskUtils.doSearch(true);
    },
    //指定ページ
    goPage : function(obj){
        var pageNo = $(obj).data('page-no');
        AlueSubsys.TaskUtils.searchPageNo = pageNo;
        AlueSubsys.TaskUtils.doSearch(true);
    },
    //次ページ
    nextPage : function(){
        AlueSubsys.TaskUtils.searchPageNo++;
        AlueSubsys.TaskUtils.doSearch(true);
    },
    //最終ページ
    lastPage : function(){
        AlueSubsys.TaskUtils.searchPageNo = AlueSubsys.TaskUtils.totalPageNo;
        AlueSubsys.TaskUtils.doSearch(true);
    },

    //検索条件リセット
    resetSearchCondition : function(){
        $('div.search-container input[type="text"]').val('');
        $('div.search-container select').val('').trigger('change');
        //「オペレータ未設定タスクに限定」チェックボックス
        $('input[name="onlyNoAssignOperator"]').prop('checked', false);
        //オペレータプルダウン
        var selectOperatorObj = $('select[name="searchSelectOperator"]');
        $(selectOperatorObj).prop("disabled", false);
        $(selectOperatorObj).val('').trigger('change');
    },

    //イベント登録
    registEventHandler : function(){
        //検索条件：クリアボタン
        $('button.btn-clear').on('click', function(){
            AlueSubsys.TaskUtils.searchPageNo = 1;
            AlueSubsys.TaskUtils.resetSearchCondition();
        });
        //検索条件：検索ボタン
        $('button.btn-search').on('click', function(){
            AlueSubsys.TaskUtils.searchPageNo = 1;
            AlueSubsys.TaskUtils.doSearch();
        });
        //全体チェック指定チェックボックス
        $('input[name="allCheck"]').on('change', function(e){
            AlueSubsys.TaskUtils.onChangeAllCheck($(e.target));
        });
        //Chatwork一括投稿ボタン
        $('button.btn-chatwork').on('click', function(){
            AlueSubsys.TaskUtils.chatworkContribute();
        });
        //「オペレータ未設定タスクに限定」チェックボックス
        $('input[name="onlyNoAssignOperator"]').on('change', function(e){
            AlueSubsys.TaskUtils.onChangeNoAssignOperator($(e.target));
        });

        //回答詳細、フィードバック・モーダル上の音声DLボタン
        $('button[name="modal-btn-answer-download"]').on('click', function(e){
            var answerUrl = $(e.target).data('answer-url');
            AlueSubsys.TaskUtils.voiceDataDownload(answerUrl);
        });
        //フィードバック・モーダル上のフィードバック登録ボタン
        $('button[name="modal-btn-feedback-regist"]').on('click', function(e){
            var userId = $('#feedbackModal').find('input[name="feedback-user-id"]').val();
            var userTaskId = $('#feedbackModal').find('input[name="feedback-user-task-id"]').val();
            var feedbackText = $('#feedbackModal').find('textarea[name="feedback-text"]').val();
            AlueSubsys.TaskUtils.registFeedback(userId, userTaskId, feedbackText);
        });
        //回答紐付けモーダル上の回答紐付け登録ボタン
        $('button[name="modal-btn-answer-link-regist"]').on('click', function(e){
            var userId = $('#answerLinkConfirmModal').find('input[name="userId"]').val();
            var userTaskId = $('#answerLinkConfirmModal').find('input[name="userTaskId"]').val();
            var userTaskQuestionSetDate = $('#answerLinkConfirmModal').find('input[name="userTaskQuestionSetDate"]').val();
            var userMessageLogId = $('#answerLinkConfirmModal').find('input[name="userMessageLogId"]').val();
            var postedContext = $('#answerLinkConfirmModal').find('input[name="postedContext"]').val();
            AlueSubsys.TaskUtils.registAnswerLink(userId, userTaskId, userTaskQuestionSetDate, userMessageLogId, postedContext);
        });
        //回答紐付け解除モーダル上の回答紐付け解除ボタン
        $('button[name="modal-btn-answer-release"]').on('click', function(e){
            var userId = $('#answerReleaseConfirmModal').find('input[name="userId"]').val();
            var userTaskId = $('#answerReleaseConfirmModal').find('input[name="userTaskId"]').val();
            var userTaskQuestionSetDate = $('#answerReleaseConfirmModal').find('input[name="userTaskQuestionSetDate"]').val();
            var userMessageLogId = $('#answerReleaseConfirmModal').find('input[name="userMessageLogId"]').val();
            AlueSubsys.TaskUtils.releaseAnswerLink(userId, userTaskId, userTaskQuestionSetDate, userMessageLogId);
        });

    },

    //初期化
    init : function(){
        //ログインアカウント種別保持
        AlueSubsys.TaskUtils.accountKindId = $('#accountKindId').val();
        //初回検索設定
        AlueSubsys.TaskUtils.searchPageNo = 1;

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
    AlueSubsys.TaskUtils.init();
    //ログインアカウント種別に応じた回答一覧の取得
    if(AlueSubsys.TaskUtils.accountKindId == 1 ||
       AlueSubsys.TaskUtils.accountKindId == 2){
        AlueSubsys.TaskUtils.getAnswerList();
    }
    //初回検索
    AlueSubsys.TaskUtils.doSearch();
});
