<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnqueteTask extends Model
{
    public $table = 'enquete_tasks';
    public $name = 'アンケートタスクマスタ';
    public $primary = 'id';
    public $guarded = ['id'];

}
