<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/AuditoriaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuditoriaController;

AppConfig::init();

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=auditoria_jaguata.xls");
header("Pragma: no-cache");
header("Expires: 0");

$controller = new AuditoriaController();
$registros = $controller->obtenerDatosExportacion();

// Si no hay registros, mostrar un mensaje
if (empty($registros)) {
    echo "No hay registros de auditoría disponibles.";
    exit;
}

// ✅ Encabezado de columnas
echo "ID\tUsuario\tAcción\tMódulo\tDetalles\tFecha\n";

// ✅ Filas de datos
foreach ($registros as $r) {
    $id = $r['id'] ?? '';
    $usuario = $r['usuario'] ?? '';
    $accion = $r['accion'] ?? '';
    $modulo = $r['modulo'] ?? '';
    $detalles = str_replace(["\r", "\n", "\t"], ' ', $r['detalles'] ?? '');
    $fecha = $r['fecha'] ?? '';

    echo "{$id}\t{$usuario}\t{$accion}\t{$modulo}\t{$detalles}\t{$fecha}\n";
}
exit;
