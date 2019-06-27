<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AlueIntegActionWatchServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__alue_integ_action_watch_service';
  }
}
