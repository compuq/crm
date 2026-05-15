<?php
namespace LEX360\Controllers;
use LEX360\Core\Controller;

class AsistenciaController extends Controller
{
    public function index(): void
    {
        $this->session->requireAuth();
        $user = $this->session->getUser();
        $hoy = date('Y-m-d');
        
        // Verificar si ya marcó entrada hoy
        $stmt = $this->db->prepare("SELECT id, entrada, salida, horas_trabajadas FROM logs_asistencia WHERE usuario_id = :uid AND fecha = :fecha");
        $stmt->execute(['uid' => $user['id'], 'fecha' => $hoy]);
        $registroHoy = $stmt->fetch();

        $pageTitle = "Asistencia | LEX 360";
        ob_start();
        require_once __DIR__ . '/../views/asistencia/index.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }

    public function registrar(): void
    {
        $this->session->requireAuth();
        $user = $this->session->getUser();
        $hoy = date('Y-m-d');
        $ahora = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("SELECT id, entrada FROM logs_asistencia WHERE usuario_id = :uid AND fecha = :fecha");
        $stmt->execute(['uid' => $user['id'], 'fecha' => $hoy]);
        $reg = $stmt->fetch();

        if (!$reg) {
            // Marcar entrada
            $this->db->prepare("INSERT INTO logs_asistencia (usuario_id, entrada, fecha) VALUES (:uid, :ent, :fec)")
                     ->execute(['uid' => $user['id'], 'ent' => $ahora, 'fec' => $hoy]);
            $msg = "✅ Entrada registrada: $ahora";
        } elseif (!$reg['salida']) {
            // Marcar salida
            $this->db->prepare("UPDATE logs_asistencia SET salida = :sal WHERE id = :id")
                     ->execute(['sal' => $ahora, 'id' => $reg['id']]);
            $msg = "👋 Salida registrada: $ahora";
        } else {
            $msg = "⚠️ Ya registraste entrada y salida hoy.";
        }

        echo json_encode(['success' => true, 'message' => $msg]);
    }
}