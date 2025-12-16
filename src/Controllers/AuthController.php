<?php

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Helpers/Validaciones.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Helpers/Auditoria.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Auditoria;

AppConfig::init();

class AuthController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function requireRole(array $rolesPermitidos): void
    {
        if (!Session::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }

        $rolActual = Session::getUsuarioRol();
        if (!in_array($rolActual, $rolesPermitidos, true)) {
            Session::setError('No tienes permisos para acceder a esta sección.');
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }
    }

    public function checkRole(string $rol): void
    {
        $this->requireRole([$rol]);
    }

    public function login(): void
    {
        // (Opcional pero recomendado) validar CSRF
        $token = $_POST['csrf_token'] ?? null;
        if (!Validaciones::verificarCSRF(is_string($token) ? $token : null)) {
            Session::setError('Sesión expirada. Volvé a intentar.');
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }

        $email    = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            Session::setError('Debes ingresar email y contraseña.');
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }

        $usuario = $this->usuarioModel->getByEmail($email);

        if (!$usuario || empty($usuario['pass']) || !password_verify($password, (string)$usuario['pass'])) {
            Auditoria::log(
                'LOGIN FALLIDO',
                'Autenticación',
                'Intento de login con email: ' . $email
            );

            Session::setError('Credenciales incorrectas.');
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }

        // ✅ Estado: permitir aprobado y activo (porque tu admin usa ambos estados)
        $estado = (string)($usuario['estado'] ?? 'pendiente');
        if (!in_array($estado, ['aprobado', 'activo'], true)) {
            Auditoria::log(
                'LOGIN BLOQUEADO',
                'Autenticación',
                "Intento de login bloqueado (estado=$estado) para email: " . ($usuario['email'] ?? $email),
                (int)($usuario['usu_id'] ?? 0)
            );

            $msg = match ($estado) {
                'pendiente'  => 'Tu cuenta está en revisión por el administrador.',
                'rechazado'  => 'Tu solicitud fue rechazada. Contactá al administrador.',
                'cancelado'  => 'Tu cuenta fue cancelada. Contactá al administrador.',
                'inactivo'   => 'Tu cuenta está inactiva. Contactá al administrador.',
                'suspendido' => 'Tu cuenta está suspendida. Contactá al administrador.',
                default      => 'Tu cuenta no está habilitada para ingresar.',
            };

            Session::setError($msg);
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }

        // ✅ Recordarme email
        if (!empty($_POST['remember_me'])) {
            setcookie('remember_email', $email, time() + (60 * 60 * 24 * 30), '/');
        } else {
            setcookie('remember_email', '', time() - 3600, '/');
        }

        // Login OK
        Session::login($usuario);

        Auditoria::log(
            'LOGIN',
            'Autenticación',
            'Inicio de sesión del usuario: ' . ($usuario['email'] ?? ''),
            (int)$usuario['usu_id']
        );

        $rol = (string)($usuario['rol'] ?? 'dueno');

        if ($rol === 'admin') {
            header('Location: ' . BASE_URL . '/features/admin/Dashboard.php');
        } elseif ($rol === 'paseador') {
            header('Location: ' . BASE_URL . '/features/paseador/Dashboard.php');
        } else {
            header('Location: ' . BASE_URL . '/features/dueno/Dashboard.php');
        }
        exit;
    }

    public function logout(): void
    {
        if (Session::isLoggedIn()) {
            $email = Session::getUsuarioEmail() ?? 'desconocido';
            Auditoria::log(
                'LOGOUT',
                'Autenticación',
                'Cierre de sesión del usuario: ' . $email
            );
        }

        Session::logout();
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }

    public function apiLogin(): array
    {
        $email    = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            return ['success' => false, 'error' => 'Campos incompletos'];
        }

        $usuario = $this->usuarioModel->getByEmail($email);

        if (!$usuario || empty($usuario['pass']) || !password_verify($password, (string)$usuario['pass'])) {
            Auditoria::log(
                'LOGIN API FALLIDO',
                'Autenticación',
                'Intento de login API con email: ' . $email
            );

            return ['success' => false, 'error' => 'Credenciales incorrectas'];
        }

        $estado = (string)($usuario['estado'] ?? 'pendiente');
        if (!in_array($estado, ['aprobado', 'activo'], true)) {
            return ['success' => false, 'error' => 'Cuenta no habilitada'];
        }

        Session::login($usuario);

        Auditoria::log(
            'LOGIN API',
            'Autenticación',
            'Inicio de sesión vía API del usuario: ' . ($usuario['email'] ?? ''),
            (int)$usuario['usu_id']
        );

        return [
            'success' => true,
            'usuario' => [
                'id'     => $usuario['usu_id'],
                'nombre' => $usuario['nombre'],
                'email'  => $usuario['email'],
                'rol'    => $usuario['rol'],
            ],
        ];
    }

    public function apiRegister(): array
    {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $email  = strtolower(trim((string)($_POST['email'] ?? '')));
        $pass   = (string)($_POST['pass'] ?? ($_POST['password'] ?? ''));
        $rol    = (string)($_POST['rol'] ?? 'dueno');

        if ($nombre === '' || $email === '' || $pass === '') {
            return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }

        if ($this->usuarioModel->getByEmail($email)) {
            return ['success' => false, 'error' => 'Ya existe un usuario con ese email'];
        }

        $data = [
            'nombre' => $nombre,
            'email'  => $email,
            'pass'   => $pass,   // el modelo lo hashea
            'rol'    => $rol,
            'estado' => 'pendiente',
        ];

        $result = $this->usuarioModel->crearDesdeRegistro($data);
        if (empty($result['success'])) {
            return ['success' => false, 'error' => $result['error'] ?? 'Error al registrar'];
        }

        $usuario = $result['usuario'];
        Session::login($usuario);

        Auditoria::log(
            'REGISTRO',
            'Autenticación',
            'Registro de nuevo usuario: ' . $email . ' con rol ' . $rol,
            (int)$usuario['usu_id']
        );

        return [
            'success' => true,
            'usuario' => [
                'id'     => $usuario['usu_id'],
                'nombre' => $usuario['nombre'],
                'email'  => $usuario['email'],
                'rol'    => $usuario['rol'],
            ],
        ];
    }
}
