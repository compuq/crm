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
        $pago_pendiente =$this->clienteDao->findPays($user['id'], $user['role'], '','PAGG');
        $pago_confirmado=$this->clienteDao->findPays($user['id'], $user['role'], '','PAGO');
        $suma_saldo = $this->clienteDao->sumClientes($user['id'], $user['role'], '','saldo');
        $suma_inicial = $this->clienteDao->sumClientes($user['id'], $user['role'], '','saldo_inicial');
        $SaldoCarteras = $this->clienteDao->getSaldoCarteras();
        ob_start();
        require_once __DIR__ . '/../views/dashboard/index.php';
        //echo "Pago confirmado: Q".number_format($pago_confirmado,2);
        //echo "<br>Pago pendiente Confirmar: Q".number_format($pago_pendiente,2);
        //echo "<br>Saldo actual de cartera: Q".number_format($suma_saldo,2);
        //echo "<br>Cartera asignada: Q".number_format($suma_inicial,2);

        $viewContent = ob_get_clean();
        
        // ✅ ESTO ES CRÍTICO: Incluir frontend.php
        require_once __DIR__ . '/../views/frontend.php';
    }
    public function stats():void{
        // 1. Obtienes los datos de tu DAO
        $pago_pendiente  = $this->clienteDao->findPays($_SESSION['user_id'], $_SESSION['role'], '', 'PAGG');
        $pago_confirmado = $this->clienteDao->findPays($_SESSION['user_id'], $_SESSION['role'], '', 'PAGO');
        $suma_saldo      = $this->clienteDao->sumClientes($_SESSION['user_id'], $_SESSION['role'], '', 'saldo');
        $suma_inicial    = $this->clienteDao->sumClientes($_SESSION['user_id'], $_SESSION['role'], '', 'saldo_inicial');

        // 2. Función corta para convertir a número de forma segura (evita nulls, strings vacíos o warnings)
        $toNum = fn($v) => (float) ($v ?? 0);

        // 3. Arma la respuesta con los nombres EXACTOS que espera el JavaScript
        $respuesta = [
            'pago_confirmado' => $toNum($pago_confirmado),
            'pago_pendiente'  => $toNum($pago_pendiente),
            'suma_saldo'      => $toNum($suma_saldo),
            'suma_inicial'    => $toNum($suma_inicial)
        ];

        // 4. Imprime JSON limpio y DETIENE la ejecución
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuesta, JSON_NUMERIC_CHECK);
        exit; // 🔴 CRUCIAL: evita que se imprima HTML, views o logs después del JSON
    }
}