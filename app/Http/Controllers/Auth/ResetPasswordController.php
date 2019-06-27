<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Enums\ResponseStatusType;
use App\Enums\ApiResultStatusType;
use App\Enums\ApiErrorCodeType;

use App\Dtos\ApiResponseDto;
use App\Dtos\ApiErrorDto;
use App\Utils\CommonUtils;
use Lang;
use App\User;
use Illuminate\Support\Facades\Hash;
use Validator;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    /**
     * Where to redirect users after resetting their password.
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
        //$this->middleware('guest');
    }

    /**
     * ResetPassword
     *
     * @param Request $request 
     * 
     * @return void
     */
    public function resetPassword(Request $request)
    {
        $validate = Validator::make(
            $request->all(),
            [
                'password'              => 'required|min:8|max:20|regex:/(^([a-zA-Z0-9!@#$%^&*()-_+ ]+)(\d+)?$)/u',
                'password_confirmation' => 'required|same:password',
            ]
        );

        if ($validate->fails()) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                $validate->messages()->toArray()
            );
            return response()->json($result, 401);
        }
        
        $user = User::where('remember_token', $request->token)->first();

        if (!$user) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'token' => $request->token,
                ]
            );
            return response()->json($result, 401);

        }

        //update password
        $password = Hash::make($request->password);
        $user->password = $password;
        $passwordReset = $user->save();

        if ($passwordReset) {
            //update token of user
            $user->remember_token = null;
            $tokenReset = $user->save();
            $result = CommonUtils::apiResultOK();
            return response()->json($result, 200);
        } else {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_DB_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_DB_MSG),
                [
                    'code' => 'Error in DB'. Config::get('database.connections.mysql.database'),
                    'message' => 'Can update password for user has mail: ' . $user->mail
                ]
            );
            return response()->json($result, 401);
        }
    }
}
