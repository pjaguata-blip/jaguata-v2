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

    /**
     * 🔹 Listar todos los usuarios
     */
    public function index(): array
    {
        try {
            return $this->usuarioModel->getAllUsuarios();
        } catch (Exception $e) {
            error_log("Error UsuarioController::index => " . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔹 Mostrar un usuario específico
     */
    public function show(int $id): ?array
    {
        try {
            return $this->usuarioModel->getById($id);
        } catch (Exception $e) {
            error_log("Error UsuarioController::show => " . $e->getMessage());
            return null;
        }
    }

    /**
     * 🔹 Crear nuevo usuario (solo admin)
     */
    public function store(array $data): bool
    {
        try {
            return (bool)$this->usuarioModel->createUsuario($data);
        } catch (Exception $e) {
            error_log("Error UsuarioController::store => " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔹 Actualizar usuario existente
     */
    public function update(int $id, array $data): bool
    {
        try {
            return $this->usuarioModel->updateUsuario($id, $data);
        } catch (Exception $e) {
            error_log("Error UsuarioController::update => " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔹 Eliminar usuario por ID
     */
    public function destroy(int $id): bool
    {
        try {
            return $this->usuarioModel->deleteUsuario($id);
        } catch (Exception $e) {
            error_log("Error UsuarioController::destroy => " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔹 Obtener puntaje del usuario
     */
    public function getPuntos(int $id): int
    {
        try {
            return $this->usuarioModel->getPuntos($id);
        } catch (Exception $e) {
            error_log("Error UsuarioController::getPuntos => " . $e->getMessage());
            return 0;
        }
    }
}
