<?php

echo "Verificando configuración de Apache" . PHP_EOL;

// Información de PHP
echo "PHP version: " . phpversion() . PHP_EOL;
echo "Server software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'No disponible') . PHP_EOL;

// Verificar si mod_rewrite está habilitado
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $mod_rewrite = in_array('mod_rewrite', $modules);
    echo "mod_rewrite está " . ($mod_rewrite ? "habilitado" : "deshabilitado") . PHP_EOL;
} else {
    echo "No se pudo verificar si mod_rewrite está habilitado (probablemente estás usando PHP-FPM)" . PHP_EOL;
}

// Verificar el directorio raíz del servidor
echo "Directorio raíz del servidor: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'No disponible') . PHP_EOL;

// Verificar permisos
$publicDir = __DIR__ . '/public';
echo "Directorio público: " . $publicDir . PHP_EOL;
if (file_exists($publicDir)) {
    echo "Permisos del directorio público: " . substr(sprintf('%o', fileperms($publicDir)), -4) . PHP_EOL;
} else {
    echo "¡ADVERTENCIA! El directorio público no existe" . PHP_EOL;
}

$htaccessFile = $publicDir . '/.htaccess';
if (file_exists($htaccessFile)) {
    echo "Archivo .htaccess existe" . PHP_EOL;
    echo "Permisos del archivo .htaccess: " . substr(sprintf('%o', fileperms($htaccessFile)), -4) . PHP_EOL;
} else {
    echo "¡ADVERTENCIA! Archivo .htaccess no existe" . PHP_EOL;
}

// Listar archivos en el directorio público
echo PHP_EOL . "Archivos en el directorio público:" . PHP_EOL;
if (is_dir($publicDir)) {
    $files = scandir($publicDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo " - " . $file . PHP_EOL;
        }
    }
} else {
    echo "No se pudo listar archivos en el directorio público" . PHP_EOL;
}

// Instrucciones para el usuario
echo PHP_EOL . "Para que el archivo .htaccess funcione, asegúrate de que:" . PHP_EOL;
echo "1. El módulo mod_rewrite esté habilitado en Apache" . PHP_EOL;
echo "2. AllowOverride esté configurado como 'All' en la configuración del VirtualHost" . PHP_EOL;
echo "3. Los permisos del archivo .htaccess permitan que Apache lo lea" . PHP_EOL; 