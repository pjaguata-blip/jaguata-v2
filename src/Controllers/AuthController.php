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

    /**
     * Verifica que el usuario tenga un rol específico
     */
    public function checkRole(string $requiredRole)
    {
        $current = Session::getUsuarioRol();

        if (!Session::isLoggedIn()) {
            header("Location: /login.php");
            exit;
        }

        // 🔹 Evita bucle: si ya estás en tu dashboard
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        if ($current === $requiredRole && str_contains($currentPath, "/$requiredRole/Dashboard.php")) {
            return;
        }

        if ($current !== $requiredRole) {
            header("Location: /features/$current/Dashboard.php");
            exit;
        }
    }


    /**
     * Verifica que el usuario esté autenticado
     */
    public function checkAuth()
    {
        if (!Session::isLoggedIn()) {
            Session::logout();
            $this->safeRedirect('/jaguata/public/login.php');
        }
    }

    /**
     * Redirección segura que evita loops
     */
    private function safeRedirect(string $target): void
    {
        $current = $_SERVER['PHP_SELF'] ?? '';
        if (
            strpos($current, 'login.php') !== false ||
            strpos($current, 'registro.php') !== false ||
            strpos($current, 'index.php') !== false ||
            strpos($current, 'logout.php') !== false
        ) {
            return;
        }

        header("Location: {$target}", true, 302);
        exit;
    }

    // =====================
    // Vistas
    // =====================

    public function showLogin()
    {
        if (Session::isLoggedIn()) {
            $rol = Session::getUsuarioRol();
            if ($rol && in_array($rol, ['dueno', 'paseador'], true)) {
                header('Location: /jaguata/features/' . $rol . '/Dashboard.php', true, 302);
                exit;
            } else {
                Session::logout();
            }
        }
        include __DIR__ . '/../../public/login.php';
    }

    public function showRegister()
    {
        if (Session::isLoggedIn()) {
            $rol = Session::getUsuarioRol();
            if ($rol && in_array($rol, ['dueno', 'paseador'], true)) {
                header('Location: /jaguata/features/' . $rol . '/Dashboard.php', true, 302);
                exit;
            } else {
                Session::logout();
            }
        }
        include __DIR__ . '/../../public/registro.php';
    }

    // =====================
    // API: LOGIN
    // =====================

    public function apiLogin(): array
    {
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Validaciones::verificarCSRF($csrf)) {
            return ['success' => false, 'error' => 'CSRF inválido'];
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            return ['success' => false, 'error' => 'Email y contraseña requeridos'];
        }

        if (!Validaciones::isEmail($email)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }

        try {
            $usuario = $this->usuarioModel->getByEmail($email);
            $hash = $usuario['pass'] ?? password_hash('dummy', PASSWORD_DEFAULT);

            if (!$usuario || !password_verify($password, $hash)) {
                return ['success' => false, 'error' => 'Credenciales inválidas'];
            }

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
            $this->safeRedirect('/jaguata/public/login.php');
        }

        $result = $this->apiLogin();

        if ($result['success']) {
            $rol = Session::get('rol');
            if ($rol && in_array($rol, ['dueno', 'paseador'], true)) {
                header('Location: /jaguata/features/' . $rol . '/Dashboard.php', true, 302);
                exit;
            } else {
                Session::logout();
                $_SESSION['error'] = 'Rol inválido. Contacta con soporte.';
                $this->safeRedirect('/jaguata/public/login.php');
            }
        }

        $_SESSION['error'] = $result['error'] ?? 'Error de autenticación';
        $this->safeRedirect('/jaguata/public/login.php');
    }

    // =====================
    // API: REGISTER
    // =====================

    public function apiRegister(): array
    {
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Validaciones::verificarCSRF($csrf)) {
            return ['success' => false, 'error' => 'CSRF inválido'];
        }

        $nombre   = trim($_POST['nombre'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $rol      = $_POST['rol'] ?? 'dueno';
        $telefono = trim($_POST['telefono'] ?? '');

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
            return ['success' => false, 'error' => 'Teléfono inválido'];
        }

        if (!in_array($rol, ['dueno', 'paseador'], true)) {
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
            $this->safeRedirect('/jaguata/public/registro.php');
        }

        $result = $this->apiRegister();

        if ($result['success']) {
            $_SESSION['success'] = 'Usuario registrado exitosamente. Inicia sesión para continuar.';
            $this->safeRedirect('/jaguata/public/login.php');
        }

        $_SESSION['error'] = $result['error'] ?? 'No se pudo registrar';
        $this->safeRedirect('/jaguata/public/registro.php');
    }

    // =====================
    // LOGOUT
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
    // GET PROFILE
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

    // =====================
    // CONTROL DE ROLES
    // =====================

    public function requireRole(array $roles)
    {
        if (!Session::isLoggedIn()) {
            $this->safeRedirect('/jaguata/public/login.php');
        }

        $rol = Session::get('rol');
        if (!in_array($rol, $roles, true)) {
            $this->redirectToDashboard();
        }
    }

    public function redirectToDashboard()
    {
        if (!Session::isLoggedIn()) {
            $this->safeRedirect('/jaguata/public/login.php');
        }

        $rol = Session::get('rol');
        if ($rol && in_array($rol, ['dueno', 'paseador'], true)) {
            header("Location: /jaguata/features/{$rol}/Dashboard.php", true, 302);
            exit;
        }

        Session::logout();
        $this->safeRedirect('/jaguata/public/login.php');
    }
}
