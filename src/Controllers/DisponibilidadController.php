<?php

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Models/Disponibilidad.php';

use Jaguata\Models\Disponibilidad;

class DisponibilidadController
{
    private Disponibilidad $model;

    public function __construct()
    {
        $this->model = new Disponibilidad();
    }

    /**
     * Devuelve registros crudos de la BD
     */
    public function getByPaseador(int $id): array
    {
        return $this->model->getByPaseador($id);
    }

    /**
     * Devuelve un array formateado para la vista:
     * [
     *   'Lunes' => ['inicio' => '08:00', 'fin' => '12:00', 'activo' => true],
     *   ...
     * ]
     */
    public function getFormDataByPaseador(int $id): array
    {
        $rows = $this->model->getByPaseador($id);
        $result = [];

        foreach ($rows as $r) {
            $dia = $r['dia_semana'] ?? null;
            if (!$dia) {
                continue;
            }
            $result[$dia] = [
                'inicio' => substr((string)$r['hora_inicio'], 0, 5),
                'fin'    => substr((string)$r['hora_fin'], 0, 5),
                'activo' => (int)($r['activo'] ?? 1) === 1,
            ];
        }

        return $result;
    }

    /**
     * Guarda disponibilidad a partir de un array normalizado.
     */
    public function save(int $paseadorId, array $data): bool
    {
        return $this->model->saveDisponibilidad($paseadorId, $data);
    }
}
