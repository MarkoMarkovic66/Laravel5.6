<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class TaskServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__task_service';
  }
}
