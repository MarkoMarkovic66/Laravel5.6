/**
 * dashboard javascript
 */
AlueIntegOffice.DashBoardUtils = {

    goToTaskWithNoAssign : function() {
        window.location.href = '/task/noassign';
        return false;
    },
    goToTask : function() {
        window.location.href = '/task';
        return false;
    },
    goToMember : function() {
        window.location.href = '/member';
        return false;
    },

    //イベント登録
    registEventHandler : function(){
        $('button[name="goToTaskWithNoAssign"]').on('click', function(){
            AlueIntegOffice.DashBoardUtils.goToTaskWithNoAssign();
        });
        $('button[name="goToTask"]').on('click', function(){
            AlueIntegOffice.DashBoardUtils.goToTask();
        });
        $('button[name="goToMember"]').on('click', function(){
            AlueIntegOffice.DashBoardUtils.goToMember();
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
    AlueIntegOffice.DashBoardUtils.init();
});

