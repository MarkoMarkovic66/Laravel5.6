<?php
namespace App\Models;

use App\Models\CommonModel;

class Package extends CommonModel
{
    public $table = 'packages';
    public $name = '会員プランマスタ';
    public $primary = 'id';
}
