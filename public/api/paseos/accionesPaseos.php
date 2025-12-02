<?php
require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../../src/Controllers/AuditoriaController.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\AuditoriaController;
use Jaguata\Helpers\Session;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // opcional si usás fetch desde otro dominio
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 🚨 Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido (solo POST)']);
    exit;
}

// 🔒 Validar que sea ADMIN (opcional pero recomendado)
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

// 📦 Datos recibidos
$id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$accion = trim($_POST['accion'] ?? '');

// 🚨 Validaciones básicas
if ($id <= 0 || $accion === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos o inválidos']);
    exit;
}

// 🧩 Log simple
error_log("[ACCION_PASEO] Acción: {$accion} | ID: {$id} | IP: {$_SERVER['REMOTE_ADDR']}");

try {
    $controller = new PaseoController();

    // 🔹 AQUÍ el cambio importante: usamos cambiarEstadoDesdeAdmin()
    $resultado = $controller->cambiarEstadoDesdeAdmin($id, $accion);

    // 🟢 Registrar en auditoría solo si fue exitoso
    if (!empty($resultado['ok']) && $resultado['ok'] === true) {
        $auditoria = new AuditoriaController();
        $usuario   = Session::getUsuarioEmail() ?? 'admin@jaguata.com';
        $detalle   = "Se ejecutó la acción '{$accion}' sobre el paseo #{$id} desde el panel admin.";

        $auditoria->registrar(
            $usuario,
            'Actualización de estado',
            'Paseos',
            $detalle
        );
    }

    // 🔹 Respuesta estandarizada
    http_response_code(!empty($resultado['ok']) && $resultado['ok'] ? 200 : 500);
    echo json_encode([
        'ok'        => $resultado['ok'] ?? false,
        'accion'    => $accion,
        'mensaje'   => $resultado['mensaje'] ?? 'Operación realizada',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("❌ Error en accionPaseo.php => " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error interno del servidor',
        'error'   => $e->getMessage()
    ]);
}
