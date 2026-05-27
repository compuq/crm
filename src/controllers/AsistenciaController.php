<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use PDO;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use DateTime;
class AsistenciaController extends Controller
{
    public function index(): void
    {
        $this->session->requireAuth();

        $user = $this->session->getUser();

        // ÚLTIMO MOVIMIENTO DEL DÍA
        $stmt = $this->db->prepare("
            SELECT id, usuario_id, tipo_movimiento, motivo, comentario, fecha_hora, created_at
            FROM asistencia_movimientos
            WHERE usuario_id = :uid
            AND DATE(fecha_hora) = DATE(NOW())
            ORDER BY fecha_hora DESC
            LIMIT 1
        ");

        $stmt->execute([
            'uid' => $user['id']
        ]);

        $ultimoMovimiento = $stmt->fetch(PDO::FETCH_ASSOC);

        // MOVIMIENTOS DEL DÍA
        $stmt = $this->db->prepare("
            SELECT id, usuario_id, tipo_movimiento, motivo, comentario, fecha_hora, created_at
            FROM asistencia_movimientos
            WHERE usuario_id = :uid
            AND DATE(fecha_hora) = DATE(NOW())
            ORDER BY id desc
        ");

        $stmt->execute([
            'uid' => $user['id']
        ]);

        $movimientosHoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Asistencia | LEX 360';
        
        ob_start();
        require_once __DIR__ . '/../views/asistencia/index.php';
        $viewContent = ob_get_clean();

        require_once __DIR__ . '/../views/frontend.php';
    }

    public function registrarMovimiento(): void
    {
        header('Content-Type: application/json');

        try {

            $this->session->requireAuth();

            $user = $this->session->getUser();

            $tipo = trim($_POST['tipo_movimiento'] ?? '');
            $motivo = trim($_POST['motivo'] ?? '');
            $comentario = trim($_POST['comentario'] ?? '');

            // VALIDACIONES BÁSICAS
            $tiposValidos = ['entrada', 'salida'];

            $motivosValidos = [
                'laboral',
                'almuerzo',
                'refaccion',
                'permiso',
                'otro'
            ];

            if (!in_array($tipo, $tiposValidos)) {
                throw new Exception('Tipo de movimiento inválido.');
            }

            if (!in_array($motivo, $motivosValidos)) {
                throw new Exception('Motivo inválido.');
            }

            // COMENTARIO OBLIGATORIO
            if (
                in_array($motivo, ['permiso', 'otro'])
                && empty($comentario)
            ) {
                throw new Exception('El comentario es obligatorio para este tipo de movimiento.');
            }

            // OBTENER ÚLTIMO MOVIMIENTO DEL DÍA
            $stmt = $this->db->prepare("
                SELECT id, usuario_id, tipo_movimiento, motivo, comentario, fecha_hora, created_at
                FROM asistencia_movimientos
                WHERE usuario_id = :uid
                AND DATE(fecha_hora) = DATE(NOW())
                ORDER BY fecha_hora DESC
                LIMIT 1
            ");

            $stmt->execute([
                'uid' => $user['id']
            ]);

            $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ultimo){
                $ultimo=[]; 
            }
            // VALIDAR FLUJO
            try{
                $this->validarFlujoMovimiento(
                    $ultimo,
                    $tipo,
                    $motivo
                );
            }catch(Exception $e){
                ?>
                <script>
                    console.log('Error en validarFlujoMovimiento'+<?php $e->getMessage()?>)
                </script>
                <?php
            }
            // INSERTAR MOVIMIENTO
            $stmt = $this->db->prepare("
                INSERT INTO asistencia_movimientos (
                    usuario_id,
                    tipo_movimiento,
                    motivo,
                    comentario,
                    fecha_hora
                ) VALUES (
                    :usuario_id,
                    :tipo_movimiento,
                    :motivo,
                    :comentario,
                    NOW()
                )
            ");

            $stmt->execute([
                'usuario_id' => $user['id'],
                'tipo_movimiento' => $tipo,
                'motivo' => $motivo,
                'comentario' => !empty($comentario)
                    ? $comentario
                    : null
            ]);

            echo json_encode([
                'success' => true,
                'msg' => 'Movimiento registrado correctamente.'
            ]);

        } catch (Exception $e) {

            echo json_encode([
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * VALIDAR TRANSICIONES DE MOVIMIENTOS
     */
    private function validarFlujoMovimiento(
        ?array $ultimo,
        string $tipo,
        string $motivo
    ): void {

        // SIN MOVIMIENTOS HOY
        if (!$ultimo) {

            if (
                $tipo !== 'entrada'
                || $motivo !== 'laboral'
            ) {
                throw new Exception(
                    'El primer movimiento del día debe ser Entrada Laboral.'
                );
            }

            return;
        }

        $ultimoTipo = $ultimo['tipo_movimiento'];
        $ultimoMotivo = $ultimo['motivo'];

        // SI EL ÚLTIMO FUE ENTRADA
        if ($ultimoTipo === 'entrada') {

            // SOLO PERMITIR SALIDAS
            if ($tipo !== 'salida') {
                throw new Exception(
                    'No puedes registrar dos entradas consecutivas.'
                );
            }

            return;
        }

        // SI EL ÚLTIMO FUE SALIDA
        if ($ultimoTipo === 'salida') {

            // SI YA SALIÓ LABORALMENTE
            if ($ultimoMotivo === 'laboral') {
                throw new Exception(
                    'La jornada ya fue finalizada.'
                );
            }

            // SOLO PERMITIR ENTRADA
            if ($tipo !== 'entrada') {
                throw new Exception(
                    'Debes registrar una entrada antes de otra salida.'
                );
            }

            // DEBE REGRESAR DEL MISMO MOTIVO
            if ($motivo !== $ultimoMotivo) {
                throw new Exception(
                    'Debes regresar del mismo tipo de salida registrada.'
                );
            }
        }
    }

    /**
     * REPORTES DE ASISTENCIA
     */
public function reportes(): void
{
    $this->session->requireAuth();

    $user = $this->session->getUser();

    if (!in_array($user['role'], [
        'admin',
        'supervisor_general',
        'supervisor'
    ])) {
        die('No autorizado');
    }

    $usuarios = [];

    // ADMIN Y SUPERVISOR GENERAL VEN TODO
    if (in_array($user['role'], [
        'admin',
        'supervisor_general'
    ])) {

        $stmt = $this->db->query("
            SELECT id, nombre, usuario, rol
            FROM usuarios
            WHERE activo = true
            ORDER BY nombre ASC
        ");

        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // SUPERVISOR SOLO VE SUS GESTORES
    if ($user['role'] === 'supervisor') {

        $stmt = $this->db->prepare("
            SELECT id, nombre, usuario, rol
            FROM usuarios
            WHERE activo = true
            AND supervisor_id = :supervisor_id
            ORDER BY nombre ASC
        ");

        $stmt->execute([
            'supervisor_id' => $user['id']
        ]);

        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

    $usuarioId = $_GET['usuario_id'] ?? '';

    $fechaInicio = $_GET['fecha_inicio']
        ?? date('Y-m-01');

    $fechaFin = $_GET['fecha_fin']
        ?? date('Y-m-d');

    /*
    |--------------------------------------------------------------------------
    | CONSULTA MOVIMIENTOS
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            am.*,
            u.nombre,
            u.usuario,
            u.rol
        FROM asistencia_movimientos am
        INNER JOIN usuarios u
            ON u.id = am.usuario_id
    ";

    $where = [];
    $params = [];

    // FILTRO FECHAS
    $where[] = "
        DATE(am.fecha_hora)
        BETWEEN :fecha_inicio AND :fecha_fin
    ";

    $params['fecha_inicio'] = $fechaInicio;
    $params['fecha_fin'] = $fechaFin;

    // FILTRO USUARIO
    if (!empty($usuarioId)) {

        $where[] = "
            am.usuario_id = :usuario_id
        ";

        $params['usuario_id'] = $usuarioId;
    }

    // SUPERVISOR SOLO VE SUS GESTORES
    if ($user['role'] === 'supervisor') {

        $where[] = "
            u.supervisor_id = :supervisor_id
        ";

        $where[] = "
            u.activo = true
        ";

        $params['supervisor_id'] = $user['id'];
    }

    // ARMAR WHERE
    if (!empty($where)) {

        $sql .= "
            WHERE " . implode(' AND ', $where);
    }

    // ORDEN
    $sql .= "
        ORDER BY am.fecha_hora DESC
    ";

    // EJECUTAR
    $stmt = $this->db->prepare($sql);

    $stmt->execute($params);

    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | VISTA
    |--------------------------------------------------------------------------
    */

    $pageTitle = 'Reportes de Asistencia | LEX 360';

    ob_start();

    require_once __DIR__ . '/../views/asistencia/reportes.php';

    $viewContent = ob_get_clean();

    require_once __DIR__ . '/../views/frontend.php';
}
public function exportarExcel(): void
{
    $this->session->requireAuth();

    $user = $this->session->getUser();

    if (!in_array($user['role'], [
        'admin',
        'supervisor_general',
        'supervisor'
    ])) {
        die('No autorizado');
    }

    $usuarioId = $_GET['usuario_id'] ?? '';

    $fechaInicio = $_GET['fecha_inicio']
        ?? date('Y-m-01');

    $fechaFin = $_GET['fecha_fin']
        ?? date('Y-m-d');

    $sql = "
        SELECT
            am.*,
            u.nombre,
            u.usuario
        FROM asistencia_movimientos am
        INNER JOIN usuarios u
            ON u.id = am.usuario_id
    ";

    $where = [];
    $params = [];

    $where[] = "
        DATE(am.fecha_hora)
        BETWEEN :fecha_inicio AND :fecha_fin
    ";

    $params['fecha_inicio'] = $fechaInicio;
    $params['fecha_fin'] = $fechaFin;

    if (!empty($usuarioId)) {

        $where[] = "
            am.usuario_id = :usuario_id
        ";

        $params['usuario_id'] = $usuarioId;
    }

    if ($user['role'] === 'supervisor') {

        $where[] = "
            u.supervisor_id = :supervisor_id
        ";

        $params['supervisor_id'] = $user['id'];
    }

    if (!empty($where)) {

        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $sql .= " ORDER BY am.fecha_hora DESC";

    $stmt = $this->db->prepare($sql);
}
private function calcularHorasTrabajadas(
    int $usuarioId,
    string $fecha
): array {

    $stmt = $this->db->prepare("
        SELECT *
        FROM asistencia_movimientos
        WHERE usuario_id = :uid
        AND DATE(fecha_hora) = :fecha
        ORDER BY fecha_hora ASC
    ");

    $stmt->execute([
        'uid' => $usuarioId,
        'fecha' => $fecha
    ]);

    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $entradaLaboral = null;
    $salidaLaboral = null;

    $segundosTrabajados = 0;
    $segundosFuera = 0;

    $ultimaEntrada = null;
    $ultimaSalidaTemporal = null;

    foreach ($movimientos as $m) {

        $fechaHora = strtotime($m['fecha_hora']);

        // PRIMER ENTRADA LABORAL
        if (
            $m['tipo_movimiento'] === 'entrada'
            && $m['motivo'] === 'laboral'
            && !$entradaLaboral
        ) {
            $entradaLaboral = $fechaHora;
        }

        // SALIDA FINAL
        if (
            $m['tipo_movimiento'] === 'salida'
            && $m['motivo'] === 'laboral'
        ) {
            $salidaLaboral = $fechaHora;
        }

        // ENTRADAS TEMPORALES
        if ($m['tipo_movimiento'] === 'entrada') {

            if ($ultimaSalidaTemporal) {

                $segundosFuera += (
                    $fechaHora - $ultimaSalidaTemporal
                );

                $ultimaSalidaTemporal = null;
            }

            $ultimaEntrada = $fechaHora;
        }

        // SALIDAS TEMPORALES
        if (
            $m['tipo_movimiento'] === 'salida'
            && $m['motivo'] !== 'laboral'
        ) {
            $ultimaSalidaTemporal = $fechaHora;
        }
    }

    // SI NO HA SALIDO HOY
    if ($entradaLaboral && !$salidaLaboral) {
        $salidaLaboral = time();
    }

    // TOTAL TRABAJADO
    if ($entradaLaboral && $salidaLaboral) {

        $segundosTrabajados = (
            $salidaLaboral - $entradaLaboral
        ) - $segundosFuera;
    }

    $horas = round(
        $segundosTrabajados / 3600,
        2
    );

    return [
        'horas_trabajadas' => $horas,
        'horas_fuera' => round($segundosFuera / 3600, 2),
        'entrada' => $entradaLaboral
            ? date('H:i:s', $entradaLaboral)
            : '-',
        'salida' => $salidaLaboral
            ? date('H:i:s', $salidaLaboral)
            : '-'
    ];
}

public function estadisticas(): void
{
    $this->session->requireAuth();

    $user = $this->session->getUser();

    if (!in_array($user['role'], [
        'admin',
        'supervisor_general',
        'supervisor'
    ])) {
        die('No autorizado');
    }

    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

    $periodo = $_GET['periodo'] ?? '7dias';

    switch ($periodo) {

        case 'hoy':

            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');

            break;

        case 'ayer':

            $fechaInicio = date('Y-m-d', strtotime('-1 day'));
            $fechaFin = date('Y-m-d', strtotime('-1 day'));

            break;

        case 'mes_actual':

            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-d');

            break;

        case 'mes_pasado':

            $fechaInicio = date(
                'Y-m-01',
                strtotime('first day of last month')
            );

            $fechaFin = date(
                'Y-m-t',
                strtotime('last month')
            );

            break;

        case '30dias':

            $fechaInicio = date(
                'Y-m-d',
                strtotime('-30 days')
            );

            $fechaFin = date('Y-m-d');

            break;

        default:

            $fechaInicio = $_GET['fecha_inicio']
                ?? date(
                    'Y-m-d',
                    strtotime('-7 days')
                );

            $fechaFin = $_GET['fecha_fin']
                ?? date('Y-m-d');

            break;
    }

    $usuarioId = $_GET['usuario_id'] ?? '';

    $supervisorId = $_GET['supervisor_id'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | USUARIOS DISPONIBLES
    |--------------------------------------------------------------------------
    */

    // SUPERVISORES
    $supervisores = [];

    if (in_array($user['role'], [
        'admin',
        'supervisor_general'
    ])) {

        $stmt = $this->db->query("
            SELECT
                id,
                nombre,
                usuario
            FROM usuarios
            WHERE activo = true
            AND rol = 'supervisor'
            ORDER BY nombre ASC
        ");

        $supervisores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | USUARIOS VISIBLES
    |--------------------------------------------------------------------------
    */

    $sqlUsuarios = "
        SELECT
            u.id,
            u.nombre,
            u.usuario,
            u.rol,
            s.nombre AS supervisor_nombre
        FROM usuarios u
        LEFT JOIN usuarios s
            ON s.id = u.supervisor_id
        WHERE u.activo = true
    ";

    $paramsUsuarios = [];

    // SUPERVISOR SOLO VE SUS GESTORES
    if ($user['role'] === 'supervisor') {

        $sqlUsuarios .= "
            AND u.supervisor_id = :supervisor_id
        ";

        $paramsUsuarios['supervisor_id']
            = $user['id'];
    }

    // FILTRO POR SUPERVISOR
    if (
        !empty($supervisorId)
        && in_array($user['role'], [
            'admin',
            'supervisor_general'
        ])
    ) {

        $sqlUsuarios .= "
            AND u.supervisor_id = :filtro_supervisor
        ";

        $paramsUsuarios['filtro_supervisor']
            = $supervisorId;
    }

    $sqlUsuarios .= "
        ORDER BY u.nombre ASC
    ";

    $stmt = $this->db->prepare($sqlUsuarios);

    $stmt->execute($paramsUsuarios);

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | ESTADÍSTICAS
    |--------------------------------------------------------------------------
    */

    $estadisticas = [];

    foreach ($usuarios as $u) {

        // FILTRO USUARIO
        if (
            !empty($usuarioId)
            && $usuarioId != $u['id']
        ) {
            continue;
        }

        $inicio = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);

        $totalHoras = 0;
        $totalFuera = 0;
        $dias = 0;

        while ($inicio <= $fin) {

            $fecha = $inicio->format('Y-m-d');

            $calc = $this->calcularHorasTrabajadas(
                $u['id'],
                $fecha
            );

            if ($calc['horas_trabajadas'] > 0) {

                $totalHoras += $calc['horas_trabajadas'];

                $totalFuera += $calc['horas_fuera'];

                $dias++;
            }

            $inicio->modify('+1 day');
        }

        $promedio = $dias > 0
            ? round($totalHoras / $dias, 2)
            : 0;

        $estadisticas[] = [

            'usuario' => $u,

            'dias' => $dias,

            'total_horas' => round(
                $totalHoras,
                2
            ),

            'horas_fuera' => round(
                $totalFuera,
                2
            ),

            'promedio' => $promedio
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PROMEDIO GENERAL
    |--------------------------------------------------------------------------
    */

    $promedioGeneral = 0;

    if (!empty($estadisticas)) {

        $promedioGeneral = round(

            array_sum(
                array_column(
                    $estadisticas,
                    'promedio'
                )
            )

            / count($estadisticas),

            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VISTA
    |--------------------------------------------------------------------------
    */

    $pageTitle = 'Estadísticas de Asistencia | LEX 360';

    ob_start();

    require_once __DIR__ . '/../views/asistencia/estadisticas.php';

    $viewContent = ob_get_clean();

    require_once __DIR__ . '/../views/frontend.php';
}

}
