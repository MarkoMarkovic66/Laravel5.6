<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AlueIntegMemberReportServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__alue_integ_member_report_service';
  }
}
