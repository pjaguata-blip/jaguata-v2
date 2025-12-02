<?php

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Models/Usuario.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Models\Usuario;

AppConfig::init();

class UsuarioController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    /**
     * Listar todos los usuarios (para admin)
     */
    public function index(): array
    {
        return $this->usuarioModel->all();
    }

    public function getById(int $id): ?array
    {
        return $this->usuarioModel->find($id);
    }

    public function actualizarUsuario(int $id, array $data): bool
    {
        // Evitar que intenten cambiar el PK
        unset($data['usu_id']);
        return $this->usuarioModel->update($id, $data);
    }

    /**
     * Datos formateados para exportación a Excel
     * Coincide con las columnas:
     * ID, Nombre, Email, Rol, Estado, Fecha Creación, Fecha Actualización
     */
    public function obtenerDatosExportacion(): array
    {
        // Traemos todos los usuarios desde el modelo
        $usuarios = $this->usuarioModel->all() ?? [];

        $export = [];

        foreach ($usuarios as $u) {
            $export[] = [
                'usu_id'       => (string)($u['usu_id']       ?? ''), // string para evitar líos en Excel
                'nombre'       => (string)($u['nombre']       ?? ''),
                'email'        => (string)($u['email']        ?? ''),
                'rol'          => (string)($u['rol']          ?? ''),
                'estado'       => (string)($u['estado']       ?? ''),
                'created_at'   => (string)($u['created_at']   ?? ''),
                'updated_at'   => (string)($u['updated_at']   ?? ''),
            ];
        }

        return $export;
    }
    /**
     * Ejecuta una acción de admin sobre el usuario
     * Acciones soportadas:
     *  - eliminar
     *  - suspender
     *  - activar
     *  - desactivar
     *  - aprobar
     *  - rechazar
     */
    public function ejecutarAccion(string $accion, int $id): array
    {
        $accion = strtolower(trim($accion));

        // Verificar que el usuario exista
        $usuario = $this->getById($id);
        if (!$usuario) {
            return [
                'ok'      => false,
                'mensaje' => 'Usuario no encontrado.'
            ];
        }

        try {
            $ok      = false;
            $mensaje = 'Acción procesada.';

            switch ($accion) {
                case 'eliminar':
                    $ok      = $this->usuarioModel->delete($id);
                    $mensaje = $ok
                        ? 'Usuario eliminado correctamente.'
                        : 'No se pudo eliminar el usuario.';
                    break;

                case 'suspender':
                    $ok      = $this->cambiarEstado($id, 'suspendido');
                    $mensaje = $ok
                        ? 'Usuario suspendido correctamente.'
                        : 'No se pudo suspender el usuario.';
                    break;

                case 'activar':
                    $ok      = $this->cambiarEstado($id, 'activo');
                    $mensaje = $ok
                        ? 'Usuario activado correctamente.'
                        : 'No se pudo activar el usuario.';
                    break;

                case 'desactivar':
                    $ok      = $this->cambiarEstado($id, 'inactivo');
                    $mensaje = $ok
                        ? 'Usuario desactivado correctamente.'
                        : 'No se pudo desactivar el usuario.';
                    break;

                case 'aprobar':
                    $ok      = $this->cambiarEstado($id, 'aprobado');
                    $mensaje = $ok
                        ? 'Usuario aprobado correctamente.'
                        : 'No se pudo aprobar el usuario.';
                    break;

                case 'rechazar':
                    $ok      = $this->cambiarEstado($id, 'rechazado');
                    $mensaje = $ok
                        ? 'Usuario rechazado correctamente.'
                        : 'No se pudo rechazar el usuario.';
                    break;

                default:
                    return [
                        'ok'      => false,
                        'mensaje' => 'Acción no válida.'
                    ];
            }

            return [
                'ok'      => (bool)$ok,
                'mensaje' => $mensaje
            ];
        } catch (\Throwable $e) {
            error_log('❌ Error en UsuarioController::ejecutarAccion(): ' . $e->getMessage());
            return [
                'ok'      => false,
                'mensaje' => 'Ocurrió un error al procesar la acción.'
            ];
        }
    }

    /**
     * Helper interno para cambiar estado
     */
    private function cambiarEstado(int $id, string $estado): bool
    {
        return $this->usuarioModel->update($id, [
            'estado' => $estado
        ]);
    }
    /**
     * Cambia la contraseña del usuario actualmente logueado (admin, dueño o paseador).
     */
    public function cambiarPasswordActual(string $passActual, string $passNueva): array
    {
        if (!Session::isLoggedIn()) {
            return ['success' => false, 'error' => 'Sesión no válida. Inicia sesión nuevamente.'];
        }

        $usuarioId = Session::getUsuarioId();

        try {
            $usuario = $this->usuarioModel->find($usuarioId);

            if (!$usuario || empty($usuario['pass'])) {
                return ['success' => false, 'error' => 'Usuario no encontrado.'];
            }

            if (!password_verify($passActual, $usuario['pass'])) {
                return ['success' => false, 'error' => 'La contraseña actual es incorrecta.'];
            }

            if (strlen($passNueva) < 6) {
                return ['success' => false, 'error' => 'La nueva contraseña debe tener al menos 6 caracteres.'];
            }

            $hash = password_hash($passNueva, PASSWORD_DEFAULT);

            $ok = $this->usuarioModel->actualizarPassword($usuarioId, $hash);

            if (!$ok) {
                return ['success' => false, 'error' => 'No se pudo actualizar la contraseña.'];
            }

            return ['success' => true];
        } catch (\PDOException $e) {
            error_log('Error UsuarioController::cambiarPasswordActual => ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error interno al cambiar la contraseña.'];
        }
    }
}
