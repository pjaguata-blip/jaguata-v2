<?php

namespace Jaguata\Controllers;

use Jaguata\Models\MetodoPago;
use Jaguata\Helpers\Session;

class MetodoPagoController
{
    private MetodoPago $metodoPagoModel;

    public function __construct()
    {
        $this->metodoPagoModel = new MetodoPago();
    }

    public function index(): array
    {
        $usuId = \Jaguata\Helpers\Session::getUsuarioId();
        return $this->metodoPagoModel->getByUsuario($usuId);
    }

    public function store(array $data): bool
    {
        $data['usuario_id'] = Session::getUsuarioId();
        return $this->metodoPagoModel->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->metodoPagoModel->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->metodoPagoModel->delete($id);
    }
}
