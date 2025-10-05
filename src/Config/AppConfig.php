<?php

namespace Jaguata\Config;

use PDO;
use PDOException;

class AppConfig
{
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // === Composer Autoload ===
        $vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }

        // Normalizar entorno cuando se ejecuta por CLI (tests/scripts)
        $isCli = (\php_sapi_name() === 'cli');
        if ($isCli) {
            $_SERVER['HTTPS']      = $_SERVER['HTTPS']      ?? 'off';
            $_SERVER['HTTP_HOST']  = $_SERVER['HTTP_HOST']  ?? 'localhost';
            $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        }

        // Detectar protocolo y host
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Detectar carpeta base (ej: /jaguata/public)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('\\', '/', dirname($scriptName));
        $basePath = rtrim($basePath, '/');

        // === URLs base ===
        if (!defined('BASE_URL')) {
            define('BASE_URL', $protocol . $host . $basePath);
        }
        if (!defined('ASSETS_URL')) {
            define('ASSETS_URL', BASE_URL . '/assets');
        }
        if (!defined('API_URL')) {
            define('API_URL', BASE_URL . '/api');
        }

        // === Seguridad / Sesiones ===
        if (!defined('APP_KEY')) {
            define('APP_KEY', 'clave-super-secreta'); // cambiar en producción
        }
        if (!defined('SESSION_NAME')) {
            define('SESSION_NAME', 'JAGUATA_SESSION');
        }

        // === Google Analytics ===
        if (!defined('GOOGLE_ANALYTICS_ID')) {
            define('GOOGLE_ANALYTICS_ID', ($host === 'localhost' ? '' : 'G-XXXXXXXXXX'));
        }

        // === Sentry ===
        if (!defined('SENTRY_DSN')) {
            define('SENTRY_DSN', ($host === 'localhost' ? '' : 'https://xxxxxxx.ingest.sentry.io/yyyyyy'));
        }

        // === Debug ===
        if (!defined('DEBUG_MODE')) {
            define('DEBUG_MODE', $host === 'localhost');
        }

        // === Config BD ===
        if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
        if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'jaguata');
        if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
        if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
        if (!defined('DB_CHARSET')) define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

        // === Conexión BD ===
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $GLOBALS['db'] = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'error' => 'Error interno al conectar con la base de datos'
            ]);
            exit;
        }

        // === Iniciar sesión global ===
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name(SESSION_NAME);
            @session_start();
        }

        self::$initialized = true;
    }

    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return defined($key) ? constant($key) : $default;
    }

    public static function getBaseUrl(): string
    {
        return BASE_URL;
    }

    public static function getAssetsUrl(): string
    {
        return ASSETS_URL;
    }

    public static function getApiUrl(): string
    {
        return API_URL;
    }
}
