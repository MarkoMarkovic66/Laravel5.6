<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AlueIntegCreateQuestionServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__alue_integ_create_question_service';
  }
}
