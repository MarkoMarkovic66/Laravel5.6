<?php

namespace App\Http\Controllers\Auth;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Http\Controllers\Controller;

use App\Enums\ResponseStatusType;

use App\Enums\ApiErrorCodeType;

use App\Utils\CommonUtils;
use Lang;
use App\User;


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
    protected $redirectTo = '/home';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('guest')->except('logout');
    }


    public function apiAuthenticate(Request $request)
    {
        $mail = $request->mail;
        $password = $request->password;
        $credentials = ['mail'=>$mail, 'password'=>$password];

        //try Login & get JWT-token
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                //ログイン失敗
                //return response()->json(['error' => 'Api Unauthorized'], 401);

                //カスタムエラー内容を返却する
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_LOGIN_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_LOGIN_MSG),
                    [
                        'mail'=>$mail,
                        'password'=>$password
                    ]
                );
                
                return response()->json($result, 401);
            }

        } catch (JWTException $e) {
            //カスタムエラー内容を返却する
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_LOGIN_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_LOGIN_MSG),
                [
                'JWTException' => $e->getMessage()
                ]
            );
            
            return response()->json($result, 500);
        }

        if (strtotime(\Auth::user()->closed_at) < time())  {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_ACCOUNT_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_ACCOUNT_EXPIRED_MSG),
                [
                    'closed_at' => \Auth::user()->closed_at
                ]
            );
            
            return response()->json($result, 500);
        }

        $result = CommonUtils::apiResultOK(
            [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth("api")->factory()->getTTL() * 60
            ]
        );

        return response()->json($result, 200);
    }

    /**
     * Check email API
     *
     * @return void
     */
    public function checkEmail(Request $request)
    {
        $mail = $request->mail;

        $check = User::where('mail', $mail)->where('is_deleted', 0)->count();
        
        if ($check) {
            $result = CommonUtils::apiResultOK();
            return response()->json($result, 200);
        } else {
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_LOGIN_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_LOGIN_MSG),
                [
                    'message' => Lang::get(ApiErrorCodeType::API_ERROR_LOGIN_MSG)
                ]
            );
            
            return response()->json($result, 401); 
        }
        
    }

    /**
     * @deprecated JWTではログアウト処理は不要
     * ログアウト処理
     */
    public function apiLogout(){
        //front側でJWT-tokenを破棄すればよく、サーバ側ではログアウト処理は不要
        //$this->middleware('jwt.refresh');
        JWTAuth::invalidate(JWTAuth::getToken());

        $result = CommonUtils::apiResultOK(
            [
                'logout' => 'logout complete'
            ]
        );

        return response()->json($result);
    }
    

    /**
     * @deprecated
     * ログインフォーム表示
     */
    /*
    public function showLoginForm(){
		$this->view_params['title'] = 'ログイン';
		$this->view_params['mail'] = '';
		$this->view_params['password'] = '';
        return view('Auth.login')->with('view_params', $this->view_params);
    }
    */

    /**
     * @deprecated
     * ログイン処理
     * @param Request $request
     */
    /*
    public function login(Request $request){
        $params = $request->all();
        if(isset($params['_token'])) {
            if(isset($params['mail']) && isset($params['password'])) {
                if (Auth::attempt(['mail' => $params['mail'], 'password' => $params['password']])) {
                    // 認証成功
                    $accountInfo = $this->getAccountInfo(Auth::user());
                    //アカウント情報をセッションに格納
                    Session::put(SessionValiableType::LOGIN_ACCOUNT_INFO, $accountInfo);
                    return redirect('/top');
                }
            }
        }

        // 認証失敗
        Session::forget(SessionValiableType::LOGIN_ACCOUNT_INFO);
        //ログイン画面再表示
		$this->view_params['title'] = 'ログイン';
		$this->view_params['mail'] = $params['mail'];
		$this->view_params['password'] = $params['password'];
        return view('Auth.login')->with('view_params', $this->view_params);
    }
    */

    /**
     * @deprecated
     * ログインアカウント情報取得
     * @param type $authUser
     * @return collection|boolean
     */
    /*
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
    */

}
