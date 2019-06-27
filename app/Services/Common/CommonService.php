<?php
namespace App\Services\Common;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controllers\ApiController;

use App\Dtos\ApiRequestDto;
use App\Dtos\ApiResponseDto;
use App\Dtos\ApiErrorDto;
use App\Dtos\UserDto;

use App\Enums\ApiMethodNameType;
use App\Enums\ApiResultStatusType;
use App\Enums\ApiErrorCodeType;
use App\Enums\IsDeletedType;
use App\Enums\LessonType;
use App\Enums\TaskType;
use App\Enums\PointType;
use App\Enums\UserTaskStatusType;

use App\Services\Lesson\LessonService;

use App\Utils\CommonUtils;
use Lang;

use App\User;
use App\Models\Lesson;
use App\Models\Reserve;
use App\Models\UserTask;
use Validator;



/**
 * CommonService class
 */
class CommonService
{
    /**
     * __construct
     */
    public function __construct()
    {

    }

    /**
     * GetTaskProgess function
     *
     * @return void
     */
    public static function getTaskProgess()
    {
        //get user logining
        $user = Auth::user();

        //get time this week
        $last_sunday = date('Y-m-d', strtotime('last sunday'));
        $start_time = date('Y-m-d', strtotime($last_sunday . '+1 day')) . ' 00:00:00';
        $end_time = date('Y-m-d', strtotime($last_sunday . '+7 day')) . ' 23:59:59';

        //get num task in week
        $all_task_num = 0;
        $all_task_num = DB::table('user_tasks')
            ->where('user_id', $user->id)
            ->whereBetween('question_set_date', [$start_time, $end_time])
            ->count();

        //check have task in this week
        if ($all_task_num == 0) {
            $result = [
                'percent' => 0
            ];
            return $result;
        }
        //get num task completed
        $completed_num = 0;
        $completed_num = DB::table('user_tasks')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['is_completed', '=', UserTaskStatusType::TASK_COMPLETED],
                    ['user_task_status', '>', UserTaskStatusType::TASK_COMPLETED]
                ]
            )
            ->whereBetween('question_set_date', [$start_time, $end_time])
            ->count();
        
        $progress = ($completed_num / $all_task_num) * 100;

        $result = [
            'percent' => $progress
        ];

        return $result;
    }

    /**
     * GetPoint function
     *
     * @return void
     */
    public static function getPoint()
    {
        //get user logining
        $user = Auth::user();
        
        //get total point
        $total_point = DB::table('user_points')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['point_type', '=', PointType::POINT_UNUSED]
                ]
            )
            ->sum('point_value');

        //get point used
        $used_point = DB::table('user_points')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['point_type', '=', PointType::POINT_USED]
                ]
            )
            ->sum('point_value');

        return ($total_point - $used_point);
    }

    /**
     * UpdatePoint function
     *
     * @return void
     */
    public static function updatePoint($params = array())
    {
        //get user logining
        $user = Auth::user();

        $validate = Validator::make(
            $params,
            [
                'point_value' => 'integer|required',
            ]
        );
        if ($validate->fails() || $params['point_value'] == 0) {

            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                $validate->messages()->toArray()
            );
            
            return $result;
        }

        $point_type = PointType::POINT_UNUSED;
        if ($params['point_value'] < 0) {
            $point_type = PointType::POINT_USED;
        }

        $insert_data = [
            'user_id' => $user->id,
            'alugo_user_id' => $user->arugo_user_id,
            'point_type' => $point_type,
            'point_value' => $params['point_value'],
            'point_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $success = DB::table('user_points')->insert(
            $insert_data
        );

        $result = CommonUtils::apiResultOK(
            [
                'point' => self::getPoint()
            ],
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * GetValidTicketNum function
     *
     * @return void
     */
    public static function getValidTicketNum()
    {
        //get user loging
        $user = Auth::user();
        
        //get lesson ticket un use
        $list_ticket_un_use = LessonService::checkTicketUnUse($user, 'lesson', date('Y-m-d H:i:s'));
        $num_lesson_ticket = count($list_ticket_un_use);

        //get num counseling ticket un use
        $list_ticket_un_use = LessonService::checkTicketUnUse($user, 'counseling', date('Y-m-d H:i:s'));
        $num_counseling_ticket = count($list_ticket_un_use);

        $result = [
            'lesson_ticket_num' => $num_lesson_ticket,
            'counseling_ticket_num' => $num_counseling_ticket
        ];
        
        return $result;
    }

    /**
     * GetUserInfo function
     *
     * @param array $params 
     * 
     * @return void
     */
    public static function getUserInfo($params = array())
    {
        $validate = Validator::make(
            $params,
            [
                'user_ids'=> 'array|min:1|required',
            ]
        );
        if ($validate->fails()) {

            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                $validate->messages()->toArray()
            );
            
            return $result;
        }

        //get list user
        if (in_array('all', $params['user_ids'])) {
            $list_users = DB::table('users')
                ->where('is_deleted', IsDeletedType::ACTIVE)
                ->get();
        } else {
            $list_users = DB::table('users')
                ->whereIn('id', $params['user_ids'])
                ->where('is_deleted', IsDeletedType::ACTIVE)
                ->get();
        }
        
        if (!$list_users) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => Lang::get('custom.not_user')
                ]
            );
        }
            
         //Setting field display on API
        $aryDisplayField = [
            'arugo_user_id',
            'sr_user_id',
            'cw_room_id',
            'cw_room_name',
            'cw_account_id',
            'first_name',
            'first_name',
            'last_name',
            'first_name_en',
            'last_name_en',
            'mail',
            'phone_number',
            'image_url',
            'curriculum_id',
            'study_sec',
            'status',
            'matrix',
            'opened_at',
            'closed_at',
            'resigned_at',
            'user_type',
            'activated',
            'quitted',
            'is_deleted',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $response = [];

        foreach ($list_users as $user) {
            $responseItem = new UserDto();
            //Setting custom field not using type slack to camel
            $responseItem->userId =  $user->id;

            //Set response from ary Setting Field
            foreach ($aryDisplayField as $field) {
                //Convert snack convention to Camel convention
                $camel = CommonUtils::snackToCamel($field);

                //set field to response
                $responseItem->{$camel} = $user->{$field};
            }

            $response[] = $responseItem;
        }

        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );

        return $result;
    }

    /**
     * CheckReviewLesson function
     *
     * @return void
     */
    public static function checkReviewLesson()
    {
        //get user loging
        $user = Auth::user();

        $reviewLesson = null;

        $reviewLesson = DB::table('review_before_lessons')
            ->where('user_id', $user->id)
            ->first();
        
        if ($reviewLesson) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * CountTaskLate function
     *
     * @return void
     */
    public static function countTaskLate() {
        $user = Auth::user();

        $start_at = $user->opened_at;
        $end_at = $user->closed_at;

        $tasks = DB::table('user_tasks')
            ->join('tasks', 'user_tasks.task_id', '=', 'tasks.id')
            ->where(
                [
                    ['user_tasks.user_id', $user->id],
                    ['user_tasks.question_set_date', '>', $start_at],
                    ['user_tasks.is_completed', '=', '0'],
                    ['user_tasks.is_deleted', '=', IsDeletedType::ACTIVE],
                    ['user_tasks.answer_deadline_date', '<', date('Y-m-d H:i:s')],
                ]
            )
            ->where('tasks.task_type', '<>', TaskType::TASK_TYPE_RV_LESSON)
            ->groupBy('user_tasks.question_set_date', 'user_tasks.task_type')
            ->get();

        //Get data review by user
        $reviewServices = DB::table('review_services')
            ->where(
                [
                    ['user_id',$user->id],
                    ['alugo_user_id', $user->arugo_user_id],
                ]
            )->count();

        $reviewLessons = DB::table('review_lessons')
            ->where(
                [
                    ['user_id', $user->id],
                    ['alugo_user_id', $user->arugo_user_id]
                ]
            )
            ->pluck('lesson_id', 'lesson_id');


        $reviewConseling = DB::table('review_counselors')
            ->where(
                [
                    ['user_id', $user->id],
                    ['alugo_user_id', $user->arugo_user_id]
                ]
            )
            ->pluck('counselor_id', 'counselor_id');

        $all_task_late = 0;
        foreach ($tasks as $key => $t) {
            if ($t->task_type == TaskType::TASK_TYPE_REVIEW_SERVICE && $reviewServices) {
                continue;
            }

            if ($t->task_type == TaskType::TASK_TYPE_REVIEW_LESSON && isset($reviewLessons[$t->cand_task_id])) {
                continue;
            }

            if ($t->task_type == TaskType::TASK_TYPE_REVIEW_COUNSELOR && isset($reviewConseling[$t->cand_task_id])) {
                continue;
            }

            $all_task_late++;
        }

        $taskRVLesson = UserTask::from('user_tasks as utsk')
                            ->join('tasks as tsk', 'utsk.task_id', 'tsk.id')
                            ->join('rv_tasks as rvtsk', 'tsk.cand_task_id', 'rvtsk.id')
                            ->where('utsk.question_set_date', '>=', $start_at)
                            ->where('utsk.answer_deadline_date', '<=', $end_at)
                            ->where('utsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('rvtsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.task_type', TaskType::TASK_TYPE_RV_LESSON)
                            ->where('utsk.user_id', $user->id)
                            ->get();

        $aryLessonExclude = [];

        $lated_rv = 0;
        if ($taskRVLesson) {
            foreach ($taskRVLesson as $key => $t) {
                $aryLessonExclude[] = $t->activity_id;

                if (strtotime($t->answer_deadline_date) < time()) {
                    $lated_rv ++;
                }
            }
        }

        //Lesson and reverser
        $lesson = Lesson::where(
            [
                ['student_id', '=', $user->arugo_user_id], 
                ['start_at', '>=', $start_at],
                ['end_at', '<', date('Y-m-d H:i:s')],
                ['deleted', '=', IsDeletedType::ACTIVE],

            ]
        )
        ->whereNotIn('id', $aryLessonExclude)
        ->whereIn('lesson_type', [LessonType::LESSON])->count();

        //get all lesson reserve
        $reserve = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id], 
                ['start_at', '>=', $start_at],
                ['end_at', '<', date('Y-m-d H:i:s')],
                ['deleted', '=', IsDeletedType::ACTIVE],
            ]
        )
        ->whereNull('lesson_id')
        ->whereIn('lesson_type', [LessonType::LESSON, LessonType::COUNSELING])->count();

        //get all lesson reserve
        $assement = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['start_at', '>=', $start_at],
                ['end_at', '<', date('Y-m-d H:i:s')],
                ['deleted', '=', IsDeletedType::ACTIVE],
            ]
        )
        ->whereNotNull('assessment_id')
        ->count();

        return $all_task_late + $reserve + $lesson + $lated_rv + $assement;
    }
}