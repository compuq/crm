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
}