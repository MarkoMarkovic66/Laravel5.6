<?php
namespace App\Dtos;

/**
 * フィードバックを管理するクラス
 */
class FeedbackDto {

    public $feedbackId;

    public $userId;
    public $userTaskId;

    public $feedbackAccountId;
    public $feedbackAccountName;

    public $feedbackComment;
    public $feedbackStatus;
    public $feedbackDate;

    public $approvedAccountId;
    public $approvedAccountName;
    public $approvedDate;

    public $userComment;

}
