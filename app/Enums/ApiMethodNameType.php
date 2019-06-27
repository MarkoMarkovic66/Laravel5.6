<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * ApiMethodNameType enum
 */
class ApiMethodNameType extends BaseEnum
{

    const API_METHOD_TEST                       = 'test';
    const API_METHOD_GET_INFO_S3                = 'getInfoS3';
    
    //favorites
    const API_METHOD_PUT_FAVORITE               = 'putFavorite';
    const API_METHOD_GET_FAVORITE               = 'getFavorite';
    const API_METHOD_UPDATE_FAVORITE            = 'updateFavorite';
    const API_METHOD_DELETE_FAVORITE            = 'deleteFavorite';

    const API_METHOD_GET_USERACCOUNT            = 'getUserAccount';
    const API_METHOD_PUT_USERACCOUNT            = 'putUserAccount';
    const API_METHOD_LOGOUT                     = 'logout';
    const API_METHOD_GET_ALL_TASK               = 'getAllTask';

    //lesson
    const API_METHOD_GET_LESSON_LIST            = 'getLessonList';
    const API_METHOD_GET_LESSON_DETAIL          = 'getLessonDetail';
    const API_METHOD_GET_LESSON_AVAILABLE_DATE  = 'getLessonAvailableDate';
    const API_METHOD_UPDATE_LESSON_RESERVE      = 'updateLessonReserve';
    const API_METHOD_REGIST_LESSON_RESERVE      = 'registLessonReserve';
    const API_METHOD_GET_LESSON_WHITEBOARD      = 'getLessonWhiteboard';
    const API_METHOD_DELETE_LESSON_RESERVE      = 'deleteLessonReserve';

    //home works
    const API_METHOD_GET_TASK                   = 'getTaskList';
    const API_METHOD_GET_ANOTHER_TASK           = 'getAnotherTask';
    const API_METHOD_GET_TASK_DETAIL            = 'getTaskDetail';
    const API_METHOD_DO_COMPLETE_TASK           = 'doCompleteTask';
    const API_METHOD_GET_REVIEW_TASK            = 'getReviewTask';
    const API_METHOD_GET_GV_TASK                = 'getGVTask';
    const API_METHOD_GET_SR_TASK                = 'getSRTask';
    const API_METHOD_GET_RV_TASK                = 'getRVTask';
    const API_METHOD_GET_WORD_CARD_TASK         = 'getWordCardTask';
    const API_METHOD_GET_LISTENING_TASK         = 'getListeningTask';

    //counseling
    const API_METHOD_GET_COUNSELING_DETAIL      = 'getCounseling';
    const API_METHOD_REGIST_COUNSELING_RESERVE  = 'registCounselingReserve';
    const API_METHOD_DELETE_COUNSELING_RESERVE  = 'deleteCounselingReserve';
    const API_METHOD_UPDATE_COUNSELING_RESERVE  = 'updateCounselingReserve';

    //common
    const API_METHOD_UPDATE_POINT               = 'updatePoint';
    const API_METHOD_USER_INFO                  = 'getUserInfo';

    //data
    const API_METHOD_GET_COUNSELING_HISTORY     = 'getCounselingHistory';
    const API_METHOD_GET_TERM_OF_SERVICE        = 'getTermsOfService';
    const API_METHOD_GET_PRIVACY_POLICY         = 'getPrivacyPolicy';
    const API_METHOD_GET_HELP                   = 'getHelp';
    const API_METHOD_GET_ACHIEVEMENT_DATA       = 'getAchievementData';
    const API_METHOD_GET_MEMBER_DETAIL          = 'getMemberDetail';
    const API_METHOD_PUT_INQUIRY                = 'putInquiry';
    const API_METHOD_PUT_MEMBER_IMAGE           = 'putMemberImage';

    //review
    const API_METHOD_REVIEW_BEFORE_LESSON       = 'reviewBeforeLesson';
    const API_METHOD_REVIEW_COUNSELING          = 'reviewCounseling';
    const API_METHOD_REVIEW_LESSON              = 'reviewLesson';
    const API_METHOD_REVIEW_SERVICE             = 'reviewService';



    // please add more...
    // const API_METHOD_******             = '*****';
    // const API_METHOD_******             = '*****';


}
