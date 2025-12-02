<?php

declare(strict_types=1);

namespace Jaguata\Services;

use PDO;
use PDOException;

/**
 * Servicio de base de datos
 * - Singleton
 * - Devuelve PDO
 * - Además expone helpers: connection(), fetchOne(), fetchAll()
 */
class DatabaseService
{
    private static ?self $instance = null;
    private PDO $connection;

    /**
     * Constructor privado: solo se accede por getInstance()
     */
    private function __construct()
    {
        // 👉 AJUSTÁ ESTO según tu entorno
        $host     = 'localhost';
        $dbname   = 'jaguata';
        $user     = 'root';
        $password = ''; // tu contraseña de MySQL si tenés

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        try {
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    /**
     * Singleton
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Devuelve el PDO "pelado"
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * ⛳ Compatibilidad con código viejo:
     * DatabaseService::connection() → devuelve el propio servicio
     */
    public static function connection(): self
    {
        return self::getInstance();
    }

    // =====================
    // Helpers de consulta
    // =====================

    public function prepare(string $sql)
    {
        return $this->connection->prepare($sql);
    }

    public function query(string $sql)
    {
        return $this->connection->query($sql);
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    // Transacciones (por si usás)
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }
}
