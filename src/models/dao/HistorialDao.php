<?php
namespace LEX360\Models\Dao;
use LEX360\Core\BaseDao;

class HistorialDao extends BaseDao
{
    protected string $table = 'historial';

    public function findByCliente(int $clienteId): array
    {
        $sql = "SELECT h.*, t.nombre as tipologia_nombre, u.nombre as gestor_nombre 
                FROM historial h 
                LEFT JOIN tipologias t ON h.id_tipologia = t.id 
                LEFT JOIN usuarios u ON h.id_usuario = u.id 
                WHERE h.id_cliente = :cid 
                ORDER BY h.fecha_gestion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function getResumenPorRango(int $userId, string $rol, string $inicio, string $fin): array
    {
        $where = ($rol === 'gestor') ? "WHERE h.id_usuario = :uid" : "WHERE c.id_supervisor_cadena = :uid";
        $sql = "SELECT COUNT(*) as total, 
                       SUM(CASE WHEN h.estatus = 'COMP' THEN 1 ELSE 0 END) as compromisos,
                       SUM(CASE WHEN h.estatus = 'PAGG' THEN 1 ELSE 0 END) as pagos_reportados
                FROM historial h
                JOIN clientes c ON h.id_cliente = c.id
                $where AND h.fecha_gestion BETWEEN :start AND :end";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'start' => $inicio, 'end' => $fin]);
        return $stmt->fetch();
    }
}
