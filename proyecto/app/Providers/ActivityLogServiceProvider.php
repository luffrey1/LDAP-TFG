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
        Log::extend('activity', function ($app, array $config) {
            return new Logger('activity', [
                new ActivityLogger(
                    $config['level'] ?? Logger::DEBUG,
                    $config['bubble'] ?? true
                )
            ]);
        });
    }

    public function boot()
    {
        //
    }
} 