<?php

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Helpers/Validaciones.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Helpers/Auditoria.php'; // 🔹 AUDITORÍA

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Auditoria; // 🔹 AUDITORÍA

AppConfig::init();

class AuthController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    /**
     * Verifica que el usuario tenga un rol específico (modo WEB)
     */
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

    /**
     * Maneja el POST del formulario de login (web)
     */
    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            Session::setError('Debes ingresar email y contraseña.');
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }

        $usuario = $this->usuarioModel->getByEmail($email);
        if (!$usuario || empty($usuario['pass']) || !password_verify($password, $usuario['pass'])) {
            // 🔹 AUDITORÍA: intento de login fallido
            Auditoria::log(
                'LOGIN FALLIDO',
                'Autenticación',
                'Intento de login con email: ' . $email
            );

            Session::setError('Credenciales incorrectas.');
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }

        // Login OK
        Session::login($usuario);

        // 🔹 AUDITORÍA: login exitoso (admin / dueño / paseador)
        Auditoria::log(
            'LOGIN',
            'Autenticación',
            'Inicio de sesión del usuario: ' . ($usuario['email'] ?? ''),
            (int) $usuario['usu_id']
        );

        // Redirigir según rol
        $rol = $usuario['rol'] ?? 'dueno';

        if ($rol === 'admin') {
            header('Location: ' . BASE_URL . '/features/admin/Dashboard.php');
        } elseif ($rol === 'paseador') {
            header('Location: ' . BASE_URL . '/features/paseador/Dashboard.php');
        } else {
            // dueño
            header('Location: ' . BASE_URL . '/features/dueno/Dashboard.php');
        }
        exit;
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        // 🔹 AUDITORÍA: logout antes de cerrar sesión
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

    /**
     * API: login (devuelve JSON, por si usás fetch/ajax)
     */
    public function apiLogin(): array
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            return ['success' => false, 'error' => 'Campos incompletos'];
        }

        $usuario = $this->usuarioModel->getByEmail($email);
        if (!$usuario || empty($usuario['pass']) || !password_verify($password, $usuario['pass'])) {
            // 🔹 AUDITORÍA: intento de login API fallido
            Auditoria::log(
                'LOGIN API FALLIDO',
                'Autenticación',
                'Intento de login API con email: ' . $email
            );

            return ['success' => false, 'error' => 'Credenciales incorrectas'];
        }

        Session::login($usuario);

        // 🔹 AUDITORÍA: login API exitoso
        Auditoria::log(
            'LOGIN API',
            'Autenticación',
            'Inicio de sesión vía API del usuario: ' . ($usuario['email'] ?? ''),
            (int) $usuario['usu_id']
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

    /**
     * API: registro de usuario
     */
    public function apiRegister(): array
    {
        $nombre   = trim($_POST['nombre'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $pass     = $_POST['pass'] ?? '';
        $rol      = $_POST['rol'] ?? 'dueno';

        if ($nombre === '' || $email === '' || $pass === '') {
            return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }

        // Revisar si ya existe
        if ($this->usuarioModel->getByEmail($email)) {
            return ['success' => false, 'error' => 'Ya existe un usuario con ese email'];
        }

        $data = [
            'nombre' => $nombre,
            'email'  => $email,
            'pass'   => $pass,
            'rol'    => $rol,
        ];

        $result = $this->usuarioModel->crearDesdeRegistro($data);
        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'Error al registrar'];
        }

        $usuario = $result['usuario'];
        Session::login($usuario);

        // 🔹 AUDITORÍA: registro de usuario (dueño o paseador normalmente)
        Auditoria::log(
            'REGISTRO',
            'Autenticación',
            'Registro de nuevo usuario: ' . $email . ' con rol ' . $rol,
            (int) $usuario['usu_id']
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
    public function checkRole(string $rol): void
    {
        $this->requireRole([$rol]);
    }
}
