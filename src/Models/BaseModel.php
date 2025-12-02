<?php

declare(strict_types=1);

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;
use PDOException;

if (!class_exists(DatabaseService::class)) {
    require_once __DIR__ . '/../Services/DatabaseService.php';
}

abstract class BaseModel
{
    protected \PDO $db;

    // Cada modelo debe definir estos:
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    // ==========
    // CRUD BÁSICO
    // ==========

    public function find(int $id): ?array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function all(): array
    {
        $sql  = "SELECT * FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $fields  = implode(', ', $columns);
        $params  = ':' . implode(', :', $columns);

        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$params})";

        $stmt = $this->db->prepare($sql);
        foreach ($data as $col => $value) {
            $stmt->bindValue(':' . $col, $value);
        }

        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $set = [];
        foreach ($data as $col => $value) {
            $set[] = "{$col} = :{$col}";
        }
        $setStr = implode(', ', $set);

        $sql = "UPDATE {$this->table} SET {$setStr} WHERE {$this->primaryKey} = :id";

        $stmt = $this->db->prepare($sql);
        foreach ($data as $col => $value) {
            $stmt->bindValue(':' . $col, $value);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql  = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
