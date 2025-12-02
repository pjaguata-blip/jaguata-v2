<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\PaseoController;

AppConfig::init();

$controller = new PaseoController();
$paseos = $controller->obtenerDatosExportacion() ?? [];

// FORZAR DESCARGA COMO EXCEL
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_paseos_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// BOM UTF-8
echo "\xEF\xBB\xBF";

function safeField(array $row, string $key): string
{
    if (!isset($row[$key])) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
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

        /* ==========================
       ENCABEZADO CHULI
    ========================== */
        .header-table {
            margin-bottom: 15px;
            width: 100%;
        }

        .header-title {
            background: #3c6255;
            /* verde jaguata */
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

        /* ==========================
       TABLA PRINCIPAL
    ========================== */
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

        .col-id,
        .col-duracion,
        .col-puntos {
            text-align: center;
        }

        .col-monto {
            text-align: right;
        }

        /* ===============================
        ESTADO PASEO
    =============================== */
        .estado-pendiente {
            background: #ffc10733;
            color: #856404;
            font-weight: 600;
        }

        .estado-confirmado,
        .estado-en_curso {
            background: #0d6efd33;
            color: #0d6efd;
            font-weight: 600;
        }

        .estado-completo,
        .estado-finalizado {
            background: #19875433;
            color: #198754;
            font-weight: 600;
        }

        .estado-cancelado {
            background: #dc354533;
            color: #dc3545;
            font-weight: 600;
        }

        /* ===============================
        ESTADO PAGO
    =============================== */
        .estado-pago-pendiente {
            background: #ffc10733;
            color: #856404;
            font-weight: 600;
        }

        .estado-pago-pagado {
            background: #19875433;
            color: #198754;
            font-weight: 600;
        }

        .estado-pago-cancelado {
            background: #dc354533;
            color: #dc3545;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <!-- ===========================
     ENCABEZADO EXCEL BONITO
=========================== -->
    <table class="header-table">
        <tr>
            <td class="header-title">
                REPORTE DE PASEOS – JAGUATA 🐾
            </td>
        </tr>
        <tr>
            <td class="header-date">
                Generado automáticamente el <?= date("d/m/Y H:i") ?>
            </td>
        </tr>
    </table>

    <!-- ===========================
     TABLA PRINCIPAL
=========================== -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Dueño</th>
                <th>Paseador</th>
                <th>Mascota</th>
                <th>Inicio</th>
                <th>Duración (min)</th>
                <th>Costo (Gs)</th>
                <th>Estado</th>
                <th>Estado Pago</th>
                <th>Puntos</th>
            </tr>
        </thead>
        <tbody>

            <?php if (empty($paseos)) : ?>

                <tr>
                    <td colspan="10" style="text-align:center; color:#777;">Sin datos disponibles</td>
                </tr>

                <?php else: $i = 0;
                foreach ($paseos as $p): $i++; ?>

                    <?php
                    $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

                    $id         = safeField($p, 'id');
                    $dueno      = safeField($p, 'dueno_nombre');
                    $paseador   = safeField($p, 'paseador_nombre');
                    $mascota    = safeField($p, 'mascota_nombre');
                    $inicio     = safeField($p, 'fecha_inicio');
                    $duracion   = safeField($p, 'duracion');
                    $costo      = safeField($p, 'costo');
                    $estado     = strtolower(safeField($p, 'estado'));
                    $estadoPago = strtolower(safeField($p, 'estado_pago'));
                    $puntos     = safeField($p, 'puntos_ganados');

                    $estadoClass = "estado-" . $estado;
                    $estadoPagoClass = "estado-pago-" . $estadoPago;

                    $estadoLabel = ucfirst(str_replace('_', ' ', $estado));
                    $estadoPagoLabel = ucfirst($estadoPago);
                    ?>

                    <tr class="<?= $rowClass ?>">
                        <td class="col-id"><?= $id ?></td>
                        <td><?= $dueno ?></td>
                        <td><?= $paseador ?></td>
                        <td><?= $mascota ?></td>
                        <td><?= $inicio ?></td>
                        <td class="col-duracion"><?= $duracion ?></td>
                        <td class="col-monto"><?= $costo ?></td>
                        <td class="<?= $estadoClass ?>"><?= $estadoLabel ?></td>
                        <td class="<?= $estadoPagoClass ?>"><?= $estadoPagoLabel ?></td>
                        <td class="col-puntos"><?= $puntos ?></td>
                    </tr>

            <?php endforeach;
            endif; ?>

        </tbody>
    </table>

</body>

</html>