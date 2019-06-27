<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Enums\ResponseStatusType;
use App\Enums\SessionValiableType;

use Illuminate\Contracts\Validation\Validator;
use Log;
use Auth;

abstract class AjaxBaseController extends Controller
{
    public function __construct() {
    }

    /**
     * Ajaxリクエストに対し
     * Ajax処理ステータスと処理結果のJSONオブジェクトを返す
     *
     * @param  boolean $status
     * @param  array $data
     * @return Response
     */
    protected function responseJsonData($status, array $data) {
        return response()->json([
            'status' => ($status ? ResponseStatusType::OK : ResponseStatusType::NG),
            'data' => $data
        ]);
    }

    /**
     * Ajaxリクエストに対し処理ステータスおよびメッセージを返す
     * ※Ajax処理ステータスはOK固定であり、
     * 処理結果のJSONオブジェクト内に
     * 処理ステータスおよびメッセージをセットして返す
     * ※サーバ処理内でエラーが発生した場合などに使用する
     *
     * @param boolean $status
     * @param array $messages
     * @return Response
     */
    protected function responseJsonStatus($status, $messages=[]) {
        return response()->json([
            'status' => ($status ? ResponseStatusType::OK : ResponseStatusType::NG),
            'data' => [
                'status' => ($status ? ResponseStatusType::OK : ResponseStatusType::NG),
                'message' => $messages
            ]
        ]);
    }

    /**
     * Ajaxリクエストに対しAjax処理ステータスのみを返す
     *
     * @param  boolean $status
     * @return Response
     */
    protected function responseJsonAjaxStatus($status) {
        return response()->json([
            'status' => ($status ? ResponseStatusType::OK : ResponseStatusType::NG),
            'data' => null
        ]);
    }

	//ログインユーザ情報を取得
	protected function getLoginUser() {
        $login_user = session(SessionValiableType::LOGIN_ACCOUNT_INFO);
		return $login_user->all();
	}

}
