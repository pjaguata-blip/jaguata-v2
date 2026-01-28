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
        // ✅ Asegurar que las constantes estén cargadas
        // (Si ya las cargas en AppConfig/init, esto igual no molesta)
        if (!defined('DB_HOST')) {
            // Intento cargar Constantes.php si existe en la ruta típica
            $constantes = dirname(__DIR__, 1) . '/Config/Constantes.php'; // src/Config/Constantes.php
            if (file_exists($constantes)) {
                require_once $constantes;
            }
        }

        // ✅ Usar constantes del proyecto (sin hardcode)
        $host     = defined('DB_HOST') ? (string) DB_HOST : '127.0.0.1';
        $port     = defined('DB_PORT') ? (string) DB_PORT : '3306';
        $dbname   = defined('DB_NAME') ? (string) DB_NAME : 'jaguata';
        $user     = defined('DB_USER') ? (string) DB_USER : 'root';
        $password = defined('DB_PASS') ? (string) DB_PASS : '';
        $charset  = defined('DB_CHARSET') ? (string) DB_CHARSET : 'utf8mb4';

        // ✅ Forzar TCP real si alguien puso localhost (evita socket)
        if ($host === 'localhost') {
            $host = '127.0.0.1';
        }

        // ✅ DSN con puerto (clave para 3307)
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,

                // ✅ Recomendado para evitar “cuelgues”
                PDO::ATTR_TIMEOUT            => 5,
            ]);
        } catch (PDOException $e) {
            // ✅ Mejor que die(): así ves el error en tu handler/log
            throw new PDOException('Error de conexión a la base de datos: ' . $e->getMessage(), (int)$e->getCode(), $e);
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
