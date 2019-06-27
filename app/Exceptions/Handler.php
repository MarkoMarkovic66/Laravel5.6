<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Auth;

use App\Exceptions\AppException;
use App\Enums\ResponseStatusType;
use App\Enums\ApiResultStatusType;
use App\Enums\ApiErrorCodeType;
use App\Dtos\ApiResponseDto;
use App\Dtos\ApiErrorDto;


class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        /*
         * 各種例外のハンドリング
         */
        if ($exception instanceof UnauthorizedHttpException){
            //JWT token 認証エラー
            //return response()->json(['error' => 'JWT token Unauthorized'], 401);

            //カスタムエラー内容を返却する
            $result = new ApiResponseDto();
            $result->status = ApiResultStatusType::NG;
            $apiError = new ApiErrorDto();
            $apiError->code = ApiErrorCodeType::API_ERROR_TOKEN_CODE;
            $apiError->message = ApiErrorCodeType::API_ERROR_TOKEN_MSG;
            $apiError->details = [
                        'message' => 'JWT token Unauthorized'
                    ];
            $result->errors = $apiError;
            return response()->json($result, 401);


        }elseif ($exception instanceof TokenMismatchException){
            //CSRFエラー
            Auth::logout();
            return redirect()->route('login');

        }elseif ($exception instanceof MethodNotAllowedHttpException){
            //実行不許可エラー
            Auth::logout();
            return response()->view("errors.commonError", ['exception' => $exception], 200);

        }else if (get_class($exception) == '\Illuminate\Session\TokenMismatchException'){
            //セッションタイムアウト
            Auth::logout();
            return redirect()->route('login');

        }else if (get_class($exception) == 'Illuminate\Auth\AuthenticationException'){
            //認証エラー
            Auth::logout();
            return redirect()->route('login');

        //ユーザロジック内での例外エラー
        }elseif($exception instanceof AppException) {
            if ($request->ajax()) {
                // Ajax-Requestの場合
                $statusCode = $exception->getStatusCode();
                $statusMessage = $exception->getMessage();
                return response()->json([
                    'status' => ResponseStatusType::OK,
                    'data' => [
                        'status' => $statusCode,
                        'message' => $statusMessage
                    ]
                ]);

            }else{
                // HTTP-Requestの場合：共通エラーページへ遷移する
                return response()->view("errors.commonError", ['exception' => $exception], 200);
            }
        }

        return parent::render($request, $exception);
    }
}
