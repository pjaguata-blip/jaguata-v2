<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Calificacion;
use Jaguata\Helpers\Session;

class CalificacionController
{
    private Calificacion $model;

    public function __construct()
    {
        $this->model = new Calificacion();
    }

    /**
     * Dueño califica al paseador
     */
    public function calificarPaseador(array $data): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $requeridos = ['paseo_id', 'rated_id', 'calificacion'];
        foreach ($requeridos as $r) {
            if (empty($data[$r])) {
                return ['error' => "Falta $r"];
            }
        }

        $raterId = (int)Session::get('usuario_id');

        // Evitar duplicado
        if ($this->model->existeParaPaseo((int)$data['paseo_id'], 'paseador', $raterId)) {
            return ['error' => 'Ya calificaste este paseo'];
        }

        $id = $this->model->crear([
            'paseo_id'     => (int)$data['paseo_id'],
            'rater_id'     => $raterId,
            'rated_id'     => (int)$data['rated_id'], // paseador
            'calificacion' => (int)$data['calificacion'],
            'comentario'   => trim($data['comentario'] ?? ''),
            'tipo'         => 'paseador'
        ]);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Paseador califica al dueño o mascota (si querés extenderlo)
     */
    public function calificarMascota(array $data): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $raterId = (int)Session::get('usuario_id');

        $id = $this->model->crear([
            'paseo_id'     => (int)$data['paseo_id'],
            'rater_id'     => $raterId,
            'rated_id'     => (int)$data['rated_id'], // mascota
            'calificacion' => (int)$data['calificacion'],
            'comentario'   => trim($data['comentario'] ?? ''),
            'tipo'         => 'mascota'
        ]);

        return ['success' => true, 'id' => $id];
    }
}
