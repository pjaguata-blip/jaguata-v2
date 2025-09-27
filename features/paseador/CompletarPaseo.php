<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

$paseoId = $_GET['id'] ?? null;
if ($paseoId) {
    $paseoController = new PaseoController();
    $paseoController->apiCompletar((int)$paseoId); // cambia a "completado"
}

header("Location: MisPaseos.php");
exit;
