<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;

class GestionController extends Controller
{
    public function getTipologias(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');
        
        // Obtener ID de cartera del cliente seleccionado (opcional pero recomendado)
        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        $carteraId = null;
        
        if ($clienteId) {
            $stmt = $this->db->prepare("SELECT id_cartera FROM clientes WHERE id = :cid LIMIT 1");
            $stmt->execute(['cid' => $clienteId]);
            $res = $stmt->fetch();
            $carteraId = $res ? $res['id_cartera'] : null;
        }
        
        $tipologias = $this->tipologiaDao->findAllForSelect($carteraId);
        echo json_encode($tipologias);
    }
    public function registrar(): void
    {
        $this->session->requireAuth();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $user = $this->session->getUser();
        $data = [
            'cliente_id'      => $_POST['cliente_id'] ?? 0,
            'id_usuario'      => $user['id'],
            'estatus'         => $_POST['estatus'] ?? 'SINC',
            'telefono_usado'  => $_POST['telefono_usado'] ?? '',
            'id_tipologia'    => $_POST['tipologia'] ?? null,
            'comentario'      => $_POST['comentario'] ?? ''
        ];

        if (empty($data['comentario'])) {
            echo json_encode(['success' => false, 'message' => 'El comentario es obligatorio']);
            return;
        }

        try {
            $this->db->beginTransaction();
            
            // 1. Insertar en historial
            $sql = "INSERT INTO historial (id_cliente, id_usuario, estatus, telefono_utilizado, id_tipologia, comentario) 
                    VALUES (:cliente_id, :id_usuario, :estatus, :telefono_usado, :id_tipologia, :comentario)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            $historialId = $this->db->lastInsertId();

            // 2. Actualizar fecha última gestión del cliente
            $this->db->prepare("UPDATE clientes SET fecha_ultima_gestion = NOW() WHERE id = :id")
                 ->execute(['id' => $data['cliente_id']]);

            // 3. Si es COMPROMISO, guardar en tabla promesas
            if ($data['estatus'] === 'COMP') {
                $monto = $_POST['monto_promesa'] ?? 0;
                $fechaComp = $_POST['fecha_compromiso'] ?? date('Y-m-d', strtotime('+3 days'));
                $this->db->prepare("INSERT INTO promesas (id_cliente, id_usuario, monto_prometido, fecha_compromiso) VALUES (:c, :u, :m, :f)")
                     ->execute(['c' => $data['cliente_id'], 'u' => $user['id'], 'm' => $monto, 'f' => $fechaComp]);
            }

            // ✅ NUEVO: Registrar en Auditoría
            \LEX360\Models\Services\LogService::registrar(
                $this->db,
                $user['id'],
                'gestion_cliente',
                'historial',
                (int)$historialId,
                null,
                ['estatus' => $data['estatus'], 'tipologia' => $data['id_tipologia'], 'comentario' => $data['comentario']]
            );

            $this->db->commit();
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function registrarGestion(): void
    {
        $this->session->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $user = $this->session->getUser();
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $tipologiaId = (int)($_POST['tipologia'] ?? 0);
        $comentario  = trim($_POST['comentario'] ?? '');
        $estatus     = $_POST['estatus'] ?? 'COMP'; // COMP, SINC, PEND, etc.

        if (!$clienteId || !$tipologiaId) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']); exit;
        }

        try {
            // 📦 Capturar campos extras dinámicos (prefijo: extra_nombre_campo)
            $extras = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'extra_') === 0 && !empty(trim($value))) {
                    $campoLimpio = str_replace('extra_', '', $key);
                    $extras[$campoLimpio] = trim($value);
                }
            }
            $dataExtrasJson = !empty($extras) ? json_encode($extras, JSON_UNESCAPED_UNICODE) : '{}';

            //  Insertar en historial
            $stmt = $this->db->prepare("
                INSERT INTO historial (
                    id_cliente, id_usuario, id_tipologia, estatus, comentario, 
                    telefono_utilizado, fecha_gestion, data_extras
                ) VALUES (
                    :id_cliente, :id_usuario, :id_tipologia, :estatus, :comentario,
                    :telefono, NOW(), :data_extras
                )
            ");
            
            $stmt->execute([
                'id_cliente'    => $clienteId,
                'id_usuario'    => $user['id'],
                'id_tipologia'  => $tipologiaId,
                'estatus'       => $estatus,
                'comentario'    => $comentario,
                'telefono'      => $_POST['telefono_utilizado'] ?? null,
                'data_extras'   => $dataExtrasJson
            ]);

            // Actualizar fecha_ultima_gestion en cliente
            $this->db->prepare("UPDATE clientes SET fecha_ultima_gestion = NOW() WHERE id = :id")
                    ->execute(['id' => $clienteId]);

            echo json_encode(['success' => true, 'message' => '✅ Gestión registrada correctamente']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }    
}