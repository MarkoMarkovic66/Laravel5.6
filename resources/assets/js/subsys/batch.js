/**
 * バッチ管理 javascript
 */
AlueSubsys.BatchUtils = {
    URL_BATCH_LOG_TOP : '/batch/',
    URL_RUN_MANUAL    : '/api/batch/run_manual',

    searchPageNo : 1,

    doSearch : function(){
        var url = AlueSubsys.BatchUtils.URL_BATCH_LOG_TOP
                + AlueSubsys.BatchUtils.searchPageNo;
        window.location.href = url;
    },

    firstPage : function(){
        AlueSubsys.BatchUtils.searchPageNo = 1;
        AlueSubsys.BatchUtils.doSearch();
    },
    previousPage : function(){
        var p = $('input[name="currentPageNo"]').val();
        AlueSubsys.BatchUtils.searchPageNo =  parseInt(p) - 1;
        if(AlueSubsys.BatchUtils.searchPageNo < 1){
           AlueSubsys.BatchUtils.searchPageNo = 1;
        }
        AlueSubsys.BatchUtils.doSearch();
    },
    nextPage : function(){
        var p = $('input[name="currentPageNo"]').val();
        AlueSubsys.BatchUtils.searchPageNo = parseInt(p) + 1;
        if(AlueSubsys.BatchUtils.searchPageNo > AlueSubsys.BatchUtils.totalPageNo){
           AlueSubsys.BatchUtils.searchPageNo = AlueSubsys.BatchUtils.totalPageNo;
        }
        AlueSubsys.BatchUtils.doSearch();
    },
    lastPage : function(p){
        AlueSubsys.BatchUtils.searchPageNo = p;
        AlueSubsys.BatchUtils.doSearch();
    },
    goPage : function(p){
        AlueSubsys.BatchUtils.searchPageNo = p;
        AlueSubsys.BatchUtils.doSearch();
    },

    batchRunManual : function(batchName){
        var postData = {
            "batchName" : batchName
        };

        AlueSubsys.Utils.dispOnLoadingIcon(true);

        $.ajax({
            type : 'POST',
            url  : this.URL_RUN_MANUAL,
            dataType : 'JSON',
            timeout : 0, //無制限
            data : postData
        }).done(function (response)
        {
            //AlueSubsys.Utils.dispOnLoadingIcon(false);

            if(!response){
                return false;
            }

            //バリデーション・エラー判定
            if(response.status !== 'OK'){
                AlueSubsys.AjaxUtils.validationErrorHandle(response);
                return false;
            }

            //バッチ管理画面を再表示する
            AlueSubsys.BatchUtils.searchPageNo = 1;
            AlueSubsys.BatchUtils.doSearch();

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueSubsys.Utils.dispOnLoadingIcon(false);
            //AlueSubsys.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });

    },


    //イベント登録
    registEventHandler : function(){
        //バッチ再実行ボタン
        $('button[name="btn-batch-modal"]').on('click', function(e){
            var batchName = $(e.target).data('batch-name');
            AlueSubsys.BatchUtils.batchRunManual(batchName);
        });

    },

    //初期化
    init : function(){
        //イベント登録
        this.registEventHandler();
    }
};

$(document).ready( function(){
    //初期化
    AlueSubsys.BatchUtils.init();
});
