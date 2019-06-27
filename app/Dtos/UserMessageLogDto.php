<?php
namespace App\Dtos;

use App\Enums\UserMessageLogCwPostType;
use App\Enums\ConnectType;
use App\Enums\UserMessageLogMessageType;

/**
 * chatwork回答を管理するクラス
 */
class UserMessageLogDto {

    public $userMessageLogId;

    public $userId;
    public $userName;

    public $messageType;
    public $messageTypeName;

    public $cwRoomId;
    public $cwRoomName;

    public $cwPostType;
    public $cwPostTypeName;

    public $connectType;
    public $connectTypeName;

    public $postedName;
    public $postedAt;
    public $deadline;
    public $topicCategory;
    public $topicId;
    public $topicStuffId;
    public $postedContext;
    public $userTaskLinked;
    public $comment;

    public $isDeleted;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    function __construct(){
    }

    public function setMessageTypeName(){
        if(!isset($this->messageType)){
            $this->messageTypeName = '';
            return;
        }
        if($this->messageType == UserMessageLogMessageType::FEEDBACK_MAIL){
            $this->messageTypeName = UserMessageLogMessageType::FEEDBACK_MAIL_NAME;
        }elseif($this->messageType == UserMessageLogMessageType::SPEAKING_RALLY){
            $this->messageTypeName =  UserMessageLogMessageType::SPEAKING_RALLY_NAME;
        }elseif($this->messageType == UserMessageLogMessageType::CHATWORK){
            $this->messageTypeName =  UserMessageLogMessageType::CHATWORK_NAME;
        }
    }

    public function setPostTypeName(){
        if(!isset($this->cwPostType)){
            $this->taskTypeName = '';
            return;
        }
        if($this->cwPostType == UserMessageLogCwPostType::TASK){
            $this->cwPostTypeName = UserMessageLogCwPostType::TASK_NAME;
        }elseif($this->cwPostType == UserMessageLogCwPostType::MESSAGE){
            $this->cwPostTypeName =  UserMessageLogCwPostType::MESSAGE_NAME;
        }elseif($this->cwPostType == UserMessageLogCwPostType::FILE){
            $this->cwPostTypeName =  UserMessageLogCwPostType::FILE_NAME;
        }
    }

    public function setConnectTypeName(){
        if(!isset($this->connectType)){
            return;
        }
        if($this->connectType == ConnectType::CONNECT_SEND){
            $this->connectTypeName = ConnectType::CONNECT_SEND_NAME;
        }elseif($this->connectType == ConnectType::CONNECT_RECEIVE){
            $this->connectTypeName =  ConnectType::CONNECT_RECEIVE_NAME;
        }
    }

}
