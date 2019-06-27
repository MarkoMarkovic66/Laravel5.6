/**
 * javascript utility
 */
AlueSubsys.Utils = {

    /**
     * 0以上の整数をチェック
     */
    isIntNumber : function(val){
        var pattern = /^([1-9]\d*|0)$/;
        return pattern.test(val);
    },
    /**
     * 現在の 年月日 時分秒 ミリ秒 を返す
     * yyyymmdd_hhmmss_ms
     * @returns string
     */
    getCurrentDateTime : function(){
        var now = new Date();
        var yy = String(now.getFullYear());
        var mm = String(now.getMonth() + 1);
        var dd = String(now.getDate());
        var hh = String(now.getHours());
        var mi = String(now.getMinutes());
        var ss = String(now.getSeconds());
        var ms = String(now.getMilliseconds());
        return (yy + mm + dd + '_' + hh + mi + ss + '_' + ms);
    },

	maskWholeDisplay : function (sw){
        if(sw){
            $('#maskWholeDisplay').show();
        }else{
            $('#maskWholeDisplay').hide();
        }
    },

	dispOnLoadingIcon : function (sw){

        $('window').scrollTop(0);

        if(sw){
            var height = $('window').innerHeight();
            var width = $('window').innerWidth();
            $('#maskWholeDisplay').height(height);
            $('#maskWholeDisplay').width(width);
            $('#maskWholeDisplay').show();
            $('#globalOnLoadingIcon').show();

        }else{
            $('#maskWholeDisplay').hide();
            $('#globalOnLoadingIcon').hide();
        }
    },

  /**
   * オブジェクトを点滅して表示します
   * @param object obj
   */
	objectBlink: function (obj) {
        $(obj).hide().fadeIn(400, function(){
			$(this).fadeOut(400, function(){
				$(this).fadeIn(400, function(){
					$(this).fadeOut(400, function(){
						$(this).fadeIn(400);
					});
				});
			});
		});
	},

    /**
     * dayNumberに対応した曜日表記を返す
     * @param int dayNumber
     * @returns string
     */
    getDayDisplay: function(dayNumber) {
        var dayName = '';
        if(dayNumber == 1){
            dayName = '月';
        }else if(dayNumber == 2){
            dayName = '火';
        }else if(dayNumber == 3){
            dayName = '水';
        }else if(dayNumber == 4){
            dayName = '木';
        }else if(dayNumber == 5){
            dayName = '金';
        }else if(dayNumber == 6){
            dayName = '土'
        }else if(dayNumber == 7){
            dayName = '日';
        }
        return dayName;
    },

    getNow : function() {
        var date = new Date();
        format_str = 'YYYY-MM-DD hh:mm:ss';
        format_str = format_str.replace(/YYYY/g, date.getFullYear());
        format_str = format_str.replace(/MM/g, ("0"+(date.getMonth() + 1)).slice(-2));
        format_str = format_str.replace(/DD/g, ("0"+date.getDate()).slice(-2));
        format_str = format_str.replace(/hh/g, ("0"+date.getHours()).slice(-2));
        format_str = format_str.replace(/mm/g, ("0"+date.getMinutes()).slice(-2));
        format_str = format_str.replace(/ss/g, ("0"+date.getSeconds()).slice(-2));
        return format_str;
    }

};

