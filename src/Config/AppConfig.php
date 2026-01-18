<?php
declare(strict_types=1);

namespace Jaguata\Config;

use Jaguata\Services\DatabaseService;

final class AppConfig
{
    // 👉 AJUSTÁ este BASE_URL según tu carpeta en XAMPP
    private const BASE_URL = 'http://localhost/jaguata';

    private static bool $booted = false;

    /**
     * Inicializa:
     * - session
     * - constante BASE_URL
     * - (opcional) ASSETS_URL si no existe
     */
    public static function init(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        // Sesión
        if (\session_status() === PHP_SESSION_NONE) {
            \session_name(\defined('SESSION_NAME') ? SESSION_NAME : 'JAGUATA_SESSION');
            \session_start();
        }

        // BASE_URL global
        if (!\defined('BASE_URL')) {
            \define('BASE_URL', self::BASE_URL);
        }

        // ASSETS_URL (si tu Constantes.php no lo define)
        if (!\defined('ASSETS_URL')) {
            \define('ASSETS_URL', BASE_URL . '/public/assets');
        }
    }

    public static function db(): \PDO
    {
        self::init();
        return DatabaseService::getInstance()->getConnection();
    }

    public static function getBaseUrl(): string
    {
        self::init();
        return BASE_URL;
    }

    public static function getAssetsUrl(): string
    {
        self::init();
        return ASSETS_URL;
    }
}
