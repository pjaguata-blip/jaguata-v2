<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * ✅ ROOT robusto (no se rompe si se mueve la carpeta)
 * Busca hacia arriba hasta encontrar: /src/Config/AppConfig.php
 */
$dir = __DIR__;
$ROOT = null;

for ($i = 0; $i < 10; $i++) {
    if (file_exists($dir . '/src/Config/AppConfig.php')) {
        $ROOT = realpath($dir);
        break;
    }
    $parent = dirname($dir);
    if ($parent === $dir) break;
    $dir = $parent;
}

if (!$ROOT) {
    http_response_code(500);
    die('❌ No se encontró el ROOT del proyecto. Falta: src/Config/AppConfig.php');
}

/** ✅ requires con rutas correctas */
require_once $ROOT . '/src/Config/AppConfig.php';
require_once $ROOT . '/src/Controllers/AuditoriaController.php';
require_once $ROOT . '/src/Helpers/Auditoria.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuditoriaController;
use Jaguata\Helpers\Auditoria;

/** ✅ iniciar config */
AppConfig::init();

/** ✅ obtener datos para exportación */
$controller = new AuditoriaController();
$registros = $controller->obtenerDatosExportacion() ?? [];

/** ✅ registrar auditoría del export */
Auditoria::log('Exportó auditoría', 'Auditoría', 'Exportación en Excel (.xls)');

/** ✅ headers para Excel */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_auditoria_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

/** ✅ BOM UTF-8 para Excel */
echo "\xEF\xBB\xBF";

function safeField(array $row, string $key): string
{
    if (!isset($row[$key]) || $row[$key] === null) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: "Poppins", Arial, sans-serif; font-size: 12px; }
        .header-table { margin-bottom: 15px; width: 100%; }
        .header-title { background: #3c6255; color: white; font-size: 20px; font-weight: 700; text-align: center; padding: 10px 0; }
        .header-date { background: #20c99733; color: #1e5247; font-size: 13px; text-align: center; padding: 6px 0; font-weight: 600; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #dee2e6; padding: 6px 8px; vertical-align: top; }
        th { background-color: #3c6255; color: #ffffff; font-weight: 600; text-align: center; }
        tr:nth-child(even) td { background-color: #f4f6f9; }
    </style>
</head>
<body>

<table class="header-table">
    <tr><td class="header-title">REPORTE DE AUDITORÍA – JAGUATA 🔐</td></tr>
    <tr><td class="header-date">Generado automáticamente el <?= date("d/m/Y H:i") ?></td></tr>
</table>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Usuario</th>
        <th>Acción</th>
        <th>Módulo</th>
        <th>Detalles</th>
        <th>Fecha</th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($registros)): ?>
        <tr><td colspan="6" style="text-align:center; color:#777;">Sin registros de auditoría</td></tr>
    <?php else: foreach ($registros as $r): ?>
        <tr>
            <td><?= safeField($r, 'id') ?></td>
            <td><?= safeField($r, 'usuario') ?></td>
            <td><?= safeField($r, 'accion') ?></td>
            <td><?= safeField($r, 'modulo') ?></td>
            <td><?= safeField($r, 'detalles') ?></td>
            <td><?= safeField($r, 'fecha') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

</body>
</html>
