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
$auth = new AuthController();
$auth->checkRole('paseador');

$paseoId = (int)($_GET['id'] ?? 0);
if ($paseoId <= 0) {
    $_SESSION['error'] = 'Solicitud inválida.';
    header('Location: MisPaseos.php');
    exit;
}

$paseoController = new PaseoController();
$paseo = $paseoController->show($paseoId);
if (!$paseo) {
    $_SESSION['error'] = 'No se encontró el paseo.';
    header('Location: MisPaseos.php');
    exit;
}

if ((int)($paseo['paseador_id'] ?? 0) !== (int)Session::get('usuario_id')) {
    $_SESSION['error'] = 'No tienes permiso para confirmar este paseo.';
    header('Location: MisPaseos.php');
    exit;
}

$ok = $paseoController->confirmar($paseoId); // cambia a "confirmado"
$_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Paseo confirmado.' : 'No se pudo confirmar el paseo.';
header('Location: MisPaseos.php');
exit;
