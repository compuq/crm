<?php
// Configuración de la Base de Datos Externa
return [
    'host'     => '195.26.255.73', // IP del servidor externo
    'port'     => '5432',      // Puerto
    'dbname'   => 'main_db',// Nombre de la BD externa
    'user'     => 'externo',
    'password' => '@CC3S0360',
    'charset'  => 'utf8',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]
];