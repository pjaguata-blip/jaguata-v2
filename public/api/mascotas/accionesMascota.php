<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 3) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 3) . '/src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\MascotaController;

AppConfig::init();
header('Content-Type: application/json; charset=utf-8');

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$id     = (int)($_POST['id'] ?? 0);
$accion = strtolower(trim((string)($_POST['accion'] ?? '')));

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
    exit;
}

$estado = match ($accion) {
    'activar' => 'activo',
    'inactivar', 'desactivar' => 'inactivo',  // ✅ por si mandás desactivar
    default => null
};

if ($estado === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Acción inválida']);
    exit;
}

$ctrl = new MascotaController();
$res  = $ctrl->setEstado($id, $estado);

echo json_encode([
    'ok' => (bool)($res['success'] ?? false),
    'mensaje' => $res['success'] ? 'Estado actualizado' : ($res['error'] ?? 'Error')
]);
