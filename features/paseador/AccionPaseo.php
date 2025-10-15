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

// Inicializar app
AppConfig::init();

// Verificar autenticación y rol
$authController = new AuthController();
$authController->checkRole('paseador');

// Validar parámetros
$id      = (int)($_POST['id'] ?? 0);
$accion  = trim($_POST['accion'] ?? '');
$redirect = $_POST['redirect_to'] ?? 'Solicitudes.php';

// Si faltan datos
if ($id <= 0 || $accion === '') {
    $_SESSION['error'] = 'Datos incompletos';
    header("Location: $redirect");
    exit;
}

// Instanciar controlador
$paseoController = new PaseoController();

// Procesar acción
switch ($accion) {
    case 'confirmar':
        $resultado = $paseoController->confirmar($id);
        $_SESSION['success'] = 'Solicitud aceptada correctamente.';
        break;

    case 'iniciar':
        $resultado = $paseoController->apiIniciar($id);
        $_SESSION['success'] = 'Paseo iniciado correctamente.';
        break;

    case 'completar':
        $resultado = $paseoController->apiCompletar($id);
        $_SESSION['success'] = 'Paseo marcado como completado.';
        break;

    case 'cancelar':
        $resultado = $paseoController->apiCancelar($id);
        $_SESSION['success'] = 'Paseo cancelado correctamente.';
        break;

    default:
        $_SESSION['error'] = 'Acción no válida.';
        break;
}

// Redirigir de nuevo
header("Location: $redirect");
exit;
