<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Services/DatabaseService.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;
use Jaguata\Helpers\Session;

AppConfig::init();

// 🔒 Solo usuarios logueados
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo 'No autorizado';
    exit;
}

$pagoId = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;
if ($pagoId <= 0) {
    http_response_code(400);
    echo 'ID de pago inválido';
    exit;
}

try {
    $db = DatabaseService::getInstance()->getConnection();

    // 🔹 Traer el pago + paseo
    $sql = "
        SELECT 
            pg.*,
            p.paseador_id,
            p.mascota_id
        FROM pagos pg
        INNER JOIN paseos p ON p.paseo_id = pg.paseo_id
        WHERE pg.id = :id
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $pagoId]);
    $pago = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$pago) {
        http_response_code(404);
        echo 'Pago no encontrado';
        exit;
    }

    // ⚠️ Opcional: validar permisos
    $usuarioId = (int)(Session::getUsuarioId() ?? 0);
    $rol       = Session::getUsuarioRol();

    if ($rol === 'paseador' && (int)$pago['paseador_id'] !== $usuarioId) {
        http_response_code(403);
        echo 'No tienes permiso para ver este comprobante.';
        exit;
    }

    // 📁 Ruta del archivo de comprobante tal como se guardó en la BD
    $archivoDb = trim((string)($pago['comprobante'] ?? ''));

    if ($archivoDb === '') {
        http_response_code(404);
        echo 'Este pago no tiene comprobante adjunto.';
        exit;
    }

    // Nos quedamos solo con el nombre del archivo, aunque venga con ruta
    $nombreArchivo = basename($archivoDb);

    // Ruta física donde realmente guardaste los archivos
    $baseDir  = __DIR__ . '/../../../assets/uploads/comprobantes/';
    $filePath = $baseDir . $nombreArchivo;

    if (!is_file($filePath)) {
        http_response_code(404);
        echo 'Archivo de comprobante no encontrado.';
        exit;
    }

    // Detectar MIME
    $mime = function_exists('mime_content_type')
        ? mime_content_type($filePath)
        : 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
    header('Content-Length: ' . filesize($filePath));

    readfile($filePath);
    exit;
} catch (\Throwable $e) {
    error_log('Error en comprobantePago.php: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error interno al mostrar el comprobante.';
    exit;
}
