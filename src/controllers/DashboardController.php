<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->session->requireAuth();
        $user = $this->session->getUser();
        $stats = $this->clienteDao->getEstadisticas($user['id'], $user['role']);

        $pageTitle = "Dashboard | LEX 360";
        $viewContent = '';
        
        // Pasar variables a la vista
        $statsHoy   = $stats['llamadas_hoy'];
        $statsTotal = $stats['total_asignados'];
        $statsProm  = $stats['promesas_hoy'];
        $nombreUser = $user['name'];

        ob_start();
        require_once __DIR__ . '/../views/dashboard/index.php';
        $viewContent = ob_get_clean();
        
        // ✅ ESTO ES CRÍTICO: Incluir frontend.php
        require_once __DIR__ . '/../views/frontend.php';
    }
}