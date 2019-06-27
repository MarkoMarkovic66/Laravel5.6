<?php

namespace App\Dtos;

/**
 * MemberDetailDto
 */
class MemberDetailDto {

    //基本情報
    public $userBasicInfo;

    //学習方針 Array<UserLearningPolicyDto>
    public $userLearningPolicies;
    //学習方針記録 Array<UserLearningPolicyDto>
    public $userLearningPolicyLogs;

    //目標
    public $userGoals;

    //アセスメント目標
    public $userAssessmentGoals;
    //アセスメント記録
    public $userAssessmentLogs;

    //アウトプット記録
    public $userOutputLogs;

    //レッスン記録
    public $userLessonLogs;

    //宿題(タスク)記録
    public $userTaskLogs;

    //メッセージ送受信記録
    public $userMessageLogs;

    //会員レポート発行日付けリスト
    public $memberReportIssueDates;

    function __construct(){
    }

}
