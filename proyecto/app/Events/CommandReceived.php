<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Events\TerminalOutputReceived;
use phpseclib3\Net\SSH2;

class CommandReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * ID de la sesión terminal
     *
     * @var string
     */
    public $sessionId;
    
    /**
     * Comando a ejecutar
     *
     * @var string
     */
    public $command;

    /**
     * Create a new event instance.
     * 
     * @param string $sessionId
     * @param string $command
     * @return void
     */
    public function __construct(string $sessionId, string $command)
    {
        $this->sessionId = $sessionId;
        $this->command = $command;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('terminal.' . $this->sessionId),
        ];
    }
    
    /**
     * Obtener el nombre del evento para broadcast
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'CommandReceived';
    }

    // Handler para whispers (Reverb)
    public static function handleWhisper($payload)
    {
        \Log::info('handleWhisper called', ['payload' => $payload]);
        $sessionId = $payload['channel_name'] ?? null;
        $command = $payload['payload']['command'] ?? null;
        \Log::info('handleWhisper: después de extraer sessionId y command', ['sessionId' => $sessionId, 'command' => $command]);
        if (!$sessionId || !$command) {
            \Log::warning('handleWhisper: sessionId or command missing', ['payload' => $payload]);
            \Log::info('Emitiendo evento TerminalOutputReceived desde handleWhisper (falta sessionId o command)', [
                'sessionId' => $sessionId,
                'output' => 'Whisper recibido pero falta sessionId o command',
                'currentDirectory' => '~'
            ]);
            broadcast(new \App\Events\TerminalOutputReceived('test', 'Whisper recibido pero falta sessionId o command', '~'));
            return;
        }
        $sessionId = str_replace('private-terminal.', '', $sessionId);
        \Log::info('handleWhisper: después de limpiar sessionId', ['sessionId' => $sessionId]);

        // Recuperar la sesión de la caché
        $session = \Cache::get("ssh_session:{$sessionId}");
        \Log::info('handleWhisper: después de recuperar sesión', ['session' => $session]);
        if (!$session) {
            \Log::warning('handleWhisper: Sesión SSH no encontrada', ['sessionId' => $sessionId]);
            \Log::info('Emitiendo evento TerminalOutputReceived desde handleWhisper (sesión no encontrada)', [
                'sessionId' => $sessionId,
                'output' => 'Error: Sesión SSH no encontrada',
                'currentDirectory' => '~'
            ]);
            broadcast(new \App\Events\TerminalOutputReceived($sessionId, "Error: Sesión SSH no encontrada", '~'));
            return;
        }
        $ssh = new \phpseclib3\Net\SSH2($session['ip_address']);
        $ssh->setTimeout(0); // Espera indefinida para evitar timeouts prematuros
        $authenticated = false;
        if (($session['auth_type'] ?? null) === 'key') {
            $key = \phpseclib3\Crypt\PublicKeyLoader::load(file_get_contents(storage_path('app/ssh/id_rsa')));
            if ($ssh->login($session['username'], $key)) {
                $authenticated = true;
            }
        } elseif (($session['auth_type'] ?? null) === 'password') {
            if ($ssh->login($session['username'], $session['password'])) {
                $authenticated = true;
            }
        }
        \Log::info('handleWhisper: después de autenticación', ['authenticated' => $authenticated]);
        if (!$authenticated) {
            \Log::warning('handleWhisper: SSH authentication failed', ['session' => $session]);
            \Log::info('Emitiendo evento TerminalOutputReceived desde handleWhisper (auth failed)', [
                'sessionId' => $sessionId,
                'output' => 'Error: La conexión SSH se ha perdido o las credenciales ya no son válidas',
                'currentDirectory' => $session['current_directory'] ?? '~'
            ]);
            broadcast(new \App\Events\TerminalOutputReceived($sessionId, "Error: La conexión SSH se ha perdido o las credenciales ya no son válidas", $session['current_directory'] ?? '~'));
            return;
        }
        $output = $ssh->exec($command);
        $stderr = $ssh->getStdError();
        $exitStatus = $ssh->getExitStatus();
        $phpseclibErrors = $ssh->getErrors();
        \Log::info('handleWhisper: después de ejecutar comando', [
            'command' => $command,
            'stdout' => $output,
            'stderr' => $stderr,
            'exitStatus' => $exitStatus,
            'phpseclibErrors' => $phpseclibErrors,
        ]);
        // Si es un comando que cambia el directorio, actualizar el directorio actual
        if (str_starts_with($command, 'cd ')) {
            $currentDirectory = trim($ssh->exec('pwd'));
            $session['current_directory'] = $currentDirectory;
            \Cache::put("ssh_session:{$sessionId}", $session, now()->addHours(4));
        }
        // Construir salida informativa
        $finalOutput = $output;
        if (empty($output) && !empty($stderr)) {
            $finalOutput = "[STDERR] " . $stderr;
        }
        if ($exitStatus !== 0 && $exitStatus !== false) {
            $finalOutput .= "\n[Exit code: $exitStatus]";
        }
        if (!empty($phpseclibErrors)) {
            $finalOutput .= "\n[phpseclib error: " . implode('; ', $phpseclibErrors) . "]";
        }
        // SIEMPRE mostrar algo en la terminal
        if (empty(trim($finalOutput))) {
            $finalOutput = "[Sin salida del comando. Puede ser un error de conexión, permisos, o el comando no genera output]";
        }
        \Log::info('Antes de emitir evento TerminalOutputReceived', [
            'sessionId' => $sessionId,
            'output' => $finalOutput,
            'currentDirectory' => $session['current_directory'] ?? '~'
        ]);
        broadcast(new \App\Events\TerminalOutputReceived($sessionId, $finalOutput, $session['current_directory'] ?? '~'));
        \Log::info('Después de emitir evento TerminalOutputReceived', [
            'sessionId' => $sessionId
        ]);
    }
} 