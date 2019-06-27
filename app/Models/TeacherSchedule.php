<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSchedule extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'teacher_schedule';
}
