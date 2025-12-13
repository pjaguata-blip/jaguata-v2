<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 3) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 3) . '/src/Models/Calificacion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Models\Calificacion;

AppConfig::init();
header('Content-Type: application/json; charset=utf-8');

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$usuarioId = (int)($_GET['usuario_id'] ?? 0);
$tipo      = (string)($_GET['tipo'] ?? 'paseador');

if ($usuarioId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'usuario_id inválido']);
    exit;
}

try {
    $model = new Calificacion();
    $opiniones = $model->opinionesPorUsuario($usuarioId, $tipo, 30);

    echo json_encode([
        'ok' => true,
        'total' => count($opiniones),
        'opiniones' => $opiniones
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener opiniones']);
}
