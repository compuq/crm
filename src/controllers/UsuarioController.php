<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;

class UsuarioController extends Controller
{
    public function index(): void
    {
        $this->session->requireAuth();
        $user = $this->session->getUser();
        $role = $user['role'];

        if (!in_array($role, ['admin', 'supervisor_general', 'supervisor'])) {
            header("Location: ?action=dashboard"); exit;
        }

        $usuarios = [];
        if ($role === 'admin' || $role === 'supervisor_general') {
            $usuarios = $this->usuarioDao->findAllWithFilters($_GET['rol'] ?? '', $_GET['q'] ?? '');
        } else {
            // Supervisor solo ve a sus gestores
            $usuarios = $this->usuarioDao->findGestoresBySupervisor($user['id']);
        }

        $pageTitle = "Gestión de Usuarios | LEX 360";
        ob_start();
        require_once __DIR__ . '/../views/usuarios/index.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }

    public function guardar(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success'=>false, 'msg'=>'Método no permitido']); return;
        }

        $adminUser = $this->session->getUser();
        $nombre    = trim($_POST['nombre'] ?? '');
        $usuario   = trim($_POST['usuario'] ?? '');
        $password  = $_POST['password'] ?? '';
        $rol       = $_POST['rol'] ?? 'gestor';
        $sup_id    = !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null;

        if (empty($nombre) || empty($usuario)) {
            echo json_encode(['success'=>false, 'msg'=>'Nombre y usuario son obligatorios']); return;
        }

        // 🔐 Validaciones de jerarquía estrictas
        if ($rol === 'gestor' && $adminUser['role'] === 'supervisor') {
            $sup_id = $adminUser['id']; // Forzar asignación al supervisor actual
        } elseif ($rol !== 'gestor' && $adminUser['role'] !== 'admin') {
            echo json_encode(['success'=>false, 'msg'=>'No tienes permisos para crear/editar este rol']); return;
        }

        $hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

        try {
            $id = $_POST['id'] ?? null;
            if ($id) {
                // Actualización
                $data = ['nombre' => $nombre, 'usuario' => $usuario, 'rol' => $rol, 'supervisor_id' => $sup_id];
                if ($hash) $data['clave_hash'] = $hash;
                $this->usuarioDao->update((int)$id, $data);
                echo json_encode(['success'=>true, 'msg'=>'Usuario actualizado correctamente']);
            } else {
                // Creación
                if (empty($password)) { echo json_encode(['success'=>false, 'msg'=>'La contraseña es obligatoria para nuevos usuarios']); return; }
                $data = ['nombre' => $nombre, 'usuario' => $usuario, 'clave_hash' => $hash, 'rol' => $rol, 'supervisor_id' => $sup_id, 'activo' => true];
                $this->usuarioDao->insert($data);
                echo json_encode(['success'=>true, 'msg'=>'Usuario creado exitosamente']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success'=>false, 'msg'=>$e->getMessage()]);
        }
    }

    public function toggleActivo(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false, 'msg'=>'ID inválido']); return; }

        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success'=>true, 'msg'=>'Estado actualizado']);
        } catch (\Exception $e) {
            echo json_encode(['success'=>false, 'msg'=>$e->getMessage()]);
        }
    }
}