<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use JWTAuth;

use App\Dtos\ApiRequestDto;
use App\Dtos\ApiResponseDto;
use App\Dtos\ApiErrorDto;

use App\Enums\ApiMethodNameType;
use App\Enums\ApiResultStatusType;
use App\Enums\ApiErrorCodeType;

use App\Services\Auth\UserAccountService;
use App\Services\TaskService;
use App\Services\S3Service;
use App\Services\Lesson\LessonService;
use App\Services\Lesson\CounselingService;
use App\Services\Homework\HomeworkService;
use App\Services\Common\CommonService;
use App\Services\Favorite\FavoriteService;
use App\Services\Data\DataService;
//use App\Services\subsys\ReviewService;
use App\Utils\CommonUtils;
use App\Test;

use Log;
use Lang;

class ApiController extends Controller
{

    /**
     * ログインユーザー情報を返却
     *
     * @return json
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * 各種api機能の実行
     *
     * @param Request $request
     *
     * @return json
     */
    public function exec(Request $request)
    {
        //json入力パラメータよりapi名を取得する
        $params = $request->input('params');
        $paramArray = json_decode($params, true);
        $method = '';
        if (is_array($paramArray) && array_key_exists('method', $paramArray)) {
            $method = $paramArray['method'];
        }


        //api名チェック
        if (empty($method)) {
            //エラー
            $result = new ApiResponseDto();
            $result->status = ApiResultStatusType::NG;
            $apiError = new ApiErrorDto();
            $apiError->code = ApiErrorCodeType::API_ERROR_PARAMS_CODE;
            $apiError->message = ApiErrorCodeType::API_ERROR_PARAMS_MSG;
            $apiError->details = [
                'method' => Lang::get('api.error_method')
            ];
            $result->errors = $apiError;
            return response()->json($result);
        }

        /*
         * api名によるdispatch
         * @see app/Enums/ApiMethodNameType.php
         *
         * 各api処理では処理結果を ApiResponseDto に格納する
         * @see app/Dtos/ApiResponseDto.php
         */

        /*** comment outed ***
        if ($method === ApiMethodNameType::API_METHOD_TEST) {
            //for TEST
            $result = new ApiResponseDto();
            $result->status = ApiResultStatusType::OK;
            $result->responses = [
                'test_response' => 'this is alue_integ_api test response.'
            ];
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_INFO_S3) {
            $result = S3Service::getInfoS3();
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_TASK) {
            //get Task list
            $result = HomeworkService::getTaskList();

        } elseif ($method === ApiMethodNameType::API_METHOD_GET_ANOTHER_TASK) {
            //get Task list
            $result = HomeworkService::getAnotherTask($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_TASK_DETAIL) {

        } elseif ($method === ApiMethodNameType::API_METHOD_DO_COMPLETE_TASK) {
            //do complete list task
            $result = HomeworkService::doCompleteTask($paramArray);

        } elseif ($method === ApiMethodNameType::API_METHOD_PUT_FAVORITE) {
            $result = FavoriteService::putFavorite($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_USERACCOUNT) {
            //Get User Account
            $result = UserAccountService::getUserAccount($paramArray);

        } elseif ($method === ApiMethodNameType::API_METHOD_PUT_USERACCOUNT) {
            //putGet User Account
            $result = UserAccountService::putUserAccount($paramArray);

        } elseif ($method === ApiMethodNameType::API_METHOD_LOGOUT) {
            //Logout
            JWTAuth::invalidate(JWTAuth::getToken());
            $result = CommonUtils::apiResultOK(
                [
                    'logout' => Lang::get('api.success_logout')
                ]
            );
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_ALL_TASK) {
            //get All task
            $result = TaskService::getAllTask();
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_LESSON_DETAIL) {
            //get Lesson Detail
            $result = LessonService::getLessonDetail($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_LESSON_AVAILABLE_DATE) {
            //get All Lesson Available Date
            $result = LessonService::getLessonAvailableDate();
        } elseif ($method === ApiMethodNameType::API_METHOD_UPDATE_LESSON_RESERVE) {
            //update lesson reserve
            $result = LessonService::updateLessonReserve($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_LESSON_WHITEBOARD) {
            //get lesson whiteboard
            $result = LessonService::getLessonWhiteboard($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_DELETE_LESSON_RESERVE) {
            //delete lesson reserve
            $result = LessonService::deleteLessonReserve($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_LESSON_LIST) {
            //get lesson list
            $result = LessonService::getLessonList();
        } elseif ($method === ApiMethodNameType::API_METHOD_REGIST_LESSON_RESERVE) {
            //regist lesson reserve
            $result = LessonService::registLessonReserve($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_REVIEW_TASK) {
            //get review Task
            $result = HomeworkService::getReviewTask($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_GV_TASK) {
            //get GV Task
            $result = HomeworkService::getGVTask($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_SR_TASK) {
            //get GV Task
            $result = HomeworkService::getSRTask($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_RV_TASK) {
            //get RV Task
            $result = HomeworkService::getRVTask($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_WORD_CARD_TASK) {
            //get word card Task
            $result = HomeworkService::getWordCardTask($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_LISTENING_TASK) {
            //get listening Task
            $result = HomeworkService::getListeningTask($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_REGIST_COUNSELING_RESERVE) {
            //regist counseling reserve
            $result = CounselingService::registCounselingReserve($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_DELETE_COUNSELING_RESERVE) {
            //delete counseling reserve
            $result = CounselingService::deleteCounselingReserve($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_UPDATE_COUNSELING_RESERVE) {
            //update counseling reserve
            $result = CounselingService::updateCounselingReserve($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_UPDATE_POINT) {
            //update point
            $result = CommonService::updatePoint($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_USER_INFO) {
            //get list user info
            $result = CommonService::getUserInfo($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_PUT_INQUIRY) {
            //put putInquiry
            $result = DataService::putInquiry($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_COUNSELING_HISTORY) {
            //get list user info
            $result = DataService::getCounselingHistory();
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_TERM_OF_SERVICE) {
            //get term of service
            $result = DataService::getTermsOfService();
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_PRIVACY_POLICY) {
            //get term of service
            $result = DataService::getPrivacyPolicy();
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_HELP) {
            //get help
            $result = DataService::getHelp();
        } elseif ($method === ApiMethodNameType::API_METHOD_PUT_MEMBER_IMAGE) {
            $result = DataService::putMemberImage($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_ACHIEVEMENT_DATA) {
            //get Achievement Data
            $result = DataService::getAchievementData();
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_MEMBER_DETAIL) {
            //get Member Detail
            $result = DataService::getMemberDetail();
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_FAVORITE) {
            //get Member Detail
            $result = FavoriteService::getFavorite();
        } elseif ($method === ApiMethodNameType::API_METHOD_UPDATE_FAVORITE) {
            //get Member Detail
            $result = FavoriteService::updateFavorite($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_DELETE_FAVORITE) {
            //get Member Detail
            $result = FavoriteService::deleteFavorite($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_REVIEW_BEFORE_LESSON) {
            //get Member Detail
            $result = ReviewService::reviewBeforeLesson($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_REVIEW_COUNSELING) {
            //get Member Detail
            $result = ReviewService::reviewCounseling($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_REVIEW_LESSON) {
            //get Member Detail
            $result = ReviewService::reviewLesson($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_REVIEW_SERVICE) {
            //get Member Detail
            $result = ReviewService::reviewLesson($paramArray);
        } elseif ($method === ApiMethodNameType::API_METHOD_GET_COUNSELING_DETAIL) {
            //get Member Detail
            $result = LessonService::getCounseling($paramArray);
        } else {
            //エラー
            $result = new ApiResponseDto();
            $result->status = ApiResultStatusType::NG;

            $apiError = new ApiErrorDto();
            $apiError->code = ApiErrorCodeType::API_ERROR_PARAMS_CODE;
            $apiError->message = ApiErrorCodeType::API_ERROR_PARAMS_MSG;
            $apiError->details = [
                'method' => $method
            ];

            $result->errors = $apiError;
        }
        *** comment outed ***/

        return response()->json($result);
    }

    /**
     * Make data test
     *
     * @param Request $request
     *
     * @return json
     */
    /*
    public function makeWhiteboard() {
        \DB::beginTransaction();
        try {
            LessonService::makeDataTest();
        } catch (Exception $e) {
            \DB::rollBack();
        }
        \DB::commit();
        echo "Done";
    }
    */

}
