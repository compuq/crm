<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use LEX360\Models\Services\ReporteService; // ✅ Importar el servicio

class ReporteController extends Controller
{
    // Vista del formulario de reportes
    public function index(): void
    {
        $this->session->requireAuth();
        $pageTitle = "Reportes | LEX 360";
        ob_start();
        require_once __DIR__ . '/../views/reportes/index.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }

    // Generación y descarga del Excel
/*     public function generar(): void
    {
        $this->session->requireAuth();
        $user = $this->session->getUser();
        
        $tipo = $_GET['tipo'] ?? 'gestiones';
        $inicio = $_GET['inicio'] ?? date('Y-m-01');
        $fin = $_GET['fin'] ?? date('Y-m-d');

        // Consulta base
        $sql = "SELECT 
                    h.fecha_gestion, 
                    c.nombre as cliente, 
                    c.identificacion, 
                    c.cuenta,
                    h.estatus, 
                    t.nombre as tipologia, 
                    h.comentario, 
                    u.nombre as gestor
                FROM historial h 
                JOIN clientes c ON h.id_cliente = c.id 
                LEFT JOIN tipologias t ON h.id_tipologia = t.id 
                LEFT JOIN usuarios u ON h.id_usuario = u.id
                WHERE h.fecha_gestion BETWEEN :start AND :end";
        
        if ($user['role'] === 'gestor') $sql .= " AND h.id_usuario = :uid";
        elseif ($user['role'] === 'supervisor') $sql .= " AND c.id_supervisor_cadena = :uid";
        
        $sql .= " ORDER BY h.fecha_gestion DESC";
        $stmt = $this->db->prepare($sql);
        $params = ['start' => $inicio, 'end' => $fin];
        if ($user['role'] !== 'supervisor_general' && $user['role'] !== 'admin') {
            $params['uid'] = $user['id'];
        }
        $stmt->execute($params);
        $datos = $stmt->fetchAll();

        // ✅ Exportar con ExcelService
        $excel = new \LEX360\Models\Services\ExcelService();
        
        $formatos = [
            'fecha_gestion' => ['formato' => 'date', 'ancho' => 15],
            'saldo' => ['formato' => 'currency', 'ancho' => 12],
        ];
        
        $excel->exportarXlsx($datos, 'LEX360_' . ucfirst($tipo), $formatos);
    } */
    public function generar(): void
    {
        $this->session->requireAuth();
        $user = $this->session->getUser();
        $inicio = $_GET['inicio'] ?? date('Y-m-01');
        $fin = $_GET['fin'] ?? date('Y-m-d');
        // ... (Tu lógica de filtros SQL igual que antes) ...
        // Consulta base
        $sql = "SELECT 
                    h.fecha_gestion, 
                    c.nombre as cliente, 
                    c.identificacion, 
                    c.cuenta,
                    h.estatus, 
                    t.nombre as tipologia, 
                    h.comentario, 
                    u.nombre as gestor
                FROM historial h 
                JOIN clientes c ON h.id_cliente = c.id 
                LEFT JOIN tipologias t ON h.id_tipologia = t.id 
                LEFT JOIN usuarios u ON h.id_usuario = u.id
                WHERE h.fecha_gestion BETWEEN :start AND :end";
        
        if ($user['role'] === 'gestor') $sql .= " AND h.id_usuario = :uid";
        elseif ($user['role'] === 'supervisor') $sql .= " AND c.id_supervisor_cadena = :uid";
        
        $sql .= " ORDER BY h.fecha_gestion DESC";
        $stmt = $this->db->prepare($sql);
        $params = ['start' => $inicio, 'end' => $fin];
        if ($user['role'] !== 'supervisor_general' && $user['role'] !== 'admin') {
            $params['uid'] = $user['id'];
        }
        $stmt->execute($params);
        
        // Obtener datos
        $datos = $stmt->fetchAll();

        // Usar ExcelService
        $excel = new \LEX360\Models\Services\ExcelService();
        
        $formatos = [
            'fecha_gestion' => ['formato' => 'date', 'ancho' => 15],
            'saldo' => ['formato' => 'currency', 'ancho' => 12]
        ];

        $excel->exportarXlsx($datos, 'Reporte_Lex360', $formatos);
    }

        public function generarGestiones(): void
    {
        while (ob_get_level()) ob_end_clean();
        $this->session->requireAuth();
        
        // Rango amplio por defecto
        $inicio = $_GET['inicio'] ?? '2026-01-01';
        $fin = $_GET['fin'] ?? '2026-12-31';

        try {
            // ✅ Consulta SIN filtros de rol/cartera (solo fechas)
            $sql = "SELECT 
                        h.fecha_gestion, 
                        c.cuenta, 
                        c.nombre, 
                        c.identificacion,
                        COALESCE(t.nombre, 'Sin Tipología') as tipologia, 
                        h.estatus, 
                        h.comentario, 
                        u.nombre as gestor
                    FROM historial h 
                    JOIN clientes c ON h.id_cliente = c.id 
                    LEFT JOIN tipologias t ON h.id_tipologia = t.id 
                    LEFT JOIN usuarios u ON h.id_usuario = u.id
                    WHERE h.fecha_gestion BETWEEN :inicio AND :fin
                    ORDER BY h.fecha_gestion DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'inicio' => $inicio . ' 00:00:00', 
                'fin' => $fin . ' 23:59:59'
            ]);
            $datos = $stmt->fetchAll();
            // En ReporteController::generarGestiones(), antes de llamar a exportarXlsx():
            foreach ($datos as &$fila) {
                if (!empty($fila['data_extras']) && is_string($fila['data_extras'])) {
                    $extrasArr = json_decode($fila['data_extras'], true);
                    if (is_array($extrasArr)) {
                        foreach ($extrasArr as $k => $v) {
                            $fila['extra_' . $k] = $v; // Crea columnas dinámicas: extra_tasa_interes, etc.
                        }
                    }
                    unset($fila['data_extras']); // Opcional: quitar columna JSON cruda
                }
            }
            $excel = new \LEX360\Models\Services\ExcelService();
            $excel->exportarXlsx($datos, 'Reporte_Gestiones', [
                'fecha_gestion' => ['formato' => 'date', 'ancho' => 18]
            ]);
        } catch (\Exception $e) {
            header('Content-Type: text/plain');
            echo "Error: " . $e->getMessage();
            exit;
        }
    }
    public function generarReporte(): void
{
    $this->session->requireAuth();
    $user = $this->session->getUser();
    $rol = $user['rol'] ?? $user['role'] ?? '';

    // ✅ Filtros recibidos (sanitizados)
    $filters = [
        'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
        'fecha_fin'    => $_GET['fecha_fin'] ?? null,
        'usuario_id'   => in_array($rol, ['admin','supervisor_general']) ? ($_GET['usuario_id'] ?? null) : null,
        'supervisor_id'=> $rol === 'admin' ? ($_GET['supervisor_id'] ?? null) : null,
        'cartera_id'   => ($rol === 'supervisor' || $rol === 'admin') ? ($_GET['cartera_id'] ?? null) : null,
    ];

    // ✅ Base de la consulta
    $sql = "SELECT g.*, c.nombre as cliente_nombre, u.nombre as gestor_nombre, s.nombre as supervisor_nombre
            FROM gestiones g
            JOIN clientes c ON c.id = g.id_cliente
            JOIN usuarios u ON u.id = g.id_usuario
            LEFT JOIN usuarios s ON s.id = c.id_supervisor_cadena
            WHERE 1=1";
    $params = [];

    // ✅ Filtros dinámicos seguros
    if ($filters['fecha_inicio']) {
        $sql .= " AND g.fecha_gestion >= :fecha_inicio";
        $params['fecha_inicio'] = $filters['fecha_inicio'] . ' 00:00:00';
    }
    if ($filters['fecha_fin']) {
        $sql .= " AND g.fecha_gestion <= :fecha_fin";
        $params['fecha_fin'] = $filters['fecha_fin'] . ' 23:59:59';
    }
    if ($filters['usuario_id']) {
        $sql .= " AND g.id_usuario = :usuario_id";
        $params['usuario_id'] = (int)$filters['usuario_id'];
    }
    if ($filters['supervisor_id']) {
        $sql .= " AND c.id_supervisor_cadena = :supervisor_id";
        $params['supervisor_id'] = (int)$filters['supervisor_id'];
    }
    if ($filters['cartera_id']) {
        $sql .= " AND c.id_cartera = :cartera_id";
        $params['cartera_id'] = (int)$filters['cartera_id'];
    }

    // ✅ Restricción por rol (seguridad backend)
    if ($rol === 'supervisor') {
        $sql .= " AND c.id_supervisor_cadena = :mi_supervisor";
        $params['mi_supervisor'] = $user['id'];
    } elseif ($rol === 'gestor') {
        $sql .= " AND g.id_usuario = :mi_usuario";
        $params['mi_usuario'] = $user['id'];
    }

    $sql .= " ORDER BY g.fecha_gestion DESC LIMIT 500";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll();

    // ✅ Listas para dropdowns
    $usuarios = $this->db->query("SELECT id, nombre FROM usuarios WHERE rol = 'gestor' AND activo = true ORDER BY nombre")->fetchAll();
    $supervisores = $this->db->query("SELECT id, nombre FROM usuarios WHERE rol IN ('supervisor','supervisor_general') AND activo = true ORDER BY nombre")->fetchAll();
    $carteras = $this->db->query("SELECT id, nombre FROM carteras WHERE activo = true ORDER BY nombre")->fetchAll();

    // Renderizar vista
    ob_start();
    require_once __DIR__ . '/../views/reportes/gestiones.php';
    $viewContent = ob_get_clean();
    require_once __DIR__ . '/../views/frontend.php';
}
}