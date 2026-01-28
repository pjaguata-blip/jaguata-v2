<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');
$controller = new MascotaController();
/* ID */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "ID de mascota inválido";
    header("Location: MisMascotas.php");
    exit;
}
$controller->destroy($id);
/* Eliminar */
$controller = new MascotaController();
$resp = $controller->destroy($id);

if (!empty($resp['success'])) {
    $_SESSION['success'] = 'Mascota eliminada correctamente 🐾';
} else {
    $_SESSION['error'] = $resp['error'] ?? 'No se pudo eliminar la mascota.';
}

/* Volver a la lista */
header('Location: MisMascotas.php');
exit;
