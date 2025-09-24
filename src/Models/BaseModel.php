<?php

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;

abstract class BaseModel
{
    protected DatabaseService $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    public function find($id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function findAll(array $conditions = [], string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $field => $value) {
                $clauses[] = "$field = :$field";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $clauses);
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        $this->db->executeQuery($sql, $data);
        return (int) $this->db->getConnection()->lastInsertId();
    }

    public function update($id, array $data): bool
    {
        $fields = implode(', ', array_map(fn($f) => "$f = :$f", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $fields WHERE {$this->primaryKey} = :{$this->primaryKey}";
        $data[$this->primaryKey] = $id;
        return $this->db->executeQuery($sql, $data);
    }

    public function delete($id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :{$this->primaryKey}";
        return $this->db->executeQuery($sql, [$this->primaryKey => $id]);
    }
}
