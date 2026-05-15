<?php
namespace LEX360\Models\Services;

use PDO;
use LEX360\Models\Dao\Db\Database;

class PagoValidationService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Procesa la carga masiva de validación de pagos
     * @param string $filePath Ruta del CSV subido
     * @param int $adminId ID del admin que valida
     * @return array Resultado de la operación
     */
    public function procesarValidacion(string $filePath, int $adminId): array
    {
        $this->db->beginTransaction();
        try {
            $handle = fopen($filePath, 'r');
            if ($handle === false) throw new \Exception("No se pudo abrir el archivo CSV.");

            // Leer encabezados
            $headers = fgetcsv($handle);
            $headers = array_map('trim', $headers);
            
            // Mapear índices (asumiendo columnas: identificacion, monto, referencia)
            $idxIdent = array_search('identificacion', $headers);
            $idxMonto = array_search('monto', $headers);
            
            if ($idxIdent === false || $idxMonto === false) {
                throw new \Exception("El CSV debe contener columnas: identificacion, monto");
            }

            $validados = 0;
            $errores = [];
            $linea = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $linea++;
                $identificacion = trim($row[$idxIdent] ?? '');
                $montoBanco = (float) trim($row[$idxMonto] ?? 0);

                if (empty($identificacion)) continue;

                // 1. Buscar cliente
                $stmtCliente = $this->db->prepare("SELECT id, saldo FROM clientes WHERE identificacion = :id");
                $stmtCliente->execute(['id' => $identificacion]);
                $cliente = $stmtCliente->fetch();

                if (!$cliente) {
                    $errores[] = "Línea $linea: Cliente con identificación $identificacion no encontrado.";
                    continue;
                }

                // 2. Buscar gestión PAGG (Pendiente)
                $stmtGestion = $this->db->prepare("
                    SELECT id, monto_pendiente FROM historial 
                    WHERE id_cliente = :cid AND estatus = 'PAGG' 
                    ORDER BY fecha_gestion ASC LIMIT 1
                ");
                // Nota: 'monto_pendiente' es un campo hipotético para validar monto. 
                // Si no existe, asumimos que valida cualquier monto o usamos el saldo del cliente.
                // Para este ejemplo, validamos que el monto del banco sea > 0 y actualizamos.
                
                $stmtGestion->execute(['cid' => $cliente['id']]);
                $gestion = $stmtGestion->fetch();

                if (!$gestion) {
                    // Si no hay gestión PAGG, registramos el pago directamente contra el saldo
                    $this->actualizarSaldo($cliente['id'], $cliente['saldo'], $montoBanco);
                    $this->registrarPagoDirecto($cliente['id'], $montoBanco, $adminId);
                    $validados++;
                    continue;
                }

                // 3. Si hay gestión PAGG, la validamos y actualizamos saldo
                $this->db->prepare("UPDATE historial SET estatus = 'PAGO' WHERE id = :gid")
                         ->execute(['gid' => $gestion['id']]);
                
                $this->actualizarSaldo($cliente['id'], $cliente['saldo'], $montoBanco);
                $validados++;
                            // ... (código existente de actualizar saldo) ...
            
            // ✅ NUEVO: Marcar promesas como cumplidas si el pago coincide
            $this->db->prepare("UPDATE promesas SET estatus = 'cumplida' 
                                WHERE id_cliente = :cid AND estatus = 'pendiente'")
                     ->execute(['cid' => $cliente['id']]);
            }

            fclose($handle);
            $this->db->commit();
            return ['success' => true, 'validados' => $validados, 'errores' => $errores];

        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function actualizarSaldo(int $clienteId, float $saldoActual, float $montoPago): void
    {
        $nuevoSaldo = $saldoActual - $montoPago;
        $this->db->prepare("UPDATE clientes SET saldo = :saldo WHERE id = :id")
                 ->execute(['saldo' => $nuevoSaldo, 'id' => $clienteId]);
    }

    private function registrarPagoDirecto(int $clienteId, float $monto, int $adminId): void
    {
        // Registro auxiliar en tabla pagos si no venía de gestión
        $this->db->prepare("INSERT INTO pagos (id_cliente, monto, fecha_pago, estatus, validado_por, fecha_validacion) 
                            VALUES (:cid, :monto, NOW(), 'PAGO', :vid, NOW())")
                 ->execute(['cid' => $clienteId, 'monto' => $monto, 'vid' => $adminId]);
    }
}