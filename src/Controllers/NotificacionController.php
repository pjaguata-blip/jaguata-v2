<?php

namespace Jaguata\Controllers;

use Jaguata\Services\DatabaseService;
use Jaguata\Helpers\Session;
use PDO;
use Exception;

/**
 * Controlador de notificaciones del sistema
 * Maneja creación, lectura, actualización y eliminación
 */
class NotificacionController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * 🔹 Listar todas las notificaciones (para admin)
     */
    public function index(array $filters): array
    {
        $db = \Jaguata\Services\DatabaseService::connection();

        $usuarioId = \Jaguata\Helpers\Session::getUsuarioId(); // ✅ dueño logueado
        $q = trim($filters['q'] ?? '');
        $leido = $filters['leido'] ?? '';
        $page = max(1, (int)($filters['page'] ?? 1));
        $perPage = (int)($filters['perPage'] ?? 10);
        $offset = ($page - 1) * $perPage;

        $where = "usu_id = :usuarioId";
        $params = ['usuarioId' => $usuarioId];

        if ($q !== '') {
            $where .= " AND (titulo LIKE :q OR mensaje LIKE :q)";
            $params['q'] = "%$q%";
        }
        if ($leido !== '') {
            $where .= " AND leido = :leido";
            $params['leido'] = (int)$leido;
        }

        // 🔹 Total de registros
        $totalSql = "SELECT COUNT(*) FROM notificaciones WHERE $where";
        $stmt = $db->prepare($totalSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        $totalPages = max(1, ceil($total / $perPage));

        // 🔹 Datos paginados
        $sql = "SELECT noti_id, titulo, mensaje, leido, created_at
            FROM notificaciones
            WHERE $where
            ORDER BY created_at DESC
            LIMIT :offset, :limit";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $rows,
            'totalPages' => $totalPages
        ];
    }



    /**
     * 🔹 Obtener notificaciones de un usuario (por rol o app)
     */
    public function getByUsuario(int $usuarioId): array
    {
        try {
            $sql = "SELECT * FROM notificaciones 
                    WHERE usu_id = :id 
                    AND (expira IS NULL OR expira > NOW())
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log('Error getByUsuario: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔹 Crear una nueva notificación
     */
    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO notificaciones (
                        usu_id, admin_id, tipo, prioridad, canal, 
                        titulo, mensaje, paseo_id, leido, estado, expira
                    ) VALUES (
                        :usu_id, :admin_id, :tipo, :prioridad, :canal,
                        :titulo, :mensaje, :paseo_id, 0, :estado, :expira
                    )";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                ':usu_id'     => $data['usu_id'],
                ':admin_id'   => $data['admin_id'] ?? null,
                ':tipo'       => $data['tipo'] ?? 'sistema',
                ':prioridad'  => $data['prioridad'] ?? 'media',
                ':canal'      => $data['canal'] ?? 'app',
                ':titulo'     => $data['titulo'],
                ':mensaje'    => $data['mensaje'],
                ':paseo_id'   => $data['paseo_id'] ?? null,
                ':estado'     => $data['estado'] ?? 'pendiente',
                ':expira'     => $data['expira'] ?? null
            ]);
        } catch (Exception $e) {
            error_log('Error create notificación: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔹 Marcar una notificación como leída
     */
    public function marcarLeida(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE notificaciones SET leido = 1 WHERE noti_id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            error_log('Error marcarLeida: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔹 Cambiar estado (enviada, fallida, etc.)
     */
    public function actualizarEstado(int $id, string $estado): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE notificaciones SET estado = :estado WHERE noti_id = :id");
            return $stmt->execute([':estado' => $estado, ':id' => $id]);
        } catch (Exception $e) {
            error_log('Error actualizarEstado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔹 Eliminar notificación
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM notificaciones WHERE noti_id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            error_log('Error delete notificación: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔹 Enviar notificación global a todos los usuarios de un rol
     * (Ejemplo: todos los paseadores o dueños)
     */
    public function enviarPorRol(string $rol, array $data): int
    {
        try {
            $usuarios = $this->db->prepare("SELECT usu_id FROM usuarios WHERE rol = :rol");
            $usuarios->execute([':rol' => $rol]);
            $ids = $usuarios->fetchAll(PDO::FETCH_COLUMN);

            $inserted = 0;
            foreach ($ids as $uid) {
                $ok = $this->create([
                    'usu_id' => $uid,
                    'admin_id' => Session::getUsuarioId(),
                    'tipo' => $data['tipo'] ?? 'general',
                    'titulo' => $data['titulo'],
                    'mensaje' => $data['mensaje'],
                    'prioridad' => $data['prioridad'] ?? 'media',
                    'canal' => $data['canal'] ?? 'app',
                    'estado' => 'pendiente',
                ]);
                if ($ok) $inserted++;
            }
            return $inserted;
        } catch (Exception $e) {
            error_log('Error enviarPorRol: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 🔹 Limpieza de notificaciones expiradas
     */
    public function limpiarExpiradas(): int
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM notificaciones WHERE expira IS NOT NULL AND expira < NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log('Error limpiarExpiradas: ' . $e->getMessage());
            return 0;
        }
    }
    /**
     * 🔹 Obtener las últimas notificaciones recientes (por defecto 5)
     */
    public function getRecientes(int $limite = 5): array
    {
        try {
            $usuarioId = \Jaguata\Helpers\Session::getUsuarioId();
            $sql = "SELECT n.noti_id, n.titulo, n.mensaje, n.created_at, n.leido
                FROM notificaciones n
                WHERE n.usu_id = :usuarioId
                ORDER BY n.created_at DESC
                LIMIT :limite";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':usuarioId', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log('Error getRecientes: ' . $e->getMessage());
            return [];
        }
    }

    public function marcarTodasLeidas(): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE notificaciones SET leido = 1 WHERE leido = 0");
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Error marcarTodasLeidas: ' . $e->getMessage());
            return false;
        }
    }
    // 🔹 Marca una notificación específica del usuario como leída
    public function marcarLeidaUsuario(int $notiId): bool
    {
        try {
            $usuarioId = \Jaguata\Helpers\Session::getUsuarioId();
            $stmt = $this->db->prepare("UPDATE notificaciones 
                                    SET leido = 1 
                                    WHERE noti_id = :id AND usu_id = :usuarioId");
            return $stmt->execute([':id' => $notiId, ':usuarioId' => $usuarioId]);
        } catch (Exception $e) {
            error_log('Error marcarLeidaUsuario: ' . $e->getMessage());
            return false;
        }
    }

    // 🔹 Marca todas las notificaciones del usuario como leídas
    public function marcarTodasLeidasUsuario(): bool
    {
        try {
            $usuarioId = \Jaguata\Helpers\Session::getUsuarioId();
            $stmt = $this->db->prepare("UPDATE notificaciones 
                                    SET leido = 1 
                                    WHERE usu_id = :usuarioId AND leido = 0");
            $stmt->execute([':usuarioId' => $usuarioId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Error marcarTodasLeidasUsuario: ' . $e->getMessage());
            return false;
        }
    }
    // === Aliases para compatibilidad con la vista ===
    public function markRead(array $data): bool
    {
        $id = (int)($data['noti_id'] ?? 0);
        return $this->marcarLeidaUsuario($id);
    }

    public function markAllRead(): bool
    {
        return $this->marcarTodasLeidasUsuario();
    }
}
