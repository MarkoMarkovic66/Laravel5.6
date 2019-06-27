<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AlueIntegNotifyExecuteServiceProvider extends ServiceProvider
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
            '__alue_integ_notify_execute_service',
            'App\Services\AlueCommandService\AlueIntegNotifyExecuteService'
        );
    }
}
