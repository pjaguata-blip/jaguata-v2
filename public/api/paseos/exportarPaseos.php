<?php
require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseoController;

AppConfig::init();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=paseos_jaguata.xls");
header("Pragma: no-cache");
header("Expires: 0");

$controller = new PaseoController();
$paseos = $controller->obtenerDatosExportacion();

echo "ID\tDueño\tPaseador\tMascota\tInicio\tDuración (min)\tCosto\tEstado\tEstado Pago\tPuntos\n";

foreach ($paseos as $p) {
    echo "{$p['id']}\t{$p['dueno_nombre']}\t{$p['paseador_nombre']}\t{$p['mascota_nombre']}\t{$p['fecha_inicio']}\t{$p['duracion']}\t{$p['costo']}\t{$p['estado']}\t{$p['estado_pago']}\t{$p['puntos_ganados']}\n";
}

exit;
