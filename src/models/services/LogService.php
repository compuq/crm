<?php
namespace LEX360\Models\Services;

use PDO;

class LogService
{
    /**
     * Registra una acción en logs_auditoria
     * @param PDO $db Conexión activa
     * @param int $userId ID del usuario
     * @param string $accion Acción (login, carga_csv, etc.)
     * @param string $tabla Tabla afectada
     * @param int|null $registroId ID del registro (opcional)
     * @param array|null $antes Datos antes del cambio (JSON)
     * @param array|null $despues Datos después del cambio (JSON)
     */
    public static function registrar(PDO $db, int $userId, string $accion, string $tabla, ?int $registroId = null, ?array $antes = null, ?array $despues = null): void
    {
        $stmt = $db->prepare("
            INSERT INTO logs_auditoria (usuario_id, accion, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip, fecha) 
            VALUES (:uid, :acc, :tab, :rid, :old, :new, :ip, NOW())
        ");
        
        $stmt->execute([
            'uid' => $userId,
            'acc' => $accion,
            'tab' => $tabla,
            'rid' => $registroId,
            'old' => $antes ? json_encode($antes) : null,
            'new' => $despues ? json_encode($despues) : null,
            'ip'  => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    }
}