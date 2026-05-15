<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;

class PromesaController extends Controller
{
    public function index(): void
    {
        $this->session->requireAuth();
        $user = $this->session->getUser();
        
        // Obtener promesas del usuario logueado
        $promesas = $this->promesaDao->findByUsuario($user['id']);
        
        $pageTitle = "Mis Promesas de Pago | LEX 360";
        ob_start();
        require_once __DIR__ . '/../views/promesas/index.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }
}