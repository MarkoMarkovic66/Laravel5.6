<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AlueIntegNotifyExecuteServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__alue_integ_notify_execute_service';
  }
}
