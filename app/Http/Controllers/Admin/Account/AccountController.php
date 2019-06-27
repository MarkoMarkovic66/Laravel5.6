<?php
namespace App\Http\Controllers\Admin\Account;

use App\Models\Account;
use App\Models\AccountKind;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseViewController;
use App\Enums\MenuNameType;
use Illuminate\Support\Facades\Hash;

/**
 * AccountController
 */
class AccountController extends BaseViewController {

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

        //指定マスタを切り替え
        $Account = new Account();
        $AccountKind = new AccountKind();

        //新規登録・更新・削除
        if(isset($params['_token'])) {

            if(isset($params['btn-create'])) {

                foreach ($Account->columns as $key => $val) {
                    if($key != 'id' && $key != 'created_at' && $key != 'updated_at' && $key != 'deleted_at') {
                        $insert_data[$key] = $params['create-'.$key];
                    }
                }
                $Account->setData([$insert_data]);

            }

            if(isset($params['btn-update'])) {

                //Log::debug($params);
                $sel_update_id = $params['sel_update_id'];

                $where = array(
                    'id' => $sel_update_id
                );

                //パスワード変更確認
                $account_data = $Account->getOne($sel_update_id);
                $account_password = $account_data->password;
                if($account_password != $params['edit-password']) {
                    $params['edit-password'] = Hash::make($params['edit-password']);
                }
                if($params['edit-assign_flag'] == null) {
                    $params['edit-assign_flag'] = 0;
                }
                if($params['edit-authority'] == null) {
                    $params['edit-authority'] = 0;
                }

                foreach ($Account->columns as $key => $val) {
                    if($key != 'updated_at' && $key != 'deleted_at') {
                        $update_data[$key] = $params['edit-'.$key];
                    }
                }
                $Account->updateData($where, $update_data);

            }

            if(isset($params['btn-delete'])) {

                //該当データを論理削除
                $sel_delete_id = $params['sel_delete_id'];
                $Account->deleteData($sel_delete_id);

            }
        }

        $search_params = (isset($params)) ? $params : null;
        $search_word = (isset($params['search_word'])) ? $params['search_word'] : null;
        if(isset($params['btn-search'])) {
            //検索ボタン、またはEnterを押したときはoffsetをリセット
            $offset = 0;
        }
        $limit = 50;
        $skip = $offset * $limit;
        $dataList = $Account->search($search_params, $search_word, $skip, $limit);

        //全件データも取得（ページング用）
        $dataAll = $Account->search($search_params, $search_word, null, null);

        //検索パラメータ文字列を生成（ページング用）
        $search_str = "?";
        foreach($Account->columns as $key => $val) {
            if($key != 'id' && $key != 'deleted_at' && $key != 'created_at' && $key != 'updated_at') {
                if(isset($search_params[$key])) {
                    $search_str .= $key . "=" . $search_params[$key] . "&";
                }
            }
        }
        if(isset($search_word)) {
            $search_str .= "search_word=" . $search_word . "&";
        }
        $search_str = substr($search_str, 0, -1);

        //アカウント種別データを取得
        $account_kinds = $AccountKind->getAll();

        $this->view_params['login_user'] = $login_user;
        $this->view_params['account_kinds'] = $account_kinds;
        $this->view_params['search_str'] = $search_str; //ページング用パラメータ
        $this->view_params['search_params'] = $search_params; //検索ボタン用パラメータ
        $this->view_params['search_word'] = $search_word; //フリーワード検索
        $this->view_params['dataAll'] = $dataAll;
        $this->view_params['dataList'] = $dataList;
        $this->view_params['columns'] = $Account->columns;
        $this->view_params['name'] = $Account->name;
        $this->view_params['offset'] = $offset;
        $this->view_params['limit'] = $limit;
        $this->view_params['next'] = ((int)(count($dataAll)/$limit) > $offset) ? $offset + 1 : (int)(count($dataAll)/$limit);
        $this->view_params['prev'] = ($offset > 0) ? $offset - 1 : 0;
        $this->view_params->put('menuName', MenuNameType::MENU_ACCOUNT);

//        Log::debug($params);

        return view('Admin.Account.index', $this->view_params)->with('view_params', $this->view_params);
	}


}
