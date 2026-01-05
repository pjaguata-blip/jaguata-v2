<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';
require_once __DIR__ . '/../../../src/Services/DatabaseService.php';
require_once __DIR__ . '/../../../src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Services\DatabaseService;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

/* 🔒 Solo admin */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

$db = DatabaseService::getInstance()->getConnection();

$controller = new UsuarioController();
$usuarios = $controller->obtenerDatosExportacion() ?? [];

/* =========================
   Helpers
========================= */
function esc($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function safeCell($v): string {
    $s = (string)($v ?? '');
    $s = str_replace(["\t", "\r"], ' ', $s);
    $s = trim($s);
    return $s;
}

/**
 * ✅ Reputación por rated_id con tipo='paseador'
 * Devuelve: [promedio(float|null), total(int)]
 */
function reputacionTipoPaseador(PDO $db, int $ratedId): array
{
    $st = $db->prepare("
        SELECT AVG(calificacion) AS promedio, COUNT(*) AS total
        FROM calificaciones
        WHERE rated_id = :id
          AND tipo = 'paseador'
    ");
    $st->execute(['id' => $ratedId]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    $total = (int)($row['total'] ?? 0);
    $prom  = $total > 0 ? (float)($row['promedio'] ?? 0) : null;

    return [$prom, $total];
}

/**
 * ✅ Comentarios (últimos N) para rated_id con tipo='paseador'
 * (solo trae los que tienen comentario)
 */
function comentariosTipoPaseador(PDO $db, int $ratedId, int $limite = 15): array
{
    $limite = max(1, min(50, $limite));

    $sql = "
        SELECT
            c.calificacion,
            c.comentario,
            c.created_at,
            c.paseo_id,
            u.nombre AS rater_nombre,
            u.email  AS rater_email
        FROM calificaciones c
        LEFT JOIN usuarios u ON u.usu_id = c.rater_id
        WHERE c.rated_id = :id
          AND c.tipo = 'paseador'
          AND c.comentario IS NOT NULL
          AND TRIM(c.comentario) <> ''
        ORDER BY c.created_at DESC
        LIMIT {$limite}
    ";

    $st = $db->prepare($sql);
    $st->execute(['id' => $ratedId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* =========================
   Headers Excel (HTML)
========================= */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_usuarios_jaguata_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// BOM UTF-8
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #dee2e6; padding: 6px 8px; vertical-align: top; }
        th { background: #3c6255; color: #fff; text-align: center; }
        .header-title { background:#3c6255; color:#fff; font-size:18px; font-weight:700; text-align:center; padding:10px; }
        .header-date { background:#20c99733; color:#1e5247; text-align:center; padding:6px; font-weight:600; }
        .muted { color:#6c757d; }
        .col-id { width:60px; text-align:center; white-space:nowrap; }
        .col-rol, .col-estado, .col-rep { text-align:center; white-space:nowrap; }
        .col-com { width:520px; }
        tr.fila-par td { background:#f4f6f9; }
    </style>
</head>
<body>

<table style="margin-bottom:12px;">
    <tr><td class="header-title">REPORTE DE USUARIOS – JAGUATA</td></tr>
    <tr><td class="header-date">Generado el <?= date("d/m/Y H:i") ?></td></tr>
</table>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Reputación (tipo=paseador)</th>
            <th>Comentarios (últimos)</th>
            <th>Fecha creación</th>
            <th>Última actualización</th>
        </tr>
    </thead>
    <tbody>

    <?php if (empty($usuarios)): ?>
        <tr><td colspan="9" style="text-align:center" class="muted">Sin usuarios registrados</td></tr>
    <?php else: ?>
        <?php $i = 0; foreach ($usuarios as $u): $i++; ?>
            <?php
            $rowClass = ($i % 2 === 0) ? 'fila-par' : '';

            $id        = (int)($u['usu_id'] ?? 0);
            $nombre    = safeCell($u['nombre'] ?? '');
            $email     = safeCell($u['email'] ?? '');
            $rolRaw    = safeCell($u['rol'] ?? '');
            $rol       = strtolower(trim($rolRaw));
            $estado    = safeCell($u['estado'] ?? 'pendiente');
            $creado    = safeCell($u['created_at'] ?? '');
            $actualiza = safeCell($u['updated_at'] ?? '');

            // ✅ Reputación SIEMPRE: si hay calificaciones tipo paseador para ese ID, se muestra
            [$prom, $total] = ($id > 0) ? reputacionTipoPaseador($db, $id) : [null, 0];

            if ($prom !== null && $total > 0) {
                $repTxt = number_format($prom, 1, ',', '.') . "/5 (" . $total . " opinión" . ($total === 1 ? "" : "es") . ")";
            } else {
                $repTxt = "—";
            }

            // ✅ Comentarios (solo si hay)
            $comTxt = "—";
            if ($id > 0) {
                $comentarios = comentariosTipoPaseador($db, $id, 15);
                if (!empty($comentarios)) {
                    $lines = [];
                    foreach ($comentarios as $c) {
                        $cal = (int)($c['calificacion'] ?? 0);
                        if ($cal < 1) $cal = 1;
                        if ($cal > 5) $cal = 5;

                        $fecha = !empty($c['created_at']) ? date('d/m/Y H:i', strtotime((string)$c['created_at'])) : '';
                        $paseo = safeCell($c['paseo_id'] ?? '');
                        $who   = safeCell($c['rater_nombre'] ?? '');
                        $whoE  = safeCell($c['rater_email'] ?? '');
                        $whoFull = trim($who . ($whoE ? " ({$whoE})" : ""));
                        $coment = safeCell($c['comentario'] ?? '');

                        $meta = [];
                        if ($fecha !== '') $meta[] = $fecha;
                        if ($paseo !== '') $meta[] = "Paseo #{$paseo}";
                        if ($whoFull !== '') $meta[] = $whoFull;

                        $lines[] = "{$cal}/5 - {$coment}" . (empty($meta) ? "" : " | " . implode(" | ", $meta));
                    }
                    // Excel-friendly: <br> suele funcionar en HTML-xls
                    $comTxt = implode("<br>", array_map('esc', $lines));
                }
            }
            ?>
            <tr class="<?= esc($rowClass) ?>">
                <td class="col-id"><?= esc((string)$id) ?></td>
                <td><?= esc($nombre) ?></td>
                <td><?= esc($email) ?></td>
                <td class="col-rol"><?= esc($rolRaw) ?></td>
                <td class="col-estado"><?= esc($estado) ?></td>
                <td class="col-rep"><?= esc($repTxt) ?></td>
                <td class="col-com"><?= $comTxt ?></td>
                <td><?= esc($creado) ?></td>
                <td><?= esc($actualiza) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>

    </tbody>
</table>

</body>
</html>
