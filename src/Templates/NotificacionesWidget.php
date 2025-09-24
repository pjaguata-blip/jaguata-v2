<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Helpers\Session;
use Jaguata\Helpers\Funciones;
use Jaguata\Services\NotificacionService;

$usuarioLogueado = Session::isLoggedIn();
$notificaciones = [];
$totalNoLeidas = 0;

if ($usuarioLogueado) {
    $notificacionService = new NotificacionService();
    $resultado = $notificacionService->getNotificacionesNoLeidas(Session::getUsuarioId());

    if ($resultado['success']) {
        $notificaciones = array_slice($resultado['notificaciones'], 0, 5);
        $totalNoLeidas = $resultado['total'];
    }
}
?>
