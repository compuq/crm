<?php
namespace LEX360\Models\Dao\Db;

use PDO;
use PDOException;

class DatabaseExterna
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require_once __DIR__ . '/../../../../config/database_externo.php';
            
            $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};";
            
            try {
                self::$instance = new PDO($dsn, $config['user'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                error_log("DB Connection failed: " . $e->getMessage());
                if (APP_ENV === 'development') {
                    die("🔴 Error de conexión a PostgreSQL: " . htmlspecialchars($e->getMessage()));
                }
                die("⚠️ Servicio no disponible. Intente más tarde.");
            }
        }
        return self::$instance;
    }
}