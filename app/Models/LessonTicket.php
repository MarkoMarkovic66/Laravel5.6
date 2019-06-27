<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonTicket extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'lesson_ticket';
}
