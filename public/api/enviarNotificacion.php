<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/NotificacionController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\NotificacionController;

AppConfig::init();
header('Content-Type: application/json; charset=utf-8');

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$usuarioId = isset($_POST['usuario_id']) ? (int) $_POST['usuario_id'] : 0;
$titulo    = trim($_POST['titulo'] ?? '');
$mensaje   = trim($_POST['mensaje'] ?? '');

if ($usuarioId <= 0 || $titulo === '' || $mensaje === '') {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$ctrl = new NotificacionController();
$ok   = $ctrl->enviarNotificacionUsuario($usuarioId, $titulo, $mensaje);

echo json_encode([
    'success' => $ok,
    'error'   => $ok ? null : 'No se pudo enviar la notificación'
]);
