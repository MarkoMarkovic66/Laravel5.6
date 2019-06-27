<?php
namespace App\Services\Favorite;

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
use App\Enums\QuestionType;
use App\Enums\IsDeletedType;
use App\Enums\LessonType;
use App\Enums\TaskType;
use App\Enums\CategoryType;
use App\Enums\IsReviewType;

use App\Services\Lesson\LessonService;
use App\Services\TaskService;

use App\Models\Lesson;
use App\Models\Reserve;

use App\Utils\CommonUtils;
use Lang;
use DB;

use Validator;

/**
 * LessonService class
 */
class FavoriteService
{
    /**
     * __construct
     */
    public function __construct()
    {

    }

    /**
     * GetFavorite function
     *
     * @param array $params
     * @return void
     */
    public static function getFavorite()
    {
        //get user loging
        $user = Auth::user();

       /*  //validate params
        $validate = Validator::make(
            $params,
            [
                'task_type' => 'required|integer',
                'category' => 'required|integer|max:2|min:1'
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
        } */

        //check task type
       /*  $ary_task_type = [1, 2, 13, 40, 50, 51, 52, 30, 70, 71, 72];
        if ($params['category'] == 2 && !in_array($params['task_type'], $ary_task_type)) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'MSG' => 'Your task type not found'
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        } */

        //get list favorite is lesson
        $favorites_lesson = DB::table('favorites')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['category', '=', CategoryType::LESSON],
                    ['is_deleted', '=', IsDeletedType::ACTIVE],
                ]
            )
            ->get();

        $favorite = [];
        if ($favorites_lesson) {
            foreach ($favorites_lesson as $lesson) {
                $t = Lesson::where('id', $lesson->activity_id)->first();
                if ($t == null) {
                    continue;
                }

                //get lesson detail
                $lesson_detail = LessonService::getLessonDetail(['lesson_id' => $lesson->activity_id])->responses;
                $detail = [];
                if (isset($lesson_detail['Lesson'])) {
                    $detail = $lesson_detail['Lesson'];
                    $detail->is_review = IsReviewType::INREVIREW;
                };

                $favorite[] = self::_makeAryOutputGetAllTask(
                    $t->id,
                    $t->start_at,
                    $t->start_at,
                    $t->end_at,
                    0,
                    'LESSON',
                    'Lesson',
                    $lesson,
                    $detail
                );

            }
        }
        

        //get list favorite is homwork
        $favorites_homewwork = DB::table('favorites')
            ->where(
                [
                    ['user_id', '=', $user->id],
                    ['category', '=', CategoryType::HOMEWORK],
                    ['is_deleted', '=', IsDeletedType::ACTIVE],
                ]
            )
            ->get();
        
        if ($favorites_homewwork) {
            foreach ($favorites_homewwork as $homewwork) {
                $t = null;
                $task_detail = null;
                if ($homewwork->task_type == TaskType::TASK_TYPE_RV_LESSON) {
                    $rv_task = DB::table('rv_tasks')
                        ->where(
                            [
                                ['activity_id', '=', $homewwork->activity_id],
                                ['user_id', '=', $user->id]
                            ]
                        )
                        ->first();

                    if ($rv_task) {
                        $task = DB::table('tasks')
                            ->where(
                                [
                                    ['cand_task_id', '=', $rv_task->id],
                                    ['task_type', '=', $homewwork->task_type],
                                    ['user_id', '=', $user->id],
                                ]
                            )
                            ->first();

                        if ($task) {
                            $t = DB::table('user_tasks')
                                ->where(
                                    [
                                        ['task_id', '=', $task->id],
                                        ['task_type', '=', $homewwork->task_type],
                                        ['user_id', '=', $user->id]
                                    ]
                                )
                                ->first();
                        }
                    }

                    $lesson_detail = LessonService::getLessonDetail(['lesson_id' => $homewwork->activity_id])->responses;
                    if (array_key_exists('Lesson', $lesson_detail)) {
                        $task_detail = $lesson_detail['Lesson'];
                    }
                    
                } else {
                    $t = DB::table('user_tasks')
                        ->where(
                            [
                                ['task_id', '=', $homewwork->activity_id],
                                ['user_id', '=', $user->id]
                            ]
                        )
                        ->first();

                    
                    if ($t) {
                        //get task
                        $task = DB::table('tasks')
                            ->where(
                                [
                                    ['id', '=', $homewwork->activity_id],
                                    ['user_id', '=', $user->id],
                                ]
                            )
                            ->first();
                        
                        if ($task) {
                            //set table task detail
                            $table = null;
                            $taskTypeGramOrVoca = null;
                            switch ($homewwork->task_type) {
                            case TaskType::TASK_GRAMMAR:
                                $table = 'grammars';
                                $taskTypeGramOrVoca = 1;
                                break;
                            case TaskType::TASK_VOCABULARY:
                                $table = 'vocabularies';
                                $taskTypeGramOrVoca = 2;
                                break;
                            case TaskType::TASK_TYPE_WORD_CARD:
                                $table = 'word_cards';
                                break;
                            case TaskType::TASK_TYPE_LISTENING_B:
                                $table = 'listening_tasks';
                                break;
                            case TaskType::TASK_TYPE_LISTENING_C:
                                $table = 'listening_tasks';
                                break;
                            case TaskType::TASK_TYPE_LISTENING_D:
                                $table = 'listening_tasks';
                                break;
                            case TaskType::TASK_SPEAKING_RALLY:
                                $table = 'speaking_rallies';
                                break;
                            }

                            //get task detail\
                            if ($homewwork->task_type != TaskType::TASK_TYPE_RV_LESSON) {
                                $task_detail = DB::table($table)
                                    ->where('id', '=', $task->cand_task_id)
                                    ->first();


                                if ($homewwork->task_type == TaskType::TASK_GRAMMAR || $homewwork->task_type == TaskType::TASK_VOCABULARY) {
                                    $user_task = DB::table('user_tasks')
                                        ->where(
                                            [
                                                ['task_id', '=', $homewwork->activity_id],
                                                ['user_id', '=', $user->id],
                                            ]
                                        )
                                        ->whereIn('task_type', [TaskType::TASK_GRAMMAR, TaskType::TASK_VOCABULARY])
                                        ->first();
                
                
                                    $user_task_first = DB::table('user_tasks')
                                        ->where(
                                            [
                                                ['question_set_date', '=', date('Y-m-d H:i:s', strtotime($user_task->question_set_date))],
                                                ['user_id', '=', $user->id],
                                                ['task_type', '=', $user_task->task_type],
                                            ]
                                        )
                                        ->first();
                
                                    $soundFile = DB::table('sound_files')
                                        ->where(
                                            [
                                                ['activity_id', '=', $user_task_first->task_id],
                                                ['user_id', '=', $user->id],
                                            ]
                                        )
                                        ->first();
                
                                    if (isset($soundFile)) {
                                        $task_detail->file_url = $soundFile->file_url;
                                    }
                                }
                            }
                        }
                        
                        //get feedBack
                        $user_feedback = null;
                        if ($homewwork->task_type == TaskType::TASK_TYPE_LISTENING_B || $homewwork->task_type == TaskType::TASK_TYPE_LISTENING_C || $homewwork->task_type == TaskType::TASK_TYPE_LISTENING_D || $homewwork->task_type == TaskType::TASK_SPEAKING_RALLY) {
                            $task_service = new TaskService();
                            $user_feedback = $task_service->getFeedbackInfo($t);
                        }

                        if ($homewwork->task_type == TaskType::TASK_SPEAKING_RALLY) {
                            //get sr category
                            $sr_cat = DB::table('sr_categories')
                                ->where('id', $task_detail->sr_category_id)
                                ->first();

                            $task_detail->srCategoryName = $sr_cat->category_name;
                        }
                    }
                }

                if ($t != null && $task_detail != null) {
                    $favorite[] = self::_makeAryOutputGetAllTask(
                        $homewwork->activity_id,
                        $t->question_set_date,
                        $t->question_set_date,
                        $t->answer_deadline_date,
                        $t->is_completed,
                        $homewwork->task_type,
                        self::getTaskTypeName($homewwork->task_type),
                        $homewwork,
                        $task_detail,
                        $user_feedback
                    );
                }
                

            }
        }
        
        //result
        $response = [];
        
        $response['data'] = $favorite;
        
        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );

        return $result;
    }

    /**
     * PutFavorite function
     *
     * @param [type] $params 
     * 
     * @return void
     */
    public static function putFavorite($params = array())
    {
        
        //get user loging
        $user = Auth::user();

        //validate params
        $validate = Validator::make(
            $params,
            [
                'category' => 'required|integer|max:2|min:1',
                'activity_id' => 'required|integer'
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

        //check task type
        $ary_task_type = [
            TaskType::TASK_GRAMMAR,
            TaskType::TASK_VOCABULARY,
            TaskType::TASK_SPEAKING_RALLY,
            TaskType::TASK_TYPE_WORD_CARD,
            TaskType::TASK_TYPE_LISTENING_B,
            TaskType::TASK_TYPE_LISTENING_C,
            TaskType::TASK_TYPE_LISTENING_D,
            TaskType::TASK_TYPE_RV_LESSON,
            TaskType::TASK_TYPE_REVIEW_BEFORE_LESSON,
            TaskType::TASK_TYPE_REVIEW_LESSON,
            TaskType::TASK_TYPE_REVIEW_COUNSELOR,
            TaskType::TASK_TYPE_REVIEW_SERVICE
        ];
        if (!isset($params['task_type']) || $params['category'] == CategoryType::HOMEWORK && !in_array($params['task_type'], $ary_task_type)) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'MSG' => Lang::get('custom.not_task_type')
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        //set up data insset
        $questionnaire1 = null;
        $questionnaire2 = null;
        $answer = null;
        $sound_file_id1 = null;
        $sound_file_id2 = null;
        $task_type = (isset($params['task_type'])) ? $params['task_type'] : null;

        //get answer
        if (isset($params['answer'])) {
            $answer = $params['answer'];
        } else {
            if ($params['category'] == CategoryType::LESSON) {
                //get whiteboard
                $param = [
                    'lesson_id' => $params['activity_id'],
                ];
                $whiteboard = LessonService::getLessonWhiteboard($param)->responses;
                if ($whiteboard) {
                    $answer = $whiteboard['whiteboard']->board;
                }
                
            } else {
                //get task
                $task = DB::table('tasks')
                    ->where('id', $params['activity_id'])
                    ->first();
                
                //get task detail
                $answer = self::getFieldFavorite($params['task_type'], 'answer', $user->id, $params['activity_id']);
            }
        }

        if ($params['category'] == CategoryType::HOMEWORK) {
            //get questionnaire 1, get questionnaire 2
            if (isset($params['questionnaire1'])) {
                $questionnaire1 = $params['questionnaire1'];
            } else {
                $questionnaire1 = self::getFieldFavorite($params['task_type'], 'questionnaire', $user->id, $params['activity_id']);
            }
            
            if ($params['task_type'] == TaskType::TASK_TYPE_LISTENING_B || $params['task_type'] == TaskType::TASK_TYPE_LISTENING_C) {
                if (isset($params['questionnaire2'])) {
                    $questionnaire2 = $params['questionnaire2'];
                } else {
                    $questionnaire2 = self::getFieldFavorite($params['task_type'], 'questionnaire', $user->id, $params['activity_id']);
                }

                if (isset($params['questionnaire1'])) {
                    $questionnaire1 = $params['questionnaire1'];
                } else {
                    $questionnaire1 = null;
                }
            }

            //get sound_file_id
            if (isset($params['sound_file_id1'])) {
                $sound_file_id1 = $params['sound_file_id1'];
            } else {
                $sound_file_id1 = self::getFieldFavorite($params['task_type'], 'sound_file_id', $user->id, $params['activity_id']);
            }
            
            if ($params['task_type'] == TaskType::TASK_TYPE_LISTENING_B || $params['task_type'] == TaskType::TASK_TYPE_LISTENING_C) {
                if (isset($params['sound_file_id2'])) {
                    $sound_file_id2 = $params['sound_file_id2'];
                } else {
                    $sound_file_id2 = self::getFieldFavorite($params['task_type'], 'sound_file_id', $user->id, $params['activity_id']);
                }

                if (isset($params['sound_file_id1'])) {
                    $sound_file_id1 = $params['sound_file_id1'];
                } else {
                    $sound_file_id1 = null;
                }
            }
        }

        $id_favorite = null;

        DB::beginTransaction();

        $checkExistFav = DB::table('favorites')
            ->where(
                [
                    ['user_id', $user->id],
                    ['activity_id', $params['activity_id']],
                    ['category', $params['category']],
                    ['is_deleted', IsDeletedType::ACTIVE],
                ]
            )
            ->count();

        try {
            $insert_data = [
                'user_id' => $user->id,
                'category' => $params['category'],
                'task_type' => $task_type,
                'activity_id' => $params['activity_id'],
                'activity_at' => date('Y-m-d H:i:s'),
                'questionnaire1' => $questionnaire1,
                'questionnaire2' => $questionnaire2,
                'answer' => $answer,
                'sound_file_id1' => $sound_file_id1,
                'sound_file_id2' => $sound_file_id2,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            //check exist favorite

           
            if (!$checkExistFav) {
                $id_favorite = DB::table('favorites')->insertGetId($insert_data);
            }

            DB::commit();
            //get data insert item
            $response = DB::table('favorites')->where('id', $id_favorite)->first();

            $result = CommonUtils::apiResultOK(
                [
                    'favorites' => $response
                ],
                CommonUtils::getCommon()
            );

            return $result;
    
        } catch (\Throwable $th) {
            DB::rollBack();

            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'MSG' => Lang::get('custom.not_task_type')
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }
    }

    /**
     * UpdateFavorite function
     *
     * @param [type] $params 
     * 
     * @return void
     */
    public static function updateFavorite($params)
    {
        //get user loging
        $user = Auth::user();

        //validate params
        $validate = Validator::make(
            $params,
            [
                'category' => 'required|integer|max:2|min:1',
                'activity_id' => 'required|integer',
                'favorite_id' => 'required|integer',
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

        //check task type
        $ary_task_type = [
            QuestionType::QUESTION_TYPE_GRAMMAR,
            QuestionType::QUESTION_TYPE_VOCABULARY,
            QuestionType::QUESTION_TYPE_SPEAKING_RALLY,
            QuestionType::QUESTION_TYPE_WORD_CARD,
            QuestionType::QUESTION_TYPE_LISTENING_B,
            QuestionType::QUESTION_TYPE_LISTENING_C,
            QuestionType::QUESTION_TYPE_LISTENING_D,
            QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON,
            QuestionType::QUESTION_TYPE_REVIEW_LESSON,
            QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR,
            QuestionType::QUESTION_TYPE_REVIEW_SERVICE
        ];
        if ($params['category'] == CategoryType::HOMEWORK && (!isset($params['task_type']) || !in_array($params['task_type'], $ary_task_type))) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'MSG' => Lang::get('custom.not_task_type')
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        //get favorite
        $favorite = DB::table('favorites')
            ->where('id', $params['favorite_id'])
            ->first();

        if (!$favorite) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'MSG' => $params['favorite_id']
                ],
                CommonUtils::getCommon()
            );
            
            return $result;
        }

        //set up data insset
        $questionnaire1 = null;
        $questionnaire2 = null;
        $answer = null;
        $sound_file_id1 = null;
        $sound_file_id2 = null;
        $task_type = (isset($params['task_type'])) ? $params['task_type'] : null;

        //get answer
        if (isset($params['answer'])) {
            $answer = $params['answer'];
        } else {
            if ($params['category'] == CategoryType::LESSON) {
                //get whiteboard
                $param = [
                    'lesson_id' => $params['activity_id'],
                ];
                $whiteboard = LessonService::getLessonWhiteboard($param)->responses;
                if ($whiteboard) {
                    $answer = $whiteboard['whiteboard']->board;
                }
                
            } else {
                //get task
                $task = DB::table('tasks')
                    ->where('id', $params['activity_id'])
                    ->first();
                
                //get task detail
                $answer = self::getFieldFavorite($params['task_type'], 'answer', $user->id, $params['activity_id']);
            }
        }

        if ($params['category'] == CategoryType::HOMEWORK) {
            //get questionnaire 1, get questionnaire 2
            if (isset($params['questionnaire1'])) {
                $questionnaire1 = $params['questionnaire1'];
            } else {
                $questionnaire1 = self::getFieldFavorite($params['task_type'], 'question', $user->id, $params['activity_id']);
            }
            
            if ($params['task_type'] == TaskType::TASK_TYPE_LISTENING_B || $params['task_type'] == TaskType::TASK_TYPE_LISTENING_C) {
                if (isset($params['questionnaire2'])) {
                    $questionnaire2 = $params['questionnaire2'];
                } else {
                    $questionnaire2 = self::getFieldFavorite($params['task_type'], 'question', $user->id, $params['activity_id']);
                }

                if (isset($params['questionnaire1'])) {
                    $questionnaire1 = $params['questionnaire1'];
                } else {
                    $questionnaire1 = null;
                }
            }

            //get sound_file_id
            if (isset($params['sound_file_id1'])) {
                $sound_file_id1 = $params['sound_file_id1'];
            } else {
                $sound_file_id1 = self::getFieldFavorite($params['task_type'], 'sound_file_id', $user->id, $params['activity_id']);
            }
            
            if ($params['task_type'] == TaskType::TASK_TYPE_LISTENING_B || $params['task_type'] == TaskType::TASK_TYPE_LISTENING_C) {
                if (isset($params['sound_file_id2'])) {
                    $sound_file_id2 = $params['sound_file_id2'];
                } else {
                    $sound_file_id2 = self::getFieldFavorite($params['task_type'], 'sound_file_id', $user->id, $params['activity_id']);
                }

                if (isset($params['sound_file_id1'])) {
                    $sound_file_id1 = $params['sound_file_id1'];
                } else {
                    $sound_file_id1 = null;
                }
            }
        }

        $favorite = DB::table('favorites')
            ->where('id', $params['favorite_id'])
            ->update(
                [
                    "user_id" => $user->id,
                    "category" => $params['category'],
                    "task_type" => $task_type,
                    "activity_id" => $params['activity_id'],
                    "activity_at" => date('Y-m-d H:i:s'),
                    "questionnaire1" => $questionnaire1,
                    "questionnaire2" => $questionnaire2,
                    "answer" => $answer,
                    "sound_file_id1" => $sound_file_id1,
                    "sound_file_id2" => $sound_file_id2,
                    "updated_at" => date('Y-m-d H:i:s'),
                ]
            );

        $favorite = DB::table('favorites')
            ->where('id', $params['favorite_id'])
            ->first();

        $result = CommonUtils::apiResultOK(
            [
                'favorites' => $favorite
            ],
            CommonUtils::getCommon()
        );
        return $result;
    }

    /**
     * DeleteFavorite function
     *
     * @param array $params 
     * 
     * @return void
     */
    public static function deleteFavorite($params = array())
    {
        //get user loging
        $user = Auth::user();
        
        //validate params
        $validate = Validator::make(
            $params,
            [
                'favorite_id' => 'required|integer',
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

        //delete favorite
        $favorite = DB::table('favorites')
            ->where('id', $params['favorite_id'])
            ->update(
                [
                    'is_deleted' => IsDeletedType::DELETED,
                    'deleted_at' => date('Y-m-d H:i:s'),
                ]
            );

        $response = [
            'data' => true
        ];

        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );
        return $result;
    }


    /**
     * GetFieldFavorite function
     *
     * @param [type] $task_type 
     * @param [type] $field 
     * @param [type] $user_id 
     * @param [type] $task_id 
     * 
     * @return void
     */
    public static function getFieldFavorite($task_type, $field, $user_id, $task_id)
    {
        //get task
        $task = null;
        $task = DB::table('tasks')
            ->where(
                [
                    ['user_id', '=', $user_id],
                    ['id', '=', $task_id],
                ]
            )
            ->first();
        
        if (!$task) {
            return null;
        }
        
        $task_detail = null;
        if ($field == 'answer') {
            switch ($task_type) {
            case TaskType::TASK_GRAMMAR:
                $task_detail = DB::table('grammars')
                    ->where('id', $task->cand_task_id)
                    ->first();
                break;
            case TaskType::TASK_VOCABULARY:
                $task_detail = DB::table('vocabularies')
                    ->where('id', $task->cand_task_id)
                    ->first();
                break;
            case TaskType::TASK_TYPE_WORD_CARD:
                $task_detail = DB::table('word_cards')
                    ->where('id', $task->cand_task_id)
                    ->first();
                break;
            case TaskType::TASK_TYPE_LISTENING_B:
            case TaskType::TASK_TYPE_LISTENING_C:
            case TaskType::TASK_TYPE_LISTENING_D:
            case TaskType::TASK_SPEAKING_RALLY:
                $task_detail = DB::table('homework_answers')
                    ->where(
                        [
                            ['user_id', '=', $user_id],
                            ['category', '=', CategoryType::HOMEWORK],
                            ['task_type', '=', $task_type],
                            ['activity_id', '=', $task_id],
                        ]
                    )
                    ->first();
                break;
            }
        }

        if ($field == 'questionaire') {
            $task_detail = DB::table('homework_answers')
                ->where(
                    [
                        ['user_id', '=', $user_id],
                        ['category', '=', CategoryType::HOMEWORK],
                        ['task_type', '=', $task_type],
                        ['activity_id', '=', $task_id],
                    ]
                )
                ->first();
        }

        if ($field == 'sound_file_id') {
            $task_detail = DB::table('homework_answers')
                ->where(
                    [
                        ['user_id', '=', $user_id],
                        ['category', '=', CategoryType::HOMEWORK],
                        ['task_type', '=', $task_type],
                        ['activity_id', '=', $task_id],
                    ]
                )
                ->first();
        }

        if ($task_detail != null) {
            $result = $task_detail->{$field};
        } else {
            $result = null;
        }
        
        return $result;
    }

    /**
     * MakeAryOutputGetAllTask function
     *
     * @param [type] $date
     * @param [type] $start
     * @param [type] $end
     * @param [type] $is_completed
     * @param [type] $type
     * @param [type] $name
     * @param [type] $favorite_detail
     * @param [type] $task_detail
     * @param [type] $user_feedback
     * @return void
     */
    private static function _makeAryOutputGetAllTask($id, $date, $start, $end, $is_completed, $type, $name,$favorite_detail, $task_detail, $user_feedback = null)
    {
        return [
            'id' => $id,
            'date' => date('m.d', strtotime($date)),
            'date_time' => date('Y-m-d H:i:s', strtotime($date)),
            'dayOfWeek' => date('l', strtotime($date)),
            'name' => $name,
            'start_time' => date('H:i', strtotime($start)),
            'end_time' => date('H:i', strtotime($end)),
            'type' => $type,
            'is_completed' => $is_completed,
            'is_lated' => (!$is_completed && strtotime($end) < time()) ? 1 : 0,
            'favorite_detail' => $favorite_detail,
            'task_detail' => $task_detail,
            'feedback' => $user_feedback
        ];
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
}