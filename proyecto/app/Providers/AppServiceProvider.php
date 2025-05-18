<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ...otros registros...
        $this->app->afterResolving('reverb.connector', function ($connector) {
            \Log::info('Registrando handler de whisper para Reverb (afterResolving)');
            $connector->listenWhisper('command', [\App\Events\CommandReceived::class, 'handleWhisper']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Usar Tailwind para la paginación
        Paginator::defaultView('pagination.custom');
        
        // Asegurarse de que la primera página siempre se muestre
        Paginator::useBootstrap();
    }
}
