<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DashBoardServiceProvider extends ServiceProvider
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
            '__dashboard_service',
            'App\Services\DashBoardService'
        );
    }
}
