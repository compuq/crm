<?php
define('APP_ENV', 'development'); // 'development' | 'production'
define('APP_URL', 'http://localhost/CRM');
define('TIMEZONE', 'America/Guatemala');

date_default_timezone_set(TIMEZONE);
error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'development' ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Crear carpeta de logs si no existe
if (!is_dir(__DIR__ . '/../storage/logs')) {
    mkdir(__DIR__ . '/../storage/logs', 0755, true);
}