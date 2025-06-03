<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use App\Logging\ActivityLogger;
use Monolog\Logger;

class ActivityLogServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('log.activity', function ($app) {
            return new Logger('activity', [
                new ActivityLogger(
                    $app['config']->get('logging.channels.activity.level', Logger::DEBUG),
                    $app['config']->get('logging.channels.activity.bubble', true)
                )
            ]);
        });

        Log::extend('activity', function ($app, array $config) {
            return $app['log.activity'];
        });
    }

    public function boot()
    {
        //
    }
} 