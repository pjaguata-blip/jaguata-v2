<?php
namespace Jaguata\Helpers;

use Jaguata\Config\AppConfig;

/**
 * Clase para manejar sesiones de usuario
 */
class Session
{
    private static bool $initialized = false;
    private static string $sessionName = 'JAGUATA_SESSION';

    /**
     * Inicializar la sesión
     */
    public static function iniciar(): void
    {
        if (self::$initialized || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Configurar parámetros de sesión
        self::configurarSesion();
        
        // Iniciar sesión
        session_name(self::$sessionName);
        session_start();

        // Regenerar ID de sesión periódicamente para seguridad
        self::regenerarIdSiEsNecesario();

        self::$initialized = true;
    }

    /**
     * Configurar parámetros de sesión para seguridad
     */
    private static function configurarSesion(): void
    {
        // Configuración de seguridad para cookies de sesión
        ini_set('session.cookie_httponly', '1'); // Solo HTTP, no JavaScript
        ini_set('session.cookie_secure', (isset($_SERVER['HTTPS']) ? '1' : '0')); // HTTPS si está disponible
        ini_set('session.cookie_samesite', 'Strict'); // Protección CSRF
        ini_set('session.use_strict_mode', '1'); // No aceptar IDs de sesión no inicializados
        ini_set('session.use_only_cookies', '1'); // Solo cookies, no URL
        ini_set('session.cookie_lifetime', '0'); // Expire al cerrar navegador
        
        // Configurar tiempo de vida de sesión desde configuración
        if (AppConfig::isInitialized()) {
            $lifetime = AppConfig::get('security.session_lifetime', 3600);
            ini_set('session.gc_maxlifetime', (string)$lifetime);
        }
    }

    /**
     * Regenerar ID de sesión si es necesario (cada 30 minutos)
     */
    private static function regenerarIdSiEsNecesario(): void
    {
        $regenerateTime = 1800; // 30 minutos
        
        if (!isset($_SESSION['_regenerated'])) {
            $_SESSION['_regenerated'] = time();
        } elseif ($_SESSION['_regenerated'] < (time() - $regenerateTime)) {
            session_regenerate_id(true);
            $_SESSION['_regenerated'] = time();
        }
    }

    // === MÉTODOS BÁSICOS DE SESIÓN ===

    /**
     * Obtener valor de la sesión
     */
    public static function obtener(string $key, mixed $default = null): mixed
    {
        self::iniciar();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Establecer valor en la sesión
     */
    public static function establecer(string $key, mixed $value): void
    {
        self::iniciar();
        $_SESSION[$key] = $value;
    }

    /**
     * Verificar si existe una clave en la sesión
     */
    public static function existe(string $key): bool
    {
        self::iniciar();
        return isset($_SESSION[$key]);
    }

    /**
     * Eliminar una clave específica de la sesión
     */
    public static function eliminar(string $key): void
    {
        self::iniciar();
        unset($_SESSION[$key]);
    }

    /**
     * Limpiar toda la sesión (mantener la sesión activa)
     */
    public static function limpiar(): void
    {
        self::iniciar();
        $_SESSION = [];
    }

    /**
     * Destruir completamente la sesión
     */
    public static function destruir(): void
    {
        self::iniciar();
        
        // Limpiar variables de sesión
        $_SESSION = [];
        
        // Eliminar cookie de sesión
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destruir sesión
        session_destroy();
        self::$initialized = false;
    }

    // === MÉTODOS PARA MANEJO DE USUARIO (compatibles con tu código) ===

    /**
     * Verificar si hay un usuario logueado
     * Método compatible con isLoggedIn()
     */
    public static function isLoggedIn(): bool
    {
        return self::obtener('usuario_logueado', false) === true;
    }

    /**
     * Alias para isLoggedIn() (mantener compatibilidad)
     */
    public static function usuarioLogueado(): bool
    {
        return self::isLoggedIn();
    }

    /**
     * Iniciar sesión de usuario
     */
    public static function login(array $usuario): void
    {
        self::iniciar();
        self::regenerarId(); // Seguridad: nuevo ID después de login
        
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_apellido'] = $usuario['apellido'] ?? '';
        $_SESSION['usuario_tipo'] = $usuario['tipo'] ?? 'cliente';
        $_SESSION['usuario_rol'] = $usuario['rol'] ?? $usuario['tipo'] ?? 'cliente';
        $_SESSION['usuario_logueado'] = true;
        $_SESSION['tiempo_login'] = time();
    }

    /**
     * Alias para login() (mantener compatibilidad)
     */
    public static function iniciarSesionUsuario(array $usuario): void
    {
        self::login($usuario);
    }

    /**
     * Cerrar sesión de usuario
     */
    public static function logout(): void
    {
        self::limpiar();
        self::regenerarId();
    }

    /**
     * Alias para logout() (mantener compatibilidad)
     */
    public static function cerrarSesionUsuario(): void
    {
        self::logout();
    }

    /**
     * Obtener ID del usuario actual
     */
    public static function getUserId(): ?int
    {
        return self::obtener('usuario_id');
    }

    /**
     * Alias para getUserId() (mantener compatibilidad)
     */
    public static function obtenerIdUsuario(): ?int
    {
        return self::getUserId();
    }

    /**
     * Obtener email del usuario actual
     */
    public static function getUserEmail(): ?string
    {
        return self::obtener('usuario_email');
    }

    /**
     * Obtener nombre completo del usuario actual
     */
    public static function getUserName(): ?string
    {
        $nombre = self::obtener('usuario_nombre', '');
        $apellido = self::obtener('usuario_apellido', '');
        
        return trim($nombre . ' ' . $apellido) ?: null;
    }

    /**
     * Obtener rol del usuario actual
     */
    public static function getUserRole(): ?string
    {
        return self::obtener('usuario_rol');
    }

    /**
     * Alias para getUserRole() (el que usas en tu código)
     */
    public static function getUsuarioRol(): ?string
    {
        return self::getUserRole();
    }

    /**
     * Obtener tipo del usuario actual
     */
    public static function getUserType(): ?string
    {
        return self::obtener('usuario_tipo');
    }

    /**
     * Obtener todos los datos del usuario actual
     */
    public static function getUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => self::obtener('usuario_id'),
            'email' => self::obtener('usuario_email'),
            'nombre' => self::obtener('usuario_nombre'),
            'apellido' => self::obtener('usuario_apellido'),
            'tipo' => self::obtener('usuario_tipo', 'cliente'),
            'rol' => self::obtener('usuario_rol', 'cliente')
        ];
    }

    /**
     * Alias para getUser() (mantener compatibilidad)
     */
    public static function obtenerUsuario(): ?array
    {
        return self::getUser();
    }

    /**
     * Verificar si el usuario es de un rol específico
     */
    public static function hasRole(string $rol): bool
    {
        return self::obtener('usuario_rol') === $rol;
    }

    /**
     * Verificar si el usuario es de un tipo específico
     */
    public static function isUserType(string $tipo): bool
    {
        return self::obtener('usuario_tipo') === $tipo;
    }

    /**
     * Alias para isUserType() (mantener compatibilidad)
     */
    public static function esTipoUsuario(string $tipo): bool
    {
        return self::isUserType($tipo);
    }

    /**
     * Verificar si el usuario es administrador
     */
    public static function isAdmin(): bool
    {
        $rol = self::getUserRole();
        return in_array($rol, ['admin', 'super_admin']);
    }

    /**
     * Verificar si el usuario es paseador
     */
    public static function isPaseador(): bool
    {
        return self::getUserRole() === 'paseador' || self::getUserType() === 'paseador';
    }

    /**
     * Verificar si el usuario es cliente
     */
    public static function isCliente(): bool
    {
        return self::getUserRole() === 'cliente' || self::getUserType() === 'cliente';
    }

    // === MÉTODOS PARA FLASH MESSAGES ===

    /**
     * Establecer mensaje flash
     */
    public static function setFlash(string $tipo, string $mensaje): void
    {
        self::iniciar();
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash'][$tipo] = $mensaje;
    }

    /**
     * Alias para setFlash() (mantener compatibilidad)
     */
    public static function establecerFlash(string $tipo, string $mensaje): void
    {
        self::setFlash($tipo, $mensaje);
    }

    /**
     * Obtener mensaje flash (se elimina después de obtenerlo)
     */
    public static function getFlash(string $tipo): ?string
    {
        self::iniciar();
        $mensaje = $_SESSION['_flash'][$tipo] ?? null;
        unset($_SESSION['_flash'][$tipo]);
        return $mensaje;
    }

    /**
     * Alias para getFlash() (mantener compatibilidad)
     */
    public static function obtenerFlash(string $tipo): ?string
    {
        return self::getFlash($tipo);
    }

    /**
     * Verificar si existe un mensaje flash
     */
    public static function hasFlash(string $tipo): bool
    {
        self::iniciar();
        return isset($_SESSION['_flash'][$tipo]);
    }

    /**
     * Alias para hasFlash() (mantener compatibilidad)
     */
    public static function tieneFlash(string $tipo): bool
    {
        return self::hasFlash($tipo);
    }

    /**
     * Obtener todos los mensajes flash y limpiarlos
     */
    public static function getAllFlash(): array
    {
        self::iniciar();
        $messages = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
        return $messages;
    }

    /**
     * Alias para getAllFlash() (mantener compatibilidad)
     */
    public static function obtenerTodosFlash(): array
    {
        return self::getAllFlash();
    }

    // === MÉTODOS DE UTILIDAD PARA FLASH MESSAGES ===

    /**
     * Establecer mensaje de éxito
     */
    public static function success(string $mensaje): void
    {
        self::setFlash('success', $mensaje);
    }

    /**
     * Alias para success() (mantener compatibilidad)
     */
    public static function exito(string $mensaje): void
    {
        self::success($mensaje);
    }

    /**
     * Establecer mensaje de error
     */
    public static function error(string $mensaje): void
    {
        self::setFlash('error', $mensaje);
    }

    /**
     * Establecer mensaje de advertencia
     */
    public static function warning(string $mensaje): void
    {
        self::setFlash('warning', $mensaje);
    }

    /**
     * Alias para warning() (mantener compatibilidad)
     */
    public static function advertencia(string $mensaje): void
    {
        self::warning($mensaje);
    }

    /**
     * Establecer mensaje informativo
     */
    public static function info(string $mensaje): void
    {
        self::setFlash('info', $mensaje);
    }

    // === MÉTODOS DE UTILIDAD ===

    /**
     * Verificar si hay una sesión activa
     */
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Alias para isActive() (mantener compatibilidad)
     */
    public static function activa(): bool
    {
        return self::isActive();
    }

    /**
     * Obtener ID de la sesión actual
     */
    public static function getId(): string
    {
        self::iniciar();
        return session_id();
    }

    /**
     * Regenerar ID de sesión (útil después de login)
     */
    public static function regenerateId(): void
    {
        self::iniciar();
        session_regenerate_id(true);
        $_SESSION['_regenerated'] = time();
    }

    /**
     * Alias para regenerateId() (mantener compatibilidad)
     */
    public static function regenerarId(): void
    {
        self::regenerateId();
    }

    /**
     * Verificar si la configuración está inicializada
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }
}