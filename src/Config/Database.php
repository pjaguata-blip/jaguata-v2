<?php

namespace Jaguata\Config;

class Database
{
    private $host = 'localhost';
    private $db_name = 'jaguata';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $conn;

    // 🔹 Instancia singleton
    private static ?\PDO $instance = null;

    /**
     * Método de instancia (uso: (new Database())->getConnection())
     */
    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new \PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }

    /**
     * Método estático (uso: Database::getConnection())
     */
    public static function getStaticConnection(): \PDO
    {
        if (self::$instance === null) {
            try {
                $host = 'localhost';
                $db_name = 'jaguata';
                $username = 'root';
                $password = '';
                $charset = 'utf8mb4';

                $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
                self::$instance = new \PDO($dsn, $username, $password);
                self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                echo "Static Connection Error: " . $e->getMessage();
            }
        }
        return self::$instance;
    }

    /**
     * Cerrar conexión (solo para instancias normales)
     */
    public function closeConnection()
    {
        $this->conn = null;
    }

    /**
     * Cerrar conexión estática
     */
    public static function closeStaticConnection()
    {
        self::$instance = null;
    }
}
