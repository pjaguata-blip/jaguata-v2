<?php

namespace Jaguata\Helpers;

class Session
{
    /** Inicia la sesión con el nombre adecuado */
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

        $_SESSION['usuario_id']     = $usuario['usu_id'] ?? null;
        $_SESSION['usuario_nombre'] = $usuario['nombre'] ?? null;
        $_SESSION['usuario_email']  = $usuario['email']  ?? null;
        $_SESSION['usuario_rol']    = $usuario['rol']    ?? null;

        // ✅ CORREGIDO: solo una vez
        $_SESSION['usuario_estado'] = $usuario['estado'] ?? 'pendiente';
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];

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
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        return !empty($_SESSION['usuario_id']);
    }

    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        self::start();
        if ($value === null) {
            unset($_SESSION[$key]);
        } else {
            $_SESSION[$key] = $value;
        }
    }

    public static function getUsuarioId(): ?int
    {
        self::start();
        return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
    }

    public static function getUsuarioNombre(): ?string
    {
        self::start();
        return $_SESSION['usuario_nombre'] ?? null;
    }

    public static function getUsuarioEmail(): ?string
    {
        self::start();
        return $_SESSION['usuario_email'] ?? null;
    }

    public static function getUsuarioRol(): ?string
    {
        self::start();
        return $_SESSION['usuario_rol'] ?? null;
    }

    /**
     * ✅ Alias para compatibilidad (evita error "Undefined method getRol()")
     */
    public static function getRol(): ?string
    {
        return self::getUsuarioRol();
    }

    public static function getUsuarioRolSeguro(): ?string
    {
        $rol = self::getUsuarioRol();
        if (!$rol) return null;

        $rolLimpio = preg_replace('/[^A-Za-z0-9_-]/', '', $rol);
        return $rolLimpio !== '' ? $rolLimpio : null;
    }

    public static function getUsuarioEstado(): ?string
    {
        self::start();
        return $_SESSION['usuario_estado'] ?? null;
    }

    /* ==========================
       FLASH MESSAGES
       ========================== */

    public static function setFlash(string $key, string $message): void
    {
        self::start();
        $_SESSION['flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        self::start();
        if (!isset($_SESSION['flash'][$key])) return null;

        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }

    public static function setError(string $message): void
    {
        self::setFlash('error', $message);
    }

    public static function getError(): ?string
    {
        return self::getFlash('error');
    }

    public static function setSuccess(string $message): void
    {
        self::setFlash('success', $message);
    }

    public static function getSuccess(): ?string
    {
        return self::getFlash('success');
    }
}
