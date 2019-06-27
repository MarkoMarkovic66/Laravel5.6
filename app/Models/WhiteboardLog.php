<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhiteboardLog extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'whiteboard_log';
    public $timestamps = false;
    protected $primaryKey = 'whiteboard_id';

    protected $fillable = [
        'whiteboard_id',
        'board',
        'created_at',
    ];
}
