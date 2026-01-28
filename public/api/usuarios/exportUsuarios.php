<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

$controller = new UsuarioController();
$usuarios   = $controller->obtenerDatosExportacion() ?? [];

/* FORZAR DESCARGA COMO EXCEL */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_usuarios_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

/* BOM UTF-8 */
echo "\xEF\xBB\xBF";

function safeField(array $row, string $key): string
{
    if (!isset($row[$key]) || $row[$key] === null) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

/* ✅ TU BD YA GUARDA 50000 => 50.000 (solo formateo, NO multiplicar) */
function fmtMontoGs(string $valor): string
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
            vertical-align: middle;
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

        .col-center {
            text-align: center;
        }

        .col-right {
            text-align: right;
        }

        /* Estado usuario */
        .estado-activo,
        .estado-aprobado {
            background: #19875433;
            color: #198754;
            font-weight: 600;
        }

        .estado-pendiente {
            background: #ffc10733;
            color: #856404;
            font-weight: 600;
        }

        .estado-rechazado,
        .estado-suspendido {
            background: #dc354533;
            color: #dc3545;
            font-weight: 600;
        }

        .estado-inactivo,
        .estado-cancelado {
            background: #adb5bd33;
            color: #6c757d;
            font-weight: 600;
        }

        /* Suscripción */
        .sub-activa {
            background: #19875433;
            color: #198754;
            font-weight: 700;
        }

        .sub-rech,
        .sub-rechazada,
        .sub-cancelada,
        .sub-vencida {
            background: #dc354533;
            color: #dc3545;
            font-weight: 700;
        }

        .sub-pendiente {
            background: #ffc10733;
            color: #856404;
            font-weight: 700;
        }

        .sub-sin {
            background: #adb5bd33;
            color: #6c757d;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="header-title">
                REPORTE DE USUARIOS – JAGUATA 👥
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
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>

                <!-- ✅ NUEVO: reputación y suscripción -->
                <th>Reputación</th>
                <th>Total calificaciones</th>
                <th>Suscripción</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Monto (Gs)</th>

                <th>Fecha creación</th>
                <th>Última actualización</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="13" style="text-align:center; color:#777;">Sin usuarios registrados</td>
                </tr>

            <?php else:
                $i = 0;
                foreach ($usuarios as $u):
                    $i++;

                    $rowClass  = ($i % 2 === 0) ? 'fila-par' : '';

                    $id        = safeField($u, 'usu_id');
                    $nombre    = safeField($u, 'nombre');
                    $email     = safeField($u, 'email');
                    $rol       = safeField($u, 'rol');

                    $estado    = strtolower(safeField($u, 'estado'));
                    $creado    = safeField($u, 'created_at');
                    $actualiza = safeField($u, 'updated_at');

                    $estadoClass = 'estado-' . ($estado ?: 'pendiente');
                    $estadoLabel = ucfirst($estado ?: 'pendiente');

                    /* ✅ Reputación */
                    $repProm  = safeField($u, 'reputacion_promedio');
                    $repTotal = safeField($u, 'reputacion_total');
                    if ($repProm === '')  $repProm  = '0';
                    if ($repTotal === '') $repTotal = '0';

                    /* ✅ Suscripción */
                    $subEst   = strtolower(safeField($u, 'suscripcion_estado'));
                    $subIni   = safeField($u, 'suscripcion_inicio');
                    $subFin   = safeField($u, 'suscripcion_fin');
                    $subMonto = safeField($u, 'suscripcion_monto');

                    $subLabel = $subEst ? strtoupper($subEst) : 'SIN';
                    $subClass = $subEst ? ('sub-' . $subEst) : 'sub-sin';
                    if ($subEst === 'rech') $subClass = 'sub-rech';
            ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="col-id"><?= $id ?></td>
                        <td><?= $nombre ?></td>
                        <td><?= $email ?></td>
                        <td class="col-center"><?= ucfirst($rol) ?></td>

                        <td class="col-center <?= $estadoClass ?>"><?= $estadoLabel ?></td>

                        <!-- ✅ Nuevas columnas -->
                        <td class="col-center"><?= $repProm ?></td>
                        <td class="col-center"><?= $repTotal ?></td>
                        <td class="col-center <?= $subClass ?>"><?= $subLabel ?></td>
                        <td class="col-center"><?= $subIni !== '' ? $subIni : '—' ?></td>
                        <td class="col-center"><?= $subFin !== '' ? $subFin : '—' ?></td>
                        <td class="col-right"><?= fmtMontoGs($subMonto) ?></td>

                        <td><?= $creado ?></td>
                        <td><?= $actualiza ?></td>
                    </tr>
            <?php
                endforeach;
            endif; ?>
        </tbody>
    </table>

</body>

</html>
