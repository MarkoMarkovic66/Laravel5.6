<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AlueIntegDataImportServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return '__alue_integ_data_import_service';
  }
}
