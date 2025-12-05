<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

// 🔒 Solo paseador
$authController = new AuthController();
$authController->checkRole('paseador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/features/paseador/Solicitudes.php');
    exit;
}

$paseadorId = (int)(Session::getUsuarioId() ?? 0);
$accion     = $_POST['accion'] ?? '';
$id         = (int)($_POST['id'] ?? 0);
$redirectTo = trim($_POST['redirect_to'] ?? 'Solicitudes.php');

// Normalizamos ruta de redirección
if ($redirectTo === '') {
    $redirectTo = 'Solicitudes.php';
}
$redirectUrl = BASE_URL . '/features/paseador/' . $redirectTo;

$controller = new PaseoController();

if ($paseadorId <= 0 || $id <= 0) {
    $_SESSION['error'] = 'Datos inválidos de la solicitud.';
    header('Location: ' . $redirectUrl);
    exit;
}

$result = null;

if ($accion === 'confirmar') {
    $result = $controller->confirmarPaseoPaseador($id, $paseadorId);
} elseif ($accion === 'cancelar') {
    $result = $controller->rechazarPaseoPaseador($id, $paseadorId);
} else {
    $_SESSION['error'] = 'Acción no válida.';
    header('Location: ' . $redirectUrl);
    exit;
}

if (!empty($result['success'])) {
    $_SESSION['success'] = $result['mensaje'] ?? 'Operación realizada correctamente.';
} else {
    $_SESSION['error'] = $result['error'] ?? 'No se pudo procesar la acción.';
}

header('Location: ' . $redirectUrl);
exit;
