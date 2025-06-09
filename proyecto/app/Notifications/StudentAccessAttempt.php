<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class StudentAccessAttempt extends Notification implements ShouldQueue
{
    use Queueable;

    protected $studentInfo;

    public function __construct($studentInfo)
    {
        $this->studentInfo = $studentInfo;
    }

    public function via($notifiable)
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'type' => 'student_access_attempt',
            'data' => [
                'username' => $this->studentInfo['username'],
                'nombre' => $this->studentInfo['nombre'],
                'hostname' => $this->studentInfo['hostname'],
                'timestamp' => now()->format('d/m/Y H:i:s'),
                'ip' => request()->ip()
            ]
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'username' => $this->studentInfo['username'],
            'nombre' => $this->studentInfo['nombre'],
            'hostname' => $this->studentInfo['hostname'],
            'timestamp' => now()->format('d/m/Y H:i:s'),
            'ip' => request()->ip()
        ];
    }
} 