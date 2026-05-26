<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use LEX360\Models\Services\BackupService;

class HistorialController extends Controller
{

    public function index(): void
    {
        $this->session->requireAuth();
        if (!in_array($this->session->getUser()['role'], ['supervisor_general', 'admin'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        

        // 📅 Capturar filtros de fecha
        $busqueda = $_GET['busqueda'] ?? '';
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin    = $_GET['fecha_fin']  ?? '';

        
        $estado    = $_GET['estado']  ??"('pagado', 'historico','activo')";
        if(in_array($estado,['pagado','historico'])){
            $estado = "('$estado')";
        } else{
            $estado    = "('pagado', 'historico','activo')";
        }

        // 📜 Construir consulta dinámica
        $sql = "SELECT id, cuenta, nombre, identificacion, saldo, estado, fecha_ultima_gestion 
                FROM clientes 
                WHERE estado IN $estado";
        $params = [];

        if (!empty($fecha_inicio)) {
            $sql .= " AND fecha_ultima_gestion >= :fecha_inicio";
            $params['fecha_inicio'] = $fecha_inicio . ' 00:00:00';
        }
        if (!empty($fecha_fin)) {
            $sql .= " AND fecha_ultima_gestion <= :fecha_fin";
            $params['fecha_fin'] = $fecha_fin . ' 23:59:59';
        }

        if (!empty($busqueda)) {
            $sql .= " AND search_vector @@ plainto_tsquery('spanish', '$busqueda')";
        }


        $sql .= " ORDER BY fecha_ultima_gestion ASC LIMIT 500";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $clientesElegibles = $stmt->fetchAll();

        $pageTitle = "Backup / Histórico | LEX 360";

        // ✅ Pasar variables a la vista
        //$fecha_inicio = $fecha_inicio;
        //$fecha_fin    = $fecha_fin;

        ob_start();
        require_once __DIR__ . '/../views/backup/index.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }

    // Endpoint AJAX para migrar
    public function migrar(): void  // ← Este método debe existir
    {
        
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        
        if (empty($ids)) { 
            echo json_encode(['success' => false, 'msg' => 'No hay clientes seleccionados']); 
            exit; 
        }
        
        $service = new BackupService();
        $res = $service->migrarAHistorico($ids, $this->session->getUser()['id']);
        echo json_encode($res);
    }
    // Endpoint AJAX para consultar histórico
    public function consultarHistorico(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');

        $fechaIni = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaFin = $_GET['fecha_fin']." 23:59:59" ?? date('Y-m-d')." 23:59:59";
        $ident    = $_GET['identificacion'] ?? '';

        $sql = "SELECT cbk.id_original, cbk.nombre, cbk.cuenta, cbk.identificacion, cbk.saldo, cbk.fecha_migracion, l.tipo_operacion, l.estado as estado_lote
                FROM clientes_bk cbk
                JOIN lotes l ON cbk.lote_id = l.id
                WHERE cbk.fecha_migracion BETWEEN :start AND :end";
        
        $params = ['start' => $fechaIni, 'end' => $fechaFin];
        if (!empty($ident)) {
            $sql .= " AND cbk.identificacion ILIKE :ident";
            $params['ident'] = "%{$ident}%";
        }

        $sql .= " ORDER BY cbk.fecha_migracion DESC LIMIT 100";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode($stmt->fetchAll());
    }
    // ... código existente ...

    // Endpoint AJAX para restaurar clientes
    public function restaurar(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (empty($ids)) { echo json_encode(['success' => false, 'msg' => 'No hay clientes seleccionados']); exit; }

        $service = new BackupService();
        $res = $service->restaurar($ids);
        echo json_encode($res);
    }

    // Endpoint AJAX para ver historial de un cliente específico (para el Modal)
    public function verHistorialCliente(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        $idCliente = (int)($_GET['id'] ?? 0);
        if (!$idCliente) { echo json_encode([]); exit; }

        // Buscamos en historial_bk
        $sql = "SELECT hbk.*, t.nombre as tipologia, u.nombre as gestor 
                FROM historial_bk hbk
                LEFT JOIN tipologias t ON t.id = hbk.id_tipologia
                LEFT JOIN usuarios u ON u.id = hbk.id_usuario
                WHERE hbk.id_cliente = :id
                ORDER BY hbk.fecha_gestion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idCliente]);
        echo json_encode($stmt->fetchAll());
    }

    // Endpoint para Exportar Histórico a Excel
    public function exportarHistorico(): void
    {
        $this->session->requireAuth();
        while (ob_get_level()) ob_end_clean(); // Limpiar buffers

        // Reutilizamos la lógica de consulta
        $fechaIni = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $ident    = $_GET['identificacion'] ?? '';

        $sql = "SELECT cbk.nombre, cbk.identificacion, cbk.saldo, cbk.estado, cbk.fecha_migracion, l.tipo_operacion 
                FROM clientes_bk cbk
                JOIN lotes l ON cbk.lote_id = l.id
                WHERE cbk.fecha_migracion BETWEEN :start AND :end";
        $params = ['start' => $fechaIni, 'end' => $fechaFin];
        if (!empty($ident)) {
            $sql .= " AND cbk.identificacion ILIKE :ident";
            $params['ident'] = "%{$ident}%";
        }
        $sql .= " ORDER BY cbk.fecha_migracion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll();

        $excel = new \LEX360\Models\Services\ExcelService();
        $excel->exportarXlsx($datos, 'Historico_Clientes_' . date('Y-m-d'));
    }    
}