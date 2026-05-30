<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use PDO;
class ClienteController extends Controller
{
public function listar(): void
{
    $this->session->requireAuth();
    $user = $this->session->getUser();
    $search = $_GET['q'] ?? '';
    
    // Obtener cartera del usuario (ajusta según tu lógica de sesión/DAO)
    $carteraId = $user['id_cartera'] ?? null; // O $this->clienteDao->getCarteraByUser($user['id'])

    // ✅ 1. Obtener config de extras ANTES de renderizar la vista
    $configExtras = [];
    if (!empty($carteraId)) {
        $stmt = $this->db->prepare("SELECT nombre_campo, etiqueta FROM extras_cartera WHERE id_cartera = :cid AND activo = true ORDER BY orden");
        $stmt->execute(['cid' => $carteraId]);
        $configExtras = $stmt->fetchAll();
    }
    
    // 2. Obtener clientes
    $clientes = $this->clienteDao->findByRole($user['id'], $user['role'], $search);
    $pagg = $this->clienteDao->findNoConfirm($user['id'], $user['role'], '');
    $incumplidas = $this->clienteDao->findNoDone($user['id'], $user['role'], '');

    // 3. Variables para el layout
    $pageTitle = "Gestión de Clientes | LEX 360";
    
    // ✅ 4. Buffer de salida (AHORA $configExtras YA ESTÁ DISPONIBLE)
    ob_start();
    require_once __DIR__ . '/../views/clientes/listar.php';
    $viewContent = ob_get_clean();
    
    // 5. Renderizar layout maestro
    require_once __DIR__ . '/../views/frontend.php';
}
public function actualizar(): void
{
    $this->session->requireAuth();
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... */ }

    try {
        $id = (int)$_POST['id'];
        
        // 1. Recopilar campos fijos
        $data = [
            'nombre' => $_POST['nombre'],
            'telefono_1' => $_POST['telefono_1'],
            'estado' => $_POST['estado'],
            // ... otros campos fijos ...
        ];

        // 2. ✅ Recopilar CAMPOS EXTRA DINÁMICOS
        $extras = [];
        foreach ($_POST as $key => $value) {
            // Si el campo empieza con 'extra_', lo guardamos en el JSON
            if (strpos($key, 'extra_') === 0) {
                $campo = str_replace('extra_', '', $key);
                $extras[$campo] = $value;
            }
        }
        if (!empty($extras)) {
            $data['data_extras'] = json_encode($extras);
        }

        // 3. Actualizar en BD (asegúrate que tu DAO o query maneje data_extras)
        $sql = "UPDATE clientes SET nombre = :nombre, telefono_1 = :telefono_1, estado = :estado";
        if (!empty($extras)) $sql .= ", data_extras = :extras";
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $params = array_merge($data, ['id' => $id]);
        if (!empty($extras)) $params['extras'] = json_encode($extras);
        
        $stmt->execute($params);

        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    }
public function getDetalle(): void
{
    header('Content-Type: application/json');
    $this->session->requireAuth();

    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        echo json_encode([]);
        exit;
    }

    $stmt = $this->db->prepare("
        SELECT
            id,
            nombre,
            identificacion,
            cuenta,
            saldo_inicial,
            saldo,
            telefono_1,
            telefono_2,
            estado,
            fecha_ultima_gestion,
            data_extras,
            id_cartera
        FROM clientes
        WHERE id = :id
    ");

    $stmt->execute(['id' => $id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo json_encode([]);
        exit;
    }

    // Obtener configuración de extras
    $stmt = $this->db->prepare("
        SELECT nombre_campo, etiqueta
        FROM extras_cartera
        WHERE id_cartera = :cid
          AND activo = true
        ORDER BY orden
    ");

    $stmt->execute([
        'cid' => $cliente['id_cartera']
    ]);

    $configExtras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear mapa nombre_campo => etiqueta
    $mapaEtiquetas = [];

    foreach ($configExtras as $extra) {
        $mapaEtiquetas[$extra['nombre_campo']] = $extra['etiqueta'];
    }

    // Transformar data_extras
    $extras = json_decode($cliente['data_extras'] ?? '{}', true);

    if (is_array($extras)) {
        $extrasTransformados = [];

        foreach ($extras as $campo => $valor) {

            $nuevoNombre = $mapaEtiquetas[$campo] ?? $campo;

            $extrasTransformados[$nuevoNombre] = $valor;
        }

        $cliente['data_extras'] = $extrasTransformados;
    }

    echo json_encode($cliente);
    exit;
}}