<?php
date_default_timezone_set('America/Guatemala');

require_once 'vendor/autoload.php';
require_once 'config/config.php';

// ===================================================================
// ✅ OPTIMIZACIÓN PARA REPORTES MASIVOS Y EXPORTACIONES
// ===================================================================
// Permitir que PHP use más memoria (1GB) para que PhpSpreadsheet no colapse
ini_set('memory_limit', '1024M'); 

// Eliminar el límite de tiempo de ejecución. El script correrá 
// hasta que termine la consulta y genere el Excel.
set_time_limit(0); 
// ===================================================================


use LEX360\Core\Router;

// Capturar errores para no romper la app en producción
try {
    $router = new Router();
    $router->dispatch();
} catch (Exception $e) {
    if (APP_ENV === 'development') {
        echo "<pre>Error crítico: " . $e->getMessage() . "</pre>";
    } else {
        echo "Hubo un error interno. Intente más tarde.";
    }
}