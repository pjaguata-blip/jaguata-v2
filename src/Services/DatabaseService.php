<?php

namespace Jaguata\Services;

use PDO;
use PDOException;
use Jaguata\Config\AppConfig;

class DatabaseService
{
    private static ?self $instance = null;
    private static ?PDO $pdoInstance = null; // 🔹 nuevo para acceso directo al PDO
    private PDO $connection;

    private function __construct()
    {
        $dsn = 'mysql:host=localhost;dbname=jaguata;charset=utf8mb4';
        $user = 'root';
        $password = '';

        try {
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // 🔹 Guardar también el PDO estático para acceso global
            self::$pdoInstance = $this->connection;
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    /**
     * Devuelve la instancia única de DatabaseService (wrapper)
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Devuelve la conexión PDO interna
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * 🔹 Devuelve directamente el PDO (uso estático, sin instancia previa)
     */
    public static function getConnectionStatic(): PDO
    {
        if (self::$pdoInstance === null) {
            self::getInstance();
        }
        return self::$pdoInstance;
    }

    /**
     * 🔹 Alias corto para obtener el PDO
     */
    public static function connection(): PDO
    {
        return self::getConnectionStatic();
    }

    // ==============================================================
    // 👇 Métodos existentes — SIN ELIMINAR NADA
    // ==============================================================

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function executeQuery(string $sql, array $params = []): bool
    {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Ejecutar y devolver un único valor escalar
     */
    public function executeScalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Manejo de transacciones
     */
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
