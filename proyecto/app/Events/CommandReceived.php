<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandReceived implements ShouldBroadcast
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
} 