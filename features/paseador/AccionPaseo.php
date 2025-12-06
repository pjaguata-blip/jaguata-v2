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
$auth = new AuthController();
$auth->checkRole('paseador');

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// 📨 Aceptamos tanto POST como GET (porque en MisPaseos tenés ambos tipos)
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $paseoId = (int)($_POST['id'] ?? 0);
    $accion  = strtolower(trim((string)($_POST['accion'] ?? '')));
} else { // GET
    $paseoId = (int)($_GET['id'] ?? 0);
    $accion  = strtolower(trim((string)($_GET['accion'] ?? '')));
}

// URL a donde volver si algo sale mal
$baseFeatures = BASE_URL . "/features/paseador";
$redirectUrl  = $baseFeatures . '/MisPaseos.php';

// Validaciones básicas
if ($paseoId <= 0 || $accion === '') {
    $_SESSION['error'] = 'Datos de paseo inválidos.';
    header("Location: {$redirectUrl}");
    exit;
}

$paseadorId     = (int)(Session::getUsuarioId() ?? 0);
$paseoController = new PaseoController();

try {
    switch ($accion) {
        case 'confirmar':
            // solicitudes solicitadas/pendientes
            $res = $paseoController->confirmarPaseoPaseador($paseoId, $paseadorId);
            if (!empty($res['success'])) {
                $_SESSION['success'] = $res['mensaje'] ?? 'Paseo confirmado correctamente.';
            } else {
                $_SESSION['error'] = $res['error'] ?? 'No se pudo confirmar el paseo.';
            }
            break;

        case 'cancelar':
            // Para solicitudes pendientes (solicitado/pendiente)
            $res = $paseoController->rechazarPaseoPaseador($paseoId, $paseadorId);
            if (!empty($res['success'])) {
                $_SESSION['success'] = $res['mensaje'] ?? 'Paseo cancelado correctamente.';
            } else {
                $_SESSION['error'] = $res['error'] ?? 'No se pudo cancelar el paseo.';
            }
            break;

        case 'iniciar':
            // Confirmado -> en_curso
            $ok = $paseoController->apiIniciar($paseoId);
            if ($ok) {
                $_SESSION['success'] = 'El paseo fue iniciado correctamente.';
            } else {
                $_SESSION['error'] = 'No se pudo iniciar el paseo. Verificá el estado actual.';
            }
            break;

        case 'completar':
            // en_curso / confirmado -> completo
            $res = $paseoController->completarPaseo($paseoId);
            if (!empty($res['success'])) {
                $_SESSION['success'] = 'El paseo fue marcado como completo.';
            } else {
                $_SESSION['error'] = $res['error'] ?? 'No se pudo completar el paseo.';
            }
            break;

        default:
            $_SESSION['error'] = 'Acción no válida.';
            break;
    }
} catch (Throwable $e) {
    error_log('Error en AccionPaseo (paseador): ' . $e->getMessage());
    $_SESSION['error'] = 'Ocurrió un error al procesar la acción.';
}

// Volver siempre a MisPaseos
header("Location: {$redirectUrl}");
exit;
