<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class ChatworkServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__chatwork_service';
  }
}
