<?php
namespace LEX360\Models\Services;
use PDO;
use LEX360\Models\Dao\Db\Database;
use PhpOffice\PhpSpreadsheet\IOFactory; // ← Requerido para XLSX

class GestionMasivaService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Importa gestiones desde archivo XLSX (PhpSpreadsheet)
     * Estructura esperada en el Excel:
     *   Fila 1: Encabezados → cuenta, tipologia, telefono, comentario, fecha_gestion
     *   Filas 2+: Datos reales de gestiones
     */

    public function importarGestiones(string $filePath, int $adminId): array
    {
        $this->db->beginTransaction();

        try {

            // =========================================================
            // 1. VALIDAR ARCHIVO
            // =========================================================
            if (!file_exists($filePath)) {
                throw new \Exception("Archivo no encontrado: $filePath");
            }

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            // false = índices numéricos
            $rows = $sheet->toArray(null, true, true, false);

            if (empty($rows) || count($rows) < 2) {
                throw new \Exception("El archivo está vacío o no tiene datos.");
            }

            // =========================================================
            // 2. LEER ENCABEZADOS
            // =========================================================
            $rawHeaders = array_map(fn($h) => trim((string)$h), array_shift($rows));

            // normalizar encabezados
            $headersNorm = array_map(
                fn($h) => strtolower(trim($h)),
                $rawHeaders
            );

            $map = [
                'cuenta'     => array_search('cuenta', $headersNorm),
                'tipologia'  => array_search('id_tipologia', $headersNorm),
                'telefono'   => array_search('telefono', $headersNorm),
                'comentario' => array_search('comentario', $headersNorm),
                'proxima'    => array_search('fecha_proxima_llamada', $headersNorm),
                'fecha'      => array_search('fecha_gestion', $headersNorm),
            ];

            // =========================================================
            // 3. VALIDAR COLUMNAS OBLIGATORIAS
            // =========================================================
            $obligatorias = ['cuenta'];

            foreach ($obligatorias as $campo) {
                if ($map[$campo] === false) {
                    throw new \Exception("Falta columna obligatoria: '$campo'");
                }
            }

            // =========================================================
            // 4. PREPARAR QUERIES
            // =========================================================

            // Buscar cliente
            $stmtCliente = $this->db->prepare("
                SELECT id, id_gestor_asignado
                FROM clientes
                WHERE cuenta = :cuenta
                LIMIT 1
            ");

            // Buscar tipología
            $stmtTipo = $this->db->prepare("
                SELECT id, nombre
                FROM tipologias
                WHERE id = :id
                LIMIT 1
            ");

            // Insert historial
            $stmtInsert = $this->db->prepare("
                INSERT INTO historial (
                    id_cliente,
                    id_usuario,
                    fecha_gestion,
                    telefono_utilizado,
                    id_tipologia,
                    comentario,
                    fecha_proxima_llamada,
                    estatus
                ) VALUES (
                    :cid,
                    :uid,
                    :fecha,
                    :tel,
                    :tipo,
                    :com,
                    :prox,
                    'SINC'
                )
            ");

            // Update cliente
            $stmtUpdate = $this->db->prepare("
                UPDATE clientes
                SET fecha_ultima_gestion = :fecha
                WHERE id = :id
            ");

            // =========================================================
            // 5. VARIABLES DE CONTROL
            // =========================================================
            $insertados = 0;
            $errores = [];

            // Excel empieza en fila 2
            $excelRow = 2;

            // =========================================================
            // 6. RECORRER FILAS
            // =========================================================
            foreach ($rows as $row) {

                // -----------------------------------------------------
                // Ignorar filas vacías
                // -----------------------------------------------------
                $filaVacia = count(
                    array_filter(
                        $row,
                        fn($v) => trim((string)($v ?? '')) !== ''
                    )
                ) === 0;

                if ($filaVacia) {
                    $excelRow++;
                    continue;
                }

                try {

                    // -------------------------------------------------
                    // EXTRAER DATOS
                    // -------------------------------------------------
                    $cuenta = trim((string)($row[$map['cuenta']] ?? ''));
                    $id_tipologia = trim((string)($row[$map['tipologia']] ?? ''));
                    $telefono = trim((string)($row[$map['telefono']] ?? ''));
                    $comentario = trim((string)($row[$map['comentario']] ?? ''));
                    $proxima = null;

                    if ($map['proxima'] !== false) {

                        $proximaRaw = $row[$map['proxima']] ?? null;

                        if ($proximaRaw instanceof \DateTimeInterface) {

                            $proxima = $proximaRaw->format('Y-m-d H:i:s');

                        } elseif (is_numeric($proximaRaw)) {

                            $proxima = \PhpOffice\PhpSpreadsheet\Shared\Date
                                ::excelToDateTimeObject($proximaRaw)
                                ->format('Y-m-d H:i:s');

                        } elseif (!empty($proximaRaw)) {

                            $timestamp = strtotime($proximaRaw);

                            if ($timestamp !== false) {
                                $proxima = date('Y-m-d H:i:s', $timestamp);
                            }
                        }
                    }

                    // -------------------------------------------------
                    // FECHA
                    // -------------------------------------------------
                    $fecha = date('Y-m-d H:i:s');

                    if ($map['fecha'] !== false) {

                        $fechaRaw = $row[$map['fecha']] ?? null;

                        if ($fechaRaw instanceof \DateTimeInterface) {

                            $fecha = $fechaRaw->format('Y-m-d H:i:s');

                        } elseif (is_numeric($fechaRaw)) {

                            $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date
                                ::excelToDateTimeObject($fechaRaw)
                                ->format('Y-m-d H:i:s');

                        } elseif (!empty($fechaRaw)) {

                            $fecha = date(
                                'Y-m-d H:i:s',
                                strtotime($fechaRaw)
                            );
                        }
                    }

                    // -------------------------------------------------
                    // VALIDACIONES
                    // -------------------------------------------------
                    if (empty($cuenta)) {
                        $errores[] = "Fila $excelRow: Cuenta vacía.";
                        $excelRow++;
                        throw new \Exception("Fila $excelRow: Cuenta vacía.");
                        continue;
                    }

                    // -------------------------------------------------
                    // BUSCAR CLIENTE
                    // -------------------------------------------------
                    $stmtCliente->execute([
                        'cuenta' => $cuenta
                    ]);

                    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

                    if (!$cliente) {
                        $errores[] = "Fila $excelRow: Cuenta '$cuenta' no encontrada.";
                        $excelRow++;
                        throw new \Exception("Fila $excelRow: Cuenta '$cuenta' no encontrada.");
                        continue;
                    }


                    // -------------------------------------------------
                    // VALIDAR TIPOLOGÍA
                    // -------------------------------------------------
                    $tipo = false;

                    if (!empty($id_tipologia)) {

                        $stmtTipo->execute([
                            'id' => $id_tipologia
                        ]);

                        $tipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);
                    }

                    if (!$tipo) {
                        $errores[] = "Fila $excelRow: Tipología '$id_tipologia' no existe.";
                        $excelRow++;
                        throw new \Exception("Tipología $id_tipologia no existe");
                        continue;
                    }

                    $parametros_gestion=[
                        'cid'   => $cliente['id'],
                        'uid'   => $cliente['id_gestor_asignado'],
                        'fecha' => $fecha,
                        'tel'   => $telefono,
                        'tipo'  => $id_tipologia,
                        'com'   => $comentario,
                        'prox'  => !empty($proxima) ? $proxima : null
                        ];

                    //print_r($parametros_gestion);
                    //die();

                    // -------------------------------------------------
                    // INSERTAR HISTORIAL
                    // -------------------------------------------------
                    $stmtInsert->execute($parametros_gestion);

                    // -------------------------------------------------
                    // ACTUALIZAR CLIENTE
                    // -------------------------------------------------
                    $stmtUpdate->execute([
                        'fecha' => $fecha,
                        'id'    => $cliente['id']
                    ]);

                    $insertados++;

                } catch (\Exception $e) {

                    $errores[] = "Fila $excelRow: " . $e->getMessage();
                }

                $excelRow++;
            }

            // =========================================================
            // 7. COMMIT
            // =========================================================
            $this->db->commit();

            //print_r($errores);//desplegar errores en caso de fallo
            //die();//Parar el programa para ver errores

            return [
                'success'        => true,
                'insertados'     => $insertados,
                'errores'        => $errores,
                'total_errores'  => count($errores)
            ];


        } catch (\Exception $e) {

            $this->db->rollBack();

            error_log(
                "[LEX360] importarGestiones XLSX: " . $e->getMessage()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'errores' => [$e->getMessage()]
            ];
        }
    }    
}


