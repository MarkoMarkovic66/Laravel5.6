<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});


//ログインしていない状態
Route::group(['middleware' => 'guest:api'], function () {
    //apiログイン(with JWT)
    Route::post('/login', 'Auth\LoginController@apiAuthenticate');
    Route::post('/checkEmail', 'Auth\LoginController@checkEmail');
    Route::post('/remindPassword', 'Auth\ForgotPasswordController@remindPassword');
    Route::post('/validateTokenResetPassword', 'Auth\ForgotPasswordController@validateTokenResetPassword');
    Route::post('/resetPassword', 'Auth\ResetPasswordController@resetPassword');
});

//ログインしている状態
//Route::group(['middleware' => 'auth:api'], function () {
Route::group(['middleware' => ['jwt.auth', 'alue.checkExpired']], function () {
    //ログアウト
    //Route::get('/logout', 'Auth\LoginController@apiLogout');

    //ユーザー情報の取得
    Route::get('/me', 'ApiController@me');

    //各種機能のdispatcher
    Route::post('/exec', 'ApiController@exec');

});

