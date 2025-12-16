<?php

declare(strict_types=1);

namespace Jaguata\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo de Notificaciones
 * -------------------------
 * Soporta notificaciones individuales, por rol y globales.
 * ✅ Regla: las masivas (rol/todos) solo se muestran desde que el usuario existe.
 */
class Notificacion extends BaseModel
{
    protected string $table      = 'notificaciones';
    protected string $primaryKey = 'noti_id';

    public function __construct()
    {
        parent::__construct();
    }

    /** ✅ Fecha de creación del usuario (para filtrar masivas antiguas) */
    private function getUserCreatedAt(int $usuId): string
    {
        $stmt = $this->db->prepare("SELECT created_at FROM usuarios WHERE usu_id = :id LIMIT 1");
        $stmt->execute([':id' => $usuId]);
        return (string)($stmt->fetchColumn() ?: '1970-01-01 00:00:00');
    }

    /** ✅ WHERE reutilizable */
    private function buildWhereUserRolTodos(int $usuId, string $rol, string $userCreatedAt): array
    {
        $rol = strtolower($rol);

        $where = ["
            (
                usu_id = :usu_id
                OR (
                    (rol_destinatario = :rol OR rol_destinatario = 'todos')
                    AND created_at >= :user_created
                )
            )
        "];

        $params = [
            ':usu_id'       => $usuId,
            ':rol'          => $rol,
            ':user_created' => $userCreatedAt,
        ];

        return [$where, $params];
    }

    /**
     * 🔹 Lista notificaciones (usuario + rol + globales)
     * ✅ Masivas solo desde created_at del usuario
     */
    public function listByUser(
        int $usuId,
        string $rol,
        ?int $leido = null,
        ?string $search = null,
        int $page = 1,
        int $perPage = 10,
        ?string $tipo = null
    ): array {
        $userCreatedAt = $this->getUserCreatedAt($usuId);

        [$where, $params] = $this->buildWhereUserRolTodos($usuId, $rol, $userCreatedAt);

        if ($leido !== null) {
            $where[] = 'leido = :leido';
            $params[':leido'] = $leido;
        }

        if ($tipo) {
            $where[] = 'tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        if ($search) {
            $where[] = '(titulo LIKE :q OR mensaje LIKE :q)';
            $params[':q'] = "%{$search}%";
        }

        $where[] = '(expira IS NULL OR expira > NOW())';

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // COUNT
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} {$whereSql}";
        $stmt = $this->db->prepare($sqlCount);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // DATA
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT *
            FROM {$this->table}
            {$whereSql}
            ORDER BY leido ASC, created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'data'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * 🔹 Notificaciones recientes (para dashboard/campanita)
     * ✅ Masivas solo desde created_at del usuario
     */
    public function getRecientes(int $usuId, string $rol, int $limit = 5): array
    {
        $userCreatedAt = $this->getUserCreatedAt($usuId);
        $limit = max(1, min($limit, 50));

        [$where, $params] = $this->buildWhereUserRolTodos($usuId, $rol, $userCreatedAt);
        $where[] = '(expira IS NULL OR expira > NOW())';

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT *
            FROM {$this->table}
            {$whereSql}
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔹 Contador de no leídas (badge)
     * ✅ Masivas solo desde created_at del usuario
     */
    public function countUnread(int $usuId, string $rol): int
    {
        $userCreatedAt = $this->getUserCreatedAt($usuId);

        [$where, $params] = $this->buildWhereUserRolTodos($usuId, $rol, $userCreatedAt);
        $where[] = 'leido = 0';
        $where[] = '(expira IS NULL OR expira > NOW())';

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT COUNT(*) FROM {$this->table} {$whereSql}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * 🔹 Marcar una notificación como leída
     * (permite usu_id = NULL para masivas)
     */
    public function markRead(int $notiId, int $usuId): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET leido = 1
            WHERE {$this->primaryKey} = :id
              AND (usu_id = :usu_id OR usu_id IS NULL)
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'     => $notiId,
            ':usu_id' => $usuId,
        ]);
    }

    /**
     * 🔹 Marcar todas como leídas (usuario + rol + globales)
     * ✅ Masivas solo desde created_at del usuario
     */
    public function markAllRead(int $usuId, string $rol): int
    {
        $userCreatedAt = $this->getUserCreatedAt($usuId);

        // Armamos el WHERE igual que listByUser
        $rol = strtolower($rol);

        $sql = "
            UPDATE {$this->table}
            SET leido = 1
            WHERE
            (
                usu_id = :usu_id
                OR (
                    (rol_destinatario = :rol OR rol_destinatario = 'todos')
                    AND created_at >= :user_created
                )
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usu_id'       => $usuId,
            ':rol'          => $rol,
            ':user_created' => $userCreatedAt,
        ]);

        return (int)$stmt->rowCount();
    }
}
