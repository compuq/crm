<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use LEX360\Models\Services\BackupService;

class BackupController extends Controller
{
    public function index(): void
    {
        $this->session->requireAuth();
        if (!in_array($this->session->getUser()['role'], ['supervisor_general', 'admin'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        // Clientes candidatos a migrar (ej: saldo 0, estado 'pagado' o 'inactivo')
        $stmt = $this->db->prepare("SELECT id, nombre, identificacion, saldo, estado FROM clientes WHERE estado IN ('pagado', 'historico') ORDER BY fecha_ultima_gestion ASC LIMIT 500");
        $stmt->execute();
        $clientesElegibles = $stmt->fetchAll();

        $pageTitle = "Backup / Histórico | LEX 360";
        ob_start();
        require_once __DIR__ . '/../views/backup/index.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }

    // Endpoint AJAX para migrar
    public function migrar(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (empty($ids)) { echo json_encode(['success' => false, 'msg' => 'No hay clientes seleccionados']); exit; }

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
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $ident    = $_GET['identificacion'] ?? '';

        $sql = "SELECT cbk.nombre, cbk.identificacion, cbk.saldo, cbk.fecha_migracion, l.tipo_operacion, l.estado as estado_lote
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
}