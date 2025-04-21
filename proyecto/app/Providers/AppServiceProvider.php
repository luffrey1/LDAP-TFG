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
        //
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
