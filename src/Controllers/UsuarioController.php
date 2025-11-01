<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Usuario;
use Exception;

/**
 * Controlador para gestionar usuarios (admin)
 */
class UsuarioController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    /** 🔹 Listar todos los usuarios */
    public function index(): array
    {
        try {
            return $this->usuarioModel->getAllUsuarios();
        } catch (Exception $e) {
            error_log("Error UsuarioController::index => " . $e->getMessage());
            return [];
        }
    }
    public function getById(int $id): ?array
    {
        try {
            $usuarioModel = new \Jaguata\Models\Usuario();
            return $usuarioModel->getById($id);
        } catch (\Exception $e) {
            error_log("Error getById UsuarioController: " . $e->getMessage());
            return null;
        }
    }


    /** 🔹 Eliminar usuario */
    public function destroy(int $id): bool
    {
        try {
            $ok = $this->usuarioModel->deleteUsuario($id);

            if (!$ok) {
                error_log("⚠️ No se eliminó el usuario con ID: $id");
            }

            return $ok;
        } catch (Exception $e) {
            error_log("Error UsuarioController::destroy => " . $e->getMessage());
            return false;
        }
    }


    /** 🔹 Cambiar el estado (pendiente/aprobado/rechazado/cancelado) */
    public function cambiarEstado(int $id, string $nuevoEstado): bool
    {
        try {
            $validos = ['pendiente', 'aprobado', 'rechazado', 'cancelado'];
            if (!in_array($nuevoEstado, $validos, true)) {
                throw new Exception("Estado inválido: $nuevoEstado");
            }
            return $this->usuarioModel->updateEstado($id, $nuevoEstado);
        } catch (Exception $e) {
            error_log("Error UsuarioController::cambiarEstado => " . $e->getMessage());
            return false;
        }
    }

    public function activarUsuario(int $id): bool
    {
        return $this->cambiarEstado($id, 'aprobado');
    }

    public function suspenderUsuario(int $id): bool
    {
        return $this->cambiarEstado($id, 'rechazado');
    }

    /** 🔹 Ejecutar acción del panel (para AJAX) */
    public function ejecutarAccion(string $accion, int $id): array
    {
        try {
            $usuarioModel = new \Jaguata\Models\Usuario();

            // Buscar usuario
            $usuario = $usuarioModel->findById($id);
            if (!$usuario) {
                return ['ok' => false, 'mensaje' => 'Usuario no encontrado.'];
            }

            switch ($accion) {
                case 'suspender':
                    $ok = $usuarioModel->updateEstado($id, 'suspendido');
                    $mensaje = "Usuario suspendido correctamente.";
                    break;

                case 'activar':
                    $ok = $usuarioModel->updateEstado($id, 'activo');
                    $mensaje = "Usuario activado correctamente.";
                    break;

                case 'eliminar':
                    $ok = $usuarioModel->deleteById($id);
                    $mensaje = $ok
                        ? "Usuario eliminado correctamente."
                        : "No se pudo eliminar el usuario (puede tener datos asociados).";
                    break;

                default:
                    return ['ok' => false, 'mensaje' => 'Acción no reconocida.'];
            }

            return ['ok' => $ok, 'mensaje' => $mensaje];
        } catch (Exception $e) {
            return ['ok' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()];
        }
    }

    /** 🔹 Datos para exportar */
    public function obtenerDatosExportacion(): array
    {
        try {
            return $this->usuarioModel->getAllUsuarios();
        } catch (Exception $e) {
            error_log("Error UsuarioController::obtenerDatosExportacion => " . $e->getMessage());
            return [];
        }
    }
    private function respuesta(bool $ok, string $mensaje): array
    {
        return [
            'ok' => $ok,
            'mensaje' => $mensaje,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    /** 🔹 Obtener usuario por ID */
    public function show(int $id): ?array
    {
        try {
            return $this->usuarioModel->getById($id);
        } catch (Exception $e) {
            error_log("Error UsuarioController::show => " . $e->getMessage());
            return null;
        }
    }

    /** 🔹 Actualizar usuario desde el panel */
    public function actualizarUsuario(int $id, array $data): bool
    {
        try {
            $usuarioModel = new \Jaguata\Models\Usuario();
            return $usuarioModel->updateUsuario($id, $data);
        } catch (Exception $e) {
            error_log("Error actualizarUsuario: " . $e->getMessage());
            return false;
        }
    }
}
