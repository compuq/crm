<?php
namespace LEX360\Controllers;

use Exception;
use LEX360\Core\Controller;
use DateTime;
use NumberFormatter;
use PDO;
class PagoController extends Controller
{
    public function validar(): void
    {
        $this->session->requireAuth();
        // Solo admin y supervisor_general pueden validar
        $user = $this->session->getUser();
        if (!in_array($user['role'], ['admin', 'supervisor_general'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        // ✅ Filtros desde GET
        $filtroGestor = $_GET['gestor_id'] ?? '';
        $filtroSupervisor = $_GET['supervisor_id'] ?? '';
        $filtroFechaInicio = $_GET['fecha_inicio'] ?? '';
        $filtroFechaFin = $_GET['fecha_fin'] ?? '';
        $buscar = $_GET['buscar'] ?? '';
        // ✅ Obtener lista de gestores y supervisores para los filtros
        $gestores = $this->db->prepare("SELECT id, nombre FROM usuarios WHERE rol = 'gestor' AND activo = true ORDER BY nombre");
        $gestores->execute();
        $listaGestores = $gestores->fetchAll();

        $supervisores = $this->db->prepare("SELECT id, nombre FROM usuarios WHERE rol IN ('supervisor','supervisor_general') AND activo = true ORDER BY nombre");
        $supervisores->execute();
        $listaSupervisores = $supervisores->fetchAll();

        // ✅ Consulta de pagos pendientes con filtros dinámicos
        $sql = "
            SELECT 
                h.id as historial_id,
                p.id as pago_id,
                c.nombre, 
                c.identificacion, 
                c.saldo, 
                u.nombre as gestor,
                u.id as gestor_id,
                sup.nombre as supervisor,
                h.fecha_gestion, 
                h.comentario,
                p.monto,
                h.comentario
            FROM pagos p
            JOIN historial h ON h.id = p.id_historial
            JOIN clientes c ON h.id_cliente = c.id 
            JOIN usuarios u ON h.id_usuario = u.id 
            LEFT JOIN usuarios sup ON c.id_supervisor_cadena = sup.id
            WHERE p.estatus = 'PAGG'
            ";
        if (!empty($buscar)){
            $sql.="
            AND
            (c.search_vector @@ websearch_to_tsquery('spanish', :buscar)
            OR h.comentario ILIKE '%' || :buscar || '%')";
            $params = ['buscar'=>$buscar];
        } else{
            $params = [];
        }
        

        if (!empty($filtroGestor)) {
            $sql .= " AND u.id = :gestor_id";
            $params['gestor_id'] = (int)$filtroGestor;
        }
        if (!empty($filtroSupervisor)) {
            $sql .= " AND c.id_supervisor_cadena = :supervisor_id";
            $params['supervisor_id'] = (int)$filtroSupervisor;
        }
        if (!empty($filtroFechaInicio)) {
            $sql .= " AND h.fecha_gestion >= :fecha_inicio";
            $params['fecha_inicio'] = $filtroFechaInicio . ' 00:00:00';
        }
        if (!empty($filtroFechaFin)) {
            $sql .= " AND h.fecha_gestion <= :fecha_fin";
            $params['fecha_fin'] = $filtroFechaFin . ' 23:59:59';
        }

        $sql .= " ORDER BY h.fecha_gestion DESC LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $pendientes = $stmt->fetchAll();

        $pageTitle = "Validación de Pagos | LEX 360";
        $mensaje = $_SESSION['flash_message'] ?? '';
        $tipoMensaje = $_SESSION['flash_type'] ?? '';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        $validacion_usuario = [];

        if($user['role']=='supervisor_general'){
            $validacion_usuario = $this->clienteDao->getValidaciones($user['id']);
        }

        // ✅ Renderizar con layout maestro
        ob_start();
        require_once __DIR__ . '/../views/pagos/validar.php';
        $viewContent = ob_get_clean();
        require_once __DIR__ . '/../views/frontend.php';
    }

    /**
     * Valida un pago individual vía AJAX (modal)
     */
    private function getPagg($pagoId){
                $stmt = $this->db->prepare("
                SELECT p.id, p.id_historial, p.id_cliente, p.monto, h.estatus as hist_estatus
                FROM pagos p
                JOIN historial h ON h.id = p.id_historial
                WHERE p.id = :pago_id AND p.estatus = 'PAGG'
                FOR UPDATE
            ");
            $stmt->execute(['pago_id' => $pagoId]);
            $pago = $stmt->fetch();
            return $pago;
    }
    public function validarPago(): void
    {



        header('Content-Type: application/json');
        $this->session->requireAuth();
        $id_super=$_SESSION['user_id'];

        $datos_validacion=$this->clienteDao->getValidaciones($id_super);
        $datos_validacion=$datos_validacion[0]??[];
        if (!$datos_validacion) {
            echo json_encode(['success' => false, 'message' => 'Usuario no autorizado para validaciones.']);
            exit;
        } 
        
        $fechaVencimiento = new DateTime($datos_validacion['limite']);
        $ahora = new DateTime();

        $monto_autorizado = $datos_validacion['monto_autorizado'];

        if ($fechaVencimiento < $ahora) {
            echo json_encode(['success' => false, 'message' => 'Autorización vencida, notifique a su administrador.']);
            exit;
        } 
        if ($datos_validacion['estado']!='ACTIVA') {
            echo json_encode(['success' => false, 'message' => 'Autorización inactiva, notifique a su administrador.']);
            exit;
        } 



        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $user = $this->session->getUser();
        $pagoId = (int)($_POST['pago_id'] ?? 0);
        $referencia = trim($_POST['referencia_bancaria'] ?? '');

        if (!$pagoId || empty($referencia)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            // 1. Obtener el pago y su historial asociado
            $stmt = $this->db->prepare("
                SELECT p.id, p.id_historial, p.id_cliente, p.monto, h.estatus as hist_estatus
                FROM pagos p
                JOIN historial h ON h.id = p.id_historial
                WHERE p.id = :pago_id AND p.estatus = 'PAGG'
                FOR UPDATE
            ");
            $stmt->execute(['pago_id' => $pagoId]);
            $pago = $stmt->fetch();

            if (!$pago) {
                throw new Exception('Pago no encontrado o ya validado.');
            }

            $montoPago = (float)$pago['monto'];
            $montoAutorizado = (float)$monto_autorizado;

            if ($montoPago > $montoAutorizado) {
                throw new Exception('Monto excede el autorizado.');
            }                
            // 2. Actualizar tabla pagos
            $this->db->prepare("
                UPDATE pagos 
                SET estatus = 'PAGO', 
                    referencia_bancaria = :referencia,
                    validado_por = :validador,
                    fecha_validacion = NOW()
                WHERE id = :id
            ")->execute([
                'id' => $pagoId,
                'referencia' => substr($referencia, 0, 100),
                'validador' => $user['id']
            ]);

            // 3. Actualizar historial a estatus PAGO
            $this->db->prepare("
                UPDATE historial 
                SET estatus = 'PAGO' 
                WHERE id = :historial_id AND estatus = 'PAGG'
            ")->execute(['historial_id' => $pago['id_historial']]);

            // 4. Descontar saldo del cliente
            $this->db->prepare("
                UPDATE clientes 
                SET saldo = GREATEST(0, saldo - :monto),
                    estado = CASE WHEN GREATEST(0, saldo - :monto2) <= 0 THEN 'pagado' ELSE estado END
                WHERE id = :cliente_id
            ")->execute([
                'cliente_id' => $pago['id_cliente'],
                'monto' => $pago['monto'],
                'monto2' => $pago['monto']
            ]);
            // ... después de actualizar pagos e historial a 'PAGO' ...

            // 🔍 Obtener historial asociado para leer data_extras
            $stmtHist = $this->db->prepare("SELECT data_extras FROM historial WHERE id = :id");
            $stmtHist->execute(['id' => $pago['id_historial']]);
            $historial = $stmtHist->fetch();
            $extras = json_decode($historial['data_extras'] ?? '{}', true);

            $idPromesaExplicita = $extras['id_promesa_aplicada'] ?? null;

            if ($idPromesaExplicita) {
                // ✅ CASO 1: El gestor seleccionó explícitamente una promesa
                $this->db->prepare("UPDATE promesas SET estatus = 'cumplida' WHERE id = :id AND estatus = 'pendiente'")
                        ->execute(['id' => $idPromesaExplicita]);
            } else {
                // 🔄 CASO 2: Pago genérico → aplicar lógica FIFO (tu código anterior)
                $montoDisponible = floatval($pago['monto']);
                $stmtPromesas = $this->db->prepare("
                    SELECT id, monto_prometido FROM promesas 
                    WHERE id_cliente = :cliente_id AND estatus = 'pendiente' 
                    ORDER BY fecha_compromiso ASC, fecha_registro ASC
                    FOR UPDATE
                ");
                $stmtPromesas->execute(['cliente_id' => $pago['id_cliente']]);
                $promesasPendientes = $stmtPromesas->fetchAll();

                foreach ($promesasPendientes as $promesa) {
                    if ($montoDisponible <= 0) break;
                    if ($montoDisponible >= $promesa['monto_prometido']) {
                        $this->db->prepare("UPDATE promesas SET estatus = 'cumplida' WHERE id = :id")->execute(['id' => $promesa['id']]);
                        $montoDisponible -= $promesa['monto_prometido'];
                    } else {
                        $montoDisponible = 0;
                    }
                }
            }
            $this->db->commit();
            echo json_encode(['success' => true, 'message' => '✅ Pago validado correctamente']);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[LEX360] validarPago: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            file_put_contents('errores.txt','Error en Validar Pago'.$e->getMessage());

        }
        exit;
    }
    public function validaciones()
    {
        $user = $this->session->getUser();

        $id_admin = $user['id'];
        $rol = $user['role'];

        if ($rol != 'admin') {

            echo json_encode([
                'status' => 'error',
                'message' => 'Transacción no autorizada para tu perfil.'
            ]);
            exit;
        }

        $operacion = $_POST['operacion'] ?? '';

        $id_supervisor    = $_POST['id_supervisor'] ?? null;
        $monto_autorizado = $_POST['monto_autorizado'] ?? null;
        $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
        $observacion      = $_POST['observacion'] ?? '';
        $estado           = $_POST['estado'] ?? 'ACTIVA';

        switch ($operacion) {

            case 'insertar':

                $resultado = $this->clienteDao->insertValidaciones(
                    $id_supervisor,
                    $id_admin,
                    $monto_autorizado,
                    $fecha_vencimiento,
                    $observacion
                );

                break;

            case 'actualizar':

                $resultado = $this->clienteDao->updateValidaciones(
                    $id_supervisor,
                    $id_admin,
                    $monto_autorizado,
                    $fecha_vencimiento,
                    $estado,
                    $observacion
                );

                break;

            case 'eliminar':

                $resultado = $this->clienteDao->eliminarValidacion(
                    $id_supervisor
                );

                break;

            default:

                $resultado = [
                    'status' => 'error',
                    'message' => 'Operación no válida.'
                ];
        }

        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }
 public function listarValidaciones()
    {
        $user = $this->session->getUser();

        if ($user['role'] != 'admin') {
            die('Acceso denegado');
        }

        $lista_validaciones = $this->clienteDao->getValidaciones();
        $supervisores=$this->clienteDao->getSupervisoresGenerales($user['role']);

        ob_start();
 

        require_once __DIR__ . '/../views/pagos/validaciones.php';

        $viewContent = ob_get_clean();

        require_once __DIR__ . '/../views/frontend.php';
    }   

    public function guardarPago():void{
        header('Content-Type: application/json');

        // 1. Configuración de la ruta FUERA del acceso público
        // Nota: Usa barras inclinadas (/) incluso en Windows, PHP lo interpreta correctamente.
        define('BASE_UPLOAD_DIR', 'C:/pagos/');


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        // 2. Validar datos del formulario
        $id_pago = filter_input(INPUT_POST, 'id_pago', FILTER_VALIDATE_INT);
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (!$id_pago || empty($descripcion)) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            exit;
        }

        // 3. Validar archivo
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Error al recibir el archivo.']);
            exit;
        }

        $archivo = $_FILES['archivo'];
        $nombre_original = $archivo['name'];
        $tamano = $archivo['size'];
        $ruta_temporal = $archivo['tmp_name'];

        // 4. Validaciones de seguridad
        $MAX_SIZE = 5 * 1024 * 1024; // 5 MB
        $ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

        if ($tamano > $MAX_SIZE) {
            echo json_encode(['success' => false, 'message' => 'El archivo excede los 5 MB.']);
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tipo_archivo_real = finfo_file($finfo, $ruta_temporal);
        finfo_close($finfo);

        if (!in_array($tipo_archivo_real, $ALLOWED_MIME_TYPES)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido (Solo PDF, JPG, PNG).']);
            exit;
        }

        // 5. Generar nombre seguro y rutas
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION)) ?: 'bin';
        $nombre_guardado = bin2hex(random_bytes(16)) . '.' . $extension;

        // Ruta relativa para la BD (ej: "2026/06/")
        $ruta_relativa = date('Y/m') . '/'; 
        // Ruta absoluta real en el disco (ej: "C:/pagos/2026/06/")
        $ruta_absoluta = BASE_UPLOAD_DIR . $ruta_relativa; 

        // Crear directorio si no existe (0755 funciona en Windows para crear carpetas)
        if (!is_dir($ruta_absoluta)) {
            if (!mkdir($ruta_absoluta, 0755, true)) {
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio en el servidor.']);
                exit;
            }
        }

        $ruta_final_en_disco = $ruta_absoluta . $nombre_guardado;

        // 6. Transacción: Mover archivo + Guardar en BD
        $this->db->beginTransaction();
        try {
            if (!move_uploaded_file($ruta_temporal, $ruta_final_en_disco)) {
                throw new Exception('No se pudo guardar el archivo en el disco.');
            }

            $sql = "INSERT INTO pagos_documentos 
                    (id_pago, descripcion, nombre_original, nombre_guardado, ruta, tipo_archivo, tamano) 
                    VALUES (:id_pago, :descripcion, :nombre_original, :nombre_guardado, :ruta, :tipo_archivo, :tamano)";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_pago'         => $id_pago,
                ':descripcion'     => $descripcion,
                ':nombre_original' => $nombre_original,
                ':nombre_guardado' => $nombre_guardado,
                ':ruta'            => $ruta_relativa, // Se guarda "2026/06/"
                ':tipo_archivo'    => $tipo_archivo_real,
                ':tamano'          => $tamano
            ]);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Documento subido exitosamente.']);

        } catch (Exception $e) {
            $this->db->rollBack();
            if (file_exists($ruta_final_en_disco)) {
                @unlink($ruta_final_en_disco); // Limpieza de archivo huérfano
            }
            error_log("Error subida soporte: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno al guardar el documento.']);
        }
     }
     public function subirSoporte() {
        // 1. Forzar respuesta JSON
        header('Content-Type: application/json');

        // 2. Validar que sea petición POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            return;
        }

        // 3. Obtener y validar datos del formulario
        $id_pago = filter_input(INPUT_POST, 'id_pago', FILTER_VALIDATE_INT);
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (!$id_pago || empty($descripcion)) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios (ID de pago o descripción).']);
            return;
        }

        // 4. Validar que se haya enviado un archivo sin errores de PHP
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = $_FILES['archivo']['error'] ?? 'Desconocido';
            echo json_encode(['success' => false, 'message' => "Error al recibir el archivo (Código: $error_msg)."]);
            return;
        }

        $archivo = $_FILES['archivo'];
        $nombre_original = $archivo['name'];
        $tamano = $archivo['size'];
        $ruta_temporal = $archivo['tmp_name'];

        // 5. Validaciones de Seguridad del Archivo
        $MAX_SIZE = 5 * 1024 * 1024; // 5 MB en bytes
        $ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

        if ($tamano > $MAX_SIZE) {
            echo json_encode(['success' => false, 'message' => 'El archivo excede el tamaño máximo permitido de 5 MB.']);
            return;
        }

        // Validar el MIME type REAL (no confiar en la extensión del navegador)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tipo_archivo_real = finfo_file($finfo, $ruta_temporal);
        finfo_close($finfo);

        if (!in_array($tipo_archivo_real, $ALLOWED_MIME_TYPES)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se aceptan PDF, JPG o PNG.']);
            return;
        }

        // 6. Generar nombre seguro y rutas
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        // Fallback por si el archivo no tiene extensión pero el MIME es válido
        if (empty($extension)) {
            $extension = ($tipo_archivo_real === 'application/pdf') ? 'pdf' : 'jpg';
        }

        $nombre_guardado = bin2hex(random_bytes(16)) . '.' . $extension; // Ej: 8f3a9b...c2d1.pdf
        
        // Ruta relativa para la BD (ej: "2026/06/")
        $ruta_relativa = date('Y/m') . '/'; 
        // Ruta absoluta real en el disco (ej: "C:/pagos/2026/06/")
        $ruta_absoluta = 'C:/pagos/' . $ruta_relativa; 

        // Crear la carpeta si no existe (0755 funciona en Windows para crear carpetas)
        if (!is_dir($ruta_absoluta)) {
            if (!mkdir($ruta_absoluta, 0755, true)) {
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio de destino en el servidor.']);
                return;
            }
        }

        $ruta_final_en_disco = $ruta_absoluta . $nombre_guardado;

        // 7. Transacción: Mover archivo + Guardar en BD
        $this->db->beginTransaction();

        try {
            // A. Mover el archivo del temporal a su destino final
            if (!move_uploaded_file($ruta_temporal, $ruta_final_en_disco)) {
                throw new Exception('No se pudo mover el archivo al directorio de destino.');
            }

            // B. Insertar en la base de datos
            $sql = "INSERT INTO pagos_documentos 
                    (id_pago, descripcion, nombre_original, nombre_guardado, ruta, tipo_archivo, tamano) 
                    VALUES 
                    (:id_pago, :descripcion, :nombre_original, :nombre_guardado, :ruta, :tipo_archivo, :tamano)";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_pago'         => $id_pago,
                ':descripcion'     => $descripcion,
                ':nombre_original' => $nombre_original,
                ':nombre_guardado' => $nombre_guardado,
                ':ruta'            => $ruta_relativa, // Guardamos SOLO la ruta relativa
                ':tipo_archivo'    => $tipo_archivo_real,
                ':tamano'          => $tamano
            ]);

            // C. Confirmar la transacción
            $this->db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Documento subido y registrado exitosamente.'
            ]);

        } catch (Exception $e) {
            // Si algo falla, revertimos la base de datos
            $this->db->rollBack();
            
            // Y borramos el archivo del disco si se llegó a copiar (evita archivos huérfanos)
            if (isset($ruta_final_en_disco) && file_exists($ruta_final_en_disco)) {
                @unlink($ruta_final_en_disco);
            }
            
            // Loguear el error real en tu servidor (revisa tu php_error.log)
            error_log("Error al subir soporte de pago: " . $e->getMessage());
            
            echo json_encode([
                'success' => false, 
                'message' => 'Ocurrió un error interno al guardar el documento.'
            ]);
        }
    }
    public function descargarPago(){
    // 1. Iniciar sesión y validar que el usuario esté logueado
    session_start();
    if (!isset($_SESSION['user_id'])) { // Ajusta a tu lógica de autenticación
        http_response_code(403);
        die('Acceso denegado.');
    }

    // 2. Obtener el ID del documento de la URL (ej: descargar.php?id=15)
    $id_doc = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id_doc) {
        die('ID de documento inválido.');
    }

    // 3. Conexión a la BD para obtener los datos del archivo
    //Ya existe $this->db predefinida
 
    $stmt = $this->db->prepare("SELECT nombre_original, nombre_guardado, ruta, tipo_archivo, activo FROM pagos_documentos WHERE id = :id");
    $stmt->execute([':id' => $id_doc]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc || $doc['activo'] == false) {
        http_response_code(404);
        die('Documento no encontrado o eliminado.');
    }

    // 4. Construir la ruta absoluta real
    define('BASE_UPLOAD_DIR', 'C:/pagos/');
    $ruta_absoluta = BASE_UPLOAD_DIR . $doc['ruta'] . $doc['nombre_guardado'];

    // 5. Verificar que el archivo existe físicamente
    if (!file_exists($ruta_absoluta)) {
        http_response_code(404);
        die('El archivo no existe en el servidor.');
    }

    // 6. Enviar el archivo al navegador de forma segura
    // Limpiar cualquier salida previa para evitar corrupción del archivo
    if (ob_get_level()) ob_end_clean();

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $doc['tipo_archivo']);
    // Forzar descarga o visualización en navegador (inline = lo muestra, attachment = lo descarga)
    header('Content-Disposition: inline; filename="' . addslashes($doc['nombre_original']) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($ruta_absoluta));

    // Leer y enviar el archivo
    readfile($ruta_absoluta);
    exit;        
    }
    public function obtenerDocumentos(){
        header('Content-Type: application/json');
        // Tu conexión PDO (la misma que usas siempre) $this->db

        $id_pago = filter_input(INPUT_GET, 'id_pago', FILTER_VALIDATE_INT);

        if (!$id_pago) {
            echo json_encode([]);
            exit;
        }

        $stmt = $this->db->prepare("SELECT id, descripcion, nombre_original, tipo_archivo, tamano, fecha_subida 
                            FROM pagos_documentos 
                            WHERE id_pago = :id_pago AND activo = TRUE 
                            ORDER BY fecha_subida DESC");
        $stmt->execute([':id_pago' => $id_pago]);
        $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($documentos);

    }
    public function borrarImagen() {
        header('Content-Type: application/json');

        // Validar que sea POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            return;
        }

        // Obtener y validar ID
        $id_documento = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id_documento) {
            echo json_encode(['success' => false, 'message' => 'ID de documento inválido.']);
            return;
        }

        // Iniciar transacción
        $this->db->beginTransaction();

        try {
            // 1. Obtener los datos del documento (ruta y nombre_guardado)
            $stmt = $this->db->prepare("SELECT nombre_guardado, ruta, activo FROM pagos_documentos WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $id_documento]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$doc) {
                throw new Exception('Documento no encontrado.');
            }

            if ($doc['activo'] == false) {
                throw new Exception('El documento ya está eliminado.');
            }

            // 2. Soft delete: Marcar como inactivo en la base de datos
            $updateStmt = $this->db->prepare("UPDATE pagos_documentos SET activo = FALSE WHERE id = :id");
            $updateStmt->execute([':id' => $id_documento]);

            // 3. Opcional: Eliminar físicamente el archivo del disco
            // Si prefieres mantener el archivo, comenta o elimina esta sección
            $ruta_absoluta = 'C:/pagos/' . $doc['ruta'] . $doc['nombre_guardado'];
            if (file_exists($ruta_absoluta)) {
                @unlink($ruta_absoluta); // @ suprime warnings si no se puede borrar
            }

            // Confirmar transacción
            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Documento eliminado correctamente.'
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al borrar documento: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}