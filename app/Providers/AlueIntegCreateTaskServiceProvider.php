<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AlueIntegCreateTaskServiceProvider extends ServiceProvider
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
            '__alue_integ_cretae_task_service',
            'App\Services\AlueCommandService\AlueIntegCreateTaskService'
        );
    }
}
