<?php
namespace Jaguata\Controllers;

use Jaguata\Models\Paseador;
use Jaguata\Helpers\Session;
use Exception;

class PaseadorController {
    private Paseador $paseadorModel;

    public function __construct() {
        $this->paseadorModel = new Paseador();
    }

    // Listar todos los paseadores
    public function index() {
        return $this->paseadorModel->all();
    }

    // Ver detalle de un paseador
    public function show($id) {
        return $this->paseadorModel->find($id);
    }

    // Crear nuevo paseador (desde API)
    public function apiStore() {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'nombre'       => $_POST['nombre'] ?? '',
            'email'        => $_POST['email'] ?? '',
            'telefono'     => $_POST['telefono'] ?? '',
            'experiencia'  => $_POST['experiencia'] ?? '',
            'disponible'   => isset($_POST['disponible']) ? (int)$_POST['disponible'] : 1
        ];

        try {
            $id = $this->paseadorModel->create($data);
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log("Error creando paseador: " . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    // Actualizar paseador
    public function apiUpdate($id) {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'nombre'       => $_POST['nombre'] ?? '',
            'telefono'     => $_POST['telefono'] ?? '',
            'experiencia'  => $_POST['experiencia'] ?? '',
            'disponible'   => isset($_POST['disponible']) ? (int)$_POST['disponible'] : 1
        ];

        return $this->paseadorModel->update($id, $data)
            ? ['success' => true]
            : ['error' => 'No se pudo actualizar'];
    }
}
