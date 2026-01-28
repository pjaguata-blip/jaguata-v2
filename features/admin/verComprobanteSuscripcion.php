<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Services\DatabaseService;

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo "ID inválido";
    exit;
}

try {
    $db = DatabaseService::getInstance()->getConnection();

    $st = $db->prepare(
        "SELECT comprobante_path
         FROM suscripciones
         WHERE id = :id
         LIMIT 1"
    );
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $file = $row['comprobante_path'] ?? null;
    if (!$file) {
        http_response_code(404);
        echo "Sin comprobante";
        exit;
    }
    $file = basename((string)$file);

    $path = dirname(__DIR__, 2) . '/uploads/suscripciones/' . $file;
    if (!is_file($path)) {
        http_response_code(404);
        echo "Archivo no encontrado";
        exit;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'pdf'          => 'application/pdf',
        'jpg', 'jpeg'  => 'image/jpeg',
        'png'          => 'image/png',
        default        => 'application/octet-stream',
    };

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="' . $file . '"');
    header('X-Content-Type-Options: nosniff');

    readfile($path);
    exit;

} catch (Throwable $e) {
    error_log("❌ verComprobanteSuscripcion: " . $e->getMessage());
    http_response_code(500);
    echo "Error interno";
    exit;
}
