<?php

declare(strict_types=1);

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use Jaguata\Models\BaseModel;

// ⛑️ Fallback: si BaseModel aún no está cargado por el autoload, lo incluimos a mano
if (!class_exists(BaseModel::class)) {
    require_once __DIR__ . '/BaseModel.php';
}

class Notificacion extends BaseModel
{
    protected string $table = 'notificaciones';
    protected string $primaryKey = 'noti_id';

    public function __construct()
    {
        parent::__construct();
    }

    /** Listar notificaciones de un usuario con filtros */
    public function listByUser(
        int $usuId,
        ?int $leido = null,
        ?string $search = null,
        int $page = 1,
        int $perPage = 10,
        ?string $tipo = null
    ): array {
        $where   = ['usu_id = :usu_id'];
        $params  = ['usu_id' => $usuId];

        if ($leido !== null) {
            $where[] = 'leido = :leido';
            $params['leido'] = $leido;
        }

        if ($tipo) {
            $where[] = 'tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        if ($search) {
            $where[] = '(titulo LIKE :q OR mensaje LIKE :q)';
            $params['q'] = "%{$search}%";
        }

        $where[] = '(expira IS NULL OR expira > NOW())';
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sqlCount = "SELECT COUNT(*) AS c FROM {$this->table} {$whereSql}";
        $total = (int) $this->db->executeScalar($sqlCount, $params);

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT * FROM {$this->table} {$whereSql} ORDER BY leido ASC, created_at DESC LIMIT :limit OFFSET :offset";

        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
        ];
    }

    /** Notificaciones recientes */
    public function getRecientes(int $usuId, int $limit = 5): array
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE usu_id = :usu_id
                  AND (expira IS NULL OR expira > NOW())
                ORDER BY created_at DESC
                LIMIT :limit";
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':usu_id', $usuId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Marcar una como leída */
    public function markRead(int $notiId, int $usuId): bool
    {
        $sql = "UPDATE {$this->table}
                SET leido = 1
                WHERE {$this->primaryKey} = :id AND usu_id = :usu_id";
        return $this->db->executeQuery($sql, ['id' => $notiId, 'usu_id' => $usuId]);
    }

    /** Marcar todas como leídas */
    public function markAllRead(int $usuId): int
    {
        $sql = "UPDATE {$this->table} SET leido = 1 WHERE usu_id = :usu_id";
        $this->db->executeQuery($sql, ['usu_id' => $usuId]);
        return (int) $this->db->getConnection()->query("SELECT ROW_COUNT()")->fetchColumn();
    }

    /** Contador de no leídas */
    public function countUnread(int $usuId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE usu_id = :usu_id AND leido = 0
                  AND (expira IS NULL OR expira > NOW())";
        return (int) $this->db->executeScalar($sql, ['usu_id' => $usuId]);
    }
}
