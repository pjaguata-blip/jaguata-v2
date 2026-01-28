<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;

AppConfig::init();

/**
 * Trae datos de suscripciones para exportación.
 * Ajustado a tu estructura real según tu Model:
 * - tabla: suscripciones
 * - campos: id, paseador_id, plan, monto, estado, inicio, fin, comprobante_path, metodo_pago, referencia, nota, motivo_rechazo, created_at, updated_at
 * - join con usuarios para nombre/email del paseador: u.usu_id = s.paseador_id
 */
$db = DatabaseService::getInstance()->getConnection();

$sql = "
    SELECT
        s.id                    AS suscripcion_id,
        s.paseador_id,
        u.nombre                AS paseador_nombre,
        u.email                 AS paseador_email,
        s.plan,
        s.monto,
        s.estado,
        s.inicio,
        s.fin,
        s.metodo_pago,
        s.referencia,
        s.comprobante_path,
        s.nota,
        s.motivo_rechazo,
        s.created_at,
        s.updated_at
    FROM suscripciones s
    JOIN usuarios u ON u.usu_id = s.paseador_id
    ORDER BY s.created_at DESC, s.id DESC
";

$st = $db->prepare($sql);
$st->execute();
$suscripciones = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* FORZAR DESCARGA COMO EXCEL */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_suscripciones_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

/* BOM UTF-8 */
echo "\xEF\xBB\xBF";

function safeField(array $row, string $key): string
{
    if (!isset($row[$key]) || $row[$key] === null) return '';
    return str_replace(["\t", "\r", "\n"], ' ', (string)$row[$key]);
}

/**
 * ✅ Devuelve monto como NÚMERO PURO (sin puntos, sin ₲).
 * Ej:
 *  "45.000" -> "45000"
 *  "45000"  -> "45000"
 *  ""       -> "0"
 */
function montoNumero(string $valor): string
{
    $valor = trim($valor);
    if ($valor === '' || $valor === '0') return '0';
    return preg_replace('/[^0-9]/', '', $valor);
}

function estadoLabel(string $estado): string
{
    $estado = trim($estado);
    if ($estado === '') return 'Pendiente';
    $estado = str_replace('_', ' ', $estado);
    return ucfirst(mb_strtolower($estado));
}

function estadoClass(string $estado): string
{
    $e = strtolower(trim($estado));
    $e = str_replace([' ', '-'], '_', $e);
    $e = preg_replace('/[^a-z0-9_]/', '', $e);

    // Normalizaciones
    if ($e === '') $e = 'pendiente';
    if ($e === 'rechazado') $e = 'rechazada';
    if ($e === 'aprobado')  $e = 'activa';

    return 'sus-' . $e;
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

        table { border-collapse: collapse; width: 100%; }

        th, td {
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

        tr.fila-par td { background-color: #f4f6f9; }

        .col-center { text-align: center; }
        .col-right  { text-align: right; }

        /* Estados suscripción (visual) */
        .sus-pendiente   { background:#ffc10733; color:#856404; font-weight:700; }
        .sus-activa      { background:#0d6efd33; color:#0d6efd; font-weight:700; }
        .sus-vencida     { background:#6c757d33; color:#6c757d; font-weight:700; }
        .sus-rechazada   { background:#dc354533; color:#dc3545; font-weight:700; }
        .sus-cancelada   { background:#dc354533; color:#dc3545; font-weight:700; }
        .sus-aprobada    { background:#19875433; color:#198754; font-weight:700; }
    </style>
</head>

<body>

<table class="header-table">
    <tr>
        <td class="header-title">REPORTE DE SUSCRIPCIONES – JAGUATA ⭐</td>
    </tr>
    <tr>
        <td class="header-date">Generado automáticamente el <?= date("d/m/Y H:i") ?></td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>ID Paseador</th>
            <th>Paseador</th>
            <th>Email</th>
            <th>Plan</th>
            <th>Monto</th>
            <th>Estado</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Método</th>
            <th>Referencia</th>
            <th>Comprobante</th>
            <th>Nota</th>
            <th>Motivo Rechazo</th>
            <th>Creado</th>
            <th>Actualizado</th>
        </tr>
    </thead>

    <tbody>
    <?php if (empty($suscripciones)): ?>
        <tr>
            <td colspan="16" style="text-align:center; color:#777;">Sin suscripciones registradas</td>
        </tr>
    <?php else:
        $i = 0;
        foreach ($suscripciones as $s):
            $i++;
            $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

            $estadoRaw   = safeField($s, 'estado');
            $estadoClass = estadoClass($estadoRaw);
            $estadoOut   = estadoLabel($estadoRaw);

            $montoRaw = safeField($s, 'monto');
            $montoOut = montoNumero($montoRaw); // ✅ número puro
    ?>
        <tr class="<?= $rowClass ?>">
            <td class="col-center"><?= safeField($s, 'suscripcion_id') ?></td>
            <td class="col-center"><?= safeField($s, 'paseador_id') ?></td>
            <td><?= safeField($s, 'paseador_nombre') ?></td>
            <td><?= safeField($s, 'paseador_email') ?></td>
            <td class="col-center"><?= safeField($s, 'plan') ?: '—' ?></td>

            <!-- ✅ MONTO SIN FORMATO -->
            <td class="col-right"><?= $montoOut ?></td>

            <td class="col-center <?= $estadoClass ?>"><?= $estadoOut ?></td>
            <td class="col-center"><?= safeField($s, 'inicio') ?: '—' ?></td>
            <td class="col-center"><?= safeField($s, 'fin') ?: '—' ?></td>
            <td class="col-center"><?= safeField($s, 'metodo_pago') ?: '—' ?></td>
            <td><?= safeField($s, 'referencia') ?: '—' ?></td>
            <td><?= safeField($s, 'comprobante_path') ?: '—' ?></td>
            <td><?= safeField($s, 'nota') ?: '—' ?></td>
            <td><?= safeField($s, 'motivo_rechazo') ?: '—' ?></td>
            <td class="col-center"><?= safeField($s, 'created_at') ?: '—' ?></td>
            <td class="col-center"><?= safeField($s, 'updated_at') ?: '—' ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

</body>
</html>
