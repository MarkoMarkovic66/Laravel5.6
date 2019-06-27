<?php

namespace App\Dtos;

/**
 * UserLessonProgressDto
 */
class UserLessonProgressDto {

    public $id;
    public $user_id;
    public $alugo_user_id;
    public $counted_at;
    public $contract_term_days;
    public $elapsed_days;
    public $tickets_total;
    public $tickets_used;
    public $tickets_used_rate;
    public $remark;
    public $is_deleted;
    public $created_at;
    public $updated_at;
    public $deleted_at;

}
