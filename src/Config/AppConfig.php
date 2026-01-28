<?php
declare(strict_types=1);

namespace Jaguata\Config;

use Jaguata\Services\DatabaseService;

final class AppConfig
{
    private const BASE_URL = 'http://localhost/jaguata';

    private static bool $booted = false;

    public static function init(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        if (\session_status() === PHP_SESSION_NONE) {
            \session_name(\defined('SESSION_NAME') ? SESSION_NAME : 'JAGUATA_SESSION');
            \session_start();
        }
        if (!\defined('BASE_URL')) {
            \define('BASE_URL', self::BASE_URL);
        }
        if (!\defined('ASSETS_URL')) {
            \define('ASSETS_URL', BASE_URL . '/public/assets');
        }
        if (\defined('TIMEZONE')) {
            @\date_default_timezone_set((string) TIMEZONE);
        }
    }
    public static function isInitialized(): bool
    {
        return self::$booted;
    }
    public static function log(string $message, string $level = 'INFO'): void
    {
        self::init();

        $line = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);
        if (\defined('LOG_FILE')) {
            $dir = \dirname((string) LOG_FILE);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            @file_put_contents((string) LOG_FILE, $line, FILE_APPEND);
        } else {
            @error_log($line);
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
