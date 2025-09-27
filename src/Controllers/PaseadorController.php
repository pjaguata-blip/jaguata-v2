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

    /**
     * Listar todos los paseadores
     */
    public function index()
    {
        return $this->paseadorModel->all();
    }

    /**
     * Listar solo paseadores disponibles
     */
    public function disponibles()
    {
        return $this->paseadorModel->getDisponibles();
    }

    /**
     * Ver detalle de un paseador
     */
    public function show($id)
    {
        return $this->paseadorModel->find((int)$id);
    }

    /**
     * Crear nuevo paseador (desde API)
     */
    public function apiStore()
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'nombre'       => $_POST['nombre'] ?? '',
            'email'        => $_POST['email'] ?? '',
            'telefono'     => $_POST['telefono'] ?? '',
            'experiencia'  => $_POST['experiencia'] ?? '',
            'disponible'   => isset($_POST['disponible']) ? (int)$_POST['disponible'] : 1,
            'precio_hora'  => $_POST['precio_hora'] ?? 0,
            'calificacion' => $_POST['calificacion'] ?? 0,
            'total_paseos' => $_POST['total_paseos'] ?? 0,
        ];

        try {
            $id = $this->paseadorModel->create($data);
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log("Error creando paseador: " . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    /**
     * Actualizar paseador (API)
     */
    public function apiUpdate($id)
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'nombre'       => $_POST['nombre'] ?? '',
            'telefono'     => $_POST['telefono'] ?? '',
            'experiencia'  => $_POST['experiencia'] ?? '',
            'disponible'   => isset($_POST['disponible']) ? (int)$_POST['disponible'] : 1,
            'precio_hora'  => $_POST['precio_hora'] ?? 0,
            'calificacion' => $_POST['calificacion'] ?? 0,
            'total_paseos' => $_POST['total_paseos'] ?? 0,
        ];

        return $this->paseadorModel->update((int)$id, $data)
            ? ['success' => true]
            : ['error' => 'No se pudo actualizar'];
    }

    /**
     * Eliminar paseador (API)
     */
    public function apiDelete($id)
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        return $this->paseadorModel->delete((int)$id)
            ? ['success' => true]
            : ['error' => 'No se pudo eliminar'];
    }

    /**
     * Cambiar disponibilidad de un paseador (API)
     */
    public function apiSetDisponible($id)
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $estado = isset($_POST['disponible']) ? (bool)$_POST['disponible'] : true;
        return $this->paseadorModel->setDisponible((int)$id, $estado)
            ? ['success' => true]
            : ['error' => 'No se pudo actualizar disponibilidad'];
    }

    /**
     * Actualizar calificación (API)
     */
    public function apiUpdateCalificacion($id)
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $nuevaCalificacion = (float)($_POST['calificacion'] ?? 0);
        return $this->paseadorModel->updateCalificacion((int)$id, $nuevaCalificacion)
            ? ['success' => true]
            : ['error' => 'No se pudo actualizar calificación'];
    }

    /**
     * Incrementar paseos completados (API)
     */
    public function apiIncrementarPaseos($id)
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        return $this->paseadorModel->incrementarPaseos((int)$id)
            ? ['success' => true]
            : ['error' => 'No se pudo incrementar paseos'];
    }
}
