<?php
namespace App\Services;

use App\Enums\IsDeletedType;
use App\Enums\AccountKindType;
use App\Enums\QuestionType;
use App\Enums\UserTaskStatusType;
use App\Enums\UserMessageLogMessageType;
use App\Enums\UserMessageLogCwPostType;
use App\Enums\UserMessageLogConnectType;
use App\Enums\UserMessageLogLinkedType;
use App\Enums\UserTaskStaffType;
use App\Enums\TaskSelectedType;
use App\Enums\TaskType;
use App\Enums\MemberReportGrammarRateColorType;
use App\Enums\UserLearningPolicyTagType;

use App\Models\Account;
use App\Models\LpCategory;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserLearningPolicy;
use App\Models\UserGoal;
use App\Models\UserScore;
use App\Models\UserOutput;
use App\Models\UserLessonLog;
use App\Models\UserSession;
use App\Models\UserTask;
use App\Models\UserTaskStaff;
use App\Models\UserFeedback;
use App\Models\UserMessageLog;
use App\Models\UserTaskPeriod;
use App\Models\Task;
use \App\Models\Grammar;
use \App\Models\Vocabulary;
use App\Models\SpeakingRally;
use App\Models\Review;
use App\Models\OtherTask;
use App\Models\SrTask;
use App\Models\UserCounselor;
use App\Models\MemberReport;
use App\Models\MemberReportChart;
use App\Models\MemberReportGsampleData;
use App\Models\MemberReportGrammarRate;
use App\Models\UserTaskProgress;
use App\Models\UserLessonProgress;
use App\Models\UserTaskCalendar;

use App\Dtos\UserDto;
use App\Dtos\MemberDetailDto;
use App\Dtos\UserLearningPolicyDto;
use App\Dtos\UserGoalDto;
use App\Dtos\UserScoreDto;
use App\Dtos\UserOutputDto;
use App\Dtos\UserOutputHistoryDto;
use App\Dtos\UserLessonLogDto;
use App\Dtos\TaskDto;
use App\Dtos\QuestionDto;
use App\Dtos\UserTaskDto;
use App\Dtos\UserMessageLogDto;
use App\Dtos\CounselorDto;
use App\Dtos\LpCategoryDto;
use App\Dtos\UserTaskProgressDto;
use App\Dtos\UserLessonProgressDto;
use App\Dtos\TaskListPerWeekDto;
use App\Dtos\TaskListPerDayDto;
use App\Dtos\TaskListPerCellDto;
use App\Dtos\TaskListPerQuestionDto;
use App\Dtos\TaskAssignDto;
use App\Dtos\TaskMasterDto;
use App\Dtos\MemberReportDto;

use App\Utils\CommonUtils;
use App\Utils\DateUtils;
use Carbon\Carbon;

use TaskService;
use ChatworkService;
use S3Service;
use Auth;
use DB;
use Log;

/**
 * MemberService
 */
class MemberService {
    /**
     * 宿題検索
     * @param array $loginAccountInfo
     * @param collection $params
     * @return array<UserDto>
     */
    public function doMemberSearch($loginAccountInfo, $params) {
        $memberId        = $params->get('memberId');
        $memberSrId      = $params->get('memberSrId');
        $memberName      = $params->get('memberName');
        $cwRoomId        = $params->get('cwRoomId');
        $cwRoomName      = $params->get('cwRoomName');
        $mail            = $params->get('mail');
        $phoneNumber     = $params->get('phoneNumber');
        $hasMemberReport = $params->get('hasMemberReport');
        $pageNo          = $params->get('pageNo');

        /*
         * ログインアカウント権限による絞り込み
         */
        if( $loginAccountInfo['account_kind_id'] == AccountKindType::ACCOUNT_MANAGER_ID ||
            $loginAccountInfo['account_kind_id'] == AccountKindType::ACCOUNT_SYSTEM_OPERATOR_ID ){
            //全レコードが対象となる
            $myQuery = User::from('users as usr')
                        ->select(['usr.id as userId', 'usr.*'])
                        ->where('usr.is_deleted', IsDeletedType::ACTIVE);

        }elseif( $loginAccountInfo['account_kind_id'] == AccountKindType::ACCOUNT_COUNSELOR_ID){
            //当該カウンセラーが担当する会員
            $myQuery = User::from('users as usr')
                        ->join('user_counselors as usrcns', 'usrcns.user_id', 'usr.id')
                        ->select(['usr.id as userId', 'usr.*'])
                        ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                        ->where('usrcns.is_deleted', IsDeletedType::ACTIVE)
                        ->where('usrcns.account_id', $loginAccountInfo['id']);

        }elseif( $loginAccountInfo['account_kind_id'] == AccountKindType::ACCOUNT_OPERATOR_ID){
            //当該オペレータが担当する会員
            $myQuery = User::from('users as usr')
                        ->select(['usr.id as userId', 'usr.*'])
                        ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                        ->whereIn('usr.id', function ($q) use($loginAccountInfo){
                            $q->select(DB::raw('DISTINCT user_id'))
                                ->from('user_task_staffs as usrtskstf')
                                ->where('usrtskstf.is_deleted', IsDeletedType::ACTIVE)
                                ->where('usrtskstf.staff_type', UserTaskStaffType::USER_TASK_STAFF_OPERATOR)
                                ->where('usrtskstf.account_id', $loginAccountInfo['id']);
                        });

        }else{
            throw new Exception('account_kind_id error');
        }
        /*
         * 検索条件による絞り込み
         */
        if(!empty($memberId)){
            $myQuery = $myQuery
                        ->where('usr.arugo_user_id', 'LIKE', '%'.$memberId.'%');
        }
        if(!empty($memberSrId)){
            $myQuery = $myQuery
                        ->where('usr.sr_user_id', 'LIKE', '%'.$memberSrId.'%');
        }
        if(!empty($memberName)){
            $myQuery = $myQuery
                        ->where(function($q) use($memberName){
                            $q->orWhere('first_name', 'LIKE', '%'.$memberName.'%')
                              ->orWhere('last_name', 'LIKE', '%'.$memberName.'%')
                              ->orWhere('first_name_en', 'LIKE', '%'.$memberName.'%')
                              ->orWhere('last_name_en', 'LIKE', '%'.$memberName.'%');
                        });
        }
        if(!empty($cwRoomId)){
            $myQuery = $myQuery
                        ->where('usr.cw_room_id', 'LIKE', '%'.$cwRoomId.'%');
        }
        if(!empty($cwRoomName)){
            $myQuery = $myQuery
                        ->where('usr.cw_room_name', 'LIKE', '%'.$cwRoomName.'%');
        }
        if(!empty($mail)){
            $myQuery = $myQuery
                        ->where('usr.mail', 'LIKE', '%'.$mail.'%');
        }
        if(!empty($phoneNumber)){
            $myQuery = $myQuery
                        ->where('usr.phone_number', 'LIKE', '%'.$phoneNumber.'%');
        }
        if($hasMemberReport){
            $myQuery = $myQuery
                        ->whereExists(function ($q) {
                            $q->select(DB::raw(1))
                                ->from('member_reports as mr')
                                ->whereRaw('mr.user_id = usr.id');
                        });
        }

        //$sql = $myQuery->toSql();

        //総件数取得
        $totalRowCount = $myQuery->count();
        //対象ページ指定からskip/take算出
        if($pageNo < 1){
            $pageNo = 1;
        }
        $take = 100; // 100行/ページ
        $skip = ($pageNo -1) * $take;

        //総ページ数算出
        $totalPageCount = intdiv($totalRowCount, $take);
        if(($totalRowCount % $take) > 0){
            $totalPageCount += 1;
        }

        //検索実行
        $users = $myQuery
                        ->orderby('arugo_user_id', 'desc')
                        ->skip($skip) //offset
                        ->take($take) //limit
                        ->get();

        $memberList = [];
        if(!empty($users) && $users->count() > 0){
            foreach($users as $user){
                $userDto = new UserDto();

                $userDto->userId		= $user->userId;
                $userDto->arugoUserId   = $user->arugo_user_id;
                $userDto->srUserId      = $user->sr_user_id;
                $userDto->cwRoomId      = empty($user->cw_room_id) ? '': $user->cw_room_id;
                $userDto->cwRoomName    = empty($user->cw_room_name) ? '': $user->cw_room_name;
                $userDto->firstName     = $user->first_name;
                $userDto->lastName      = $user->last_name;
                $userDto->firstNameEn   = $user->first_name_en;
                $userDto->lastNameEn    = $user->last_name_en;
                $userDto->mail          = empty($user->mail) ? '': $user->mail;
                $userDto->phoneNumber   = empty($user->phone_number) ? '': $user->phone_number;
                $userDto->curriculumId  = $user->curriculum_id;
                $userDto->studySec      = $user->study_sec;
                $userDto->status        = $user->status;
                $userDto->matrix		= $user->matrix;
                $userDto->openedAt      = $user->opened_at;
                $userDto->closedAt      = $user->closed_at;
                $userDto->resignedAt    = $user->resigned_at;
                $userDto->accountType   = $user->user_type;
                $userDto->activated     = $user->activated;
                $userDto->quitted       = $user->quitted;
                $userDto->isDeleted     = $user->is_deleted;
                $userDto->createdAt     = $user->created_at;
                $userDto->updatedAt     = $user->updated_at;
                $userDto->deletedAt     = $user->deleted_at;
                $memberList[] = $userDto;
            }
        }
        return ['memberList' => $memberList,
                'totalRowCount' => $totalRowCount,
                'totalPageCount' => $totalPageCount
                ];
    }

    /**
     * 会員基本情報取得
     * @param type $params
     * @return MemberDetailDto
     */
    public function getMemberBasic($params) {
        $memberDetail = new MemberDetailDto();
        //基本情報
        $memberDetail->userBasicInfo = $this->getUserBasicInfo($params);
        return $memberDetail;
    }

    /**
     * 会員詳細情報取得
     * @param type $params
     * @return MemberDetailDto
     */
    public function getMemberDetail($params) {

        $memberDetail = new MemberDetailDto();

        //基本情報
        $memberDetail->userBasicInfo = $this->getUserBasicInfo($params);

        //学習方針
        $memberDetail->userLearningPolicies = $this->getUserLearningPolicies($params);
        //学習方針記録
        $memberDetail->userLearningPolicyLogs = $this->getUserLearningPolicyLogs($params);

        //目標
        $memberDetail->userGoals = $this->getUserGoals($params);

        //アセスメント目標
        $memberDetail->userAssessmentGoals = $this->getUserAssessmentGoals($params);
        //アセスメント記録
        $memberDetail->userAssessmentLogs = $this->getUserAssessmentLogs($params);

        /*
         * 2018-06-07 修正
         * 会員詳細画面の表示が遅いため、
         * 処理に時間を要する下記のデータは初期表示時には表示せず、
         * ページ描画後にajaxにて動的に取得するように変更する。
         *
         * また各データについて「ページ送り対応」とすることで、
         * 1回に取得/表示するデータ量を制限し、
         * これによっても処理に要する時間を短縮するものである。
         *
         * -- ajax化するデータ種別は下記：
         *  ① OUTPUT履歴
         *  ② レッスン受講状況
         *  ③ 宿題実施状況
         *  ④ メッセージ送受信履歴
         */
        /****
        //アウトプット記録 2018-04-04変更
        ////$memberDetail->userOutputLogs = $this->getUserOutputLogs($params);
        $memberDetail->userOutputLogs = $this->getUserOutputHistory($params);
        //レッスン記録
        $memberDetail->userLessonLogs = $this->getUserLessonLogs($params);
        //宿題(タスク)記録
        $memberDetail->userTaskLogs = $this->getUserTaskLogs($params);
        //メッセージ送受信記録
        $memberDetail->userMessageLogs = $this->getUserMessageLogs($params);
        ***/


        //会員レポート発行日付けリスト
        $memberDetail->memberReportIssueDates = $this->getMemberReportIssueDates($params);

        return $memberDetail;
    }

    //基本情報
    private function getUserBasicInfo($params) {
        $dto = new UserDto();
        $userId = $params['userId'];
        $rec = User::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('id', $userId)
                    ->first();
        if(!empty($rec) && $rec->count() > 0){
            $dto->userId		= $rec->id;
            $dto->arugoUserId   = $rec->arugo_user_id;
            $dto->srUserId      = $rec->sr_user_id;
            $dto->cwRoomId      = $rec->cw_room_id;
            $dto->cwRoomName    = $rec->cw_room_name;
            $dto->cwAccountId   = $rec->cw_account_id;
            $dto->firstName     = $rec->first_name;
            $dto->lastName      = $rec->last_name;
            $dto->firstNameEn   = $rec->first_name_en;
            $dto->lastNameEn    = $rec->last_name_en;
            $dto->mail          = $rec->mail;
            $dto->phoneNumber   = $rec->phone_number;
            $dto->curriculumId  = $rec->curriculum_id;
            $dto->studySec      = $rec->study_sec;
            $dto->status        = $rec->status;
            $dto->matrix		= $rec->matrix;
            $dto->openedAt      = $rec->opened_at;
            $dto->closedAt      = $rec->closed_at;
            $dto->resignedAt    = $rec->resigned_at;
            $dto->accountType   = $rec->user_type;
            $dto->activated     = $rec->activated;
            $dto->quitted       = $rec->quitted;
            $dto->isDeleted     = $rec->is_deleted;
            $dto->createdAt     = $rec->created_at;
            $dto->updatedAt     = $rec->updated_at;
            $dto->deletedAt     = $rec->deleted_at;
        }

        //レッスン開始/終了日をセット
        $userPlan = UserPlan::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('user_id', $userId)
                    ->orderby('lesson_start_at', 'desc')
                    ->first();
        if(!empty($userPlan) && $userPlan->count() > 0){
            $dto->lessonPeriodSince = $userPlan->lesson_start_at;
            $dto->lessonPeriodUntil = $userPlan->lesson_end_at;
        }

        return $dto;
    }

    //学習方針
    private function getUserLearningPolicies($params) {
        $userId = $params['userId'];
        $latestPolicies = [];

        //各カテゴリ毎の最新の学習方針関連
        $mysql = 'select ulp.*, mlp.category_name '
                . ' from user_learning_policies as ulp '
                . ' inner join lp_categories mlp on ulp.lp_category_id1 = mlp.id '
                . ' where ulp.id = ( '
                . '     select id '
                . '     from user_learning_policies as ulp2 '
                . '     where ulp.lp_category_id1 = ulp2.lp_category_id1 '
                . '     order by ulp2.posted_at desc '
                . '     limit 1 '
                . ' ) '
                . ' and ulp.is_deleted = 0 '
                . ' and ulp.user_id = ' . $userId
                . ' order by ulp.lp_category_id1 asc ';

        $rows = DB::select($mysql);

        foreach ($rows as $row) {
            $userLearningPolicyDto = new UserLearningPolicyDto();
            $userLearningPolicyDto->id              = $row->id;
            $userLearningPolicyDto->user_id         = $row->user_id;
            $userLearningPolicyDto->posted_at       = empty($row->posted_at) ? '': $row->posted_at;
            $userLearningPolicyDto->policy          = empty($row->policy) ? '': $row->policy;
            $userLearningPolicyDto->comment         = empty($row->comment) ? '': $row->comment;
            $userLearningPolicyDto->lpTagName       = empty($row->tag_name) ? '': $row->tag_name;
            $userLearningPolicyDto->lpCategory1Id   = $row->lp_category_id1;
            $userLearningPolicyDto->lpCategory1Name = empty($row->category_name) ? '': $row->category_name;
            $userLearningPolicyDto->lpCategory2Id   = $row->lp_category_id2;
            $userLearningPolicyDto->setCategory2Name();
            $userLearningPolicyDto->setTags();
            $latestPolicies[] = $userLearningPolicyDto;
        }
        return $latestPolicies;
    }
    //学習方針記録
    private function getUserLearningPolicyLogs($params) {
        $userId = $params['userId'];
        $learningPolicies = [];

        $rows = UserLearningPolicy::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('user_id', $userId)
                                ->orderby('posted_at', 'desc')
                                ->get();
        foreach($rows as $row){
            $userLearningPolicyDto = new UserLearningPolicyDto();
            $userLearningPolicyDto->id              = $row->id;
            $userLearningPolicyDto->user_id         = $row->user_id;
            $userLearningPolicyDto->posted_at       = empty($row->posted_at) ? '': $row->posted_at;
            $userLearningPolicyDto->policy          = empty($row->policy) ? '': $row->policy;
            $userLearningPolicyDto->comment         = empty($row->comment) ? '': $row->comment;
            $userLearningPolicyDto->lpTagName       = empty($row->tag_name) ? '': $row->tag_name;
            $userLearningPolicyDto->lpCategory1Id   = $row->lp_category_id1;
            $userLearningPolicyDto->lpCategory2Id   = $row->lp_category_id2;
            $userLearningPolicyDto->setCategory1Name();
            $userLearningPolicyDto->setCategory2Name();
            $userLearningPolicyDto->setTags();
            $learningPolicies[] = $userLearningPolicyDto;
        }
        return $learningPolicies;
    }

    //目標
    private function getUserGoals($params) {
        $dtos = [];
        $userId = $params['userId'];
        $recs = UserGoal::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('user_id', $userId)
                    ->orderby('id')
                    ->get();
        if(!empty($recs) && $recs->count() > 0){
            foreach($recs as $rec){
                $dto = new UserGoalDto();
                $dto->id		  = $rec->id;
                $dto->user_id     = $rec->user_id;
                $dto->target      = $rec->target;
                $dto->level       = $rec->level;
                $dto->grade       = $rec->grade;
                $dto->goal_limit  = $rec->goal_limit;
                $dto->scene       = $rec->scene;
                $dto->is_deleted  = $rec->is_deleted;
                $dto->created_at  = $rec->created_at;
                $dto->updated_at  = $rec->updated_at;
                $dto->deleted_at  = $rec->deleted_at;
                $dtos[] = $dto;
            }
        }
        return $dtos;
    }

    //アセスメント目標
    private function getUserAssessmentGoals($params) {
        $dtos = [];
        $userId = $params['userId'];
        $recs = UserGoal::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('user_id', $userId)
                    ->orderby('id')
                    ->get();
        if(!empty($recs) && $recs->count() > 0){
            foreach($recs as $rec){
                $dto = new UserGoalDto();
                $dto->id		  = $rec->id;
                $dto->user_id     = $rec->user_id;
                $dto->target      = $rec->target;
                $dto->level       = $rec->level;
                $dto->grade       = $rec->grade;
                $dto->goal_limit  = $rec->goal_limit;
                $dto->scene       = $rec->scene;
                $dto->is_deleted  = $rec->is_deleted;
                $dto->created_at  = $rec->created_at;
                $dto->updated_at  = $rec->updated_at;
                $dto->deleted_at  = $rec->deleted_at;
                $resultArray = [];
                $gradeArrray = CommonUtils::objArray2arr(json_decode($rec->grade));

                $sort_layerId = [];
                $sort_categoryId = [];
                foreach( $gradeArrray as $value ) {
                    $sort_layerId[] = $value['layer_id'];
                    $sort_categoryId[] = $value['category_id'];
                }
                array_multisort( $sort_layerId, SORT_ASC, $sort_categoryId, SORT_ASC, $gradeArrray);
                $sorted = collect($gradeArrray);
                $sorted->each(function($item) use(&$resultArray){
                    $group = $item['layer_id'].'-'.$item['category_id'];
                    $resultArray[] = [
                                'group' => $group,
                                'layer'=>$item['layer_id'],
                                'category'=>$item['category_id'],
                                'grade'=>$item['grade']
                            ];
                });
                $dto->grade_array = $resultArray;

                $dtos[] = $dto;
            }
        }
        return $dtos;
    }

    //アセスメント受講記録
    private function getUserAssessmentLogs($params) {
        $dtos = [];
        $userId = $params['userId'];
        $recs = UserScore::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('user_id', $userId)
                    ->orderby('id')
                    ->get();
        if(!empty($recs) && $recs->count() > 0){
            foreach($recs as $rec){
                $dto = new UserScoreDto();
                $dto->id						= $rec->id;
                $dto->user_id                   = $rec->user_id;
                $dto->alugo_user_id             = $rec->alugo_user_id;
                $dto->assessment_alugo_stuff_id = $rec->assessment_alugo_stuff_id;
                $dto->feedback_alugo_stuff_id   = $rec->feedback_alugo_stuff_id;
                $dto->count                     = $rec->count;
                $dto->level                     = $rec->level;
                $dto->report                    = $rec->report;
                $dto->result                    = $rec->result;
                $dto->assessment_started_at     = DateUtils::convertToYYMMDD($rec->assessment_started_at);
                $dto->assessment_ended_at       = $rec->assessment_ended_at;
                $dto->feedback_started_at       = $rec->feedback_started_at;
                $dto->feedback_ended_at         = $rec->feedback_ended_at;
                $dto->comment                   = $rec->comment;
                $dto->is_deleted                = $rec->is_deleted;
                $dto->created_at                = $rec->created_at;
                $dto->updated_at                = $rec->updated_at;
                $dto->deleted_at                = $rec->deleted_at;

                $resultArray = [];
                $resultCollect = collect(CommonUtils::objArray2arr(json_decode($rec->result)));
                $resultCollect->each(function($item) use(&$resultArray){
                    if($item['category'] == 'Attitude'){
                        $resultArray['Attitude'] = ['category'=>'Attitude', 'grade'=>$item['grade'],'score'=>$item['score']];
                    }elseif($item['category'] == 'Speaking'){
                        $resultArray['Speaking'] = ['category'=>'Speaking', 'grade'=>$item['grade'],'score'=>$item['score']];
                    }elseif($item['category'] == 'Listening'){
                        $resultArray['Listening'] = ['category'=>'Listening', 'grade'=>$item['grade'],'score'=>$item['score']];
                    }elseif($item['category'] == 'Grammar'){
                        $resultArray['Grammar'] = ['category'=>'Grammar', 'grade'=>$item['grade'],'score'=>$item['score']];
                    }elseif($item['category'] == 'Vocabulary'){
                        $resultArray['Vocabulary'] = ['category'=>'Vocabulary', 'grade'=>$item['grade'],'score'=>$item['score']];
                    }
                });
                $dto->result_array = $resultArray;

                $dtos[] = $dto;
            }
        }
        return $dtos;
    }

    /**
     * @deprecated
     * 2018-04-04 これまでのアウトプット記録をgetUserOutputLogsからこちらに変更した
     * アウトプット履歴
     * @param array $params
     * @return UserOutputHistoryDto
     */
    private function getUserOutputHistory($params) {
        $dtos = [];
        $userId = $params['userId'];

        $recs = UserSession::from('user_sessions as ses')
                    ->join('user_lesson_logs as ulg', 'ses.lesson_id', 'ulg.id')
                    ->where('ses.is_deleted', IsDeletedType::ACTIVE)
                    ->where('ulg.is_deleted', IsDeletedType::ACTIVE)
                    ->where('ulg.user_id', $userId)
                    ->select([
                        'ses.id as sesId',
                        'ses.session_id as sesSessionId',
                        'ses.lesson_id as sesLessonId',
                        'ses.feedback_input as sesFeedbackInput',
                        'ulg.id as ulgId',
                        'ulg.user_id as ulgUserId',
                        'ulg.alugo_teacher_id as ulgTeacherId',
                        'ulg.alugo_user_id as ulgStudentId',
                        'ulg.start_at as ulgStartAt',
                        'ulg.end_at as ulgEndAt',
                        'ulg.today_feedback as ulgTodayFeedback',
                        'ulg.approved_at as ulgApprovedAt'
                    ])
                    ->orderby('ulg.start_at', 'desc')
                    ->get();

        if(!empty($recs) && $recs->count() > 0){
            foreach($recs as $rec){

                //空欄のものはskipする
                if( empty($rec->sesFeedbackInput) ||
                    strlen($rec->sesFeedbackInput) < 1 ){
                    continue;
                }

                $dto = new UserOutputHistoryDto();
                $dto->id	          = $rec->sesId;
                $dto->sessionId       = $rec->sesSessionId;
                $dto->studentId       = $rec->ulgStudentId;
                $dto->lessonDateTime  = $rec->ulgStartAt;
                $dto->sentences       = $rec->sesFeedbackInput;
                $dtos[] = $dto;
            }
        }
        return $dtos;
    }
    /**
     * @deprecated
     * アウトプット記録
     * @param array $params
     * @return UserOutputDto
     */
    private function getUserOutputLogs($params) {
        $dtos = [];
        $userId = $params['userId'];
        $recs = UserOutput::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('user_id', $userId)
                    ->orderby('id')
                    ->get();
        if(!empty($recs) && $recs->count() > 0){
            foreach($recs as $rec){
                $dto = new UserOutputDto();
                $dto->id              = $rec->id;
                $dto->user_id         = $rec->user_id;
                $dto->activated_at    = $rec->activated_at;
                $dto->category        = $rec->category;
                $dto->topic           = $rec->topic;
                $dto->original_answer = $rec->original_answer;
                $dto->revised_answer  = $rec->revised_answer;
                $dto->result          = $rec->result;
                $dto->comment         = $rec->comment;
                $dto->is_deleted      = $rec->is_deleted;
                $dto->created_at      = $rec->created_at;
                $dto->updated_at      = $rec->updated_at;
                $dto->deleted_at      = $rec->deleted_at;
                $dtos[] = $dto;
            }
        }
        return $dtos;
    }
    /**
     * @deprecated
     * レッスン記録
     * @param array $params
     * @return UserLessonLogDto
     */
    private function getUserLessonLogs($params) {
        $dtos = [];
        $userId = $params['userId'];
        $recs = UserLessonLog::from('user_lesson_logs as ulg')
                    ->leftjoin('user_questionnaires as uqs', 'uqs.user_id', 'ulg.user_id')
                    ->where('ulg.is_deleted', IsDeletedType::ACTIVE)
                    ->where('uqs.is_deleted', IsDeletedType::ACTIVE)
                    ->where('ulg.user_id', $userId)
                    ->select(['ulg.*', 'uqs.evaluation'])
                    ->orderby('ulg.start_at')
                    ->get();

        if(!empty($recs) && $recs->count() > 0){
            foreach($recs as $rec){
                $dto = new UserLessonLogDto();
                $dto->id                = $rec->id;
                $dto->user_id           = $rec->user_id;
                $dto->alugo_teacher_id  = $rec->alugo_teacher_id;
                $dto->alugo_user_id     = $rec->alugo_user_id;
                $dto->start_at          = $rec->start_at;
                $dto->end_at            = $rec->end_at;
                $dto->report            = $rec->report;
                $dto->lesson_id         = $rec->lesson_id;
                $dto->today_feedback    = $rec->today_feedback;
                $dto->message_user      = $rec->message_user;
                $dto->share_coach       = $rec->share_coach;
                $dto->approved_at       = $rec->approved_at;
                $dto->lesson_type       = $rec->lesson_type;
                $dto->canceled          = $rec->canceled;
                $dto->canceled_sec      = $rec->canceled_sec;
                $dto->request_sec       = $rec->request_sec;
                $dto->is_deleted        = $rec->is_deleted;
                $dto->created_at        = $rec->created_at;
                $dto->updated_at        = $rec->updated_at;
                $dto->deleted_at        = $rec->deleted_at;
                $dto->lesson_evaluation = $rec->evaluation;
                $dto->setLessonEvaluationMark();
                $dtos[] = $dto;
            }
        }
        return $dtos;
    }
    /**
     * @deprecated
     * 宿題(タスク)記録
     * @param array $params
     * @return UserTaskDto
     */
    private function getUserTaskLogs($params) {
        $dtos = [];
        $userId = $params['userId'];
        $recs = UserTask::from('user_tasks as usrtsk')
                ->join('tasks as tsk', 'usrtsk.task_id', 'tsk.id')
                ->where('usrtsk.is_deleted', IsDeletedType::ACTIVE)
                ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                ->whereIn('tsk.task_type', [TaskType::TASK_GRAMMAR, TaskType::TASK_VOCABULARY])
                ->where('usrtsk.user_id', $userId)
                ->select(['usrtsk.id as userTaskId','usrtsk.*'])
                ->get();

        if(!empty($recs) && $recs->count() > 0){
            foreach($recs as $rec){
                $userTaskDto = new UserTaskDto();
                $userTaskDto->userTaskId		  = $rec->id;
                $userTaskDto->userId              = $rec->user_id;
                $userTaskDto->taskId              = $rec->task_id;
                $userTaskDto->userTaskStatus      = $rec->user_task_status;
                $userTaskDto->setUserTaskStatusName();

                $userTaskDto->questionSetDate     = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($rec->question_set_date));
                $userTaskDto->answerDeadlineDate  = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($rec->answer_deadline_date));
                $userTaskDto->answeredDate        = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($rec->answered_date));

                //回答レコードid
                $userTaskDto->userMessageLogId    = $rec->user_message_log_id;

                //カウンセラー情報
                $userTaskDto->counselorInfo = TaskService::getStaffInfo($rec, UserTaskStaffType::USER_TASK_STAFF_COUNSELOR);
                //オペレータ情報
                $userTaskDto->operatorInfo  = TaskService::getStaffInfo($rec, UserTaskStaffType::USER_TASK_STAFF_OPERATOR);

                $userTaskDto->originalAnswer      = $rec->original_answer;
                $userTaskDto->userFeedbackId      = $rec->user_feedback_id;
                $userTaskDto->comment             = $rec->comment;
                $userTaskDto->isDeleted           = $rec->is_deleted;
                $userTaskDto->createdAt           = $rec->created_at;
                $userTaskDto->updatedAt           = $rec->updated_at;
                $userTaskDto->deletedAt           = $rec->deleted_at;
                //ユーザ情報
                $userTaskDto->userInfo            = TaskService::getUserInfo($rec);
                //出題情報
                $userTaskDto->taskInfo            = TaskService::getTaskInfo($rec);
                //フィードバック情報
                $userTaskDto->feedbackInfo        = TaskService::getFeedbackInfo($rec);

                $dtos[] = $userTaskDto;
            }
        }
        return $dtos;
    }

    /**
     * @deprecated
     * メッセージ送受信記録
     * @param array $params
     * @return UserMessageLogDto
     */
    private function getUserMessageLogs($params) {
        $dtos = [];
        $userId = $params['userId'];
        $recs = UserMessageLog::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('user_id', $userId)
                    ->orderby('posted_at', 'desc')
                    ->get();
        if(!empty($recs) && $recs->count() > 0){
            foreach($recs as $rec){
                $dto = new UserMessageLogDto();
                $dto->userMessageLogId  = $rec->id;
                $dto->userId            = $rec->user_id;
                $dto->userName          = '';
                $dto->messageType       = $rec->message_type;
                $dto->cwRoomId          = $rec->cw_room_id;
                $dto->cwRoomName        = $rec->cw_room_name;
                $dto->cwPostType        = $rec->cw_post_type;
                $dto->connectType       = $rec->connect_type;
                $dto->postedName        = $rec->posted_name;
                $dto->postedAt          = $rec->posted_at;
                $dto->deadline          = $rec->deadline;
                $dto->topicCategory     = $rec->topic_category;
                $dto->topicId           = $rec->topic_id;
                $dto->topicStuffId      = $rec->topic_stuff_id;
                $dto->postedContext     = $rec->posted_context;
                $dto->userTaskLinked    = $rec->user_task_linked;
                $dto->comment           = $rec->comment;
                $dto->isDeleted         = $rec->is_deleted;
                $dto->createdAt         = $rec->created_at;
                $dto->updatedAt         = $rec->updated_at;
                $dto->deletedAt         = $rec->deleted_at;
                $dto->setMessageTypeName();
                $dto->setPostTypeName();
                $dto->setConnectTypeName();
                $dtos[] = $dto;
            }
        }
        return $dtos;
    }


    public function updateMemberBasicInfo($params, &$errorMessages) {
        $userId      = $params->get('userId');
        $cwRoomId    = $params->get('cwRoomId');
        $cwAccountId = $params->get('cwAccountId');

        $response = '';

        if(empty($cwRoomId) || !is_numeric($cwRoomId)){
            $cwRoomId = null;
            $cwRoomName = '';
        }else{
            $alueChatworkBaseService = new AlueCommandService\AlueChatworkBaseService();
            $ret = $alueChatworkBaseService->getCwRoomName($cwRoomId, $response);
            if($ret){
                $cwRoomName = $response;
            }else{
                $cwRoomName = '';
            }
        }

        if(empty($cwAccountId) || !is_numeric($cwAccountId)){
            $cwAccountId = null;
        }

        User::where('is_deleted', IsDeletedType::ACTIVE)
            ->where('id', $userId)
            ->update([
                'cw_room_id' => $cwRoomId,
                'cw_room_name' => $cwRoomName,
                'cw_account_id' => $cwAccountId,
                'updated_at' => Carbon::now()
            ]);

        $result = [
            'status' => true,
            'cw_room_id' => $cwRoomId,
            'cw_room_name' => $cwRoomName,
            'cw_account_id' => $cwAccountId,
        ];
        return $result;
    }

    /**
     * 学習方針新規登録
     */
    public function registMemberLearningPolicy($params, &$errorMessages) {

        $userId = $params->get('userId');
        $lpCategory1Id = empty($params->get('lpCategory1Id')) ? null : $params->get('lpCategory1Id');
        $lpCategory2Id = empty($params->get('lpCategory2Id')) ? null : $params->get('lpCategory2Id');
        $lpTag  = strlen($params->get('lpTag'))  > 0  ? $params->get('lpTag')  : null ;
        $policy = strlen($params->get('policy')) > 0  ? $params->get('policy') : null ;
        $remark = strlen($params->get('remark')) > 0  ? $params->get('remark') : null ;

        try {
            //学習方針登録
            $userLearningPolicy = new UserLearningPolicy();
            $userLearningPolicy->user_id    = $userId;
            $userLearningPolicy->posted_at  = Carbon::now();
            $userLearningPolicy->lp_category_id1 = $lpCategory1Id;
            $userLearningPolicy->lp_category_id2 = $lpCategory2Id;
            $userLearningPolicy->tag_name   = $lpTag;
            $userLearningPolicy->policy     = $policy;
            $userLearningPolicy->comment    = $remark;
            $userLearningPolicy->is_deleted = IsDeletedType::ACTIVE;
            $userLearningPolicy->created_at = Carbon::now();
            $userLearningPolicy->save();

        } catch (\Exception $ex) {
            Log::error('error', 'registMemberLearningPolicy:' . $ex->getMessage());
            $errorMessages = $ex->getMessage();
            return false;
        }

        //カテゴリ毎の最新学習方針情報の再取得
        $latestLpDatas = $this->getUserLearningPolicies(['userId' => $userId]);

        //学習方針情報の全件の再取得
        $userLpDatas = $this->getUserLearningPolicyLogs(['userId' => $userId]);

        $result = [
            'status' => true,
            'latestLpDatas' => $latestLpDatas,
            'userLpDatas' => $userLpDatas
        ];

        return $result;

    }

    /**
     * 2018-12-24 (新ロジック)
     * 当該ユーザの出題カレンダー情報を取得する
     * @param array $params
     */
    public function getTaskCalendar($params) {
        $userId = $params['userId'];

        //当該ユーザについての1週間の出題情報
        $taskListPerWeekDto = new TaskListPerWeekDto();
        $taskListPerWeekDto->userId = $userId;
        /***
        $userTaskCalendars = UserTaskCalendar::from('user_task_calendars as usrtskcal')
                            ->join('tasks as tsk', 'usrtskcal.task_id', 'tsk.id')
                            ->select(['usrtskcal.id as userTaskCalId', 'usrtskcal.*', 'tsk.*'])
                            ->where('usrtskcal.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_selected', TaskSelectedType::SELECTED)
                            ->where('usrtskcal.user_id', $userId)
                            ->orderby('usrtskcal.day_number')
                            ->orderby('usrtskcal.task_period_order')
                            ->get();
        ***/
        $userTaskCalendars = UserTaskCalendar::from('user_task_calendars as usrtskcal')
                            ->select(['usrtskcal.id as userTaskCalId', 'usrtskcal.*'])
                            ->where('usrtskcal.is_deleted', IsDeletedType::ACTIVE)
                            ->where('usrtskcal.user_id', $userId)
                            ->orderby('usrtskcal.day_number')
                            ->orderby('usrtskcal.task_period_order')
                            ->get();

        $dayNumber = 0;
        $cellNumber = 0;
        $taskListPerDayDto = new TaskListPerDayDto();
        $taskListPerCellDto = new TaskListPerCellDto();

        foreach($userTaskCalendars as $userTaskCalendar){

            if(empty($dayNumber)){
                //当該曜日のための初期化
                $dayNumber = $userTaskCalendar->day_number;
                $taskListPerDayDto = new TaskListPerDayDto();//1日単位のタスクリスト
                $taskListPerDayDto->dayNumber = $dayNumber;  // 曜日番号( 1:Mon 2:Tue 3:Wed ... )
                $cellNumber = 0; //コマidxを初期化する

            }elseif($dayNumber != $userTaskCalendar->day_number){
                //当該コマについての設問情報を日単位の集合に保持する
                $taskListPerDayDto->taskList[] = $taskListPerCellDto;
                //当該曜日についての設問情報を週単位の集合に保持する
                $taskListPerWeekDto->taskList[] = $taskListPerDayDto;

                //次の曜日のための初期化
                $dayNumber = $userTaskCalendar->day_number;
                $taskListPerDayDto = new TaskListPerDayDto();//1日単位のタスクリスト
                $taskListPerDayDto->dayNumber = $dayNumber;  // 曜日番号( 1:Mon 2:Tue 3:Wed ... )
                $cellNumber = 0; //コマidxを初期化する
            }

            if(empty($cellNumber)){
                //当該のコマ単位のための初期化
                $cellNumber = $userTaskCalendar->task_period_order;
                $taskListPerCellDto = new TaskListPerCellDto(); //1コマ単位のタスクリスト
                $taskListPerCellDto->cellNumber = $cellNumber;  // コマ番号( 1...5 )

                $taskListPerCellDto->taskType = $userTaskCalendar->task_type;//出題ジャンル
                $taskListPerCellDto->setTaskTypeName();//出題ジャンル名

            }elseif($cellNumber != $userTaskCalendar->task_period_order){
                //当該コマについての設問情報を日単位の集合に保持する
                $taskListPerDayDto->taskList[] = $taskListPerCellDto;

                //次のコマ単位のための初期化
                $cellNumber = $userTaskCalendar->task_period_order;
                $taskListPerCellDto = new TaskListPerCellDto(); //1コマ単位のタスクリスト
                $taskListPerCellDto->cellNumber = $cellNumber;  // コマ番号( 1...5 )

                $taskListPerCellDto->taskType = $userTaskCalendar->task_type;//出題ジャンル
                $taskListPerCellDto->setTaskTypeName();//出題ジャンル名
            }

            //1題の出題についての情報を取得してセットする
            //$taskListPerQuestionDto = $this->getQuestionInfo($userTaskCalendar);
            //$taskListPerCellDto->taskList[] = $taskListPerQuestionDto;

        }//foreach

        //Loop後処理
        //当該コマについての設問情報を日単位の集合に保持する
        $taskListPerDayDto->taskList[] = $taskListPerCellDto;
        //当該曜日についての設問情報を週単位の集合に保持する
        $taskListPerWeekDto->taskList[] = $taskListPerDayDto;

        return $taskListPerWeekDto;
    }

    /**
     * @deprecated
     * (旧ロジック)
     * 当該ユーザの出題カレンダー情報を取得する
     * @param array $params
     */
    public function old_getTaskCalendar($params) {
        $userId = $params['userId'];

        //当該ユーザについての1週間の出題情報
        $taskListPerWeekDto = new TaskListPerWeekDto();
        $taskListPerWeekDto->userId = $userId;

        $userTaskPeriods = UserTaskPeriod::from('user_task_periods as usrtskprd')
                            ->join('tasks as tsk', 'usrtskprd.task_id', 'tsk.id')
                            ->select(['usrtskprd.id as userTaskPeriodId', 'usrtskprd.*', 'tsk.*'])
                            ->where('usrtskprd.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_selected', TaskSelectedType::SELECTED)
                            ->where('usrtskprd.user_id', $userId)
                            ->orderby('usrtskprd.day_number')
                            ->orderby('usrtskprd.task_period_order')
                            ->get();
        $dayNumber = 0;
        $cellNumber = 0;
        $taskListPerDayDto = new TaskListPerDayDto();
        $taskListPerCellDto = new TaskListPerCellDto();

        foreach($userTaskPeriods as $userTaskPeriod){

            if(empty($dayNumber)){
                //当該曜日のための初期化
                $dayNumber = $userTaskPeriod->day_number;
                $taskListPerDayDto = new TaskListPerDayDto();//1日単位のタスクリスト
                $taskListPerDayDto->dayNumber = $dayNumber;  // 曜日番号( 1:Mon 2:Tue 3:Wed ... )
                $cellNumber = 0; //コマidxを初期化する

            }elseif($dayNumber != $userTaskPeriod->day_number){
                //当該コマについての設問情報を日単位の集合に保持する
                $taskListPerDayDto->taskList[] = $taskListPerCellDto;
                //当該曜日についての設問情報を週単位の集合に保持する
                $taskListPerWeekDto->taskList[] = $taskListPerDayDto;

                //次の曜日のための初期化
                $dayNumber = $userTaskPeriod->day_number;
                $taskListPerDayDto = new TaskListPerDayDto();//1日単位のタスクリスト
                $taskListPerDayDto->dayNumber = $dayNumber;  // 曜日番号( 1:Mon 2:Tue 3:Wed ... )
                $cellNumber = 0; //コマidxを初期化する
            }

            if(empty($cellNumber)){
                //当該のコマ単位のための初期化
                $cellNumber = $userTaskPeriod->task_period_order;
                $taskListPerCellDto = new TaskListPerCellDto(); //1コマ単位のタスクリスト
                $taskListPerCellDto->cellNumber = $cellNumber;  // コマ番号( 1...5 )

                $taskListPerCellDto->taskType = $userTaskPeriod->task_type;//出題ジャンル
                $taskListPerCellDto->setTaskTypeName();//出題ジャンル名

            }elseif($cellNumber != $userTaskPeriod->task_period_order){
                //当該コマについての設問情報を日単位の集合に保持する
                $taskListPerDayDto->taskList[] = $taskListPerCellDto;

                //次のコマ単位のための初期化
                $cellNumber = $userTaskPeriod->task_period_order;
                $taskListPerCellDto = new TaskListPerCellDto(); //1コマ単位のタスクリスト
                $taskListPerCellDto->cellNumber = $cellNumber;  // コマ番号( 1...5 )

                $taskListPerCellDto->taskType = $userTaskPeriod->task_type;//出題ジャンル
                $taskListPerCellDto->setTaskTypeName();//出題ジャンル名
            }

            //1題の出題についての情報を取得してセットする
            $taskListPerQuestionDto = $this->getQuestionInfo($userTaskPeriod);
            $taskListPerCellDto->taskList[] = $taskListPerQuestionDto;

        }//foreach

        //Loop後処理
        //当該コマについての設問情報を日単位の集合に保持する
        $taskListPerDayDto->taskList[] = $taskListPerCellDto;
        //当該曜日についての設問情報を週単位の集合に保持する
        $taskListPerWeekDto->taskList[] = $taskListPerDayDto;

        return $taskListPerWeekDto;
    }// deprecated

    /**
     * 1題の出題についての情報を取得する
     * @param eloquant $userTaskCalendar
     */
    private function getQuestionInfo($userTaskCalendar){
        $taskListPerQuestionDto = new TaskListPerQuestionDto();

        $taskListPerQuestionDto->userTaskCalId = $userTaskCalendar->userTaskCalId;
        $taskListPerQuestionDto->taskId = $userTaskCalendar->task_id;
        $taskListPerQuestionDto->taskType = $userTaskCalendar->task_type;
        $taskListPerQuestionDto->candidateTaskId = $userTaskCalendar->cand_task_id;
        $taskListPerQuestionDto->question = $this->getQuestionText($userTaskCalendar);

        return $taskListPerQuestionDto;
    }
    /**
     * 1題の出題についての設問文言を取得する
     * @param eloquant $userTaskCalendar
     * 注意
     * eloquant $userTaskCalendarはテーブルがjoinされているので
     * user_ask_calendars以外の項目も含まれることに注意
     *
     * 注意
     * getReserveQuestionInfo から呼び出される場合は、
     * eloquant $userTaskCalendar　ではなく、
     * eloquant $task が渡されるので注意！
     *
     */
    private function getQuestionText($userTaskCalendar){
        $taskType = $userTaskCalendar->task_type;
        $taskId = $userTaskCalendar->cand_task_id;
        $question = '';

        //該当出題の問題文を取得する
        if($taskType == TaskType::TASK_GRAMMAR){
            $row = Grammar::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('id', $taskId)
                                ->first();
            if(!empty($row) && $row->count() > 0){
                $question = $row->question;
            }

        }elseif($taskType == TaskType::TASK_VOCABULARY){
            $row = Vocabulary::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('id', $taskId)
                                ->first();
            if(!empty($row) && $row->count() > 0){
                $question = $row->question;
            }

        }elseif($taskType == TaskType::TASK_SPEAKING_RALLY){
            /***
            $row = SrTask::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('id', $taskId)
                                ->first();
            if(!empty($row) && $row->count() > 0){
                $question = $row->sr_context;
            }***/
            $row = SpeakingRally::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('id', $taskId)
                                ->first();
            if(!empty($row) && $row->count() > 0){
                $question = $row->context;
            }

        }elseif($taskType == TaskType::TASK_REVIEW){
            $row = Review::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('id', $taskId)
                                ->first();
            if(!empty($row) && $row->count() > 0){
                $question = $row->review_context;
            }

        /***
        }elseif($taskType >= TaskType::TASK_OTHERS){
            $row = OtherTask::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('id', $taskId)
                                ->first();
            if(!empty($row) && $row->count() > 0){
                $question = $row->task_context;
            }
        ***/

        }elseif($taskType == TaskType::TASK_TYPE_RV_LESSON){
            //レッスン復習
            $question = 'レッスン復習';

        }elseif($taskType == TaskType::TASK_TYPE_WORD_CARD){
            //単語カード
            $row = WordCard::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('id', $taskId)
                                ->first();
            if(!empty($row) && $row->count() > 0){
                $question = $row->word;
            }

        }elseif( $taskType == TaskType::TASK_TYPE_LISTENING_B ||
                 $taskType == TaskType::TASK_TYPE_LISTENING_C ||
                 $taskType == TaskType::TASK_TYPE_LISTENING_D ){
            //リスニングトレーニング B
            //リスニングトレーニング C
            //リスニングトレーニング D
            $row = ListeningTask::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('id', $taskId)
                                ->first();
            if(!empty($row) && $row->count() > 0){
                $question = $row->hearing_text;
            }
        }//end if

        return $question;
    }

    /**
     * 1題の予備出題についての情報を取得する
     * @param eloquant $task
     */
    private function getReserveQuestionInfo($task){
        $taskListPerQuestionDto = new TaskListPerQuestionDto();

        $taskListPerQuestionDto->userTaskCalId = null;
        $taskListPerQuestionDto->taskId = $task->id;
        $taskListPerQuestionDto->taskType = $task->task_type;
        $taskListPerQuestionDto->candidateTaskId = $task->cand_task_id;
        $taskListPerQuestionDto->question = $this->getQuestionText($task);

        return $taskListPerQuestionDto;
    }


    /**
     * 出題予定問題リスト
     * 指定された会員にアサインされたGrammar/Vocabulary問題一覧を取得する
     * @param type $params
     * @return type
     */
    public function getTaskAssignList($params) {
        $userId = $params['userId'];
        $qType = $params['qType'];

        if($qType == 'Grammar'){
            $taskType = TaskType::TASK_GRAMMAR;
        }elseif($qType == 'Vocabulary'){
            $taskType = TaskType::TASK_VOCABULARY;
        }

        $taskAssignDto = new TaskAssignDto();

        /*
         * 1.出題設定済み問題の取得
         */
        $userTaskPeriods = UserTaskPeriod::from('user_task_periods as prd')
                            ->join('tasks as tsk', 'prd.task_id', 'tsk.id')
                            ->select(['prd.id as userTaskPeriodId', 'prd.*', 'tsk.*'])
                            ->where('prd.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_selected', TaskSelectedType::SELECTED)
                            ->where('prd.task_type', $taskType)
                            ->where('prd.user_id', $userId)
                            ->orderby('prd.day_number')
                            ->orderby('prd.task_period_order')
                            ->orderby('tsk.task_order')
                            ->get();
        $dayNumber = 0;
        $cellNumber = 0;
        $taskListPerDayDto = null;
        $taskListPerCellDto = null;

        if(!empty($userTaskPeriods) && $userTaskPeriods->count() > 0){

            foreach($userTaskPeriods as $userTaskPeriod){

                if(empty($dayNumber)){
                    //当該曜日のための初期化
                    $dayNumber = $userTaskPeriod->day_number;
                    $taskListPerDayDto = new TaskListPerDayDto();//1日単位のタスクリスト
                    $taskListPerDayDto->dayNumber = $dayNumber;  // 曜日番号( 1:Mon 2:Tue 3:Wed ... )
                    $cellNumber = 0; //コマidxを初期化する

                }elseif($dayNumber != $userTaskPeriod->day_number){
                    //当該コマについての設問情報を日単位の集合に保持する
                    $taskListPerCellDto->setTaskCount();
                    $taskListPerDayDto->taskList[] = $taskListPerCellDto;
                    //当該曜日についての設問情報を週単位の集合に保持する
                    $taskListPerDayDto->setTaskCount();
                    $taskAssignDto->selectedTaskList[] = $taskListPerDayDto;

                    //次の曜日のための初期化
                    $dayNumber = $userTaskPeriod->day_number;
                    $taskListPerDayDto = new TaskListPerDayDto();//1日単位のタスクリスト
                    $taskListPerDayDto->dayNumber = $dayNumber;  // 曜日番号( 1:Mon 2:Tue 3:Wed ... )
                    $cellNumber = 0; //コマidxを初期化する
                }

                if(empty($cellNumber)){
                    //当該のコマ単位のための初期化
                    $cellNumber = $userTaskPeriod->task_period_order;
                    $taskListPerCellDto = new TaskListPerCellDto(); //1コマ単位のタスクリスト
                    $taskListPerCellDto->cellNumber = $cellNumber;  // コマ番号( 1...5 )

                    $taskListPerCellDto->taskType = $userTaskPeriod->task_type;//出題ジャンル
                    $taskListPerCellDto->setTaskTypeName();//出題ジャンル名

                }elseif($cellNumber != $userTaskPeriod->task_period_order){
                    //当該コマについての設問情報を日単位の集合に保持する
                    $taskListPerCellDto->setTaskCount();
                    $taskListPerDayDto->taskList[] = $taskListPerCellDto;

                    //次のコマ単位のための初期化
                    $cellNumber = $userTaskPeriod->task_period_order;
                    $taskListPerCellDto = new TaskListPerCellDto(); //1コマ単位のタスクリスト
                    $taskListPerCellDto->cellNumber = $cellNumber;  // コマ番号( 1...5 )

                    $taskListPerCellDto->taskType = $userTaskPeriod->task_type;//出題ジャンル
                    $taskListPerCellDto->setTaskTypeName();//出題ジャンル名
                }

                //1題の出題についての情報を取得してセットする
                $taskListPerQuestionDto = $this->getQuestionInfo($userTaskPeriod);
                $taskListPerCellDto->taskList[] = $taskListPerQuestionDto;

            }//foreach

            //Loop後処理
            //当該コマについての設問情報を日単位の集合に保持する
            $taskListPerCellDto->setTaskCount();
            $taskListPerDayDto->taskList[] = $taskListPerCellDto;
            //当該曜日についての設問情報を全体の集合に保持する
            $taskListPerDayDto->setTaskCount();
            $taskAssignDto->selectedTaskList[] = $taskListPerDayDto;

        }//end of if

        /*
         * 2.出題未設定（予備）問題の取得
         */
        $reserveTasks = Task::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('is_selected', TaskSelectedType::RESERVED)
                            ->where('task_type', $taskType)
                            ->where('user_id', $userId)
                            ->orderby('task_order')
                            ->get();

        foreach($reserveTasks as $reserveTask){
            //1題の出題についての情報を取得してセットする
            $taskListPerQuestionDto = $this->getReserveQuestionInfo($reserveTask);
            $taskAssignDto->reservedTaskList[] = $taskListPerQuestionDto;
        }//foreach

        return $taskAssignDto;
    }

    /**
     * 2018-12-24 旧ロジック
     * @deprecated
     * タスク種別変更
     * @param type $params
     */
    public function old_changeTaskType($params) {

        DB::beginTransaction();
        try {
            $userId = $params['userId'];
            $dayNumber = $params['dayNumber'];
            $cellNumber = $params['cellNumber'];
            $newTaskType = $params['newTaskType'];

            /*
             * 既存登録のタスク周期レコードをすべて論理削除する
             * 同時に、関連するtasksレコードのis_selectedを「0:選択なし」に
             * 更新する。
             */
            $userTaskPeriods = UserTaskPeriod::where('is_deleted', IsDeletedType::ACTIVE)
                                            ->where('user_id', $userId)
                                            ->where('day_number', $dayNumber)
                                            ->where('task_period_order', $cellNumber)
                                            ->get();

            foreach($userTaskPeriods as $userTaskPeriod){
                //関連するtasksレコードの is_selectedを「0:選択なし」に更新
                $taskId = $userTaskPeriod->task_id;
                Task::where('id', $taskId)
                    ->update([
                        'is_selected' => TaskSelectedType::RESERVED,
                        'updated_at'  => Carbon::now()
                    ]);

                //該当UserTaskPeriodを論理削除
                $userTaskPeriod->is_deleted = IsDeletedType::DELETED;
                $userTaskPeriod->updated_at = Carbon::now();
                $userTaskPeriod->save();
            }


            /*
             * 新しいタスク周期レコードを追加登録する
             * ※「なし」に設定した場合は $newTaskType が 0 に設定されていることに注意！
             */
            if( $newTaskType == TaskType::TASK_GRAMMAR     ||
                $newTaskType == TaskType::TASK_VOCABULARY  ){

                /*
                 * TaskType::TASK_GRAMMAR
                 * TaskType::TASK_VOCABULARY
                 * の場合はすでにtasksレコードは生成済みであるため、
                 * タスクをUserTaskPeriodsに追加登録する。
                 */

                //生成するデフォルト件数
                $newTaskNumber = env('TASK_CALENDAR_CREATE_NEW_TASK_NUMBER');

                //追加すべきタスクをtasksより取得
                $newTasks = Task::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('is_selected', TaskSelectedType::RESERVED)
                                ->where('user_id', $userId)
                                ->where('task_type', $newTaskType)
                                ->orderby('task_order')
                                ->skip(0)
                                ->take($newTaskNumber)
                                ->get();

                foreach($newTasks as $newTask){
                    //タスクをUserTaskPeriodsに追加登録する
                    $userTaskPeriod = new UserTaskPeriod();
                    $userTaskPeriod->user_id            = $userId;
                    $userTaskPeriod->day_number         = $dayNumber;
                    $userTaskPeriod->task_type          = $newTaskType;
                    $userTaskPeriod->task_id            = $newTask->id;
                    $userTaskPeriod->task_period_order  = $cellNumber;
                    $userTaskPeriod->comment            = null;
                    $userTaskPeriod->is_deleted         = IsDeletedType::ACTIVE;
                    $userTaskPeriod->created_at         = Carbon::now();
                    $userTaskPeriod->save();

                    //追加したタスクについて is_selectedを「1:選択済み」に更新
                    $newTask->is_selected = TaskSelectedType::SELECTED;
                    $newTask->save();
                }

            }elseif($newTaskType == TaskType::TASK_REVIEW){
                /*
                 * TaskType::TASK_REVIEW
                 * の場合はすでにtasksレコードは7日分が生成済みであるが、
                 * 変則的に、1日に2枠以上の復習を設定する場合は、
                 * 新規にtasksレコードを追加する必要がある。
                 * その後、タスクをUserTaskPeriodsに追加登録する。
                 */
                //追加すべきタスクをtasksより取得
                $newTask = Task::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('is_selected', TaskSelectedType::RESERVED)
                                ->where('user_id', $userId)
                                ->where('task_type', $newTaskType)
                                ->first();

                if(empty($newTask) && $newTask->count() < 1){
                    /*
                     * 追加可能なtasksレコードが無い場合は、
                     * 新規にtasksレコードを追加する。
                     * その後、タスクをUserTaskPeriodsに追加登録する。
                     */
                    $this->createTaskRecord($newTaskType, $userId, $dayNumber, $cellNumber);

                }else{
                    //タスクをUserTaskPeriodsに追加登録する
                    $userTaskPeriod = new UserTaskPeriod();
                    $userTaskPeriod->user_id            = $userId;
                    $userTaskPeriod->day_number         = $dayNumber;
                    $userTaskPeriod->task_type          = $newTaskType;
                    $userTaskPeriod->task_id            = $newTask->id;
                    $userTaskPeriod->task_period_order  = $cellNumber;
                    $userTaskPeriod->comment            = null;
                    $userTaskPeriod->is_deleted         = IsDeletedType::ACTIVE;
                    $userTaskPeriod->created_at         = Carbon::now();
                    $userTaskPeriod->save();

                    //追加したタスクについて is_selectedを「1:選択済み」に更新
                    $newTask->is_selected = TaskSelectedType::SELECTED;
                    $newTask->save();
                }


            }elseif($newTaskType == TaskType::TASK_SPEAKING_RALLY){
                /*
                 * TaskType::TASK_SPEAKING_RALLY
                 * TaskType::TASK_OTHERS
                 * の場合は、該当するtasksレコードは生成してから、
                 * タスクをUserTaskPeriodsに追加登録する。
                 */
                $this->createTaskRecord($newTaskType, $userId, $dayNumber, $cellNumber);

            }elseif($newTaskType >= TaskType::TASK_OTHERS){
                /*
                 * TaskType::TASK_SPEAKING_RALLY
                 * TaskType::TASK_OTHERS
                 * の場合は、該当するtasksレコードは生成してから、
                 * タスクをUserTaskPeriodsに追加登録する。
                 */
                $this->createTaskRecord($newTaskType, $userId, $dayNumber, $cellNumber);
            }

            DB::commit();
            return true;

        } catch(\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
        }
        return false;
    }// 2018-12-24 旧ロジック @deprecated

    /**
     * タスクカレンダー：タスク種別変更
     * @param type $params
     *
     * 2018-12-24 新ロジック
     * user_task_periods から user_task_calendars に変更
     * （注意）
     * 旧ロジックでは、このメソッド内でuser_task_periods, tasksを直接変更していたが
     * 新ロジックでは、user_task_calendarsを変更するだけとなる。
     */
    public function changeTaskType($params) {

        DB::beginTransaction();
        try {
            $userId = $params['userId'];
            $dayNumber = $params['dayNumber'];
            $cellNumber = $params['cellNumber'];
            $newTaskType = $params['newTaskType'];

            //指定された「曜日＋セル」にある既存登録のタスク周期レコードを論理削除する。
            $operationComment = Carbon::now().' 出題カレンダー画面より手動削除: 変更者ID:' . Auth::user()->id;//操作コメント
            UserTaskCalendar::where('user_id', $userId)
                            ->where('day_number', $dayNumber)
                            ->where('task_period_order', $cellNumber)
                            ->update([
                                'is_deleted' => IsDeletedType::DELETED,
                                'updated_at'  => Carbon::now(),
                                'deleted_at'  => Carbon::now(),
                                'comment'     => $operationComment
                            ]);
            /*
             * 指定された「曜日＋セル」に、新しいタスク周期レコードを追加登録する
             * ※「なし」に設定した場合は $newTaskType が 0 に設定されているため、
             * user_task_calendarsにはレコードは追加されないことに注意！
             *
             * ★ここでは user_task_calendars に、user毎にタスク種別の１レコードを追加するだけである。
             * 出題バッチは出題時に、user毎にこのレコードを見て、
             * 「何曜日の何枠目に、どのタスク種別を出題するか」、を判定して出題処理を行う。
             *
             */
            if( $newTaskType == TaskType::TASK_GRAMMAR          || //瞬間口頭英作G
                $newTaskType == TaskType::TASK_VOCABULARY       || //瞬間口頭英作V
                $newTaskType == TaskType::TASK_SPEAKING_RALLY   || //SpeakingRally
                $newTaskType == TaskType::TASK_TYPE_WORD_CARD   || //単語カード
                $newTaskType == TaskType::TASK_TYPE_LISTENING_B || //リスニングトレーニング
                $newTaskType == TaskType::TASK_TYPE_LISTENING_C || //リスニングトレーニング
                $newTaskType == TaskType::TASK_TYPE_LISTENING_D ){ //リスニングトレーニング

                //タスクをUserTaskCalendarsに追加登録する
                $operationComment = Carbon::now().' 出題カレンダー画面より手動追加: 変更者ID:' . Auth::user()->id;//操作コメント
                $userTaskCalendar = new UserTaskCalendar();
                $userTaskCalendar->user_id            = $userId;
                $userTaskCalendar->day_number         = $dayNumber;
                $userTaskCalendar->task_type          = $newTaskType;
                $userTaskCalendar->task_id            = null;
                $userTaskCalendar->task_period_order  = $cellNumber;
                $userTaskCalendar->comment            = $operationComment;
                $userTaskCalendar->is_deleted         = IsDeletedType::ACTIVE;
                $userTaskCalendar->created_at         = Carbon::now();
                $userTaskCalendar->save();
            }

            DB::commit();
            return true;

        } catch(\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
        }
        return false;
    }

    /**
     * 2018-12-24 旧ロジック
     * @deprecated
     * @param type $taskType
     * @param type $targetUserId
     * @param type $targetDayNumber
     * @param type $targetCellNumber
     * @throws \Exception
     */
    private function old_createTaskRecord($taskType, $targetUserId, $targetDayNumber, $targetCellNumber){

        try{
            /*
             * $targetDayNumber, $targetCellNumber の出題枠に、tasksレコードを追加する
             * 注意：
             * ①タスク種別が SR, RVの場合は、cand_task_idは全て 1 となる。
             *
             * ②タスク種別が OT(その他)の場合は、$taskTypeは５以上の値をとるので、
             * この値でother_tasksを引いて、そのidを cand_task_id とする。
             *
             * ※本メソッドはタスク種別が SR、OT(その他)の場合のみ呼び出される
             */
            if($taskType == TaskType::TASK_SPEAKING_RALLY ||
               $taskType == TaskType::TASK_REVIEW ){
                $candTaskId = 1;

            }else{
                // $taskType >= TaskType::TASK_OTHERS
                $otherTask = OtherTask::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('task_type_id', $taskType)
                                        ->first();
                if(!empty($otherTask) && $otherTask->count() > 0){
                    $candTaskId = $otherTask->id;
                }else{
                    $candTaskId = 1;//default
                }
            }

            //tasksレコード生成
            $taskId = Task::insertGetId([
                'user_id' => $targetUserId,
                'task_type' => $taskType,
                'cand_task_id' => $candTaskId,
                'task_order' => 1, // 常に1で良い
                'is_selected' => TaskSelectedType::SELECTED, //選択済み
                'comment' => null,
                'is_deleted' => IsDeletedType::ACTIVE,
                'created_at' => Carbon::now()
            ]);

            //user_task_periodsを追加
            UserTaskPeriod::insert([
                'user_id' => $targetUserId,
                'day_number' => $targetDayNumber,
                'task_type' => $taskType,
                'task_id' => $taskId,
                'task_period_order' => $targetCellNumber,
                'comment' => null,
                'is_deleted' => IsDeletedType::ACTIVE,
                'created_at' => Carbon::now()
            ]);

            Log::info('出題カレンダー：手動変更：create UserTaskPeriod:'
                        . ' :Target User Id:' . $targetUserId
                        . ' :Target Day Number:' . $targetDayNumber
                        . ' :Target Cell Number:' . $targetCellNumber
                        . ' :Target Task Type:' . $taskType
                        . ' :Target Task Id:' . $taskId );

        } catch (\Exception $ex) {
            throw $ex;
        }
    }// 2018-12-24 旧ロジック @deprecated


    /**
     * G/Vモーダル上でのタスク変更
     * @param type $params
     */
    public function modalChangeTask($params) {

        DB::beginTransaction();
        try {
            $qType = $params['qType'];
            $userTaskPeriodId = $params['userTaskPeriodId'];
            $orgTaskId = $params['orgTaskId'];
            $newTaskId = $params['newTaskId'];

            //UserTaskPeriodのtask_idを新しいtasksレコードのidとする
            UserTaskPeriod::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('id', $userTaskPeriodId)
                            ->update([
                                'task_id' => $newTaskId
                            ]);

            //古いtasksレコードの is_selectedを「0:選択なし」に更新
            Task::where('id', $orgTaskId)
                ->update([
                    'is_selected' => TaskSelectedType::RESERVED
                ]);

            //新しいtasksレコードの is_selectedを「1:選択済み」に更新
            Task::where('id', $newTaskId)
                ->update([
                    'is_selected' => TaskSelectedType::SELECTED
                ]);

            DB::commit();
            return true;

        } catch(\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
        }
        return false;
    }

    /**
     * モーダル：マスタから切り替える：マスタ検索
     * @param type $params
     */
    public function searchTaskMaster($params) {
        $search_qtype	  = trim($params['search_qtype']);
        $search_module    = trim($params['search_module']);
        $search_grade     = trim($params['search_grade']);
        $search_stage     = trim($params['search_stage']);
        //$search_tips      = trim($params['search_tips']);
        $search_focus     = trim($params['search_focus']);
        $search_freeword  = trim($params['search_freeword']);

        //使用されていない、かつ予備問題にも登録されていないものを抽出する
        // → tasksに登録されていないもの、または登録があっても無効なもの
        if($search_qtype == 'Grammar'){
            $query = Grammar::from('grammars as grm')
                            ->where('grm.is_deleted', IsDeletedType::ACTIVE)
                            ->whereNotIn('grm.id', function($q){
                                $q->select('cand_task_id')
                                  ->from('tasks as tsk')
                                  ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                                  ->where('tsk.task_type', TaskType::TASK_GRAMMAR);
                            })
                            ->select(['grm.grammar_code as code', 'grm.id as mstId', 'grm.*'])
                            ->orderby('grm.grammar_code');

        }elseif($search_qtype == 'Vocabulary'){
            $query = Vocabulary::from('vocabularies as voc')
                            ->where('voc.is_deleted', IsDeletedType::ACTIVE)
                            ->whereNotIn('voc.id', function($q){
                                $q->select('cand_task_id')
                                  ->from('tasks as tsk')
                                  ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                                  ->where('tsk.task_type', TaskType::TASK_VOCABULARY);
                            })
                            ->select(['voc.vocabulary_code as code', 'voc.id as mstId', 'voc.*'])
                            ->orderby('voc.vocabulary_code');
        }

        if(isset($search_module) && strlen($search_module) > 0){
            $query = $query->where('module', 'LIKE', '%'.$search_module.'%');
        }
        if(isset($search_grade) && strlen($search_grade) > 0){
            $query = $query->where('grade', 'LIKE', '%'.$search_grade.'%');
        }
        if(isset($search_stage) && strlen($search_stage) > 0){
            $query = $query->where('stage', 'LIKE', '%'.$search_stage.'%');
        }
        //if(isset($search_tips) && strlen($search_tips) > 0){
        //    $query = $query->where('tips', 'LIKE', '%'.$search_tips.'%');
        //}
        if(isset($search_focus) && strlen($search_focus) > 0){
            $query = $query->where('focus', 'LIKE', '%'.$search_focus.'%');
        }
        if(isset($search_freeword) && strlen($search_freeword) > 0){
            $query = $query->where(function($q) use($search_freeword){
                        $q->where('module',   'LIKE', '%'.$search_freeword.'%')
                          ->orWhere('grade',  'LIKE', '%'.$search_freeword.'%')
                          ->orWhere('stage',  'LIKE', '%'.$search_freeword.'%')
                          ->orWhere('tips',   'LIKE', '%'.$search_freeword.'%')
                          ->orWhere('focus',  'LIKE', '%'.$search_freeword.'%');
            });
        }

        $result = [];
        $rows = $query->get();
        foreach($rows as $row){
            $taskMasterDto = new TaskMasterDto();
            $taskMasterDto->id			= $row->mstId;
            $taskMasterDto->code        = $row->code;
            $taskMasterDto->module      = $row->module;
            $taskMasterDto->grade       = empty($row->grade)      ? '': $row->grade;
            $taskMasterDto->stage       = empty($row->stage)      ? '': $row->stage;
            $taskMasterDto->seq_number  = empty($row->seq_number) ? '': $row->seq_number;
            $taskMasterDto->focus       = empty($row->focus)      ? '': $row->focus;
            $taskMasterDto->tips        = empty($row->tips)       ? '': $row->tips;
            $taskMasterDto->question    = empty($row->question)   ? '': $row->question;
            $taskMasterDto->answer      = empty($row->answer)     ? '': $row->answer;
            $taskMasterDto->branch      = empty($row->branch)     ? '': $row->branch;
            $taskMasterDto->is_deleted  = $row->is_deleted;
            $taskMasterDto->created_at  = $row->created_at;
            $taskMasterDto->updated_at  = $row->updated_at;
            $taskMasterDto->deleted_at  = $row->deleted_at;
            $result[] = $taskMasterDto;
        }

        return $result;
    }

    /**
     * モーダル：マスタから切り替える：タスク切り替え実行
     * ※マスタから切り替える場合は、まだtasksにレコードが存在しないため、
     * まずtasksにレコードを作成する。
     *
     * @param type $params
     */
    public function changeTaskMaster($params) {

        DB::beginTransaction();
        try {
            $qtype = $params['qtype'];
            $userId = $params['userId'];
            $userTaskPeriodId = $params['userTaskPeriodId'];
            $orgTaskId = $params['orgTaskId'];
            $taskMasterId = $params['taskMasterId'];

            //古いtasksレコードの is_selectedを「0:選択なし」に更新
            Task::where('id', $orgTaskId)
                ->update([
                    'is_selected' => TaskSelectedType::RESERVED
                ]);

            /*
             * ※マスタから切り替える場合は、まだtasksにレコードが存在しないため、
             * まずtasksにレコードを作成する。
             */
            if($qtype == 'Grammar'){
                $taskType = TaskType::TASK_GRAMMAR;
            }elseif($qtype == 'Vocabulary'){
                $taskType = TaskType::TASK_VOCABULARY;
            }

            $task = new Task();
            $task->user_id      = $userId;
            $task->task_type    = $taskType;
            $task->cand_task_id = $taskMasterId;
            $task->task_order   = 1;
            $task->is_selected  = TaskSelectedType::SELECTED;
            $task->comment      = null;
            $task->is_deleted   = IsDeletedType::ACTIVE;
            $task->created_at   = Carbon::now();
            $task->save();

            $newTaskId = $task->id;//作成されたid

            //UserTaskPeriodのtask_idを新しいtasksレコードのidとする
            UserTaskPeriod::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('id', $userTaskPeriodId)
                            ->update([
                                'task_id' => $newTaskId
                            ]);

            DB::commit();
            return true;

        } catch(\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
        }
        return false;
    }



    /**
     *
     * @param type $params
     */
    public function checkPermissionForMember($params) {
        $userId = $params['userId'];
        $loginUser = $params['loginUser'];
        $loginUserAccountId = $loginUser['id'];
        $loginUserAccountKindId = $loginUser['account_kind_id'];

        if( $loginUserAccountKindId == AccountKindType::ACCOUNT_COUNSELOR_ID ){
            $isExist = UserCounselor::where('is_deleted', IsDeletedType::ACTIVE)
                                    ->where('account_id', $loginUserAccountId)
                                    ->where('user_id', $userId)
                                    ->exists();
        }else{
            $isExist = UserTaskStaff::where('is_deleted', IsDeletedType::ACTIVE)
                                    ->where('account_id', $loginUserAccountId)
                                    ->where('user_id', $userId)
                                    ->exists();
        }

        return $isExist;
    }

    public function postChatworkMessage($params) {
        $userId = $params['userId'];
        $postText = $params['postText'];

        $userDto = $this->getUserBasicInfo($params);
        $roomId = $userDto->cwRoomId;

        $cwParams = [
            'roomid' => $roomId,
            'title' => 'お知らせ',
            'message' => $postText,
        ];
        ChatworkService::sendChatworkMessage($cwParams);
        return true;
    }

    /**
     * カウンセラー情報取得
     * @param type $params
     * @return CounselorDto
     */
    public function getCounselorInfo($params) {
        $userId = $params['userId'];

        //該当ユーザに割り当てられているカウンセラー一覧
        $userCounselors = UserCounselor::from('user_counselors as cns')
                                    ->join('accounts as acc', 'cns.account_id', 'acc.id')
                                    ->where('acc.account_kind_id', AccountKindType::ACCOUNT_COUNSELOR_ID)
                                    ->where('acc.is_deleted', IsDeletedType::ACTIVE)
                                    ->where('cns.is_deleted', IsDeletedType::ACTIVE)
                                    ->where('cns.user_id', $userId)
                                    ->select(['acc.id as accountId', 'acc.first_name', 'acc.last_name', 'acc.first_name_en', 'acc.last_name_en'])
                                    ->orderby('acc.id')
                                    ->distinct()
                                    ->get();
        $assignedCounselorList = [];
        $assignedCounselorIds = [];
        foreach($userCounselors as $userCounselor){
            $counselorDto = new CounselorDto();
            $assignedCounselorIds[] = $userCounselor->accountId;
            $counselorDto->accountId = $userCounselor->accountId;
            $counselorDto->accountName = $userCounselor->last_name.' '.$userCounselor->first_name
                                       . ' ('.$userCounselor->first_name_en.' '.$userCounselor->last_name_en.')';
            $assignedCounselorList[] = $counselorDto;
        }

        //該当ユーザに割り当てられていないカウンセラー一覧（候補一覧）
        $candidateCounselors = Account::from('accounts as acc')
                                    ->leftjoin('user_counselors as cns', 'cns.account_id', 'acc.id')
                                    ->where('acc.account_kind_id', AccountKindType::ACCOUNT_COUNSELOR_ID)
                                    ->where('acc.is_deleted', IsDeletedType::ACTIVE)
                                    ->whereNotIn('acc.id', $assignedCounselorIds)
                                    ->select(['acc.id as accountId', 'acc.first_name', 'acc.last_name', 'acc.first_name_en', 'acc.last_name_en'])
                                    ->orderby('acc.id')
                                    ->distinct()
                                    ->get();
        $candidateCounselorList = [];
        foreach($candidateCounselors as $candidateCounselor){
            $counselorDto = new CounselorDto();
            $counselorDto->accountId = $candidateCounselor->accountId;
            $counselorDto->accountName = $candidateCounselor->last_name.' '.$candidateCounselor->first_name
                                       . ' ('.$candidateCounselor->first_name_en.' '.$candidateCounselor->last_name_en.')';
            $candidateCounselorList[] = $counselorDto;
        }

        $result = [
            'assignedCounselorList'  => $assignedCounselorList,
            'candidateCounselorList' => $candidateCounselorList,
        ];
        return $result;
    }

    public function putCounselorInfo($params) {
        $userId = $params['userId'];
        $accountDatas = $params['accountDatas'];

        foreach($accountDatas as $accountData){
            $registStatus = $accountData['registStatus'];
            $accountId = $accountData['accountId'];

            if($registStatus == '0'){//削除
                UserCounselor::where('account_id', $accountId)
                            ->update([
                                'is_deleted' => IsDeletedType::DELETED,
                                'deleted_at' => Carbon::now()
                            ]);

            }elseif($registStatus == '1'){//そのまま

            }elseif($registStatus == '2'){//新規追加
                $userCounselor = new UserCounselor();
                $userCounselor->user_id = $userId;
                $userCounselor->account_id = $accountId;
                $userCounselor->is_deleted = IsDeletedType::ACTIVE;
                $userCounselor->created_at = Carbon::now();
                $userCounselor->save();
            }
        }
        return true;
    }

    /**
     * 会員レポート発行日付けリスト取得
     * @param array $params
     */
    public function getMemberReportIssueDates($params) {
        $userId = $params['userId'];
        $reportIssueDates = [];

        $reportInfo = MemberReport::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('user_id', $userId)
                            ->orderby('report_created_at', 'desc')
                            ->get();
        foreach($reportInfo as $row){
            $issueDateKey = DateUtils::shortenToYYMMDD($row->report_created_at);
            $issueNumber  = DateUtils::convertToYYMMDD_Japanese($issueDateKey).'号';
            $reportIssueDates[$issueDateKey] = $issueNumber;
        }
        return $reportIssueDates;
    }

    /**
     * 会員レポート情報取得
     * @param array $params
     * @return array
     */
    public function getMemberReportInfo($params) {
        $userId = $params['userId'];
        $memberReportIssue = $params['memberReportIssue'];

        $memberReportDto = new MemberReportDto();

        //会員基本情報(最新情報)の取得
        $memberDetail = $this->getMemberBasic($params);
        $userDto = $memberDetail->userBasicInfo;
        $memberReportDto->userId       = $userDto->userId;
        $memberReportDto->arugoUserId  = $userDto->arugoUserId;

        //2018-05-31 会員レポート氏名表記は「英字の苗字のみ」とする
        $memberReportDto->memberName   = $userDto->lastName . ' ' . $userDto->firstName;
        $memberReportDto->memberNameEn = $userDto->lastNameEn; //$userDto->firstNameEn . ' ' . $userDto->lastNameEn;

        //該当発行号のレポート情報を取得
        $reportInfo = MemberReport::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('user_id', $userId)
                            ->whereDate('report_created_at', $memberReportIssue)
                            ->first();

        if(!empty($reportInfo) && $reportInfo->count() > 0){
            $memberReportDto->contractPeriodSince = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->contract_period_since));
            $memberReportDto->contractPeriodUntil = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->contract_period_until));
            $memberReportDto->lessonPeriodSince   = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->lesson_period_since));
            $memberReportDto->lessonPeriodUntil   = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->lesson_period_until));
            $memberReportDto->reportCreatedAt     = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($reportInfo->report_created_at));
            $memberReportDto->reportName          = $reportInfo->report_name;
            $memberReportDto->remark              = $reportInfo->report_remark;
            $memberReportDto->isPosted            = $reportInfo->is_posted;
            $memberReportDto->postedAt            = $reportInfo->posted_at;

            //該当ユーザについての前回レポート発行日を取得
            $beforeReportInfo = MemberReport::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('user_id', $userId)
                                ->whereDate('report_created_at', '<', $memberReportIssue)
                                ->orderby('report_created_at', 'desc')
                                ->first();
            if(!empty($beforeReportInfo) && $beforeReportInfo->count() > 0){
                $memberReportDto->beforeReportCreatedAt = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($beforeReportInfo->report_created_at));
            }

            //該当発行号のグラフ画像情報を取得
            $chartInfos = MemberReportChart::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('user_id', $userId)
                                ->whereDate('report_created_at', $memberReportIssue)
                                ->orderby('chart_order')
                                ->get();
            $chartList = [];
            if(!empty($chartInfos) && $chartInfos->count() > 0){
                foreach($chartInfos as $chartInfo){
                    //s3Keyから署名付きURLを取得する
                    $chartS3Key = $chartInfo->chart_s3_key;
                    $chartUrl = S3Service::getPreSignedUrlByS3Key($chartS3Key);
                    $chartList[$chartInfo->chart_type] = [
                                    'chartTitle'    => $chartInfo->chart_title,
                                    'chartFilePath' => $chartUrl,
                                    'chartRemark'   => $chartInfo->chart_remark,
                                    'chartMessage'  => $chartInfo->chart_message,
                                ];
                }
            }
            $memberReportDto->chartList = $chartList;

            //grammarRate用表データ設定
            $memberReportDto->grammarRates = $this->getGrammarRateData($userId, $memberReportIssue);

            // g-sample用表データ設定
            $memberReportGsampleDatas = MemberReportGsampleData::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('user_id', $userId)
                                        ->whereDate('report_created_at', $memberReportIssue)
                                        ->orderby('id')
                                        ->get();
            $gSampleDatas = [];
            if(!empty($memberReportGsampleDatas) && $memberReportGsampleDatas->count() > 0){
                foreach($memberReportGsampleDatas as $memberReportGsampleData){
                    $gSampleDatas[] = [
                                    'id'          => $memberReportGsampleData->id,
                                    'g_index'     => $memberReportGsampleData->g_index,
                                    'g_name'      => $memberReportGsampleData->g_name,
                                    'lesson_time' => DateUtils::shortenToYYMMDDHHMM_SlashDelimited($memberReportGsampleData->lesson_time),
                                    'tf_flag'     => ($memberReportGsampleData->tf_flag == 1) ? 'True' : 'False',
                                    'sentence'    => $memberReportGsampleData->sentence,
                                ];
                }
            }
            $memberReportDto->gSampleDatas = $gSampleDatas;


            //累積レッスンチケット消化データ
            $memberReportDto->accumulatedLessonTicketsDatas = $this->getAccumulatedLessonTicketsData($userId);
            //累積タスク完了数データ取得
            $memberReportDto->accumulatedTaskCompleteRates = $this->getAccumulatedTaskCompleteRate($userId);

        }//end of if

        return $memberReportDto;
    }

    /**
     * grammarRate用表データ設定
     * @param string $userId
     * @param string $issueDate
     */
    public function getGrammarRateData($userId, $issueDate) {

        $memberReportGrammarRates = MemberReportGrammarRate::where('is_deleted', IsDeletedType::ACTIVE)
                                    ->where('user_id', $userId)
                                    ->whereDate('report_created_at', $issueDate)
                                    ->orderby('id')
                                    ->get();
        $grammarRates = [];
        if(!empty($memberReportGrammarRates) && $memberReportGrammarRates->count() > 0){
            foreach($memberReportGrammarRates as $memberReportGrammarRate){
                $latestValue = 0;
                if(!empty($memberReportGrammarRate->latest_value)){
                    $latestValue = (float) $memberReportGrammarRate->latest_value;
                }
                $latestValueBGColor = $this->getGrammarRateBGColor($latestValue);

                $previousValue = 0;
                if(!empty($memberReportGrammarRate->previous_value)){
                    $previousValue = (float) $memberReportGrammarRate->previous_value;
                }
                $previousValueBGColor = $this->getGrammarRateBGColor($previousValue);

                $diffValue = (float)($latestValue - $previousValue);
                if($diffValue >= 0){
                    $diffValueColor = 'blue';
                }else{
                    $diffValueColor = 'red';
                }

                $grammarRates[] = [
                                'id'             => $memberReportGrammarRate->id,
                                'g_index'        => trim($memberReportGrammarRate->g_index),
                                'g_name'         => trim($memberReportGrammarRate->g_name),
                                'latest_value'   => trim(number_format($latestValue, 1)),
                                'previous_value' => trim(number_format($previousValue, 1)),
                                'diff_value'     => trim(number_format($diffValue, 1)),
                                'latestValueBGColor'   => $latestValueBGColor,
                                'previousValueBGColor' => $previousValueBGColor,
                                'diffValueColor' => $diffValueColor,
                            ];
            }
        }
        return $grammarRates;
    }

    /**
     * grammarRate数値に応じた背景色の設定
     * @param float $value
     * @return string
     */
    private function getGrammarRateBGColor($value) {
        $bgColor = '';

        if($value >= 0 && $value < 21){
            $bgColor = MemberReportGrammarRateColorType::MEMBER_REPORT_GRAMMAR_RATE_COLOR_00_20;
        }elseif($value >= 21 && $value < 41){
            $bgColor = MemberReportGrammarRateColorType::MEMBER_REPORT_GRAMMAR_RATE_COLOR_21_40;
        }elseif($value >= 41 && $value < 61){
            $bgColor = MemberReportGrammarRateColorType::MEMBER_REPORT_GRAMMAR_RATE_COLOR_41_60;
        }elseif($value >= 61 && $value < 81){
            $bgColor = MemberReportGrammarRateColorType::MEMBER_REPORT_GRAMMAR_RATE_COLOR_61_80;
        }elseif($value >= 81 && $value < 101){
            $bgColor = MemberReportGrammarRateColorType::MEMBER_REPORT_GRAMMAR_RATE_COLOR_81_100;
        }elseif($value >= 101){
            $bgColor = MemberReportGrammarRateColorType::MEMBER_REPORT_GRAMMAR_RATE_COLOR_81_100;
        }else{
            $bgColor = '';
        }
        return $bgColor;
    }

    /**
     * 2018-06-07 追加
     * 会員詳細:データの動的取得
     *
     * 会員詳細画面の表示が遅いため、
     * 処理に時間を要する下記のデータは初期表示時には表示せず、
     * ページ描画後にajaxにて動的に取得するように変更する。
     *
     * また各データについて「無限スクロール対応」とすることで、
     * 1回に取得/表示するデータ量を制限し、
     * これによっても処理に要する時間を短縮するものである。
     *
     * -- ajax化するデータ種別は下記：
     *  ① OUTPUT履歴
     *  ② レッスン受講状況
     *  ③ 宿題実施状況
     *  ④ メッセージ送受信履歴
     *
     * @param array $params
     * @return array
     */
    public function getMemberDetailPartData($params) {
        $dataType = $params['dataType'];
        $result = [];

        if($dataType === 'UserOutputLogs'){
            $result = $this->getUserOutputLogsPartData($params);
        }else if($dataType === 'UserLessonLogs'){
            $result = $this->getUserLessonLogsPartData($params);
        }else if($dataType === 'UserTaskLogs'){
            $result = $this->getUserTaskLogsPartData($params);
        }else if($dataType === 'UserMessageLogs'){
            $result = $this->getUserMessageLogsPartData($params);
        }

        return $result;
    }

    /**
     * アウトプット履歴の部分データ取得
     * @param array $params
     * @return UserOutputHistoryDto
     */
    private function getUserOutputLogsPartData($params) {
        $dtos = [];
        $userId = $params['userId'];
        $skip = $params['skip'];

        $query = UserSession::from('user_sessions as ses')
                    ->join('user_lesson_logs as ulg', 'ses.lesson_id', 'ulg.id')
                    ->where('ses.is_deleted', IsDeletedType::ACTIVE)
                    ->where('ulg.is_deleted', IsDeletedType::ACTIVE)
                    ->where('ulg.user_id', $userId)
                    ->whereNotNull('ses.feedback_input')
                    ->where(DB::raw('CHAR_LENGTH(ses.feedback_input)'), '>', 0)
                    ->select([
                        'ses.id as sesId',
                        'ses.session_id as sesSessionId',
                        'ses.lesson_id as sesLessonId',
                        'ses.feedback_input as sesFeedbackInput',
                        'ulg.id as ulgId',
                        'ulg.user_id as ulgUserId',
                        'ulg.alugo_teacher_id as ulgTeacherId',
                        'ulg.alugo_user_id as ulgStudentId',
                        'ulg.start_at as ulgStartAt',
                        'ulg.end_at as ulgEndAt',
                        'ulg.today_feedback as ulgTodayFeedback',
                        'ulg.approved_at as ulgApprovedAt'
                    ]);

        $totalCount = $query->count();
        $recNo = 0;

        $recs = $query->orderby('ulg.start_at', 'desc')
                        ->skip($skip)
                        ->take(10)
                        ->get();

        if(!empty($recs) && $recs->count() > 0){
            $recNo = $skip + 1;
            foreach($recs as $rec){
                $dto = new UserOutputHistoryDto();
                $dto->id	          = $rec->sesId;
                $dto->sessionId       = $rec->sesSessionId;
                $dto->studentId       = $rec->ulgStudentId;
                $dto->lessonDateTime  = $rec->ulgStartAt;
                $dto->sentences       = $rec->sesFeedbackInput;
                $dto->recNo           = $recNo;
                $dtos[] = $dto;
                $recNo ++;
            }
        }

        return [
            'dataRows' => $dtos,
            'totalCount' => $totalCount,
            'currentCount' => empty($recNo) ? 0 : ($recNo - 1)
        ];
    }

    /**
     * レッスン記録
     * @param array $params
     * @return UserLessonLogDto
     */
    private function getUserLessonLogsPartData($params) {
        $dtos = [];
        $userId = $params['userId'];
        $skip = $params['skip'];

        $query = UserLessonLog::from('user_lesson_logs as ulg')
                    ->leftjoin('user_questionnaires as uqs', function ($join) {
                        $join->on('uqs.user_id', 'ulg.user_id');
                        $join->on('uqs.lesson_id', 'ulg.id');
                    })
                    ->where('ulg.is_deleted', IsDeletedType::ACTIVE)
                    ->where('uqs.is_deleted', IsDeletedType::ACTIVE)
                    ->where('ulg.user_id', $userId)
                    ->select(['ulg.*', 'uqs.evaluation']);

        $totalCount = $query->count();
        $recNo = 0;

        $recs = $query->orderby('ulg.start_at', 'desc')
                        ->skip($skip)
                        ->take(10)
                        ->get();

        if(!empty($recs) && $recs->count() > 0){
            $recNo = $skip + 1;
            foreach($recs as $rec){
                $dto = new UserLessonLogDto();
                $dto->id                = $rec->id;
                $dto->user_id           = $rec->user_id;
                $dto->alugo_teacher_id  = empty($rec->alugo_teacher_id) ? '' : $rec->alugo_teacher_id;
                $dto->alugo_user_id     = $rec->alugo_user_id;
                $dto->start_at          = $rec->start_at;
                $dto->end_at            = $rec->end_at;
                $dto->report            = $rec->report;
                $dto->lesson_id         = $rec->lesson_id;
                $dto->today_feedback    = $rec->today_feedback;
                $dto->message_user      = $rec->message_user;
                $dto->share_coach       = $rec->share_coach;
                $dto->approved_at       = $rec->approved_at;
                $dto->lesson_type       = $rec->lesson_type;
                $dto->canceled          = $rec->canceled;
                $dto->canceled_sec      = $rec->canceled_sec;
                $dto->request_sec       = $rec->request_sec;
                $dto->is_deleted        = $rec->is_deleted;
                $dto->created_at        = $rec->created_at;
                $dto->updated_at        = $rec->updated_at;
                $dto->deleted_at        = $rec->deleted_at;
                $dto->lesson_evaluation = $rec->evaluation;
                $dto->recNo             = $recNo;
                $dto->setLessonEvaluationMark();
                $dtos[] = $dto;
                $recNo ++;
            }
        }

        return [
            'dataRows' => $dtos,
            'totalCount' => $totalCount,
            'currentCount' => empty($recNo) ? 0 : ($recNo - 1)
        ];
    }

    /**
     * 宿題(タスク)記録
     * @param array $params
     * @return UserTaskDto
     */
    private function getUserTaskLogsPartData($params) {
        $dtos = [];
        $userId = $params['userId'];
        $skip = $params['skip'];

        $query = UserTask::from('user_tasks as usrtsk')
                ->join('tasks as tsk', 'usrtsk.task_id', 'tsk.id')
                ->where('usrtsk.is_deleted', IsDeletedType::ACTIVE)
                ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                ->whereIn('tsk.task_type', [TaskType::TASK_GRAMMAR, TaskType::TASK_VOCABULARY])
                ->where('usrtsk.user_id', $userId)
                ->select(['usrtsk.id as userTaskId','usrtsk.*']);

        $totalCount = $query->count();
        $recNo = 0;

        $recs = $query->orderby('usrtsk.question_set_date', 'desc')
                        ->skip($skip)
                        ->take(10)
                        ->get();

        if(!empty($recs) && $recs->count() > 0){
            $recNo = $skip + 1;
            foreach($recs as $rec){
                $dto = new UserTaskDto();
                $dto->userTaskId		  = $rec->userTaskId;
                $dto->userId              = $rec->user_id;
                $dto->taskId              = $rec->task_id;
                $dto->userTaskStatus      = $rec->user_task_status;
                $dto->setUserTaskStatusName();

                $dto->questionSetDate     = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($rec->question_set_date));
                $dto->answerDeadlineDate  = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($rec->answer_deadline_date));
                $dto->answeredDate        = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($rec->answered_date));

                //回答レコードid
                $dto->userMessageLogId    = $rec->user_message_log_id;

                //カウンセラー情報
                $dto->counselorInfo = TaskService::getStaffInfo($rec, UserTaskStaffType::USER_TASK_STAFF_COUNSELOR);
                //オペレータ情報
                $dto->operatorInfo  = TaskService::getStaffInfo($rec, UserTaskStaffType::USER_TASK_STAFF_OPERATOR);

                $dto->originalAnswer      = $rec->original_answer;
                $dto->userFeedbackId      = $rec->user_feedback_id;
                $dto->comment             = $rec->comment;
                $dto->isDeleted           = $rec->is_deleted;
                $dto->createdAt           = $rec->created_at;
                $dto->updatedAt           = $rec->updated_at;
                $dto->deletedAt           = $rec->deleted_at;
                $dto->recNo               = $recNo;
                //ユーザ情報
                $dto->userInfo            = TaskService::getUserInfo($rec);
                //出題情報
                $dto->taskInfo            = TaskService::getTaskInfo($rec);
                //フィードバック情報
                $dto->feedbackInfo        = TaskService::getFeedbackInfo($rec);
                $dtos[] = $dto;
                $recNo ++;
            }
        }

        return [
            'dataRows' => $dtos,
            'totalCount' => $totalCount,
            'currentCount' => empty($recNo) ? 0 : ($recNo - 1)
        ];
    }

    /**
     * メッセージ送受信履歴
     * @param array $params
     * @return UserMessageLogDto
     */
    private function getUserMessageLogsPartData($params) {
        $dtos = [];
        $userId = $params['userId'];
        $skip = $params['skip'];

        $query = UserMessageLog::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('user_id', $userId);

        $totalCount = $query->count();
        $recNo = 0;

        $recs = $query->orderby('posted_at', 'desc')
                        ->skip($skip)
                        ->take(10)
                        ->get();

        if(!empty($recs) && $recs->count() > 0){
            $recNo = $skip + 1;
            foreach($recs as $rec){

                //メッセージ投稿内容を整形する
                $postedContext = '';
                if($rec->cw_post_type == UserMessageLogCwPostType::FILE){
                    $fileInfo = json_decode($rec->posted_context);
                    $fileName = '';
                    $fileUrl = '';
                    if(is_object($fileInfo) && property_exists($fileInfo, 'file_name')){
                        $fileName = $fileInfo->file_name;
                    }
                    if(is_object($fileInfo) && property_exists($fileInfo, 'file_url')){
                        $fileUrl  = $fileInfo->file_url;
                    }
                    $postedContext = nl2br(e($fileName . "\n" . $fileUrl));

                }else{
                    $postedContext = nl2br(e($rec->posted_context));
                }

                $dto = new UserMessageLogDto();
                $dto->userMessageLogId  = $rec->id;
                $dto->userId            = $rec->user_id;
                $dto->userName          = '';
                $dto->messageType       = $rec->message_type;
                $dto->cwRoomId          = $rec->cw_room_id;
                $dto->cwRoomName        = $rec->cw_room_name;
                $dto->cwPostType        = $rec->cw_post_type;
                $dto->connectType       = $rec->connect_type;
                $dto->postedName        = $rec->posted_name;
                $dto->postedAt          = $rec->posted_at;
                $dto->deadline          = $rec->deadline;
                $dto->topicCategory     = $rec->topic_category;
                $dto->topicId           = $rec->topic_id;
                $dto->topicStuffId      = $rec->topic_stuff_id;
                $dto->postedContext     = $postedContext;
                $dto->userTaskLinked    = $rec->user_task_linked;
                $dto->comment           = $rec->comment;
                $dto->isDeleted         = $rec->is_deleted;
                $dto->createdAt         = $rec->created_at;
                $dto->updatedAt         = $rec->updated_at;
                $dto->deletedAt         = $rec->deleted_at;
                $dto->recNo             = $recNo;
                $dto->setMessageTypeName();
                $dto->setPostTypeName();
                $dto->setConnectTypeName();
                $dtos[] = $dto;
                $recNo ++;
            }
        }

        return [
            'dataRows' => $dtos,
            'totalCount' => $totalCount,
            'currentCount' => empty($recNo) ? 0 : ($recNo - 1)
        ];
    }

    /**
     * 学習方針カテゴリ取得
     * $params['category1Id'] が無指定の場合はカテゴリ大を取得
     * $params['category1Id'] が指定された場合は紐付くカテゴリ小を取得
     * @param type $categoryLayer
     * @param type $parentId
     * @return array<LpCategoryDto>
     */
    public function getUserLearningPolicyCategory($params=null) {

        $categoryLayer = 1;
        $parentId = 0;
        if(!empty($params) && array_key_exists('category1Id', $params)){
            $categoryLayer = 2;
            $parentId = $params['category1Id'];
        }

        $lpCategories = [];
        $rows = LpCategory::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('category_layer', $categoryLayer)
                            ->where('parent_id', $parentId)
                            ->orderby('order_no')
                            ->get();

        foreach($rows as $row){
            $lpCategoryDto = new LpCategoryDto();
            $lpCategoryDto->id				= $row->id;
            $lpCategoryDto->category_layer  = $row->category_layer;
            $lpCategoryDto->parent_id       = $row->parent_id;
            $lpCategoryDto->order_no        = $row->order_no;
            $lpCategoryDto->category_name   = $row->category_name;
            $lpCategoryDto->remark          = $row->remark;
            $lpCategoryDto->is_deleted      = $row->is_deleted;
            $lpCategoryDto->created_at      = $row->created_at;
            $lpCategoryDto->updated_at      = $row->updated_at;
            $lpCategoryDto->deleted_at      = $row->deleted_at;
            $lpCategories[] = $lpCategoryDto;
        }
        return $lpCategories;
    }


    //累積レッスンチケット消化データ取得
    public function getAccumulatedLessonTicketsData($userId) {
        $datas = [];
        $userLessonProgresses = UserLessonProgress::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('user_id', $userId)
                                        ->orderby('counted_at', 'desc')
                                        ->limit(30)
                                        ->get();
        foreach($userLessonProgresses as $userLessonProgress){
            $userLessonProgressDto = new UserLessonProgressDto();
            $userLessonProgressDto->id					= $userLessonProgress->id;
            $userLessonProgressDto->user_id             = $userLessonProgress->user_id;
            $userLessonProgressDto->alugo_user_id       = $userLessonProgress->alugo_user_id;
            $userLessonProgressDto->counted_at          = $userLessonProgress->counted_at;
            $userLessonProgressDto->contract_term_days  = $userLessonProgress->contract_term_days;
            $userLessonProgressDto->elapsed_days        = $userLessonProgress->elapsed_days;
            $userLessonProgressDto->tickets_total       = $userLessonProgress->tickets_total;
            $userLessonProgressDto->tickets_used        = $userLessonProgress->tickets_used;
            $userLessonProgressDto->tickets_used_rate   = sprintf('%.1f', $userLessonProgress->tickets_used_rate);
            $userLessonProgressDto->remark              = $userLessonProgress->remark;
            $userLessonProgressDto->is_deleted          = $userLessonProgress->is_deleted;
            $userLessonProgressDto->created_at          = $userLessonProgress->created_at;
            $userLessonProgressDto->updated_at          = $userLessonProgress->updated_at;
            $userLessonProgressDto->deleted_at          = $userLessonProgress->deleted_at;
            $datas[] = $userLessonProgressDto;
        }
        return $datas;
    }

    //累積タスク完了数データ取得
    public function getAccumulatedTaskCompleteRate($userId) {
        $datas = [];
        $userTaskProgresses = UserTaskProgress::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('user_id', $userId)
                                        ->orderby('counted_at', 'desc')
                                        ->limit(30)
                                        ->get();
        foreach($userTaskProgresses as $userTaskProgress){
            $userTaskProgressDto = new UserTaskProgressDto();
            $userTaskProgressDto->id					= $userTaskProgress->id;
            $userTaskProgressDto->user_id               = $userTaskProgress->user_id;
            $userTaskProgressDto->alugo_user_id         = $userTaskProgress->alugo_user_id;
            $userTaskProgressDto->counted_at            = $userTaskProgress->counted_at;
            $userTaskProgressDto->task_type             = $userTaskProgress->task_type;
            $userTaskProgressDto->task_type_name        = TaskService::getTaskTypeName($userTaskProgress->task_type);
            $userTaskProgressDto->sent_task_count       = $userTaskProgress->sent_task_count;
            $userTaskProgressDto->completed_task_count  = $userTaskProgress->completed_task_count;
            $userTaskProgressDto->completed_task_rate   = sprintf('%.1f', $userTaskProgress->completed_task_rate);
            $userTaskProgressDto->remark                = $userTaskProgress->remark;
            $userTaskProgressDto->is_deleted            = $userTaskProgress->is_deleted;
            $userTaskProgressDto->created_at            = $userTaskProgress->created_at;
            $userTaskProgressDto->updated_at            = $userTaskProgress->updated_at;
            $userTaskProgressDto->deleted_at            = $userTaskProgress->deleted_at;
            $datas[] = $userTaskProgressDto;
        }
        return $datas;
    }


}

