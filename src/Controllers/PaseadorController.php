<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Paseador;
use Jaguata\Helpers\Session;
use Exception;

class PaseadorController
{
    private Paseador $paseadorModel;

    public function __construct()
    {
        $this->paseadorModel = new Paseador();
    }

    public function index()
    {
        return $this->paseadorModel->all();
    }

    public function disponibles()
    {
        return $this->paseadorModel->getDisponibles();
    }

    public function show($id)
    {
        return $this->paseadorModel->find((int)$id);
    }

    public function apiStore()
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'nombre'       => $_POST['nombre'] ?? '',
            'email'        => $_POST['email'] ?? '',
            'telefono'     => $_POST['telefono'] ?? '',
            'experiencia'  => $_POST['experiencia'] ?? 0,
            'disponible'   => $_POST['disponible'] ?? 1,
            'precio_hora'  => $_POST['precio_hora'] ?? 0,
            'calificacion' => $_POST['calificacion'] ?? 0,
            'total_paseos' => $_POST['total_paseos'] ?? 0,
        ];

        try {
            $id = $this->paseadorModel->create($data);
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function apiUpdate($id)
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'nombre'       => $_POST['nombre'] ?? '',
            'telefono'     => $_POST['telefono'] ?? '',
            'experiencia'  => $_POST['experiencia'] ?? 0,
            'disponible'   => $_POST['disponible'] ?? 1,
            'precio_hora'  => $_POST['precio_hora'] ?? 0,
            'calificacion' => $_POST['calificacion'] ?? 0,
            'total_paseos' => $_POST['total_paseos'] ?? 0,
        ];

        return $this->paseadorModel->update((int)$id, $data)
            ? ['success' => true]
            : ['error' => 'No se pudo actualizar'];
    }

    public function apiDelete($id)
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        return $this->paseadorModel->delete((int)$id)
            ? ['success' => true]
            : ['error' => 'No se pudo eliminar'];
    }

    public function apiSetDisponible($id)
    {
        return $this->paseadorModel->setDisponible((int)$id, (bool)($_POST['disponible'] ?? true));
    }

    public function apiUpdateCalificacion($id)
    {
        return $this->paseadorModel->updateCalificacion((int)$id, (float)($_POST['calificacion'] ?? 0));
    }

    public function apiIncrementarPaseos($id)
    {
        return $this->paseadorModel->incrementarPaseos((int)$id);
    }

    public function buscar(string $query = '')
    {
        return empty($query)
            ? $this->paseadorModel->all()
            : $this->paseadorModel->search($query);
    }
}
