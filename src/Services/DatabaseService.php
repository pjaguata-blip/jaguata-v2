<?php
namespace Jaguata\Services;

use PDO;
use PDOException;

class DatabaseService {
    private static ?DatabaseService $instance = null;
    private PDO $connection;

    private function __construct() {
        $dsn = "mysql:host=localhost;dbname=jaguata;charset=utf8mb4";
        $user = "root"; // ⚠️ cambia por tu usuario real
        $pass = "";     // ⚠️ cambia por tu contraseña real

        try {
            $this->connection = new PDO($dsn, $user, $pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance(): DatabaseService {
        if (!self::$instance) {
            self::$instance = new DatabaseService();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Ejecuta una consulta SELECT y devuelve todas las filas
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ejecuta una consulta SELECT y devuelve una sola fila
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Ejecuta una consulta INSERT/UPDATE/DELETE
     */
    public function executeQuery(string $sql, array $params = []): bool {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }
}
