<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use LEX360\Models\Services\PagoValidationService;

class PagoController extends Controller
{
    public function validar(): void
    {
        $this->session->requireAuth();
        
        // Solo admin y supervisor general
        if (!in_array($this->session->getUser()['role'], ['admin', 'supervisor_general'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        $pageTitle = "Validación de Pagos | LEX 360";
        $mensaje = '';
        $tipoMensaje = '';

        // Procesar CSV si se subió
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_banco'])) {
            $service = new PagoValidationService();
            $resultado = $service->procesarValidacion(
                $_FILES['csv_banco']['tmp_name'], 
                $this->session->getUser()['id']
            );

            if ($resultado['success']) {
                $mensaje = "✅ " . ($resultado['msg'] ?? "Validación exitosa: {$resultado['validados']} pagos procesados.");
                $tipoMensaje = 'success';
            } else {
                $mensaje = "❌ Error: " . ($resultado['error'] ?? 'Error desconocido');
                $tipoMensaje = 'danger';
            }
        }

        // Obtener pagos pendientes (sin monto_pendiente)
        try {
            $pendientes = $this->db->query("
                SELECT h.id, c.nombre, c.identificacion, c.saldo, u.nombre as gestor, h.fecha_gestion, h.comentario
                FROM historial h 
                JOIN clientes c ON h.id_cliente = c.id 
                JOIN usuarios u ON h.id_usuario = u.id 
                WHERE h.estatus = 'PAGG' 
                ORDER BY h.fecha_gestion DESC 
                LIMIT 20
            ")->fetchAll();
        } catch (\Exception $e) {
            $pendientes = [];
            $mensaje = "⚠️ No se pudieron cargar los pendientes: " . $e->getMessage();
            $tipoMensaje = 'warning';
        }

        // ✅ USAR LAYOUT MAESTRO CORRECTAMENTE
        ob_start();
        require_once __DIR__ . '/../views/pagos/validar.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }
}