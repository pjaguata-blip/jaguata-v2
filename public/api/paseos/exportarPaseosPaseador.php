<?php
require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// Inicializar configuración
AppConfig::init();

// Verificar sesión
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

// Obtener el ID del paseador logueado
$paseadorId = (int)(Session::get('usuario_id') ?? 0);

// Instanciar controlador
$paseoController = new PaseoController();

// Obtener los paseos del paseador
$paseos = $paseoController->indexForPaseador($paseadorId);

// Si no hay paseos, mostrar mensaje
if (empty($paseos)) {
    echo "<script>alert('No hay datos para exportar'); window.history.back();</script>";
    exit;
}

// Nombre del archivo
$filename = "MisPaseos_" . date('Y-m-d_H-i-s') . ".xls";

// Cabeceras HTTP para descarga
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Estilos básicos para Excel
echo "
<table border='1'>
    <thead>
        <tr style='background-color:#3c6255; color:#fff; font-weight:bold;'>
            <th>ID</th>
            <th>Mascota</th>
            <th>Dueño</th>
            <th>Fecha</th>
            <th>Duración</th>
            <th>Estado</th>
            <th>Pago</th>
            <th>Precio Total</th>
        </tr>
    </thead>
    <tbody>
";

// Cargar los datos en la tabla
foreach ($paseos as $p) {
    $id = $p['paseo_id'] ?? '-';
    $mascota = htmlspecialchars($p['nombre_mascota'] ?? '-', ENT_QUOTES, 'UTF-8');
    $dueno = htmlspecialchars($p['nombre_dueno'] ?? '-', ENT_QUOTES, 'UTF-8');
    $fecha = isset($p['inicio']) ? date('d/m/Y H:i', strtotime($p['inicio'])) : '-';
    $duracion = $p['duracion'] ?? $p['duracion_min'] ?? '-';
    $estado = ucfirst(str_replace('_', ' ', strtolower($p['estado'] ?? '-')));
    $pago = match ($p['estado_pago'] ?? '') {
        'procesado' => 'Pagado',
        'pendiente' => 'Pendiente',
        default => '—'
    };
    $precio = number_format((float)($p['precio_total'] ?? 0), 0, ',', '.');

    echo "
        <tr>
            <td>{$id}</td>
            <td>{$mascota}</td>
            <td>{$dueno}</td>
            <td>{$fecha}</td>
            <td>{$duracion} min</td>
            <td>{$estado}</td>
            <td>{$pago}</td>
            <td>₲ {$precio}</td>
        </tr>
    ";
}

echo "</tbody></table>";
exit;
