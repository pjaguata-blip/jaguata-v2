<?php
namespace Jaguata\Controllers;

use Jaguata\Models\Calificacion;
use Jaguata\Helpers\Session;
use Exception;

class CalificacionController {
    private $caliModel;

    public function __construct() {
        $this->caliModel = new Calificacion();
    }

    /**
     * Listar todas las calificaciones
     */
    public function index() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        return $this->caliModel->all();
    }

    /**
     * Mostrar una calificación
     */
    public function show($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }
        return $this->caliModel->find($id);
    }

    /**
     * Crear nueva calificación
     */
    public function apiStore() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'paseo_id' => $_POST['paseo_id'] ?? null,
            'rater_id' => $_SESSION['usuario_id'],
            'rated_id' => $_POST['rated_id'] ?? null,
            'calificacion' => $_POST['calificacion'] ?? null,
            'comentario' => $_POST['comentario'] ?? '',
            'tipo' => $_POST['tipo'] ?? 'paseador'
        ];

        if (!$data['paseo_id'] || !$data['rated_id'] || !$data['calificacion']) {
            return ['error' => 'Faltan datos obligatorios'];
        }

        try {
            $id = $this->caliModel->create($data);
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log('Error al crear calificación: ' . $e->getMessage());
            return ['error' => 'Error interno al crear calificación'];
        }
    }

    /**
     * Actualizar una calificación
     */
    public function apiUpdate($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $result = $this->caliModel->update($id, $_POST);
            return $result
                ? ['success' => true, 'message' => 'Calificación actualizada']
                : ['error' => 'No se pudo actualizar calificación'];
        } catch (Exception $e) {
            error_log('Error al actualizar calificación: ' . $e->getMessage());
            return ['error' => 'Error interno del servidor'];
        }
    }

    /**
     * Eliminar una calificación
     */
    public function apiDestroy($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $result = $this->caliModel->delete($id);
            return $result
                ? ['success' => true, 'message' => 'Calificación eliminada']
                : ['error' => 'No se pudo eliminar calificación'];
        } catch (Exception $e) {
            error_log('Error al eliminar calificación: ' . $e->getMessage());
            return ['error' => 'Error interno del servidor'];
        }
    }
}
