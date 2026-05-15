<?php
namespace LEX360\Models\Services;
use PDO;
use LEX360\Models\Dao\Db\Database;

class BackupService
{
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function migrarAHistorico(array $clienteIds, int $userId): array
    {
        $this->db->beginTransaction();
        try {
            // 1. Crear lote
            $this->db->prepare("INSERT INTO lotes (usuario_id, tipo_operacion, cantidad_registros, estado) VALUES (:uid, 'migracion_backup', :cant, 'completado') RETURNING id")
                     ->execute(['uid' => $userId, 'cant' => count($clienteIds)]);
            $loteId = $this->db->lastInsertId();

            // 2. Migrar clientes
            $idsStr = implode(',', $clienteIds);
            $this->db->exec("INSERT INTO clientes_bk (id_original, lote_id, fecha_migracion, id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, nombre, saldo, telefono_1, telefono_2, estado, fecha_ultima_gestion)
                             SELECT id, $loteId, NOW(), id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, nombre, saldo, telefono_1, telefono_2, estado, fecha_ultima_gestion FROM clientes WHERE id IN ($idsStr)");
            
            // 3. Migrar historial
            $this->db->exec("INSERT INTO historial_bk (id_original, lote_id, fecha_migracion, id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, id_tipologia, comentario)
                             SELECT id, $loteId, NOW(), id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, id_tipologia, comentario FROM historial WHERE id_cliente IN ($idsStr)");

            // 4. Eliminar de tablas activas
            $this->db->exec("DELETE FROM historial WHERE id_cliente IN ($idsStr)");
            $this->db->exec("DELETE FROM clientes WHERE id IN ($idsStr)");

            $this->db->commit();
            return ['success' => true, 'lote_id' => $loteId, 'migrados' => count($clienteIds)];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}