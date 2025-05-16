<?php

// Script personalizado para iniciar Laravel Reverb sin requerir extensión pcntl
// Este script suprime las advertencias relacionadas con señales

use Illuminate\Contracts\Console\Kernel;

// Definir constantes de señales si no existen para evitar errores
if (!defined('SIGINT')) define('SIGINT', 2);
if (!defined('SIGTERM')) define('SIGTERM', 15);
if (!defined('SIGTSTP')) define('SIGTSTP', 20);
if (!defined('SIGUSR1')) define('SIGUSR1', 10);
if (!defined('SIGUSR2')) define('SIGUSR2', 12);

// Definir función vacía de pcntl_signal si no existe
if (!function_exists('pcntl_signal')) {
    function pcntl_signal($signo, $handler, $restart_syscalls = true) {
        // No hacer nada, solo evitar el error
        return true;
    }
}

// Definir función vacía de pcntl_signal_dispatch si no existe
if (!function_exists('pcntl_signal_dispatch')) {
    function pcntl_signal_dispatch() {
        // No hacer nada, solo evitar el error
        return true;
    }
}

// Cargar el autoloader de Composer
require __DIR__.'/vendor/autoload.php';

// Suprimir errores/advertencias para ignorar los mensajes sobre señales
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

$app = require_once __DIR__.'/bootstrap/app.php';

// Ejecutar el comando Reverb:start
$status = $app->make(Kernel::class)->call('reverb:start');

exit($status); 