<?php
namespace LEX360\Models\Dao;

use LEX360\Core\BaseDao;
use PDO;
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

            $where";

        if(in_array($role,['admin','supervisor_general'])){
            $params = [];
        }else {
            $params = ['uid' => $userId];
        }
        

        if (!empty($search)) {
            $sql .= " AND (
                c.nombre ILIKE :search 
                OR c.identificacion ILIKE :search 
                OR c.cuenta ILIKE :search
            )";
            $params['search'] = "%{$search}%";
        }

        // Prioridad: primero los que NO han sido gestionados hoy, luego los más antiguos
        $sql .= " ORDER BY fecha_ultima_gestion ASC NULLS FIRST, id DESC LIMIT 200";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function findNoConfirm(int $userId, string $role, string $search = ''): array
    {
        $where = ($role === 'gestor') 
            ? "WHERE c.id_gestor_asignado = :uid" 
            : "WHERE c.id_supervisor_cadena = :uid";

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

                h.fecha_proxima_llamada,
                h.estatus AS ultimo_estatus,
                t.nombre AS ultima_tipologia

            FROM clientes c

            LEFT JOIN historial h 
            ON h.id_cliente = c.id
            AND h.estatus = 'PAGG'

            LEFT JOIN tipologias t 
            ON t.id = h.id_tipologia

            $where and h.estatus is not null ";

        $params = ['uid' => $userId];

        if (!empty($search)) {
            $sql .= " AND (
                c.nombre ILIKE :search 
                OR c.identificacion ILIKE :search 
                OR c.cuenta ILIKE :search
            )";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY c.fecha_ultima_gestion ASC NULLS FIRST, c.id DESC LIMIT 200";

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
            LIMIT 200
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
}