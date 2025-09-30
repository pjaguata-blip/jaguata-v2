<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MetodoPagoController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MetodoPagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

// Verificar rol
$auth = new AuthController();
$auth->requireRole(['dueno']);

$controller = new MetodoPagoController();
$usuarioId = Session::get('usuario_id');
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: MetodosPago.php?error=noparam");
    exit;
}

if ($controller->delete($id)) {
    header("Location: MetodosPago.php?success=deleted");
    exit;
} else {
    header("Location: MetodosPago.php?error=notdeleted");
    exit;
}
