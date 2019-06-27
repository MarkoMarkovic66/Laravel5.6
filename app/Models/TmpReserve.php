<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmpReserve extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'tmp_reserve';
}