<?php

namespace Jaguata\Config;

use PDO;
use PDOException;

class AppConfig
{
    private static bool $initialized = false;

    /**
     * Inicializa el entorno, conexión a BD, sesiones, constantes y configuración general.
     */
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

        // === Carga de variables de entorno (.env opcional) ===
        $envPath = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                [$name, $value] = array_map('trim', explode('=', $line, 2));
                if (!getenv($name)) putenv("$name=$value");
            }
        }

        // === Normalizar entorno CLI ===
        $isCli = (\php_sapi_name() === 'cli');
        if ($isCli) {
            $_SERVER['HTTPS']       = $_SERVER['HTTPS']       ?? 'off';
            $_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
            $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        }

        // === Detectar protocolo y host ===
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // === Detectar carpeta base ===
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath   = str_replace('\\', '/', dirname($scriptName));
        $basePath   = rtrim($basePath, '/');

        // === URLs base ===
        if (!defined('BASE_URL'))   define('BASE_URL',   $protocol . $host . $basePath);
        if (!defined('ASSETS_URL')) define('ASSETS_URL', BASE_URL . '/assets');
        if (!defined('API_URL'))    define('API_URL',    BASE_URL . '/api');

        // === Seguridad / Sesiones ===
        if (!defined('APP_KEY'))      define('APP_KEY', 'clave-super-secreta'); // cambiar en prod
        if (!defined('SESSION_NAME')) define('SESSION_NAME', 'JAGUATA_SESSION');

        // === Integraciones externas ===
        if (!defined('GOOGLE_ANALYTICS_ID'))
            define('GOOGLE_ANALYTICS_ID', ($host === 'localhost' ? '' : 'G-XXXXXXXXXX'));
        if (!defined('SENTRY_DSN'))
            define('SENTRY_DSN', ($host === 'localhost' ? '' : 'https://xxxxxxx.ingest.sentry.io/yyyyyy'));

        // === Debug ===
        if (!defined('DEBUG_MODE')) define('DEBUG_MODE', $host === 'localhost');

        // === Config BD ===
        if (!defined('DB_HOST'))    define('DB_HOST',    getenv('DB_HOST')    ?: '127.0.0.1');
        if (!defined('DB_NAME'))    define('DB_NAME',    getenv('DB_NAME')    ?: 'jaguata');
        if (!defined('DB_USER'))    define('DB_USER',    getenv('DB_USER')    ?: 'root');
        if (!defined('DB_PASS'))    define('DB_PASS',    getenv('DB_PASS')    ?: '');
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
            self::log("❌ Error de conexión a la base de datos: " . $e->getMessage());
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'error'   => 'Error interno al conectar con la base de datos'
            ]);
            exit;
        }

        // === Iniciar sesión global ===
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name(SESSION_NAME);
            @session_start();
        }

        // === Configuración de tamaños de mascotas ===
        if (!defined('TAMANOS_MASCOTA')) {
            define('TAMANOS_MASCOTA', [
                'pequeno' => ['label' => 'Pequeño', 'rango' => '0 - 10 kg'],
                'mediano' => ['label' => 'Mediano', 'rango' => '11 - 25 kg'],
                'grande'  => ['label' => 'Grande',  'rango' => '26 - 45 kg'],
                'extra_grande' => ['label' => 'Extra Grande', 'rango' => '46+ kg']
            ]);
        }

        // ============================================
        // 🔹 CREACIÓN AUTOMÁTICA DE USUARIO ADMIN
        // ============================================
        try {
            $pdo = $GLOBALS['db'];
            $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
            if ($stmt->rowCount() > 0) {
                $adminCheck = $pdo->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin'");
                $count = $adminCheck->fetchColumn();

                if ($count == 0) {
                    $hash = password_hash('admin123', PASSWORD_BCRYPT);
                    $insert = $pdo->prepare("
                        INSERT INTO usuarios (nombre, email, pass, rol, estado)
                        VALUES (:nombre, :email, :pass, :rol, :estado)
                    ");
                    $insert->execute([
                        ':nombre' => 'Administrador General',
                        ':email'  => 'admin@jaguata.com',
                        ':pass'   => $hash,
                        ':rol'    => 'admin',
                        ':estado' => 'aprobado'
                    ]);
                    self::log("✅ Usuario administrador creado automáticamente (admin@jaguata.com / admin123)");
                }
            }
        } catch (PDOException $e) {
            self::log("⚠️ Error al verificar/crear admin: " . $e->getMessage());
        }

        self::$initialized = true;
    }

    // ==========================================================
    // 🔧 MÉTODOS DE UTILIDAD
    // ==========================================================

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

    /**
     * Retorna la conexión PDO global inicializada.
     */
    public static function db(): PDO
    {
        if (!self::$initialized) {
            self::init();
        }
        if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof PDO)) {
            throw new \RuntimeException('Conexión DB no inicializada');
        }
        return $GLOBALS['db'];
    }

    public static function pdo(): PDO
    {
        return self::db();
    }

    /**
     * Obtiene una variable de entorno con valor por defecto.
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Devuelve una ruta absoluta dentro del proyecto.
     */
    public static function path(string $relative): string
    {
        return realpath(dirname(__DIR__, 2) . '/' . ltrim($relative, '/')) ?: $relative;
    }

    /**
     * Guarda mensajes en el log de PHP y en consola si DEBUG_MODE = true
     */
    public static function log(string $message): void
    {
        error_log($message);
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "<pre style='color:#888'>" . htmlspecialchars($message) . "</pre>";
        }
    }
}
