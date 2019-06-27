/**
 * main javascript
 */
AlueSubsys.AlueMain = {

    //初期化
    init : function(){
        //カレンダー
        $('#datepicker-daterange .input-daterange').datepicker({
            language: 'ja',
            format: "yyyy/mm/dd"
        });

        //セレクトボックス
        $('.select2').select2({
            language: 'ja'
        });

    }
};

$(document).ready( function(){
    AlueSubsys.AlueMain.init();
});
