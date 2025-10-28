<?php

declare(strict_types=1);

namespace Jaguata\Controllers;

use Jaguata\Models\Soporte;

class SoporteController
{
    private Soporte $model;

    public function __construct()
    {
        $this->model = new Soporte();
    }

    /** 🔹 Listar tickets */
    public function index(?string $estado = null): array
    {
        return $this->model->getAll($estado);
    }

    /** 🔹 Obtener un ticket */
    public function verTicket(int $id): ?array
    {
        return $this->model->getById($id);
    }

    /** 🔹 Crear nuevo ticket (usuario) */
    public function crearTicket(int $usuId, string $asunto, string $mensaje): bool
    {
        if (empty($asunto) || empty($mensaje)) {
            return false;
        }
        return $this->model->crearTicket($usuId, $asunto, $mensaje);
    }



    /** 🔹 Responder ticket (admin) */
    public function responderTicket(int $ticketId, string $respuesta): bool
    {
        return $this->model->responder($ticketId, $respuesta);
    }

    /** 🔹 Cambiar estado */
    public function cambiarEstado(int $ticketId, string $estado): bool
    {
        return $this->model->actualizarEstado($ticketId, $estado);
    }

    /** 🔹 Eliminar ticket */
    public function eliminarTicket(int $id): bool
    {
        return $this->model->eliminar($id);
    }
}
