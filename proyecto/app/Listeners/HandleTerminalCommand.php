<?php

namespace App\Listeners;

use App\Events\CommandReceived;
use App\Events\TerminalOutputReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class HandleTerminalCommand
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\CommandReceived  $event
     * @return void
     */
    public function handle(CommandReceived $event)
    {
        $sessionId = $event->sessionId;
        $command = $event->command;
        $session = Cache::get("ssh_session:{$sessionId}");
        if (!$session) {
            broadcast(new TerminalOutputReceived($sessionId, "Error: Sesión SSH no encontrada", '~'));
            return;
        }
        $ssh = new SSH2($session['ip_address']);
        $authenticated = false;
        if (($session['auth_type'] ?? null) === 'key') {
            $key = PublicKeyLoader::load(file_get_contents(storage_path('app/ssh/id_rsa')));
            if ($ssh->login($session['username'], $key)) {
                $authenticated = true;
            }
        } elseif (($session['auth_type'] ?? null) === 'password') {
            if ($ssh->login($session['username'], $session['password'])) {
                $authenticated = true;
            }
        }
        if (!$authenticated) {
            broadcast(new TerminalOutputReceived($sessionId, "Error: La conexión SSH se ha perdido o las credenciales ya no son válidas", $session['current_directory'] ?? '~'));
            return;
        }
        $output = $ssh->exec($command);
        // Si es un comando que cambia el directorio, actualizar el directorio actual
        if (str_starts_with($command, 'cd ')) {
            $currentDirectory = trim($ssh->exec('pwd'));
            $session['current_directory'] = $currentDirectory;
            Cache::put("ssh_session:{$sessionId}", $session, now()->addHours(4));
        }
        broadcast(new TerminalOutputReceived($sessionId, $output, $session['current_directory'] ?? '~'));
    }
} 