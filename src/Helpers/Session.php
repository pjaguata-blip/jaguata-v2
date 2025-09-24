<?php
namespace Jaguata\Helpers;

class Session
{
    private static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(defined('SESSION_NAME') ? SESSION_NAME : 'JAGUATA_SESSION');
            session_start();
        }
    }

    public static function login(array $usuario): void
    {
        self::start();
        $_SESSION['usuario_id']   = $usuario['usu_id'] ?? null;
        $_SESSION['nombre']       = $usuario['nombre'] ?? '';
        $_SESSION['email']        = $usuario['email'] ?? '';
        $_SESSION['rol']          = $usuario['rol'] ?? 'dueno';
        $_SESSION['usuario_tipo'] = $usuario['rol'] ?? 'dueno';
    }

    public static function logout(): void
    {
        self::start();
        session_unset();
        session_destroy();
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

    public static function getUsuarioNombre(): ?string
    {
        self::start();
        return $_SESSION['nombre'] ?? null;
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

    // 🔹 Métodos directos para errores y éxitos
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
