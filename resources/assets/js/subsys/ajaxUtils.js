/**
 * ajax utility
 */
AlueSubsys.AjaxUtils = {
    /**
     * ajax POSTリクエストにおけるtokenを設定する。
     */
    setupToken : function(){
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            //IE対策(ajaxリクエストをキャッシュしない)
            cache: false,
            timeout: 5 * (60 * 1000)   //5分 = 5 * (60秒)
        });
    },

    errorHandle : function(XMLHttpRequest, textStatus, errorThrown){
        var msg = 'Ajax Error' + '\n'
                + 'status:' + XMLHttpRequest.status + '\n'
                + errorThrown;
        alert(msg);
        return false;
    },

    userErrorHandle : function(response){
        //var statusCode = response.status; //未使用
        var statusMessage = 'エラーコード：' + response.data.status + '\n'
                          + 'エラー内容：' + response.data.message;
        var msg = '処理時にエラーが発生しました。' + '\n'
                + statusMessage;
        alert(msg);
        return false;
    },

    validationErrorHandle : function(response){
        var statusMessage = '';
        var errorMessages = response.data;
        $.each(errorMessages, function(idx, item){
            statusMessage += '・' + item + '\n';
        });
        var msg = '入力書式・内容をご確認ください。' + '\n\n'
                + statusMessage;
        alert(msg);
        return false;
    },


    postForm : function(url, data) {
        var $form = $('<form/>', {'action': url, 'method': 'post'});
        for(var key in data) {
            $form.append($('<input/>', {'type': 'hidden', 'name': key, 'value': data[key]}));
        }

        var xCsrfToken = $('meta[name="csrf-token"]').attr('content');
        $form.append(
            $('<input/>', {'type': 'hidden', 'name': '_token', 'value': xCsrfToken})
        );

        $form.appendTo(document.body);
        $form.submit();
    },

    submitGetForm : function(url, data) {
        var $form = $('<form/>', {'action': url, 'method': 'get'});
        $form.appendTo(document.body);
        $form.submit();
    },

    submitPostForm : function(url, data, target) {
        if(target){
            var $form = $('<form/>', {'action': url, 'method': 'post', 'target': target});
        }else{
            var $form = $('<form/>', {'action': url, 'method': 'post'});
        }

        for(var key in data) {
            $form.append($('<input/>', {'type': 'hidden', 'name': key, 'value': data[key]}));
        }
        var xCsrfToken = $('meta[name="csrf-token"]').attr('content');
        $form.append(
            $('<input/>', {'type': 'hidden', 'name': '_token', 'value': xCsrfToken})
        );
        $form.appendTo(document.body);
        $form.submit();
    }

};

$(document).ready( function(){
    AlueSubsys.AjaxUtils.setupToken();
});
