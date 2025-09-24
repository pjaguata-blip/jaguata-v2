<?php
namespace Jaguata\Services;

use Jaguata\Services\DatabaseService;
use PDO;

class CalificacionService {
    private $db;

    public function __construct() {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * Obtener todas las calificaciones de un paseo
     */
    public function getByPaseo($paseoId) {
        $stmt = $this->db->prepare("
            SELECT * FROM calificaciones 
            WHERE paseo_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$paseoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener promedio de calificaciones de un usuario (ej: un paseador)
     */
    public function getPromedioByUsuario($usuarioId) {
        $stmt = $this->db->prepare("
            SELECT 
                AVG(calificacion) as promedio, 
                COUNT(*) as total 
            FROM calificaciones 
            WHERE rated_id = ?
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todas las calificaciones hechas por un usuario
     */
    public function getHistorialByUsuario($usuarioId) {
        $stmt = $this->db->prepare("
            SELECT * FROM calificaciones 
            WHERE rater_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener estadísticas de calificaciones de un usuario
     */
    public function getEstadisticasByUsuario($usuarioId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN calificacion = 5 THEN 1 ELSE 0 END) as cinco_estrellas,
                SUM(CASE WHEN calificacion = 4 THEN 1 ELSE 0 END) as cuatro_estrellas,
                SUM(CASE WHEN calificacion = 3 THEN 1 ELSE 0 END) as tres_estrellas,
                SUM(CASE WHEN calificacion = 2 THEN 1 ELSE 0 END) as dos_estrellas,
                SUM(CASE WHEN calificacion = 1 THEN 1 ELSE 0 END) as una_estrella
            FROM calificaciones
            WHERE rated_id = ?
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
