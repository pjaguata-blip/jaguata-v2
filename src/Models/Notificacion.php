<?php

declare(strict_types=1);

namespace Jaguata\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';

class Notificacion extends BaseModel
{
    protected string $table      = 'notificaciones';
    protected string $primaryKey = 'noti_id';

    public function __construct()
    {
        parent::__construct();
    }

    /* =================== Helpers =================== */

    private function getUserCreatedAt(int $usuId): string
    {
        $stmt = $this->db->prepare("SELECT created_at FROM usuarios WHERE usu_id = :id LIMIT 1");
        $stmt->execute([':id' => $usuId]);
        return (string)($stmt->fetchColumn() ?: '1970-01-01 00:00:00');
    }

    /**
     * Filtro base:
     * - personales: usu_id = :usu_id
     * - masivas: usu_id IS NULL y rol_destinatario coincide con rol o 'todos'
     * - NO archivadas
     * - NO ocultas (notificaciones_ocultas)
     * - masivas solo desde created_at del usuario
     */
    private function buildWhereUserRolTodos(int $usuId, string $rol, string $userCreatedAt): array
    {
        $rol = strtolower(trim($rol));

        // ✅ soportar dueno/dueño (por consistencia)
        $rolAlt = $rol;
        if ($rol === 'dueno') $rolAlt = 'dueño';
        if ($rol === 'dueño') $rolAlt = 'dueno';

        $where = ["(
            (
                usu_id = :usu_id
                OR (
                    usu_id IS NULL
                    AND (rol_destinatario = :rol OR rol_destinatario = :rol_alt OR rol_destinatario = 'todos')
                    AND created_at >= :user_created
                )
            )
            AND (archivada IS NULL OR archivada = 0)
            AND NOT EXISTS (
                SELECT 1
                FROM notificaciones_ocultas o
                WHERE o.noti_id = {$this->table}.{$this->primaryKey}
                  AND o.usu_id  = :uid_oculta
            )
        )"];

        $params = [
            ':usu_id'       => $usuId,
            ':rol'          => $rol,
            ':rol_alt'      => $rolAlt,
            ':user_created' => $userCreatedAt,
            ':uid_oculta'   => $usuId,
        ];

        return [$where, $params];
    }

    /* =================== Listado =================== */

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
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
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
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

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

    /* =================== Leído =================== */

    public function markRead(int $notiId, int $usuId): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET leido = 1
            WHERE {$this->primaryKey} = :id
              AND (usu_id = :usu_id OR usu_id IS NULL)
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $notiId, ':usu_id' => $usuId]);
    }

    public function markAllRead(int $usuId, string $rol): int
    {
        $userCreatedAt = $this->getUserCreatedAt($usuId);
        $rol = strtolower(trim($rol));

        // ✅ alt
        $rolAlt = $rol;
        if ($rol === 'dueno') $rolAlt = 'dueño';
        if ($rol === 'dueño') $rolAlt = 'dueno';

        $sql = "
            UPDATE {$this->table} n
            SET n.leido = 1
            WHERE
                (
                    (
                        n.usu_id = :usu_id
                        OR (
                            n.usu_id IS NULL
                            AND (n.rol_destinatario = :rol OR n.rol_destinatario = :rol_alt OR n.rol_destinatario = 'todos')
                            AND n.created_at >= :user_created
                        )
                    )
                    AND (n.archivada IS NULL OR n.archivada = 0)
                    AND NOT EXISTS (
                        SELECT 1 FROM notificaciones_ocultas o
                        WHERE o.noti_id = n.{$this->primaryKey}
                          AND o.usu_id  = :uid_oculta
                    )
                )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usu_id'       => $usuId,
            ':rol'          => $rol,
            ':rol_alt'      => $rolAlt,
            ':user_created' => $userCreatedAt,
            ':uid_oculta'   => $usuId,
        ]);

        return (int)$stmt->rowCount();
    }

    /* =================== ELIMINAR / LIMPIAR =================== */

    /**
     * Devuelve:
     * - 'personal' si usu_id = usuario
     * - 'masiva' si usu_id IS NULL (rol/todos)
     * - null si no pertenece / no existe / expirada / archivada
     */
    public function tipoParaUsuario(int $notiId, int $usuId, string $rol): ?string
    {
        $userCreatedAt = $this->getUserCreatedAt($usuId);
        $rol = strtolower(trim($rol));

        $rolAlt = $rol;
        if ($rol === 'dueno') $rolAlt = 'dueño';
        if ($rol === 'dueño') $rolAlt = 'dueno';

        $sql = "
            SELECT usu_id
            FROM {$this->table}
            WHERE {$this->primaryKey} = :id
              AND (expira IS NULL OR expira > NOW())
              AND (archivada IS NULL OR archivada = 0)
              AND (
                    usu_id = :uid
                    OR (
                        usu_id IS NULL
                        AND (rol_destinatario = :rol OR rol_destinatario = :rol_alt OR rol_destinatario = 'todos')
                        AND created_at >= :user_created
                    )
              )
            LIMIT 1
        ";

        $st = $this->db->prepare($sql);
        $st->execute([
            ':id'           => $notiId,
            ':uid'          => $usuId,
            ':rol'          => $rol,
            ':rol_alt'      => $rolAlt,
            ':user_created' => $userCreatedAt,
        ]);

        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        return ($row['usu_id'] === null) ? 'masiva' : 'personal';
    }

    public function archivarPersonal(int $notiId, int $usuId): bool
    {
        $st = $this->db->prepare("
            UPDATE {$this->table}
            SET archivada = 1
            WHERE {$this->primaryKey} = :id AND usu_id = :uid
            LIMIT 1
        ");
        $st->execute([':id' => $notiId, ':uid' => $usuId]);
        return $st->rowCount() > 0;
    }

    public function ocultarMasivaParaUsuario(int $notiId, int $usuId): bool
    {
        $st = $this->db->prepare("
            INSERT IGNORE INTO notificaciones_ocultas (noti_id, usu_id, created_at)
            VALUES (:nid, :uid, NOW())
        ");
        return $st->execute([':nid' => $notiId, ':uid' => $usuId]);
    }

    public function archivarTodasPersonales(int $usuId): int
    {
        $st = $this->db->prepare("
            UPDATE {$this->table}
            SET archivada = 1
            WHERE usu_id = :uid
        ");
        $st->execute([':uid' => $usuId]);
        return (int)$st->rowCount();
    }

    public function ocultarTodasMasivasParaUsuario(int $usuId, string $rol): int
    {
        $userCreatedAt = $this->getUserCreatedAt($usuId);
        $rol = strtolower(trim($rol));

        $rolAlt = $rol;
        if ($rol === 'dueno') $rolAlt = 'dueño';
        if ($rol === 'dueño') $rolAlt = 'dueno';

        $sql = "
            INSERT IGNORE INTO notificaciones_ocultas (noti_id, usu_id, created_at)
            SELECT n.{$this->primaryKey}, :uid, NOW()
            FROM {$this->table} n
            WHERE
                n.usu_id IS NULL
                AND (n.rol_destinatario = :rol OR n.rol_destinatario = :rol_alt OR n.rol_destinatario = 'todos')
                AND n.created_at >= :user_created
                AND (n.expira IS NULL OR n.expira > NOW())
                AND (n.archivada IS NULL OR n.archivada = 0)
        ";

        $st = $this->db->prepare($sql);
        $st->execute([
            ':uid'          => $usuId,
            ':rol'          => $rol,
            ':rol_alt'      => $rolAlt,
            ':user_created' => $userCreatedAt,
        ]);

        return (int)$st->rowCount();
    }
}
