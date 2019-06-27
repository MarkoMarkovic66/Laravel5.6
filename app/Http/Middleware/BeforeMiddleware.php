<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use View;

use App\Models\AuthAccount;

class BeforeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $currentBasicInfo = [];

        //関連と日時の取得とセット
        $currentDateTime = date('Y-m-d');
        $currentDateTimeForDisp = date('Y/m/d');
        $currentBasicInfo['currentDateTime'] = $currentDateTime;
        $currentBasicInfo['currentDateTimeForDisp'] = $currentDateTimeForDisp;

        //カレントパスの取得とセット
        $uri = $request->path();
        $currentBasicInfo['currentUri'] = $uri;

        return $next($request);
    }
}
