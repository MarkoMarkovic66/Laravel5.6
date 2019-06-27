<?php
namespace App\Services\Lesson;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Http\Controllers\ApiController;

use App\Dtos\ApiRequestDto;
use App\Dtos\ApiResponseDto;
use App\Dtos\ApiErrorDto;
use App\Dtos\UserLessonLogDto;
use App\Dtos\WhiteboardDto;


use App\Enums\ApiMethodNameType;
use App\Enums\ApiResultStatusType;
use App\Enums\ApiErrorCodeType;
use App\Enums\ApiLessonLang;
use App\Enums\IsDeletedType;
use App\Enums\LessonType;
use App\Enums\TimeReserve;

use App\Utils\CommonUtils;
use App\Services\Lesson\LessonService;
use Lang;

use App\Models\Reserve;
use App\Models\Student;
use App\Models\TeacherSchedule;
use App\Models\CounselingTicket;
use App\Models\AssessmentTicket;

use Validator;

/**
 * LessonService class
 */
class CounselingService
{
    /**
     * __construct
     */
    public function __construct()
    {

    }

    /**
     * RegistCounselingReserve function
     *
     * @param [type] $params 
     * 
     * @return void
     */
    public static function registCounselingReserve($params)
    {
        //get user loging
        $user = Auth::user();

        //valider parrams
        $validate = Validator::make(
            $params,
            [
                'rsv_date'=> 'date_format:"Y-m-d H:i"|required',
            ],
            CommonUtils::getCommon()
        );

        //check tiket counseling
        $list_ticket_un_use = LessonService::checkTicketUnUse($user, 'counseling', $params['rsv_date']);
        if (!is_array($list_ticket_un_use) || count($list_ticket_un_use) == 0) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                [
                    'MSG' => Lang::get('custom.ticket_used')
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        $is_book = LessonService::checkTimeBook($params['rsv_date'], $user, 'counseling');
        if ($is_book) {
            //get teacher to book counseling
            $list_teacher = TeacherSchedule::select(
                'teacher_id',
                'telephone',
                'lastassign_at',
                'level'
            )->selectRaw('CASE WHEN lastassign_at IS NULL THEN 0 ELSE 1 END AS exp')->leftJoin('teacher', 'teacher.id', '=', 'teacher_schedule.teacher_id')->where(
                [
                    ['skill_japanese', '=', 0],
                    ['counseler', '<', 2],
                    ['teacher.assessmentonly_flag', '<>', 1],
                    ['teacher_schedule.deleted', '=', IsDeletedType::ACTIVE],
                    ['teacher.deleted', '=', IsDeletedType::ACTIVE],
                ]
            )->groupBy('teacher_id', 'telephone', 'lastassign_at', 'level')->get()->toArray();
            
            if (count($list_teacher) == 0) {
                $result = new ApiResponseDto();
                $apiError = new ApiErrorDto();
            
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                    [
                        'MSG' => Lang::get('custom.not_teacher')
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }

            //check schedule of teacher
            foreach ($list_teacher as $key => $teacher) {
                $num_reserve = LessonService::checkScheduleOfTeacher('reserve', $teacher['teacher_id'], $params['rsv_date']);
                $num_tmp_reserve = LessonService::checkScheduleOfTeacher('tmp_reserve', $teacher['teacher_id'], $params['rsv_date']);
                $num_teacher_task = LessonService::checkScheduleOfTeacher('teacher_task', $teacher['teacher_id'], $params['rsv_date']);
                
                if ($num_reserve || $num_teacher_task || $num_tmp_reserve) {
                    unset($list_teacher[$key]);
                }
            }

            if (count($list_teacher) == 0) {
                $result = new ApiResponseDto();
                $apiError = new ApiErrorDto();
            
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                    [
                        'MSG' => Lang::get('custom.teacher_busy')
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }

            //get teacher to regist counseling
            $teacher_regist = reset($list_teacher);

            //get ticket to regist counseling
            $ticket_regist = reset($list_ticket_un_use);

            //get student regist counseling
            $arugo_user = Student::where('id', $user->arugo_user_id)->first();

            if (!$arugo_user) {
                $result = new ApiResponseDto();
                $apiError = new ApiErrorDto();
               
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                    [
                        'MSG' => Lang::get('custom.not_student')
                    ],
                    CommonUtils::getCommon()
                );
                
                return $result;
            }


            //setup data insert
            $insert_data = [
                'student_id' => $user->arugo_user_id,
                'item_id' => $arugo_user->item_id,
                'phone_number' => $arugo_user->phone_number,
                'teacher_id' => $teacher_regist['teacher_id'],
                'start_at' => $params['rsv_date'],
                'end_at' => date('Y-m-d H:i:s', strtotime($params['rsv_date'] . '+' . TimeReserve::TIME . ' minutes')),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'reserve_sec' => TimeReserve::TIME * 60,
                'reserve_type' => $arugo_user->reserve_type,
                'curriculum' => ApiLessonLang::LANG,
                'lang' => ApiLessonLang::LANG,
                'lesson_type' => LessonType::COUNSELING,
                'counseling_ticket_id' => $ticket_regist,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $success = Reserve::insertGetId(
                $insert_data
            );

            $lesson_name = LessonService::_getLessonTypeName(LessonType::COUNSELING);

            $lesson = LessonService::_makeAryOutputGetListLesson(
                $success,
                $insert_data['start_at'],
                $insert_data['start_at'],
                $insert_data['end_at'],
                null,
                null,
                $lesson_name,
                $insert_data['lesson_type'],
                null
            );

            if ($success) {
                $result = CommonUtils::apiResultOK(
                    [
                        'Lesson' => $lesson,
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }
        } else {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                [
                    'MSG' => Lang::get('custom.not_regist')
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }
    }

    
    /**
     *  DeleteCounselingReserve function
     *
     * @param [type] $params 
     * 
     * @return void
     */
    public static function deleteCounselingReserve($params)
    {
        //get user logined
        $user = Auth::user();

        //valider parrams
        $validate = Validator::make(
            $params,
            [
                'reserve_id' => 'required|numeric',
            ]
        );
        if ($validate->fails()) {

            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                $validate->messages()->toArray(),
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        $reserve = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['id', '=', $params['reserve_id']],
            ]
        )->first();
        
        if (!$reserve) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => Lang::get('custom.not_reserve'),
                ],
                CommonUtils::getCommon()
            );
            return $result;

        }
        //check counseling deleted
        if ($reserve->deleted) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => Lang::get('custom.counseling_deleted'),
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }

        //check counseling finish
        $now = date('Y-m-d H:i:s', strtotime(now()));
        if ($now >= date('Y-m-d H:i:s', strtotime($reserve->end_at))) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                [
                    'MSG' => Lang::get('custom.counseling_finished'), 
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }

        //check time update before 10 minutes
        $time_del = date('Y-m-d H:i:s', strtotime(now() . '+' . TImeReserve::TIME_EDIT . ' minutes'));
        if ($time_del >= date('Y-m-d H:i:s', strtotime($reserve->start_at))) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                [
                    'MSG' => Lang::get('custom.counseling_delete_time')
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }
        $reserve->deleted = IsDeletedType::DELETED;
        $reserve->updated_at = date('Y-m-d H:i:s');
        $reserve->deleted_at = $now;
        $reserve->counseling_ticket_id = null;
        $success = $reserve->save();

        if ($success) {
            $result = CommonUtils::apiResultOK(
                null,
                CommonUtils::getCommon()
            );
            return $result;
        }
    }

    /**
     *  UpdateCounselingReserve function
     *
     * @param array $params 
     * 
     * @return void
     */
    public static function updateCounselingReserve($params = array())
    {
        //get user logined
        $user = Auth::user();

        //valider parrams
        $validate = Validator::make(
            $params,
            [
                'reserve_id' => 'required|numeric',
                'rsv_date'   => 'date_format:"Y-m-d H:i"|required',
            ]
        );
        if ($validate->fails()) {

            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                $validate->messages()->toArray(),
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        $reserve = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['id', '=', $params['reserve_id']],
                ['lesson_type', '=', LessonType::COUNSELING],
            ]
        )->first();
        
        if (!$reserve) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG'  => Lang::get('custom.not_reserve'),

                ],
                CommonUtils::getCommon()
            );
            return $result;

        }

        $reserve_ticket = (int)$reserve->counseling_ticket_id;
        $reserve->counseling_ticket_id = null;
        $reserve->save();

        //get list ticket un use
        $list_ticket_un_use = LessonService::checkTicketUnUse($user, 'counseling', $params['rsv_date']);

        //check rsv_date book
        $is_book = LessonService::checkTimeBook($params['rsv_date'], $user, 'counseling');
        if ($is_book) {
            //get teacher to book counseling
            $list_teacher = TeacherSchedule::select(
                'teacher_id',
                'telephone',
                'lastassign_at',
                'level'
            )->selectRaw('CASE WHEN lastassign_at IS NULL THEN 0 ELSE 1 END AS exp')->leftJoin('teacher', 'teacher.id', '=', 'teacher_schedule.teacher_id')->where(
                [
                    ['skill_japanese', '=', 0],
                    ['counseler', '<', 2],
                    ['teacher.assessmentonly_flag', '<>', 1],
                    ['teacher_schedule.deleted', '=', IsDeletedType::ACTIVE],
                    ['teacher.deleted', '=', IsDeletedType::ACTIVE],
                ]
            )->groupBy('teacher_id', 'telephone', 'lastassign_at', 'level')->get()->toArray();
            if (count($list_teacher) == 0) {
                $result = new ApiResponseDto();
                $apiError = new ApiErrorDto();
            
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                    [
                        'MSG' => Lang::get('custom.not_teacher')
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }

            //check schedule of teacher
            foreach ($list_teacher as $key => $teacher) {
                $num_reserve = LessonService::checkScheduleOfTeacher('reserve', $teacher['teacher_id'], $params['rsv_date']);
                $num_tmp_reserve = LessonService::checkScheduleOfTeacher('tmp_reserve', $teacher['teacher_id'], $params['rsv_date']);
                $num_teacher_task = LessonService::checkScheduleOfTeacher('teacher_task', $teacher['teacher_id'], $params['rsv_date']);
                
                if ($num_reserve || $num_teacher_task || $num_tmp_reserve) {
                    unset($list_teacher[$key]);
                }
            }

            if (count($list_teacher) == 0) {
                $result = new ApiResponseDto();
                $apiError = new ApiErrorDto();
            
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                    [
                        'MSG' => Lang::get('custom.teacher_busy')
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }

            //get teacher to regist counseling
            foreach ($list_teacher as $teacher) {
                if ($teacher['teacher_id'] == $reserve->teacher_id) {
                    $teacher_regist = $teacher;
                } 
            }
            if (isset($teacher_regist)) {
                $teacher_regist = reset($list_teacher);
            }

            //check time update before 1 hour
            $time_update = date('Y-m-d H:i:s', strtotime(now() . '+' . TImeReserve::TIME_EDIT . ' minutes'));
            if ($time_update >= date('Y-m-d H:i:s', strtotime($reserve->start_at))) {
                $result = new ApiResponseDto();
                $apiError = new ApiErrorDto();
                
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                    [
                        'MSG' => Lang::get('custom.counseling_update_time')
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }
            $reserve->start_at = $params['rsv_date'];
            $reserve->end_at   = date('Y-m-d H:i:s', strtotime($params['rsv_date'] . '+' . TimeReserve::TIME . ' minutes'));
            $reserve->teacher_id = $teacher_regist['teacher_id'];
            $reserve->updated_at = date('Y-m-d H:i:s');
            if (in_array($reserve_ticket, $list_ticket_un_use)) {
                $reserve->counseling_ticket_id = $reserve_ticket;
            } else {
                $reserve->counseling_ticket_id = reset($list_ticket_un_use);
            }
            $success = $reserve->save();
            if ($success) {
                $result = CommonUtils::apiResultOK(
                    [
                        'Lesson' => $reserve,
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }
        } else {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                [
                    'MSG' => Lang::get('custom.not_edit_counseling')
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }
    }
}