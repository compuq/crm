<?php
namespace LEX360\Controllers;

use Exception;

class GestionController extends \LEX360\Core\Controller
{
    public function getTipologiasConfig(): void
    {
        header('Content-Type: application/json');
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, requiere_proxima_fecha, requiere_monto, estatus_default FROM tipologias");
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

        public function registrarGestion(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $this->session->requireAuth();
        $user = $this->session->getUser();

        // 1. Sanitización estricta de datos
        $clienteId   = (int)($_POST['cliente_id'] ?? 0);
        $tipologiaId = (int)($_POST['tipologia'] ?? 0);
        $comentario  = trim($_POST['comentario'] ?? '');
        $telefono    = substr(trim($_POST['telefono_utilizado'] ?? ''), 0, 20);
        
        // ✅ VALIDACIÓN ESTRICTA DE ESTATUS (máx 4 chars, solo valores permitidos)
        $estatusRaw = strtoupper(trim($_POST['estatus'] ?? ''));
        $estatusValidos = ['SINC', 'COMP', 'PAGG', 'PAGO'];
        $estatus = in_array($estatusRaw, $estatusValidos, true) ? $estatusRaw : 'SINC';

        if (!$clienteId || !$tipologiaId || empty($comentario)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            // 2. Validar tipología y configuración
            $stmtTip = $this->db->prepare("SELECT id, requiere_proxima_fecha, requiere_monto, estatus_default FROM tipologias WHERE id = :id");
            $stmtTip->execute(['id' => $tipologiaId]);
            $tipologia = $stmtTip->fetch();
            if (!$tipologia) {
                echo json_encode(['success' => false, 'message' => 'Tipología no válida']);
                exit;
            }

            // Si no se envió estatus válido, usar el default de la tipología
            if ($estatus === 'SINC' && isset($tipologia['estatus_default'])) {
                $estatus = strtoupper(trim($tipologia['estatus_default']));
                if (!in_array($estatus, $estatusValidos, true)) $estatus = 'SINC';
            }

            // 3. Manejo de Fecha Próxima Llamada (TIMESTAMP)
            $fechaProxima = null;
            $fechaRaw = $_POST['fecha_proxima_llamada'] ?? '';
            if (!empty(trim($fechaRaw))) {
                $dt = \DateTime::createFromFormat('Y-m-d\TH:i', trim($fechaRaw));
                $fechaProxima = $dt ? $dt->format('Y-m-d H:i:s') : null;
            } elseif ($tipologia['requiere_proxima_fecha']) {
                echo json_encode(['success' => false, 'message' => '⚠️ Fecha próxima obligatoria para esta tipología']);
                exit;
            }

            // 4. Capturar extras dinámicos
            $jsonExtras = $this->capturarExtras($_POST);

            // 5. Insertar en Historial
            $stmt = $this->db->prepare("
                INSERT INTO historial (id_cliente, id_usuario, id_tipologia, estatus, comentario, telefono_utilizado, fecha_gestion, fecha_proxima_llamada, data_extras) 
                VALUES (:cli, :usr, :tip, :est, :com, :tel, NOW(), :fecha, :extras::jsonb)
            ");
            $stmt->execute([
                'cli' => $clienteId, 'usr' => $user['id'], 'tip' => $tipologiaId, 
                'est' => $estatus, 'com' => $comentario, 'tel' => $telefono, 
                'fecha' => $fechaProxima, 'extras' => $jsonExtras
            ]);
            $historialId = $this->db->lastInsertId();

            // 6. Distribución según Estatus
            if ($estatus === 'COMP') {
                $monto = $_POST['monto_gestion'] ?? null;
                $fechaComp = $_POST['fecha_compromiso'] ?? date('Y-m-d', strtotime('+3 days'));
                
                if ($monto) {
                    $this->db->prepare("INSERT INTO promesas (id_cliente, id_usuario, monto_prometido, fecha_compromiso, estatus, fecha_registro) 
                                        VALUES (:c, :u, :m, :f, 'pendiente', NOW())")
                         ->execute(['c'=>$clienteId, 'u'=>$user['id'], 'm'=>floatval($monto), 'f'=>$fechaComp]);
                }
            } 
            elseif ($estatus === 'PAGG') {
                $monto = $_POST['monto_gestion'] ?? null;
                if ($monto) {
                    // ✅ CORRECCIÓN CLAVE: 'PAGG' en lugar de 'pendiente' para respetar varchar(4)
                    $this->db->prepare("INSERT INTO pagos (id_cliente, monto, fecha_pago, referencia_bancaria, estatus, validado_por, fecha_validacion, id_historial) 
                                        VALUES (:c, :m, NOW(), :ref, 'PAGG', NULL, NULL,$historialId)")
                         ->execute(['c'=>$clienteId, 'm'=>floatval($monto), 'ref'=>substr($_POST['referencia_pago'] ?? '', 0, 100)]);
                }
            }

            // 7. Actualizar cliente
            $this->db->prepare("UPDATE clientes SET fecha_ultima_gestion = NOW() WHERE id = :id")->execute(['id'=>$clienteId]);
            $this->db->commit();
            echo json_encode(['success' => true, 'message' => '✅ Gestión registrada']);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[LEX360] registrarGestion: ' . $e->getMessage() . ' | estatus_raw=' . ($_POST['estatus'] ?? 'null'));
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Retorna configuración de tipologías para el frontend (JS)
     * Se llama al cargar la página para evitar async/await
     */
    public function getTipologias(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        try {
            $sql = "SELECT t.id, t.nombre, t.padre_id, t.requiere_proxima_fecha 
                    FROM tipologias t
                    LEFT JOIN tipologias p 
                        ON t.padre_id = p.id
                    ORDER BY 
                        COALESCE(t.padre_id, t.id), -- agrupa padre e hijos
                        CASE 
                            WHEN t.padre_id IS NULL THEN 0 
                            ELSE 1 
                        END,
                        t.nombre;
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            error_log('[LEX360] getTipologias: ' . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }
    /**
     * Registra una nueva gestión en el historial
     * Maneja fecha/hora, obligatoriedad dinámica y extras JSON
     */


    private function capturarExtras(array $postData): string
    {
        $extras = [];
        foreach ($postData as $key => $value) {
            if (strpos($key, 'extra_') === 0 && trim($value) !== '') {
                $extras[substr($key, 6)] = trim($value);
            }
        }
        return $extras ? json_encode($extras, JSON_UNESCAPED_UNICODE) : '{}';
    }

    /**
     * Helper privado: Extrae campos extra_ y los convierte a JSON
     */
        /**
     * Retorna tipologías en formato JSON para el modal de gestión
     * Filtra por cartera si el usuario no es admin
     */
/*     public function getTipologias(): void
    {
        header('Content-Type: application/json');
        
        $this->session->requireAuth();
        $user = $this->session->getUser();
        $clienteId = (int)($_GET['cliente_id'] ?? 0);

        try {
            // Obtener cartera del cliente para filtrar tipologías (si aplica)
            $carteraId = null;
            if ($clienteId > 0) {
                $stmt = $this->db->prepare("SELECT id_cartera FROM clientes WHERE id = :id");
                $stmt->execute(['id' => $clienteId]);
                $carteraId = $stmt->fetchColumn();
            }

            // Consulta base de tipologías
            $sql = "SELECT id, nombre, padre_id, requiere_proxima_fecha FROM tipologias WHERE activo = true";
            $params = [];

            // Filtrar por cartera si el usuario es gestor o supervisor
            if ($user['role'] === 'gestor' && $carteraId) {
                $sql .= " AND (id_cartera = :cid OR id_cartera IS NULL)";
                $params['cid'] = $carteraId;
            }

            $sql .= " ORDER BY orden ASC, nombre ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            // En producción, loguear en lugar de mostrar
            error_log('[LEX360] Error getTipologias: ' . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    } */
}