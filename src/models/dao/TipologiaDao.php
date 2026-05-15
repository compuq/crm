<?php
namespace LEX360\Models\Dao;

use LEX360\Core\BaseDao;

class TipologiaDao extends BaseDao
{
    protected string $table = 'tipologias';

    public function findAllForSelect(int $carteraId = null): array
    {
        if ($carteraId) {
            $sql = "SELECT id, padre_id, nombre FROM tipologias WHERE id_cartera = :cid ORDER BY COALESCE(padre_id, id), id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['cid' => $carteraId]);
        } else {
            $sql = "SELECT id, padre_id, nombre FROM tipologias ORDER BY COALESCE(padre_id, id), id";
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll();
    }

    public function findByCartera(int $carteraId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tipologias WHERE id_cartera = :cid ORDER BY clase DESC, codigo_origen");
        $stmt->execute(['cid' => $carteraId]);
        return $stmt->fetchAll();
    }

    public function deleteByCartera(int $carteraId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tipologias WHERE id_cartera = :cid");
        return $stmt->execute(['cid' => $carteraId]);
    }
}