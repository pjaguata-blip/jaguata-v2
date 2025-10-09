<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// Init + auth
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: MisPaseos.php");
    exit;
}

$id     = (int)($_POST['id'] ?? 0);
$accion = strtolower(trim((string)($_POST['accion'] ?? '')));

$mapDestino = [
    'confirmar' => 'confirmado',
    'iniciar'   => 'en_curso',
    'completar' => 'completo',
    'cancelar'  => 'cancelado',
];

if ($id <= 0 || !isset($mapDestino[$accion])) {
    $_SESSION['error'] = "Solicitud inválida.";
    header("Location: MisPaseos.php");
    exit;
}

try {
    $controller = new PaseoController();
    $paseo      = $controller->show($id);

    if (!$paseo) {
        $_SESSION['error'] = "Paseo no encontrado.";
        header("Location: MisPaseos.php");
        exit;
    }

    // Debe pertenecer al paseador logueado
    $paseadorId = (int)Session::get('usuario_id');
    if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorId) {
        $_SESSION['error'] = "No tienes permiso para modificar este paseo.";
        header("Location: MisPaseos.php");
        exit;
    }

    $ok = false;
    switch ($accion) {
        case 'confirmar':
            $ok = $controller->confirmar($id);
            break;
        case 'iniciar':
            $ok = $controller->apiIniciar($id);
            break;
        case 'completar':
            $ok = $controller->apiCompletar($id);
            break;
        case 'cancelar':
            $ok = $controller->apiCancelar($id);
            break;
    }

    if ($ok) {
        $_SESSION['success'] = "Paseo " . $mapDestino[$accion] . " correctamente.";
        header("Location: MisPaseos.php?estado=" . urlencode($mapDestino[$accion]));
    } else {
        $_SESSION['error'] = "No se pudo actualizar el estado.";
        header("Location: MisPaseos.php");
    }
    exit;
} catch (\Throwable $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: MisPaseos.php");
    exit;
}
