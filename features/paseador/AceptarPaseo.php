<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;

// Inicializar
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

// Obtener id del paseo
$paseoId = $_GET['id'] ?? null;
if ($paseoId) {
    $paseoController = new PaseoController();
    $paseoController->confirmar((int)$paseoId); // cambia a "confirmado"
}

header("Location: MisPaseos.php");
exit;
