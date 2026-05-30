<?php
namespace LEX360\Models\Services;
use PDO;
use LEX360\Models\Dao\Db\Database;
use LEX360\Core\Session;

class BackupService
{
    protected Session $session;
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
            
            $idsStr = implode(',', $clienteIds);

            // 2. Migrar clientes
            $this->db->exec("INSERT INTO clientes_bk (id_original, lote_id, fecha_migracion, id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, nombre, saldo, saldo_inicial, telefono_1, telefono_2, estado, fecha_ultima_gestion, data_extras)
                SELECT id, $loteId, NOW(), id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, nombre, saldo, saldo_inicial, telefono_1, telefono_2, estado, fecha_ultima_gestion, data_extras FROM clientes WHERE id IN ($idsStr)");

            // 3. Migrar historial
            $this->db->exec("INSERT INTO historial_bk (id_original, lote_id, fecha_migracion, id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, id_tipologia, comentario, fecha_proxima_llamada, data_extras)
                SELECT id, $loteId, NOW(), id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, id_tipologia, comentario, fecha_proxima_llamada, data_extras FROM historial WHERE id_cliente IN ($idsStr)");

            // Agregar después de migrar historial (línea ~35)
            // 3.1 Migrar promesas
            $this->db->exec("INSERT INTO promesas_bk (id_original, lote_id, fecha_migracion, id_cliente, id_usuario, monto_prometido, fecha_compromiso, fecha_registro, estatus, id_historial)
            SELECT id, $loteId, NOW(), id_cliente, id_usuario, monto_prometido, fecha_compromiso, fecha_registro, estatus, id_historial 
            FROM promesas WHERE id_cliente IN ($idsStr)");

            // 3.2 Migrar pagos
            $this->db->exec("INSERT INTO pagos_bk (id_original, lote_id, fecha_migracion, id_cliente, monto, fecha_pago, referencia_bancaria, estatus, validado_por, fecha_validacion, id_historial)
            SELECT id, $loteId, NOW(), id_cliente, monto, fecha_pago, referencia_bancaria, estatus, validado_por, fecha_validacion, id_historial 
            FROM pagos WHERE id_cliente IN ($idsStr)");

            // 4. Eliminar dependencias PRIMERO
            $this->db->exec("DELETE FROM pagos WHERE id_cliente IN ($idsStr)");
            $this->db->exec("DELETE FROM promesas WHERE id_cliente IN ($idsStr)");
            $this->db->exec("DELETE FROM historial WHERE id_cliente IN ($idsStr)");
            $this->db->exec("DELETE FROM clientes WHERE id IN ($idsStr)");


            $this->db->commit();
            return ['success' => true, 'lote_id' => $loteId, 'migrados' => count($clienteIds)];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }    

public function migrar(): void
{
    $this->session->requireAuth();
    header('Content-Type: application/json');
    
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (empty($ids)) { 
        echo json_encode(['success' => false, 'msg' => 'No hay clientes seleccionados']); 
        exit; 
    }

    try {
        $this->db->beginTransaction();
        
        // 1. Primero eliminar/actualizar registros relacionados en PAGOS
        $idsStr = implode(',', array_map('intval', $ids));
        
        // Eliminar pagos relacionados
        $this->db->exec("DELETE FROM pagos WHERE id_historial IN (
            SELECT id FROM historial WHERE id_cliente IN ($idsStr)
        )");
        
        // Eliminar promesas relacionadas
        $this->db->exec("DELETE FROM promesas WHERE id_cliente IN ($idsStr)");
        
        // 2. Ahora sí migrar historial
        $this->db->exec("INSERT INTO historial_bk 
            SELECT h.*, NOW() as fecha_migracion 
            FROM historial h 
            WHERE h.id_cliente IN ($idsStr)");
        
        $this->db->exec("DELETE FROM historial WHERE id_cliente IN ($idsStr)");
        
        // 3. Migrar clientes
        $this->db->exec("INSERT INTO clientes_bk 
            SELECT c.*, NOW() as fecha_migracion 
            FROM clientes c 
            WHERE c.id IN ($idsStr)");
        
        $this->db->exec("DELETE FROM clientes WHERE id IN ($idsStr)");
        
        $this->db->commit();
        
        echo json_encode([
            'success' => true, 
            'migrados' => count($ids),
            'msg' => 'Migración completada exitosamente'
        ]);
        
    } catch (\Exception $e) {
        $this->db->rollBack();
        echo json_encode([
            'success' => false, 
            'error' => 'Error al migrar: ' . $e->getMessage()
        ]);
    }
}
    /**
     * Restaura clientes y su historial desde el backup al sistema activo
     */
    public function restaurar(array $clienteIds): array
    {
        $this->db->beginTransaction();
        try {
            $idsStr = implode(',', array_map('intval', $clienteIds));
            if (empty($idsStr)) throw new \Exception("No hay clientes seleccionados.");

            // 1. Restaurar Clientes (Insertando el ID original para mantener integridad)
            $this->db->exec("
                INSERT INTO clientes (
                    id, id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, 
                    nombre, saldo, saldo_inicial, telefono_1, telefono_2, estado, fecha_ultima_gestion, fecha_asignacion, data_extras
                )
                SELECT id_original, id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, 
                       nombre, saldo, saldo_inicial, telefono_1, telefono_2, estado, fecha_ultima_gestion, now(), data_extras
                FROM clientes_bk
                WHERE id_original IN ($idsStr)
            ");

            // 2. Restaurar Historial
            $this->db->exec("
                INSERT INTO historial (
                    id, id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, 
                    id_tipologia, comentario, data_extras, fecha_proxima_llamada
                )
                SELECT 
                    id_original, id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, 
                    id_tipologia, comentario, data_extras, fecha_proxima_llamada
                FROM historial_bk
                WHERE id_cliente IN ($idsStr)
            ");

            // 3. Restaurar Pagos (Si existen)
            // Nota: Ajusta esta parte si tu tabla pagos_bk tiene una estructura diferente
            if ($this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'pagos_bk')")->fetchColumn()) {
                $this->db->exec("
                    INSERT INTO pagos (id, id_cliente, monto, fecha_pago, referencia_bancaria, estatus, validado_por, fecha_validacion, id_historial)
                    SELECT id_original, id_cliente, monto, fecha_pago, referencia_bancaria, estatus, validado_por, fecha_validacion, id_historial
                    FROM pagos_bk
                    WHERE id_cliente IN ($idsStr)
                ");
            }

            // 4. Restaurar Promesas (Si existen)
            if ($this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'promesas_bk')")->fetchColumn()) {
                $this->db->exec("
                    INSERT INTO promesas (id, id_cliente, id_usuario, monto_prometido, fecha_compromiso, fecha_registro, estatus, id_historial)
                    SELECT id_original, id_cliente, id_usuario, monto_prometido, fecha_compromiso, fecha_registro, estatus, id_historial
                    FROM promesas_bk
                    WHERE id_cliente IN ($idsStr)
                ");
            }

            // 5. Limpiar Backup
            $this->db->exec("DELETE FROM historial_bk WHERE id_cliente IN ($idsStr)");
            $this->db->exec("DELETE FROM clientes_bk WHERE id_original IN ($idsStr)");
            if ($this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'pagos_bk')")->fetchColumn()) {
                $this->db->exec("DELETE FROM pagos_bk WHERE id_cliente IN ($idsStr)");
            }
            if ($this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'promesas_bk')")->fetchColumn()) {
                $this->db->exec("DELETE FROM promesas_bk WHERE id_cliente IN ($idsStr)");
            }

            $this->db->commit();
            return ['success' => true, 'migrados' => count($clienteIds)];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}