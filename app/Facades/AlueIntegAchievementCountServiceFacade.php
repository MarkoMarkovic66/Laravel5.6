<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AlueIntegAchievementCountServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__alue_integ_achievement_count_service';
  }
}
