<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\CommandReceived;
use App\Listeners\HandleTerminalCommand;
use Illuminate\Pagination\Paginator;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        CommandReceived::class => [
            HandleTerminalCommand::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Usar Tailwind para la paginación
        Paginator::defaultView('pagination.custom');
        
        // Asegurarse de que la primera página siempre se muestre
        Paginator::useBootstrap();

        // Solo registrar el handler si estamos en el proceso de Reverb
        if (app()->runningInConsole() && $this->app->bound('reverb.connector')) {
            \Log::info('Registrando handler de whisper para Reverb (proceso console)');
            echo "Registrando handler de whisper para Reverb (proceso console)\n";
            $this->app->resolving('reverb.connector', function ($connector) {
                $connector->listenWhisper('command', [\App\Events\CommandReceived::class, 'handleWhisper']);
            });
        } else {
          //  \Log::info('NO se registra handler de whisper (no es proceso console o no está reverb.connector)');
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 