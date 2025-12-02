<?php

declare(strict_types=1);

namespace Jaguata\Models;

use PDO;

require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo de Notificaciones
 * -------------------------
 * Soporta notificaciones individuales, por rol y globales.
 */
class Notificacion extends BaseModel
{
    protected string $table      = 'notificaciones';
    protected string $primaryKey = 'noti_id';

    public function __construct()
    {
        parent::__construct(); // inicializa $this->db como PDO
    }

    /**
     * 🔹 Lista notificaciones según usuario y su rol.
     * Incluye:
     *  - Notificaciones del usuario (usu_id)
     *  - Notificaciones dirigidas a su rol
     *  - Notificaciones globales (rol_destinatario = 'todos')
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
        $rol = strtolower($rol);

        $where   = ['(usu_id = :usu_id OR rol_destinatario = :rol OR rol_destinatario = \'todos\')'];
        $params  = [
            ':usu_id' => $usuId,
            ':rol'    => $rol,
        ];

        if ($leido !== null) {
            $where[]        = 'leido = :leido';
            $params[':leido'] = $leido;
        }

        if ($tipo) {
            $where[]        = 'tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        if ($search) {
            $where[]         = '(titulo LIKE :q OR mensaje LIKE :q)';
            $params[':q'] = "%{$search}%";
        }

        $where[] = '(expira IS NULL OR expira > NOW())';

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // 🔹 Total de registros
        $sqlCount = "SELECT COUNT(*) AS c FROM {$this->table} {$whereSql}";
        $stmt = $this->db->prepare($sqlCount);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // 🔹 Datos paginados
        $offset = max(0, ($page - 1) * $perPage);
        $sql    = "
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

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * 🔹 Notificaciones recientes (usuario + rol + globales)
     */
    public function getRecientes(int $usuId, string $rol, int $limit = 5): array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE (usu_id = :usu_id OR rol_destinatario = :rol OR rol_destinatario = 'todos')
              AND (expira IS NULL OR expira > NOW())
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usu_id', $usuId, PDO::PARAM_INT);
        $stmt->bindValue(':rol', strtolower($rol), PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 🔹 Marcar una notificación como leída
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
     */
    public function markAllRead(int $usuId, string $rol): int
    {
        $sql = "
            UPDATE {$this->table}
            SET leido = 1
            WHERE (usu_id = :usu_id OR rol_destinatario = :rol OR rol_destinatario = 'todos')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usu_id' => $usuId,
            ':rol'    => strtolower($rol),
        ]);

        return (int)$stmt->rowCount();
    }

    /**
     * 🔹 Contador de no leídas (usuario + rol + globales)
     */
    public function countUnread(int $usuId, string $rol): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM {$this->table}
            WHERE (usu_id = :usu_id OR rol_destinatario = :rol OR rol_destinatario = 'todos')
              AND leido = 0
              AND (expira IS NULL OR expira > NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usu_id' => $usuId,
            ':rol'    => strtolower($rol),
        ]);

        return (int)$stmt->fetchColumn();
    }
}
