<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session", "token"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            //'provider' => 'users',
            //'provider' => 'alue_integ_users',
            'provider' => 'alue_integ_admin',
        ],

        'api' => [
            // 2018-09-03 jwtを使用するように変更した
            //'driver' => 'token',
            //'provider' => 'users',
            'driver' => 'jwt',
            'provider' => 'alue_integ_users',
        ],

        //2018-12-11 管理画面auth
        'admin' => [
            'driver' => 'session',
            'provider' => 'alue_integ_admin',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\User::class,
        ],

        'alue_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\AuthAccount::class,
        ],

        //2018-09-03 usersテーブルを使用して認証する
        'alue_integ_users' => [
            'driver' => 'eloquent',
            'model' => App\User::class,
        ],

        //2018-12-11 管理画面auth
        'alue_integ_admin' => [
            'driver' => 'eloquent',
            'model' => App\Models\AuthAccount::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expire time is the number of minutes that the reset token should be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    */

    //2018-09-03 パスワードリセットは変更する必要がある
    // @TODO
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
        ],
    ],

];
