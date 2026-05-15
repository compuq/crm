<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;

class ConfiguracionController extends Controller
{
    public function index(): void
    {
        $this->session->requireAuth();
        if (!in_array($this->session->getUser()['role'], ['admin', 'supervisor_general'])) {
            header("Location: ?action=dashboard"); exit;
        }

        $carteras   = $this->carteraDao->findAll();
        $pageTitle = "Configuración del Sistema | LEX 360";
        
        ob_start();
        require_once __DIR__ . '/../views/configuracion/index.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }

    public function cargarTipologias(): void
    {
        $this->session->requireAuth();
        // Header JSON ANTES de cualquier salida
        header('Content-Type: application/json; charset=utf-8');

        try {
            $carteraId = (int)($_POST['id_cartera'] ?? 0);
            if ($carteraId <= 0) {
                echo json_encode(['success' => false, 'msg' => 'Selecciona una cartera válida.']);
                return;
            }

            if (!isset($_FILES['csv_tipologias']) || $_FILES['csv_tipologias']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'msg' => 'Error al recibir el archivo.']);
                return;
            }

            $handle = fopen($_FILES['csv_tipologias']['tmp_name'], 'r');
            if (!$handle) throw new \Exception("No se pudo abrir el CSV.");

            // Saltar BOM y leer headers
            $firstLine = fgets($handle);
            if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") $firstLine = substr($firstLine, 3);
            $headers = str_getcsv($firstLine);
            if (count($headers) < 4) {
                fclose($handle);
                throw new \Exception("CSV inválido. Se requieren 4 columnas: codigo, clase, padre, nombre");
            }

            $this->db->beginTransaction();
            $count = 0;
            $errors = [];
            $lineNum = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $lineNum++;
                if (count($row) < 4) continue;

                $codigo = trim($row[0] ?? '');
                $clase  = strtoupper(trim($row[1] ?? 'T'));
                $padre  = trim($row[2] ?? '');
                $nombre = trim($row[3] ?? '');

                if (empty($codigo) || empty($nombre)) {
                    $errors[] = "Línea $lineNum: Código o nombre vacío.";
                    continue;
                }

                $padreId = null;
                if ($clase === 'S' && !empty($padre)) {
                    $stmt = $this->db->prepare("SELECT id FROM tipologias WHERE codigo_origen = :cod AND id_cartera = :cid LIMIT 1");
                    $stmt->execute(['cod' => $padre, 'cid' => $carteraId]);
                    $res = $stmt->fetch();
                    if (!$res) {
                        $errors[] = "Línea $lineNum: Padre '$padre' no existe en esta cartera.";
                    } else {
                        $padreId = $res['id'];
                    }
                }

                $stmt = $this->db->prepare("
                    INSERT INTO tipologias (codigo_origen, clase, padre_id, nombre, id_cartera)
                    VALUES (:cod, :cla, :pid, :nom, :cid)
                    ON CONFLICT (codigo_origen, id_cartera) DO UPDATE
                    SET nombre = EXCLUDED.nombre, padre_id = EXCLUDED.padre_id, clase = EXCLUDED.clase
                ");
                $stmt->execute(['cod' => $codigo, 'cla' => $clase, 'pid' => $padreId, 'nom' => $nombre, 'cid' => $carteraId]);
                $count++;
            }
            fclose($handle);

            if (!empty($errors)) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'msg' => "Operación cancelada: " . count($errors) . " errores.", 'errors' => $errors]);
                return;
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'msg' => "✅ $count tipologías cargadas correctamente."]);

        } catch (\Throwable $e) { // Captura TODO: Exception, Error, PDOException
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['success' => false, 'msg' => 'Error interno: ' . $e->getMessage()]);
        }
    }
    public function guardarCartera(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success'=>false, 'msg'=>'Método no permitido']); return;
        }

        $user = $this->session->getUser();

        try {
            $data = $_POST;
            $id   = $data['id'] ?? null;
            unset($data['id']);
            
            // Limpieza básica de campos vacíos
            $data = array_filter($data, fn($v) => $v !== '');

            if ($id) {
                // Actualizar existente
                $this->carteraDao->update((int)$id, $data);
                
                // ✅ Registrar evento en Auditoría
                \LEX360\Models\Services\LogService::registrar(
                    $this->db, $user['id'], 'update', 'carteras', $id, null, $data
                );
                
                echo json_encode(['success'=>true, 'msg'=>'Cartera actualizada correctamente.']);
            } else {
                // Crear nueva
                $newId = $this->carteraDao->insert($data);
                
                // ✅ Registrar evento en Auditoría
                \LEX360\Models\Services\LogService::registrar(
                    $this->db, $user['id'], 'insert', 'carteras', $newId, null, $data
                );
                
                echo json_encode(['success'=>true, 'msg'=>'Cartera creada correctamente.']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success'=>false, 'msg'=>$e->getMessage()]);
        }
    }
    /**
     * AJAX: Obtiene tipologías filtradas por cartera sin recargar
     */
    public function obtenerTipologias(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        $carteraId = (int)($_GET['cartera_id'] ?? 0);
        if ($carteraId <= 0) {
            echo json_encode(['success' => false, 'msg' => 'Seleccione una cartera']);
            return;
        }

        try {
            // Reutilizamos el DAO existente
            $tipos = $this->tipologiaDao->findByCartera($carteraId);
            echo json_encode(['success' => true, 'data' => $tipos]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    }
    /** AJAX: Obtener campos extra de una cartera */
    /** AJAX: Obtener campos extra de una cartera */
    public function obtenerExtras(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        $cid = (int)($_GET['cartera_id'] ?? 0);
        if ($cid <= 0) {
            echo json_encode(['success' => false, 'msg' => 'ID inválido']);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, nombre_campo, etiqueta, tipo FROM extras_cartera WHERE id_cartera = :cid AND activo = true ORDER BY orden, id");
            $stmt->execute(['cid' => $cid]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    /** AJAX: Agregar campo extra */
    public function guardarExtra(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'msg' => 'Método no permitido']);
            return;
        }

        try {
            $cid = (int)($_POST['id_cartera'] ?? 0);
            $nombre = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $_POST['nombre_campo'] ?? '')));
            $etiqueta = trim($_POST['etiqueta'] ?? $_POST['nombre_campo'] ?? '');
            
            if (empty($nombre)) {
                echo json_encode(['success' => false, 'msg' => 'Nombre de campo requerido']);
                return;
            }

            $stmt = $this->db->prepare("INSERT INTO extras_cartera (id_cartera, nombre_campo, etiqueta, tipo, activo) VALUES (:cid, :nom, :etiq, 'texto', true) ON CONFLICT (id_cartera, nombre_campo) DO UPDATE SET etiqueta = EXCLUDED.etiqueta");
            $stmt->execute(['cid' => $cid, 'nom' => $nombre, 'etiq' => $etiqueta]);
            
            echo json_encode(['success' => true, 'msg' => 'Campo guardado correctamente']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    /** AJAX: Eliminar campo extra */
    public function eliminarExtra(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'msg' => 'ID inválido']);
            return;
        }

        try {
            $this->db->prepare("DELETE FROM extras_cartera WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['success' => true, 'msg' => 'Campo eliminado']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    }
}