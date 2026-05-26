<?php
namespace LEX360\Models\Dao;
use Exception;
use LEX360\Core\BaseDao;
use PDO;
class PagoDao extends BaseDao
{
    protected string $table = 'pagos';

    public function findPendientes(): array
    {
        return $this->db->query("SELECT p.*, c.nombre as cliente_nombre, c.identificacion 
                                 FROM pagos p 
                                 JOIN clientes c ON p.id_cliente = c.id 
                                 WHERE p.estatus = 'PAGG' 
                                 ORDER BY p.fecha_pago DESC")->fetchAll();
    }

    public function confirmarPago(int $pagoId, int $validadorId): bool
    {
        $this->db->beginTransaction();
        try {
            $pago = $this->findById($pagoId);
            if (!$pago || $pago['estatus'] !== 'PAGG') throw new Exception("Pago no válido o ya procesado.");

            $this->db->prepare("UPDATE pagos SET estatus = 'PAGO', validado_por = :vid, fecha_validacion = NOW() WHERE id = :pid")
                     ->execute(['vid' => $validadorId, 'pid' => $pagoId]);

            $this->db->prepare("UPDATE clientes SET saldo = saldo - :monto WHERE id = :cid")
                     ->execute(['monto' => $pago['monto'], 'cid' => $pago['id_cliente']]);
            if($this->getSaldoCliente($this->db,$pago['id_cliente'])<=0){
                if(!$this->estadoPagado($this->db,$pago['id_cliente'])){
                    throw new Exception("No se pudo cambiar estado de cliente a pagado.");
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    private function estadoPagado(PDO $db, int $id):bool{

        $ok = $db->prepare("UPDATE clientes set estado = 'pagado' WHERE id = :cid")
        ->execute(['cid'=>$id]);
        
        return $ok;

    }
    private function getSaldoCliente(PDO $db, int $id):int{
        $stmt = $db->prepare("
            SELECT saldo 
            FROM clientes 
            WHERE id = :cid
        ");

        $stmt->execute([
            'cid' => $id
        ]);

        return $stmt->fetchColumn();    
    }
}