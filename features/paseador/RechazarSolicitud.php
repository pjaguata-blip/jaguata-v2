<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// Inicializar app
AppConfig::init();

// Solo paseador puede rechazar
$authController = new AuthController();
$authController->checkRole('paseador');

// Validar ID
$paseoId = (int)($_GET['id'] ?? 0);
if ($paseoId <= 0) {
    die("Solicitud inválida");
}

// Cambiar estado a cancelado
$paseoController = new PaseoController();
$resultado = $paseoController->apiCancelar($paseoId);

if ($resultado) {
    $_SESSION['success'] = "Solicitud de paseo rechazada correctamente.";
} else {
    $_SESSION['error'] = "No se pudo rechazar la solicitud.";
}

header("Location: Solicitudes.php");
exit;
