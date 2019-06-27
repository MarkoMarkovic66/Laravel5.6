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
use App\Enums\CategoryType;
use App\Enums\IsReviewType;
use App\Enums\TimeReserve;
use App\Enums\TaskType;

use App\Utils\CommonUtils;
use Lang;
use App\Models\Lesson;
use App\Models\LessonTicket;
use App\Models\Reserve;
use App\Models\TmpReserve;
use App\Models\Plan;
use App\Models\Student;
use App\Models\Whiteboard;
use App\Models\WhiteboardLog;
use App\Models\TeacherSchedule;
use App\Models\Teacher;
use App\Models\TeacherTask;
use App\Models\CounselingTicket;
use App\Models\AssessmentTicket;

use App\Services\Homework\HomeworkService;

use Validator;

use DB;

/**
 * LessonService class
 */
class LessonService
{
    /**
     * __construct
     */
    public function __construct()
    {

    }

    /**
     * GetLessonAvailableDate function
     *
     * @return void
     */
    public static function getLessonAvailableDate()
    {
        //get user login
        $user = Auth::user();
        
        //user end
        $end_at = $user->closed_at;

        //get data in file
        $path = env('LESSON_AVAILABILITY');
        $file_content = file_get_contents($path);

        //validate data is json
        if (!is_string($file_content)|| !is_array(json_decode($file_content, true))) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => Lang::get('custom.content_not_json')
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }
        $data = json_decode($file_content, true);

        //setup data responce
        $availableDate = [];
        foreach ($data as $lang => $data_lang) {
            foreach ($data_lang as $date => $time) {
                $date = date("Y-m-d", strtotime($date));
                if (strtotime($end_at) < strtotime($date)) {
                    continue;
                }
            
                $day = [];
                $num_date = date('N', strtotime($date));

                switch ($num_date) {
                case 1:
                    $date_text = '月';
                    break;
                case 2:
                    $date_text = '火';
                    break;
                case 3:
                    $date_text = '水';
                    break;
                case 4:
                    $date_text = '木';
                    break;
                case 5:
                    $date_text = '金';
                    break;
                case 6:
                    $date_text = '土';
                    break;
                case 7:
                    $date_text = '日';
                    break;
                }

                $day['label'] = $date . ' (' . $date_text . ')';
                $day['value'] = $date;
                foreach ($time as $hour => $minute) {
                    $hour = substr($hour, 0, 2);
                    foreach ($minute as $min => $num) {
                        //none available
                        if ($num == 0) {
                            continue;
                        }

                        $minute = substr($min, 0, 2);

                        $time = [];
                        $time['hour'] = $hour.':'.$minute;
                        $time['number'] = $num;

                        $day['time'][] = $time;
                    }
                }
                $availableDate[$lang]['date'][] = $day;
            }
        }
            

        //setup result
        $result = CommonUtils::apiResultOK(
            $availableDate,
            CommonUtils::getCommon()
        );
        return $result;
    }


    /**
     * GetLessonDetail function
     *
     * @param mixed $params 
     * 
     * @return void
     */
    public static function getLessonDetail($params = array())
    {
        //get user logined
        $user = Auth::user();

        //validate lesson id
        $validate = Validator::make(
            $params,
            [
                'lesson_id'    => 'required|numeric',
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
        
        //get Lesson
        $lesson = Lesson::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['id', '=', $params['lesson_id']],
            ]
        )
        ->select(self::getSelectLessionRaw())->first();
        
        if ($lesson == null) {
            $lesson = Reserve::where(
                [
                    ['student_id', '=', $user->arugo_user_id],
                    ['id', '=', $params['lesson_id']],
                ]
            )->select(self::getSelectReserveRaw())->first();
        }

        if (!$lesson) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => Lang::get('custom.not_lesson')
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        //setup field
        $list_field = [
            'id',
            'start_at',
            'end_at',
            'report',
            'lesson_id',
            'today_feedback',
            'share_coach',
            'approved_at',
            'lesson_type',
            'canceled',
            'canceled_sec',
            'request_sec',
            //'is_deleted',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
        //setup data responce
        $lesson_log = new UserLessonLogDto();

        $lesson_log->user_id = $user->id;
        $lesson_log->alugo_teacher_id = $lesson->teacher_id;
        $lesson_log->alugo_user_id = $user->alugo_user_id;
        $lesson_log->dayOfWeek = date('D', strtotime($lesson->stat_at));
        $lesson_log->is_deleted = $lesson->deleted;
        
        foreach ($list_field as $field) {
            $lesson_log->{$field} = $lesson->{$field};
        }

        //get teacher arugo
        $teacher = Teacher::where('id', $lesson->teacher_id)->first();
        if ($teacher) {
            $lesson_log->teacherName = $teacher->last_name . ' ' . $teacher->first_name;
        }

        //get whiteboard
        $whiteboard = self::getLessonWhiteboard(['lesson_id' => $params['lesson_id']])->responses;
        if ($whiteboard) {
            $lesson_log->whiteboard_id = $whiteboard['whiteboard']->whiteboardId;
            $lesson_log->whiteboard_board = $whiteboard['whiteboard']->board;
            $lesson_log->whiteboard_create_at = $whiteboard['whiteboard']->createdAt;
        }

        //get Lesson favorite
        $favorite_id = HomeworkService::getFavorite($user, $params['lesson_id'], 2);
        $lesson_log->favorite_id = $favorite_id;

        //Get lesson feedback (user_lesson_logs)
        $lessonFeedback = DB::table('user_lesson_logs')
                            ->where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('id', $params['lesson_id'])
                            ->first();

        if ($lessonFeedback) {
            $lesson_log->feedback = $lessonFeedback->today_feedback;
            $lesson_log->message_user = $lessonFeedback->message_user;
        }

        //get leson review
        $lessonReview = DB::table('review_lessons')
            ->where('is_deleted', IsDeletedType::ACTIVE)
            ->where('lesson_id', $params['lesson_id'])
            ->first();
        $is_review = 0;

        if ($lessonReview) {
            $is_review = IsReviewType::REVIEW;
        }
        $lesson_log->is_review = $is_review;
        //setup result
        $result = CommonUtils::apiResultOK(
            [
                'Lesson' => $lesson_log
            ],
            CommonUtils::getCommon()
        );
        return $result;

    }
    
    /**
     * RegistLessonReserve function
     *
     * @param array $params 
     * 
     * @return void
     */
    public static function registLessonReserve($params = array())
    {
        //get user logined
        $user = Auth::user();
        //valider parrams
        $validate = Validator::make(
            $params,
            [
                'rsv_date'       => 'date_format:"Y-m-d H:i"|required',
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

        //get list ticket un use
        $list_ticket_un_use = self::checkTicketUnUse($user, 'lesson', $params['rsv_date']);
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

        //check lestion_date book
        $is_book = self::checkTimeBook($params['rsv_date'], $user, 'lesson');
        if ($is_book) {

            //check number lesson 4 day before
            if (self::_checkNumLesson(-4, $user, $params['rsv_date']) > 6 || self::_checkNumLesson(4, $user, $params['rsv_date']) > 6) {
                
                $result = new ApiResponseDto();
                $apiError = new ApiErrorDto();
            
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                    [
                        'MSG' => Lang::get('custom.lesson_too_much')
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }

            //get teacher to book lesson
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
            
            if (!$list_teacher) {
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
            }

            //check schedule of teacher
            foreach ($list_teacher as $key => $teacher) {
                $num_reserve = self::checkScheduleOfTeacher('reserve', $teacher['teacher_id'], $params['rsv_date']);
                $num_tmp_reserve = self::checkScheduleOfTeacher('tmp_reserve', $teacher['teacher_id'], $params['rsv_date']);
                $num_teacher_task = self::checkScheduleOfTeacher('teacher_task', $teacher['teacher_id'], $params['rsv_date']);
                
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

            //get teacher to regist lesson
            $teacher_regist = reset($list_teacher);

            //get student regist lesson
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
                'end_at' => date('Y-m-d H:i:s', strtotime($params['rsv_date'] . '+' . TimeReserve::TIME . ' minutes')) ,
                'reserve_sec' => TimeReserve::TIME * 60,
                'reserve_type' => $arugo_user->reserve_type,
                'curriculum' => ApiLessonLang::LANG,
                'lang' => ApiLessonLang::LANG,
                'lesson_type' => LessonType::LESSON,
                'lesson_ticket_id' => reset($list_ticket_un_use),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            $success = Reserve::create(
                $insert_data
            );

            $lesson_name = self::_getLessonTypeName(LessonType::LESSON);

            $lesson = self::_makeAryOutputGetListLesson(
                $success->id,
                $insert_data['start_at'],
                $insert_data['start_at'],
                $insert_data['end_at'],
                null,
                null,
                $lesson_name,
                $insert_data['lesson_type'],
                null,
                1
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
     * UpdateLessonReserve function
     *
     * @param array $params 
     * 
     * @return void
     */
    public static function updateLessonReserve($params = array())
    {
        //get user logined
        $user = Auth::user();

        //valider parrams
        $validate = Validator::make(
            $params,
            [
                'lesson_reserve_id' => 'required|numeric',
                'lesson_date'       => 'date_format:"Y-m-d H:i"|required',
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
                ['id', '=', $params['lesson_reserve_id']],
                //['lesson_type', '=', $params['lesson_type']],
            ]
        )->first();
        
        if (!$reserve) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => Lang::get('custom.not_lesson'),
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }

        $reserve_ticket = (int)$reserve->lesson_ticket_id;
        $reserve->lesson_ticket_id = null;
        $reserve->save();

        //get list ticket un use
        $list_ticket_un_use = self::checkTicketUnUse($user, 'lesson', $params['lesson_date']);
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

        //check lestion_date book
        $is_book = self::checkTimeBook($params['lesson_date'], $user, 'lesson');
        if ($is_book) {
            //check time update before 1 hour
            $time_update = date('Y-m-d H:i:s', strtotime(now() . '+' . TImeReserve::TIME_EDIT . ' minutes'));
            if ($time_update >= date('Y-m-d H:i:s', strtotime($reserve->start_at))) {
                $result = new ApiResponseDto();
                $apiError = new ApiErrorDto();
                
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                    [
                        'MSG' => Lang::get('custom.lesson_update_time')
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }
            $reserve->start_at = $params['lesson_date'];
            $reserve->end_at = date('Y-m-d H:i:s', strtotime($params['lesson_date'] . '+' . TimeReserve::TIME . ' minutes'));
            $reserve->updated_at = date('Y-m-d H:i:s');
            if (in_array($reserve_ticket, $list_ticket_un_use)) {
                $reserve->lesson_ticket_id = $reserve_ticket;
            } else {
                $reserve->lesson_ticket_id = reset($list_ticket_un_use);
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
                    'MSG' => Lang::get('custom.not_edit_lesson')
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }
    }

    /**
     * DeleteLessonReserve function
     *
     * @param [type] $params 
     * 
     * @return void
     */
    public static function deleteLessonReserve($params)
    {
        //get user logined
        $user = Auth::user();

        //valider parrams
        $validate = Validator::make(
            $params,
            [
                'lesson_reserve_id' => 'required|numeric',
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
                ['id', '=', $params['lesson_reserve_id']],
            ]
        )->first();
        
        if (!$reserve) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => Lang::get('custom.not_lesson'),
                ],
                CommonUtils::getCommon()
            );
            return $result;

        }
        //check lesson deleted
        if ($reserve->deleted) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => Lang::get('custom.lesson_deleted'),
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }

        //check lesson finish
        $now = date('Y-m-d H:i:s', strtotime(now()));
        if ($now >= date('Y-m-d H:i:s', strtotime($reserve->end_at))) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                [
                    'MSG' => Lang::get('custom.lesson_finished'), 
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }

        //check time update before 1 hour
        $time_del = date('Y-m-d H:i:s', strtotime(now() . '+' . TImeReserve::TIME_EDIT . ' minutes'));
        if ($time_del >= date('Y-m-d H:i:s', strtotime($reserve->start_at))) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                [
                    'MSG' => Lang::get('cusstom.lesson_delete_time')
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }
        $reserve->deleted = IsDeletedType::DELETED;
        $reserve->deleted_at = $now;
        $reserve->lesson_ticket_id = null;
        $reserve->updated_at = date('Y-m-d H:i:s');
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
     * GetLessonList function
     * 
     * @return void
     */
    public static function getLessonList()
    {
        //get user logined
        $user = Auth::user();

        //Make time get data
        //get time range lesson reserve
        $start_at = $user->opened_at;
        $end_at = $user->closed_at;

        //get all lesson finished
        $lesson = null;
        $lesson = Lesson::where(
            [
                ['lesson.student_id', '=', $user->arugo_user_id], 
                ['lesson.start_at', '>=', $start_at],
                ['lesson.end_at', '<', $end_at],
                ['lesson.deleted', '=', IsDeletedType::ACTIVE],
                ['lesson.lesson_type', '=', LessonType::LESSON], 
            ]
        )->get();

        //get all lesson not finish of user
        $lesson_reverse = null;
        $lesson_reverse = Lesson::select(
            'lesson.id',
            'lesson.lesson_type as lesson_type',
            'lesson.start_at as lesson_start_at',
            'lesson.end_at as lesson_end_at',
            'reserve.start_at as reserve_start_at',
            'reserve.end_at as reserve_end_at'
        )->join('reserve', 'lesson.id', '=', 'reserve.lesson_id')->where(
            [
                ['lesson.student_id', '=', $user->arugo_user_id], 
                ['reserve.start_at', '>=', $start_at],
                ['reserve.end_at', '<', $end_at],
                ['lesson.deleted', '=', IsDeletedType::ACTIVE],
                ['lesson.lesson_type', '=', LessonType::LESSON], 
            ]
        )->where(
            function ($query) {
                $query->whereNull('lesson.start_at')
                    ->orWhereNull('lesson.end_at');
            }
        )->orderBy('reserve.start_at', 'ASC')->get();

        //get all lesson reserve noi have lesson id
        $reserve = null;
        $reserve = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id], 
                ['start_at', '>=', $start_at],
                ['end_at', '<', $end_at],
                ['deleted', '=', IsDeletedType::ACTIVE],
                ['lesson_type', '=', LessonType::LESSON],
            ]
        )->whereNull('lesson_id')->orderBy('start_at', 'ASC')->get();

        $aryLessonList = [];
        
        if ($reserve) {
            foreach ($reserve as $l) {
                $week = self::getWeekNo($l->start_at);
                $year = date('Y', strtotime($l->start_at));
                $lesson_name = self::_getLessonTypeName($l->lesson_type);

                $aryLessonList[$week][] = self::_makeAryOutputGetListLesson(
                    $l->id,
                    $l->start_at,
                    $l->start_at,
                    $l->end_at,
                    null,
                    null,
                    $lesson_name,
                    $l->lesson_type,
                    null,
                    1
                );
            }
        }
        
        if ($lesson) {
            foreach ($lesson as $l) {
                $week = self::getWeekNo($l->start_at);
                $year = date('Y', strtotime($l->start_at));
                $lesson_name = self::_getLessonTypeName($l->lesson_type);

                $favorite_id = HomeworkService::getFavorite($user, $l->id, CategoryType::LESSON);

                $aryLessonList[$week][] = self::_makeAryOutputGetListLesson(
                    $l->id,
                    $l->start_at,
                    $l->start_at,
                    $l->end_at,
                    $l->start_at,
                    $l->end_at,
                    $lesson_name,
                    $l->lesson_type,
                    $favorite_id
                );
            }
        }
        
        if ($lesson_reverse) {
            foreach ($lesson_reverse as $l) {
                $week = self::getWeekNo($l->reserve_start_at);
                $year = date('Y', strtotime($l->reserve_start_at));
                $lesson_name = self::_getLessonTypeName($l->lesson_type);

                $favorite_id = HomeworkService::getFavorite($user, $l->id, CategoryType::LESSON);

                $aryLessonList[$week][] = self::_makeAryOutputGetListLesson(
                    $l->id,
                    $l->reserve_start_at,
                    $l->reserve_start_at,
                    $l->reserve_end_at,
                    $l->lesson_start_at,
                    $l->lesson_end_at,
                    $lesson_name,
                    $l->lesson_type,
                    $favorite_id
                );
            }
        }
        
        //result
        $response = [];

        foreach ($aryLessonList as $w => &$data) {
            usort($data, array("TaskService","sortTask"));
        }

        $response['data'] = $aryLessonList;
        
        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );

        return $result;
    }

    /**
     * SortTask
     *
     * @param [type] $a 
     * @param [type] $b 
     * 
     * @return void
     */
    public static function sortTask($a, $b) 
    {
        if ($a['start_time'] == $b['start_time']) {
            return 0;
        }

        return ($a['start_time'] > $b['start_time']) ? 1 : -1;
    }

    /**
     * GetWeekNo function
     *
     * @param [type] $date
     * @return void
     */
    public static function getWeekNo($date)
    {
        $dateTarget = date('Y-m-d', strtotime($date));
        $weekTarget = \Carbon\Carbon::createFromFormat('Y-m-d', $dateTarget)->format('W');
        $weekNow = \Carbon\Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->format('W');

        $yearNow = date('Y');
        $yearTarget = date('Y', strtotime($date));

        $week = 0;
        if ($yearTarget < $yearNow) {
            $yearStart = $yearTarget;
            $yearEnd = $yearNow;
        } else {
            $yearStart = $yearNow;
            $yearEnd = $yearTarget;
        }

        for ($i = $yearStart; $i < $yearEnd; $i++) {
            if ($yearTarget < $yearNow) {
                $week -= \Carbon\Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($i . '-01-01')))->format('W');
            } else {
                $week += \Carbon\Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($i . '-01-01')))->format('W');
            }
        }

        return $week -$weekNow + $weekTarget;
    }

    /**
     * _makeAryOutputGetListLesson function
     *
     * @param [type] $lesson_id 
     * @param [type] $date 
     * @param [type] $start 
     * @param [type] $end 
     * @param [type] $start_at 
     * @param [type] $end_at 
     * @param [type] $name 
     * @param [type] $type 
     * 
     * @return void
     */
    public static function _makeAryOutputGetListLesson($lesson_id, $date, $start, $end, $start_at, $end_at, $name, $type, $favorite_id, $is_reserve = 0)
    {
        $checkIsLated = 1;

        if (strtotime($end) > time()) {
            $checkIsLated = 0;
        } elseif ($end_at) {
            $checkIsLated = 0;
        }

        return [
            'lesson_id' => $lesson_id,
            'date' => date('m.d', strtotime($date)),
            'date_time' => date('Y-m-d', strtotime($date)),
            'dayOfWeek' => date('D', strtotime($date)),
            'name' => $name,
            'start_time' => date('H:i', strtotime($start)),
            'end_time' => date('H:i', strtotime($end)),
            'is_finished' => ($start_at != null && $end_at != null) ? 1 : 0,
            'is_started' => ($start_at != null && $end_at == null) ? 1 : 0,
            'before_start' => ($start_at == null && $end_at == null) ? 1 : 0,
            'is_lated'      => $checkIsLated,
            'is_comingsoon' => (date('Y-m-d', strtotime($date)) > date('Y-m-d')) ? 1 : 0,
            'lesson_type' => $type,
            'favorite_id' => $favorite_id,
            'is_reserve' => $is_reserve,
        ];
    }

    /**
     * GetLessonWhiteboard function
     *
     * @param array $params 
     * 
     * @return void
     */
    public static function getLessonWhiteboard($params = array())
    {
        //get user logined
        $user = Auth::user();

        //validate lesson id
        $validate = Validator::make(
            $params,
            [
                'lesson_id'    => 'required|numeric',
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

        //get whiteboard
        $whiteboard = Whiteboard::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['lesson_id', '=', $params['lesson_id']],
            ]
        )->first();

        //validate whiteboard
        if (!$whiteboard) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get('custom.not_whiteboard'),
                [
                    'MSG' => Lang::get('custom.not_lesson'),
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }

        //get whiteboard log
        $whiteboard_log = WhiteboardLog::where('whiteboard_id', $whiteboard->id)->orderBy('created_at', 'DESC')->first();

        if (!$whiteboard_log) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get('custom.not_whiteboard_log'),
                [
                    'MSG' => Lang::get('custom.not_lesson'),
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }

        //setup field
        $list_field = [
            'whiteboard_id',
            'board',
            'created_at',
        ];
        //setup data responce
        $response = new WhiteboardDto();
        //Set response from ary Setting Field
        foreach ($list_field as $field) {
            //Convert snack convention to Camel convention
            $camel = CommonUtils::snackToCamel($field);

            //set field to response
            $response->{$camel} = $whiteboard_log->{$field};
        }

        $result = CommonUtils::apiResultOK(
            [
                'whiteboard' => $response
            ],
            CommonUtils::getCommon()
        );

        return $result;
    }

    /**
     * CheckTicketUnUse function
     *
     * @param [type] $user 
     * @param [type] $type 
     * 
     * @return void
     */
    public static function checkTicketUnUse($user, $type, $date)
    {
        //get user in arugo db
        $student = Student::where('id', $user->arugo_user_id)->first();
        
        if(!$student) {
            return []; 
        }

        // get list ticket not use
        $close_at = '';
        if ($type == 'lesson') {
            $model = new LessonTicket();
            $close_at = 'close_at';
            $ticket_field = 'lesson_ticket_id';
        } elseif ($type == 'counseling') {
            $model = new CounselingTicket();
            $close_at = 'counseling_close_at';
            $ticket_field = 'counseling_ticket_id';
        } else {
            $model = new AssessmentTicket();
            $close_at = 'assessment_close_at';
            $ticket_field = 'ticket_id';
        }


        $list_not_use = $model->select('id')
            ->where(
                [
                    ['student_id', '=', $user->arugo_user_id],
                    [$close_at, '>=', date('Y-m-d H:i:s', strtotime($date))],
                    [$close_at, '<=', $student->close_at],
                    ['deleted', '=', IsDeletedType::ACTIVE],
                ]
            )
            ->orderBy($close_at, "asc");
        if ($type == 'lesson') {
            $list_not_use= $list_not_use->whereNull('use_at');
        }
        $list_not_use = $list_not_use->get()->toArray();
        if (!$list_not_use) {
            return [];
        }
        foreach ($list_not_use as $item_not_use) {
            $not_use[] = $item_not_use['id'];
        }

        //get lesson tiket in reserve
        $list_reserve = Reserve::select($ticket_field)->whereIn($ticket_field, $not_use)->where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['canceled', '=', IsDeletedType::ACTIVE],
                ['deleted', '=', IsDeletedType::ACTIVE],
            ]
        )->get()->toArray();

        //get list tiket use by type
        $list_ticket = [];
        foreach ($list_reserve as $ticket) {
            if (!in_array($ticket[$ticket_field], $list_ticket)) {
                $list_ticket[] = $ticket[$ticket_field];
            }
        }        

        //get ticket not book
        $result = array_diff($not_use, $list_ticket);

        return $result;
    }
    
    /**
     * CheckTimeBook function
     *
     * @param [type] $lesson_date 
     * @param [type] $user 
     * @param [type] $type 
     * 
     * @return void
     */
    public static function checkTimeBook($lesson_date, $user, $type)
    {
        $result = 0;
        //get list date available book
        $listAvailableDate = self::getLessonAvailableDate()->responses;
        $date = date('Y-m-d', strtotime($lesson_date));
        $time = date('H:i', strtotime($lesson_date));

        //check $lesson_date in list date available 
        if ($type == 'lesson') {
            $lang = ApiLessonLang::LANG;
            foreach ($listAvailableDate[$lang]['date'] as $key => $value) {
                if ($value['value'] == $date) {
                    foreach ($value['time'] as $t) {
                        if ($t['hour'] == $time) {
                            $result = 1;
                            break;
                        }
                    }
                    
                }
            }
            if ($result == 0) {
                return $result;
            }
        }
        
        //check $lesson_date with time lesson of user
        $time_end = date('Y-m-d H:i', strtotime($lesson_date .' +' . TimeReserve::TIME . ' minutes'));
        $plan = Plan::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['lesson_start_at', '<=', $lesson_date],
                ['lesson_end_at', '>', $time_end],
                ['deleted', '=', '0'],
            ]
        )->orderBy('lesson_end_at', 'DESC')->first();
        if ($plan) {
            $result = 1;
        } else {
            return 0;
        }

        //check $lesson_date in reserve
        $reserve = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['start_at', '=', $lesson_date],
                ['deleted', '=', IsDeletedType::ACTIVE],
            ]
        )->get();

        if (count($reserve) > 0) {
            return 0;
        }

        return $result;
    }

    /**
     * _checkNumLesson function
     *
     * @param [type] $no_date 
     * @param [type] $user 
     * @param [type] $lesson_date 
     * 
     * @return void
     */
    private static function _checkNumLesson($no_date, $user, $lesson_date)
    {
        //get time check
        $txt_time_start = $no_date .' day';
        $txt_time_end = $lesson_date;
        if ($no_date > 0) {
            $txt_time_start = $lesson_date;
            $txt_time_end = $no_date .' day';
        }
        $start_at = date("Y-m-d H:i:s", strtotime($txt_time_start));
        $end_at = date("Y-m-d H:i:s", strtotime($txt_time_end . '23:59:59'));

        $numLesson = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['lesson_type', '=', LessonType::LESSON],
                ['deleted', '=', IsDeletedType::ACTIVE],
                ['canceled', '=', IsDeletedType::ACTIVE],
            ]
        )->whereNull('lesson_id')->whereBetween('start_at', array($start_at, $end_at))->count();

        return $numLesson;
    }

    /**
     * _checkScheduleOfTeacher function
     *
     * @param [type] $table 
     * @param [type] $teacher_id 
     * @param [type] $lesson_date 
     * 
     * @return void
     */
    public static function checkScheduleOfTeacher($table, $teacher_id, $lesson_date)
    {
        $model;
        if ($table == 'reserve') {
            $model = new Reserve();
        } elseif ($table == 'tmp_reserve') {
            $model = new TmpReserve();
        } else {
            $model = new TeacherTask();
        }
        $time_end = date('Y-m-d H:i:s', strtotime($lesson_date . '+' . TimeReserve::TIME . ' minutes'));

        $data = $model->where(
            [
                ['teacher_id', '=', $teacher_id],
                ['start_at', '<=', $lesson_date],
                ['end_at', '>=', $time_end ],
            ]
        )->count();

        return $data;
    }
    
    /**
     * __getLessonTypeName function
     *
     * @param [int] $lesson_type 
     * 
     * @return void
     */
    public static function _getLessonTypeName($lesson_type)
    {
        if ($lesson_type == LessonType::LESSON) {
            return 'Lesson'; 
        } 
        if ($lesson_type == LessonType::ASSESSMENT) {
            return 'Assessment'; 
        }
        if ($lesson_type == LessonType::ASSESSMENT_FEEDBACK) {
            return 'Assessment feedback'; 
        }
        if ($lesson_type == LessonType::COUNSELING) {
            return 'Counseling'; 
        }
        if ($lesson_type == LessonType::PRE_ASSESSMENT) {
            return 'Pre-assessment'; 
        }
        if ($lesson_type == LessonType::DEMO_LESSON) {
            return 'Demo lesson'; 
        }
        return 0;
    }

    public static function getCounseling($params) {
        //get user logined
        $user = Auth::user();


        //validate param
        $validate = Validator::make(
            $params,
            [
                'lesson_id'     => 'integer',
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

        $lesson_id = $params['lesson_id'];

        $counseling = Lesson::select('counseling.*')
            ->join('counseling', 'counseling.lesson_id', 'lesson.id')
            //->where('lesson.student_id', $user->alugo_user_id)
            ->where('lesson.id', $lesson_id)
            ->where('lesson_type', LessonType::COUNSELING)
            ->where('lesson.deleted', IsDeletedType::ACTIVE)
            ->first();

        if ($counseling) {
            //result
            $response = [];
            
            $response['data'] = [
                'lesson_id' => $lesson_id,
                'trouble' => $counseling->trouble,
                'impression' => $counseling->impression,
                'advice' => $counseling->advice,
                'share_coach' => $counseling->share_coach,
                'student_id' => $counseling->student_id,
            ];
            
            $result = CommonUtils::apiResultOK(
                $response,
                CommonUtils::getCommon()
            );

            return $result;
        }

        return [];
    }

    /**
     * GetFavorite function
     *
     * @param [type] $user 
     * @param [type] $lesson_id 
     * 
     * @return void
     */
    public static function getFavorite($user, $lesson_id)
    {
        //get favorite
        $favorite = DB::table('favorites')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['activity_id', '=', $lesson_id],
                    ['category', '=', CategoryType::LESSON],
                    ['is_deleted', '=', IsDeletedType::ACTIVE]
                ]
            )
            ->first();
        
        $favorite_id = null;
        if ($favorite) {
            $favorite_id = $favorite->id; 
        }

        return $favorite_id;
    }

    /**
     * Make data test
     *
     * @param [type] $user 
     * @param [type] $lesson_id 
     * 
     * @return void
     */

    public static function makeDataTest() {
        $arugo_user_id = 90272;
        $teacher_id = 1045;
        $item_id = 1989;
        $date = '2018-10-27';
        $minutes = [
            '00:00',
            '30:00'
        ];

        $minutes_end = [
            '25:00',
            '55:00'
        ];

        $aryDataLessonInsert = [];

        for ($i=1; $i < 23; $i++) { 
            for ($j=0; $j < 2; $j++) { 
                $insert_data = [
                    'student_id' => $arugo_user_id,
                    'item_id' => $item_id,
                    'phone_number' => null,
                    'teacher_id' => $teacher_id,
                    'start_at' => $date .' ' . $i . ':' . $minutes[$j],
                    'end_at' => $date .' ' . $i . ':' . $minutes_end[$j],
                    'reserve_sec' => 25 * 60,
                    'curriculum' => ApiLessonLang::LANG,
                    'lang' => ApiLessonLang::LANG,
                    'lesson_type' => LessonType::LESSON,
                    'lesson_ticket_id' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $reserve = Reserve::create($insert_data);
                $idReserve = $reserve->id;

                $aryDataLessonInsert = [
                    'teacher_id' => $teacher_id,
                    'student_id' => $arugo_user_id,
                    'item_id' => $item_id,
                    'lesson_type' => LessonType::LESSON,
                    'start_at' => $date .' ' . $i . ':' . $minutes[$j],
                    'end_at' => $date .' ' . $i . ':' . $minutes_end[$j],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $idLesson = Lesson::create($aryDataLessonInsert);
                $idLesson = $idLesson->id;

                //Update Reserve
                Reserve::where('id', $idReserve)->update(
                    [
                        'lesson_id' => $idLesson
                    ]
                );

                //create white board
                $aryWhiteBoard = [
                    'lesson_id' => $idLesson,
                    'teacher_id' => $teacher_id,
                    'student_id' => $arugo_user_id,
                    'start_at' => date('Y-m-d H:i:s'),
                    'end_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $idWhiteBoard = Whiteboard::create($aryWhiteBoard);
                $idWhiteBoard = $idWhiteBoard->id;

                //create whiteborad log
                $aryWhiteBoardLog = [
                    'whiteboard_id' => $idWhiteBoard,
                    'board' => 'hello<br /><br />Question:&nbsp;&nbsp; &nbsp;How do you manage your anger? Please use &quot;such as&quot; in your answer and give your reasons.<br />Your answer:<br />When I get angry, I often eat dinner which I like the best. I like sushi best. So, If I have much stress such as working, our family and so on. I eat sushi when I get angry and I have a stress such as mistakes in working or trouble with my family and so on. In addition, another way of deal with anger , I think it is better to sleep early. Thanks to sleeping for a long time. Next day, I forgot a part of angry.&nbsp;<br /><br />Improved version:<br /><span background-color:="" font-size:="" line-height:="" style="color: rgb(51, 51, 51); font-family: sans-serif, Arial, Verdana, " trebuchet="">When I get angry, I often eat dinner which I like the best. I like sushi best. So, If I have much stress such as working, our family and so on. I eat sushi when I get angry and I have a stress such as mistakes in working or trouble with my family and so on. In addition, another way of deal with anger , I think it is better to sleep early. Thanks to sleeping for a long time. Next day, I forgot a part of angry.&nbsp;</span>',
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                WhiteboardLog::create($aryWhiteBoardLog);
            }
        }
        
    }

    /**
     * Get Select Lesson
     *
     * @return void
     */
    public static function getSelectLessionRaw() {
        return \DB::raw(
            "id,
            start_at,
            end_at,
            report,
            lesson_id,
            today_feedback,
            message_user,
            share_coach,
            approved_at,
            lesson_type,
            canceled,
            canceled_sec,
            request_sec,
            deleted,
            to_char(created_at, 'YYYY-mm-dd HH24:MI:SS') as created_at,
            to_char(updated_at, 'YYYY-mm-dd HH24:MI:SS') as updated_at,
            to_char(deleted_at, 'YYYY-mm-dd HH24:MI:SS') as deleted_at"
        );
    }
    /**
     * Get Select Lesson
     *
     * @return void
     */
    public static function getSelectReserveRaw() {
        return \DB::raw(
            "id,
            start_at,
            end_at,
            lesson_id,
            lesson_type,
            canceled,
            canceled_at,
            deleted,
            to_char(created_at, 'YYYY-mm-dd HH24:MI:SS') as created_at,
            to_char(updated_at, 'YYYY-mm-dd HH24:MI:SS') as updated_at,
            to_char(deleted_at, 'YYYY-mm-dd HH24:MI:SS') as deleted_at"
        );
    }
}