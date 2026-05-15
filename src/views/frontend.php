<?php
// frontend.php - Layout Maestro
// Este archivo conecta el Header, Navbar y Footer con el contenido de cada vista.

// 1. FALLBACK DE USUARIO: Si el Controller no pasó $user, lo tomamos de la sesión para evitar errores
if (!isset($user)) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['user_id'])) {
        $user = [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'Usuario',
            'role' => $_SESSION['role'] ?? 'gestor',
            'supervisor_id' => $_SESSION['supervisor_id'] ?? null
        ];
    } else {
        // Por si acaso no hay sesión activa (no debería pasar tras login)
        $user = ['role' => 'guest', 'name' => 'Invitado'];
    }
}

// 2. INCLUIR HEADER (CSS y Metadatos)
if (file_exists(__DIR__ . '/layouts/header.php')) {
    require_once __DIR__ . '/layouts/header.php';
} else {
    echo "<div class='alert alert-danger'>Error: No se encuentra src/views/layouts/header.php</div>";
}

// 3. INCLUIR NAV (Menú de Navegación)
if (file_exists(__DIR__ . '/layouts/nav.php')) {
    require_once __DIR__ . '/layouts/nav.php';
} else {echo"No existe el nav";}
?>

<!-- Contenido Principal de la Vista -->
<main class="container-fluid px-4 py-4">
    <?= $viewContent ?? '<div class="alert alert-warning">Contenido no disponible</div>' ?>
</main>

<?php 
// 4. INCLUIR FOOTER (Scripts y cierres HTML)
if (file_exists(__DIR__ . '/layouts/footer.php')) {
    require_once __DIR__ . '/layouts/footer.php';
}
?>