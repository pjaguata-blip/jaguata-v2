<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Usuario;
use Jaguata\Models\Paseador;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Exception;

class AuthController
{
    private $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function checkRole(string $role)
    {
        // Evitar notices y confiar en Session helper
        if (!Session::isLoggedIn() || Session::get('rol') !== $role) {
            header('Location: /jaguata/public/login.php', true, 302);
            exit;
        }
    }

    public function checkAuth()
    {
        if (!Session::isLoggedIn()) {
            header('Location: /jaguata/public/login.php', true, 302);
            exit;
        }
    }

    public function showLogin()
    {
        if (Session::isLoggedIn()) {
            header('Location: /jaguata/features/' . Session::get('rol') . '/Dashboard.php', true, 302);
            exit;
        }
        include __DIR__ . '/../../public/login.php';
    }

    public function showRegister()
    {
        if (Session::isLoggedIn()) {
            header('Location: /jaguata/features/' . Session::get('rol') . '/Dashboard.php', true, 302);
            exit;
        }
        include __DIR__ . '/../../public/registro.php';
    }

    // =====================
    // API: LOGIN
    // =====================
    public function apiLogin(): array
    {
        // 1) CSRF
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Validaciones::verificarCSRF($csrf)) {
            return ['success' => false, 'error' => 'CSRF inválido'];
        }

        // 2) Inputs
        $email = \trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            return ['success' => false, 'error' => 'Email y contraseña requeridos'];
        }

        // 3) Email válido (usar bool)
        if (!Validaciones::isEmail($email)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }

        try {
            $usuario = $this->usuarioModel->getByEmail($email);

            // Evitar filtraciones de timing en comparación de password:
            // si no existe usuario, aún así realiza un password_verify contra un hash dummy
            $hash = $usuario['pass'] ?? password_hash('dummy', PASSWORD_DEFAULT);

            if (!$usuario || !password_verify($password, $hash)) {
                // (Opcional) contar intentos fallidos por IP/usuario para backoff
                return ['success' => false, 'error' => 'Credenciales inválidas'];
            }

            // Regenerar ID de sesión y setear datos
            Session::login($usuario);

            return [
                'success' => true,
                'usuario' => [
                    'id'     => $usuario['usu_id'] ?? $usuario['id'] ?? null,
                    'nombre' => $usuario['nombre'] ?? '',
                    'email'  => $usuario['email'] ?? '',
                    'rol'    => $usuario['rol'] ?? ''
                ]
            ];
        } catch (Exception $e) {
            error_log('Error en login: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error interno del servidor'];
        }
    }

    public function login()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: /jaguata/public/login.php', true, 302);
            exit;
        }

        $result = $this->apiLogin();

        if ($result['success']) {
            $rol = Session::get('rol');
            $redirectUrl = '/jaguata/features/' . $rol . '/Dashboard.php';
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $_SESSION['error'] = $result['error'] ?? 'Error de autenticación';
        header('Location: /jaguata/public/login.php', true, 302);
        exit;
    }

    // =====================
    // API: REGISTER
    // =====================
    public function apiRegister(): array
    {
        // 1) CSRF
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Validaciones::verificarCSRF($csrf)) {
            return ['success' => false, 'error' => 'CSRF inválido'];
        }

        // 2) Inputs
        $nombre   = \trim($_POST['nombre'] ?? '');
        $email    = \trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $rol      = $_POST['rol'] ?? 'dueno';
        $telefono = \trim($_POST['telefono'] ?? '');

        if ($nombre === '' || $email === '' || $password === '') {
            return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
        }

        if (!Validaciones::isEmail($email)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }

        $pwdCheck = Validaciones::validarPassword($password, 8);
        if (!$pwdCheck['valido']) {
            return ['success' => false, 'error' => $pwdCheck['mensaje']];
        }

        if ($confirmPassword !== '' && $password !== $confirmPassword) {
            return ['success' => false, 'error' => 'Las contraseñas no coinciden'];
        }

        if ($telefono !== '' && !Validaciones::validarTelefono($telefono)) {
            return ['success' => false, 'error' => 'Teléfono inválido (ej: 0981-123-456 o solo dígitos)'];
        }

        if (!\in_array($rol, ['dueno', 'paseador'], true)) {
            return ['success' => false, 'error' => 'Rol inválido'];
        }

        try {
            if ($this->usuarioModel->getByEmail($email)) {
                return ['success' => false, 'error' => 'El email ya está registrado'];
            }

            $usuarioId = $this->usuarioModel->create([
                'nombre'   => $nombre,
                'email'    => $email,
                'pass'     => password_hash($password, PASSWORD_DEFAULT),
                'rol'      => $rol,
                'telefono' => $telefono
            ]);

            if ($usuarioId && $rol === 'paseador') {
                $paseadorModel = new Paseador();
                $paseadorModel->create([
                    'paseador_id'     => $usuarioId,
                    'experiencia'     => '',
                    'zona'            => '',
                    'precio_hora'     => 0,
                    'disponibilidad'  => true,
                    'calificacion'    => 0,
                    'total_paseos'    => 0
                ]);
            }

            return $usuarioId
                ? ['success' => true, 'message' => 'Usuario registrado correctamente']
                : ['success' => false, 'error' => 'Error al registrar usuario'];
        } catch (Exception $e) {
            error_log('Error en registro: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error interno del servidor'];
        }
    }

    public function register()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: /jaguata/public/registro.php', true, 302);
            exit;
        }

        $result = $this->apiRegister();

        if ($result['success']) {
            $_SESSION['success'] = 'Usuario registrado exitosamente. Inicia sesión para continuar.';
            header('Location: /jaguata/public/login.php', true, 302);
            exit;
        }

        $_SESSION['error'] = $result['error'] ?? 'No se pudo registrar';
        header('Location: /jaguata/public/registro.php', true, 302);
        exit;
    }

    // =====================
    // API: LOGOUT
    // =====================
    public function apiLogout(): array
    {
        Session::logout();
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }

    public function logout()
    {
        Session::logout();
        header('Location: /jaguata/public/index.php', true, 302);
        exit;
    }

    // =====================
    // API: GET PROFILE
    // =====================
    public function apiGetProfile(): array
    {
        if (!Session::isLoggedIn()) {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        return [
            'success' => true,
            'usuario' => [
                'id'     => Session::get('usuario_id') ?? Session::get('id'),
                'nombre' => Session::get('nombre'),
                'email'  => Session::get('email'),
                'rol'    => Session::get('rol')
            ]
        ];
    }
}
