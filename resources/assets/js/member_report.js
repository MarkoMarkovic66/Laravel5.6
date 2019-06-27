/**
 * 会員レポート javascript
 */
AlueIntegOffice.MemberReportUtils = {

    URL_PUT_PDF : '/member_report_pdf',

    putPdf : function() {
        var userId = $('input[name="userId"]').val();
        var issueDate = $('input[name="issueDate"]').val();
        var url = this.URL_PUT_PDF + '/' + userId + '/' + issueDate;
        AlueSubsys.AjaxUtils.submitGetForm(url);
        return false;
    },


    //イベント登録
    registEventHandler : function(){
    },

    //初期化
    init : function(){
        //イベント登録
        this.registEventHandler();
    }
};

$(document).ready( function(){
    //初期化
    AlueSubsys.MemberReportUtils.init();
});

