<?php

declare(strict_types=1);

namespace Jaguata\Controllers;

use Jaguata\Models\Notificacion;
use Jaguata\Helpers\Session;

// ⛑️ Fallback: por si el autoload aún no cargó el modelo
if (!class_exists(Notificacion::class)) {
    require_once __DIR__ . '/../Models/Notificacion.php';
}

class NotificacionController
{
    private Notificacion $model;

    public function __construct()
    {
        $this->model = new Notificacion();
    }

    private function currentUserId(): int
    {
        $id = Session::get('user_id') ?? Session::get('usu_id') ?? Session::get('usuario_id') ?? null;
        if (!$id) {
            throw new \RuntimeException('Usuario no autenticado');
        }
        return (int) $id;
    }

    /** Notificaciones recientes */
    public function getRecientes(int $limit = 5): array
    {
        $usuId = $this->currentUserId();
        return $this->model->getRecientes($usuId, $limit);
    }

    /** Lista filtrada */
    public function index(array $query): array
    {
        $usuId   = $this->currentUserId();
        $page    = max(1, (int)($query['page'] ?? 1));
        $perPage = min(50, max(5, (int)($query['perPage'] ?? 10)));
        $leido   = isset($query['leido']) && $query['leido'] !== '' ? (int)$query['leido'] : null;
        $search  = trim((string)($query['q'] ?? '')) ?: null;
        $tipo    = trim((string)($query['tipo'] ?? '')) ?: null;

        return $this->model->listByUser($usuId, $leido, $search, $page, $perPage, $tipo);
    }

    /** Marcar una como leída */
    public function markRead(array $post): bool
    {
        $usuId  = $this->currentUserId();
        $notiId = (int)($post['noti_id'] ?? 0);
        if ($notiId <= 0) return false;
        return $this->model->markRead($notiId, $usuId);
    }

    /** Marcar todas como leídas */
    public function markAllRead(): int
    {
        $usuId = $this->currentUserId();
        return $this->model->markAllRead($usuId);
    }

    /** Contar no leídas */
    public function unreadCount(): int
    {
        $usuId = $this->currentUserId();
        return $this->model->countUnread($usuId);
    }

    public function crear(): array
    {
        try {
            $usuId   = (int)($_POST['usu_id'] ?? 0);
            $tipo    = trim($_POST['tipo'] ?? 'general');
            $titulo  = trim($_POST['titulo'] ?? '');
            $mensaje = trim($_POST['mensaje'] ?? '');
            $paseoId = (int)($_POST['paseo_id'] ?? 0);

            if ($usuId <= 0 || $titulo === '' || $mensaje === '') {
                return ['error' => 'Datos insuficientes para crear la notificación'];
            }

            $db = \Jaguata\Services\DatabaseService::getInstance()->getConnection();

            $sql = "INSERT INTO notificaciones 
                    (usu_id, tipo, titulo, mensaje, paseo_id, leido, created_at)
                VALUES 
                    (:usu_id, :tipo, :titulo, :mensaje, :paseo_id, 0, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':usu_id'   => $usuId,
                ':tipo'     => $tipo,
                ':titulo'   => $titulo,
                ':mensaje'  => $mensaje,
                ':paseo_id' => $paseoId > 0 ? $paseoId : null
            ]);

            return [
                'success' => true,
                'id'      => (int)$db->lastInsertId()
            ];
        } catch (\Throwable $e) {
            error_log('❌ Error al crear notificación: ' . $e->getMessage());
            return ['error' => 'Error interno al crear la notificación'];
        }
    }
}
