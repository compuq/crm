<?php
return [
    'host'     => '217.77.15.168',
    'port'     => '5432',
    'dbname'   => 'crm',
    'user'     => 'postgres',      // ← Ajusta a tu usuario
    'password' => 'C0N3CT4D0',   // ← Ajusta a tu contraseña
    'charset'  => 'utf8',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Importante para PostgreSQL
        PDO::ATTR_PERSISTENT         => false
    ]
];