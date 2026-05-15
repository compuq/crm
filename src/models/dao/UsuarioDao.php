<?php
namespace LEX360\Models\Dao;

use LEX360\Core\BaseDao;

class UsuarioDao extends BaseDao
{
    protected string $table = 'usuarios';

    /**
     * 🔐 LOGIN: Buscar usuario por nombre de usuario
     * Necesario para AuthController
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE usuario = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * 🏢 JERARQUÍA: Buscar gestores bajo un supervisor específico
     * Necesario para que el Supervisor vea solo a su equipo
     */
    public function findGestoresBySupervisor(int $supervisorId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE supervisor_id = :sid AND rol = 'gestor' ORDER BY nombre");
        $stmt->execute(['sid' => $supervisorId]);
        return $stmt->fetchAll();
    }

    /**
     * 🏢 JERARQUÍA: Buscar supervisores activos (para asignación en dropdowns)
     * Necesario para crear nuevos usuarios
     */
    public function findSupervisores(): array
    {
        $stmt = $this->db->prepare("SELECT id, nombre FROM usuarios WHERE rol = 'supervisor' AND activo = true ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 🔍 FILTROS: Búsqueda avanzada con filtros de rol y texto
     * Necesario para el Admin y Supervisor General
     */
    public function findAllWithFilters(string $rol = '', string $search = ''): array
    {
        $sql = "SELECT * FROM usuarios WHERE 1=1";
        $params = [];
        
        if (!empty($rol)) {
            $sql .= " AND rol = :rol";
            $params['rol'] = $rol;
        }
        if (!empty($search)) {
            $sql .= " AND (nombre ILIKE :search OR usuario ILIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        $sql .= " ORDER BY rol, nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}