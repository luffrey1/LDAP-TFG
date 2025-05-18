<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TerminalOutputReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * ID de la sesión terminal
     *
     * @var string
     */
    public $sessionId;
    
    /**
     * Salida del comando
     *
     * @var string
     */
    public $output;
    
    /**
     * Directorio actual
     *
     * @var string
     */
    public $currentDirectory;

    /**
     * Create a new event instance.
     * 
     * @param string $sessionId
     * @param string $output
     * @param string $currentDirectory
     * @return void
     */
    public function __construct(string $sessionId, string $output, string $currentDirectory = '~')
    {
        $this->sessionId = $sessionId;
        $this->output = $output;
        $this->currentDirectory = $currentDirectory;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('terminal.' . $this->sessionId),
        ];
    }
    
    /**
     * Obtener el nombre del evento para broadcast
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'TerminalOutputReceived';
    }
} 