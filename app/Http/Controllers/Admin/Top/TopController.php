<?php
namespace App\Http\Controllers\Admin\Top;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseViewController;
use App\Enums\MenuNameType;

use DashBoardService;

/**
 * TopController
 */
class TopController extends BaseViewController {

    public function index(Request $request)
	{
        $this->view_params->put('menuName', MenuNameType::MENU_TOP);

        $loginAccountInfo = parent::getLoginUser();

        $dashBoardData = DashBoardService::getDashBoardData($loginAccountInfo);
        $this->view_params->put('dashBoardData', $dashBoardData);

		return view('Admin.Top.index')->with('view_params', $this->view_params);
	}


}
