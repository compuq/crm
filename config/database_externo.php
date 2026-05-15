<?php
// Configuración de la Base de Datos Externa
return [
    'host'     => '127.0.0.1', // IP del servidor externo
    'port'     => '5432',      // Puerto
    'dbname'   => 'db_externa',// Nombre de la BD externa
    'user'     => 'usuario_externo',
    'password' => 'clave_externa',
    'charset'  => 'utf8',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]
];