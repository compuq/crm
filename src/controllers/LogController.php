<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;

class LogController extends Controller
{
    public function index(): void
    {
        $this->session->requireAuth();
        if (!in_array($this->session->getUser()['role'], ['admin', 'supervisor_general'])) {
            header("Location: ?action=dashboard"); exit;
        }

        $filters = [
            'usuario' => $_GET['usuario'] ?? '',
            'accion'  => $_GET['accion'] ?? '',
            'tabla'   => $_GET['tabla'] ?? '',
            'fecha'   => $_GET['fecha'] ?? date('Y-m-d')
        ];

        $sql = "SELECT l.*, u.nombre as usuario_nombre, u.usuario as usuario_login 
                FROM logs_auditoria l 
                LEFT JOIN usuarios u ON l.usuario_id = u.id 
                WHERE 1=1";
        $params = [];

        if ($filters['usuario']) { 
            $sql .= " AND (u.nombre ILIKE :usuario OR u.usuario ILIKE :usuario)"; 
            $params['usuario'] = "%{$filters['usuario']}%"; 
        }
        if ($filters['accion']) { 
            $sql .= " AND l.accion ILIKE :accion"; 
            $params['accion'] = "%{$filters['accion']}%"; 
        }
        if ($filters['tabla']) { 
            $sql .= " AND l.tabla_afectada ILIKE :tabla"; 
            $params['tabla'] = "%{$filters['tabla']}%"; 
        }
        if ($filters['fecha']) { 
            $sql .= " AND l.fecha::date = :fecha"; 
            $params['fecha'] = $filters['fecha']; 
        }

        $sql .= " ORDER BY l.fecha DESC LIMIT 500";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $pageTitle = "Auditoría y Trazabilidad | LEX 360";
        ob_start();
        require_once __DIR__ . '/../views/logs/index.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }
}