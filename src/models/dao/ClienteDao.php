<?php
namespace LEX360\Models\Dao;

use LEX360\Core\BaseDao;
use PDO;
use PDOException;
class ClienteDao extends BaseDao
{
    protected string $table = 'clientes';

    // ... tus métodos anteriores ...

    /**
     * Obtener estadísticas para el Dashboard
     * @param int $userId ID del usuario logueado
     * @param string $role Rol del usuario (gestor, supervisor, etc.)
     */
    public function getEstadisticas(int $userId, string $role): array
    {
        // Definir el filtro según el rol
        $where = ($role === 'gestor') 
            ? "WHERE id_gestor_asignado = :uid" 
            : "WHERE id_supervisor_cadena = :uid";

        $where = ($role == 'supervisor')
            ? "WHERE id_supervisor_cadena = :uid" 
            : "WHERE id_gestor_asignado = :uid";

        $where = in_array($role , ['supervisor_general','admin'])
            ? "where 1=1" 
            : "WHERE id_gestor_asignado = :uid";


        if (!in_array($role , ['supervisor_general','admin'])){
            $params = ['uid' => $userId];
            }
        else{
            $params = [];
        }

        // 1. Total Asignados
        $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM clientes $where");
        $stmtTotal->execute($params);
        $totalAsignados = $stmtTotal->fetchColumn();

        // 2. Llamadas para Hoy (Gestiones pendientes o nuevas hoy)
        // Asumimos que si no tienen fecha_ultima_gestion, son nuevos para hoy
        $stmtHoy = $this->db->prepare("
            SELECT COUNT(*)
            FROM clientes c
            $where
            AND (
                DATE(
                    (
                        SELECT h.fecha_proxima_llamada
                        FROM historial h
                        WHERE h.id_cliente = c.id and date(h.fecha_proxima_llamada) = date(now())
                        ORDER BY h.id DESC
                        LIMIT 1
                    )
                ) =date(now())
            )        
        ");
        $stmtHoy->execute($params);
        $llamadasHoy = $stmtHoy->fetchColumn();



        // 3. Promesas de Pago Hoy (Futuro: Tabla Promesas)
        // Por ahora simulamos 0 o sacamos de una tabla promesas si ya existe
        $promesasHoy = 0; 

        return [
            'total_asignados' => $totalAsignados,
            'llamadas_hoy'    => $llamadasHoy,
            'promesas_hoy'    => $promesasHoy
        ];
    }
    
    public function findByRole(int $userId, string $role, string $search = ''): array
    {
        if (in_array($role, ['admin', 'supervisor_general'])) {
            $where = "WHERE 1=1";
            $params = [];
        } elseif ($role === 'gestor') {
            $where = "WHERE c.id_gestor_asignado = :uid";
            $params = ['uid' => $userId];
        } else {
            $where = "WHERE c.id_supervisor_cadena = :uid";
            $params = ['uid' => $userId];
        }

        $sql = "
            SELECT 
                c.id,
                c.nombre,
                c.identificacion,
                c.cuenta,
                c.saldo_inicial,
                c.saldo,
                c.telefono_1,
                c.telefono_2,
                c.estado,
                c.fecha_ultima_gestion,
                c.data_extras,
                u.usuario,
                sup.usuario as supervisor,
                h.fecha_proxima_llamada,
                h.estatus AS ultimo_estatus,
                t.nombre AS ultima_tipologia

            FROM clientes c

            LEFT JOIN historial h 
            ON h.id = (
                SELECT hh.id
                FROM historial hh
                WHERE hh.id_cliente = c.id
                ORDER BY hh.id DESC
                LIMIT 1
            )

            LEFT JOIN tipologias t 
            ON t.id = h.id_tipologia
            LEFT JOIN usuarios u on c.id_gestor_asignado = u.id
            LEFT JOIN usuarios sup
            ON sup.id = c.id_supervisor_cadena

            $where";

        if(in_array($role,['admin','supervisor_general'])){
            $params = [];
        }else {
            $params = ['uid' => $userId];
        }
        

        if (!empty($search)) {
        $sql .= "
            AND c.search_vector @@ websearch_to_tsquery('spanish', :search)
        ";            $params['search'] = "%{$search}%";
                }

        // Prioridad: primero los que NO han sido gestionados hoy, luego los más antiguos
        $sql .= " ORDER BY fecha_ultima_gestion ASC NULLS FIRST, id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function findNoConfirm(int $userId, string $role, string $search = ''): array
    {
        if (in_array($role, ['admin', 'supervisor_general'])) {
            $where = "WHERE 1=1";
            $params = [];
        } elseif ($role === 'gestor') {
            $where = "WHERE c.id_gestor_asignado = :uid";
            $params = ['uid' => $userId];
        } else {
            $where = "WHERE c.id_supervisor_cadena = :uid";
            $params = ['uid' => $userId];
        }

        $sql = "
            SELECT 
                c.id,
                c.nombre,
                c.identificacion,
                c.cuenta,
                c.saldo_inicial,
                c.saldo,
                c.telefono_1,
                c.telefono_2,
                c.estado,
                c.fecha_ultima_gestion,
                c.data_extras,
                u.usuario,
                sup.usuario as supervisor,
                h.fecha_proxima_llamada,
                h.estatus AS ultimo_estatus,
                t.nombre AS ultima_tipologia

            FROM clientes c

            LEFT JOIN historial h 
            ON h.id_cliente = c.id
            AND h.estatus = 'PAGG'
            LEFT JOIN tipologias t 
            ON t.id = h.id_tipologia
            LEFT JOIN usuarios u on c.id_gestor_asignado = u.id
            LEFT JOIN usuarios sup
            ON sup.id = c.id_supervisor_cadena
            

            $where and h.estatus is not null ";


        if (!empty($search)) {
            $sql .= " AND (
                c.nombre ILIKE :search 
                OR c.identificacion ILIKE :search 
                OR c.cuenta ILIKE :search
            )";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY c.fecha_ultima_gestion ASC NULLS FIRST, c.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
    public function findNoDone(int $userId, string $role, string $search = '', $estatus=NULL): array
    {
        if (in_array($role, ['admin', 'supervisor_general'])) {

            $where = "WHERE p.estatus != 'cumplida'";

            if ($estatus){
                $where = "WHERE p.estatus = '$estatus'";
            }

            $params = [];

        } elseif ($role === 'gestor') {

            $where = "
                WHERE p.estatus != 'cumplida'
                AND p.id_usuario = :uid
            ";

            $params = ['uid' => $userId];

        } else {

            $where = "
                WHERE p.estatus != 'cumplida'
                AND c.id_supervisor_cadena = :uid
            ";

            $params = ['uid' => $userId];
        }

        $sql = "
            SELECT 
                c.id,
                c.nombre,
                c.identificacion,
                c.cuenta,
                c.saldo_inicial,
                c.saldo,
                c.telefono_1,
                c.telefono_2,
                c.estado,
                c.fecha_ultima_gestion,
                c.data_extras,
                u.usuario,
                sup.usuario as supervisor,
                p.id AS id_promesa,
                p.monto_prometido,
                p.fecha_compromiso,
                p.fecha_registro,
                p.estatus AS estatus_promesa,

                h.fecha_proxima_llamada,
                h.estatus AS ultimo_estatus,
                t.nombre AS ultima_tipologia

            FROM promesas p

            INNER JOIN clientes c
                ON c.id = p.id_cliente

            LEFT JOIN historial h 
                ON h.id = (
                    SELECT hh.id
                    FROM historial hh
                    WHERE hh.id_cliente = c.id
                    ORDER BY hh.id DESC
                    LIMIT 1
                )

            LEFT JOIN tipologias t 
                ON t.id = h.id_tipologia
            LEFT JOIN usuarios u on c.id_gestor_asignado = u.id
            LEFT JOIN usuarios sup
            ON sup.id = c.id_supervisor_cadena

            $where
        ";

        if (!empty($search)) {

            $sql .= "
                AND (
                    c.nombre ILIKE :search
                    OR c.identificacion ILIKE :search
                    OR c.cuenta ILIKE :search
                )
            ";

            $params['search'] = "%{$search}%";
        }

        $sql .= "
            ORDER BY 
                p.fecha_compromiso ASC,
                c.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }    
public function findPays(
    int $userId,
    string $role,
    string $search = '',
    $estatus = null
): float {

    // ROLES
    if (in_array($role, ['admin', 'supervisor_general'])) {

        $where = "WHERE 1=1";
        $params = [];

    } elseif ($role === 'gestor') {

        $where = "WHERE c.id_gestor_asignado = :uid";
        $params = ['uid' => $userId];

    } else {

        $where = "WHERE c.id_supervisor_cadena = :uid";
        $params = ['uid' => $userId];
    }

    // ESTATUS
    if ($estatus) {

        $where .= " AND p.estatus = :estatus";
        $params['estatus'] = $estatus;
    }

    // SEARCH
    if (!empty($search)) {

        $where .= "
            AND (
                c.nombre ILIKE :search
                OR c.identificacion ILIKE :search
                OR c.cuenta ILIKE :search
            )
        ";

        $params['search'] = "%{$search}%";
    }

    $sql = "
        SELECT 
            COALESCE(SUM(p.monto), 0) AS suma

        FROM pagos p

        INNER JOIN clientes c
            ON c.id = p.id_cliente

        $where
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return (float)$stmt->fetchColumn();
}
public function sumClientes(
    int $userId,
    string $role,
    string $search = '',
    ?string $campo = null
): float {
    $camposPermitidos = ['saldo', 'saldo_inicial'];

    $campo = $campo ?? 'saldo';

    if (!in_array($campo, $camposPermitidos)) {

        echo "Campo saldo '{$campo}' no corresponde a la tabla clientes<br>";
        $campo = 'saldo';
    }

    // ROLES
    if (in_array($role, ['admin', 'supervisor_general'])) {

        $where = "WHERE 1=1";
        $params = [];

    } elseif ($role === 'gestor') {

        $where = "WHERE c.id_gestor_asignado = :uid";
        $params = ['uid' => $userId];

    } else {

        $where = "WHERE c.id_supervisor_cadena = :uid";
        $params = ['uid' => $userId];
    }

    // SEARCH
    if (!empty($search)) {

        $where .= "
            AND (
                c.nombre ILIKE :search
                OR c.identificacion ILIKE :search
                OR c.cuenta ILIKE :search
            )
        ";

        $params['search'] = "%{$search}%";
    }

    $sql = "
        SELECT 
            COALESCE(SUM(c.{$campo}), 0) AS suma
        FROM clientes c
        $where
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return (float)$stmt->fetchColumn();
}
/**
 * Obtiene saldo_inicial, saldo y logro (diferencia) por cartera,
 * respetando roles y filtros de búsqueda.
 * 
 * @return array [
 *   ['id'=>1, 'cartera'=>'Nombre', 'inicial'=>1000, 'saldo'=>300, 'logro'=>700],
 *   ...
 * ]
 */
public function getSaldoCarteras(
): array {
    $userId =  $_SESSION['user_id']??'';
    $role = $_SESSION['role']??'';
   

    // ─────────────────────────────────────────
    // LÓGICA DE ROLES (consistente con sumClientes)
    // ─────────────────────────────────────────
    if (in_array($role, ['admin', 'supervisor_general'])) {
        $where = "WHERE 1=1";
        $params = [];
    } elseif ($role === 'gestor') {
        $where = "WHERE c.id_gestor_asignado = :uid";
        $params = ['uid' => $userId];
    } else {
        $where = "WHERE c.id_supervisor_cadena = :uid";
        $params = ['uid' => $userId];
    }


    // ─────────────────────────────────────────
    // CONSULTA: SALDO_INICIAL, SALDO Y LOGRO POR CARTERA
    // ─────────────────────────────────────────
    $sql = "
        SELECT 
            car.id AS id_cartera,
            car.nombre_cartera AS cartera,
            COALESCE(SUM(c.saldo_inicial), 0) AS total_inicial,
            COALESCE(SUM(c.saldo), 0) AS total_saldo,
            COALESCE(SUM(c.saldo_inicial - c.saldo), 0) AS logro
        FROM clientes c
        INNER JOIN carteras car ON c.id_cartera = car.id
        {$where}
        AND car.activa = true
        GROUP BY car.id, car.nombre_cartera
        ORDER BY logro DESC
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ─────────────────────────────────────────
    // FORMATO LISTO PARA CHART.JS
    // ─────────────────────────────────────────
    $data = [];
    foreach ($resultados as $row) {
        $data[] = [
            'id'        => (int)$row['id_cartera'],
            'cartera'   => $row['cartera'],
            'inicial'   => (float)$row['total_inicial'],  // Total asignado
            'saldo'     => (float)$row['total_saldo'],    // Saldo restante
            'logro'     => (float)$row['logro']           // Inicial - Saldo = Logro
        ];
    }

    return $data;
}
public function findByRoleExcel(int $userId, string $role, string $search = ''): array
{
    if (in_array($role, ['admin', 'supervisor_general'])) {
        $where = "WHERE 1=1";
        $params = [];
    } elseif ($role === 'gestor') {
        $where = "WHERE c.id_gestor_asignado = :uid";
        $params = ['uid' => $userId];
    } else {
        $where = "WHERE c.id_supervisor_cadena = :uid";
        $params = ['uid' => $userId];
    }

    $sql = "
        SELECT
            c.id,
            c.nombre,
            c.identificacion,
            c.cuenta,
            c.saldo_inicial,
            c.saldo,
            c.telefono_1,
            c.telefono_2,
            c.estado,
            c.fecha_ultima_gestion,
            u.usuario,
            sup.usuario as supervisor,
            h.fecha_proxima_llamada,
            h.estatus,
            t.nombre AS tipologia

        FROM clientes c

        LEFT JOIN historial h
        ON h.id = (
            SELECT hh.id
            FROM historial hh
            WHERE hh.id_cliente = c.id
            ORDER BY hh.id DESC
            LIMIT 1
        )

        LEFT JOIN tipologias t
            ON t.id = h.id_tipologia

        LEFT JOIN usuarios u
            ON u.id = c.id_gestor_asignado

        LEFT JOIN usuarios sup
            ON sup.id = c.id_supervisor_cadena

        $where
    ";

    if (!empty($search)) {
        $sql .= " AND (
            c.nombre ILIKE :search
            OR c.identificacion ILIKE :search
            OR c.cuenta ILIKE :search
        )";

        $params['search'] = "%{$search}%";
    }

    $sql .= " ORDER BY c.id DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getGestores($id,$role):array{
    if (in_array($role,['administrador'])){
        $sql = "SELECT nombre, id FROM usuarios WHERE supervisor_id = :supervisor";
        $params=['supervisor'=>$id];
    } else {
        return [];
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);

    
}

public function getSupervisoresGenerales($role):array{
    if (in_array($role,['admin'])){
        $sql = "SELECT nombre, id FROM usuarios WHERE rol = 'supervisor_general'";
        $params=[];
    } else {
        return [];
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);

}
public function getSupervisores($role):array{
    if (in_array($role,['admin','administrador_general'])){
        $sql = "SELECT nombre, id FROM usuarios WHERE rol = 'supervisor'";
        $params=[];
    } else {
        return [];
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);

    
}
private function getDashboardFilters(
    int $userId,
    string $role,
    ?int $supervisorId = null,
    ?int $gestorId = null
): array {

    $where = [];
    $params = [];

    if ($role === 'gestor') {

        $where[] = "u.id = :uid";
        $params['uid'] = $userId;

    } elseif ($role === 'supervisor') {

        $where[] = "u.supervisor_id = :uid";
        $params['uid'] = $userId;

        if (!empty($gestorId)) {
            $where[] = "u.id = :gestor";
            $params['gestor'] = $gestorId;
        }

    } elseif (in_array($role,['admin','supervisor_general'])) {

        if (!empty($supervisorId)) {
            $where[] = "u.supervisor_id = :supervisor";
            $params['supervisor'] = $supervisorId;
        }

        if (!empty($gestorId)) {
            $where[] = "u.id = :gestor";
            $params['gestor'] = $gestorId;
        }
    }

    return [
        'where' => count($where)
            ? ' WHERE '.implode(' AND ', $where)
            : '',
        'params' => $params
    ];
}
public function getGestionesPeriodo(
    int $userId,
    string $role,
    string $inicio,
    string $fin,
    ?int $supervisorId = null,
    ?int $gestorId = null
): int {

    $filter = $this->getDashboardFilters(
        $userId,
        $role,
        $supervisorId,
        $gestorId
    );

    $params = $filter['params'];

    $params['inicio'] = $inicio;
    $params['fin'] = $fin;

    $sql = "
        SELECT COUNT(*)

        FROM historial h

        INNER JOIN usuarios u
            ON u.id = h.id_usuario

        {$filter['where']}
        " . (empty($filter['where']) ? "WHERE" : "AND") . "

        DATE(h.fecha_gestion)
            BETWEEN :inicio AND :fin
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}
public function getDashboardPeriodo(
    int $userId,
    string $role,
    string $inicio,
    string $fin,
    ?int $supervisorId = null,
    ?int $gestorId = null
): array {

    return [

        'gestiones' =>
            $this->getGestionesPeriodo(
                $userId,
                $role,
                $inicio,
                $fin,
                $supervisorId,
                $gestorId
            ),

        'clientes_gestionados' =>
            $this->getClientesGestionadosPeriodo(
                $userId,
                $role,
                $inicio,
                $fin,
                $supervisorId,
                $gestorId
            ),

        'promesas' =>
            $this->getPromesasPeriodo(
                $userId,
                $role,
                $inicio,
                $fin,
                $supervisorId,
                $gestorId
            ),

        'monto_promesas' =>
            $this->getMontoPromesasPeriodo(
                $userId,
                $role,
                $inicio,
                $fin,
                $supervisorId,
                $gestorId
            ),

        'saldo_recuperado' =>
            $this->getSaldoRecuperadoPeriodo(
                $userId,
                $role,
                $supervisorId,
                $gestorId
            )

    ];
}
public function getClientesGestionadosPeriodo(
    int $userId,
    string $role,
    string $inicio,
    string $fin,
    ?int $supervisorId = null,
    ?int $gestorId = null
): int {

    $filter = $this->getDashboardFilters(
        $userId,
        $role,
        $supervisorId,
        $gestorId
    );

    $params = $filter['params'];
    $params['inicio'] = $inicio;
    $params['fin'] = $fin;

    $sql = "
        SELECT COUNT(DISTINCT h.id_cliente)

        FROM historial h

        INNER JOIN usuarios u
            ON u.id = h.id_usuario

        {$filter['where']}
        " . (empty($filter['where']) ? "WHERE" : "AND") . "

        DATE(h.fecha_gestion)
            BETWEEN :inicio AND :fin
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}
public function getPromesasPeriodo(
    int $userId,
    string $role,
    string $inicio,
    string $fin,
    ?int $supervisorId = null,
    ?int $gestorId = null
): int {

    $filter = $this->getDashboardFilters(
        $userId,
        $role,
        $supervisorId,
        $gestorId
    );

    $params = $filter['params'];
    $params['inicio'] = $inicio;
    $params['fin'] = $fin;

    $sql = "
        SELECT COUNT(*)

        FROM promesas p

        INNER JOIN usuarios u
            ON u.id = p.id_usuario

        {$filter['where']}
        " . (empty($filter['where']) ? "WHERE" : "AND") . "

        DATE(p.fecha_registro)
            BETWEEN :inicio AND :fin
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}
public function getMontoPromesasPeriodo(
    int $userId,
    string $role,
    string $inicio,
    string $fin,
    ?int $supervisorId = null,
    ?int $gestorId = null
): float {

    $filter = $this->getDashboardFilters(
        $userId,
        $role,
        $supervisorId,
        $gestorId
    );

    $params = $filter['params'];
    $params['inicio'] = $inicio;
    $params['fin'] = $fin;

    $sql = "
        SELECT
            COALESCE(
                SUM(p.monto_prometido),
                0
            )

        FROM promesas p

        INNER JOIN usuarios u
            ON u.id = p.id_usuario

        {$filter['where']}
        " . (empty($filter['where']) ? "WHERE" : "AND") . "

        DATE(p.fecha_registro)
            BETWEEN :inicio AND :fin
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return (float)$stmt->fetchColumn();
}
public function getSaldoRecuperadoPeriodo(
    int $userId,
    string $role,
    ?int $supervisorId = null,
    ?int $gestorId = null
): float {

    $where = [];
    $params = [];

    if ($role === 'gestor') {

        $where[] = "c.id_gestor_asignado = :uid";
        $params['uid'] = $userId;

    } elseif ($role === 'supervisor') {

        $where[] = "c.id_supervisor_cadena = :uid";
        $params['uid'] = $userId;

        if (!empty($gestorId)) {
            $where[] = "c.id_gestor_asignado = :gestor";
            $params['gestor'] = $gestorId;
        }

    } elseif (in_array($role,['admin','supervisor_general'])) {

        if (!empty($supervisorId)) {
            $where[] = "c.id_supervisor_cadena = :supervisor";
            $params['supervisor'] = $supervisorId;
        }

        if (!empty($gestorId)) {
            $where[] = "c.id_gestor_asignado = :gestor";
            $params['gestor'] = $gestorId;
        }
    }

    $sql = "
        SELECT
            COALESCE(
                SUM(c.saldo_inicial - c.saldo),
                0
            )

        FROM clientes c
    ";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return (float)$stmt->fetchColumn();
}
public function getGestionesPorDia(
    int $userId,
    string $role,
    string $inicio,
    string $fin,
    ?int $supervisorId = null,
    ?int $gestorId = null
): array {

    $filter = $this->getDashboardFilters(
        $userId,
        $role,
        $supervisorId,
        $gestorId
    );

    $params = $filter['params'];
    $params['inicio'] = $inicio;
    $params['fin'] = $fin;

    $sql = "
        SELECT
            DATE(h.fecha_gestion) AS fecha,
            COUNT(*) AS cantidad

        FROM historial h

        INNER JOIN usuarios u
            ON u.id = h.id_usuario

        {$filter['where']}
        " . (empty($filter['where']) ? "WHERE" : "AND") . "

        DATE(h.fecha_gestion)
            BETWEEN :inicio AND :fin

        GROUP BY DATE(h.fecha_gestion)

        ORDER BY fecha ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getPromesasPagosPorDia(
    int $userId,
    string $role,
    string $inicio,
    string $fin,
    ?int $supervisorId = null,
    ?int $gestorId = null
): array {

    $wherePromesas = [];
    $wherePagos = [];

    $params = [
        'inicio' => $inicio,
        'fin'    => $fin
    ];

    if ($role === 'gestor') {

        $wherePromesas[] = "p.id_usuario = :uid";
        $wherePagos[] = "c.id_gestor_asignado = :uid";

        $params['uid'] = $userId;

    } elseif ($role === 'supervisor') {

        $wherePromesas[] = "u.supervisor_id = :uid";
        $wherePagos[] = "u.supervisor_id = :uid";

        $params['uid'] = $userId;

        if (!empty($gestorId)) {

            $wherePromesas[] = "p.id_usuario = :gestor";
            $wherePagos[] = "c.id_gestor_asignado = :gestor";

            $params['gestor'] = $gestorId;
        }

    } elseif (in_array($role, ['admin','supervisor_general'])) {

        if (!empty($supervisorId)) {

            $wherePromesas[] = "u.supervisor_id = :supervisor";
            $wherePagos[] = "u.supervisor_id = :supervisor";

            $params['supervisor'] = $supervisorId;
        }

        if (!empty($gestorId)) {

            $wherePromesas[] = "p.id_usuario = :gestor";
            $wherePagos[] = "c.id_gestor_asignado = :gestor";

            $params['gestor'] = $gestorId;
        }
    }

    $sql = "

        SELECT
            fecha,
            SUM(prometido) AS prometido,
            SUM(recuperado) AS recuperado
        FROM (

            SELECT
                DATE(p.fecha_registro) AS fecha,
                SUM(p.monto_prometido) AS prometido,
                0::numeric AS recuperado

            FROM promesas p

            INNER JOIN usuarios u
                ON u.id = p.id_usuario

            WHERE DATE(p.fecha_registro)
                BETWEEN :inicio AND :fin
    ";

    if (!empty($wherePromesas)) {
        $sql .= " AND " . implode(" AND ", $wherePromesas);
    }

    $sql .= "

            GROUP BY DATE(p.fecha_registro)

            UNION ALL

            SELECT
                DATE(pg.fecha_pago) AS fecha,
                0::numeric AS prometido,
                SUM(pg.monto) AS recuperado

            FROM pagos pg

            INNER JOIN clientes c
                ON c.id = pg.id_cliente

            INNER JOIN usuarios u
                ON u.id = c.id_gestor_asignado

            WHERE DATE(pg.fecha_pago)
                BETWEEN :inicio AND :fin

            AND pg.estatus = 'PAGO'
    ";

    if (!empty($wherePagos)) {
        $sql .= " AND " . implode(" AND ", $wherePagos);
    }

    $sql .= "

            GROUP BY DATE(pg.fecha_pago)

        ) t

        GROUP BY fecha
        ORDER BY fecha ASC

    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateValidaciones(
        $id_supervisor,
        $id_autoriza,
        $monto_autorizado,
        $fecha_vencimiento,
        $estado,
        $observacion
    ): array {

        $sql = "
            UPDATE validaciones
            SET
                id_autoriza = :id_autoriza,
                monto_autorizado = :monto_autorizado,
                fecha_autorizacion = CURRENT_TIMESTAMP,
                fecha_vencimiento = :fecha_vencimiento,
                estado = :estado,
                observacion = :observacion
            WHERE id_supervisor = :id_supervisor
        ";

        try {

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'id_supervisor'     => $id_supervisor,
                'id_autoriza'       => $id_autoriza,
                'monto_autorizado'  => $monto_autorizado,
                'fecha_vencimiento' => $fecha_vencimiento,
                'estado'            => $estado,
                'observacion'       => $observacion
            ]);

            if ($stmt->rowCount() === 0) {
                return [
                    'status' => 'error',
                    'message' => 'No se encontró el supervisor a actualizar.'
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Validación actualizada exitosamente.'
            ];

        } catch (PDOException $e) {

            return [
                'status' => 'error',
                'message' => 'Error al actualizar la validación.'
            ];
        }
    }
    public function insertValidaciones(
        $id_supervisor,
        $id_autoriza,
        $monto_autorizado,
        $fecha_vencimiento,
        $observacion
    ): array {

        $sql = "
            INSERT INTO validaciones (
                id_supervisor,
                id_autoriza,
                monto_autorizado,
                fecha_vencimiento,
                observacion
            )
            VALUES (
                :id_supervisor,
                :id_autoriza,
                :monto_autorizado,
                :fecha_vencimiento,
                :observacion
            )
        ";

        try {

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'id_supervisor'     => $id_supervisor,
                'id_autoriza'       => $id_autoriza,
                'monto_autorizado'  => $monto_autorizado,
                'fecha_vencimiento' => $fecha_vencimiento,
                'observacion'       => $observacion
            ]);

            return [
                'status'  => 'ok',
                'message' => 'Transacción procesada exitosamente.'
            ];

        } catch (PDOException $e) {

            // Restricción UNIQUE
            if ($e->getCode() === '23505') {

                return [
                    'status'  => 'error',
                    'message' => 'Ya existe un usuario asignado. Por favor verifique y modifíquelo en caso de necesitarlo.'
                ];
            }

            return [
                'status'  => 'error',
                'message' => 'Error en grabación.'
            ];
        }
    }
    public function getValidaciones(int $id_supervisor=null):Array{
        $sql = "
        SELECT
            v.id,
            v.monto_autorizado,

            TO_CHAR(v.fecha_autorizacion, 'DD/MM/YYYY') AS fecha_autorizacion,

            TO_CHAR(v.fecha_vencimiento, 'DD/MM/YYYY') AS fecha_vencimiento,
            v.fecha_vencimiento as limite,
            v.id_supervisor,
            v.id_autoriza,
            v.estado,
            v.observacion,

            admin.nombre AS nombre_admin,
            admin.usuario AS usuario_admin,

            supervisor.nombre AS nombre_supervisor,
            supervisor.usuario AS usuario_supervisor


        FROM validaciones v
        INNER JOIN usuarios admin
            ON admin.id = v.id_autoriza
        INNER JOIN usuarios supervisor
            ON supervisor.id = v.id_supervisor

        ";
        $params = [];
        if (!$id_supervisor){
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $resultados;
        } 

        $params["id_supervisor"]=$id_supervisor;
        $sql.="WHERE id_supervisor = :id_supervisor";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$resultados){
            return[];
        }
        return [$resultados];

    }
    public function eliminarValidacion($id_user): array
    {
        $sql = "DELETE FROM validaciones
                WHERE id_supervisor = :id_supervisor";

        try {

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'id_supervisor' => $id_user
            ]);

            if ($stmt->rowCount() === 0) {
                return [
                    'status' => 'error',
                    'message' => 'No se encontró la validación a eliminar.'
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Validación eliminada exitosamente.'
            ];

        } catch (PDOException $e) {

            return [
                'status' => 'error',
                'message' => 'Error al eliminar la validación.'
            ];
        }
    }
}