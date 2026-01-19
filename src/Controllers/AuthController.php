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
use PDO;
use Throwable;

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

        if (!empty($_POST['remember_me'])) {
            setcookie('remember_email', $email, time() + (60 * 60 * 24 * 30), '/');
        } else {
            setcookie('remember_email', '', time() - 3600, '/');
        }

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
        $nombre   = trim((string)($_POST['nombre'] ?? ''));
        $email    = strtolower(trim((string)($_POST['email'] ?? '')));
        $pass     = (string)($_POST['pass'] ?? ($_POST['password'] ?? ''));
        $rol      = strtolower(trim((string)($_POST['rol'] ?? 'dueno')));
        $telefono = trim((string)($_POST['telefono'] ?? ''));

        $acepto = !empty($_POST['acepto_terminos']) ? 1 : 0;

        if ($nombre === '' || $email === '' || $pass === '' || $telefono === '') {
            return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
        }

        if (!in_array($rol, ['dueno','paseador'], true)) {
            return ['success' => false, 'error' => 'Rol inválido'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }

        if (strlen($pass) < 6) {
            return ['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres'];
        }

        if ($acepto !== 1) {
            return ['success' => false, 'error' => 'Debes aceptar las Bases y Condiciones'];
        }

        if ($this->usuarioModel->getByEmail($email)) {
            return ['success' => false, 'error' => 'Ya existe un usuario con ese email'];
        }

        /* ==========================
           SUBIDA DE ARCHIVOS (solo paseador)
           ========================== */
        $uploadsDirAbs = dirname(__DIR__, 2) . '/public/assets/uploads/documentos';
        $uploadsDirRel = 'assets/uploads/documentos';

        if (!is_dir($uploadsDirAbs)) {
            @mkdir($uploadsDirAbs, 0775, true);
        }

        $paths = [
            'foto_cedula_frente'       => null,
            'foto_cedula_dorso'        => null,
            'foto_selfie'              => null,
            'certificado_antecedentes' => null,
        ];

        if ($rol === 'paseador') {
            $map = [
                'cedula_frente' => 'foto_cedula_frente',
                'cedula_dorso'  => 'foto_cedula_dorso',
                'selfie'        => 'foto_selfie',
                'antecedentes'  => 'certificado_antecedentes',
            ];

            foreach ($map as $inputName => $dbField) {
                if (empty($_FILES[$inputName]['name'])) {
                    return ['success' => false, 'error' => 'Faltan documentos obligatorios para paseador'];
                }
                if (!isset($_FILES[$inputName]['error']) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
                    return ['success' => false, 'error' => 'Error al subir un documento. Probá nuevamente'];
                }

                $tmp  = (string)$_FILES[$inputName]['tmp_name'];
                $orig = (string)$_FILES[$inputName]['name'];

                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $permitidas = ['jpg','jpeg','png','webp','pdf'];

                if (!in_array($ext, $permitidas, true)) {
                    return ['success' => false, 'error' => "Formato no permitido en $inputName (solo jpg, png, webp, pdf)"];
                }

                $safeEmail = preg_replace('/[^a-z0-9]+/i', '_', $email);
                $fileName  = $dbField . '_' . $safeEmail . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                $destAbs = $uploadsDirAbs . '/' . $fileName;
                $destRel = $uploadsDirRel . '/' . $fileName;

                if (!move_uploaded_file($tmp, $destAbs)) {
                    return ['success' => false, 'error' => 'No se pudo guardar un documento. Revisá permisos de carpeta uploads.'];
                }

                $paths[$dbField] = $destRel;
            }
        }

        /* ==========================
           CREAR USUARIO + CREAR FILA EN PASEADORES (si corresponde)
           ========================== */
        $pdo = AppConfig::db();
        $pdo->beginTransaction();

        try {
            $data = [
                'nombre' => $nombre,
                'email'  => $email,
                'pass'   => $pass,
                'rol'    => $rol,
                'estado' => 'pendiente',

                'telefono'         => $telefono,
                'acepto_terminos'  => 1,
                'fecha_aceptacion' => date('Y-m-d H:i:s'),
                'ip_registro'      => $_SERVER['REMOTE_ADDR'] ?? null,

                'foto_cedula_frente'       => $paths['foto_cedula_frente'],
                'foto_cedula_dorso'        => $paths['foto_cedula_dorso'],
                'foto_selfie'              => $paths['foto_selfie'],
                'certificado_antecedentes' => $paths['certificado_antecedentes'],
            ];

            $result = $this->usuarioModel->crearDesdeRegistro($data);
            if (empty($result['success'])) {
                $pdo->rollBack();
                return ['success' => false, 'error' => $result['error'] ?? 'Error al registrar'];
            }

            $usuario = $result['usuario'];
            $newId   = (int)($usuario['usu_id'] ?? 0);

            // ✅ FIX: crear paseador SIEMPRE que rol = paseador
            if ($rol === 'paseador' && $newId > 0) {
                $sqlP = "
                    INSERT INTO paseadores (
                        paseador_id, nombre, experiencia, disponible, zona, descripcion,
                        foto_url, precio_hora, disponibilidad, calificacion, total_paseos, created_at, latitud, longitud
                    )
                    VALUES (
                        :id, :nombre, '', 1, '', '',
                        NULL, 0, 1, 0, 0, NOW(), NULL, NULL
                    )
                    ON DUPLICATE KEY UPDATE
                        nombre = VALUES(nombre)
                ";
                $stP = $pdo->prepare($sqlP);
                $stP->execute([
                    ':id'     => $newId,
                    ':nombre' => $nombre,
                ]);
            }

            $pdo->commit();

            // ✅ opcional: loguear automáticamente
            Session::login($usuario);

            Auditoria::log(
                'REGISTRO',
                'Autenticación',
                'Registro de nuevo usuario: ' . $email . ' con rol ' . $rol,
                (int)($usuario['usu_id'] ?? 0)
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

        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('AuthController::apiRegister error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error interno al registrar.'];
        }
    }
}
