<?php
namespace LEX360\Controllers;

// Asegúrate de tener esto si tu base controller no lo tiene globalmente
use \Exception;

class ConfigController extends \LEX360\Core\Controller // O tu clase base correspondiente
{
    /**
     * Muestra la pantalla de configuración de extras para una cartera específica
     */

    public function configurarExtras(): void
    {
        $this->session->requireAuth();
        
        $carteraId = (int)($_GET['id'] ?? 0);
        if (!$carteraId) {
            header("Location: index.php?action=lista_carteras");
            exit;
        }

        // Obtener extras configurados
        $stmt = $this->db->prepare("
            SELECT * FROM extras_cartera 
            WHERE id_cartera = :cid 
            ORDER BY modulo, orden ASC
        ");
        $stmt->execute(['cid' => $carteraId]);
        $extras = $stmt->fetchAll();

        // ✅ Variables para el layout (igual que en ClienteController)
        $pageTitle = "Configurar Extras | LEX 360";
        
        // ✅ Buffer de salida (PATRÓN ARQUITECTÓNICO)
        ob_start();
        require_once __DIR__ . '/../views/carteras/configurar_extras.php';
        $viewContent = ob_get_clean();
        
        // ✅ Renderizar layout maestro (frontend.php)
        require_once __DIR__ . '/../views/frontend.php';
    }

    /**
     * Guarda un nuevo campo extra (Clientes o Gestiones)
     */
    public function guardarCampoExtra(): void
    {
        $this->session->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=configurar_extras&id=" . ($_POST['id_cartera'] ?? 0));
            exit;
        }

        $carteraId   = (int)($_POST['id_cartera'] ?? 0);
        $nombreCampo = strtolower(trim(preg_replace('/[^a-z0-9_]/', '_', $_POST['nombre_campo'])));
        $etiqueta    = trim($_POST['etiqueta'] ?? '');
        $modulo      = in_array($_POST['modulo'], ['clientes', 'gestiones']) ? $_POST['modulo'] : 'clientes';

        if (!$carteraId || empty($nombreCampo) || empty($etiqueta)) {
            $this->redirigirConMensaje('⚠️ Completa todos los campos correctamente.', 'warning', $carteraId);
            return;
        }

        try {
            // Validar que no exista otro campo con el mismo nombre técnico en esta cartera
            $stmt = $this->db->prepare("SELECT id FROM extras_cartera WHERE id_cartera = :cid AND nombre_campo = :nc");
            $stmt->execute(['cid' => $carteraId, 'nc' => $nombreCampo]);
            if ($stmt->fetch()) {
                $this->redirigirConMensaje("⚠️ Ya existe el campo técnico '$nombreCampo' en esta cartera.", 'warning', $carteraId);
                return;
            }

            $stmt = $this->db->prepare("
                INSERT INTO extras_cartera (id_cartera, nombre_campo, etiqueta, modulo, activo, orden) 
                VALUES (:cid, :nc, :et, :mod, true, (SELECT COALESCE(MAX(orden),0)+1 FROM extras_cartera WHERE id_cartera = :cid2))
            ");
            $stmt->execute([
                'cid' => $carteraId, 
                'nc' => $nombreCampo, 
                'et' => $etiqueta, 
                'mod' => $modulo, 
                'cid2' => $carteraId
            ]);

            $this->redirigirConMensaje("✅ Campo '$etiqueta' ($modulo) guardado correctamente.", 'success', $carteraId);
        } catch (Exception $e) {
            $this->redirigirConMensaje("❌ Error BD: " . $e->getMessage(), 'danger', $carteraId);
        }
    }

    /**
     * Activa o Desactiva un campo extra
     */
    public function toggleExtra(): void
    {
        $this->session->requireAuth();
        
        $id      = (int)($_GET['id'] ?? 0);
        $cartera = (int)($_GET['cartera'] ?? 0);

        if (!$id || !$cartera) {
            header("Location: index.php?action=configurar_extras&id=$cartera");
            exit;
        }

        try {
            $stmt = $this->db->prepare("UPDATE extras_cartera SET activo = NOT activo WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $this->redirigirConMensaje('✅ Estado actualizado.', 'success', $cartera);
        } catch (Exception $e) {
            $this->redirigirConMensaje('❌ Error: ' . $e->getMessage(), 'danger', $cartera);
        }
    }

    /**
     * Helper privado para redirecciones con mensajes flash
     */
    private function redirigirConMensaje(string $msg, string $tipo, int $carteraId): void
    {
        $_SESSION['flash_message'] = $msg;
        $_SESSION['flash_type'] = $tipo;
        // Redirige de vuelta a la configuración de esa cartera específica
        header("Location: index.php?action=configurar_extras&id=" . $carteraId);
        exit;
    }
}
