/**
 * 会員管理 javascript
 */
AlueIntegOffice.MemberUtils = {

    URL_MEMBER_DETAIL      : '/member_detail',
    URL_ACCOUNT_MANAGE     : '/account',
    URL_MEMBER_SEARCH      : '/api/member_search',
    URL_GET_COUNSELOR_INFO : '/api/get_counselor_info',
    URL_REGIST_COUNSELOR   : '/api/regist_counselor',

    //URL_POST_CHATWORK_MESSAGE : '/api/post_chatwork_message',

    //ログインアカウント種別
    accountKindId : '',
    //一覧データの保持
    memberListData : '',

    //検索条件の保持
    searchCondition : null,
    //ページNoの保持
    searchPageNo : 1,
    totalPageNo : 0,


    //一覧リスト検索
    doSearch : function(reSearch){
        if(reSearch){
            //再検索(前回検索条件で再度検索する)
            var postData = this.searchCondition;
            //ページ指定のみ設定する
            postData.pageNo = this.searchPageNo;

        }else{
            //初回検索
            var memberId        = $('input[name="memberId"]').val();
            var memberSrId      = $('input[name="memberSrId"]').val();
            var memberName      = $('input[name="memberName"]').val();
            var mail            = $('input[name="mail"]').val();
            var phoneNumber     = $('input[name="phoneNumber"]').val();
            var hasMemberReport = $('input[name="hasMemberReport"]').is(':checked');
            var pageNo          = this.searchPageNo;

            var postData = {
                "memberId"        : memberId,
                "memberSrId"      : memberSrId,
                "memberName"      : memberName,
                "mail"            : mail,
                "phoneNumber"     : phoneNumber,
                "hasMemberReport" : hasMemberReport,
                "pageNo"          : pageNo
                };

            //検索条件の保持
            AlueIntegOffice.MemberUtils.searchCondition = postData;
        }

        AlueIntegOffice.Utils.dispOnLoadingIcon(true);

        $.ajax({
            type : 'POST',
            url  : this.URL_MEMBER_SEARCH,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);

            if(!response){
                return false;
            }

            //バリデーション・エラー判定
            if(response.status !== 'OK'){
                AlueIntegOffice.AjaxUtils.validationErrorHandle(response);
                return false;
            }

            //ページネーション表示をいったん消去する
            $('div#task-list-pagination').empty();

            if(response.data.count < 1){
                var resultMsg = '<div class="top_guidance_message">'
                               + '該当データがありません。'
                               + '</div>';
                $('div#member-list').empty();
                $('div#member-list').html(resultMsg);
                return false;
            }

            //総件数の保持
            var totalRowCount  = response.data.totalRowCount;
            var totalPageCount = response.data.totalPageCount;
            AlueIntegOffice.MemberUtils.totalPageNo = totalPageCount;

            //一覧データの保持
            var memberList = response.data.memberList;
            AlueIntegOffice.MemberUtils.memberListData = memberList;
            //一覧リストのDOMを生成する
            var memberListHtml = '<table class="table table-sm table-hover member-list">';
            $.each(memberList, function(idx, rowData){
                var listRowHtml = AlueIntegOffice.MemberUtils.getListRowHtml(idx, rowData);
                memberListHtml += listRowHtml;
            });
            memberListHtml += '</tbody></table>';

            //一覧リストのDOMを描画する
            $('div#member-list').empty();
            $('div#member-list').html(memberListHtml);
            $(document).scrollTop(0);
            $('div.panel-scroll').scrollTop(0);
            //生成したDOMにイベントを付与する
            AlueIntegOffice.MemberUtils.setEventHandlerAfterDomCreated();

            //ページネーション表示
            var pageBlade = response.data.pageBlade;
            $('div#member-list-pagination').html(pageBlade);

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);
            //AlueIntegOffice.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    /**
     * 一覧表示の1行のHtmlを取得する
     * @param int idx
     * @param object rowData
     * @returns String
     */
    getListRowHtml : function(idx, rowData){

        var listRowHtml = '';
        if(idx < 1){
            //先頭行の場合は見出し行を生成
            if(AlueIntegOffice.MemberUtils.accountKindId == '4'){
                //オペレータ：詳細項目なし
                listRowHtml += '<thead class="thead-default"><tr>'
                    + '<th>会員ID</th>'
                    + '<th>SR-ID</th>'
                    + '<th>氏名</th>'
                    + '<th>Mail</th>'
                    + '<th>PhoneNumber</th>'
                    + '</tr></thead>'
                    + '<tbody>';

            }else{
                //オペレータ以外
                listRowHtml += '<thead class="thead-default"><tr>'
                    + '<th>会員ID</th>'
                    + '<th>SR-ID</th>'
                    + '<th>氏名</th>'
                    + '<th>Mail</th>'
                    + '<th>PhoneNumber</th>'
                    + '<th>詳細</th>'
                    + '</tr></thead>'
                    + '<tbody>';
            }
        }

        //会員情報
        var userId = rowData.userId;
        var arugoUserId = rowData.arugoUserId;
        var srUserId = (rowData.srUserId) ? rowData.srUserId: '';
        var memberName = rowData.lastName + ' ' + rowData.firstName + '<br />'
                       + '(' + rowData.firstNameEn + ' ' + rowData.lastNameEn + ')';

        var buttonCounselorHtml = '<button name="btn-counselor" class="btn btn-domain">担当カウンセラー一覧</button>';

        var buttonCalendarHtml  = '<a href="/task_calendar/' + userId + '" target="task_calendar">'
                                + '<button name="btn-calendar" class="btn btn-domain">出題カレンダー</button>'
                                + '</a>';

        var buttonHtml = '';
        /***** 12/25 暫定対応 出題カレンダーボタンのみ表示
        if(AlueIntegOffice.MemberUtils.accountKindId == '1' ||
           AlueIntegOffice.MemberUtils.accountKindId == '2' ){
            buttonHtml = buttonCounselorHtml + '&nbsp;'
                       + buttonCalendarHtml;
        }else{
            buttonHtml = buttonCalendarHtml;
        }
        ***/
        buttonHtml = buttonCalendarHtml;



        if(AlueIntegOffice.MemberUtils.accountKindId == '4'){
            //オペレータ
            listRowHtml += '<tr class="table-row"' + ' data-user-id="' + userId + '">'
                + '<td>' + arugoUserId + '</td>'
                + '<td>' + srUserId + '</td>'
                + '<td>' + memberName + '</td>'
                + '<td>' + rowData.mail + '</td>'
                + '<td>' + rowData.phoneNumber + '</td>'
                + '</tr>';

        }else{
            //オペレータ以外
            listRowHtml += '<tr class="table-row"' + ' data-user-id="' + userId + '">'
                + '<td>' + arugoUserId + '</td>'
                + '<td>' + srUserId + '</td>'
                + '<td>' + memberName + '</td>'
                + '<td>' + rowData.mail + '</td>'
                + '<td>' + rowData.phoneNumber + '</td>'
                + '<td>' + buttonHtml + '</td>'
                + '</tr>';
        }

        return listRowHtml;
    },

    /**
     * DOM描画後のイベントハンドラ再設定
     */
    setEventHandlerAfterDomCreated : function(){
        //行クリック・イベントハンドラ
        $('table.member-list tr.table-row').on('click', function(e){
            if ($(e.target).is('button[name="btn-counselor"]')) {
                //担当カウンセラー一覧ボタン
                var userId = $(this).data('user-id');
                AlueIntegOffice.MemberUtils.counselorInfo(userId);
                return false;

            }else if ($(e.target).is('button[name="btn-calendar"]')) {
                //出題カレンダーボタン
                return true;

            }else{
                // 12/25暫定対応（行クリックで遷移しない）
                return false;


                if(AlueIntegOffice.MemberUtils.accountKindId == '4'){
                    //オペレータ：会員詳細表示は無し
                    return false;

                }else{
                    //オペレータ以外
                    //行クリックで会員詳細画面へ遷移
                    var userId = $(this).data('user-id');
                    AlueIntegOffice.Utils.dispOnLoadingIcon(true);
                    var postData = {
                            "userId" : userId
                        };
                    //会員詳細画面へ遷移する
                    AlueIntegOffice.AjaxUtils.postForm(AlueIntegOffice.MemberUtils.URL_MEMBER_DETAIL, postData);
                    return false;
                }
            }
        });

    },

    firstPage : function(){
        AlueIntegOffice.MemberUtils.searchPageNo = 1;
        AlueIntegOffice.MemberUtils.doSearch(true);
    },
    previousPage : function(){
        var p = AlueIntegOffice.MemberUtils.searchPageNo;
        AlueIntegOffice.MemberUtils.searchPageNo = p-1;
        if(AlueIntegOffice.MemberUtils.searchPageNo < 1){
           AlueIntegOffice.MemberUtils.searchPageNo = 1;
        }
        AlueIntegOffice.MemberUtils.doSearch(true);
    },
    nextPage : function(){
        var p = AlueIntegOffice.MemberUtils.searchPageNo;
        AlueIntegOffice.MemberUtils.searchPageNo = p+1;
        if(AlueIntegOffice.MemberUtils.searchPageNo > AlueIntegOffice.MemberUtils.totalPageNo){
           AlueIntegOffice.MemberUtils.searchPageNo = AlueIntegOffice.MemberUtils.totalPageNo;
        }
        AlueIntegOffice.MemberUtils.doSearch(true);
    },
    lastPage : function(){
        AlueIntegOffice.MemberUtils.searchPageNo = AlueIntegOffice.MemberUtils.totalPageNo;
        AlueIntegOffice.MemberUtils.doSearch(true);
    },
    goPage : function(p){
        AlueIntegOffice.MemberUtils.searchPageNo = p;
        AlueIntegOffice.MemberUtils.doSearch(true);
    },

    resetSearchCondition : function(){
        $('input').val('');
        $('input[name="hasMemberReport"]').prop('checked', false);
    },

    //カウンセラーモーダル
    counselorInfo : function(userId){
        var memberInfo = AlueIntegOffice.MemberUtils.getMemberInfo(userId);
        var memberName = memberInfo.lastName + ' ' + memberInfo.firstName
                       + ' (' + memberInfo.firstNameEn + ' ' + memberInfo.lastNameEn + ')';
        var title =  memberName + ' さんのカウンセラー一覧';

        var modalBody = $('#counselorModal').find('div.modal-body');
        $(modalBody).find('span[name="title"]').html(title);
        $(modalBody).find('input[name="userId"]').val(userId);

        AlueIntegOffice.Utils.dispOnLoadingIcon(true);

        var postData = {
            "userId"    : userId
        };

        $.ajax({
            type : 'POST',
            url  : this.URL_GET_COUNSELOR_INFO,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);

            if(!response){
                return false;
            }

            //バリデーション・エラー判定
            if(response.status !== 'OK'){
                AlueIntegOffice.AjaxUtils.validationErrorHandle(response);
                return false;
            }

            var assignedCounselorList  = response.data.assignedCounselorList;
            var candidateCounselorList = response.data.candidateCounselorList;

            //設定されたカウンセラー一覧を表示
            var assignedCounselorListHtml =
                        '<table class="counselor-table core-tbl">'
                      + '<thead><tr>'
                      + '<th>カウンセラー名</th>'
                      + '<th>操作</th>'
                      + '</tr></thead><tbody>';

            $.each(assignedCounselorList, function(idx, row){
                assignedCounselorListHtml +=
                        '<tr data-account-id="' + row.accountId + '" data-regist-status="1">'
                      + '<td class="counselor-name">' + row.accountName + '</td>'
                      + '<td class="counselor-op">'
                      + '<button class="btn btn-rid">担当から外す</button>'
                      + '</td>'
                      + '</tr>';
            });
            assignedCounselorListHtml += '</tbody></table>';
            $(modalBody).find('div[name="counselorList"]').html(assignedCounselorListHtml);

            //候補カウンセラー一覧を表示する
            var candidateCounselorListHtml =
                        '<select name="counselor-select" class="counselor-select select2">'
                      + '<option value="0">カウンセラーを新規に割り当てる</option>';
            $.each(candidateCounselorList, function(idx, row){
                candidateCounselorListHtml +=
                        '<option value="'+ row.accountId +'">'
                      + row.accountName
                      + '</option>';
            });
            candidateCounselorListHtml += '</select>';
            $(modalBody).find('div[name="candidateCounselorList"]').html(candidateCounselorListHtml);

            AlueIntegOffice.MemberUtils.setEventOnCounselorModal();

            //モーダル開く
            $('#counselorModal').modal();

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);
            //AlueIntegOffice.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });

    },

    setEventOnCounselorModal : function() {
        //セレクトボックスの生成
        $('.select2').select2({
            language: 'ja',
            width: '250px'
        });

        $('div#counselorModal div.modal-body div.counselor-list button.btn-rid')
        .off('click');
        $('div#counselorModal div.modal-body div.counselor-list button.btn-rid')
        .on('click', function(e){
            $(e.target).closest('tr').toggleClass('row-deleted');
            var registStatus = $(e.target).closest('tr').data('regist-status');
            if(registStatus == '0'){//削除されたもの
                $(e.target).closest('tr').data('regist-status', '1');
                $(e.target).text('担当から外す');

            }else if(registStatus == '1'){//存在するもの
                $(e.target).closest('tr').data('regist-status', '0');
                $(e.target).text('担当に戻す');

            }else if(registStatus == '2'){//新規追加されたもの
                var accountId = $(e.target).closest('tr').data('account-id');
                var accountName = $(e.target).closest('tr').data('account-name');
                //該当行の削除
                $(e.target).closest('tr').remove();

                //プルダウンの復元
                var candidateCounselorOption = $('<option>')
                        .val(accountId)
                        .text(accountName);
                var selectObj = $('div#counselorModal div.modal-body div.counselor-asign-item select.counselor-select');
                (selectObj).append(candidateCounselorOption);
                //select2の再構築
                $('span.select2-container').remove();
                $(selectObj).select2({
                    language: 'ja',
                    width: '250px'
                });

            }else if(registStatus == '3'){//新規追加されたのちに削除されたもの
                $(e.target).closest('tr').data('regist-status', '2');
                $(e.target).text('担当から外す');
            }
            return false;
        });

        $('div#counselorModal div.modal-body div.counselor-asign-item select.counselor-select')
        .off('change');
        $('div#counselorModal div.modal-body div.counselor-asign-item select.counselor-select')
        .on('change', function(e){
            var accountId = $(e.target).find('option:selected').val();
            var accountName = $(e.target).find('option:selected').text();
            var counselorListRowHtml =
                        '<tr data-account-id="' + accountId + '" data-account-name="' + accountName + '" data-regist-status="2">'
                      + '<td class="counselor-name">' + accountName + '</td>'
                      + '<td class="counselor-op">'
                      + '<button class="btn btn-rid">担当から外す</button>'
                      + '</td>'
                      + '</tr>';
            $('table.counselor-table tbody').append(counselorListRowHtml);
            $(e.target).find('option:selected').remove();
            AlueIntegOffice.MemberUtils.setEventOnCounselorModal();
            return false;
        });

        $('div#counselorModal div.modal-body div.counselor-asign-item div.counselor-add button.btn-add')
        .off('click');
        $('div#counselorModal div.modal-body div.counselor-asign-item div.counselor-add button.btn-add')
        .on('click', function(e){
            AlueIntegOffice.Utils.dispOnLoadingIcon(true);
            //アカウント管理画面へ遷移する
            window.location.href = AlueIntegOffice.MemberUtils.URL_ACCOUNT_MANAGE;
        });

        $('div#counselorModal div.modal-footer button.btn-register').off('click');
        $('div#counselorModal div.modal-footer button.btn-register').on('click', function(e){
            var modalBody = $('#counselorModal').find('div.modal-body');
            var userId = $(modalBody).find('input[name="userId"]').val();
            AlueIntegOffice.MemberUtils.registCounselorInfo(userId);
            return false;
        });
    },

    //カウンセラー情報の登録
    registCounselorInfo : function(userId){
        var accountDatas =[];
        $('table.counselor-table tbody tr').each(function(idx, row){
            var registStatus = $(row).data('regist-status');
            var accountId = $(row).data('account-id');
            accountDatas.push({
                "registStatus" : registStatus,
                "accountId"    : accountId
            });
        });

        AlueIntegOffice.Utils.dispOnLoadingIcon(true);

        var postData = {
            "userId" : userId,
            "accountDatas" : accountDatas
        };

        $.ajax({
            type : 'POST',
            url  : this.URL_REGIST_COUNSELOR,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);

            if(!response){
                return false;
            }

            //バリデーション・エラー判定
            if(response.status !== 'OK'){
                AlueIntegOffice.AjaxUtils.validationErrorHandle(response);
                return false;
            }

            //モーダル閉じる
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $('#counselorModal').modal('hide');

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);
            //AlueIntegOffice.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },

    //チャットワーク投稿ボタン
    chatworkPost : function(userId){
        var memberInfo = AlueIntegOffice.MemberUtils.getMemberInfo(userId);
        var memberName = memberInfo.lastName + ' ' + memberInfo.firstName
                       + ' (' + memberInfo.firstNameEn + ' ' + memberInfo.lastNameEn + ')';
        var title =  memberName + ' さんへChatWork投稿';

        var modalBody = $('#chatworkPostModal').find('div.modal-body');
        $(modalBody).find('span[name="title"]').html(title);
        $(modalBody).find('input[name="userId"]').val(userId);
        $(modalBody).find('div[name="memberName"]').html(memberName);
        $(modalBody).find('textarea').val('');

        $('#chatworkPostModal').modal();
    },


    /**
     * @deprecated
     * チャットワーク投稿実行
     * @returns {Boolean}
     */
    /*****
    doPostChatworkMessage : function() {
        var modalBody = $('#chatworkPostModal').find('div.modal-body');
        var userId = $(modalBody).find('input[name="userId"]').val();
        var postText = $(modalBody).find('textarea').val();

        if(postText.length < 1){
            return false;
        }

        var postData = {
            "userId"    : userId,
            "postText"  : postText
        };

        AlueIntegOffice.Utils.dispOnLoadingIcon(true);

        $.ajax({
            type : 'POST',
            url  : this.URL_POST_CHATWORK_MESSAGE,
            dataType : 'JSON',
            data : postData
        }).done(function (response)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);

            if(!response){
                return false;
            }

            //バリデーション・エラー判定
            if(response.status !== 'OK'){
                AlueIntegOffice.AjaxUtils.validationErrorHandle(response);
                return false;
            }

            //モーダル閉じる
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $('#chatworkPostModal').modal('hide');

        }).fail(function (XMLHttpRequest, textStatus, errorThrown)
        {
            AlueIntegOffice.Utils.dispOnLoadingIcon(false);
            //AlueIntegOffice.AjaxUtils.errorHandle(XMLHttpRequest, textStatus, errorThrown);
        });
    },
    *****/

    getMemberInfo : function(userId) {
        var targetMemberInfo = null;
        $(AlueIntegOffice.MemberUtils.memberListData).each(function(idx, obj){
            if(obj.userId == userId){
                targetMemberInfo = obj;
                return false;
            }
        });
        return targetMemberInfo;
    },

    //イベント登録
    registEventHandler : function(){
        //検索条件：クリアボタン
        $('button.btn-clear').on('click', function(){
            AlueIntegOffice.MemberUtils.resetSearchCondition();
        });
        //検索条件：検索ボタン
        $('button.btn-search').on('click', function(){
            AlueIntegOffice.MemberUtils.searchPageNo = 1;
            AlueIntegOffice.MemberUtils.searchCondition = '';
            AlueIntegOffice.MemberUtils.doSearch(false);
        });
        $(document).keypress( function ( e ) {
            if( e.which === 13 ){
                if( ($('#chatworkPostModal').data('bs.modal') || {})._isShown ){
                    return true;
                }
                AlueIntegOffice.MemberUtils.doSearch();
                return false;
            }
        });

        //CW投稿ボタン
        $('button[name="cwregist"]').on('click', function(){
            AlueIntegOffice.MemberUtils.doPostChatworkMessage();
            return false;
        });
    },

    //初期化
    init : function(){
        //ログインアカウント種別保持
        AlueIntegOffice.MemberUtils.accountKindId = $('input[name="loginUserAccountType"]').val();
        //イベント登録
        this.registEventHandler();
    }
};

$(document).ready( function(){
    //初期化
    AlueIntegOffice.MemberUtils.init();

    //「一覧へ戻る」対応
    var backToList = $('input[name="backToList"]').val();
    if(backToList){
        //前回検索条件を復元
        var searchCond = $('input[name="searchCondition"]').val();
        var searchCondObj = JSON.parse(searchCond);
        var pageNo = searchCondObj.pageNo;
        if(pageNo && pageNo > 0){
            AlueIntegOffice.MemberUtils.searchPageNo = pageNo;
        }else{
            AlueIntegOffice.MemberUtils.searchPageNo = 1;
        }
        AlueIntegOffice.MemberUtils.searchCondition = searchCondObj;
        //前回と同じ検索
        AlueIntegOffice.MemberUtils.doSearch(true);
    }else{
        //初回検索
        AlueIntegOffice.MemberUtils.searchPageNo = 1;
        AlueIntegOffice.MemberUtils.doSearch(false);
    }

});
