<?php

namespace App\Logging;

use App\Models\ActivityLog;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class ActivityLogger extends AbstractProcessingHandler
{
    public function __construct($level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        try {
            $level = strtoupper($record->level->name);
            $message = $record->message;
            $context = $record->context;

            ActivityLog::create([
                'level' => $level,
                'user' => auth()->user()->name ?? session('auth_user')['username'] ?? 'Sistema',
                'action' => $context['action'] ?? 'Información',
                'description' => $message
            ]);
        } catch (\Exception $e) {
            // Si hay un error al escribir el log, lo registramos en el log del sistema
            \Illuminate\Support\Facades\Log::error('Error al escribir log de actividad: ' . $e->getMessage());
        }
    }
} 