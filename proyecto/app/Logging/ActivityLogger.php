<?php

namespace App\Logging;

use App\Models\ActivityLog;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class ActivityLogger extends AbstractProcessingHandler
{
    public function __construct($level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $level = strtoupper($record['level_name']);
        $message = $record['message'];
        $context = $record['context'];

        ActivityLog::create([
            'level' => $level,
            'user' => auth()->user()->name ?? null,
            'action' => $context['action'] ?? 'Información',
            'description' => $message
        ]);
    }
} 