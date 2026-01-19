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

    /** ✅ Estados válidos en tu BD (según tu ENUM) */
    private array $estadosValidos = ['pendiente', 'aprobado', 'rechazado', 'cancelado'];

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    /** Listar todos los usuarios (admin) */
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
        unset($data['usu_id']); // no tocar PK
        return $this->usuarioModel->update($id, $data);
    }

    /** Exportación Excel */
    public function obtenerDatosExportacion(): array
    {
        $usuarios = $this->usuarioModel->all() ?? [];
        $export = [];

        foreach ($usuarios as $u) {
            $export[] = [
                'usu_id'     => (string)($u['usu_id'] ?? ''),
                'nombre'     => (string)($u['nombre'] ?? ''),
                'email'      => (string)($u['email'] ?? ''),
                'rol'        => (string)($u['rol'] ?? ''),
                'estado'     => (string)($u['estado'] ?? ''),
                'created_at' => (string)($u['created_at'] ?? ''),
                'updated_at' => (string)($u['updated_at'] ?? ''),
            ];
        }

        return $export;
    }

    /**
     * ✅ Acciones de admin sobre usuario
     *
     * Tu ENUM actual permite:
     *  - pendiente
     *  - aprobado
     *  - rechazado
     *  - cancelado
     *
     * Entonces mapeamos acciones “activar/suspender/desactivar” a estados válidos.
     */
    public function ejecutarAccion(string $accion, int $id): array
    {
        $accion = strtolower(trim($accion));

        $usuario = $this->getById($id);
        if (!$usuario) {
            return ['ok' => false, 'mensaje' => 'Usuario no encontrado.'];
        }

        try {
            $ok = false;
            $mensaje = 'Acción procesada.';

            switch ($accion) {

                case 'eliminar':
                    $ok = $this->usuarioModel->delete($id);
                    $mensaje = $ok ? 'Usuario eliminado correctamente.' : 'No se pudo eliminar el usuario.';
                    break;

                /** ✅ Acciones reales (compatibles con ENUM) */
                case 'aprobar':
                    $ok = $this->cambiarEstadoSeguro($id, 'aprobado');
                    $mensaje = $ok ? 'Usuario aprobado correctamente.' : 'No se pudo aprobar el usuario.';
                    break;

                case 'rechazar':
                    $ok = $this->cambiarEstadoSeguro($id, 'rechazado');
                    $mensaje = $ok ? 'Usuario rechazado correctamente.' : 'No se pudo rechazar el usuario.';
                    break;

                case 'pendiente':
                    $ok = $this->cambiarEstadoSeguro($id, 'pendiente');
                    $mensaje = $ok ? 'Usuario marcado como pendiente.' : 'No se pudo cambiar a pendiente.';
                    break;

                case 'cancelar':
                    $ok = $this->cambiarEstadoSeguro($id, 'cancelado');
                    $mensaje = $ok ? 'Usuario cancelado correctamente.' : 'No se pudo cancelar el usuario.';
                    break;

                /** ✅ Compatibilidad con tus botones actuales */
                case 'activar':
                    // tu BD no tiene "activo" => lo más cercano es "aprobado"
                    $ok = $this->cambiarEstadoSeguro($id, 'aprobado');
                    $mensaje = $ok ? 'Usuario activado (aprobado) correctamente.' : 'No se pudo activar el usuario.';
                    break;

                case 'desactivar':
                    // tu BD no tiene "inactivo" => usamos "cancelado"
                    $ok = $this->cambiarEstadoSeguro($id, 'cancelado');
                    $mensaje = $ok ? 'Usuario desactivado (cancelado) correctamente.' : 'No se pudo desactivar el usuario.';
                    break;

                case 'suspender':
                    // tu BD no tiene "suspendido" => usamos "cancelado"
                    $ok = $this->cambiarEstadoSeguro($id, 'cancelado');
                    $mensaje = $ok ? 'Usuario suspendido (cancelado) correctamente.' : 'No se pudo suspender el usuario.';
                    break;

                default:
                    return ['ok' => false, 'mensaje' => 'Acción no válida.'];
            }

            return ['ok' => (bool)$ok, 'mensaje' => $mensaje];

        } catch (\Throwable $e) {
            error_log('❌ Error en UsuarioController::ejecutarAccion(): ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'Ocurrió un error al procesar la acción.'];
        }
    }

    /** ✅ Cambia estado, pero SOLO si es válido para tu ENUM */
    private function cambiarEstadoSeguro(int $id, string $estado): bool
    {
        $estado = strtolower(trim($estado));

        if (!in_array($estado, $this->estadosValidos, true)) {
            error_log("❌ Estado inválido para ENUM usuarios.estado: {$estado}");
            return false;
        }

        return $this->usuarioModel->update($id, ['estado' => $estado]);
    }

    /** Cambiar contraseña actual */
    public function cambiarPasswordActual(string $passActual, string $passNueva): array
    {
        if (!Session::isLoggedIn()) {
            return ['success' => false, 'error' => 'Sesión no válida. Inicia sesión nuevamente.'];
        }

        $usuarioId = (int)Session::getUsuarioId();

        try {
            $usuario = $this->usuarioModel->find($usuarioId);

            if (!$usuario || empty($usuario['pass'])) {
                return ['success' => false, 'error' => 'Usuario no encontrado.'];
            }

            if (!password_verify($passActual, (string)$usuario['pass'])) {
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
