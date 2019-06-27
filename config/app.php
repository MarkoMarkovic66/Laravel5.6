<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', true),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    //'timezone' => 'UTC',
    'timezone' => 'Asia/Tokyo',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'ja',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log settings for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Settings: "single", "daily", "syslog", "errorlog"
    |
    */
/************* laravel 5.6 から laravel/config/logging.php に移動
    //'log' => env('APP_LOG', 'single'),
    'log' => env('APP_LOG', 'daily'), // logファイルを日付ごとに出力
    'log_max_files' => 365,
    'log_level' => env('APP_LOG_LEVEL', 'debug'),
***************/

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    /*
     * TASK_ANSWER_LIMIT_DAYS
     */
    'answer_limit_days' => env('TASK_ANSWER_LIMIT_DAYS', 3),

    /*
     * TIME_TOTAL_WORD_CARD
     */
    'time_total_word_card' => env('TIME_TOTAL_WORD_CARD', 60),

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * 2018-09-04 API認証：jwtトークン関連
         */
        Tymon\JWTAuth\Providers\LaravelServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        Maatwebsite\Excel\ExcelServiceProvider::class,

        /*
         * AlueCommand(バッチ処理)関連Service Provider
         */
        /*
        App\Providers\AlueDataImportServiceProvider::class,
        App\Providers\AlueChatworkPullServiceProvider::class,
        App\Providers\AlueChatworkPushServiceProvider::class,
        App\Providers\AlueCreateQuestionServiceProvider::class,
        App\Providers\AlueCreateTaskServiceProvider::class,
        App\Providers\AlueAchievementCountServiceProvider::class,
        */
        App\Providers\AlueIntegDataImportServiceProvider::class,
        App\Providers\AlueIntegCreateQuestionServiceProvider::class,
        App\Providers\AlueIntegCreateTaskServiceProvider::class,
        App\Providers\AlueIntegAchievementCountServiceProvider::class,
        App\Providers\AlueIntegMemberReportServiceProvider::class,
        App\Providers\AlueIntegActionWatchServiceProvider::class,

        App\Providers\AlueIntegNotifyManageServiceProvider::class,
        App\Providers\AlueIntegNotifyExecuteServiceProvider::class,


        /*
         * 宿題管理Service Provider
         */
        App\Providers\TaskServiceProvider::class,
        /*
         * ChatworkService Provider
         */
        App\Providers\ChatworkServiceProvider::class,

        /*
         * 会員管理Service Provider
         */
        App\Providers\MemberServiceProvider::class,
        App\Providers\TaskCalendarServiceProvider::class,

        /*
         * バッチ管理Service Provider
         */
        App\Providers\BatchServiceProvider::class,

        /*
         * DashBoardService Provider
         */
        App\Providers\DashBoardServiceProvider::class,

        /*
         * S3Service Provider
         */
        App\Providers\S3ServiceProvider::class,

        /*
		 * Debugger
		 */
		Barryvdh\Debugbar\ServiceProvider::class,

        /*
         * AWS S3
         */
        Aws\Laravel\AwsServiceProvider::class,
        Jenssegers\Agent\AgentServiceProvider::class,

        /*
         * Snappy
         */
        Barryvdh\Snappy\ServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Debugbar' => Barryvdh\Debugbar\Facade::class,
        'AWS' => Aws\Laravel\AwsFacade::class,
        'Agent' => Jenssegers\Agent\Facades\Agent::class,
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,


        /*
         * 2018-09-04 API認証：jwtトークン関連
         */
        'JWTAuth' => Tymon\JWTAuth\Facades\JWTAuth::class,
        'JWTFactory' => Tymon\JWTAuth\Facades\JWTFactory::class,


        /*
         * AlueCommand(バッチ処理)関連Service Facade
         */
        /*
        'AlueDataImportService' => App\Facades\AlueDataImportServiceFacade::class,
        'AlueChatworkPullService' => App\Facades\AlueChatworkPullServiceFacade::class,
        'AlueChatworkPushService' => App\Facades\AlueChatworkPushServiceFacade::class,
        'AlueCreateQuestionService' => App\Facades\AlueCreateQuestionServiceFacade::class,
        'AlueCreateTaskService' => App\Facades\AlueCreateTaskServiceFacade::class,
        'AlueAchievementCountService' => App\Facades\AlueAchievementCountServiceFacade::class,
        */
        'AlueIntegDataImportService' => App\Facades\AlueIntegDataImportServiceFacade::class,
        'AlueIntegCreateQuestionService' => App\Facades\AlueIntegCreateQuestionServiceFacade::class,
        'AlueIntegCreateTaskService' => App\Facades\AlueIntegCreateTaskServiceFacade::class,
        'AlueIntegAchievementCountService' => App\Facades\AlueIntegAchievementCountServiceFacade::class,
        'AlueIntegMemberReportService' => App\Facades\AlueIntegMemberReportServiceFacade::class,
        'AlueIntegActionWatchService' => App\Facades\AlueIntegActionWatchServiceFacade::class,

        'AlueIntegNotifyManageService' => App\Facades\AlueIntegNotifyManageServiceFacade::class,
        'AlueIntegNotifyExecuteService' => App\Facades\AlueIntegNotifyExecuteServiceFacade::class,

        
        /*
         * 宿題管理Service Facade
         */
        'TaskService' => App\Facades\TaskServiceFacade::class,
        /*
         * ChatworkService Facade
         */
        'ChatworkService' => App\Facades\ChatworkServiceFacade::class,

        /*
         * 会員管理Service Facade
         */
        'MemberService' => App\Facades\MemberServiceFacade::class,
        'TaskCalendarService' => App\Facades\TaskCalendarServiceFacade::class,

        /*
         * バッチ管理Service Facade
         */
        'BatchService' => App\Facades\BatchServiceFacade::class,

        /*
         * DashBoardService Facade
         */
        'DashBoardService' => App\Facades\DashBoardServiceFacade::class,

        /*
         * S3Service Facade
         */
        'S3Service' => App\Facades\S3ServiceFacade::class,

        /*
         * Snappy
         */
        'SnappyPdf' => Barryvdh\Snappy\Facades\SnappyPdf::class,
        'SnappyImage' => Barryvdh\Snappy\Facades\SnappyImage::class,
    ],

];
