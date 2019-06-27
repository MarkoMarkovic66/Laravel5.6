<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserve extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'reserve';

    protected $fillable = [
        'student_id',
        'item_id',
        'phone_number',
        'teacher_id',
        'start_at',
        'end_at',
        'reserve_sec',
        'curriculum',
        'lang',
        'lesson_type',
        'lesson_ticket_id',
        'created_at',
        'updated_at',
    ];

}
