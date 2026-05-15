<?php
namespace LEX360\Models\Dao;
use LEX360\Core\BaseDao;

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
            if (!$pago || $pago['estatus'] !== 'PAGG') throw new \Exception("Pago no válido o ya procesado.");

            $this->db->prepare("UPDATE pagos SET estatus = 'PAGO', validado_por = :vid, fecha_validacion = NOW() WHERE id = :pid")
                     ->execute(['vid' => $validadorId, 'pid' => $pagoId]);

            $this->db->prepare("UPDATE clientes SET saldo = saldo - :monto WHERE id = :cid")
                     ->execute(['monto' => $pago['monto'], 'cid' => $pago['id_cliente']]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}