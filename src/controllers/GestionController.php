<?php
namespace LEX360\Controllers;

use Exception;
use PDO;
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
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        exit;
    }

    $this->session->requireAuth();
    $user = $this->session->getUser();

    $clienteId   = (int)($_POST['cliente_id'] ?? 0);
    $tipologiaId = (int)($_POST['tipologia'] ?? 0);
    $comentario  = trim($_POST['comentario'] ?? '');
    $telefono    = substr(trim($_POST['telefono_utilizado'] ?? ''), 0, 20);

    $estatusRaw = strtoupper(trim($_POST['estatus'] ?? ''));

    $estatusValidos = ['SINC', 'COMP', 'PAGG', 'PAGO'];

    $estatus = in_array($estatusRaw, $estatusValidos, true)
        ? $estatusRaw
        : 'SINC';

    if (!$clienteId || !$tipologiaId || empty($comentario)) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos'
        ]);
        exit;
    }

    try {

        $this->db->beginTransaction();

        // =====================================================
        // VALIDAR TIPOLOGÍA
        // =====================================================

        $stmtTip = $this->db->prepare("
            SELECT
                id,
                requiere_proxima_fecha,
                requiere_monto,
                estatus_default
            FROM tipologias
            WHERE id = :id
        ");

        $stmtTip->execute([
            'id' => $tipologiaId
        ]);

        $tipologia = $stmtTip->fetch();

        if (!$tipologia) {

            echo json_encode([
                'success' => false,
                'message' => 'Tipología no válida'
            ]);

            exit;
        }

        if ($estatus === 'SINC' && !empty($tipologia['estatus_default'])) {

            $estatusTmp = strtoupper(trim($tipologia['estatus_default']));

            if (in_array($estatusTmp, $estatusValidos, true)) {
                $estatus = $estatusTmp;
            }
        }

        // =====================================================
        // FECHA COMPROMISO / PRÓXIMA LLAMADA
        // =====================================================

        $fechaCompromiso = null;

        $fechaRaw = trim($_POST['fecha_compromiso'] ?? '');

        if (!empty($fechaRaw)) {

            $dt = \DateTime::createFromFormat(
                'Y-m-d\TH:i',
                $fechaRaw
            );

            if ($dt) {
                $fechaCompromiso = $dt->format('Y-m-d H:i:s');
            }
        }

        if (
            $tipologia['requiere_proxima_fecha']
            && empty($fechaCompromiso)
        ) {

            echo json_encode([
                'success' => false,
                'message' => '⚠️ Fecha próxima obligatoria'
            ]);

            exit;
        }

        // =====================================================
        // EXTRAS
        // =====================================================

        $idPromesaSeleccionada = $_POST['id_promesa_seleccionada'] ?? null;

        $extras = $this->capturarExtras($_POST);

        $extrasArr = json_decode($extras, true) ?: [];

        if ($idPromesaSeleccionada) {

            $extrasArr['id_promesa_aplicada']
                = (int)$idPromesaSeleccionada;

            $this->db
                ->prepare("
                    UPDATE promesas
                    SET estatus = 'cumplida'
                    WHERE id = :id
                ")
                ->execute([
                    'id' => $idPromesaSeleccionada
                ]);
        }

        $jsonExtras = json_encode(
            $extrasArr,
            JSON_UNESCAPED_UNICODE
        );

        // =====================================================
        // HISTORIAL
        // =====================================================

        $stmt = $this->db->prepare("
            INSERT INTO historial (
                id_cliente,
                id_usuario,
                id_tipologia,
                estatus,
                comentario,
                telefono_utilizado,
                fecha_gestion,
                fecha_proxima_llamada,
                data_extras
            )
            VALUES (
                :cli,
                :usr,
                :tip,
                :est,
                :com,
                :tel,
                NOW(),
                :fecha,
                :extras::jsonb
            )
        ");

        $stmt->execute([
            'cli'    => $clienteId,
            'usr'    => $user['id'],
            'tip'    => $tipologiaId,
            'est'    => $estatus,
            'com'    => $comentario,
            'tel'    => $telefono,
            'fecha'  => $fechaCompromiso,
            'extras' => $jsonExtras
        ]);

        $historialId = $this->db->lastInsertId();

        // =====================================================
        // PROMESAS
        // =====================================================

        if ($estatus === 'COMP') {

            $monto = floatval($_POST['monto_gestion'] ?? 0);

            if ($monto > 0) {

                $stmtPromesa = $this->db->prepare("
                    INSERT INTO promesas (
                        id_cliente,
                        id_usuario,
                        monto_prometido,
                        fecha_compromiso,
                        estatus,
                        fecha_registro,
                        id_historial
                    )
                    VALUES (
                        :c,
                        :u,
                        :m,
                        :f,
                        'pendiente',
                        NOW(),
                        :id_historial
                    )
                ");

                $stmtPromesa->execute([
                    'c' => $clienteId,
                    'u' => $user['id'],
                    'm' => $monto,
                    'f' => $fechaCompromiso,
                    'id_historial'=>$historialId
                ]);
            }
        }

        // =====================================================
        // PAGOS REPORTADOS
        // =====================================================

        elseif ($estatus === 'PAGG') {

            $monto = floatval($_POST['monto_gestion'] ?? 0);

            if ($monto > 0) {

                $stmtPago = $this->db->prepare("
                    INSERT INTO pagos (
                        id_cliente,
                        monto,
                        fecha_pago,
                        referencia_bancaria,
                        estatus,
                        validado_por,
                        fecha_validacion,
                        id_historial
                    )
                    VALUES (
                        :c,
                        :m,
                        NOW(),
                        :ref,
                        'PAGG',
                        NULL,
                        NULL,
                        :id_historial
                    )
                ");

                $stmtPago->execute([
                    'c'    => $clienteId,
                    'm'    => $monto,
                    'ref'  => substr($_POST['referencia_pago'] ?? '', 0, 100),
                    'id_historial' => $historialId
                ]);
            }
        }

        // =====================================================
        // ACTUALIZAR CLIENTE
        // =====================================================

        $this->db
            ->prepare("
                UPDATE clientes
                SET fecha_ultima_gestion = NOW()
                WHERE id = :id
            ")
            ->execute([
                'id' => $clienteId
            ]);

        $this->db->commit();

        echo json_encode([
            'success' => true,
            'message' => '✅ Gestión registrada'
        ]);

    } catch (Exception $e) {

        $this->db->rollBack();

        error_log(
            '[LEX360] registrarGestion: '
            . $e->getMessage()
        );

        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
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
        /**
     * Retorna promesas pendientes de un cliente para selección en PAGG
     */
    public function getPromesasPendientes(): void
    {
        header('Content-Type: application/json');
        $this->session->requireAuth();
        
        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        if (!$clienteId) { echo json_encode([]); exit; }

        try {
            $stmt = $this->db->prepare("
                SELECT id, monto_prometido, fecha_registro, fecha_compromiso 
                FROM promesas 
                WHERE id_cliente = :cid AND estatus = 'pendiente' 
                ORDER BY fecha_compromiso ASC, fecha_registro ASC
            ");
            $stmt->execute(['cid' => $clienteId]);
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            error_log('[LEX360] getPromesasPendientes: ' . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }
    /**
     * Retorna las últimas 5 gestiones de un cliente para el modal de historial
     */
    public function getUltimasGestiones(): void
    {
        header('Content-Type: application/json');
        $this->session->requireAuth();
        
        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        if (!$clienteId) { echo json_encode([]); exit; }

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    h.id,
                    h.fecha_gestion,
                    h.fecha_proxima_llamada,
                    h.comentario,
                    h.estatus,
                    t.nombre as tipologia,
                    u.nombre as gestor
                FROM historial h
                LEFT JOIN tipologias t ON t.id = h.id_tipologia
                LEFT JOIN usuarios u ON u.id = h.id_usuario
                WHERE h.id_cliente = :cid
                ORDER BY h.id DESC
                LIMIT 5
            ");
            $stmt->execute(['cid' => $clienteId]);
            
            $gestiones = $stmt->fetchAll();
            
            // Formatear fechas para el frontend
            foreach ($gestiones as &$g) {
                if ($g['fecha_gestion']) {
                    $g['fecha_gestion_fmt'] = date('d/m/Y H:i', strtotime($g['fecha_gestion']));
                }
                if ($g['fecha_proxima_llamada']) {
                    $g['fecha_proxima_fmt'] = date('d/m/Y H:i', strtotime($g['fecha_proxima_llamada']));
                }
            }
            
            echo json_encode($gestiones ?: []);
            
        } catch (\Throwable $e) {
            error_log('[LEX360] getUltimasGestiones: ' . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }
        /**
     * Retorna llamadas programadas dentro de la ventana ±5 minutos
     * Excluye si ya se registró una gestión posterior a la fecha programada
     */

    public function getProximasLlamadas(): void
    {
        error_reporting(0);
        header('Content-Type: application/json');
        
        try {
            $this->session->requireAuth();
            $user = $this->session->getUser();
            $userRole = $user['rol'] ?? $user['role'] ?? '';

            // ✅ FORZAR ZONA HORARIA (Ajusta si es necesario)
            $this->db->exec("SET TIME ZONE 'America/Guatemala'");

            // 📋 CONSULTA BASADA EN PROMESAS (Más limpia)
            // Busca promesas pendientes cercanas a la hora actual
            $sql = "SELECT p.id, p.id_cliente, c.nombre, p.fecha_compromiso 
                    FROM promesas p
                    JOIN clientes c ON p.id_cliente = c.id
                    WHERE p.estatus = 'pendiente'
                      AND p.fecha_compromiso BETWEEN NOW() - INTERVAL '10 minutes' 
                                                  AND NOW() + INTERVAL '1 hour'";
            
            // Opcional: Si es gestor, solo ve sus promesas
            if ($userRole === 'gestor') {
                $sql .= " AND p.id_usuario = :usuario_id";
            }
            
            $sql .= " ORDER BY p.fecha_compromiso ASC";

            $stmt = $this->db->prepare($sql);
            $params = [];
            if ($userRole === 'gestor') {
                $params['usuario_id'] = $user['id'] ?? 0;
            }
            $stmt->execute($params);
            
            $result = [];
            foreach ($stmt->fetchAll() as $row) {
                $result[] = [
                    'id' => $row['id'], // ID de la promesa
                    'cliente_id' => $row['id_cliente'],
                    'nombre' => $row['nombre'],
                    'tipologia' => '💰 Promesa de Pago', // Etiqueta fija
                    'hora' => date('H:i', strtotime($row['fecha_compromiso']))
                ];
            }
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log('[LEX360] getProximasLlamadas: ' . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }        

    public function listarUsuarios(): array
    {
        $sql = "
            SELECT
                id,
                usuario,
                nombre
            FROM usuarios
            WHERE activo = TRUE
            ORDER BY nombre
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function borrarGestiones(): void
    {
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            echo json_encode([
                'success' => false,
                'message' => 'No se recibieron gestiones'
            ]);
            return;
        }

        $ids = array_map('intval', $ids);

        try {

            $this->db->beginTransaction();

            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // Eliminar promesas
            $stmt = $this->db->prepare("
                DELETE FROM promesas
                WHERE id_historial IN ($placeholders)
            ");
            $stmt->execute($ids);

            // Eliminar pagos
            $stmt = $this->db->prepare("
                DELETE FROM pagos
                WHERE id_historial IN ($placeholders)
            ");
            $stmt->execute($ids);

            // Eliminar historial
            $stmt = $this->db->prepare("
                DELETE FROM historial
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($ids);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => count($ids) . ' gestiones eliminadas'
            ]);

        } catch (Exception $e) {

            $this->db->rollBack();

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function listarGestiones(): array
    {
        $where = [];
        $params = [];

        // Filtro usuario
        if (!empty($_GET['idUsuario'])) {
            $where[] = 'h.id_usuario = :idUsuario';
            $params['idUsuario'] = (int)$_GET['idUsuario'];
        }

        // Fechas recibidas desde datetime-local
        $fechaInicio = !empty($_GET['fechaInicio'])
            ? str_replace('T', ' ', $_GET['fechaInicio'])
            : null;

        $fechaFin = !empty($_GET['fechaFin'])
            ? str_replace('T', ' ', $_GET['fechaFin'])
            : null;

        // Si no envían fechas, mostrar únicamente hoy
        if (empty($fechaInicio) && empty($fechaFin)) {
            $fechaInicio = date('Y-m-d 00:00:00');
            $fechaFin    = date('Y-m-d 23:59:59');
        }

        // Aplicar filtros de fecha
        if (!empty($fechaInicio) && !empty($fechaFin)) {

            $where[] = 'h.fecha_gestion BETWEEN :fechaInicio AND :fechaFin';

            $params['fechaInicio'] = $fechaInicio;
            $params['fechaFin'] = $fechaFin;

        } else {

            if (!empty($fechaInicio)) {
                $where[] = 'h.fecha_gestion >= :fechaInicio';
                $params['fechaInicio'] = $fechaInicio;
            }

            if (!empty($fechaFin)) {
                $where[] = 'h.fecha_gestion <= :fechaFin';
                $params['fechaFin'] = $fechaFin;
            }
        }

        $sqlWhere = '';

        if (!empty($where)) {
            $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = "
            SELECT
                h.id,
                h.id_cliente,
                h.id_usuario,
                u.usuario,
                u.nombre AS nombre_usuario,
                h.fecha_gestion,
                h.estatus,
                h.telefono_utilizado,
                h.id_tipologia,
                t.nombre AS tipologia,
                h.comentario,
                h.fecha_proxima_llamada,

                (
                    SELECT COUNT(*)
                    FROM promesas p
                    WHERE p.id_historial = h.id
                ) AS total_promesas,

                (
                    SELECT COUNT(*)
                    FROM pagos pg
                    WHERE pg.id_historial = h.id
                ) AS total_pagos

            FROM historial h

            LEFT JOIN usuarios u
                ON u.id = h.id_usuario

            LEFT JOIN tipologias t
                ON t.id = h.id_tipologia

            $sqlWhere

            ORDER BY h.fecha_gestion DESC
            LIMIT 1000
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function borradoGestiones(): void
    {
        if(!in_array($_SESSION["role"]??"",['admin'])){
            die("Operación no autorizada para su perfil");
        }
        $gestiones = $this->listarGestiones();
        $usuarios  = $this->listarUsuarios();

        ob_start();
        require_once __DIR__ . '/../views/borrado/index.php';
        $viewContent = ob_get_clean();
        
        // 5. Renderizar layout maestro
        require_once __DIR__ . '/../views/frontend.php';

    }
}