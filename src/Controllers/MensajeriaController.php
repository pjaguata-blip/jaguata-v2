<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Mensaje;
use Jaguata\Helpers\Session;

class MensajeriaController
{
    private Mensaje $mensajeModel;

    public function __construct()
    {
        $this->mensajeModel = new Mensaje();
    }

    public function chat(int $paseoId, int $destinatarioId)
    {
        if (!Session::isLoggedIn()) {
            header("Location: /jaguata/public/login.php");
            exit;
        }

        $mensajes = $this->mensajeModel->getMensajes($paseoId);
        include dirname(__DIR__, 2) . "/features/mensajeria/chat.php";
    }
}
