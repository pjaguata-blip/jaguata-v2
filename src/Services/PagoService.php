<?php
namespace Jaguata\Services;

use Jaguata\Services\DatabaseService;
use PDO;

class PagoService {
    private $db;

    public function __construct() {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function getByUsuario($usuarioId, $rol = 'dueno') {
        $campo = $rol === 'paseador' ? 'paseador_id' : 'dueno_id';
        $stmt = $this->db->prepare("SELECT * FROM pagos WHERE {$campo} = ? ORDER BY created_at DESC");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGananciasByPaseador($paseadorId) {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(ganancia_paseador) as total_ganado,
                COUNT(*) as total_pagos
            FROM pagos
            WHERE paseador_id = ? AND estado = 'procesado'
        ");
        $stmt->execute([$paseadorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEstadisticas() {
        $stmt = $this->db->query("
            SELECT estado, COUNT(*) as total, SUM(monto) as monto_total
            FROM pagos
            GROUP BY estado
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
