<?php

declare(strict_types=1);

namespace Jaguata\Config;

use Jaguata\Services\DatabaseService;

class AppConfig
{
    // 👉 AJUSTÁ este BASE_URL según tu carpeta en XAMPP
    // Ej: http://localhost/jaguata  (si el proyecto está en htdocs/jaguata)
    private const BASE_URL = 'http://localhost/jaguata';

    /**
     * Inicializa:
     * - session
     * - autoload de composer
     * - constante BASE_URL
     */
    public static function init(): void
    {
        // Sesión
        if (session_status() === PHP_SESSION_NONE) {
            \session_name(defined('SESSION_NAME') ? SESSION_NAME : 'JAGUATA_SESSION');
            \session_start();
        }

        // Autoload de composer (si lo usás)
        $rootPath  = dirname(__DIR__, 2); // desde src/Config → raíz del proyecto
        $autoload  = $rootPath . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        // Definir BASE_URL global si aún no existe
        if (!defined('BASE_URL')) {
            define('BASE_URL', self::BASE_URL);
        }
    }

    /**
     * Atajo para obtener el PDO directo
     */
    public static function db(): \PDO
    {
        return DatabaseService::getInstance()->getConnection();
    }
}
