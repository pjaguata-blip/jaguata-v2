<?php
require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=usuarios_jaguata.xls");
header("Pragma: no-cache");
header("Expires: 0");

$controller = new UsuarioController();
$usuarios = $controller->obtenerDatosExportacion();

echo "ID\tNombre\tEmail\tRol\tEstado\tFecha Creación\tFecha Actualización\n";

foreach ($usuarios as $u) {
    echo "{$u['usu_id']}\t{$u['nombre']}\t{$u['email']}\t{$u['rol']}\t{$u['estado']}\t{$u['created_at']}\t{$u['updated_at']}\n";
}
exit;
