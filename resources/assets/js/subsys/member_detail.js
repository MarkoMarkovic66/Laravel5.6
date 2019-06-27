/**
 * 会員詳細 javascript
 */
AlueSubsys.MemberDetailUtils = {
    URL_MEMBER_DETAIL          : '/member_detail',
    URL_MEMBER_REPORT          : '/member_report',
    URL_BASIC_INFO_UPDATE      : '/api/member_basicinfo_update',
    URL_LEARNING_POLICY_REGIST : '/api/member_learningpolicy_regist',
    URL_GET_LEARNING_POLICY_CATEGORY : '/api/get_lp_category',

    /*
     * 2018-06-09
     * 下記データの取得と描画をajax化対応する。
     *  ① OUTPUT履歴
     *  ② レッスン受講状況
     *  ③ 宿題実施状況
     *  ④ メッセージ送受信履歴
     *  これに伴い以下の項目を追加する。
     */
    URL_GET_MEMBER_DETAIL_PART_DATA : '/api/get_member_detail_part_data',

    //① OUTPUT履歴
    skipRowCountUserOutputLogs  : 0, //skipカウンタ
    onScrollingUserOutputLogs   : false, //スクロール処理中フラグ
    //② レッスン受講状況
    skipRowCountUserLessonLogs  : 0, //skipカウンタ
    onScrollingUserLessonLogs   : false, //スクロール処理中フラグ
    //③ 宿題実施状況
    skipRowCountUserTaskLogs    : 0, //skipカウンタ
    onScrollingUserTaskLogs     : false, //スクロール処理中フラグ
    userTaskLogsJson          : [], //出題情報jsonを保持するエリア
    //④ メッセージ送受信履歴
    skipRowCountUserMessageLogs : 0, //skipカウンタ
    onScrollingUserMessageLogs  : false, //スクロール処理中フラグ


    updateUserBasicInfo : function(){
        var userId = $('input[name="userId"]').val();
        var cwRoomId = $('input[name="cwroomid"]').val();
        var cwAccountId = $('input[name="cwaccountid"]').val();
        var postData = {
            "userId"      : userId,
            "cwRoomId"    : cwRoomId,
            "cwAccountId" : cwAccountId
            };

        AlueSubsys.Utils.dispOnLoadingIcon(true);

        $.ajax({
            type : 'POST',
            url  : this.URL_BASIC_INFO_UPDATE,
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
            //正常終了
            var result = response.data;
            var roomName = result.cw_room_name;
            $('table[name="user-basic-info"] td[name="cwroomname"]').text(roomName);

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);
            //AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    //学習方針新規登録
    registUserLearningPolicy : function(){

        var userId = $('input[name="lp-user-id"]').val();
        var lpCategory1Id = $('select[name="lp-category1"]').val();
        var lpCategory2Id = $('select[name="lp-category2"]').val();
        var lpTag = $('input[name="lp-tag"]').val();
        var policy = $('input[name="lp-policy"]').val();
        var remark = $('input[name="lp-remark"]').val();

        var postData = {
            "userId" : userId,
            "lpCategory1Id" : lpCategory1Id,
            "lpCategory2Id" : lpCategory2Id,
            "lpTag"  : lpTag,
            "policy" : policy,
            "remark" : remark
        };

        if(policy.length == 0) {
            alert('学習方針が未入力です。');
            return false;
        }

        AlueSubsys.Utils.dispOnLoadingIcon(true);

        $.ajax({
            type : 'POST',
            url  : AlueSubsys.MemberDetailUtils.URL_LEARNING_POLICY_REGIST,
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
            //正常終了
            var result = response.data;
            var latestLpDatas = result.latestLpDatas;
            var allLpDatas = result.userLpDatas;

            //モーダル閉じる
            $('#newPolicyModal').modal('hide');

            //結果を学習方針履歴一覧に反映
            var latestPolicyHtml = '';
            jQuery.each(latestLpDatas, function(k, val) {
                latestPolicyHtml += '<tr>';
                latestPolicyHtml += '<td>' + val.lpCategory1Name + '</td>';
                latestPolicyHtml += '<td>' + val.lpTagName + '</td>';
                latestPolicyHtml += '<td>' + val.policy + '</td>';
                latestPolicyHtml += '<td>' + val.posted_at + '</td>';
                latestPolicyHtml += '</tr>';
            });
            $('table[name="latestLpDatas"] tbody').empty();
            $('table[name="latestLpDatas"] tbody').html(latestPolicyHtml);

            var allPolicyHtml = '';
            jQuery.each(allLpDatas, function(k, val) {
                allPolicyHtml += '<tr>';
                allPolicyHtml += '<td>' + (k+1) +'</td>';
                allPolicyHtml += '<td>' + val.posted_at +'</td>';
                allPolicyHtml += '<td>' + val.lpCategory1Name +'</td>';
                allPolicyHtml += '<td>' + val.lpCategory2Name +'</td>';
                allPolicyHtml += '<td>' + val.lpTagName +'</td>';
                allPolicyHtml += '<td>' + val.policy +'</td>';
                allPolicyHtml += '<td>' + val.comment +'</td>';
                allPolicyHtml += '</tr>';
            });
            $('table[name="allLpDatas"] tbody').empty();
            $('table[name="allLpDatas"] tbody').html(allPolicyHtml);

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);
            //AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    //会員詳細画面の再表示
    pageReload : function() {
        var userId = $('table[name="user-basic-info"]').find('input[name="userId"]').val();
        AlueSubsys.Utils.dispOnLoadingIcon(true);
        var postData = {
                "userId" : userId
            };
        AlueSubsys.AjaxUtils.postForm(AlueSubsys.MemberDetailUtils.URL_MEMBER_DETAIL, postData);
        return false;
    },

    //出題詳細モーダルを開く
    openTaskDetailModal : function(userTaskId) {

        if(!userTaskId || userTaskId < 1){
            return false;
        }

        //保持されている出題情報jsonを取得する
        var userTaskLogsJsonObj = AlueSubsys.MemberDetailUtils.userTaskLogsJson;
        var targetUserTask = null;

        $(userTaskLogsJsonObj).each(function(idx, obj){
            if(obj.userTaskId == userTaskId){
                targetUserTask = obj;
                return false;
            }
        });
        if(targetUserTask){
            //モーダル内にデータを設定する
            var panelObj = $('#taskDetailModal div.modal-body');

            var userName = targetUserTask.userInfo.lastName + ' ' + targetUserTask.userInfo.firstName
                         + ' (' + targetUserTask.userInfo.firstNameEn + ' ' + targetUserTask.userInfo.lastNameEn + ')';
            $(panelObj).find('span[name="title"]').text(userName + ' さんの宿題回答詳細');
            $(panelObj).find('input[name="memberName"]').val(userName);

            var operatorName = '';
            var operatorInfos = targetUserTask.operatorInfo;
            if(operatorInfos.length > 0){
                operatorName = targetUserTask.operatorInfo[0].staffName;
            }
            $(panelObj).find('input[name="operatorName"]').val(operatorName);

            var taskTypeName = targetUserTask.taskInfo.taskTypeName;
            $(panelObj).find('input[name="taskTypeName"]').val(taskTypeName);

            var questionSetDate = targetUserTask.questionSetDate;
            $(panelObj).find('input[name="questionSetDate"]').val(questionSetDate);

            var answerDeadlineDate = targetUserTask.answerDeadlineDate;
            $(panelObj).find('input[name="answerDeadlineDate"]').val(answerDeadlineDate);

            var answeredDate = targetUserTask.answeredDate;
            $(panelObj).find('input[name="answeredDate"]').val(answeredDate);

            var answerUrl = targetUserTask.originalAnswer;
            if(answerUrl){
                $(panelObj).find('button[name="modal-btn-answer-download"]').data('answer-url', answerUrl);
                $(panelObj).find('button[name="modal-btn-answer-download"]').prop('disabled', false);
            }

            var feedbackDate = targetUserTask.feedbackInfo.feedbackDate;
            $(panelObj).find('input[name="feedbackDate"]').val(feedbackDate);

            var feedbackComment = targetUserTask.feedbackInfo.feedbackComment;
            $(panelObj).find('textarea[name="feedbackComment"]').val(feedbackComment);
        }

        $('#taskDetailModal').modal();
    },
    closeTaskDetailModal : function() {
        //モーダル閉じる
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
        $('#taskDetailModal').modal('hide');
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
     * レッスンレポート表示
     */
    lessonReport : function(userId){
        var memberReportIssue = $('select[name="selectMemberReportIssue"]').val();
        var postData = {
                "userId" : userId,
                "memberReportIssue" : memberReportIssue
            };
        AlueSubsys.AjaxUtils.submitPostForm(this.URL_MEMBER_REPORT, postData, 'memberReport');
        return false;
    },

    //イベント登録
    registEventHandler : function(){
        $('button.btn-user-basic-update').on('click', function(){
            AlueSubsys.MemberDetailUtils.updateUserBasicInfo();
        });

        $('button[name="create-lp-btn"]').on('click', function(){
            AlueSubsys.MemberDetailUtils.registUserLearningPolicy();
        });

        $('button[name="closeTaskDetailModal"]').on('click', function(e){
            AlueSubsys.MemberDetailUtils.closeTaskDetailModal();
        });
        $('button[name="modal-btn-answer-download"]').on('click', function(e){
            var answerUrl = $(e.target).data('answer-url');
            AlueSubsys.MemberDetailUtils.voiceDataDownload(answerUrl);
        });

        //学習方針登録モーダルの閉時処理（データをリセットする）
        $(document).on('shown.bs.modal', '#newPolicyModal', function(){
            $(this).find('input[name="lp-created_at"]').val(AlueSubsys.Utils.getNow());
            $(this).find('select[name="lp-category1"]').val('');
            $(this).find('select[name="lp-category2"]').children().remove();
            $(this).find('select[name="lp-category2"]').append('<option value=""></option>');
            $(this).find('input[name="lp-tag"]').val('');
            $(this).find('input[name="lp-policy"]').val('');
            $(this).find('input[name="lp-remark"]').val('');
        });

        //表示領域スクロール・イベントハンドラ：OUTPUT履歴
        $('div#userOutputLogsDiv').on('scroll', function(){
            var outerObj = $('div#userOutputLogsDiv');      //外側表示枠<div>
            var innerObj = $('table#userOutputLogsTable');  //内側表示枠<table>

            var totalCount = $(innerObj).data('total-count');     //全件数
            var currentCount = $(innerObj).data('current-count'); //現在の表示件数
            if(currentCount >= totalCount){
                return false;//最終データまで表示されていたら何もしない
            }

            if(AlueSubsys.MemberDetailUtils.onScrollingUserOutputLogs){
                return false;//スクロール処理中(データ取得処理中)であれば何もしない
            }

            var innerHeight = $(innerObj).innerHeight(); //内側の要素の高さ
            var outerHeight = $(outerObj).innerHeight(); //外側の要素の高さ
            var outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
            var outerScrollTop = $(outerObj).scrollTop();

            if(outerBottom <= outerScrollTop) {
                //指定した要素の一番下までスクロールした時に実行

                //スクロール処理の処理中をセット
                AlueSubsys.MemberDetailUtils.onScrollingUserOutputLogs = true;
                //データの動的取得：OUTPUT履歴
                AlueSubsys.MemberDetailUtils.getUserOutputLogs();
            }
            return false;
        });

        //表示領域スクロール・イベントハンドラ：レッスン受講状況
        $('div#userLessonLogsDiv').on('scroll', function(){
            var outerObj = $('div#userLessonLogsDiv');      //外側表示枠<div>
            var innerObj = $('table#userLessonLogsTable');  //内側表示枠<table>

            var totalCount = $(innerObj).data('total-count');     //全件数
            var currentCount = $(innerObj).data('current-count'); //現在の表示件数
            if(currentCount >= totalCount){
                return false;//最終データまで表示されていたら何もしない
            }

            if(AlueSubsys.MemberDetailUtils.onScrollingUserLessonLogs){
                return false;//スクロール処理中(データ取得処理中)であれば何もしない
            }

            var innerHeight = $(innerObj).innerHeight(); //内側の要素の高さ
            var outerHeight = $(outerObj).innerHeight(); //外側の要素の高さ
            var outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
            var outerScrollTop = $(outerObj).scrollTop();

            if(outerBottom <= outerScrollTop) {
                //指定した要素の一番下までスクロールした時に実行

                //スクロール処理の処理中をセット
                AlueSubsys.MemberDetailUtils.onScrollingUserLessonLogs = true;
                //データの動的取得：レッスン受講状況
                AlueSubsys.MemberDetailUtils.getUserLessonLogs();
            }
            return false;
        });

        //表示領域スクロール・イベントハンドラ：宿題実施状況
        $('div#userTaskLogsDiv').on('scroll', function(){
            var outerObj = $('div#userTaskLogsDiv');      //外側表示枠<div>
            var innerObj = $('table#userTaskLogsTable');  //内側表示枠<table>

            var totalCount = $(innerObj).data('total-count');     //全件数
            var currentCount = $(innerObj).data('current-count'); //現在の表示件数
            if(currentCount >= totalCount){
                return false;//最終データまで表示されていたら何もしない
            }

            if(AlueSubsys.MemberDetailUtils.onScrollingUserTaskLogs){
                return false;//スクロール処理中(データ取得処理中)であれば何もしない
            }

            var innerHeight = $(innerObj).innerHeight(); //内側の要素の高さ
            var outerHeight = $(outerObj).innerHeight(); //外側の要素の高さ
            var outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
            var outerScrollTop = $(outerObj).scrollTop();

            if(outerBottom <= outerScrollTop) {
                //指定した要素の一番下までスクロールした時に実行

                //スクロール処理の処理中をセット
                AlueSubsys.MemberDetailUtils.onScrollingUserTaskLogs = true;
                //データの動的取得：レッスン受講状況
                AlueSubsys.MemberDetailUtils.getUserTaskLogs();
            }
            return false;
        });

        //表示領域スクロール・イベントハンドラ：メッセージ送受信履歴
        $('div#userMessageLogsDiv').on('scroll', function(){
            var outerObj = $('div#userMessageLogsDiv');      //外側表示枠<div>
            var innerObj = $('table#userMessageLogsTable');  //内側表示枠<table>

            var totalCount = $(innerObj).data('total-count');     //全件数
            var currentCount = $(innerObj).data('current-count'); //現在の表示件数
            if(currentCount >= totalCount){
                return false;//最終データまで表示されていたら何もしない
            }

            if(AlueSubsys.MemberDetailUtils.onScrollingUserMessageLogs){
                return false;//スクロール処理中(データ取得処理中)であれば何もしない
            }

            var innerHeight = $(innerObj).innerHeight(); //内側の要素の高さ
            var outerHeight = $(outerObj).innerHeight(); //外側の要素の高さ
            var outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
            var outerScrollTop = $(outerObj).scrollTop();

            if(outerBottom <= outerScrollTop) {
                //指定した要素の一番下までスクロールした時に実行

                //スクロール処理の処理中をセット
                AlueSubsys.MemberDetailUtils.onScrollingUserMessageLogs = true;
                //データの動的取得：メッセージ送受信履歴
                AlueSubsys.MemberDetailUtils.getUserMessageLogs();
            }
            return false;
        });

        //学習方針カテゴリ選択イベント
        $('select[name="lp-category1"]').on('change', function(){
            //カテゴリ小をリセット
            $('select[name="lp-category2"]').children().remove();
            var option = $('<option>').val('').text('');
            $('select[name="lp-category2"]').append(option);

            var category1Id = $(this).val();
            if(category1Id.length < 1){
                return false;
            }

            var postData = {
                "category1Id" : category1Id
            };

            //データLoadingの表示
        	AlueSubsys.Utils.dispOnLoadingIcon(true);

            $.ajax({
                type : 'GET',
                url  : AlueSubsys.MemberDetailUtils.URL_GET_LEARNING_POLICY_CATEGORY,
                dataType : 'JSON',
                data : postData
            }).done(function (response)
            {
                //データLoadingの表示解除
                AlueSubsys.Utils.dispOnLoadingIcon(false);

                if(!response){
                    return false;
                }

                //取得したデータの描画
                var userLpCategories = response.data;
                $.each(userLpCategories, function(idx, item){
                    var option = $('<option>')
                                    .val(item.id)
                                    .text(item.category_name);
                    $('select[name="lp-category2"]').append(option);
                });

            }).fail(function (XMLHttpRequest, textStatus, errorThrown)
            {
                //データLoadingの表示解除
                AlueSubsys.Utils.dispOnLoadingIcon(false);
                //AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
            });
            return false;
        });

    },

    //データの動的取得：OUTPUT履歴
    getUserOutputLogs : function(){
        var skip = AlueSubsys.MemberDetailUtils.skipRowCountUserOutputLogs;
        AlueSubsys.MemberDetailUtils.ajaxGetDataAndDraw('UserOutputLogs', skip);
    },
    //データの動的取得：レッスン受講状況
    getUserLessonLogs : function(){
        var skip = AlueSubsys.MemberDetailUtils.skipRowCountUserLessonLogs;
        AlueSubsys.MemberDetailUtils.ajaxGetDataAndDraw('UserLessonLogs', skip);
    },
    //データの動的取得：宿題実施状況
    getUserTaskLogs : function(){
        var skip = AlueSubsys.MemberDetailUtils.skipRowCountUserTaskLogs;
        AlueSubsys.MemberDetailUtils.ajaxGetDataAndDraw('UserTaskLogs', skip);
    },
    //データの動的取得：メッセージ送受信履歴
    getUserMessageLogs : function(){
        var skip = AlueSubsys.MemberDetailUtils.skipRowCountUserMessageLogs;
        AlueSubsys.MemberDetailUtils.ajaxGetDataAndDraw('UserMessageLogs', skip);
    },
    //データの動的取得：ajax共通処理(データ取得＆描画)
    ajaxGetDataAndDraw : function(dataType, skip){
        if(!skip){
            skip = 0;
        }
        var userId = $('input[name="userId"]').val();
        var postData = {
            "userId" : userId,
            "dataType" : dataType,
            "skip": skip
        };

        //データLoading行の表示
        AlueSubsys.MemberDetailUtils.dispDataLoading(dataType, true);

        $.ajax({
            type : 'GET',
            url  : this.URL_GET_MEMBER_DETAIL_PART_DATA,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            //データLoading行の表示解除
            AlueSubsys.MemberDetailUtils.dispDataLoading(dataType, false);

            if(!response){
                return false;
            }
            //取得したデータの描画
            AlueSubsys.MemberDetailUtils.ajaxDrawData(dataType, response.data);

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            //データLoading行の表示解除
            AlueSubsys.MemberDetailUtils.dispDataLoading(dataType, false);
            //AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },
    //データの動的取得：データの描画
    ajaxDrawData : function(dataType, responseData){

        var dataRows = responseData.dataRows;
        var totalCount = responseData.totalCount;
        var currentCount = responseData.currentCount;
        var rowHtml = '';

        if(dataType === 'UserOutputLogs'){
            //OUTPUT履歴
            $.each(dataRows, function(idx, row){
                rowHtml += '<tr>'
                        + '<td>' + row.recNo + '</td>'
                        + '<td>' + row.id + '</td>'
                        + '<td>' + row.lessonDateTime + '</td>'
                        + '<td>' + row.sessionId + '</td>'
                        + '<td>' + row.studentId + '</td>'
                        + '<td>' + row.sentences + '</td>'
                        + '</tr>';
            });
            $('#userOutputLogsTable tbody').append(rowHtml);
            $('#userOutputLogsTable').data('total-count', totalCount);
            $('#userOutputLogsTable').data('current-count', currentCount);
            //データ全件数を表示
            $('span#userOutputLogsTotalCount').html('全件数: '+ totalCount + '件');
            //データskip数を保持しておく
            AlueSubsys.MemberDetailUtils.skipRowCountUserOutputLogs = currentCount;
            //スクロール処理の完了をセット
            AlueSubsys.MemberDetailUtils.onScrollingUserOutputLogs = false;

        }else if(dataType === 'UserLessonLogs'){
            //レッスン受講状況
            $.each(dataRows, function(idx, row){
                rowHtml += '<tr>'
                        + '<td>' + row.recNo + '</td>'
                        + '<td>' + row.start_at + '</td>'
                        + '<td>' + row.end_at + '</td>'
                        + '<td>' + 'レッスン種別ID:' + row.lesson_type + '</td>'
                        + '<td>' + '講師ID: ' + row.alugo_teacher_id + '</td>'
                        + '<td>' + row.lesson_evaluation_mark + '</td>'
                        + '</tr>';
            });
            $('#userLessonLogsTable tbody').append(rowHtml);
            $('#userLessonLogsTable').data('total-count', totalCount);
            $('#userLessonLogsTable').data('current-count', currentCount);
            //データ全件数を表示
            $('span#userLessonLogsTotalCount').html('全件数: '+ totalCount + '件');
            //データskip数を保持しておく
            AlueSubsys.MemberDetailUtils.skipRowCountUserLessonLogs = currentCount;
            //スクロール処理の完了をセット
            AlueSubsys.MemberDetailUtils.onScrollingUserLessonLogs = false;

        }else if(dataType === 'UserTaskLogs'){
            //宿題実施状況
            $.each(dataRows, function(idx, row){

                var feedbackDate = row.feedbackInfo.feedbackDate;
                if(!feedbackDate){
                    feedbackDate = '';
                }

                rowHtml += '<tr>'
                        + '<td>' + row.recNo + '</td>'
                        + '<td>' + row.questionSetDate + '  -  ' + row.answerDeadlineDate + '</td>'
                        + '<td>' + row.taskInfo.taskTypeName + '</td>'
                        + '<td>' + row.answeredDate + '</td>'
                        + '<td>' + feedbackDate + '</td>'
                        + '<td class="basic-table-btn">'
                            + '<button name="openTaskDetailModal" '
                            + 'class="btn btn-domain" '
                            + 'onclick="AlueSubsys.MemberDetailUtils.openTaskDetailModal('+ row.userTaskId + ');" '
                            + '>詳細</button>'
                        + '</td>'
                        + '</tr>';
            });
            $('#userTaskLogsTable tbody').append(rowHtml);
            $('#userTaskLogsTable').data('total-count', totalCount);
            $('#userTaskLogsTable').data('current-count', currentCount);

            //出題詳細モーダル表示のために、出題情報jsonを保持しておく(追加となることに注意)
            AlueSubsys.MemberDetailUtils.userTaskLogsJson =
                        AlueSubsys.MemberDetailUtils.userTaskLogsJson.concat(dataRows);

            //データ全件数を表示
            $('span#userTaskLogsTotalCount').html('全件数: '+ totalCount + '件');
            //データskip数を保持しておく
            AlueSubsys.MemberDetailUtils.skipRowCountUserTaskLogs = currentCount;
            //スクロール処理の完了をセット
            AlueSubsys.MemberDetailUtils.onScrollingUserTaskLogs = false;

        }else if(dataType === 'UserMessageLogs'){
            //メッセージ送受信履歴
            $.each(dataRows, function(idx, row){
                rowHtml += '<tr>'
                        + '<td>' + row.recNo + '</td>'
                        + '<td>' + row.connectTypeName + '</td>'
                        + '<td>' + row.postedAt + '</td>'
                        + '<td>' + row.messageTypeName + '<br />' + row.cwPostTypeName + '</td>'
                        + '<td>' + row.postedName + '</td>'
                        + '<td>' + row.postedContext + '</td>'
                        + '</tr>';
            });
            $('#userMessageLogsTable tbody').append(rowHtml);
            $('#userMessageLogsTable').data('total-count', totalCount);
            $('#userMessageLogsTable').data('current-count', currentCount);
            //データ全件数を表示
            $('span#userMessageLogsTotalCount').html('全件数: '+ totalCount + '件');
            //データskip数を保持しておく
            AlueSubsys.MemberDetailUtils.skipRowCountUserMessageLogs = currentCount;
            //スクロール処理の完了をセット
            AlueSubsys.MemberDetailUtils.onScrollingUserMessageLogs = false;
        }
    },

    //データLoading行の表示および解除
    dispDataLoading : function(dataType, sw){
        var loadingRowHtml = '<tr class="member-detail-data-onloading-row">'
                           + '<td colspan="6">'
                           + '<span class="member-detail-data-onloading-icon"></span>'
                           + '</td>'
                           + '</tr>';

        if(dataType === 'UserOutputLogs'){
            if(sw){
                $('#userOutputLogsTable tbody').append(loadingRowHtml);
            }else{
                $('#userOutputLogsTable tbody tr.member-detail-data-onloading-row').remove();
            }
        }else if(dataType === 'UserLessonLogs'){
            if(sw){
                $('#userLessonLogsTable tbody').append(loadingRowHtml);
            }else{
                $('#userLessonLogsTable tbody tr.member-detail-data-onloading-row').remove();
            }
        }else if(dataType === 'UserTaskLogs'){
            if(sw){
                $('#userTaskLogsTable tbody').append(loadingRowHtml);
            }else{
                $('#userTaskLogsTable tbody tr.member-detail-data-onloading-row').remove();
            }
        }else if(dataType === 'UserMessageLogs'){
            if(sw){
                $('#userMessageLogsTable tbody').append(loadingRowHtml);
            }else{
                $('#userMessageLogsTable tbody tr.member-detail-data-onloading-row').remove();
            }
        }
    },

    //初期化
    init : function(){
        //イベント登録
        this.registEventHandler();

        //セレクトボックスの生成
        $('.select2').select2({
            language: 'ja',
            width: '100%'
        });
    }

};

$(document).ready( function(){
    //初期化
    AlueSubsys.MemberDetailUtils.init();

    //データの動的取得：OUTPUT履歴
    AlueSubsys.MemberDetailUtils.getUserOutputLogs();
    //データの動的取得：レッスン受講状況
    AlueSubsys.MemberDetailUtils.getUserLessonLogs();
    //データの動的取得：宿題実施状況
    AlueSubsys.MemberDetailUtils.userTaskLogsJson = [];
    AlueSubsys.MemberDetailUtils.getUserTaskLogs();
    //データの動的取得：メッセージ送受信履歴
    AlueSubsys.MemberDetailUtils.getUserMessageLogs();

});

