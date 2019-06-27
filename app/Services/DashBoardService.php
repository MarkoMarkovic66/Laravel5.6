<?php
namespace App\Services;

use App\Enums\UserMessageLogMessageType;
use App\Enums\UserMessageLogCwPostType;
use App\Enums\UserMessageLogConnectType;
use App\Enums\AccountKindType;
use App\Enums\UserTaskStaffType;
use App\Enums\IsDeletedType;
use App\Enums\TaskType;

use App\Dtos\UserMessageLogDto;
use App\Dtos\UserDto;
use App\Dtos\TaskDto;
use App\Dtos\UserTaskDto;

use App\Models\User;
use App\Models\UserTask;
use App\Models\UserMessageLog;

use TaskService;
use DB;

/**
 * Description of DashBoardService
 */
class DashBoardService {

    public function getDashBoardData($loginAccountInfo) {

        if( $loginAccountInfo['account_kind_id'] == AccountKindType::ACCOUNT_MANAGER_ID ||
            $loginAccountInfo['account_kind_id'] == AccountKindType::ACCOUNT_SYSTEM_OPERATOR_ID ){
            /*
             * 管理者、運用者
             *
             * 1. 講師(オペレータ)未割当タスク一覧
             *    user_tasksのうち、オペレータが未割当のもの max10件 を表示。
             *
             * 2. 新着回答一覧
             *    新着回答のうち max10件 を表示。
             */
            //user_tasksのうち、オペレータが未割当のもの max10件
            $taskList = TaskService::getNoAssignOperatorTask(10);

            /**
            $userMessageLogs = UserMessageLog::from('user_message_logs as uml')
                                ->where('uml.is_deleted', IsDeletedType::ACTIVE)
                                ->where('uml.message_type', UserMessageLogMessageType::CHATWORK)
                                ->where('uml.cw_post_type', UserMessageLogCwPostType::FILE)
                                ->where('uml.connect_type', UserMessageLogConnectType::RECEIVE)
                                ->whereNotNull('uml.posted_context')
                                ->orderby('uml.posted_at', 'desc')
                                ->skip(0)
                                ->take(10)
                                ->get();
            $answerList = [];
            foreach($userMessageLogs as $userMessageLog){

                $postedContext = '';
                if($userMessageLog->cw_post_type == UserMessageLogCwPostType::FILE){

                    if(!empty($userMessageLog->posted_context)){
                        if(!empty($fileInfo = json_decode($userMessageLog->posted_context))){
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
                            $postedContext = nl2br(e($userMessageLog->posted_context));
                        }
                    }

                }else{
                    $postedContext = nl2br(e($userMessageLog->posted_context));
                }

                $userMessageLogDto= new UserMessageLogDto();
                $userMessageLogDto->userMessageLogId = $userMessageLog->id;
                $userMessageLogDto->userId           = $userMessageLog->user_id;
                $userMessageLogDto->postedName       = $userMessageLog->posted_name;
                $userMessageLogDto->postedAt         = $userMessageLog->posted_at;
                $userMessageLogDto->postedContext    = $postedContext;
                $answerList[] = $userMessageLogDto;
            }
            **/
            return [
                'accountKindId' => $loginAccountInfo['account_kind_id'],
                'taskList' => $taskList,
                'answerList' => [], //$answerList
            ];

        }elseif( $loginAccountInfo['account_kind_id'] == AccountKindType::ACCOUNT_COUNSELOR_ID){
            /*
             * カウンセラー
             *
             * 1. 担当会員一覧
             *    自分が担当している生徒のうち max20件 を表示。
             *    →ボタンクリックで会員管理画面に遷移する。
             */
            $users = User::from('users as usr')
                        ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                        ->whereIn('usr.id', function ($q) use($loginAccountInfo){
                            $q->select(DB::raw('DISTINCT cns.user_id'))
                                ->from('user_counselors as cns')
                                ->where('cns.is_deleted', IsDeletedType::ACTIVE)
                                ->where('cns.account_id', $loginAccountInfo['id']);
                        })
                        ->select(['usr.*'])
                        ->orderby('usr.arugo_user_id')
                        ->skip(0)
                        ->take(20)
                        ->get();
            $memberList = [];
            foreach($users as $user){
                $userDto= new UserDto();
                $userDto->userId	  = $user->userId;
                $userDto->arugoUserId = $user->arugo_user_id;
                $userDto->srUserId    = $user->sr_user_id;
                $userDto->cwRoomId    = $user->cw_room_id;
                $userDto->firstName   = $user->first_name;
                $userDto->lastName    = $user->last_name;
                $userDto->firstNameEn = $user->first_name_en;
                $userDto->lastNameEn  = $user->last_name_en;
                $userDto->mail        = $user->mail;
                $memberList[] = $userDto;
            }

            return [
                'accountKindId' => $loginAccountInfo['account_kind_id'],
                'memberList' => $memberList
            ];

        }elseif( $loginAccountInfo['account_kind_id'] == AccountKindType::ACCOUNT_OPERATOR_ID){
            /*
             * オペレータ
             *
             * 1. 新着回答一覧
             *    自分が担当している生徒の新着回答のうち max20件 を表示。
             *    →ボタンクリックでタスク一覧に遷移する。
             */
            $userMessageLogs = UserMessageLog::from('user_message_logs as uml')
                                ->where('uml.is_deleted', IsDeletedType::ACTIVE)
                                ->where('uml.message_type', UserMessageLogMessageType::CHATWORK)
                                ->where('uml.cw_post_type', UserMessageLogCwPostType::FILE)
                                ->where('uml.connect_type', UserMessageLogConnectType::RECEIVE)
                                ->whereNotNull('uml.posted_context')
                                ->whereIn('uml.user_id', function ($q) use($loginAccountInfo){
                                    $q->select(DB::raw('DISTINCT user_id'))
                                        ->from('user_task_staffs as stf')
                                        ->where('stf.is_deleted', IsDeletedType::ACTIVE)
                                        ->where('stf.staff_type', UserTaskStaffType::USER_TASK_STAFF_OPERATOR)
                                        ->where('stf.account_id', $loginAccountInfo['id']);
                                })
                                ->orderby('uml.posted_at', 'desc')
                                ->skip(0)
                                ->take(20)
                                ->get();
            $answerList = [];
            foreach($userMessageLogs as $userMessageLog){

                $postedContext = '';
                if($userMessageLog->cw_post_type == UserMessageLogCwPostType::FILE){
                    $fileInfo = json_decode($userMessageLog->posted_context);
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
                    $postedContext = nl2br(e($userMessageLog->posted_context));
                }

                $userMessageLogDto= new UserMessageLogDto();
                $userMessageLogDto->userMessageLogId = $userMessageLog->id;
                $userMessageLogDto->userId           = $userMessageLog->user_id;
                $userMessageLogDto->postedName       = $userMessageLog->posted_name;
                $userMessageLogDto->postedAt         = $userMessageLog->posted_at;
                $userMessageLogDto->postedContext    = $postedContext;
                $answerList[] = $userMessageLogDto;
            }

            return [
                'accountKindId' => $loginAccountInfo['account_kind_id'],
                'answerList' => $answerList
            ];
        }
    }



}
