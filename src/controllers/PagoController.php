<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;

class PagoController extends Controller
{
    public function validar(): void
    {
        $this->session->requireAuth();
        
        // Solo admin y supervisor_general pueden validar
        $user = $this->session->getUser();
        if (!in_array($user['role'], ['admin', 'supervisor_general'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        // ✅ Filtros desde GET
        $filtroGestor = $_GET['gestor_id'] ?? '';
        $filtroSupervisor = $_GET['supervisor_id'] ?? '';
        $filtroFechaInicio = $_GET['fecha_inicio'] ?? '';
        $filtroFechaFin = $_GET['fecha_fin'] ?? '';

        // ✅ Obtener lista de gestores y supervisores para los filtros
        $gestores = $this->db->prepare("SELECT id, nombre FROM usuarios WHERE rol = 'gestor' AND activo = true ORDER BY nombre");
        $gestores->execute();
        $listaGestores = $gestores->fetchAll();

        $supervisores = $this->db->prepare("SELECT id, nombre FROM usuarios WHERE rol IN ('supervisor','supervisor_general') AND activo = true ORDER BY nombre");
        $supervisores->execute();
        $listaSupervisores = $supervisores->fetchAll();

        // ✅ Consulta de pagos pendientes con filtros dinámicos
        $sql = "
            SELECT 
                h.id as historial_id,
                p.id as pago_id,
                c.nombre, 
                c.identificacion, 
                c.saldo, 
                u.nombre as gestor,
                u.id as gestor_id,
                sup.nombre as supervisor,
                h.fecha_gestion, 
                h.comentario,
                p.monto
            FROM pagos p
            JOIN historial h ON h.id = p.id_historial
            JOIN clientes c ON h.id_cliente = c.id 
            JOIN usuarios u ON h.id_usuario = u.id 
            LEFT JOIN usuarios sup ON c.id_supervisor_cadena = sup.id
            WHERE p.estatus = 'PAGG'
        ";
        $params = [];

        if (!empty($filtroGestor)) {
            $sql .= " AND u.id = :gestor_id";
            $params['gestor_id'] = (int)$filtroGestor;
        }
        if (!empty($filtroSupervisor)) {
            $sql .= " AND c.id_supervisor_cadena = :supervisor_id";
            $params['supervisor_id'] = (int)$filtroSupervisor;
        }
        if (!empty($filtroFechaInicio)) {
            $sql .= " AND h.fecha_gestion >= :fecha_inicio";
            $params['fecha_inicio'] = $filtroFechaInicio . ' 00:00:00';
        }
        if (!empty($filtroFechaFin)) {
            $sql .= " AND h.fecha_gestion <= :fecha_fin";
            $params['fecha_fin'] = $filtroFechaFin . ' 23:59:59';
        }

        $sql .= " ORDER BY h.fecha_gestion DESC LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $pendientes = $stmt->fetchAll();

        $pageTitle = "Validación de Pagos | LEX 360";
        $mensaje = $_SESSION['flash_message'] ?? '';
        $tipoMensaje = $_SESSION['flash_type'] ?? '';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        // ✅ Renderizar con layout maestro
        ob_start();
        require_once __DIR__ . '/../views/pagos/validar.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }

    /**
     * Valida un pago individual vía AJAX (modal)
     */
    public function validarPago(): void
    {
        header('Content-Type: application/json');
        $this->session->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $user = $this->session->getUser();
        $pagoId = (int)($_POST['pago_id'] ?? 0);
        $referencia = trim($_POST['referencia_bancaria'] ?? '');

        if (!$pagoId || empty($referencia)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            // 1. Obtener el pago y su historial asociado
            $stmt = $this->db->prepare("
                SELECT p.id, p.id_historial, p.id_cliente, p.monto, h.estatus as hist_estatus
                FROM pagos p
                JOIN historial h ON h.id = p.id_historial
                WHERE p.id = :pago_id AND p.estatus = 'PAGG'
                FOR UPDATE
            ");
            $stmt->execute(['pago_id' => $pagoId]);
            $pago = $stmt->fetch();

            if (!$pago) {
                throw new \Exception('Pago no encontrado o ya validado');
            }

            // 2. Actualizar tabla pagos
            $this->db->prepare("
                UPDATE pagos 
                SET estatus = 'PAGO', 
                    referencia_bancaria = :referencia,
                    validado_por = :validador,
                    fecha_validacion = NOW()
                WHERE id = :id
            ")->execute([
                'id' => $pagoId,
                'referencia' => substr($referencia, 0, 100),
                'validador' => $user['id']
            ]);

            // 3. Actualizar historial a estatus PAGO
            $this->db->prepare("
                UPDATE historial 
                SET estatus = 'PAGO' 
                WHERE id = :historial_id AND estatus = 'PAGG'
            ")->execute(['historial_id' => $pago['id_historial']]);

            // 4. Descontar saldo del cliente
            $this->db->prepare("
                UPDATE clientes 
                SET saldo = GREATEST(0, saldo - :monto),
                    estado = CASE WHEN GREATEST(0, saldo - :monto2) <= 0 THEN 'pagado' ELSE estado END
                WHERE id = :cliente_id
            ")->execute([
                'cliente_id' => $pago['id_cliente'],
                'monto' => $pago['monto'],
                'monto2' => $pago['monto']
            ]);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => '✅ Pago validado correctamente']);

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LEX360] validarPago: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            file_put_contents('errores.txt','Error en Validar Pago'.$e->getMessage());

        }
        exit;
    }
}