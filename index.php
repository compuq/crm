<?php



require_once 'vendor/autoload.php';
require_once 'config/config.php';

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