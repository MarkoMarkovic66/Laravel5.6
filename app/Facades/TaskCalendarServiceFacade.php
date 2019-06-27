<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class TaskCalendarServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__task_calendar_service';
  }
}
