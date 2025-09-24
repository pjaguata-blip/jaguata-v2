<?php
namespace Jaguata\Controllers;

use Jaguata\Models\Paseo;
use Jaguata\Helpers\Session;
use Exception;

class PaseoController {
    private Paseo $paseoModel;

    public function __construct() {
        $this->paseoModel = new Paseo();
    }

    // === Métodos REST básicos ===

    public function index() {
        return $this->paseoModel->all();
    }

    public function show($id) {
        return $this->paseoModel->find($id);
    }

    public function store() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'mascota_id'   => (int)($_POST['mascota_id'] ?? 0),
            'paseador_id'  => (int)($_POST['paseador_id'] ?? 0),
            'inicio'       => $_POST['inicio'] ?? '',
            'duracion'     => (int)($_POST['duracion'] ?? 0),
            'precio_total' => (float)($_POST['precio_total'] ?? 0)
        ];

        return ['id' => $this->paseoModel->create($data)];
    }

    public function update($id) {
        $data = [
            'inicio'       => $_POST['inicio'] ?? '',
            'duracion'     => (int)($_POST['duracion'] ?? 0),
            'precio_total' => (float)($_POST['precio_total'] ?? 0),
        ];
        return $this->paseoModel->update($id, $data);
    }

    public function destroy($id) {
        return $this->paseoModel->delete($id);
    }

    // === Métodos especiales para estados ===

    public function confirmar($id) {
        return $this->paseoModel->cambiarEstado($id, 'confirmado');
    }

    public function apiIniciar($id) {
        return $this->paseoModel->cambiarEstado($id, 'en_progreso');
    }

    public function apiCompletar($id) {
        return $this->paseoModel->cambiarEstado($id, 'completado');
    }

    public function apiCancelar($id) {
        return $this->paseoModel->cambiarEstado($id, 'cancelado');
    }
}
