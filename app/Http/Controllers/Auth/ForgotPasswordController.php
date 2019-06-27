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
use App\Mail\PasswordReset;

use Validator;

/**
 * ForgotPasswordController class
 */
class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */


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
     * RemindPassword
     *
     * @param Request $request 
     * 
     * @return void
     */
    public function remindPassword(Request $request)
    {
        $validate = Validator::make(
            $request->all(),
            [
            'mail' => 'required|email',
            ]
        );
        if ($validate->fails()) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'mail' => $request->mail,
                ]
            );
            return response()->json($result, 401);
        }
        $user = User::where('mail', $request->mail)->first();
        if (!$user) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_PARAMS_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_PARAMS_MSG),
                [
                    'mail' => $request->mail,
                ]
            );

            return response()->json($result, 401);
        }

        $token = str_random(60);
        $user->remember_token = $token;
        $passwordReset = $user->save();

        if ($user && $passwordReset) {
            try {
                \Mail::to($request->mail)->send(new PasswordReset($user));
            } catch (\Throwable $th) {
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_OTHERS_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_OTHERS_MSG),
                    [
                        'MSG' => Lang::get('custom.mail_error')
                    ],
                    CommonUtils::getCommon()
                );
                return $result;
            }
            
        }

        //success
        $result = CommonUtils::apiResultOK(
            [
                'token' => $token
            ]
        );

        return response()->json($result, 200);
    }

    /**
     * Validate token reset password
     * 
     * @param Request $request 
     * 
     * @return void
     */
    public function validateTokenResetPassword(Request $request)
    {
        $validate = Validator::make(
            $request->all(),
            [
            'token' => 'required|string',
            ]
        );

        if ($validate->fails()) {
            $result = new ApiResponseDto();
            $apiError = new ApiErrorDto();
           
            $result = CommonUtils::apiResultNG(
                ApiErrorCodeType::API_ERROR_VALIDATION_CODE,
                Lang::get(ApiErrorCodeType::API_ERROR_VALIDATION_MSG),
                [
                    'token' => $request->token,
                ]
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

        $result = CommonUtils::apiResultOK();

        return response()->json($result, 200);
    }
}
