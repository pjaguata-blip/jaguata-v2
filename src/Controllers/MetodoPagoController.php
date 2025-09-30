<?php

namespace Jaguata\Controllers;

use Jaguata\Models\MetodoPago;

class MetodoPagoController
{
    private MetodoPago $model;

    public function __construct()
    {
        $this->model = new MetodoPago();
    }

    public function getByUsuario(int $usuarioId): array
    {
        return $this->model->findByUsuario($usuarioId);
    }

    public function store(array $data): int
    {
        return $this->model->createMetodo($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->updateMetodo($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->model->deleteMetodo($id);
    }

    public function setDefault(int $usuarioId, int $metodoId): bool
    {
        return $this->model->setDefault($usuarioId, $metodoId);
    }
}
