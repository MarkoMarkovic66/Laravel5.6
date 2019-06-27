<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ChatworkServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            '__chatwork_service',
            'App\Services\ChatworkService'
        );
    }
}
