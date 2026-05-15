<?php
namespace LEX360\Models\Dao;

use LEX360\Core\BaseDao;

class AsistenciaDao extends BaseDao
{
    protected string $table = 'logs_asistencia';

    /**
     * Consulta el historial de asistencia de un usuario específico
     * (Para el perfil del gestor o revisión individual)
     */
    public function findByUser(int $userId, string $fechaInicio, string $fechaFin): array
    {
        $sql = "SELECT fecha, entrada, salida, horas_trabajadas 
                FROM {$this->table} 
                WHERE usuario_id = :uid 
                AND fecha BETWEEN :start AND :end 
                ORDER BY fecha DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uid' => $userId,
            'start' => $fechaInicio,
            'end' => $fechaFin
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Consulta resumen diario para Supervisores
     * Muestra quién entró/salió hoy (o fecha específica)
     */
    public function getDailySummary(int $supervisorId, string $fecha): array
    {
        // Trae los registros de los gestores asignados a este supervisor
        $sql = "SELECT u.nombre, u.usuario, a.entrada, a.salida, a.horas_trabajadas 
                FROM {$this->table} a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE u.supervisor_id = :supId 
                AND a.fecha = :fecha
                ORDER BY a.entrada ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'supId' => $supervisorId,
            'fecha' => $fecha
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Total de horas trabajadas en un rango (Para nómina o reportes)
     */
    public function getTotalHours(int $userId, string $fechaInicio, string $fechaFin): float
    {
        $sql = "SELECT COALESCE(SUM(horas_trabajadas), 0) 
                FROM {$this->table} 
                WHERE usuario_id = :uid 
                AND fecha BETWEEN :start AND :end";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uid' => $userId,
            'start' => $fechaInicio,
            'end' => $fechaFin
        ]);
        
        return (float) $stmt->fetchColumn();
    }
}