<?php
namespace App\Services\Homework;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Http\Controllers\ApiController;

use App\Dtos\ApiRequestDto;
use App\Dtos\ApiResponseDto;
use App\Dtos\ApiErrorDto;
use App\Dtos\UserLessonLogDto;
use App\Dtos\WhiteboardDto;
use App\Dtos\UserTaskDto;
use App\Dtos\QuestionDto;
use App\Dtos\TaskDto;
use App\Enums\IsDeletedType;
use App\Enums\LessonType;
use App\Enums\TaskType;
use App\Enums\CategoryType;
use App\Enums\UserTaskStatusType;
use App\Enums\TaskSelectedType;
use App\Enums\NumQuestion;

use App\Enums\ApiMethodNameType;
use App\Enums\ApiResultStatusType;
use App\Enums\ApiErrorCodeType;
use App\Enums\QuestionType;

use App\Utils\CommonUtils;
use Lang;
use DB;

use App\Models\UserTask;
use App\Models\UserTaskPeriod;
use App\Models\Task;
use App\Models\lesson;
use App\Models\WhiteboardLog;
use App\Models\UserListeningAssign;

use App\Services\TaskService;
use App\Services\Common\CommonService;

use Validator;

use Config;

/**
 * HomeworkService class
 */
class HomeworkService
{
    /**
     * __construct
     */
    public function __construct()
    {

    }

    /**
     * GetTaskList function
     *
     * @return void
     */
    public static function getTaskList()
    {
        //get user logined
        $user = Auth::user();

        //Make time get data
        //get time range lesson reserve
        $start_at = $user->opened_at;
        $end_at = $user->closed_at;

        $aryMappingTaskType = self::getMappingTaskType();
        //get all task 
        $task = null;
        $task = DB::table('user_tasks')
            ->join('tasks', 'user_tasks.task_id', '=', 'tasks.id')
            ->whereIn('tasks.task_type', array_keys($aryMappingTaskType))
            ->where('user_tasks.user_id',  $user->id)
            ->where('user_tasks.question_set_date', '>=', $start_at)
            ->where('user_tasks.answer_deadline_date', '<=', $end_at)
            ->where('user_tasks.is_deleted', IsDeletedType::ACTIVE)
            ->where('user_tasks.task_type', '<>', TaskType::TASK_TYPE_RV_LESSON)
            ->where('user_tasks.task_type', '<>', TaskType::TASK_TYPE_REVIEW_LESSON)
            ->where('user_tasks.task_type', '<>', TaskType::TASK_TYPE_REVIEW_BEFORE_LESSON)
            ->where('user_tasks.task_type', '<>', TaskType::TASK_TYPE_REVIEW_COUNSELOR)
            ->where('user_tasks.task_type', '<>', TaskType::TASK_TYPE_REVIEW_SERVICE)
            ->orderBy('user_tasks.question_set_date', 'ASC')
            ->groupBy('user_tasks.question_set_date')
            ->get();

        $aryTaskAll = [];

        if ($task) {
            foreach ($task as $t) {
                $week = TaskService::getWeekNo($t->question_set_date, $start_at);

                $favorite_id = self::getFavorite($user, $t->task_id, CategoryType::HOMEWORK);
               
                $aryTaskTmp = self::_makeAryOutputGetAllTask(
                    $t->task_id,
                    $t->question_set_date,
                    $t->question_set_date,
                    $t->answer_deadline_date,
                    $t->is_completed,
                    $t->task_type,
                    self::getTaskTypeName($t->task_type),
                    $favorite_id
                );

                $aryTaskTmp['cand_task_id'] = $t->cand_task_id;
                if ($t->task_type == TaskType::TASK_TYPE_WORD_CARD) {
                    $wordcardTaskDetail = DB::table('tasks as tsk')
                        ->join('word_cards as wc', 'wc.id', '=', 'tsk.cand_task_id')
                        ->join('word_card_categories as catwc', 'wc.word_card_category_id', '=', 'catwc.id')
                        ->where(
                            [
                                ['tsk.id', '=', $t->task_id],
                                ['tsk.user_id', '=', $t->user_id],
                            ]
                        )
                        ->first();
                    
                    $aryTaskTmp['word_card_category'] = $wordcardTaskDetail->category;
                }

                $aryTaskAll[$week][] = $aryTaskTmp;
            }
        }

        //result
        $response = [];
        
        $response['data'] = $aryTaskAll;
        
        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );

        return $result;
    }

    /**
     * DoCompleteTask function
     *
     * @param array $params 
     * 
     * @return void
     */
    public static function doCompleteTask($params = array())
    {
        //user logined
        $user = Auth::user();

        //valider parrams
        $validate = Validator::make(
            $params,
            [
                'tasks'=> 'required|array|min:1',
                'task_type' => 'required'
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

        //get list tasks
        $list_task = $params['tasks'];

        //list task type regist sound files
        $list_sound_files = [
            QuestionType::QUESTION_TYPE_GRAMMAR, 
            QuestionType::QUESTION_TYPE_VOCABULARY, 
            QuestionType::QUESTION_TYPE_SPEAKING_RALLY
        ];

        //list task type regist homework answers
        $list_homework_answers = [
            QuestionType::QUESTION_TYPE_GRAMMAR, 
            QuestionType::QUESTION_TYPE_VOCABULARY, 
            QuestionType::QUESTION_TYPE_SPEAKING_RALLY, 
            QuestionType::QUESTION_TYPE_LISTENING_B, 
            QuestionType::QUESTION_TYPE_LISTENING_C, 
            QuestionType::QUESTION_TYPE_LISTENING_D 
        ];

        foreach ($list_task as $task) {
            //setup data insert
            $file_url = (isset($task['file_url'])) ? $task['file_url'] : null;
            $file_name = (isset($task['file_name'])) ? $task['file_name'] : null;
            $sound_files_id = null;
            $answer = (isset($task['answer'])) ? $task['answer'] : null;
            $sr_answer_id = (isset($task['sr_answer_id'])) ? $task['sr_answer_id'] : null;
            $homework_answer_id = null;
            
            //get homework_answer
            $homework_answer = null;
            $homework_answer = DB::table('homework_answers')
                ->where(
                    [
                        ['user_id', '=', $user->id],
                        ['category', '=', CategoryType::HOMEWORK],
                        ['task_type', '=', $params['task_type']],
                        ['activity_id', '=', $task['task_id']],
                    ]
                )
                ->first();

            if (!$homework_answer) {
                 //regist sound files 
                if (in_array($params['task_type'], $list_sound_files)) {
                    $insert_sound_file = [
                        'user_id' => $user->id,
                        'category' => CategoryType::HOMEWORK,
                        'task_type' => $params['task_type'],
                        'activity_id' => $task['task_id'],
                        'file_url' => $file_url,
                        'file_name' => $file_name
                    ];

                    $sound_files_id = DB::table('sound_files')
                        ->insertGetId($insert_sound_file);
                }

                //regist homework answer
                if (in_array($params['task_type'], $list_homework_answers)) {
                    $insert_homework_answer = [
                        'user_id' => $user->id,
                        'category' => CategoryType::HOMEWORK,
                        'task_type' => $params['task_type'],
                        'activity_id' => $task['task_id'],
                        'sound_file_id' => $sound_files_id,
                        'sr_answer_id' => $sr_answer_id,
                        'answer'=>$answer
                    ];

                    $homework_answer_id = DB::table('homework_answers')
                        ->insertGetId($insert_homework_answer);
                }
            } else {
                if (in_array($params['task_type'], $list_sound_files)) {
                    $sound_files = null;
                    $sound_file_id = null;
                    $sound_files = DB::table('sound_files')
                        ->where(
                            [
                                ['user_id', '=', $user->id],
                                ['category', '=', CategoryType::HOMEWORK],
                                ['task_type', '=', $params['task_type']],
                                ['activity_id', '=', $task['task_id']],
                            ]
                        )
                        ->first();

                    if ($sound_files) {
                        $sound_files_id = $sound_files->id;
                        $update_sound_files = DB::table('sound_files')
                            ->where('id', $sound_files_id)
                            ->update(
                                [
                                    'file_url' => $file_url,
                                    'file_name' => $file_name,
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]
                            );
                    }
                }

                if (in_array($params['task_type'], $list_homework_answers)) {
                    $update_homework_answers = DB::table('homework_answers')
                        ->where('id', $homework_answer->id)
                        ->update(
                            [
                                'sound_file_id' => $sound_files_id,
                                'answer' => $answer,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                }


            }

           
            //update complete task
            $user_task = UserTask::where(
                [
                    ['user_id', '=', $user->id],
                    ['task_id', '=', $task['task_id']]
                ]
            )->first();

            if ($user_task) {
                $user_task->user_task_status = UserTaskStatusType::TASK_STATUS_VAL_ANSWERED;
                $user_task->is_completed = UserTaskStatusType::TASK_COMPLETED;
                $user_task->completed_at = date('Y-m-d H:i:s');
                $user_task->save(); 
            }

            //update favorite
            $favorite = null;
            $favorite = DB::table('favorites')
                ->where(
                    [
                        ['user_id', '=', $user->id],
                        ['category', '=', CategoryType::HOMEWORK],
                        ['task_type', '=', $params['task_type']],
                        ['activity_id', '=', $task['task_id']],
                        ['is_deleted', '=', IsDeletedType::ACTIVE]
                    ]
                )
                ->first();

            if ($favorite) {
                $update_homework_answers = DB::table('favorites')
                    ->where('id', $favorite->id)
                    ->update(
                        [
                            'answer' => $answer,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
            }

        }
        
        $result = CommonUtils::apiResultOK(
            null,
            CommonUtils::getCommon()
        );
        return $result;

    }

    /**
     * GetAnotherTask function
     *
     * @param array $params 
     * 
     * @return void
     */
    public static function getAnotherTask($params = array())
    {
        //get user logined
        $user = Auth::user();
        $start_at = $user->opened_at;
        $end_at = $user->closed_at;

        //valider parrams
        $validate = Validator::make(
            $params,
            [
                'task_type'     => 'integer|required',
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
        DB::beginTransaction();

        try {
            //get another task by task_type
            switch ($params['task_type']) {
            case TaskType::TASK_GRAMMAR:
                $listTaskG = Task::where(
                    [
                        ['user_id', '=', $user->id],
                        ['task_type', '=', TaskType::TASK_GRAMMAR],
                        ['is_selected', '=', TaskSelectedType::RESERVED],
                        ['is_deleted', '=', IsDeletedType::ACTIVE],
                    ]
                )
                ->limit(NumQuestion::NUM_QUESTION)
                ->get();

                $listTaskV = Task::where(
                    [
                        ['user_id', '=', $user->id],
                        ['task_type', '=', TaskType::TASK_VOCABULARY],
                        ['is_selected', '=', TaskSelectedType::RESERVED],
                        ['is_deleted', '=', IsDeletedType::ACTIVE],
                    ]
                )
                ->limit(NumQuestion::NUM_QUESTION)
                ->get();

                break;
            case TaskType::TASK_TYPE_LISTENING_B:
                //get new task
                $getTask = self::getMoreListeningTask($user);
                if (!$getTask) {
                    $result = CommonUtils::apiResultOK(
                        [],
                        CommonUtils::getCommon()
                    );
                    return $result;
                }
              
                //add task to table task
                $listTask = self::insertTask($getTask['task_id'], $user, $getTask['task_type']);
                
                break;
            case TaskType::TASK_SPEAKING_RALLY:
                //get new task
                $getTask = self::getMoreSRTask($user);
                if (!$getTask) {
                    $result = CommonUtils::apiResultOK(
                        [],
                        CommonUtils::getCommon()
                    );
                    return $result;
                }

                //insert to task
                $listTask = self::insertTask($getTask, $user, $params['task_type']);
                break;
            case TaskType::TASK_TYPE_WORD_CARD:
                //get new task
                $getTask = self::getMoreWordCardTask($user);
                
                if (!$getTask) {
                    $result = CommonUtils::apiResultOK(
                        [],
                        CommonUtils::getCommon()
                    );
                    return $result;
                }
                //insert to task
                $listTask = self::insertTask($getTask, $user, $params['task_type']);
                
                break;
            default:
                $result = new ApiResponseDto();
                $apiError = new ApiErrorDto();
                
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                    [
                        'MSG'  => $params['task_type'],
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }


        } catch (Exception $e) {
            DB::rollBack();
        }

        //add data to user task return fist id
        if ($params['task_type'] == QuestionType::QUESTION_TYPE_GRAMMAR) {
            $paramG['task_id'] = self::addUserTask($listTaskG, $user);
            $paramV['task_id'] = self::addUserTask($listTaskV, $user);
        } else {
            $param['task_id'] = self::addUserTask($listTask, $user);
        }

        //get list user task
        $list_user_task = null;
        switch ($params['task_type']) {
        case TaskType::TASK_GRAMMAR:
            $list_user_task = [];
            if ($paramG['task_id'] != null) {
                $list_user_task['grammars'] = self::getGVTask($paramG);
            }
            
            if ($paramV['task_id'] != null) {
                $list_user_task['vocaburaries'] = self::getGVTask($paramV);
            }
            break;
        case TaskType::TASK_TYPE_LISTENING_B:
            if ($param['task_id'] != null) {
                $list_user_task = self::getListeningTask($param);
            }
            break;
        case TaskType::TASK_SPEAKING_RALLY:
            if ($param['task_id'] != null) {
                $list_user_task = self::getSRTask($param);
            }
            break;
        case TaskType::TASK_TYPE_WORD_CARD:
            if ($param['task_id'] != null) {
                $list_user_task = self::getWordCardTask($param);
            }
            break;
        }

        if (!$list_user_task) {
            $result = CommonUtils::apiResultOK(
                [],
                CommonUtils::getCommon()
            );
            return $result;
        } else {
            if ($params['task_type'] == TaskType::TASK_GRAMMAR) {
                foreach ($list_user_task as $key => $value) {
                    foreach ($value->responses as $t) {
                        $week = TaskService::getWeekNo($t->questionSetDate, $start_at);
            
                        $aryTaskAll[$week][] = self::_makeAryOutputGetAllTask(
                            $t->taskId,
                            $t->questionSetDate,
                            $t->questionSetDate,
                            $t->answerDeadlineDate,
                            0,
                            $t->taskInfo->taskType,
                            self::getTaskTypeName($t->taskInfo->taskType),
                            $t->taskInfo->favoriteId
                        );

                        break;
                    }
                }
                
                
            } else {
                $list_task_added = $list_user_task->responses;
                
                if ($params['task_type'] == TaskType::TASK_TYPE_WORD_CARD) {
                    $list_task_added = $list_task_added['data'];
                }
                foreach ($list_task_added as $t) {
                    $week = TaskService::getWeekNo($t->questionSetDate, $start_at);
        
                    $aryTaskAll[$week][] = self::_makeAryOutputGetAllTask(
                        $t->taskId,
                        $t->questionSetDate,
                        $t->questionSetDate,
                        $t->answerDeadlineDate,
                        0,
                        $t->taskInfo->taskType,
                        self::getTaskTypeName($t->taskInfo->taskType),
                        $t->taskInfo->favoriteId
                    );

                    break;
                }
            }

            

            //update user point
            try {
                $update_point = CommonService::updatePoint(['point_value'=>env('TASK_OKAWARI_POINT_VALUE')]);
                if ($update_point->status == UserTaskStatusType::TASK_COMPLETED) {
                    $result = new ApiResponseDto();
                    $apiError = new ApiErrorDto();
                    
                    $result = CommonUtils::apiResultNG(
                        ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                        Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                        [
                            'MSG' => Lang::get('not_update_point'),
                        ],
                        CommonUtils::getCommon()
                    );
                    return $result;
                }
            } catch (Exception $e) {
                DB::rollBack();
            }

            DB::commit();

            //result
            $response = [];
            
            $response['data'] = $aryTaskAll;
            
            $result = CommonUtils::apiResultOK(
                $response,
                CommonUtils::getCommon()
            );
    
            return $result;
        }

        return [];
    }
   
    /**
     * GetReviewTask function
     *
     * @param [type] $params 
     * 
     * @return void
     */
    public static function getReviewTask($params)
    {
        //get user logined
        $user = Auth::user();

        //validate param
        $validate = Validator::make(
            $params,
            [
                'task_id'     => 'integer',
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

        //get rv_task
        $rv_task = DB::table('rv_tasks')->where(
            [
                ['user_id', '=', $user->id],
                ['id', '=', $params['task_id']]
            ]
        )->first();

        if (!$rv_task) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'MSG' => Lang::get('custom.not_task_detail')
                ],
                CommonUtils::getCommon()
            );
            return $result;
        }

        //get lesson
        $lesson = Lesson::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['id', '=', $rv_task->activity_id]
            ]
        )->first();

        if (!$lesson) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'MSG' => Lang::get('custom.not_lesson')
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        //get favorite
        $favorite = DB::table('favorites')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['activity_id', '=', $rv_task->activity_id],
                    ['category', '=', CategoryType::LESSON],
                ]
            )
            ->first();
        
        $favorite_id = null;
        if ($favorite) {
            $favorite_id = $favorite->id; 
        }
        $lesson->favorite_id  = $favorite_id;

        //get whteboard
        $whiteboardLog = WhiteboardLog::
            leftJoin('whiteboard', 'whiteboard.id', '=', 'whiteboard_log.whiteboard_id')
            ->where(
                [
                    ['whiteboard.student_id', '=', $user->arugo_user_id],
                    ['whiteboard.lesson_id', '=', $rv_task->activity_id]
                ]
            )->first();

        if ($whiteboardLog) {
            //set whiteboard log to lesson
            $lesson->whiteboard_id          = $whiteboardLog->whiteboard_id;
            $lesson->whiteboard_board       = $whiteboardLog->board;
            $lesson->whiteboard_create_at   = $whiteboardLog->created_t;
        }

       
        $result = CommonUtils::apiResultOK(
            [
                'Lesson' => $lesson
            ],
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * GetSRTask function
     *
     * @param [type] $params 
     *
     * @return void 
     */
    public static function getSRTask($params)
    {
        //get user logined
        $user = Auth::user();

        //validate param
        $validate = Validator::make(
            $params,
            [
                'task_id'     => 'integer',
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

        //get user task
        $user_task = UserTask::Where(
            [
                ['user_id', '=', $user->id],
                ['task_id', '=', $params['task_id']],
            ]
        )->first();

        if (!$user_task) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'task_id' => $params['task_id']
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        //get task
        $task = Task::where('id', $params['task_id'])->first();

        if ($task->task_type != TaskType::TASK_SPEAKING_RALLY) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'task_id' => $params['task_id']
                ],
                CommonUtils::getCommon()
            );
            
            return $result; 
        }

        //get user feedbacks
        $task_service = new TaskService();
        $user_feedback = $task_service->getFeedbackInfo($user_task);
        $user_info = $task_service->getUserInfo($user_task);

        //get task detail
        $task_detail = DB::table('speaking_rallies')
            ->where('id', $task->cand_task_id)->first();

        //set task detail
        $questionDto = new QuestionDto();
        $questionDto->questionId        = $task_detail->id;
        $questionDto->srCategoryId      = $task_detail->sr_category_id;
        $questionDto->srContext         = $task_detail->context;
        $questionDto->srCreatedStuffId  = $task_detail->created_stuff_id;
        $questionDto->srOpened          = $task_detail->opened;

        //get sr category
        $sr_cat = DB::table('sr_categories')
            ->where('id', $task_detail->sr_category_id)
            ->first();
        
        if ($sr_cat) {
            $questionDto->srCategoryName      = $sr_cat->category_name;
        }

        $favorite_id = self::getFavorite($user, $params['task_id'], CategoryType::HOMEWORK);

        //setup task info
        $taskDto = new TaskDto();
        $taskDto->taskId           = $task->id;
        $taskDto->userId           = $task->user_id;
        $taskDto->taskType         = $task->task_type;
        $taskDto->setTaskTypeName();
        $taskDto->candidateTaskId  = $task->cand_task_id;
        $taskDto->taskOrder        = $task->task_order;
        $taskDto->isSelected       = $task->is_selected;
        $taskDto->comment          = $task->comment;
        $taskDto->isDeleted        = $task->is_deleted;
        $taskDto->createdAt        = $task->created_at;
        $taskDto->updatedAt        = $task->updated_at;
        $taskDto->deletedAt        = $task->deleted_at;
        $taskDto->taskDetail       = $questionDto;
        $taskDto->favoriteId       = $favorite_id;
       
        //Setting field display on API
        $aryDisplayField = [
            'user_task_status',
            'question_set_date',
            'answer_deadline_date',
            'answered_date',
            'user_message_log_id',
            'counselor_info',
            'operator_info',
            'original_answer',
            'user_feedback_id',
            'comment',
            'is_deleted',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $response = new UserTaskDto();

        //Setting custom field not using type slack to camel
        $response->userId       = $user->id;
        $response->taskId       = $task->id;
        $response->userTaskId   = $user_task->id;
        //$response->userInfo     = $user_info;
        $response->feedbackInfo = $user_feedback;
        $response->taskInfo     = $taskDto;

        //get sound url
        if ($user_task->is_completed == UserTaskStatusType::TASK_COMPLETED) {
            $homework_answer = DB::table('homework_answers')
                ->Where(
                    [
                        ['user_id', '=', $user->id],
                        ['activity_id', '=', $params['task_id']],
                    ]
                )->first();
            
            if ($homework_answer) {
                if ($homework_answer->sound_file_id != null) {
                    $sound_files = DB::table('sound_files')
                        ->Where(
                            [
                                ['user_id', '=', $user->id],
                                ['activity_id', '=', $params['task_id']],
                                ['category', '=', CategoryType::HOMEWORK],
                                ['task_type', '=', TaskType::TASK_SPEAKING_RALLY],
                            ]
                        )->first();

                    if ($sound_files) {
                        $response->fileUrl  = $sound_files->file_url;
                        $response->fileName = $sound_files->file_name;
                    } else {
                        $response->fileUrl = null;
                        $response->fileName = null;
                    }
                }
                
                $response->answer = $homework_answer->answer;
                $response->srAnswerId = $homework_answer->sr_answer_id;
            } 
        }
        
        //Set response from ary Setting Field
        foreach ($aryDisplayField as $field) {
            //Convert snack convention to Camel convention
            $camel = CommonUtils::snackToCamel($field);

            //set field to response
            $response->{$camel} = $user_task->{$field};
        }
        $response->setUserTaskStatusName();

        $result = CommonUtils::apiResultOK(
            [
                'task' => $response
            ],
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * GetGVTask function
     *
     * @param [type] $params
     * @return void
     */
    public static function getGVTask($params)
    {
        //get user logined
        $user = Auth::user();

        //validate param
        $validate = Validator::make(
            $params,
            [
                'task_id'     => 'integer',
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

        //get task type of taak_id
        $task = null;
        $task = DB::table('tasks')
            ->select('task_type')
            ->where('id', $params['task_id'])
            ->first();

        if (!$task) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => $params['task_id']
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        //get question set date and task period order
        $user_task_first = UserTask::select('question_set_date', 'task_period_order')
            ->where(
                [
                    ['task_id', '=', $params['task_id']],
                    ['user_id', '=', $user->id],
                ]
            )
            ->first();
        
        if (!$task) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => $params['task_id']
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        //get list user tassk
        $listUsertask = DB::table('user_tasks')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['task_type', '=', $task->task_type],
                    ['question_set_date', '=', $user_task_first->question_set_date],
                    ['task_period_order', '=', $user_task_first->task_period_order],
                ]
            )
            ->limit(NumQuestion::NUM_QUESTION)
            ->get();
        
        if (!$listUsertask) {
            $result = CommonUtils::apiResultOK(
                [],
                CommonUtils::getCommon()
            );
            return $result;
        }

        //setup result
        $response = [];
        foreach ($listUsertask as $userTask) {
            $task_service = new TaskService();
            $user_feedback = $task_service->getFeedbackInfo($userTask);
            $user_info = $task_service->getUserInfo($userTask);
            $task_info = $task_service->getTaskInfo($userTask);
            $favorite_id = self::getFavorite($user, $userTask->task_id, CategoryType::HOMEWORK);
            $task_info->favoriteId = $favorite_id;
     
            //Setting field display on API
            $aryDisplayField = [
                'user_task_status',
                'question_set_date',
                'answer_deadline_date',
                'answered_date',
                'user_message_log_id',
                'original_answer',
                'user_feedback_id',
                'comment',
                'is_deleted',
                'created_at',
                'updated_at',
                'deleted_at',
            ];

            $responseItem = new UserTaskDto();

            //Setting custom field not using type slack to camel
            $responseItem->userId       = $user->id;
            $responseItem->taskId       = $userTask->task_id;
            $responseItem->userTaskId   = $userTask->id;
            //$responseItem->userInfo     = $user_info;
            $responseItem->feedbackInfo = $user_feedback;
            $responseItem->taskInfo     = $task_info;

            if ($userTask->is_completed == UserTaskStatusType::TASK_COMPLETED) {
                $homework_answer = DB::table('homework_answers')
                    ->Where(
                        [
                            ['user_id', '=', $user->id],
                            ['activity_id', '=', $userTask->task_id],
                        ]
                    )->first();
                
                if ($homework_answer && $homework_answer->sound_file_id != null) {
                    $sound_files = DB::table('sound_files')
                        ->Where(
                            [
                                ['user_id', '=', $user->id],
                                ['activity_id', '=', $userTask->task_id],
                                ['category', '=', CategoryType::HOMEWORK],
                                ['task_type', '=', $userTask->task_type],
                            ]
                        )->first();
                    
                    if ($sound_files) {
                        $responseItem->fileUrl  = $sound_files->file_url;
                        $responseItem->fileName = $sound_files->file_name;
                    } else {
                        $response->fileUrl = null;
                        $response->fileName = null;
                    }
                } 
            }
            
            //Set response from ary Setting Field
            foreach ($aryDisplayField as $field) {
                //Convert snack convention to Camel convention
                $camel = CommonUtils::snackToCamel($field);

                //set field to response
                $responseItem->{$camel} = $userTask->{$field};
            }
            $responseItem->setUserTaskStatusName();

            $response[] = $responseItem; 
        }

        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );
        return $result;
       
    }

    public static function getRVTask($params) {


        //get user logined
        $user = Auth::user();
        $start_at = $user->opened_at;
        $end_at = $user->closed_at;

        //①レッスン復習タスクがすでにあるかどうかをチェックする
        $task = UserTask::from('user_tasks as utsk')
                            ->join('tasks as tsk', 'utsk.task_id', 'tsk.id')
                            ->join('rv_tasks as rvtsk', 'tsk.cand_task_id', 'rvtsk.id')
                            ->where('utsk.question_set_date', '>=', $start_at)
                            ->where('utsk.answer_deadline_date', '<=', $end_at)
                            ->where('utsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('rvtsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.task_type', TaskType::TASK_TYPE_RV_LESSON)
                            ->where('utsk.user_id', $user->id)
                            ->groupBy('tsk.id')
                            ->get();

        $response = [];
        $aryTaskAll = [];

        if ($task) {
            foreach ($task as $t) {
                $week = TaskService::getWeekNo($t->question_set_date, $start_at);
                $favorite_id = self::getFavorite($user, $t->activity_id, CategoryType::HOMEWORK);

                $aryFormatTask = self::_makeAryOutputGetAllTask(
                    $t->task_id,
                    $t->question_set_date,
                    $t->question_set_date,
                    $t->answer_deadline_date,
                    $t->is_completed,
                    $t->task_type,
                    self::getTaskTypeName($t->task_type),
                    $favorite_id
                );

                //get review lesson
                $reviewLesson = DB::table('review_lessons')
                    ->where('lesson_id', $t->activity_id)
                    ->first();

                if ($reviewLesson) {
                    $aryFormatTask['is_review'] = 1;
                }

                $aryFormatTask['lesson_id'] = $t->activity_id;

                $aryTaskAll[$week][] = $aryFormatTask;
            }
        }

        //result
        $response = [];
        
        $response['data'] = $aryTaskAll;
        
        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );

        return $result;
    }

    /**
     * GetWordCardTask function
     *
     * @param [type] $params
     * @return void
     */
    public static function getWordCardTask($params)
    {
        //get user logined
        $user = Auth::user();

        //validate param
        $validate = Validator::make(
            $params,
            [
                'task_id'     => 'integer',
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

        //get task type of task_id
        $task = null;
        $task = DB::table('tasks')
            ->select('task_type')
            ->where('id', $params['task_id'])
            ->first();

        if (!$task || $task->task_type != 40) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'task_id' => $params['task_id']
                ],
                CommonUtils::getCommon()
            );
            
            return $result; 
        }
        //get question set date and task period order
        $user_task_first = UserTask::select('question_set_date', 'task_period_order')
            ->where(
                [
                    ['task_id', '=', $params['task_id']],
                    ['user_id', '=', $user->id],
                ]
            )
            ->first();
        
        if (!$user_task_first) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => $params['task_id']
                ],
                CommonUtils::getCommon()
            );
            
            return $result; 
        }

        //get list user tassk
        $listUsertask = UserTask::leftJoin('tasks', 'user_tasks.task_id', '=', 'tasks.id')
            ->where(
                [
                    ['user_tasks.user_id', '=', $user->id],
                    ['tasks.task_type', '=', $task->task_type],
                    ['user_tasks.question_set_date', '=', $user_task_first->question_set_date],
                    ['user_tasks.task_period_order', '=', $user_task_first->task_period_order],
                ]
            )
            ->get();
        
        if (!$listUsertask) {
            $result = CommonUtils::apiResultOK(
                [],
                CommonUtils::getCommon()
            );
            return $result; 
        }

        //setup result
        $response = [];
        foreach ($listUsertask as $userTask) {
            $task_service = new TaskService();
            $user_feedback = $task_service->getFeedbackInfo($userTask);
            $user_info = $task_service->getUserInfo($userTask);
     
            //get task info
            $task = DB::table('tasks')
                ->where('id', $userTask->task_id)
                ->first();
            $task_detail = DB::table('word_cards')
                ->where('id', $task->cand_task_id)->first();

            $favorite_id = self::getFavorite($user, $userTask->task_id, CategoryType::HOMEWORK);

            //set task detail
            $questionDto = new QuestionDto();
            $questionDto->questionId        = $task_detail->id;
            $questionDto->wordCardCategoryId= $task_detail->word_card_category_id;
            $questionDto->word              = $task_detail->word;
            $questionDto->answer            = $task_detail->answer;

            //get word card category name
            $wordCardcategory = DB::table('word_card_categories')
                ->where('id', $task_detail->word_card_category_id)
                ->first();

            $questionDto->wordCardCategory = $wordCardcategory->category;

            //setup task info
            $taskDto = new TaskDto();
            $taskDto->taskId           = $task->id;
            $taskDto->userId           = $task->user_id;
            $taskDto->taskType         = $task->task_type;
            $taskDto->setTaskTypeName();
            $taskDto->candidateTaskId  = $task->cand_task_id;
            $taskDto->taskOrder        = $task->task_order;
            $taskDto->isSelected       = $task->is_selected;
            $taskDto->comment          = $task->comment;
            $taskDto->isDeleted        = $task->is_deleted;
            $taskDto->createdAt        = $task->created_at;
            $taskDto->updatedAt        = $task->updated_at;
            $taskDto->deletedAt        = $task->deleted_at;
            $taskDto->taskDetail       = $questionDto;
            $taskDto->favoriteId       = $favorite_id;


            //Setting field display on API
            $aryDisplayField = [
                'user_task_status',
                'question_set_date',
                'answer_deadline_date',
                'answered_date',
                'user_message_log_id',
                'counselor_info',
                'operator_info',
                'original_answer',
                'user_feedback_id',
                'comment',
                'is_deleted',
                'created_at',
                'updated_at',
                'deleted_at',
            ];

            $responseItem = new UserTaskDto();

            //Setting custom field not using type slack to camel
            $responseItem->userId       = $user->id;
            $responseItem->taskId       = $userTask->task_id;
            $responseItem->userTaskId   = $userTask->id;
            //$responseItem->userInfo     = $user_info;
            $responseItem->feedbackInfo = $user_feedback;
            $responseItem->taskInfo     = $taskDto;
            
            //Set response from ary Setting Field
            foreach ($aryDisplayField as $field) {
                //Convert snack convention to Camel convention
                $camel = CommonUtils::snackToCamel($field);

                //set field to response
                $responseItem->{$camel} = $userTask->{$field};
            }
            $responseItem->setUserTaskStatusName();

            $response[] = $responseItem; 
        }

        $result = CommonUtils::apiResultOK(
            [
                'total_time' => Config::get('app.time_total_word_card', 30),
                'data' => $response
            ],
            CommonUtils::getCommon()
        );
        return $result;
       
    }

    /**
    * GetWordCardTask function
    *
    * @param [type] $params
    * @return void
    */
    public static function getListeningTask($params)
    {
        //get user logined
        $user = Auth::user();

        //validate param
        $validate = Validator::make(
            $params,
            [
                'task_id' => 'integer',
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

        //get task type og taak_id
        $task = DB::table('tasks')
            ->where('id', $params['task_id'])
            ->first();

        if (!$task) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'task_id' => $params['task_id']
                ],
                CommonUtils::getCommon()
            );
            
            return $result; 
        }

        if ($task->task_type != TaskType::TASK_TYPE_LISTENING_B && $task->task_type != TaskType::TASK_TYPE_LISTENING_C && $task->task_type != TaskType::TASK_TYPE_LISTENING_D) {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'task_id' => $params['task_id']
                ],
                CommonUtils::getCommon()
            );
            
            return $result; 
        }

        //get user task
        $user_task = DB::table('user_tasks')
            ->where(
                [
                    ['task_id', '=', $task->id],
                    ['user_id', '=', $user->id],
                ]
            )
            ->first();

        if (!$user_task) {
            $result = CommonUtils::apiResultOK(
                [],
                CommonUtils::getCommon()
            );
            return $result;
        }
            
        
        //get list assign 
        $list_user_task = DB::table('user_tasks')
            ->where(
                [
                    ['question_set_date', '=', $user_task->question_set_date],
                    ['user_id', '=', $user->id]
                ]
            )
            ->get();

        //setup result
        $response = [];
        foreach ($list_user_task as $userTask) {
            $task_service = new TaskService();
            $user_feedback = $task_service->getFeedbackInfo($userTask);
            //$user_info = $task_service->getUserInfo($userTask);
     
            //get task info
            $task = Task::where('id', $userTask->task_id)->first();
            
            //get task detail
            $task_detail = DB::table('listening_tasks')
                ->where('id', $task->cand_task_id)->first();

            if (!$task_detail) {
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                    [
                        'task_id' => $params['task_id']
                    ],
                    CommonUtils::getCommon()
                );
                
                return $result; 
            }

            $favorite_id = self::getFavorite($user, $userTask->task_id, CategoryType::HOMEWORK);

            //set task detail
            $questionDto = new QuestionDto();
            $questionDto->questionId        = $task_detail->id;
            $questionDto->unitName          = $task_detail->unit_name;
            $questionDto->subjectName       = $task_detail->subject_name;
            $questionDto->heatingText       = $task_detail->hearing_text;
            $questionDto->question          = $task_detail->question;
            $questionDto->answer            = $task_detail->answer;
            $questionDto->fileUrl1          = $task_detail->file_url1;
            $questionDto->fileUrl2          = $task_detail->file_url2;

            //set listening type
            if ($task->task_type == TaskType::TASK_TYPE_LISTENING_D) {
                $listening_type = 'Listening D';
            } elseif ($task->task_type == TaskType::TASK_TYPE_LISTENING_C) {
                $listening_type = 'Listening C';
            } else {
                $listening_type = 'Listening B';
            }
            $questionDto->listeningType = $listening_type;

            //setup task info
            $taskDto = new TaskDto();
            $taskDto->taskId           = $task->id;
            $taskDto->userId           = $task->user_id;
            $taskDto->taskType         = $task->task_type;
            $taskDto->setTaskTypeName();
            $taskDto->candidateTaskId  = $task->cand_task_id;
            $taskDto->taskOrder        = $task->task_order;
            $taskDto->isSelected       = $task->is_selected;
            $taskDto->comment          = $task->comment;
            $taskDto->isDeleted        = $task->is_deleted;
            $taskDto->createdAt        = $task->created_at;
            $taskDto->updatedAt        = $task->updated_at;
            $taskDto->deletedAt        = $task->deleted_at;
            $taskDto->taskDetail       = $questionDto;
            $taskDto->favoriteId       = $favorite_id;


            //Setting field display on API
            $aryDisplayField = [
                'user_task_status',
                'question_set_date',
                'answer_deadline_date',
                'answered_date',
                'user_message_log_id',
                'original_answer',
                'user_feedback_id',
                'comment',
                'is_deleted',
                'created_at',
                'updated_at',
                'deleted_at',
            ];

            $responseItem = new UserTaskDto();

            //Setting custom field not using type slack to camel
            $responseItem->userId       = $user->id;
            $responseItem->taskId       = $userTask->task_id;
            $responseItem->userTaskId   = $userTask->id;
            //$responseItem->userTaskId   = $userTask->id;
            //$responseItem->userInfo     = $user_info;
            $responseItem->feedbackInfo = $user_feedback;
            $responseItem->taskInfo     = $taskDto;
            
            //Set response from ary Setting Field
            foreach ($aryDisplayField as $field) {
                //Convert snack convention to Camel convention
                $camel = CommonUtils::snackToCamel($field);

                //set field to response
                $responseItem->{$camel} = $userTask->{$field};
            }
            $responseItem->setUserTaskStatusName();

            
            //check homework answer
            $homework_answer = DB::table('homework_answers')
                ->where(
                    [
                        ['user_id', '=', $user->id],
                        ['category', '=', CategoryType::HOMEWORK],
                        ['task_type', '=', $task->task_type],
                        ['activity_id', '=', $task->id],
                    ]
                )
                ->first();
            if ($homework_answer) {
                $responseItem->answer = $homework_answer->answer;
            } else {
                $responseItem->answer = null;
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
     * MakeAryOutputGetAllTask function
     *
     * @param [type] $id
     * @param [type] $date
     * @param [type] $start
     * @param [type] $end
     * @param [type] $is_completed
     * @param [type] $type
     * @param [type] $name
     * @return void
     */
    private static function _makeAryOutputGetAllTask($id, $date, $start, $end, $is_completed, $type, $name, $favorite_id)
    {
        return [
            'id'            => $id,
            'date'          => date('m.d', strtotime($date)),
            'date_time'     => date('Y-m-d', strtotime($date)),
            'dayOfWeek'     => date('l', strtotime($date)),
            'name'          => $name,
            'start_time'    => date('H:i', strtotime($start)),
            'end_time'      => date('H:i', strtotime($end)),
            'type'          => $type,
            'is_completed'  => $is_completed,
            'is_lated'      => (!$is_completed && strtotime($end) < time()) ? 1 : 0,
            'favorite_id'   => $favorite_id
        ];
    }

    /**
     * Mapping Type function
     *
     * @return void
     */
    public static function getMappingTaskType()
    {
        $aryMapping = [
            QuestionType::QUESTION_TYPE_GRAMMAR              => QuestionType::QUESTION_TYPE_GRAMMAR_NAME,
            QuestionType::QUESTION_TYPE_VOCABULARY           => QuestionType::QUESTION_TYPE_VOCABULARY_NAME,
            QuestionType::QUESTION_TYPE_WORD_CARD            => QuestionType::QUESTION_TYPE_WORD_CARD_NAME,
            QuestionType::QUESTION_TYPE_LISTENING_B          => QuestionType::QUESTION_TYPE_LISTENING_B_NAME,
            QuestionType::QUESTION_TYPE_LISTENING_C          => QuestionType::QUESTION_TYPE_LISTENING_C_NAME,
            QuestionType::QUESTION_TYPE_LISTENING_D          => QuestionType::QUESTION_TYPE_LISTENING_D_NAME,
            QuestionType::QUESTION_TYPE_SPEAKING_RALLY       => QuestionType::QUESTION_TYPE_SPEAKING_RALLY_NAME,
            QuestionType::QUESTION_TYPE_RV_LESSON            => QuestionType::QUESTION_TYPE_RV_LESSON_NAME,
            QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON => QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON_NAME,
            QuestionType::QUESTION_TYPE_REVIEW_LESSON        => QuestionType::QUESTION_TYPE_REVIEW_LESSON_NAME,
            QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR     => QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR_NAME,
            QuestionType::QUESTION_TYPE_REVIEW_SERVICE       => QuestionType::QUESTION_TYPE_REVIEW_SERVICE_NAME,
        ];

        return $aryMapping;
    }

    /**
     * Mapping Type function
     *
     * @return void
     */
    public static function getIconMappingTaskType()
    {
        $aryMapping = [
            QuestionType::QUESTION_TYPE_GRAMMAR              => QuestionType::QUESTION_TYPE_GRAMMAR_ICON,
            QuestionType::QUESTION_TYPE_VOCABULARY           => QuestionType::QUESTION_TYPE_VOCABULARY_ICON,
            QuestionType::QUESTION_TYPE_WORD_CARD            => QuestionType::QUESTION_TYPE_WORD_CARD_ICON,
            QuestionType::QUESTION_TYPE_LISTENING_B          => QuestionType::QUESTION_TYPE_OTHER_05_ICON,
            QuestionType::QUESTION_TYPE_LISTENING_C          => QuestionType::QUESTION_TYPE_OTHER_06_ICON,
            QuestionType::QUESTION_TYPE_LISTENING_D          => QuestionType::QUESTION_TYPE_OTHER_07_ICON,
            QuestionType::QUESTION_TYPE_SPEAKING_RALLY       => QuestionType::QUESTION_TYPE_SPEAKING_RALLY_ICON,
            QuestionType::QUESTION_TYPE_RV_LESSON            => QuestionType::QUESTION_TYPE_RV_LESSON_ICON,
            QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON => QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON_ICON,
            QuestionType::QUESTION_TYPE_REVIEW_LESSON        => QuestionType::QUESTION_TYPE_REVIEW_LESSON_ICON,
            QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR     => QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR_ICON,
            QuestionType::QUESTION_TYPE_REVIEW_SERVICE       => QuestionType::QUESTION_TYPE_REVIEW_SERVICE_ICON,
        ];

        return $aryMapping;
    }

    /**
     * Get Task name by type
     *
     * @param  integer $taskType Task type
     * @return string
     */
    public static function getTaskTypeName($taskType)
    {
        $aryMapping = self::getMappingTaskType();

        if (isset($aryMapping[$taskType])) {
            return $aryMapping[$taskType];
        }

        return '';
    }

    /**
     * Get Task name by type
     *
     * @param integer $taskType Task type
     * 
     * @return string
     */
    public static function getTaskTypeIcon($taskType)
    {
        $aryMapping = self::getIconMappingTaskType();

        if (isset($aryMapping[$taskType])) {
            return $aryMapping[$taskType];
        }

        return '';
    }

    /**
     * GetMoreListeningTask function
     *
     * @param [type] $taskType 
     * 
     * @return void
     */
    public static function getMoreListeningTask($user)
    {
        //get user score listening
        $user_score_json = DB::table('user_scores')
            ->where('user_id', $user->id)
            ->whereNotNull('result')    //アセスメント情報が格納されていて...
            ->where('result', '<>', '') //アセスメント情報が格納されていて...
            ->whereNotNull('assessment_ended_at')    //アセスメント日付が格納されていて...
            ->orderby('assessment_ended_at', 'desc') //最新のアセスメント情報を取得
            ->orderby('id', 'desc') //同一日付けの場合はより後のレコードを取得する...
            ->first()->result;
        
        $user_score = json_decode($user_score_json, true);

        if (!$user_score) {
            return [];
        }
        //get grade of user
        $grade = '';
        foreach ($user_score as $type) {
            if ($type['category'] == 'Listening') {
                $grade = $type['grade'];
            }
        }

        if (empty($grade)) {
            return [];
        }

        $limit = 0;

        //get listening type
        $listening_type = '';
        $task_type = 0;
        switch ($grade) {
        case 'B':
            $listening_type = 'B';
            $task_type = TaskType::TASK_TYPE_LISTENING_B;
            $limit = NumQuestion::NUM_QUESTION_LISTENING_B;
            break;
        case 'C':
            $listening_type = 'C';
            $task_type = TaskType::TASK_TYPE_LISTENING_C;
            $limit = NumQuestion::NUM_QUESTION_LISTENING_C;
            break;
        case 'D':
            $listening_type = 'D';
            $task_type = TaskType::TASK_TYPE_LISTENING_D;
            $limit = NumQuestion::NUM_QUESTION_LISTENING_D;
            break;
        }

        //get last assigned
        $last_assigned = DB::table('user_listening_assigns')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['listening_type', '=', $task_type],
                ]
            )
            ->orderBy('assigned_date', 'desc')
            ->first();

        if (!$last_assigned) {
            $last_assigned_id = 0;
        } else {
            $last_assigned_id = $last_assigned->assigned_id;
        }
        
        //listening tasks to add
        $listeningTasks = DB::table('listening_tasks')
            ->where('is_deleted', IsDeletedType::ACTIVE)
            ->where('listening_type', $listening_type)
            ->get();
        
        if (!$listeningTasks) {
            return [];
        }

        $dataCnt = 0;
        $listeningTaskIds = [];
        foreach ($listeningTasks as $listeningTask) {
            if ($listeningTask->id <= $last_assigned_id ) {
                continue;
            }
            if ($dataCnt < $limit) {
                $listeningTaskIds[] = $listeningTask->id;
                $dataCnt++;
            }
            if ($dataCnt >= $limit) {
                break;
            }
        }

        if ($dataCnt < $limit) {
            foreach ($listeningTasks as $listeningTask) {
                if (!in_array($listeningTask->id, $listeningTaskIds)) {
                    $listeningTaskIds[] = $listeningTask->id;
                    $dataCnt++;
                }
                if ($dataCnt >= $limit) {
                    break;
                }
            }
        }
        
        if (!$last_assigned) {
            $userListeningAssign = new UserListeningAssign();
            $userListeningAssign->user_id                = $user->id;
            $userListeningAssign->listening_type         = $task_type;
            $userListeningAssign->assigned_id            = end($listeningTaskIds);
            $userListeningAssign->assigned_date          = date('Y-m-d H:i:s');
            $userListeningAssign->is_deleted             = IsDeletedType::ACTIVE;
            $userListeningAssign->created_at             = date('Y-m-d H:i:s');
            $userListeningAssign->save();

        } else {
            $last_assigned = DB::table('user_listening_assigns')
                ->where('id', $last_assigned->id)
                ->update(
                    [
                        'listening_type'    => $task_type,
                        'assigned_id'       => end($listeningTaskIds),
                        'assigned_date'     => date('Y-m-d H:i:s'),
                        'updated_at'        => date('Y-m-d H:i:s'),
                    ]
                );
        }

        $result = [];
        $result['task_type'] = $task_type;
        $result['task_id'] = $listeningTaskIds;

        return $result;
    }

    /**
     * GetMoreWordCardTask function
     *
     * @param [type] $user
     * @return void
     */
    public static function getMoreWordCardTask($user)
    {
        //get user_score
        $user_score_json = DB::table('user_scores')
            ->where('user_id', $user->id)
            ->whereNotNull('result')    //アセスメント情報が格納されていて...
            ->where('result', '<>', '') //アセスメント情報が格納されていて...
            ->whereNotNull('assessment_ended_at')    //アセスメント日付が格納されていて...
            ->orderby('assessment_ended_at', 'desc') //最新のアセスメント情報を取得
            ->orderby('id', 'desc') //同一日付けの場合はより後のレコードを取得する...
            ->first()->result;
        
        $user_score = json_decode($user_score_json, true);

        if (!$user_score) {
            return [];
        }
        //get grade of user
        $grade = '';
        foreach ($user_score as $type) {
            if ($type['category'] == 'Grammar') {
                $grade = $type['grade'];
            }
        }

        if (empty($grade)) {
            return [];
        }

        //get user word card assigns
        $user_word_card_assigns = DB::table('user_word_card_assigns')
            ->where('user_id', $user->id)
            ->get();
        
        $list_category_word_card = [];
        foreach ($user_word_card_assigns as $word_card) {
            if (in_array($word_card->word_card_category_id, $list_category_word_card)) {
                $list_category_word_card = null;
            }
            $list_category_word_card[] = $word_card->word_card_category_id;
        }

        $priority = 0;

        if ($user_word_card_assigns && isset($user_word_card_assigns[0])) {
            $assignTmp = DB::table('word_card_categories')->find($user_word_card_assigns[0]->word_card_category_id);

            if ($assignTmp) {
                $priority = $assignTmp->priority;
            }
        }

        //get word card category
        $wordCardcategory = DB::table('word_card_categories')
            ->where('grades', 'like', '%' . $grade . '%')
            ->whereNotIn('id', $list_category_word_card)
            ->where('priority', '>', $priority)
            ->orderBy('priority', 'ASC')
            ->first();

        //check not categories reset to category first
        if (!$wordCardcategory) {
            $wordCardcategory = DB::table('word_card_categories')
                ->where('grades', 'like', '%' . $grade . '%')
                ->orderBy('priority', 'ASC')
                ->first();
        }

        //check not any categories
        if (!$wordCardcategory) {
            return null;
        }

        //get word card tasks
        $list_word_card = DB::table('word_cards')
            ->where('word_card_category_id', $wordCardcategory->id)
            ->get();
        
        //check update or edit
        if (DB::table('user_word_card_assigns')->where(['user_id' => $user->id])->count()) {
            DB::table('user_word_card_assigns')->where(['user_id' => $user->id])
                ->update([
                    'word_card_category_id' => $wordCardcategory->id,
                    'assigned_date' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } else {
            $insert_word_card_assigns = [
                'user_id' => $user->id,
                'word_card_category_id' => $wordCardcategory->id,
                'assigned_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            ];

            DB::table('user_word_card_assigns')->insert($insert_word_card_assigns);
        }

        //set up list task_id
        $list_task_id = [];        
        foreach ($list_word_card as $word_card) {
            $list_task_id[] = $word_card->id;
        }

        return $list_task_id;
    }

    /**
     * GetFavorite function
     *
     * @param [type] $user 
     * @param [type] $task_id 
     * @param [type] $category 
     * 
     * @return void
     */
    public static function getFavorite($user, $task_id, $category)
    {
        //get favorite
        $favorite = DB::table('favorites')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['activity_id', '=', $task_id],
                    ['category', '=', $category],
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
     * InsertTask function
     *
     * @param [type] $list_id 
     * @param [type] $user 
     * @param [type] $task_type 
     * 
     * @return void
     */
    public static function insertTask($list_id, $user, $task_type)
    {
        $data_insert_task = [];
        foreach ($list_id as $key => $id) {
            $task_item = [];
            $task_item['user_id'] = $user->id;
            $task_item['task_type'] = $task_type;
            $task_item['cand_task_id'] = $id;
            $task_item['is_selected'] = TaskSelectedType::RESERVED;
            $task_item['task_order'] = ($key + 1);
            $task_item['created_at'] = date('Y-m-d H:i:s');
            $task_item['updated_at'] = date('Y-m-d H:i:s');
            $data_insert_task[] = $task_item;
        }

        $success = DB::table('tasks')->insert($data_insert_task);

        //get list task inserted
        $list_task = DB::table('tasks')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['task_type', '=', $task_type]
                ]
            )
            ->orderBy('id', 'DESC')
            ->limit(count($data_insert_task))
            ->get()->toArray();

        
        array_reverse($list_task);
        
        return $list_task;
    }

    /**
     * AddUserTask function
     *
     * @param [type] $listTask
     * @param [type] $user
     * @return void
     */
    public static function addUserTask($listTask, $user)
    {
        //setup time for user task
        $question_set_date = date("Y-m-d H:i:s");
        $limit_days = \Config::get('app.answer_limit_days');
        $answer_deadline_date = date("Y-m-d H:i:s", strtotime($question_set_date . '+' . $limit_days .'days'));

        //Get task_period_order
        $task_period_order = 1;
        $task_period_order_max = UserTask::whereRaw('DATE_FORMAT(question_set_date, "%Y-%m-%d") = ?', [date('Y-m-d')])
            ->selectRaw('max(task_period_order) as max')->first();

        if ($task_period_order_max) {
            $task_period_order = $task_period_order_max->max + 1;
        }

        //Check max okawari 
        //2018.10.26 Update not check max task_period_order_max
        /*
        if ($task_period_order_max->max >= 5) {
            return [];
        }
        */

        //Get day_number
        $day_number = date('N');

        //setup data insert
        $dataInsert = [];
        $dataInsertPeriod = [];
        $list_task_id = [];
        foreach ($listTask as $task) {
            $insertItem = [];
            $insertItem['user_id'] = $user->id;
            $insertItem['task_id'] = $task->id;
            $insertItem['user_task_status'] = UserTaskStatusType::TASK_COMPLETED;
            $insertItem['question_set_date'] = $question_set_date;
            $insertItem['answer_deadline_date'] = $answer_deadline_date;
            $insertItem['is_completed'] = 0;
            $insertItem['task_period_order'] = $task_period_order;
            $insertItem['day_number'] = $day_number;
            $insertItem['task_type'] = $task->task_type;
            $insertItem['created_at'] = date('Y-m-d H:i:s');
            $insertItem['updated_at'] = date('Y-m-d H:i:s');
            $dataInsert[] = $insertItem;
            $list_task_id[] = $task->id;

            unset($insertItem['user_task_status']);
            unset($insertItem['question_set_date']);
            unset($insertItem['answer_deadline_date']);
            unset($insertItem['is_completed']);

            $dataInsertPeriod[] = $insertItem;
        }

        if (!$dataInsert) {
            return [];
        }

        try {
            $list_id = UserTask::insert($dataInsert);
            UserTaskPeriod::insert($dataInsertPeriod);
        } catch (Exception $e) {
            DB::rollBack();
            return [];
        }

        //update select in tasks
        $update_data = [
            'is_selected' => TaskSelectedType::SELECTED
        ];

        $update = DB::table('tasks')->whereIn('id', $list_task_id)->update($update_data);

        //get first user_task in list added
        $first_add = UserTask::where(
            [
                ['user_id', '=', $user->id],
                ['task_id', '=', $dataInsert[0]['task_id']],
                ['user_task_status', '=', UserTaskStatusType::TASK_COMPLETED]
            ]
        )->first();

        return $first_add->task_id;
    }

    /**
     * GetMoreSRTask function
     *
     * @param [type] $user 
     * 
     * @return void
     */
    public static function getMoreSRTask($user)
    {
        //get last assigned
        $last_assigned = DB::table('user_sr_assigns')
            ->where('user_id', $user->id)
            ->orderBy('assigned_id', 'DESC')
            ->first();
        
        if ($last_assigned) {
            //get new task
            $task = DB::table('speaking_rallies')
                ->where('id', '>', $last_assigned->assigned_id)
                ->first();
        } else {
            //get new task
            $task = DB::table('speaking_rallies')->first();
        }

        //insert to user sr assign
        //check insert or update
        $checkExist = DB::table('user_sr_assigns')->where(['user_id' => $user->id, 'category' => $task->sr_category_id])->count();

        if ($checkExist) {
            DB::table('user_sr_assigns')->where(['user_id' => $user->id, 'category' => $task->sr_category_id])
                    ->update(
                        [
                            'assigned_id' => $task->id,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'assigned_date' => date('Y-m-d H:i:s')
                        ]);
        } else {
            DB::table('user_sr_assigns')
            ->insert(
                [
                    'user_id' => $user->id,
                    'category' => $task->sr_category_id,
                    'assigned_id' => $task->id,
                    'assigned_date' => date('Y-m-d H:i:s'),
                ]
            );
        }
        
        return [$task->id]; 
    }

    private static function makeRvLessonTaskDataTest($aryLesson, $user_id) {
        //Make data test
        foreach ($aryLesson as $key => $value) {
            $aryData = [
                'user_id' => $user_id,
                'activity_id' => $value,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $rv_id = DB::table('rv_tasks')->insertGetId($aryData);

            //Make table task
            $aryData = [
                'user_id' => 89,
                'task_type' => TaskType::TASK_TYPE_RV_LESSON,
                'cand_task_id' => $rv_id,
                'task_order' => 1,
                'is_selected' => TaskSelectedType::RESERVED,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $ret = DB::table('tasks')->insertGetId($aryData);

            $aryData = [
                'user_id' => 89,
                'task_id' => $ret,
                'task_type' => TaskType::TASK_TYPE_RV_LESSON,
                'day_number' => date('N'),
                'task_period_order' => 99,
                'question_set_date' => date('Y-m-d H:i:s'),
                'answer_deadline_date' => date('Y-m-d H:i:s', strtotime("+2 day", time())),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $ret = DB::table('user_tasks')->insert($aryData);
        }
    }
}