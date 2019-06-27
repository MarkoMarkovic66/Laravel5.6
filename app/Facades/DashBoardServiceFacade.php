<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class DashBoardServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__dashboard_service';
  }
}
