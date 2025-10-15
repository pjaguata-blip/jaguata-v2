<?php

namespace Jaguata\Services;

use PDO;

class NotificacionService
{
    private PDO $db;

    public function __construct()
    {
        // Mantengo compatibilidad con tu forma actual
        $this->db = $GLOBALS['db'];
    }

    /* ================== Helpers internos ================== */

    private function clampLimit(int $limite): int
    {
        // evita LIMIT 0 o absurdos
        return max(1, min($limite, 100));
    }

    private function normOffset(?int $offset): int
    {
        return max(0, (int)($offset ?? 0));
    }

    private function normLeido($leido): ?int
    {
        if ($leido === null || $leido === '') return null;
        return ((int)$leido === 1) ? 1 : 0;
    }

    /* ================== Lecturas ================== */

    /**
     * Listado por usuario, con filtros y paginación
     * @param int $usuarioId
     * @param string|int|null $leido  0|1|null
     * @param int $limite
     * @param int|null $offset
     */
    public function getNotificacionesByUsuario(int $usuarioId, $leido = null, int $limite = 20, ?int $offset = 0): array
    {
        try {
            $leido = $this->normLeido($leido);
            $limite = $this->clampLimit($limite);
            $offset = $this->normOffset($offset);

            $sql = "SELECT * FROM notificaciones WHERE usu_id = :usu_id";
            if ($leido !== null) {
                $sql .= " AND leido = :leido";
            }
            $sql .= " ORDER BY created_at DESC LIMIT :limite OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':usu_id', $usuarioId, PDO::PARAM_INT);
            if ($leido !== null) {
                $stmt->bindValue(':leido', $leido, PDO::PARAM_INT);
            }
            // IMPORTANTE: bind de LIMIT/OFFSET como enteros
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'count'   => count($items),
                'items'   => $items,
                'meta'    => ['limit' => $limite, 'offset' => $offset]
            ];
        } catch (\Exception $e) {
            error_log("Error getNotificacionesByUsuario: " . $e->getMessage());
            return ['success' => false, 'count' => 0, 'items' => [], 'meta' => ['limit' => $limite ?? null, 'offset' => $offset ?? null]];
        }
    }

    /**
     * No leídas para badge/navbar
     */
    public function getNotificacionesNoLeidas(int $usuarioId, int $limite = 5): array
    {
        try {
            $limite = $this->clampLimit($limite);

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
            error_log("Error obteniendo no leídas: " . $e->getMessage());
            return ['success' => false, 'total' => 0, 'notificaciones' => []];
        }
    }

    /**
     * Recientes (independiente de leídas/no)
     */
    public function getNotificacionesRecientes(int $usuarioId, int $limite = 5): array
    {
        try {
            $limite = $this->clampLimit($limite);
            $stmt = $this->db->prepare("
                SELECT * FROM notificaciones
                WHERE usu_id = :usu_id
                ORDER BY created_at DESC
                LIMIT :limite
            ");
            $stmt->bindValue(':usu_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getNotificacionesRecientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contador de no leídas
     */
    public function getContadorNoLeidas(int $usuarioId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM notificaciones
                WHERE usu_id = :usu_id AND leido = 0
            ");
            $stmt->execute([':usu_id' => $usuarioId]);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            error_log("Error getContadorNoLeidas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Estadísticas
     */
    public function getEstadisticas(int $usuarioId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN leido = 0 THEN 1 ELSE 0 END) AS no_leidas,
                    SUM(CASE WHEN leido = 1 THEN 1 ELSE 0 END) AS leidas
                FROM notificaciones
                WHERE usu_id = :usu_id
            ");
            $stmt->execute([':usu_id' => $usuarioId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'no_leidas' => 0, 'leidas' => 0];
        } catch (\Exception $e) {
            error_log("Error getEstadisticas: " . $e->getMessage());
            return ['total' => 0, 'no_leidas' => 0, 'leidas' => 0];
        }
    }

    /* ================== Escrituras ================== */

    /**
     * Marcar UNA notificación como leída, verificando pertenencia.
     * (Evita marcar notificaciones de otro usuario)
     */
    public function marcarComoLeida(int $id, ?int $usuarioId = null): bool
    {
        try {
            if ($usuarioId !== null) {
                $stmt = $this->db->prepare("
                    UPDATE notificaciones
                    SET leido = 1
                    WHERE noti_id = :id AND usu_id = :usu_id
                ");
                return $stmt->execute([':id' => $id, ':usu_id' => $usuarioId]);
            }
            // Mantener compatibilidad si no se pasa usuarioId (pero es recomendable pasarlo)
            $stmt = $this->db->prepare("
                UPDATE notificaciones SET leido = 1 WHERE noti_id = :id
            ");
            return $stmt->execute([':id' => $id]);
        } catch (\Exception $e) {
            error_log("Error marcarComoLeida: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marcar TODAS como leídas del usuario
     */
    public function marcarTodasComoLeidas(int $usuarioId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE notificaciones SET leido = 1 WHERE usu_id = :usu_id
            ");
            return $stmt->execute([':usu_id' => $usuarioId]);
        } catch (\Exception $e) {
            error_log("Error marcarTodasComoLeidas: " . $e->getMessage());
            return false;
        }
    }
}
