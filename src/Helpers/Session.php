<?php

namespace Jaguata\Helpers;

class Session
{
    /** Asegura que la sesión esté iniciada con el nombre correcto */
    private static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            \session_name(defined('SESSION_NAME') ? SESSION_NAME : 'JAGUATA_SESSION');
            \session_start();
        }
    }

    public static function login(array $usuario): void
    {
        self::start();
        $_SESSION['usuario_id']   = $usuario['usu_id'] ?? null;
        $_SESSION['nombre']       = $usuario['nombre'] ?? '';
        $_SESSION['email']        = $usuario['email'] ?? '';
        $_SESSION['rol']          = strtolower($usuario['rol'] ?? 'dueno'); // 🔹 agregado strtolower
        $_SESSION['usuario_tipo'] = strtolower($usuario['rol'] ?? 'dueno');
    }

    /** Cierra la sesión limpiando variables, cookie e ID de sesión */
    public static function logout(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'] ?? '/', $p['domain'] ?? '', $p['secure'] ?? false, $p['httponly'] ?? true);
        }

        session_destroy();

        // sesión limpia para evitar reutilización de ID
        session_start();
        session_regenerate_id(true);
        $_SESSION = [];
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        return isset($_SESSION['usuario_id']);
    }

    public static function getUsuarioRol(): ?string
    {
        self::start();
        return $_SESSION['rol'] ?? null;
    }

    public static function getUsuarioRolSeguro(): ?string
    {
        self::start();
        $rol = $_SESSION['rol'] ?? null;
        return ($rol && in_array($rol, ['dueno', 'paseador'], true)) ? $rol : null;
    }

    public static function getUsuarioNombre(): ?string
    {
        self::start();
        return $_SESSION['nombre'] ?? null;
    }

    /** ✅ Nuevo método: obtener email del usuario logueado */
    public static function getUsuarioEmail(): ?string
    {
        self::start();
        return $_SESSION['email'] ?? null;
    }

    public static function getUsuarioId(): ?int
    {
        self::start();
        return $_SESSION['usuario_id'] ?? null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function setFlash(string $key, string $message): void
    {
        self::start();
        $_SESSION['flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        self::start();
        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }

    public static function getFlashMessages(): array
    {
        self::start();
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    public static function setError(string $mensaje): void
    {
        self::setFlash('error', $mensaje);
    }

    public static function getError(): ?string
    {
        return self::getFlash('error');
    }

    public static function setSuccess(string $mensaje): void
    {
        self::setFlash('success', $mensaje);
    }

    public static function getSuccess(): ?string
    {
        return self::getFlash('success');
    }
}
