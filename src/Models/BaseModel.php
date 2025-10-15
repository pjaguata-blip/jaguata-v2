<?php

declare(strict_types=1);

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;

// 🔹 fallback opcional por si el autoload aún no cargó DatabaseService
if (!class_exists(DatabaseService::class)) {
    require_once __DIR__ . '/../Services/DatabaseService.php';
}

/**
 * Clase base para todos los modelos
 * Provee operaciones CRUD genéricas y helpers de consulta
 */
abstract class BaseModel
{
    protected DatabaseService $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        // 🔹 inicializamos la instancia de base de datos de forma segura
        $this->db = DatabaseService::getInstance();
    }

    /**
     * Buscar un registro por ID
     */
    public function find(int|string $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Obtener todos los registros con condiciones opcionales
     */
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

    /**
     * Insertar un registro
     */
    public function create(array $data): int
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        $this->db->executeQuery($sql, $data);

        return (int) $this->db->getConnection()->lastInsertId();
    }

    /**
     * Actualizar un registro por ID
     */
    public function update(int|string $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = implode(', ', array_map(fn($f) => "$f = :$f", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $fields WHERE {$this->primaryKey} = :{$this->primaryKey}";
        $data[$this->primaryKey] = $id;

        return $this->db->executeQuery($sql, $data);
    }

    /**
     * Eliminar un registro por ID
     */
    public function delete(int|string $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :{$this->primaryKey}";
        return $this->db->executeQuery($sql, [$this->primaryKey => $id]);
    }

    /**
     * Helper: fetchOne (atajo a DatabaseService)
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Helper: fetchAll (atajo a DatabaseService)
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    // --------------------------------------------------------------------
    // 🔹 Alias en español (manteniendo compatibilidad con tus controladores)
    // --------------------------------------------------------------------

    /** Alias de create */
    public function crear(array $data): int
    {
        return $this->create($data);
    }

    /** Alias de update */
    public function actualizar(int|string $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /** Alias de delete */
    public function eliminar(int|string $id): bool
    {
        return $this->delete($id);
    }
}
