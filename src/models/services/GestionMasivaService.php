<?php
namespace LEX360\Models\Services;

use PDO;
use LEX360\Models\Dao\Db\Database;

class GestionMasivaService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function importarGestiones(string $filePath, int $adminId): array
    {
        $this->db->beginTransaction();
        try {
            $handle = fopen($filePath, 'r');
            if ($handle === false) throw new \Exception("No se pudo abrir el archivo.");

            // Leer encabezados y limpiar espacios
            $headers = array_map('trim', fgetcsv($handle));

            // Mapeo de columnas esperado
            $map = [
                'cuenta'      => array_search('cuenta', $headers),
                'tipologia'   => array_search('tipologia', $headers),
                'telefono'    => array_search('telefono', $headers),
                'comentario'  => array_search('comentario', $headers),
                'fecha'       => array_search('fecha_gestion', $headers)
            ];

            // Validar que exista la columna clave 'cuenta'
            if ($map['cuenta'] === false) {
                throw new \Exception("Falta columna obligatoria: 'cuenta'.");
            }

            $insertados = 0;
            $errores = [];
            $linea = 1;

            // Preparar statement para buscar tipología (cache implícito)
            $stmtTipo = $this->db->prepare("SELECT id FROM tipologias WHERE LOWER(nombre) = LOWER(:nombre) LIMIT 1");

            while (($row = fgetcsv($handle)) !== false) {
                $linea++;
                
                $cuenta = trim($row[$map['cuenta']] ?? '');
                $tipologiaNombre = trim($row[$map['tipologia']] ?? '');
                $telefono = trim($row[$map['telefono']] ?? '');
                $comentario = trim($row[$map['comentario']] ?? '');
                $fecha = trim($row[$map['fecha']] ?? date('Y-m-d H:i:s'));

                if (empty($cuenta)) continue; // Saltar filas vacías

                // 1. Buscar Cliente por CUENTA
                $stmtCli = $this->db->prepare("SELECT id FROM clientes WHERE cuenta = :cuenta LIMIT 1");
                $stmtCli->execute(['cuenta' => $cuenta]);
                $cliente = $stmtCli->fetch();

                if (!$cliente) {
                    $errores[] = "Línea $linea: Cuenta '$cuenta' no encontrada.";
                    continue;
                }

                // 2. Buscar Tipología
                $stmtTipo->execute(['nombre' => $tipologiaNombre]);
                $tipo = $stmtTipo->fetch();
                $tipologiaId = $tipo ? $tipo['id'] : null;

                // 3. Insertar Gestión
                $sql = "INSERT INTO historial (
                            id_cliente, id_usuario, fecha_gestion, 
                            telefono_utilizado, id_tipologia, comentario, estatus
                        ) VALUES (
                            :cid, :uid, :fecha, :tel, :tipo, :com, 'SINC'
                        )";
                
                $this->db->prepare($sql)->execute([
                    'cid'   => $cliente['id'],
                    'uid'   => $adminId, 
                    'fecha' => $fecha,
                    'tel'   => $telefono,
                    'tipo'  => $tipologiaId,
                    'com'   => $comentario
                ]);

                // 4. Actualizar fecha última gestión del cliente
                $this->db->prepare("UPDATE clientes SET fecha_ultima_gestion = :fecha WHERE id = :id")
                    ->execute(['fecha' => $fecha, 'id' => $cliente['id']]);

                $insertados++;
            }

            fclose($handle);
            $this->db->commit();
            return ['success' => true, 'insertados' => $insertados, 'errores' => $errores];

        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}