<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class MemberServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__member_service';
  }
}
