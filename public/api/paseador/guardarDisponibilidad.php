<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/DisponibilidadController.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\DisponibilidadController;
use Jaguata\Helpers\Session;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

// Leer body (enviado como JSON por fetch)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'JSON inválido']);
    exit;
}

$paseadorIdBody    = (int)($data['paseador_id'] ?? 0);
$paseadorIdSession = (int)(Session::getUsuarioId() ?? 0);
$paseadorId        = $paseadorIdBody > 0 ? $paseadorIdBody : $paseadorIdSession;

if ($paseadorId <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado (sin paseador_id válido).']);
    exit;
}

$items = $data['disponibilidad'] ?? [];

if (!is_array($items)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Estructura inválida']);
    exit;
}

// Normalizar datos
$normalizados = [];
foreach ($items as $item) {
    $dia    = trim($item['dia']    ?? '');
    $inicio = trim($item['inicio'] ?? '');
    $fin    = trim($item['fin']    ?? '');
    $activo = !empty($item['activo']) ? 1 : 0;

    if ($dia === '' || $inicio === '' || $fin === '') {
        continue;
    }

    $normalizados[] = [
        'dia'    => $dia,
        'inicio' => $inicio,
        'fin'    => $fin,
        'activo' => $activo,
    ];
}

try {
    $ctrl = new DisponibilidadController();
    $ok   = $ctrl->save($paseadorId, $normalizados);

    http_response_code($ok ? 200 : 500);
    echo json_encode([
        'ok'      => $ok,
        'mensaje' => $ok
            ? 'Disponibilidad guardada correctamente.'
            : 'No se pudo guardar la disponibilidad.'
    ]);
} catch (\Throwable $e) {
    // 👇 mientras debugueamos, devolvemos el error
    error_log('❌ Error guardarDisponibilidad: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
