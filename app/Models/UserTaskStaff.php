<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class UserTaskStaff extends CommonModel {
    public $table = 'user_task_staffs';
    public $name = '会員課題別担当者管理';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'user_task_id' => 'user_tasksのPK',
        'staff_type' => '1:カウンセラー 2:オペレータ',
        'account_id' => 'accountsのPK',
        'user_id' => 'usersのPK',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
        'deleted_at' => '削除日',
    );

}
