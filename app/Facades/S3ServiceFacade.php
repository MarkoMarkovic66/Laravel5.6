<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class S3ServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__s3_service';
  }
}
