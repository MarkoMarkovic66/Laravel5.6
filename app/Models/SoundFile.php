<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoundFile extends Model
{
    public $table = 'sound_files';
    public $name = '音声ファイル管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
