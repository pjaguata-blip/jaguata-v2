<?php

declare(strict_types=1);

namespace Jaguata\Controllers;

use Jaguata\Models\Punto;

class PuntoController
{
    private Punto $model;

    public function __construct()
    {
        $this->model = new Punto();
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->model->getByUsuario($usuarioId);
    }

    public function totalUsuario(int $usuarioId): int
    {
        return $this->model->getTotal($usuarioId);
    }

    public function totalMesActual(int $usuarioId): int
    {
        return $this->model->getTotalMesActual($usuarioId);
    }

    public function agregar(int $usuarioId, string $descripcion, int $puntos): int
    {
        return $this->model->add($usuarioId, $descripcion, $puntos);
    }
}
