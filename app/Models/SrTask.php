<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class SrTask extends CommonModel
{
    public $table = 'sr_tasks';
    public $name = 'SR出題マスタ';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'sr_context' => 'SR出題内容',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
        'deleted_at' => '削除日',
    );


}
