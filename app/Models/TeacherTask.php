<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherTask extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'teacher_task';
}