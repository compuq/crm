<?php

namespace LEX360\Controllers;

use LEX360\Core\Controller;

class ReporteController extends Controller
{

    // =====================================================
    // 📊 REPORTE GENERAL DE GESTIONES
    // =====================================================
    public function generarReporte(): void
    {
        $this->session->requireAuth();

        $user = $this->session->getUser();
        $rol = $user['rol'] ?? $user['role'] ?? '';

        $pageTitle = "Reporte de Gestiones | LEX 360";

        $filters = [
            'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
            'fecha_fin'    => $_GET['fecha_fin'] ?? '',
            'supervisor_id'=> ($rol === 'admin' || $rol === 'supervisor_general')
                                ? ($_GET['supervisor_id'] ?? '')
                                : '',
            'usuario_id'   => in_array($rol, ['admin', 'supervisor', 'supervisor_general'])
                                ? ($_GET['usuario_id'] ?? '')
                                : '',
            'cartera_id'   => in_array($rol, ['admin', 'supervisor'])
                                ? ($_GET['cartera_id'] ?? '')
                                : '',
        ];

        $sql = "
            SELECT
                h.id,
                h.fecha_gestion,
                h.estatus,
                h.comentario,
                h.fecha_proxima_llamada,

                c.nombre as cliente_nombre,
                c.cuenta,
                c.identificacion,

                u.nombre as gestor_nombre,

                t.nombre as tipologia_nombre,

                CASE
                    WHEN h.estatus IN ('PAGO', 'PAGG') THEN p.monto
                    WHEN h.estatus = 'COMP' THEN pr.monto_prometido
                    ELSE NULL
                END as monto_reporte

            FROM historial h

            JOIN clientes c
                ON c.id = h.id_cliente

            JOIN usuarios u
                ON u.id = h.id_usuario

            LEFT JOIN tipologias t
                ON t.id = h.id_tipologia

            LEFT JOIN pagos p
                ON p.id_historial = h.id

            LEFT JOIN promesas pr
                ON pr.id_historial = h.id

            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['fecha_inicio'])) {
            $sql .= " AND DATE(h.fecha_gestion) >= :fecha_inicio ";
            $params['fecha_inicio'] = $filters['fecha_inicio'];
        }

        if (!empty($filters['fecha_fin'])) {
            $sql .= " AND DATE(h.fecha_gestion) <= :fecha_fin ";
            $params['fecha_fin'] = $filters['fecha_fin'];
        }

        if (!empty($filters['supervisor_id'])) {
            $sql .= " AND c.id_supervisor_cadena = :supervisor_id ";
            $params['supervisor_id'] = (int)$filters['supervisor_id'];
        }

        if (!empty($filters['usuario_id'])) {
            $sql .= " AND h.id_usuario = :usuario_id ";
            $params['usuario_id'] = (int)$filters['usuario_id'];
        }

        if (!empty($filters['cartera_id'])) {
            $sql .= " AND c.id_cartera = :cartera_id ";
            $params['cartera_id'] = (int)$filters['cartera_id'];
        }

        // 🔒 Seguridad
        if ($rol === 'supervisor') {

            $sql .= " AND c.id_supervisor_cadena = :mi_supervisor ";
            $params['mi_supervisor'] = $user['id'];

        } elseif ($rol === 'gestor') {

            $sql .= " AND h.id_usuario = :mi_usuario ";
            $params['mi_usuario'] = $user['id'];
        }

        $sql .= " ORDER BY h.id DESC LIMIT 500 ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $gestiones = $stmt->fetchAll();

        // Dropdowns
        $usuarios = $this->db
            ->query("
                SELECT id, nombre
                FROM usuarios
                WHERE rol = 'gestor'
                AND activo = true
                ORDER BY nombre
            ")
            ->fetchAll();

        $carteras = $this->db
            ->query("
                SELECT id, nombre_cartera AS nombre
                FROM carteras
                WHERE activa = true
                ORDER BY nombre_cartera
            ")
            ->fetchAll();

        $supervisores = [];

        if ($rol === 'admin' || $rol === 'supervisor_general') {

            $supervisores = $this->db
                ->query("
                    SELECT id, nombre
                    FROM usuarios
                    WHERE rol IN ('supervisor','supervisor_general')
                    AND activo = true
                    ORDER BY nombre
                ")
                ->fetchAll();
        }

        $viewData = compact(
            'rol',
            'filters',
            'gestiones',
            'usuarios',
            'carteras',
            'supervisores',
            'pageTitle'
        );

        extract($viewData);

        ob_start();

        require_once __DIR__ . '/../views/reportes/gestiones.php';

        $viewContent = ob_get_clean();

        require_once __DIR__ . '/../views/frontend.php';
    }

    // =====================================================
    // 📥 EXPORTAR GESTIONES EXCEL
    // =====================================================
    public function exportarGestionesExcel(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $this->session->requireAuth();

        $user = $this->session->getUser();
        $rol = $user['rol'] ?? $user['role'] ?? '';

        $filters = [
            'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
            'fecha_fin'    => $_GET['fecha_fin'] ?? '',
            'supervisor_id'=> $_GET['supervisor_id'] ?? '',
            'usuario_id'   => $_GET['usuario_id'] ?? '',
            'cartera_id'   => $_GET['cartera_id'] ?? '',
        ];

        try {

            $sql = "
                SELECT
                    h.id,
                    h.fecha_gestion,
                    h.estatus,
                    h.comentario,
                    h.fecha_proxima_llamada,

                    c.nombre as cliente_nombre,
                    c.cuenta,
                    c.identificacion,

                    u.nombre as gestor_nombre,

                    t.nombre as tipologia_nombre,

                    CASE
                        WHEN h.estatus IN ('PAGO', 'PAGG') THEN p.monto
                        WHEN h.estatus = 'COMP' THEN pr.monto_prometido
                        ELSE NULL
                    END as monto_reporte

                FROM historial h

                JOIN clientes c
                    ON c.id = h.id_cliente

                JOIN usuarios u
                    ON u.id = h.id_usuario

                LEFT JOIN tipologias t
                    ON t.id = h.id_tipologia

                LEFT JOIN pagos p
                    ON p.id_historial = h.id

                LEFT JOIN promesas pr
                    ON pr.id_historial = h.id

                WHERE 1=1
            ";

            $params = [];

            if (!empty($filters['fecha_inicio'])) {
                $sql .= " AND DATE(h.fecha_gestion) >= :fecha_inicio ";
                $params['fecha_inicio'] = $filters['fecha_inicio'];
            }

            if (!empty($filters['fecha_fin'])) {
                $sql .= " AND DATE(h.fecha_gestion) <= :fecha_fin ";
                $params['fecha_fin'] = $filters['fecha_fin'];
            }

            if (!empty($filters['supervisor_id'])) {
                $sql .= " AND c.id_supervisor_cadena = :supervisor_id ";
                $params['supervisor_id'] = (int)$filters['supervisor_id'];
            }

            if (!empty($filters['usuario_id'])) {
                $sql .= " AND h.id_usuario = :usuario_id ";
                $params['usuario_id'] = (int)$filters['usuario_id'];
            }

            if (!empty($filters['cartera_id'])) {
                $sql .= " AND c.id_cartera = :cartera_id ";
                $params['cartera_id'] = (int)$filters['cartera_id'];
            }

            // 🔒 Seguridad
            if ($rol === 'supervisor') {

                $sql .= " AND c.id_supervisor_cadena = :mi_supervisor ";
                $params['mi_supervisor'] = $user['id'];

            } elseif ($rol === 'gestor') {

                $sql .= " AND h.id_usuario = :mi_usuario ";
                $params['mi_usuario'] = $user['id'];
            }

            $sql .= " ORDER BY h.fecha_gestion DESC ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute($params);

            $datos = $stmt->fetchAll();

            $excel = new \LEX360\Models\Services\ExcelService();

            $excel->exportarXlsx(
                $datos,
                'Reporte_Gestiones_' . date('Y-m-d'),
                [
                    'fecha_gestion' => [
                        'formato' => 'date',
                        'ancho' => 18
                    ],
                    'fecha_proxima_llamada' => [
                        'formato' => 'date',
                        'ancho' => 18
                    ]
                ]
            );

        } catch (\Throwable $e) {

            header('Content-Type: text/plain');

            echo "ERROR EXPORTANDO GESTIONES:\n\n";
            echo $e->getMessage();

            exit;
        }
    }

    // =====================================================
    // 📊 VER PAGOS
    // =====================================================

    public function verPagos(): void
    {
        $this->session->requireAuth();

        $user = $this->session->getUser();
        $rol = $user['rol'] ?? $user['role'] ?? '';

        $pageTitle = "Reporte de Pagos | LEX 360";

        // =========================
        // FILTROS
        // =========================
        $filters = [
            'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
            'fecha_fin'    => $_GET['fecha_fin'] ?? '',
            'estatus_pago' => $_GET['estatus_pago'] ?? 'ambos',
        ];

        // =========================
        // WHERE
        // =========================
        $where = " WHERE UPPER(TRIM(h.estatus)) IN ('PAGG','PAGO') ";
        $params = [];

        if (!empty($filters['fecha_inicio'])) {
            $where .= " AND DATE(h.fecha_gestion) >= :fecha_inicio ";
            $params['fecha_inicio'] = $filters['fecha_inicio'];
        }

        if (!empty($filters['fecha_fin'])) {
            $where .= " AND DATE(h.fecha_gestion) <= :fecha_fin ";
            $params['fecha_fin'] = $filters['fecha_fin'];
        }

        if ($filters['estatus_pago'] !== 'ambos') {
            $where .= " AND UPPER(TRIM(h.estatus)) = :estatus_pago ";
            $params['estatus_pago'] = strtoupper(trim($filters['estatus_pago']));
        }

        // =========================
        // SEGURIDAD POR ROL
        // =========================
        if ($rol === 'supervisor') {

            $where .= " AND c.id_supervisor_cadena = :mi_supervisor ";
            $params['mi_supervisor'] = $user['id'];

        } elseif ($rol === 'gestor') {

            $where .= " AND h.id_usuario = :mi_usuario ";
            $params['mi_usuario'] = $user['id'];
        }

        // =========================
        // PAGINACIÓN
        // =========================
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // =========================
        // COUNT
        // =========================
        $countSql = "
            SELECT COUNT(*)
            FROM historial h
            JOIN clientes c ON c.id = h.id_cliente
            LEFT JOIN pagos p ON p.id_historial = h.id
            $where
        ";

        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);

        $total = (int)$stmtCount->fetchColumn();
        $totalPages = max(1, ceil($total / $perPage));

        // =========================
        // QUERY PRINCIPAL
        // =========================
        $sql = "
            SELECT
                h.id,
                h.fecha_gestion,
                h.estatus,
                COALESCE(p.monto, 0) AS monto,
                h.comentario,

                c.nombre AS nombre,
                c.cuenta,
                c.identificacion,

                u.nombre AS gestor

            FROM historial h

            JOIN clientes c
                ON c.id = h.id_cliente

            JOIN usuarios u
                ON u.id = h.id_usuario

            LEFT JOIN pagos p
                ON p.id_historial = h.id

            $where

            ORDER BY h.fecha_gestion DESC

            LIMIT $perPage OFFSET $offset
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $pagos = $stmt->fetchAll();

        // DEBUG
        /*
        echo '<pre>';
        print_r($pagos);
        exit;
        */

        // =========================
        // DROPDOWNS
        // =========================
        $usuarios = [];
        $carteras = [];

        // =========================
        // VIEW
        // =========================
        $viewData = compact(
            'rol',
            'filters',
            'pagos',
            'page',
            'totalPages',
            'total',
            'pageTitle',
            'usuarios',
            'carteras'
        );

        extract($viewData);

        ob_start();

        require_once __DIR__ . '/../views/reportes/pagos.php';

        $viewContent = ob_get_clean();

        require_once __DIR__ . '/../views/frontend.php';
    }

    // =====================================================
    // 💰 VER PROMESAS
    // =====================================================
    public function verPromesas(): void
    {
        $this->session->requireAuth();

        $user = $this->session->getUser();

        $rol = $user['rol'] ?? $user['role'] ?? '';

        $pageTitle = "Reporte de Promesas y Seguimiento | LEX 360";

        $filters = [
            'fecha_inicio'    => $_GET['fecha_inicio'] ?? '',
            'fecha_fin'       => $_GET['fecha_fin'] ?? '',
            'usuario_id'      => in_array($rol, ['admin', 'supervisor', 'supervisor_general'])
                                    ? ($_GET['usuario_id'] ?? '')
                                    : '',
            'cartera_id'      => in_array($rol, ['admin', 'supervisor'])
                                    ? ($_GET['cartera_id'] ?? '')
                                    : '',
            'estatus_promesa' => $_GET['estatus_promesa'] ?? 'pendiente',
            'llamadas'        => $_GET['llamadas'] ?? 'todas',
        ];

        // =====================================================
        // PAGINACIÓN
        // =====================================================

        $page = max(1, (int)($_GET['page'] ?? 1));

        $perPage = 20;

        $offset = ($page - 1) * $perPage;

        // =====================================================
        // WHERE DINÁMICO
        // =====================================================

        $where = " WHERE 1=1 ";
        $params = [];

        if (!empty($filters['fecha_inicio'])) {

            $where .= " AND DATE(p.fecha_compromiso) >= :fecha_inicio ";

            $params['fecha_inicio'] = $filters['fecha_inicio'];
        }

        if (!empty($filters['fecha_fin'])) {

            $where .= " AND DATE(p.fecha_compromiso) <= :fecha_fin ";

            $params['fecha_fin'] = $filters['fecha_fin'];
        }

        if (!empty($filters['usuario_id'])) {

            $where .= " AND p.id_usuario = :usuario_id ";

            $params['usuario_id'] = (int)$filters['usuario_id'];
        }

        if (!empty($filters['cartera_id'])) {

            $where .= " AND c.id_cartera = :cartera_id ";

            $params['cartera_id'] = (int)$filters['cartera_id'];
        }

        if ($filters['estatus_promesa'] !== 'ambas') {

            $where .= " AND LOWER(TRIM(p.estatus)) = :estatus_promesa ";

            $params['estatus_promesa'] = strtolower(trim($filters['estatus_promesa']));
        }

        // =====================================================
        // SEGURIDAD POR ROL
        // =====================================================

        if ($rol === 'supervisor') {

            $where .= " AND c.id_supervisor_cadena = :mi_supervisor ";

            $params['mi_supervisor'] = $user['id'];

        } elseif ($rol === 'gestor') {

            $where .= " AND p.id_usuario = :mi_usuario ";

            $params['mi_usuario'] = $user['id'];
        }

        // =====================================================
        // COUNT TOTAL
        // =====================================================

        $countSql = "
            SELECT COUNT(*)
            FROM promesas p
            JOIN clientes c
                ON c.id = p.id_cliente
            $where
        ";

        $stmtCount = $this->db->prepare($countSql);

        $stmtCount->execute($params);

        $total = $stmtCount->fetchColumn();

        $totalPages = ceil($total / $perPage);

        // =====================================================
        // QUERY PRINCIPAL
        // =====================================================

        $sql = "
            SELECT
                p.id,
                p.fecha_registro,
                p.estatus,
                p.monto_prometido,
                p.fecha_compromiso,
                p.fecha_registro,

                c.nombre,
                c.cuenta,
                c.identificacion,

                u.nombre as gestor,
                CASE
                    WHEN p.estatus = 'cumplida'  THEN 0
                    WHEN p.estatus = 'pendiente' THEN 
                    (
                        SELECT COUNT(*)
                        FROM historial h2
                        WHERE h2.id_cliente = p.id_cliente
                        AND h2.id > p.id_historial
                    ) 
                    ELSE null
                    END as llamadas_seguimiento

            FROM promesas p

            JOIN clientes c
                ON c.id = p.id_cliente

            LEFT JOIN usuarios u
                ON u.id = p.id_usuario

            $where
        ";

        // =====================================================
        // FILTRO DE LLAMADAS
        // =====================================================

        if ($filters['llamadas'] !== 'todas') {

            if ($filters['llamadas'] === '5+') {

                $sql .= "
                    AND (
                        SELECT COUNT(*)
                        FROM historial h2
                        WHERE h2.id_cliente = p.id_cliente
                        AND h2.id > p.id_historial
                    ) >= 5
                ";

            } else {

                $sql .= "
                    AND (
                        SELECT COUNT(*)
                        FROM historial h2
                        WHERE h2.id_cliente = p.id_cliente
                        AND h2.fecha_gestion > p.fecha_compromiso
                    ) = :llamadas
                ";

                $params['llamadas'] = (int)$filters['llamadas'];
            }
        }

        $sql .= "
            ORDER BY p.id DESC
            LIMIT $perPage OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        $promesas = $stmt->fetchAll();

        // =====================================================
        // DROPDOWNS
        // =====================================================

        $usuarios = $this->db
            ->query("
                SELECT id, nombre
                FROM usuarios
                WHERE rol = 'gestor'
                AND activo = true
                ORDER BY nombre
            ")
            ->fetchAll();

        $carteras = $this->db
            ->query("
                SELECT id, nombre_cartera AS nombre
                FROM carteras
                WHERE activa = true
                ORDER BY nombre_cartera
            ")
            ->fetchAll();

        // =====================================================
        // VIEW
        // =====================================================

        $viewData = compact(
            'rol',
            'filters',
            'promesas',
            'page',
            'totalPages',
            'total',
            'pageTitle',
            'usuarios',
            'carteras'
        );

        extract($viewData);

        ob_start();

        require_once __DIR__ . '/../views/reportes/promesas.php';

        $viewContent = ob_get_clean();

        require_once __DIR__ . '/../views/frontend.php';
    }
        // =====================================================
    // 📥 EXPORTAR PAGOS EXCEL
    // =====================================================
    public function exportarPagosExcel(): void
    {
        // Limpiar buffers para descarga limpia
        while (ob_get_level()) {
            ob_end_clean();
        }

        $this->session->requireAuth();

        $user = $this->session->getUser();
        $rol = $user['rol'] ?? $user['role'] ?? '';

        // =========================
        // FILTROS (mismos que verPagos)
        // =========================
        $filters = [
            'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
            'fecha_fin'    => $_GET['fecha_fin'] ?? '',
            'estatus_pago' => $_GET['estatus_pago'] ?? 'ambos',
        ];

        try {
            // =========================
            // WHERE DINÁMICO
            // =========================
            $where = " WHERE UPPER(TRIM(h.estatus)) IN ('PAGG','PAGO') ";
            $params = [];

            if (!empty($filters['fecha_inicio'])) {
                $where .= " AND DATE(h.fecha_gestion) >= :fecha_inicio ";
                $params['fecha_inicio'] = $filters['fecha_inicio'];
            }

            if (!empty($filters['fecha_fin'])) {
                $where .= " AND DATE(h.fecha_gestion) <= :fecha_fin ";
                $params['fecha_fin'] = $filters['fecha_fin'];
            }

            if ($filters['estatus_pago'] !== 'ambos') {
                $where .= " AND UPPER(TRIM(h.estatus)) = :estatus_pago ";
                $params['estatus_pago'] = strtoupper(trim($filters['estatus_pago']));
            }

            // =========================
            // SEGURIDAD POR ROL
            // =========================
            if ($rol === 'supervisor') {
                $where .= " AND c.id_supervisor_cadena = :mi_supervisor ";
                $params['mi_supervisor'] = $user['id'];
            } elseif ($rol === 'gestor') {
                $where .= " AND h.id_usuario = :mi_usuario ";
                $params['mi_usuario'] = $user['id'];
            }

            // =========================
            // QUERY SIN LÍMITE (exportar TODO)
            // =========================
            $sql = "
                SELECT
                    h.fecha_gestion,
                    h.estatus,
                    COALESCE(p.monto, 0) AS monto,
                    h.comentario,
                    c.nombre AS cliente,
                    c.cuenta,
                    c.identificacion,
                    u.nombre AS gestor

                FROM historial h

                JOIN clientes c
                    ON c.id = h.id_cliente

                JOIN usuarios u
                    ON u.id = h.id_usuario

                LEFT JOIN pagos p
                    ON p.id_historial = h.id

                $where

                ORDER BY h.fecha_gestion DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            // =========================
            // EXPORTAR CON ExcelService
            // =========================
            $excel = new \LEX360\Models\Services\ExcelService();

            $excel->exportarXlsx(
                $datos,
                'Reporte_Pagos_' . date('Y-m-d'),
                [
                    'fecha_gestion' => [
                        'formato' => 'date',
                        'ancho' => 18
                    ],
                    'monto' => [
                        'formato' => 'currency',
                        'ancho' => 15
                    ]
                ]
            );

        } catch (\Throwable $e) {
            header('Content-Type: text/plain');
            echo "ERROR EXPORTANDO PAGOS:\n\n";
            echo $e->getMessage();
            exit;
        }
    }
    // =====================================================
    // 📥 EXPORTAR PROMESAS EXCEL
    // =====================================================
    public function exportarPromesasExcel(): void
    {
        // Limpiar buffers para descarga limpia
        while (ob_get_level()) {
            ob_end_clean();
        }

        $this->session->requireAuth();

        $user = $this->session->getUser();
        $rol = $user['rol'] ?? $user['role'] ?? '';

        // =========================
        // FILTROS (mismos que verPromesas)
        // =========================
        $filters = [
            'fecha_inicio'    => $_GET['fecha_inicio'] ?? '',
            'fecha_fin'       => $_GET['fecha_fin'] ?? '',
            'usuario_id'      => $_GET['usuario_id'] ?? '',
            'cartera_id'      => $_GET['cartera_id'] ?? '',
            'estatus_promesa' => $_GET['estatus_promesa'] ?? 'pendiente',
            'llamadas'        => $_GET['llamadas'] ?? 'todas',
        ];

        try {
            // =========================
            // WHERE DINÁMICO BASE
            // =========================
            $where = " WHERE 1=1 ";
            $params = [];

            if (!empty($filters['fecha_inicio'])) {
                $where .= " AND DATE(p.fecha_compromiso) >= :fecha_inicio ";
                $params['fecha_inicio'] = $filters['fecha_inicio'];
            }

            if (!empty($filters['fecha_fin'])) {
                $where .= " AND DATE(p.fecha_compromiso) <= :fecha_fin ";
                $params['fecha_fin'] = $filters['fecha_fin'];
            }

            if (!empty($filters['usuario_id'])) {
                $where .= " AND p.id_usuario = :usuario_id ";
                $params['usuario_id'] = (int)$filters['usuario_id'];
            }

            if (!empty($filters['cartera_id'])) {
                $where .= " AND c.id_cartera = :cartera_id ";
                $params['cartera_id'] = (int)$filters['cartera_id'];
            }

            if ($filters['estatus_promesa'] !== 'ambas') {
                $where .= " AND LOWER(TRIM(p.estatus)) = :estatus_promesa ";
                $params['estatus_promesa'] = strtolower(trim($filters['estatus_promesa']));
            }

            // =========================
            // SEGURIDAD POR ROL
            // =========================
            if ($rol === 'supervisor') {
                $where .= " AND c.id_supervisor_cadena = :mi_supervisor ";
                $params['mi_supervisor'] = $user['id'];
            } elseif ($rol === 'gestor') {
                $where .= " AND p.id_usuario = :mi_usuario ";
                $params['mi_usuario'] = $user['id'];
            }

            // =========================
            // SUBCONSULTA PARA CONTAR LLAMADAS DE SEGUIMIENTO
            // =========================
            $subqueryLlamadas = "
                (SELECT COUNT(*)
                 FROM historial h2
                 WHERE h2.id_cliente = p.id_cliente
                 AND h2.id > COALESCE(p.id_historial, 0))
            ";

            // =========================
            // QUERY PRINCIPAL (SIN LIMIT - EXPORTAR TODO)
            // =========================
            $sql = "
                SELECT
                    p.fecha_registro,
                    p.estatus,
                    p.monto_prometido,
                    p.fecha_compromiso,
                    c.nombre AS cliente,
                    c.cuenta,
                    c.identificacion,
                    u.nombre AS gestor,
                    $subqueryLlamadas AS llamadas_seguimiento

                FROM promesas p

                JOIN clientes c
                    ON c.id = p.id_cliente

                LEFT JOIN usuarios u
                    ON u.id = p.id_usuario

                $where
            ";

            // =========================
            // FILTRO DE LLAMADAS DE SEGUIMIENTO
            // =========================
            if ($filters['llamadas'] !== 'todas') {
                if ($filters['llamadas'] === '5+') {
                    $sql .= " AND $subqueryLlamadas >= 5 ";
                } else {
                    $sql .= " AND $subqueryLlamadas = :llamadas ";
                    $params['llamadas'] = (int)$filters['llamadas'];
                }
            }

            $sql .= " ORDER BY p.id DESC ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            // =========================
            // EXPORTAR CON ExcelService
            // =========================
            $excel = new \LEX360\Models\Services\ExcelService();

            $excel->exportarXlsx(
                $datos,
                'Reporte_Promesas_' . date('Y-m-d'),
                [
                    'fecha_registro' => [
                        'formato' => 'date',
                        'ancho' => 18
                    ],
                    'fecha_compromiso' => [
                        'formato' => 'date',
                        'ancho' => 18
                    ],
                    'monto_prometido' => [
                        'formato' => 'currency',
                        'ancho' => 15
                    ],
                    'llamadas_seguimiento' => [
                        'formato' => 'number',
                        'ancho' => 12
                    ]
                ]
            );

        } catch (\Throwable $e) {
            header('Content-Type: text/plain');
            echo "ERROR EXPORTANDO PROMESAS:\n\n";
            echo $e->getMessage();
            exit;
        }
    }    
}