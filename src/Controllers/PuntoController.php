<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Punto;
use Jaguata\Helpers\Session;


class PuntoController
{
    private Punto $puntoModel;

    public function __construct()
    {
        $this->puntoModel = new Punto();
    }

    // Vista de puntos del usuario actual
    public function misPuntos(): array
    {
        if (!Session::isLoggedIn()) {
            header("Location: /jaguata/public/login.php");
            exit;
        }

        $usuarioId = Session::get('usuario_id');
        return [
            'historial' => $this->puntoModel->getByUsuario($usuarioId),
            'total' => $this->puntoModel->getTotal($usuarioId)
        ];
    }

    // API para agregar puntos (ejemplo: tras reservar un paseo)
    public function apiAgregar(int $usuarioId, string $descripcion, int $puntos): array
    {
        $id = $this->puntoModel->add($usuarioId, $descripcion, $puntos);
        return ['success' => true, 'id' => $id];
    }
}
