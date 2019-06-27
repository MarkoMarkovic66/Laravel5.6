<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\User;
use Lang;
use App\Utils\CommonUtils;
use App\Enums\ApiErrorCodeType;

class CheckExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::check()) {
            $user = User::find(AUth::user()->id);

            $openAt = strtotime($user->opened_at);
            $closeAt = strtotime($user->closed_at);

            if (time() < $openAt) {
                //Exception account not open
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_ACCOUNT_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_ACCOUNT_NOT_ACTIVE_MSG),
                    []
                );

                return response()->json($result);
            }

            if (time() > $closeAt) {
                //Exception account exprired
                $result = CommonUtils::apiResultNG(
                    ApiErrorCodeType::API_ERROR_ACCOUNT_CODE,
                    Lang::get(ApiErrorCodeType::API_ERROR_ACCOUNT_EXPIRED_MSG),
                    []
                );

                return response()->json($result);
            }
        }

        return $next($request);
    }
}
