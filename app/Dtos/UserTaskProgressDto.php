<?php

namespace App\Dtos;

/**
 * UserTaskProgressDto
 */
class UserTaskProgressDto {

    public $id;
    public $user_id;
    public $alugo_user_id;
    public $counted_at;
    public $task_type;
    public $task_type_name;
    public $sent_task_count;
    public $completed_task_count;
    public $completed_task_rate;
    public $remark;
    public $is_deleted;
    public $created_at;
    public $updated_at;
    public $deleted_at;

}
