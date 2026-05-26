<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use LEX360\Models\Services\BackupService;

class BackupController extends Controller
{
    public function index(): void
    {

        $action = $_GET['action'];
        $this->session->requireAuth();
        if (!in_array($this->session->getUser()['role'], ['supervisor_general', 'admin'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        // 📥 Obtener filtros desde GET
        $filters = [
            'cartera_id'   => $_GET['cartera_id'] ?? '',
            'usuario_id'   => $_GET['usuario_id'] ?? '',
            'supervisor_id'=> $_GET['supervisor_id'] ?? '',
            'search'       => $_GET['search'] ?? '',
        ];

        // 📋 Consulta base con filtros dinámicos
        $sql = "SELECT c.id, c.cuenta, c.nombre, c.identificacion, c.saldo, c.estado,
                       u.nombre as gestor_nombre, sup.nombre as supervisor_nombre, car.nombre_cartera
                FROM clientes c
                LEFT JOIN usuarios u ON u.id = c.id_gestor_asignado
                LEFT JOIN usuarios sup ON sup.id = c.id_supervisor_cadena
                LEFT JOIN carteras car ON car.id = c.id_cartera
                WHERE 1=1";
        $params = [];

        if (!empty($filters['cartera_id'])) {
            $sql .= " AND c.id_cartera = :cartera_id";
            $params['cartera_id'] = (int)$filters['cartera_id'];
        }
        if (!empty($filters['usuario_id'])) {
            $sql .= " AND c.id_gestor_asignado = :usuario_id";
            $params['usuario_id'] = (int)$filters['usuario_id'];
        }
        if (!empty($filters['supervisor_id'])) {
            $sql .= " AND c.id_supervisor_cadena = :supervisor_id";
            $params['supervisor_id'] = (int)$filters['supervisor_id'];
        }
        if (!empty($filters['search'])) {
            // 🔍 Búsqueda por search_vector (PostgreSQL)
            $sql .= " AND c.search_vector @@ to_tsquery('spanish', :search)";
            $params['search'] = implode(' & ', array_map('trim', explode(' ', $filters['search'])));
        }

        $sql .= " ORDER BY c.id DESC LIMIT 500";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $clientes = $stmt->fetchAll();

        // 📊 Datos para dropdowns
        $carteras = $this->db->query("SELECT id, nombre_cartera FROM carteras WHERE activa = true ORDER BY nombre_cartera")->fetchAll();
        $usuarios = $this->db->query("SELECT id, nombre FROM usuarios WHERE rol = 'gestor' AND activo = true ORDER BY nombre")->fetchAll();
        $supervisores = $this->db->query("SELECT id, nombre FROM usuarios WHERE rol IN ('supervisor', 'supervisor_general') AND activo = true ORDER BY nombre")->fetchAll();

        $pageTitle = "Migración de Clientes | LEX 360";
        $viewData = compact('clientes', 'filters', 'carteras', 'usuarios', 'supervisores', 'pageTitle');
        extract($viewData);
        
        ob_start();
        if($action === 'backup'){
            require_once __DIR__ . '/../views/backup/index.php';
        } elseif($action === 'migrar_clientes'){
            require_once __DIR__ . '/../views/backup/migrar.php';
        }
        
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }

    // 🔄 AJAX: Trasladar clientes a otro usuario/supervisor
    public function trasladarClientes(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $nuevoGestorId = (int)($_POST['nuevo_gestor_id'] ?? 0);
        $nuevoSupervisorId = (int)($_POST['nuevo_supervisor_id'] ?? 0);
        
        if (empty($ids) || !$nuevoGestorId || !$nuevoSupervisorId) {
            echo json_encode(['success' => false, 'msg' => 'Datos incompletos']);
            exit;
        }

        try {
            $this->db->beginTransaction();
            $idsStr = implode(',', array_map('intval', $ids));
            
            $sql = "UPDATE clientes SET id_gestor_asignado = :gid, id_supervisor_cadena = :sid 
                    WHERE id IN ($idsStr)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['gid' => $nuevoGestorId, 'sid' => $nuevoSupervisorId]);
            
            $this->db->commit();
            echo json_encode(['success' => true, 'migrados' => count($ids)]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    // 📦 AJAX: Enviar clientes a backup (ya existente en BackupService)
    public function migrarHistorico(): void
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
public function consultarHistorico(): void
{
    $this->session->requireAuth();
    header('Content-Type: application/json');

    $fechaIni = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
    // ✅ CORREGIDO: Paréntesis para precedencia correcta
    $fechaFin = ($_GET['fecha_fin'] ?? date('Y-m-d')) . " 23:59:59";
    $ident    = $_GET['identificacion'] ?? '';

    $sql = "SELECT cbk.id_original, cbk.nombre, cbk.cuenta, cbk.identificacion, 
                   cbk.saldo, cbk.fecha_migracion, l.tipo_operacion, l.estado as estado_lote
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
/*     public function consultarHistorico(): void
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
 */    // ... código existente ...

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


}