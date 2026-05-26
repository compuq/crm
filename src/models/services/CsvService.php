<?php
namespace LEX360\Models\Services;

use PDO;
use LEX360\Models\Dao\Db\Database;
use LEX360\Models\Dao\CarteraDao;
use LEX360\Models\Dao\UsuarioDao;
use PhpOffice\PhpSpreadsheet\IOFactory; // ← Requerido para XLSX

class CsvService
{
    private PDO $db;
    private array $baseColumns = [
        'cuenta', 'identificacion', 'nombre', 'saldo', 
        'telefono_1', 'telefono_2', 'id_empresa', 'estado'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

        /**
     * Importa clientes desde archivo XLSX (PhpSpreadsheet)
     * Estructura esperada:
     *   Fila 1: Nombres técnicos (cuenta, nombre, identificacion, saldo, telefono_1, tasa_interes, etc.)
     *   Fila 2: Etiquetas visuales (para referencia humana, se ignora)
     *   Fila 3: Ejemplo de valores (se ignora)
     *   Fila 4+: Datos reales de clientes
     */

    /**
     * Importa clientes desde archivo XLSX (PhpSpreadsheet)
     * Basado en CAMPO CUENTA (llave única por contrato/deuda)
     */

public function importarClientes(string $filePath, int $carteraId, int $uploadedByUserId, array $uploadedByUser = null): array
{
    $this->db->beginTransaction();

    try {

        // 1. Configuración de Cartera y Extras
        $stmt = $this->db->prepare("SELECT * FROM carteras WHERE id = :id");
        $stmt->execute(['id' => $carteraId]);
        $config = $stmt->fetch();

        if (!$config) {
            throw new \Exception("Cartera no encontrada.");
        }

        $stmt = $this->db->prepare("
            SELECT nombre_campo, etiqueta
            FROM extras_cartera
            WHERE id_cartera = :cid
            AND activo = true
        ");

        $stmt->execute(['cid' => $carteraId]);
        $extrasConfig = $stmt->fetchAll();

        // Columnas reales en tabla clientes
        $baseColumns = [
            'cuenta',
            'nombre',
            'identificacion',
            'saldo',
            'telefono_1',
            'telefono_2'
        ];

        // Permitidas en Excel
        $allAllowed = [...$baseColumns, 'gestor_usuario'];

        foreach ($extrasConfig as $e) {
            $allAllowed[] = $e['nombre_campo'];
        }

        // 2. Leer XLSX
        if (!file_exists($filePath)) {
            throw new \Exception("Archivo no encontrado.");
        }

        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        if (empty($rows)) {
            throw new \Exception("El archivo está vacío.");
        }

        // =====================================================
        // 3. ESTRUCTURA FIJA DEL ARCHIVO
        // Línea 1 = encabezados
        // Línea 2 = referencias
        // Línea 3 = ejemplo
        // Línea 4+ = datos reales
        // =====================================================

        $headerRowIndex = 0;

        if (!isset($rows[$headerRowIndex])) {
            throw new \Exception("No se encontró la fila de encabezados.");
        }

        $rawHeaders = $rows[$headerRowIndex];

        // Desde línea 4 (índice 3)
        $dataRows = array_slice($rows, 3);

        // =====================================================
        // 4. MAPEO DE ENCABEZADOS
        // =====================================================

        $normalize = function ($s) {
            $s = trim((string)$s);
            $s = mb_strtolower($s, 'UTF-8');

            // remover acentos
            $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);

            return trim($s);
        };

        $headersMap = [];

        foreach ($rawHeaders as $idx => $h) {

            $hNorm = $normalize($h);

            if (in_array($hNorm, $allAllowed)) {
                $headersMap[$idx] = $hNorm;
            }
        }

        if (empty($headersMap)) {
            throw new \Exception("Encabezados no reconocidos.");
        }

        // Validar obligatorios
        $required = [
            'cuenta',
            'nombre',
            'identificacion',
            'saldo',
            'telefono_1'
        ];

        $missing = array_diff($required, array_values($headersMap));

        if (!empty($missing)) {
            throw new \Exception(
                "Faltan columnas obligatorias: " . implode(', ', $missing)
            );
        }

        // =====================================================
        // 5. PREPARAR UPSERT
        // =====================================================

        $stmtUpsert = $this->db->prepare("
            INSERT INTO clientes (
                id_cartera,
                id_gestor_asignado,
                id_supervisor_cadena,
                cuenta,
                nombre,
                identificacion,
                saldo,
                telefono_1,
                telefono_2,
                data_extras
            ) VALUES (
                :id_cartera,
                :id_gestor_asignado,
                :id_supervisor_cadena,
                :cuenta,
                :nombre,
                :identificacion,
                :saldo,
                :telefono_1,
                :telefono_2,
                :data_extras
            )

            ON CONFLICT (cuenta)

            DO UPDATE SET
                nombre = EXCLUDED.nombre,
                identificacion = EXCLUDED.identificacion,
                saldo = EXCLUDED.saldo,
                telefono_1 = EXCLUDED.telefono_1,
                telefono_2 = EXCLUDED.telefono_2,
                data_extras = EXCLUDED.data_extras,
                id_cartera = EXCLUDED.id_cartera,
                id_gestor_asignado = EXCLUDED.id_gestor_asignado,
                id_supervisor_cadena = EXCLUDED.id_supervisor_cadena,
                fecha_actualizacion = NOW()

            RETURNING id
        ");

        $insertados = 0;
        $errores = [];

        // Inicia en línea 4 del Excel
        $excelRowBase = 4;

        // =====================================================
        // 6. FILTRO DE FILAS BASURA / EJEMPLOS
        // =====================================================

        $junkKeywords = [
            'tarjeta',
            'prestamo',
            'dpi',
            'saldo',
            'celular',
            'email',
            'direccion',
            'gestor asignado',
            'gestor usuario',
            'cuenta',
            'nombre',
            'identificacion',
            'telefono',
            'ejemplo',
            'demo',
            'test',
            'visa-'
        ];

        // =====================================================
        // 7. RECORRER FILAS
        // =====================================================

        foreach ($dataRows as $offset => $row) {

            $excelRow = $excelRowBase + $offset;

            // Saltar filas totalmente vacías
            if (count(array_filter($row)) === 0) {
                continue;
            }

            try {

                $vals = [];

                foreach ($headersMap as $idx => $campo) {

                    $raw = $row[$idx] ?? '';

                    $vals[$campo] = is_scalar($raw)
                        ? trim((string)$raw)
                        : '';
                }

                // =================================================
                // Ignorar filas ejemplo/plantilla
                // =================================================

                $cuentaTest = strtolower($vals['cuenta'] ?? '');

                $isJunk = false;

                foreach ($junkKeywords as $keyword) {

                    if (strpos($cuentaTest, $keyword) !== false) {
                        $isJunk = true;
                        break;
                    }
                }

                if ($isJunk) {
                    continue;
                }

                // =================================================
                // VALIDACIONES
                // =================================================

                if (empty($vals['cuenta'])) {

                    $errores[] = "Línea $excelRow: Campo 'cuenta' vacío.";

                    continue;
                }

                // =================================================
                // RESOLVER GESTOR
                // =================================================

                $idGestor = null;

                if (!empty($vals['gestor_usuario'])) {

                    if (strpos(strtolower($vals['gestor_usuario']), 'gestor') === false) {

                        $stmtG = $this->db->prepare("
                            SELECT id, supervisor_id
                            FROM usuarios
                            WHERE usuario = :u
                            AND activo = true
                            AND rol = 'gestor'
                        ");

                        $stmtG->execute([
                            'u' => $vals['gestor_usuario']
                        ]);

                        $gestor = $stmtG->fetch();

                        if ($gestor) {

                            $idGestor = $gestor['id'];

                        } else {

                            $errores[] = "Línea $excelRow: Gestor '{$vals['gestor_usuario']}' no encontrado";
                        }
                    }
                }

                // =================================================
                // ASIGNACIÓN FINAL
                // =================================================

                $idAsignado = $idGestor
                    ?? (!empty($_POST['id_gestor_asignado'])
                        ? (int)$_POST['id_gestor_asignado']
                        : $uploadedByUserId);

                // Supervisor
                $stmtSuper = $this->db->prepare("
                    SELECT supervisor_id
                    FROM usuarios
                    WHERE id = :id
                ");

                $stmtSuper->execute([
                    'id' => $idAsignado
                ]);

                $superData = $stmtSuper->fetch();

                $idSupervisor = $superData
                    ? $superData['supervisor_id']
                    : null;

                // =================================================
                // DATOS BASE
                // =================================================

                $data = [

                    'id_cartera' => $carteraId,

                    'id_gestor_asignado' => $idAsignado,

                    'id_supervisor_cadena' => $idSupervisor,

                    'cuenta' => strtoupper(
                        trim($vals['cuenta'])
                    ),

                    'nombre' => $vals['nombre'],

                    'identificacion' => (string)$vals['identificacion'],

                    'saldo' => floatval(
                        preg_replace(
                            '/[^0-9.]/',
                            '',
                            $vals['saldo'] ?? '0'
                        )
                    ),

                    'telefono_1' => $vals['telefono_1'],

                    'telefono_2' => !empty($vals['telefono_2'])
                        ? $vals['telefono_2']
                        : null,
                ];

                // =================================================
                // EXTRAS DINÁMICOS -> JSON
                // =================================================

                $extras = [];

                $basePlusGestor = [
                    'cuenta',
                    'nombre',
                    'identificacion',
                    'saldo',
                    'telefono_1',
                    'telefono_2',
                    'gestor_usuario'
                ];

                foreach ($vals as $k => $v) {

                    if (
                        !in_array($k, $basePlusGestor)
                        && $v !== ''
                    ) {
                        $extras[$k] = $v;
                    }
                }

                $data['data_extras'] = !empty($extras)
                    ? json_encode($extras, JSON_UNESCAPED_UNICODE)
                    : null;

                // =================================================
                // UPSERT
                // =================================================

                $stmtUpsert->execute($data);

                $insertados++;

            } catch (\Exception $e) {

                $errores[] = "Línea $excelRow: " . $e->getMessage();
            }
        }

        // =====================================================
        // FINALIZAR
        // =====================================================

        $this->db->commit();

        return [
            'success' => true,
            'insertados' => $insertados,
            'errores' => $errores,
            'total_errores' => count($errores)
        ];

    } catch (\Exception $e) {

        $this->db->rollBack();

        return [
            'success' => false,
            'errores' => [
                "Error crítico: " . $e->getMessage()
            ]
        ];
    }
}    
    
    private function prepareInsertCliente(): \PDOStatement
    {
        $sql = "INSERT INTO clientes (id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, nombre, saldo, telefono_1, telefono_2, id_empresa, estado) 
                VALUES (:id_cartera, :id_gestor_asignado, :id_supervisor_cadena, :cuenta, :identificacion, :nombre, :saldo, :telefono_1, :telefono_2, :id_empresa, :estado)";
        return $this->db->prepare($sql);
    }

    private function prepareInsertExtra(): \PDOStatement
    {
        $sql = "INSERT INTO data_extras (id_cliente, id_extra, valor) VALUES (:id_cliente, :id_extra, :valor)";
        return $this->db->prepare($sql);
    }
}