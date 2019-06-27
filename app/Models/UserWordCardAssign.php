<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWordCardAssign extends Model
{
    public $table = 'user_word_card_assigns';
    public $primary = 'id';
    public $guarded = ['id'];
}
