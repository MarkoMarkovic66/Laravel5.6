<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TaskCalendarServiceProvider extends ServiceProvider
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
            '__task_calendar_service',
            'App\Services\TaskCalendarService'
        );
    }
}
