<?php
namespace LEX360\Models\Dao;

use LEX360\Core\BaseDao;

class CarteraDao extends BaseDao
{
    protected string $table = 'carteras';

    /**
     * Obtener todas las carteras activas (para selects de carga)
     */
    public function findAllActive(): array
    {
        $stmt = $this->db->prepare("SELECT id, nombre_cartera, cuenta_nombre, identificacion_nombre FROM {$this->table} WHERE activa = true ORDER BY nombre_cartera");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener configuración completa de una cartera (labels + extras)
     */
    public function findByIdWithConfig(int $id): ?array
    {
        // 1. Datos base de la cartera
        $cartera = $this->findById($id);
        if (!$cartera) return null;

        // 2. Extras asociados
        $stmt = $this->db->prepare("SELECT id, nombre_campo, etiqueta_display, tipo, orden_visual FROM extras WHERE id_cartera = :id ORDER BY orden_visual");
        $stmt->execute(['id' => $id]);
        $cartera['extras'] = $stmt->fetchAll();

        return $cartera;
    }
}