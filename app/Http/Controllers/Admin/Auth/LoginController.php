<?php
namespace App\Http\Controllers\Admin\Auth;

use Auth;
use Session;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Http\Controllers\Controller;
use App\Enums\SessionValiableType;
use App\Models\AccountKind;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    //protected $redirectTo = '/top';
    protected $redirectTo = '/task';

	protected $view_params;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
  		$this->view_params = collect();
        $this->middleware('guest')->except('logout');
    }

    /**
     * ログインフォーム表示
     */
    public function showLoginForm(){
		$this->view_params['title'] = 'ログイン';
		$this->view_params['mail'] = '';
		$this->view_params['password'] = '';
        return view('Admin.Auth.login')->with('view_params', $this->view_params);
    }

    /**
     * ログイン処理
     * @param Request $request
     */
    public function login(Request $request){
        $params = $request->all();
        if(isset($params['_token'])) {
            if(isset($params['mail']) && isset($params['password'])) {
                if (Auth::attempt(['mail' => $params['mail'], 'password' => $params['password']])) {
                    // 認証成功
                    $accountInfo = $this->getAccountInfo(Auth::user());
                    //アカウント情報をセッションに格納
                    Session::put(SessionValiableType::LOGIN_ACCOUNT_INFO, $accountInfo);
                    return redirect($this->redirectTo);
                }
            }
        }

        // 認証失敗
        Session::forget(SessionValiableType::LOGIN_ACCOUNT_INFO);
        //ログイン画面再表示
		$this->view_params['title'] = 'ログイン';
		$this->view_params['mail'] = $params['mail'];
		$this->view_params['password'] = $params['password'];
        return view('Admin.Auth.login')->with('view_params', $this->view_params);
    }

    /**
     * ログアウト処理
     * @param Request $request
     */
    public function logout(Request $request){
        Auth::logout();
        Session::forget(SessionValiableType::LOGIN_ACCOUNT_INFO);
        return  redirect('/login');
    }

    /**
     * ログインアカウント情報取得
     * @param type $authUser
     * @return collection|boolean
     */
    private function getAccountInfo($authUser){
        if(empty($authUser)){
            return false;
        }
        $accountKind = AccountKind::find($authUser->account_kind_id);
        $accountInfo = collect($authUser);
        $accountInfo->forget('password');
        $accountInfo->forget('remember_token');
        $accountInfo->put('account_kind_name', $accountKind->kind_name);
        return $accountInfo;
    }


}
