<?php
namespace LEX360\Core;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }

    public function getUser(): ?array
    {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'role' => $_SESSION['role'],
                'supervisor_id' => $_SESSION['supervisor_id'] ?? null
            ];
        }
        return null;
    }

    public function login(array $user): void
    {
        session_regenerate_id(true); // Seguridad contra secuestro de sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['role'] = $user['rol'];
        $_SESSION['supervisor_id'] = $user['supervisor_id'] ?? null;
        $_SESSION['last_activity'] = time();
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            header("Location: ?action=login");
            exit;
        }
    }
    
    // Para futuras validaciones
    public function hasRole(array $allowedRoles): bool
    {
        if (!$this->isLoggedIn()) return false;
        return in_array($_SESSION['role'], $allowedRoles);
    }
}