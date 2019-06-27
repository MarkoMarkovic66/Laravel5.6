<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserListeningAssign extends Model
{
    public $table = 'user_listening_assigns';
    public $primary = 'id';
    public $guarded = ['id'];
}
