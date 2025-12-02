<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!Session::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Sesión expirada. Inicia sesión nuevamente.']);
    exit;
}

$passActual  = $_POST['pass_actual']  ?? '';
$passNueva   = $_POST['pass_nueva']   ?? '';
$passConfirm = $_POST['pass_confirm'] ?? '';

if ($passActual === '' || $passNueva === '' || $passConfirm === '') {
    echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios.']);
    exit;
}

if ($passNueva !== $passConfirm) {
    echo json_encode(['success' => false, 'error' => 'La nueva contraseña y la confirmación no coinciden.']);
    exit;
}

$usuarioController = new UsuarioController();
$result = $usuarioController->cambiarPasswordActual($passActual, $passNueva);

echo json_encode($result);
exit;
