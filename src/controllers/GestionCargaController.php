<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use LEX360\Models\Services\GestionMasivaService;

class GestionCargaController extends Controller
{
    /**
     * Muestra el formulario de carga de gestiones (CSV)
     */
    public function formulario(): void
    {
        $this->session->requireAuth();
        
        // Solo administradores y supervisores generales pueden cargar históricos masivos
        if (!in_array($this->session->getUser()['role'], ['admin', 'supervisor_general'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        $pageTitle = "Carga Masiva de Gestiones | LEX 360";
        
        // Buffer de salida para cargar la vista dentro del layout maestro
        ob_start();
        require_once __DIR__ . '/../views/carga/gestiones.php';
        $viewContent = ob_get_clean();
        
        // Renderizar layout completo (header + nav + footer)
        require_once __DIR__ . '/../views/frontend.php';
    }

    /**
     * Procesa el archivo CSV y guarda las gestiones
     */
    public function procesar(): void
    {
        $this->session->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_gestiones'])) {
            header("Location: ?action=carga_gestiones");
            exit;
        }

        $service = new GestionMasivaService();
        $resultado = $service->importarGestiones(
            $_FILES['csv_gestiones']['tmp_name'], 
            $this->session->getUser()['id']
        );

        // Redirección con mensaje flash (simple)
        $tipo = $resultado['success'] ? 'success' : 'danger';
        $msg  = $resultado['success'] 
            ? "✅ Importadas: {$resultado['insertados']} gestiones." 
            : "❌ Error: {$resultado['error']}";
        
        // Nota: Para una implementación robusta de mensajes flash, usa $_SESSION
        // Aquí usamos parámetros GET para simplificar la demo
        header("Location: ?action=carga_gestiones&msg=" . urlencode($msg) . "&type=" . $tipo);
        exit;
    }
}