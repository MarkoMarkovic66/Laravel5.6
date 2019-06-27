<?php

//ログインしている状態
Route::group(['middleware' => ['auth', 'before', 'after']], function () {
    //ログアウト
    Route::group(['namespace' => 'Admin\Auth'], function() {
      Route::get('/logout', 'LoginController@logout')->name('logout');
    });

    //管理者ダッシュボード
    Route::group(['namespace' => 'Admin\Top'], function() {
        Route::get('/top', 'TopController@index')->name('top');
    });

    //宿題管理
    Route::group(['namespace' => 'Admin\Task'], function() {
        //宿題管理Top
        Route::get('/task/noassign', 'TaskController@indexWithNoAssign');
        Route::get('/task/{backToList?}', 'TaskController@index')->name('admintask');

        //宿題管理Ajax
        Route::post('/api/task_search', 'AjaxController@taskSearch');
        Route::post('/api/assign_operator', 'AjaxController@assignOperator');
        Route::post('/api/get_task_answer_fb', 'AjaxController@getTaskAnswerFb');
        Route::post('/api/regist_feedback', 'AjaxController@registFeedback');
        Route::post('/api/approve_feedback', 'AjaxController@approveFeedback');
        Route::post('/api/reject_feedback', 'AjaxController@rejectFeedback');
    });

    //会員管理
    Route::group(['namespace' => 'Admin\Member'], function() {
        //会員管理:Top
        Route::get('/member/{backToList?}', 'MemberController@index')->name('member');
        //会員一覧
        Route::post('/api/member_search', 'AjaxController@memberSearch');
        //会員一覧：カウンセラー情報
        Route::post('/api/get_counselor_info', 'AjaxController@modalGetCounselorInfo');
        //会員一覧：カウンセラー情報
        Route::post('/api/regist_counselor', 'AjaxController@modalRegistCounselor');

        //会員詳細:Top
        Route::post('/member_detail', 'MemberController@memberDetail');
        //会員詳細:基本情報更新
        Route::post('/api/member_basicinfo_update', 'AjaxController@memberBasicinfoUpdate');

        //会員詳細:学習方針新規登録
        Route::post('/api/member_learningpolicy_regist', 'AjaxController@memberLearningpolicyRegist');
        //会員詳細:データの動的取得
        Route::get('/api/get_member_detail_part_data', 'AjaxController@getMemberDetailPartData');

        //会員詳細:会員レポート
        Route::post('/member_report', 'MemberController@memberReport');
        Route::get('/member_report_pdf/{userId}/{issue?}', 'MemberController@memberReportPdf');

        //会員詳細:出題カレンダー
        Route::get('/task_calendar/{userId}', 'MemberController@taskCalendar');

        //会員詳細:出題カレンダー:Grammar/Vocabulary 予約問題一覧取得
        Route::get('/api/get_task_assign_list', 'AjaxController@getTaskAssignList');
        //会員詳細:出題カレンダー:タスク種別変更
        Route::post('/api/change_task_type', 'AjaxController@changeTaskType');


        //会員詳細:出題カレンダー:GVモーダル：タスク変更
        Route::post('/api/modal_change_task', 'AjaxController@modalChangeTask');
        //会員詳細:出題カレンダー:GVモーダル：マスタタスク検索
        Route::post('/api/modal_search_task_master', 'AjaxController@modalSearchTaskMaster');
        //会員詳細:出題カレンダー:GVモーダル：マスタから切り替える
        Route::post('/api/modal_change_task_master', 'AjaxController@modalChangeTaskMaster');

        //会員詳細:OUTPUT履歴
        Route::get('/member_output/{arugoUserId}', 'MemberController@memberOutput');

        //会員詳細:学習方針履歴
        Route::get('/api/get_lp_category', 'AjaxController@getLpCategory');

    });

    //レビュー管理
    Route::group(['namespace' => 'Admin\Review'], function() {
        Route::get('/review/lessons/{offset?}', 'ReviewController@lessons')->name('review.lessons');
        Route::post('/review/lessons/{offset?}', 'ReviewController@lessons')->name('review.lessons');
        Route::get('/review/counselors/{offset?}', 'ReviewController@counselors')->name('review.counselors');
        Route::post('/review/counselors/{offset?}', 'ReviewController@counselors')->name('review.counselors');
        Route::get('/review/services/{offset?}', 'ReviewController@services')->name('review.services');
        Route::post('/review/services/{offset?}', 'ReviewController@services')->name('review.services');
    });

    //プラン管理
    Route::group(['namespace' => 'Admin\Plan'], function() {
        Route::get('/plan/{offset?}', 'PlanController@index')->name('plan.index');
        Route::post('/plan/{offset?}', 'PlanController@index')->name('plan.index');
    });

    //マスター管理
    Route::group(['namespace' => 'Admin\Master'], function() {
        //マスタ管理
        Route::get('/master/{master_name?}/{offset?}', 'MasterController@index')->name('master');
        Route::post('/master/{master_name?}/{offset?}', 'MasterController@index')->name('master');

        //データ取得API
        Route::post('/api/master/getdata', 'AjaxController@getdata')->name('masterajax');
    });

    //アカウント管理
    Route::group(['namespace' => 'Admin\Account'], function() {
        //アカウント管理Top
        Route::get('/account/{offset?}', 'AccountController@index')->name('account');
        Route::post('/account/{offset?}', 'AccountController@index')->name('account');

        //データ取得API
        Route::post('/api/account/getdata', 'AjaxController@getdata')->name('accountajax');
    });


});


//Webログイン
Route::group(['namespace' => 'Admin\Auth'], function() {
    Route::get('/login', 'LoginController@showLoginForm')->name('login');
    Route::post('/login', 'LoginController@login');
});


//ログイン後のhome
Route::get('/home', function () {
  return redirect('/task');
});


Route::get('/', function () {
  return redirect('login');
});

/****/
//デバッグ用
Route::group(['namespace' => 'Test'], function() {
    Route::get('/test', 'AppController@index')->name('/test');
    //Route::get('/batchTest', 'AppController@batchTest')->name('/batchTest');
    Route::get('/pdftest', 'PdfController@getPrint')->name('/pdftest');
});
/***/



//バッチ・テスト用
/**
Route::get('/batchTest/{runType?}', '\App\Console\Commands\AlueCommand\ImportDataCommand@handle');
//Route::get('/cwPullTest/{runType?}', '\App\Console\Commands\AlueCommand\PullCwDataCommand@handle');
//Route::get('/cwPushTest/{runType?}', '\App\Console\Commands\AlueCommand\PushCwDataCommand@handle');
//Route::get('/cwCreateTaskTest/{runType?}', '\App\Console\Commands\AlueCommand\CreateTaskCommand@handle');

Route::get('/createQuestionTest/{runType?}', '\App\Console\Commands\AlueCommand\CreateQuestionCommand@handle');
Route::get('/resetQuestionTest/{runType?}', '\App\Console\Commands\AlueCommand\ResetQuestionCommand@handle');

Route::get('/createMemberReport/{runType?}', '\App\Console\Commands\AlueCommand\CreateMemberReportCommand@handle');
//Route::get('/uploadMemberReport/{runType?}', '\App\Console\Commands\AlueCommand\UploadMemberReportCommand@handle');
**/
