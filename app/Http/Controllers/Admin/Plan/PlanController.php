<?php
namespace App\Http\Controllers\Admin\Plan;

use App\Models\Plan;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseViewController;
use App\Enums\MenuNameType;

use Illuminate\Support\Facades\Hash;

/**
 * AccountController
 */
class PlanController extends BaseViewController {

    public function index(Request $request, $offset = 0)
    {
        //ログインユーザ情報
        $login_user = $this->getLoginUser();

        //管理者、または運用担当のみがアクセス可能
        if ($login_user['account_kind_id'] != 1 && $login_user['account_kind_id'] != 2) {
            return redirect('/');
        }

        //リクエストデータを取得
        $params = $request->all();

        $Plan = new Plan();

        $search_params = (isset($params)) ? $params : null;
        $search_word = (isset($params['search_word'])) ? $params['search_word'] : null;
        if(isset($params['btn-search'])) {
            //検索ボタン、またはEnterを押したときはoffsetをリセット
            $offset = 0;
        }
        $limit = 50;
        $skip = $offset * $limit;
        //$dataList = $Plan->search($search_params, $search_word, $skip, $limit);

        //全件データも取得（ページング用）
        //$dataAll = $Plan->search($search_params, $search_word, null, null);

        //検索パラメータ文字列を生成（ページング用）
        $search_str = "?";
        /*foreach($Plan->columns as $key => $val) {
            if($key == 'alugo_user_id' && $key == 'eval') {
                if(isset($search_params[$key])) {
                    $search_str .= $key . "=" . $search_params[$key] . "&";
                }
            }
        }*/

        $search_str = substr($search_str, 0, -1);

        //アカウント種別データを取得
        //$account_kinds = $AccountKind->getAll();

        $this->view_params['login_user'] = $login_user;
        //$this->view_params['account_kinds'] = $account_kinds;
        $this->view_params['search_str'] = $search_str; //ページング用パラメータ
        //$this->view_params['search_params'] = $search_params; //検索ボタン用パラメータ
        //$this->view_params['search_word'] = $search_word; //フリーワード検索
        //$this->view_params['dataAll'] = $dataAll;
        //$this->view_params['dataList'] = $dataList;
        //$this->view_params['columns'] = $Plan->columns;
        $this->view_params['offset'] = $offset;
        //$this->view_params['limit'] = $limit;
       // $this->view_params['next'] = ((int)(count($dataAll)/$limit) > $offset) ? $offset + 1 : (int)(count($dataAll)/$limit);
        //$this->view_params['prev'] = ($offset > 0) ? $offset - 1 : 0;
        $this->view_params->put('menuName', MenuNameType::MENU_PLAN);

        return view('Admin.Plan.index', $this->view_params)->with('view_params', $this->view_params);
    }

}
