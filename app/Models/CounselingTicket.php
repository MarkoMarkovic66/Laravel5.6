<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounselingTicket extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'counseling_ticket';
}
