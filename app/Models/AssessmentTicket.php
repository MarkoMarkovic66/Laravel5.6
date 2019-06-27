<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentTicket extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'assessment_ticket';
}
