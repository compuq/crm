<?php
namespace LEX360\Models\Dao;

use LEX360\Core\BaseDao;

class ClienteDao extends BaseDao
{
    protected string $table = 'clientes';

    // ... tus métodos anteriores ...

    /**
     * Obtener estadísticas para el Dashboard
     * @param int $userId ID del usuario logueado
     * @param string $role Rol del usuario (gestor, supervisor, etc.)
     */
    public function getEstadisticas(int $userId, string $role): array
    {
        // Definir el filtro según el rol
        $where = ($role === 'gestor') 
            ? "WHERE id_gestor_asignado = :uid" 
            : "WHERE id_supervisor_cadena = :uid";

        $params = ['uid' => $userId];

        // 1. Total Asignados
        $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM clientes $where");
        $stmtTotal->execute($params);
        $totalAsignados = $stmtTotal->fetchColumn();

        // 2. Llamadas para Hoy (Gestiones pendientes o nuevas hoy)
        // Asumimos que si no tienen fecha_ultima_gestion, son nuevos para hoy
        $stmtHoy = $this->db->prepare("
            SELECT COUNT(*) FROM clientes 
            $where 
            AND (fecha_ultima_gestion IS NULL OR DATE(fecha_ultima_gestion) < CURRENT_DATE)
        ");
        $stmtHoy->execute($params);
        $llamadasHoy = $stmtHoy->fetchColumn();

        // 3. Promesas de Pago Hoy (Futuro: Tabla Promesas)
        // Por ahora simulamos 0 o sacamos de una tabla promesas si ya existe
        $promesasHoy = 0; 

        return [
            'total_asignados' => $totalAsignados,
            'llamadas_hoy'    => $llamadasHoy,
            'promesas_hoy'    => $promesasHoy
        ];
    }
    
    public function findByRole(int $userId, string $role, string $search = ''): array
    {
        $where = ($role === 'gestor') 
            ? "WHERE id_gestor_asignado = :uid" 
            : "WHERE id_supervisor_cadena = :uid";

        $sql = "SELECT id, cuenta, nombre, identificacion, saldo, telefono_1, estado, fecha_ultima_gestion 
                FROM clientes $where";

        $params = ['uid' => $userId];

        if (!empty($search)) {
            $sql .= " AND (nombre ILIKE :search OR identificacion ILIKE :search OR cuenta ILIKE :search)";
            $params['search'] = "%{$search}%";
        }

        // Prioridad: primero los que NO han sido gestionados hoy, luego los más antiguos
        $sql .= " ORDER BY fecha_ultima_gestion ASC NULLS FIRST, id DESC LIMIT 200";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}