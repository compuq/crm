<?php
namespace LEX360\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $stmt = $this->db->prepare("SELECT nombre_campo, etiqueta FROM extras_cartera WHERE id_cartera = :cid AND activo = true ORDER BY id desc");
        $stmt->execute(['cid' => $carteraId]);
        $configExtras = $stmt->fetchAll();
    }
    
    // 2. Obtener clientes
    $clientes = $this->clienteDao->findByRole($user['id'], $user['role'], $search);
    $pagg = $this->clienteDao->findNoConfirm($user['id'], $user['role'], $search);
    $incumplidas = $this->clienteDao->findNoDone($user['id'], $user['role'], $search);

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
}

public function exportarClientes(): void
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
    $hoy = [];
    $atrasados = [];
    $asignados = [];
    $programados = [];
    //$incumplidas =[];
    $comp = [];
    //$pagg = [];
    $userRole=$user['role']??'';
    foreach ($clientes as $cliente) {

        $fecha = $cliente['fecha_proxima_llamada'] ?? null;
        $estatus = $cliente['ultimo_estatus'] ?? null;

        // ===== ASIGNADOS =====
        if (empty($fecha)) {
            $asignados[] = $cliente;
            continue;
        }
        
        $timestamp = strtotime($fecha);

        // ===== HOY =====
        if (date('Y-m-d', $timestamp) == date('Y-m-d')) {

            $hoy[] = $cliente;

        }

        // ===== ATRASADOS =====
        elseif (date('Y-m-d', $timestamp) < date('Y-m-d')) {

            $atrasados[] = $cliente;

        }

        // ===== PROGRAMADOS =====
        elseif (date('Y-m-d', $timestamp) > date('Y-m-d')) {

            $programados[] = $cliente;

        }

        // ===== COMPROMISOS =====
        if ($estatus === 'COMP') {
            $comp[] = $cliente;
        }

    }

    //$hoy          = $this->clienteDao->obtenerHoy($user['id'], $user['rol'], $search);
    //$atrasados    = $this->clienteDao->obtenerAtrasados($user['id'], $user['rol'], $search);
    //$asignados    = $this->clienteDao->obtenerAsignados($user['id'], $user['rol'], $search);
    //$programados  = $this->clienteDao->obtenerProgramados($user['id'], $user['rol'], $search);
    //$comp         = $this->clienteDao->obtenerCompromisos($user['id'], $user['rol'], $search);
    //$incumplidas  = $this->clienteDao->obtenerIncumplidas($user['id'], $user['rol'], $search);
    //$pagg         = $this->clienteDao->findNoConfirm($user['id'], $user['rol'], $search);

    $spreadsheet = new Spreadsheet();

    // Hoja 1
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Hoy');
    $this->llenarHoja($sheet, $hoy);

    // Hoja 2
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('Atrasados');
    $this->llenarHoja($sheet2, $atrasados);

    // Hoja 3
    $sheet3 = $spreadsheet->createSheet();
    $sheet3->setTitle('Asignados');
    $this->llenarHoja($sheet3, $asignados);

    // Hoja 4
    $sheet4 = $spreadsheet->createSheet();
    $sheet4->setTitle('Programados');
    $this->llenarHoja($sheet4, $programados);

    // Hoja 5
    $sheet5 = $spreadsheet->createSheet();
    $sheet5->setTitle('Compromisos');
    $this->llenarHoja($sheet5, $comp);

    // Hoja 6
    $sheet6 = $spreadsheet->createSheet();
    $sheet6->setTitle('Incumplidas');
    $this->llenarHoja($sheet6, $incumplidas);

    // Hoja 7
    $sheet7 = $spreadsheet->createSheet();
    $sheet7->setTitle('PAGG');
    $this->llenarHoja($sheet7, $pagg);

    // Dejar activa la primera hoja
    $spreadsheet->setActiveSheetIndex(0);

    $filename = 'clientes_' . date('Ymd_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

private function llenarHoja($sheet, array $clientes): void
{
    $headers = [
        'ID',
        'gestor',
        'supervisor',
        'Nombre',
        'Identificación',
        'Cuenta',
        'Saldo Inicial',
        'Saldo',
        'Teléfono 1',
        'Teléfono 2',
        'Estado',
        'Última Gestión',
        'Próxima Llamada',
        'Tipología'
    ];

    $col = 'A';

    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    $fila = 2;

    foreach ($clientes as $c) {

        $sheet->setCellValue("A{$fila}", $c['id']);
        $sheet->setCellValue("B{$fila}", $c['usuario']);
        $sheet->setCellValue("C{$fila}", $c['supervisor']);
        $sheet->setCellValue("D{$fila}", $c['nombre']);
        $sheet->setCellValue("E{$fila}", $c['identificacion']);
        $sheet->setCellValue("F{$fila}", $c['cuenta']);
        $sheet->setCellValue("G{$fila}", $c['saldo_inicial']);
        $sheet->setCellValue("H{$fila}", $c['saldo']);
        $sheet->setCellValue("I{$fila}", $c['telefono_1']);
        $sheet->setCellValue("J{$fila}", $c['telefono_2']);
        $sheet->setCellValue("K{$fila}", $c['estado']);
        $sheet->setCellValue("L{$fila}", $c['fecha_ultima_gestion']);
        $sheet->setCellValue("M{$fila}", $c['fecha_proxima_llamada']);
        $sheet->setCellValue("N{$fila}", $c['ultima_tipologia']);
        $fila++;
    }

    foreach (range('A', 'L') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
}
public function modificar()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        
        $usuarios = $this->db->query("
            SELECT
                id,
                nombre,
                rol,
                supervisor_id
            FROM usuarios
            WHERE activo = true
            ORDER BY nombre
        ")->fetchAll(PDO::FETCH_ASSOC);
        ob_start();
        require __DIR__ . '/../views/clientes/modificar.php';
        $viewContent = ob_get_clean();
        
        // ✅ ESTO ES CRÍTICO: Incluir frontend.php
        require_once __DIR__ . '/../views/frontend.php';
        exit;

    }

    header('Content-Type: application/json; charset=utf-8');

    $accion = $_POST['accion'] ?? '';

    try {

        switch ($accion) {

            case 'buscar':

                $nombre = trim($_POST['nombre'] ?? '');
                $datos  = trim($_POST['datos'] ?? '');

                if ($nombre === '' && $datos === '') {
                    echo json_encode([
                        'ok' => false,
                        'mensaje' => 'Debe indicar un criterio de búsqueda'
                    ]);
                    exit;
                }

                if ($nombre !== '') {

                    $sql = "
                        SELECT
                            id,
                            cuenta,
                            identificacion,
                            nombre,
                            saldo,
                            telefono_1,
                            id_gestor_asignado
                        FROM clientes
                        WHERE to_tsvector(
                            'spanish',
                            lower(coalesce(nombre,''))
                        ) @@ plainto_tsquery(
                            'spanish',
                            lower(:nombre)
                        )
                        ORDER BY nombre
                        LIMIT 20
                    ";

                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        ':nombre' => $nombre
                    ]);

                } else {

                    $sql = "
                        SELECT
                            id,
                            cuenta,
                            identificacion,
                            nombre,
                            saldo,
                            telefono_1,
                            id_gestor_asignado
                        FROM clientes
                        WHERE search_vector
                        @@ websearch_to_tsquery(
                            'spanish',
                            :datos
                        )
                        ORDER BY nombre
                        LIMIT 20
                    ";

                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        ':datos' => $datos
                    ]);
                }

                echo json_encode([
                    'ok' => true,
                    'clientes' => $stmt->fetchAll(PDO::FETCH_ASSOC)
                ]);
                exit;

            case 'cargar':

                $id = (int)($_POST['id'] ?? 0);

                $stmt = $this->db->prepare("
                    SELECT *
                    FROM clientes
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':id' => $id
                ]);

                $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$cliente) {

                    echo json_encode([
                        'ok' => false,
                        'mensaje' => 'Cliente no encontrado'
                    ]);

                    exit;
                }

                $stmt = $this->db->prepare("
                    SELECT
                        nombre_campo,
                        etiqueta
                    FROM extras_cartera
                    WHERE id_cartera = :id_cartera
                    ORDER BY id
                ");

                $stmt->execute([
                    ':id_cartera' => $cliente['id_cartera']
                ]);

                $camposExtras = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'ok' => true,
                    'cliente' => $cliente,
                    'campos_extras' => $camposExtras
                ]);

                exit;
            case 'guardar':
                $dataExtras = $_POST['data_extras'] ?? '{}';

                json_decode($dataExtras, true);

                if (json_last_error() !== JSON_ERROR_NONE) {

                    echo json_encode([
                        'ok' => false,
                        'mensaje' => 'JSON de data_extras inválido'
                    ]);

                    exit;
                }
                $stmt = $this->db->prepare("
                    UPDATE clientes
                    SET
                        nombre = :nombre,
                        saldo = :saldo,
                        saldo_inicial = :saldo_inicial,
                        telefono_1 = :telefono_1,
                        telefono_2 = :telefono_2,
                        estado = :estado,
                        data_extras = CAST(:data_extras AS jsonb),
                        id_supervisor_cadena = :id_supervisor_cadena,
                        id_gestor_asignado = :id_gestor_asignado,
                        fecha_actualizacion = now()
                    WHERE id = :id                ");

                $stmt->execute([
                    ':id' => $_POST['id'],
                    ':nombre' => $_POST['nombre'],
                    ':saldo' => $_POST['saldo'],
                    ':saldo_inicial' => $_POST['saldo_inicial'],
                    ':telefono_1' => $_POST['telefono_1'],
                    ':telefono_2' => $_POST['telefono_2'],
                    ':estado' => $_POST['estado'],
                    ':id_supervisor_cadena' => $_POST['id_supervisor_cadena'],
                    ':id_gestor_asignado' => $_POST['id_gestor_asignado'],
                    ':data_extras' => $_POST['data_extras'],
                ]);

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'Cliente actualizado correctamente'
                ]);
                exit;
        }

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Acción inválida'
        ]);

    } catch (\Exception $e) {

        echo json_encode([
            'ok' => false,
            'mensaje' => $e->getMessage()
        ]);
    }

    exit;
}
public function eliminarCliente()
{
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)($_POST['id'] ?? 0);

    if (!$id) {

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Cliente inválido'
        ]);

        exit;
    }

    try {

        $this->db->beginTransaction();

        $stmt = $this->db->prepare("
            DELETE FROM historial
            WHERE id_cliente = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $historial = $stmt->rowCount();

        $stmt = $this->db->prepare("
            DELETE FROM promesas
            WHERE id_cliente = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $promesas = $stmt->rowCount();

        $stmt = $this->db->prepare("
            DELETE FROM pagos
            WHERE id_cliente = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $pagos = $stmt->rowCount();

        $stmt = $this->db->prepare("
            DELETE FROM clientes
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new Exception('No se pudo eliminar el cliente');
        }

        $this->db->commit();

        echo json_encode([
            'ok' => true,
            'mensaje' =>
                "Cliente eliminado correctamente\n" .
                "Historial: {$historial}\n" .
                "Promesas: {$promesas}\n" .
                "Pagos: {$pagos}"
        ]);

    } catch (\Exception $e) {

        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        echo json_encode([
            'ok' => false,
            'mensaje' => $e->getMessage()
        ]);
    }

    exit;
}
}