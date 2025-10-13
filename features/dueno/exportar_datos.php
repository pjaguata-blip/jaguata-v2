<?php

/**
 * features/dueno/exportar_datos.php
 * Exporta los datos del dueño autenticado (paseos, mascotas o pagos)
 * en formato CSV compatible con Excel.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Controllers/PagoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Controllers\PagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

// Autenticación
$auth = new AuthController();
$auth->checkRole('dueno');
$duenoId = (int)(Session::get('usuario_id') ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    echo "No autorizado.";
    exit;
}

$tipo = strtolower($_GET['tipo'] ?? '');
$filename = 'export_' . $tipo . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para Excel UTF-8

switch ($tipo) {
    case 'paseos':
        $controller = new PaseoController();
        $paseos = $controller->index();
        $paseos = array_filter($paseos, fn($p) => (int)($p['dueno_id'] ?? 0) === $duenoId);
        fputcsv($out, ['ID', 'Mascota', 'Paseador', 'Inicio', 'Fin', 'Duración (min)', 'Estado', 'Precio']);
        foreach ($paseos as $p) {
            fputcsv($out, [
                $p['paseo_id'] ?? '',
                $p['nombre_mascota'] ?? '',
                $p['nombre_paseador'] ?? '',
                $p['inicio'] ?? '',
                $p['fin'] ?? '',
                $p['duracion'] ?? '',
                ucfirst($p['estado'] ?? ''),
                $p['precio_total'] ?? ''
            ]);
        }
        break;

    case 'mascotas':
        $controller = new MascotaController();
        $mascotas = $controller->index();
        $mascotas = array_filter($mascotas, fn($m) => (int)($m['dueno_id'] ?? 0) === $duenoId);
        fputcsv($out, ['ID', 'Nombre', 'Raza', 'Edad (meses)', 'Tamaño', 'Sexo']);
        foreach ($mascotas as $m) {
            fputcsv($out, [
                $m['id'] ?? '',
                $m['nombre'] ?? '',
                $m['raza'] ?? '',
                $m['edad'] ?? '',
                $m['tamano'] ?? '',
                $m['sexo'] ?? ''
            ]);
        }
        break;

    case 'pagos':
        $controller = new PagoController();
        $pagos = $controller->listarGastosDueno(['dueno_id' => $duenoId]);
        fputcsv($out, ['ID Pago', 'Fecha', 'Monto', 'Método', 'Estado', 'Mascota', 'Paseador']);
        foreach ($pagos as $p) {
            fputcsv($out, [
                $p['id'] ?? '',
                $p['fecha_pago'] ?? '',
                $p['monto'] ?? '',
                $p['metodo'] ?? '',
                $p['estado'] ?? '',
                $p['mascota'] ?? '',
                $p['paseador'] ?? ''
            ]);
        }
        break;

    default:
        fputcsv($out, ['Error: tipo de exportación no válido']);
        break;
}

fclose($out);
exit;
