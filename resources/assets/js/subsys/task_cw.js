/**
 * 宿題管理Chatwork投稿 javascript
 */
AlueSubsys.TaskCwUtils = {

    URL_DO_CHATWORK_CONTRIBUTE : '/api/do_chatwork_contribute',

    //ログインアカウント種別
    accountKindId : '',
    //一覧データの保持
    taskListData : '',

    /**
     * 回答音声データ・ダウンロード
     */
    voiceDataDownload : function(answerUrl){
        var windowOption = 'width=500, height=100, top=0,left=0, menubar=no, toolbar=no, scrollbars=no';
        window.open(answerUrl, 'alue_voice', windowOption);
        return false;
    },

    //一括投稿
    doRegist : function(){
        //var userTaskIds = $('#userTaskIds').val();
        var contents = [];
        var userTaskIdObj = $('input[name^="userTaskId"]');
        $(userTaskIdObj).each(function(idx, item){
            var userId = $('input[name="userId-'+ String(idx) + '"]').val();
            var userTaskId = $('input[name="userTaskId-'+ String(idx) + '"]').val();
            var feedbackText = $('textarea[name="feedbackText-'+ String(idx) + '"]').val();
            var content = {
                "userId" : userId,
                "userTaskId" : userTaskId,
                "feedbackText" : feedbackText
            };
            contents.push(content);
        });

        var postData = {
                "contents" : contents
            };

        AlueSubsys.Utils.dispOnLoadingIcon(true);

        $.ajax({
            type : 'POST',
            url  : this.URL_DO_CHATWORK_CONTRIBUTE,
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

            //登録OK -> 一覧に戻る
            AlueSubsys.TaskCwUtils.goback();

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);
            AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    //一覧に戻る
    goback : function(){
        AlueSubsys.AjaxUtils.submitGetForm('/task/back', {});
    },

    //イベント登録
    registEventHandler : function(){
        //音声DLボタン
        $('button.btn-voice-dl').on('click', function(e){
            var answerUrl = $(e.target).data('answer-url');
            AlueSubsys.TaskCwUtils.voiceDataDownload(answerUrl);
        });
        //一括投稿ボタン
        $('button.btn-register').on('click', function(e){
            AlueSubsys.TaskCwUtils.doRegist();
        });
        //一覧に戻るボタン
        $('button.btn-goback').on('click', function(e){
            AlueSubsys.TaskCwUtils.goback();
        });
    },

    //初期化
    init : function(){
        //ログインアカウント種別保持
        AlueSubsys.TaskCwUtils.accountKindId = $('#accountKindId').val();
        //イベント登録
        this.registEventHandler();
    }
};

$(document).ready( function(){
    AlueSubsys.TaskCwUtils.init();
});

