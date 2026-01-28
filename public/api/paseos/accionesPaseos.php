<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseoController;

AppConfig::init();

$controller = new PaseoController();
$paseos     = $controller->obtenerDatosExportacion() ?? [];

/* FORZAR DESCARGA COMO EXCEL */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_paseos_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

/* BOM UTF-8 */
echo "\xEF\xBB\xBF";

function safeField(array $row, string $key): string
{
    if (!isset($row[$key]) || $row[$key] === null) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

function fmtGs(string $valor): string
{
    if ($valor === '' || $valor === '0') return '0';
    $n = (int)str_replace(['.', ',', ' '], ['', '', ''], $valor);
    return number_format($n, 0, ',', '.'); // 50000 -> 50.000
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
        th, td { border: 1px solid #dee2e6; padding: 6px 8px; vertical-align: middle; }
        th { background-color: #3c6255; color: #ffffff; font-weight: 600; text-align: center; }

        tr.fila-par td { background-color: #f4f6f9; }

        .col-id, .col-duracion, .col-puntos, .col-cant { text-align: center; }
        .col-monto { text-align: right; }

        .estado-pendiente { background: #ffc10733; color: #856404; font-weight: 600; }
        .estado-confirmado, .estado-en_curso { background: #0d6efd33; color: #0d6efd; font-weight: 600; }
        .estado-completo, .estado-finalizado { background: #19875433; color: #198754; font-weight: 600; }
        .estado-cancelado { background: #dc354533; color: #dc3545; font-weight: 600; }

        .estado-pago-pendiente { background: #ffc10733; color: #856404; font-weight: 600; }
        .estado-pago-pagado, .estado-pago-confirmado_por_admin, .estado-pago-confirmado_por_dueno, .estado-pago-procesado { background: #19875433; color: #198754; font-weight: 600; }
        .estado-pago-cancelado, .estado-pago-rechazado { background: #dc354533; color: #dc3545; font-weight: 600; }
    </style>
</head>

<body>

<table class="header-table">
    <tr><td class="header-title">REPORTE DE PASEOS – JAGUATA 🐾</td></tr>
    <tr><td class="header-date">Generado automáticamente el <?= date("d/m/Y H:i") ?></td></tr>
</table>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Dueño</th>
            <th>Paseador</th>
            <th>Mascotas</th>
            <th>Cant.</th>
            <th>Inicio</th>
            <th>Duración (min)</th>
            <th>Costo (Gs)</th>
            <th>Estado</th>
            <th>Estado Pago</th>
            <th>Puntos</th>
            <th>Calificación</th>
            <th>Comentario</th>
        </tr>
    </thead>

    <tbody>
        <?php if (empty($paseos)) : ?>
            <tr>
                <td colspan="13" style="text-align:center; color:#777;">Sin datos disponibles</td>
            </tr>
        <?php else:
            $i = 0;
            foreach ($paseos as $p):
                $i++;

                $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

                $id         = safeField($p, 'id');
                $dueno      = safeField($p, 'dueno_nombre');
                $paseador   = safeField($p, 'paseador_nombre');

                $m1         = safeField($p, 'mascota_nombre');
                $m2         = safeField($p, 'mascota2_nombre');
                $cant       = safeField($p, 'cantidad_mascotas');

                $inicio     = safeField($p, 'fecha_inicio');
                $duracion   = safeField($p, 'duracion');

                $costo      = safeField($p, 'costo');

                $estado     = strtolower(safeField($p, 'estado'));
                $estadoPago = strtolower(safeField($p, 'estado_pago'));

                $puntos     = safeField($p, 'puntos_ganados');

                $calif      = safeField($p, 'calificacion');
                $coment     = safeField($p, 'comentario_calificacion');

                $estadoClass     = "estado-" . ($estado ?: 'pendiente');
                $estadoPagoClass = "estado-pago-" . ($estadoPago ?: 'pendiente');

                $estadoLabel     = ucfirst(str_replace('_', ' ', ($estado ?: 'pendiente')));
                $estadoPagoLabel = ucfirst(str_replace('_', ' ', ($estadoPago ?: 'pendiente')));

                $mascotasLabel = $m1;
                if ($m2 !== '') {
                    $mascotasLabel .= " + " . $m2;
                }
        ?>
            <tr class="<?= $rowClass ?>">
                <td class="col-id"><?= $id ?></td>
                <td><?= $dueno ?></td>
                <td><?= $paseador ?></td>
                <td><?= $mascotasLabel ?></td>
                <td class="col-cant"><?= $cant !== '' ? $cant : '1' ?></td>
                <td><?= $inicio ?></td>
                <td class="col-duracion"><?= $duracion ?></td>
                <td class="col-monto"><?= fmtGs($costo) ?></td>
                <td class="<?= $estadoClass ?>"><?= $estadoLabel ?></td>
                <td class="<?= $estadoPagoClass ?>"><?= $estadoPagoLabel ?></td>
                <td class="col-puntos"><?= $puntos !== '' ? $puntos : '0' ?></td>
                <td class="col-id"><?= $calif !== '' ? $calif : '0' ?></td>
                <td><?= $coment !== '' ? $coment : '—' ?></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

</body>
</html>
