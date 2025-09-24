<?php
namespace Jaguata\Services;

use PDO;

class NotificacionService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    /**
     * Obtener notificaciones de un usuario
     */
    public function getNotificacionesByUsuario(int $usuarioId, ?string $leido = null, int $limite = 20): array
    {
        $sql = "SELECT * FROM notificaciones WHERE usu_id = :usu_id";
        $params = [':usu_id' => $usuarioId];

        if ($leido !== null) {
            $sql .= " AND leido = :leido";
            $params[':leido'] = (int)$leido;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usu_id', $usuarioId, PDO::PARAM_INT);
        if ($leido !== null) {
            $stmt->bindValue(':leido', (int)$leido, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener notificaciones no leídas (para Navbar y badge)
     */
    public function getNotificacionesNoLeidas(int $usuarioId, int $limite = 5): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * 
                FROM notificaciones 
                WHERE usu_id = :usu_id AND leido = 0 
                ORDER BY created_at DESC 
                LIMIT :limite
            ");
            $stmt->bindValue(':usu_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'total'   => count($notificaciones),
                'notificaciones' => $notificaciones
            ];
        } catch (\Exception $e) {
            error_log("Error obteniendo notificaciones no leídas: " . $e->getMessage());
            return [
                'success' => false,
                'total'   => 0,
                'notificaciones' => []
            ];
        }
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE notificaciones SET leido = 1 WHERE noti_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasComoLeidas(int $usuarioId): bool
    {
        $stmt = $this->db->prepare("UPDATE notificaciones SET leido = 1 WHERE usu_id = :usu_id");
        return $stmt->execute([':usu_id' => $usuarioId]);
    }

    /**
     * Obtener contador de no leídas
     */
    public function getContadorNoLeidas(int $usuarioId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notificaciones WHERE usu_id = :usu_id AND leido = 0");
        $stmt->execute([':usu_id' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtener notificaciones recientes
     */
    public function getNotificacionesRecientes(int $usuarioId, int $limite = 5): array
    {
        $stmt = $this->db->prepare("SELECT * FROM notificaciones WHERE usu_id = :usu_id ORDER BY created_at DESC LIMIT :limite");
        $stmt->bindValue(':usu_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function getEstadisticas(int $usuarioId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN leido = 0 THEN 1 ELSE 0 END) AS no_leidas,
                SUM(CASE WHEN leido = 1 THEN 1 ELSE 0 END) AS leidas
            FROM notificaciones
            WHERE usu_id = :usu_id
        ");
        $stmt->execute([':usu_id' => $usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
