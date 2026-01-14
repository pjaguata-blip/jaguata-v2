<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

/* ✅ Suscripción */
require_once __DIR__ . '/../../src/Models/Suscripcion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;
use Jaguata\Models\Suscripcion;

AppConfig::init();

/* 🔒 Solo paseador */
$auth = new AuthController();
$auth->checkRole('paseador');

/* 🔒 BLOQUEO POR ESTADO */
if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* =========================
   Helpers redirect seguro
   ========================= */
$baseFeatures = BASE_URL . "/features/paseador";
$defaultBack  = $baseFeatures . '/MisPaseos.php';

$redirectTo = trim((string)($_POST['redirect_to'] ?? $_GET['redirect_to'] ?? ''));
$allowedRedirects = ['MisPaseos.php', 'Solicitudes.php'];

$redirectUrl = $defaultBack;
if ($redirectTo !== '' && in_array($redirectTo, $allowedRedirects, true)) {
    $redirectUrl = $baseFeatures . '/' . $redirectTo;
}

/* =========================
   Leer acción (POST o GET)
   ========================= */
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $paseoId = (int)($_POST['id'] ?? 0);
    $accion  = strtolower(trim((string)($_POST['accion'] ?? '')));
} else {
    $paseoId = (int)($_GET['id'] ?? 0);
    $accion  = strtolower(trim((string)($_GET['accion'] ?? '')));
}

/* Validaciones básicas */
if ($paseoId <= 0 || $accion === '') {
    $_SESSION['error'] = 'Datos de paseo inválidos.';
    header("Location: {$redirectUrl}");
    exit;
}

$paseadorId      = (int)(Session::getUsuarioId() ?? 0);
$paseoController = new PaseoController();

/* =========================
   ✅ Validar suscripción PRO
   ========================= */
$tieneProActiva = false;
try {
    if ($paseadorId > 0) {
        $subModel = new Suscripcion();
        // opcional: marcar vencidas al vuelo
        if (method_exists($subModel, 'marcarVencidas')) {
            $subModel->marcarVencidas();
        }

        $ultima = method_exists($subModel, 'getUltimaPorPaseador')
            ? $subModel->getUltimaPorPaseador($paseadorId)
            : null;

        if ($ultima) {
            $estado = strtolower(trim((string)($ultima['estado'] ?? '')));
            $tieneProActiva = ($estado === 'activa');
        }
    }
} catch (Throwable $e) {
    $tieneProActiva = false;
}

/* Acciones que requieren PRO activa */
$accionesRequierenPro = ['confirmar', 'iniciar', 'completar'];

if (in_array($accion, $accionesRequierenPro, true) && !$tieneProActiva) {
    $_SESSION['error'] = 'Necesitás Suscripción PRO activa para aceptar/iniciar/completar paseos. Subí tu comprobante o renová.';
    header("Location: {$redirectUrl}");
    exit;
}

try {
    switch ($accion) {
        case 'confirmar':
            $res = $paseoController->confirmarPaseoPaseador($paseoId, $paseadorId);
            if (!empty($res['success'])) {
                $_SESSION['success'] = $res['mensaje'] ?? 'Paseo confirmado correctamente.';
            } else {
                $_SESSION['error'] = $res['error'] ?? 'No se pudo confirmar el paseo.';
            }
            break;

        case 'cancelar':
            $res = $paseoController->rechazarPaseoPaseador($paseoId, $paseadorId);
            if (!empty($res['success'])) {
                $_SESSION['success'] = $res['mensaje'] ?? 'Paseo cancelado correctamente.';
            } else {
                $_SESSION['error'] = $res['error'] ?? 'No se pudo cancelar el paseo.';
            }
            break;

        case 'iniciar':
            $ok = $paseoController->apiIniciar($paseoId);
            if ($ok) {
                $_SESSION['success'] = 'El paseo fue iniciado correctamente.';
            } else {
                $_SESSION['error'] = 'No se pudo iniciar el paseo. Verificá el estado actual.';
            }
            break;

        case 'completar':
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

header("Location: {$redirectUrl}");
exit;
