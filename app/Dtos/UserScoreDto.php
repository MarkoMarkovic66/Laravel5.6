<?php

namespace App\Dtos;

/**
 * UserScoreDto
 */
class UserScoreDto {
    public $id;
    public $user_id;
    public $alugo_user_id;
    public $assessment_alugo_stuff_id;
    public $feedback_alugo_stuff_id;
    public $count;
    public $level;
    public $report;

    public $result;
    public $result_array;
    
    public $assessment_started_at;
    public $assessment_ended_at;
    public $feedback_started_at;
    public $feedback_ended_at;
    public $comment;
    public $is_deleted;
    public $created_at;
    public $updated_at;
    public $deleted_at;

}
