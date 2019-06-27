<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSrAssign extends Model
{
    public $table = 'user_sr_assigns';
    public $primary = 'id';
    public $guarded = ['id'];
}
