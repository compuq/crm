<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;

class AuthController extends Controller
{
    public function loginView(): void
    {
        // Si ya está logueado, lo mandamos al dashboard
        if ($this->session->isLoggedIn()) {
            header("Location: ?action=dashboard");
            exit;
        }
        
        // Por ahora solo mostramos un texto, luego pondremos la vista HTML
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function doLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=login");
            exit;
        }

        $username = $_POST['usuario'] ?? '';
        $password = $_POST['password'] ?? '';
        $error = '';

        // Buscar usuario
        $user = $this->usuarioDao->findByUsername($username);

        if ($user && password_verify($password, $user['clave_hash'])) {
            if ($user['activo']) {
                // Iniciar sesión
                $this->session->login($user);
                
                // ✅ Registrar evento en Auditoría
                \LEX360\Models\Services\LogService::registrar(
                    $this->db, 
                    $user['id'], 
                    'login', 
                    'usuarios', 
                    $user['id']
                );

                header("Location: ?action=dashboard");
                exit;
            } else {
                $error = "Cuenta inactiva. Contacte al administrador.";
            }
        } else {
            $error = "Credenciales incorrectas.";
        }

        // Si falla, mostrar login con error
        require_once __DIR__ . '/../views/auth/login.php';
    }
    public function logout(): void
    {
        $this->session->logout();
        header("Location: ?action=login");
        exit;
    }
        public function cambiarClave(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success'=>false, 'msg'=>'Método no permitido']); return;
        }

        $user = $this->session->getUser();
        $actual    = $_POST['actual'] ?? '';
        $nueva     = $_POST['nueva'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if (empty($actual) || empty($nueva) || empty($confirmar)) {
            echo json_encode(['success'=>false, 'msg'=>'Todos los campos son obligatorios']); return;
        }
        if ($nueva !== $confirmar) {
            echo json_encode(['success'=>false, 'msg'=>'Las contraseñas nuevas no coinciden']); return;
        }
        if (strlen($nueva) < 6) {
            echo json_encode(['success'=>false, 'msg'=>'Mínimo 6 caracteres']); return;
        }

        // Verificar contraseña actual
        $stmt = $this->db->prepare("SELECT clave_hash FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $user['id']]);
        $hashActual = $stmt->fetchColumn();

        if (!password_verify($actual, $hashActual)) {
            echo json_encode(['success'=>false, 'msg'=>'La contraseña actual es incorrecta']); return;
        }

        // Actualizar en BD
        $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE usuarios SET clave_hash = :hash WHERE id = :id");
        $stmt->execute(['hash' => $nuevoHash, 'id' => $user['id']]);

        echo json_encode(['success'=>true, 'msg'=>'Contraseña actualizada correctamente.']);
    }
}