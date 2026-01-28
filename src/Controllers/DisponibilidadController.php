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

    public function getByPaseador(int $id): array
    {
        return $this->model->getByPaseador($id);
    }
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

    public function save(int $paseadorId, array $data): bool
    {
        return $this->model->saveDisponibilidad($paseadorId, $data);
    }
}
