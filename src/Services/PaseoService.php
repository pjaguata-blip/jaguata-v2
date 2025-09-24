<?php
namespace Jaguata\Services;

use Jaguata\Services\DatabaseService;
use PDO;

class PaseoService {
    private $db;

    public function __construct() {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function getByPaseador($paseadorId, $estado = null) {
        $sql = "SELECT * FROM paseos WHERE paseador_id = :paseadorId";
        $params = [':paseadorId' => $paseadorId];

        if ($estado) {
            $sql .= " AND estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY inicio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDueno($duenoId, $estado = null) {
        $sql = "
            SELECT p.* 
            FROM paseos p 
            INNER JOIN mascotas m ON p.mascota_id = m.mascota_id 
            WHERE m.dueno_id = :duenoId
        ";
        $params = [':duenoId' => $duenoId];

        if ($estado) {
            $sql .= " AND p.estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY p.inicio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstadisticasByPaseador($paseadorId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado='completo' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado='cancelado' THEN 1 ELSE 0 END) as cancelados
            FROM paseos
            WHERE paseador_id = ?
        ");
        $stmt->execute([$paseadorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
