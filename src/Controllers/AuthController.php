<?php
namespace Jaguata\Controllers;

use Jaguata\Models\Usuario;
use Jaguata\Models\Paseador;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;

class AuthController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    /**
     * Verifica que el usuario tenga un rol específico
     */
    public function checkRole(string $role) {
        if (!Session::isLoggedIn() || $_SESSION['usuario_tipo'] !== $role) {
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }
    }

    // =====================
    // API: LOGIN
    // =====================
    public function apiLogin(): array {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            return ['success' => false, 'error' => 'Email y contraseña requeridos'];
        }

        $usuario = $this->usuarioModel->findByEmail($email);

        if (!$usuario || !password_verify($password, $usuario['pass'])) {
            return ['success' => false, 'error' => 'Credenciales inválidas'];
        }

        Session::login($usuario);

        return [
            'success' => true,
            'usuario' => [
                'id'     => $usuario['usu_id'],
                'nombre' => $usuario['nombre'],
                'email'  => $usuario['email'],
                'rol'    => $usuario['rol']
            ]
        ];
    }

    // =====================
    // API: REGISTER
    // =====================
    public function apiRegister(): array {
        $nombre   = trim($_POST['nombre'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol      = $_POST['rol'] ?? 'dueno';
        $telefono = trim($_POST['telefono'] ?? '');

        if (!$nombre || !$email || !$password) {
            return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
        }

        if (!Validaciones::validarEmail($email)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }

        if ($this->usuarioModel->findByEmail($email)) {
            return ['success' => false, 'error' => 'El email ya está registrado'];
        }

        $usuarioId = $this->usuarioModel->create([
            'nombre'   => $nombre,
            'email'    => $email,
            'pass'     => password_hash($password, PASSWORD_DEFAULT),
            'rol'      => $rol,
            'telefono' => $telefono
        ]);

        if ($rol === 'paseador') {
            $paseadorModel = new Paseador();
            $paseadorModel->create([
                'paseador_id'  => $usuarioId,
                'experiencia'  => '',
                'zona'         => '',
                'precio_hora'  => 0
            ]);
        }

        return $usuarioId
            ? ['success' => true, 'message' => 'Usuario registrado correctamente']
            : ['success' => false, 'error' => 'Error al registrar usuario'];
    }

    // =====================
    // API: LOGOUT
    // =====================
    public function apiLogout(): array {
        Session::logout();
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }

    // =====================
    // API: GET PROFILE
    // =====================
    public function apiGetProfile(): array {
        if (!Session::isLoggedIn()) {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        return [
            'success' => true,
            'usuario' => [
                'id'     => $_SESSION['usuario_id'],
                'nombre' => $_SESSION['nombre'],
                'email'  => $_SESSION['email'],
                'rol'    => $_SESSION['rol']
            ]
        ];
    }
}
