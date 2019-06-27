<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AlueIntegActionWatchServiceProvider extends ServiceProvider
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
            '__alue_integ_action_watch_service',
            'App\Services\AlueCommandService\AlueIntegActionWatchService'
        );
    }
}
