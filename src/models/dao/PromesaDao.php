<?php
namespace LEX360\Models\Dao;

use LEX360\Core\BaseDao;

class PromesaDao extends BaseDao
{
    protected string $table = 'promesas';

    public function findByCliente(int $clienteId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM promesas WHERE id_cliente = :cid ORDER BY fecha_compromiso DESC");
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    /**
     * ✅ NUEVO: Buscar promesas por usuario (Gestor)
     */
    public function findByUsuario(int $userId): array
    {
        $sql = "SELECT p.id, p.monto_prometido, p.fecha_compromiso, p.estatus, 
                       c.nombre as cliente_nombre, c.identificacion
                FROM promesas p
                JOIN clientes c ON p.id_cliente = c.id
                WHERE p.id_usuario = :uid
                ORDER BY p.fecha_compromiso ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function markCumplida(int $promesaId): bool
    {
        return $this->db->prepare("UPDATE promesas SET estatus = 'cumplida' WHERE id = :id AND estatus = 'pendiente'")
                        ->execute(['id' => $promesaId]);
    }
}