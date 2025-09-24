<?php
namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;

class Pago {
    private $db;

    public function __construct() {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function all() {
        $stmt = $this->db->query("SELECT * FROM pagos ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM pagos WHERE pago_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO pagos 
            (paseo_id, dueno_id, paseador_id, monto, tarifa, ganancia_paseador, metodo_id, currency) 
            VALUES (:paseo_id, :dueno_id, :paseador_id, :monto, :tarifa, :ganancia_paseador, :metodo_id, :currency)
        ");
        $stmt->execute([
            ':paseo_id' => $data['paseo_id'],
            ':dueno_id' => $data['dueno_id'],
            ':paseador_id' => $data['paseador_id'],
            ':monto' => $data['monto'],
            ':tarifa' => $data['tarifa'],
            ':ganancia_paseador' => $data['ganancia_paseador'],
            ':metodo_id' => $data['metodo_id'],
            ':currency' => $data['currency']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE pagos 
            SET estado = :estado, processed_at = NOW() 
            WHERE pago_id = :id
        ");
        return $stmt->execute([
            ':estado' => $data['estado'] ?? 'procesado',
            ':id' => $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM pagos WHERE pago_id = ?");
        return $stmt->execute([$id]);
    }
}
