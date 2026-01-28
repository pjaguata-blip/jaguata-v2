<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\MascotaController;

AppConfig::init();

$controller = new MascotaController();
$mascotas   = $controller->obtenerDatosExportacion() ?? [];

/* FORZAR DESCARGA COMO EXCEL */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_mascotas_jaguata_" . date('Ymd_His') . ".xls");
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
        .header-title {
            background: #3c6255; color: white; font-size: 20px; font-weight: 700;
            text-align: center; padding: 10px 0;
        }
        .header-date {
            background: #20c99733; color: #1e5247; font-size: 13px;
            text-align: center; padding: 6px 0; font-weight: 600;
        }

        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #dee2e6; padding: 6px 8px; vertical-align: middle; }
        th { background-color: #3c6255; color: #ffffff; font-weight: 600; text-align: center; }

        tr.fila-par td { background-color: #f4f6f9; }

        .col-id { text-align: center; width: 60px; }
        .col-center { text-align: center; }
        .col-right { text-align: right; }

        .estado-activo { background: #19875433; color: #198754; font-weight: 700; }
        .estado-inactivo { background: #dc354533; color: #dc3545; font-weight: 700; }

        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-weight: 700;
        }
        .pill-p { background:#e7f6ff; color:#0b6fa4; }
        .pill-m { background:#fff3cd; color:#856404; }
        .pill-g { background:#d1e7dd; color:#0f5132; }
    </style>
</head>

<body>

<table class="header-table">
    <tr><td class="header-title">REPORTE DE MASCOTAS – JAGUATA 🐾</td></tr>
    <tr><td class="header-date">Generado automáticamente el <?= date("d/m/Y H:i") ?></td></tr>
</table>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Mascota</th>
            <th>Raza</th>
            <th>Peso (kg)</th>
            <th>Tamaño</th>
            <th>Edad (meses)</th>

            <th>Dueño</th>
            <th>Email dueño</th>

            <th>Estado</th>

            <th>Total paseos</th>
            <th>Total gastado (Gs)</th>
            <th>Último paseo</th>
            <th>Puntos</th>

            <th>Creación</th>
            <th>Actualización</th>
        </tr>
    </thead>

    <tbody>
    <?php if (empty($mascotas)): ?>
        <tr>
            <td colspan="15" style="text-align:center; color:#777;">Sin mascotas registradas</td>
        </tr>
    <?php else:
        $i = 0;
        foreach ($mascotas as $m):
            $i++;
            $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

            $id     = safeField($m, 'mascota_id');
            $nom    = safeField($m, 'nombre');
            $raza   = safeField($m, 'raza');
            $peso   = safeField($m, 'peso_kg');
            $tam    = strtolower(safeField($m, 'tamano'));
            $edad   = safeField($m, 'edad_meses');

            $duenoN = safeField($m, 'dueno_nombre');
            $duenoE = safeField($m, 'dueno_email');

            $estado = strtolower(safeField($m, 'estado'));
            $estadoClass = ($estado === 'activo') ? 'estado-activo' : 'estado-inactivo';
            $estadoLabel = $estado ? ucfirst($estado) : '—';

            $tp     = safeField($m, 'total_paseos');
            $tg     = safeField($m, 'total_gastado');
            $ult    = safeField($m, 'ultimo_paseo');
            $pts    = safeField($m, 'puntos_ganados');

            $crea   = safeField($m, 'created_at');
            $act    = safeField($m, 'updated_at');

            // “Tamaño” bonito
            $pill = 'pill-m';
            $tamLabel = $tam ?: '—';
            if ($tam === 'pequeno') { $pill='pill-p'; $tamLabel='Pequeño'; }
            elseif ($tam === 'mediano') { $pill='pill-m'; $tamLabel='Mediano'; }
            elseif ($tam === 'grande') { $pill='pill-g'; $tamLabel='Grande'; }
    ?>
        <tr class="<?= $rowClass ?>">
            <td class="col-id"><?= $id ?></td>
            <td><?= $nom ?></td>
            <td><?= $raza ?></td>
            <td class="col-center"><?= $peso ?></td>
            <td class="col-center"><span class="pill <?= $pill ?>"><?= $tamLabel ?></span></td>
            <td class="col-center"><?= $edad !== '' ? $edad : '—' ?></td>

            <td><?= $duenoN ?></td>
            <td><?= $duenoE ?></td>

            <td class="col-center <?= $estadoClass ?>"><?= $estadoLabel ?></td>

            <td class="col-center"><?= $tp !== '' ? $tp : '0' ?></td>
            <td class="col-right"><?= fmtGs($tg) ?></td>
            <td class="col-center"><?= $ult !== '' ? $ult : '—' ?></td>
            <td class="col-center"><?= $pts !== '' ? $pts : '0' ?></td>

            <td><?= $crea ?></td>
            <td><?= $act ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

</body>
</html>
