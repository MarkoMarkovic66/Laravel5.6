<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'lesson';

    protected $fillable = [
        'teacher_id',
        'student_id',
        'item_id',
        'start_at',
        'end_at',
        'created_at',
        'updated_at',
        'lesson_type'
    ];
}
