<?php

namespace Jaguata\Config;

use PDO;
use PDOException;

class Database
{
    /**
     * Instancia singleton global
     */
    private static ?PDO $instance = null;

    /**
     * Devuelve una instancia PDO reutilizable.
     * Preferí usar este método en tus modelos:
     *
     *   $db = Database::getConnection();
     */
    public static function getConnection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        // Nos aseguramos de que AppConfig esté inicializado
        if (!AppConfig::isInitialized()) {
            AppConfig::init();
        }

        try {
           $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            AppConfig::log('Static Connection Error: ' . $e->getMessage());
            throw $e;
        }

        return self::$instance;
    }

    /**
     * Alias para compatibilidad: Database::getStaticConnection()
     */
    public static function getStaticConnection(): PDO
    {
        return self::getConnection();
    }

    /**
     * Cerrar conexión estática (si querés forzar reconexión)
     */
    public static function closeStaticConnection(): void
    {
        self::$instance = null;
    }
}
