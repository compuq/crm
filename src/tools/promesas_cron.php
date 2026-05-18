<?php
// src/tools/promesas_cron.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/models/dao/db/Database.php';

use LEX360\Models\DAO\DB\Database;

try {
    $db = Database::getInstance(); // Ajusta según tu instanciación real
    
    // Marcar como incumplidas las que pasaron de fecha sin pago
    $stmt = $db->prepare("
        UPDATE promesas 
        SET estatus = 'incumplida' 
        WHERE estatus = 'pendiente' 
          AND fecha_compromiso < CURRENT_DATE
    ");
    $stmt->execute();
    $afectadas = $stmt->rowCount();

    echo date('Y-m-d H:i:s') . " | ✅ Promesas vencidas marcadas como incumplidas: {$afectadas}\n";
    
} catch (\Exception $e) {
    echo date('Y-m-d H:i:s') . " | ❌ Error en cron de promesas: " . $e->getMessage() . "\n";
}