<?php
namespace App\Http\Controllers\Admin\Account;

use App\Models\Account;
use App\Models\AccountKind;

use Illuminate\Http\Request;
use App\Exceptions\AppException;
use \App\Http\Controllers\ApiBaseController;

/**
 * AjaxController
 */
class AjaxController extends ApiBaseController {

//    public function __construct() {
//        parent::__construct();
//    }

    //データ取得API
    public function getdata(Request $request)
    {

        $params = $request->all();
//        Log::debug($params);

        $data_id = $params['data_id'];
        $Account = new Account();

        $result = $Account->getOne($data_id);
//        Log::debug((array)$result);

        //JSON形式で返却する
        return response((array)$result);

    }
}
