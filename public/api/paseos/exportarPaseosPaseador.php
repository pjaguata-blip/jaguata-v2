<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Solo paseador logueado */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

// ✅ Usa el helper correcto, NO Session::get('usuario_id')
$paseadorId = Session::getUsuarioId() ?? 0;
$paseadorId = (int)$paseadorId;

$controller = new PaseoController();

/**
 * Opción A (recomendada):
 *  - Crear en tu PaseoController un método obtenerDatosExportacionPaseador($paseadorId)
 *  - Que devuelva los mismos campos que obtenerDatosExportacion(), pero filtrados por el paseador.
 */
$paseos = $controller->obtenerDatosExportacionPaseador($paseadorId) ?? [];

/**
 * Opción B (si todavía no tienes el método):
 *  - Puedes usar indexForPaseador($paseadorId) pero quizá devuelve menos columnas.
 *  - En ese caso, cambia la línea anterior por:
 *      $paseos = $controller->indexForPaseador($paseadorId) ?? [];
 */

// Si no hay datos, aviso simple
if (empty($paseos)) {
    echo "<script>alert('No hay datos para exportar'); window.history.back();</script>";
    exit;
}

// FORZAR DESCARGA COMO EXCEL
$filename = "MisPaseos_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Pragma: no-cache");
header("Expires: 0");

// BOM UTF-8 (para acentos)
echo "\xEF\xBB\xBF";

/**
 * Limpia campos para Excel
 */
function safeField(array $row, string $key): string
{
    if (!isset($row[$key])) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

/**
 * Formatea fecha/hora (usa 'inicio' o 'fecha' según venga del controlador)
 */
function formatFecha(array $row): string
{
    $raw = $row['inicio'] ?? ($row['fecha'] ?? '');
    if (!$raw) return '';
    $ts = strtotime($raw);
    if (!$ts) return $raw;
    return date('d/m/Y H:i', $ts);
}

/**
 * Formatea estado del paseo
 */
function formatEstado(array $row): string
{
    $estado = strtolower((string)($row['estado'] ?? ''));
    if ($estado === '') return '—';
    $estado = str_replace('_', ' ', $estado);
    return ucfirst($estado);
}

/**
 * Formatea estado de pago
 */
function formatEstadoPago(array $row): string
{
    $pago = strtolower((string)($row['estado_pago'] ?? ''));

    return match ($pago) {
        'procesado', 'pagado' => 'Pagado',
        'pendiente'           => 'Pendiente',
        default               => '—'
    };
}

/**
 * Formatea precio con separador de miles
 */
function formatPrecio(array $row): string
{
    $monto = (float)($row['precio_total'] ?? 0);
    if ($monto <= 0) return '—';
    return '₲ ' . number_format($monto, 0, ',', '.');
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Poppins", Arial, sans-serif;
            font-size: 12px;
        }

        .header-table {
            margin-bottom: 15px;
            width: 100%;
        }

        .header-title {
            background: #3c6255;
            color: white;
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            padding: 10px 0;
        }

        .header-date {
            background: #20c99733;
            color: #1e5247;
            font-size: 13px;
            text-align: center;
            padding: 6px 0;
            font-weight: 600;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
        }

        th {
            background-color: #3c6255;
            color: #ffffff;
            font-weight: 600;
            text-align: center;
        }

        tr.fila-par td {
            background-color: #f4f6f9;
        }

        .col-id {
            text-align: center;
            width: 60px;
        }

        .col-fecha {
            text-align: center;
            white-space: nowrap;
        }

        .col-estado,
        .col-pago {
            text-align: center;
        }

        .col-precio {
            text-align: right;
            white-space: nowrap;
        }

        .badge-pago-pendiente {
            background: #ffc10733;
            color: #856404;
            font-weight: 600;
        }

        .badge-pago-pagado {
            background: #19875433;
            color: #198754;
            font-weight: 600;
        }

        .badge-pago-otro {
            background: #adb5bd33;
            color: #495057;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="header-title">
                REPORTE – MIS PASEOS 🐾
            </td>
        </tr>
        <tr>
            <td class="header-date">
                Generado automáticamente el <?= date("d/m/Y H:i") ?>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>ID Paseo</th>
                <th>Mascota</th>
                <th>Dueño</th>
                <th>Fecha / Hora</th>
                <th>Duración (min)</th>
                <th>Estado Paseo</th>
                <th>Estado Pago</th>
                <th>Precio Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($paseos)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; color:#777;">
                        Sin paseos registrados.
                    </td>
                </tr>
            <?php else: ?>
                <?php $i = 0; ?>
                <?php foreach ($paseos as $p): ?>
                    <?php
                    $i++;
                    $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

                    $id        = safeField($p, 'paseo_id');
                    $mascota   = safeField($p, 'nombre_mascota');
                    $dueno     = safeField($p, 'nombre_dueno');   // asegúrate que el SELECT devuelva este alias
                    $fecha     = formatFecha($p);
                    $duracion  = safeField($p, 'duracion_min');   // o 'duracion' según tu consulta
                    $estado    = formatEstado($p);
                    $pagoTxt   = formatEstadoPago($p);
                    $precio    = formatPrecio($p);

                    $pagoLower = strtolower($pagoTxt);
                    $pagoClass = match ($pagoLower) {
                        'pagado'   => 'badge-pago-pagado',
                        'pendiente' => 'badge-pago-pendiente',
                        default    => 'badge-pago-otro'
                    };
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="col-id"><?= $id ?></td>
                        <td><?= $mascota ?></td>
                        <td><?= $dueno ?></td>
                        <td class="col-fecha"><?= $fecha ?></td>
                        <td style="text-align:center;"><?= $duracion ?></td>
                        <td class="col-estado"><?= $estado ?></td>
                        <td class="col-pago <?= $pagoClass ?>"><?= $pagoTxt ?></td>
                        <td class="col-precio"><?= $precio ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>