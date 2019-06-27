<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Whiteboard extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'whiteboard';

    protected $fillable = [
        'lesson_id',
        'teacher_id',
        'student_id',
        'start_at',
        'end_at',
        'created_at',
        'updated_at',
    ];
}
