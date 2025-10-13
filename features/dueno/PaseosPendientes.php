<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();

// Seguridad
$auth = new AuthController();
$auth->checkRole('dueno');

// URL de retorno segura
$rol = Session::getUsuarioRol() ?: 'dueno';
$defaultBack = BASE_URL . "/features/{$rol}/Dashboard.php";
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$backUrl = (is_string($referer) && str_starts_with($referer, BASE_URL)) ? $referer : $defaultBack;

// Datos base
$paseoController   = new PaseoController();
$mascotaController = new MascotaController();

// Traer TODO (con relaciones) y luego filtramos por mascotas del dueño
$allPaseos = $paseoController->index();   // SELECT p.*, nombre_paseador, nombre_mascota...
$mascotas  = $mascotaController->index(); // solo mascotas del dueño logueado

// ---- Helpers de presentación ----
function fmtDate($v, string $format = 'd/m/Y H:i'): string
{
    if ($v === null || $v === '') return '-';
    $ts = is_numeric($v) ? (int)$v : (is_string($v) ? strtotime($v) : null);
    return $ts ? date($format, $ts) : '-';
}
function fmtInt($v, string $suffix = ' min'): string
{
    return ($v !== null && $v !== '') ? ((int)$v) . $suffix : '-';
}
function fmtGs($v): string
{
    $n = ($v === null || $v === '') ? 0 : (float)$v;
    return '₲' . number_format($n, 0, ',', '.');
}
function estadoToBadge(string $estadoRaw): array
{
    $lc = mb_strtolower($estadoRaw);
    // Normalizo a: Solicitado / Confirmado / Completo / Cancelado
    if (in_array($lc, ['pendiente', 'solicitado'], true)) return ['warning', 'Solicitado'];
    if ($lc === 'confirmado')                                return ['info', 'Confirmado'];
    if (in_array($lc, ['completo', 'completado'], true))     return ['success', 'Completo'];
    if ($lc === 'cancelado')                                 return ['danger', 'Cancelado'];
    return ['secondary', ucfirst($estadoRaw ?: '-')];
}

// ---- Filtros UI ----
$q     = trim((string)($_GET['q'] ?? ''));
$desde = $_GET['desde'] ?? null; // YYYY-MM-DD
$hasta = $_GET['hasta'] ?? null;

$normalizeTs = function (?string $d, bool $end = false): ?int {
    if (!$d) return null;
    $dt = DateTime::createFromFormat('Y-m-d', $d) ?: (strtotime($d) ? new DateTime($d) : null);
    if (!$dt) return null;
    $dt->setTime($end ? 23 : 0, $end ? 59 : 0, $end ? 59 : 0);
    return $dt->getTimestamp();
};
$tsDesde = $normalizeTs($desde, false);
$tsHasta = $normalizeTs($hasta, true);

// ---- Filtrar SOLO paseos de las mascotas del dueño ----
$idsMascotasDueno = [];
foreach ($mascotas as $m) {
    $mid = $m['mascota_id'] ?? $m['id'] ?? $m['id_mascota'] ?? null;
    if ($mid !== null) $idsMascotasDueno[(int)$mid] = true;
}

$soloDueno = array_values(array_filter($allPaseos, function ($p) use ($idsMascotasDueno) {
    $mid = $p['mascota_id'] ?? $p['id_mascota'] ?? $p['idMascota'] ?? null;
    return $mid !== null && isset($idsMascotasDueno[(int)$mid]);
}));

// ---- Filtrar por estado Pendiente/Confirmado + filtros de fecha y texto ----
$paseos = array_values(array_filter($soloDueno, function (array $p) use ($q, $tsDesde, $tsHasta) {
    $estado = (string)($p['estado'] ?? '');
    if (!in_array(mb_strtolower($estado), ['pendiente', 'solicitado', 'confirmado'], true)) return false;

    $iniTs = null;
    if (isset($p['inicio']) && $p['inicio'] !== '') {
        $iniTs = is_numeric($p['inicio']) ? (int)$p['inicio'] : (strtotime((string)$p['inicio']) ?: null);
    }
    if ($tsDesde && $iniTs && $iniTs < $tsDesde) return false;
    if ($tsHasta && $iniTs && $iniTs > $tsHasta) return false;

    if ($q !== '') {
        foreach (['nombre_mascota', 'nombre_paseador', 'comentario', 'estado', 'direccion_origen', 'direccion_destino'] as $k) {
            if (!empty($p[$k]) && stripos((string)$p[$k], $q) !== false) return true;
        }
        return false;
    }
    return true;
}));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Paseos Pendientes - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-12 col-lg-12 px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-outline-secondary btn-sm"
                            onclick="event.preventDefault(); if (history.length > 1) { history.back(); } else { window.location.href='<?= htmlspecialchars($backUrl) ?>'; }">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <h1 class="h2 mb-0"><i class="fas fa-hourglass-half me-2"></i>Paseos Pendientes</h1>
                    </div>
                    <a class="btn btn-outline-secondary btn-sm" href="ExportarDashboard.php?estado=Pendiente">
                        <i class="fas fa-download me-1"></i> Exportar
                    </a>
                </div>

                <!-- Filtros -->
                <form class="row g-2 mb-3" method="get">
                    <div class="col-sm-3">
                        <label class="form-label">Desde</label>
                        <input type="date" name="desde" value="<?= htmlspecialchars((string)$desde) ?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="hasta" value="<?= htmlspecialchars((string)$hasta) ?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Mascota, paseador, dirección…" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-2 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm w-100"><i class="fas fa-search me-1"></i> Filtrar</button>
                    </div>
                </form>

                <div class="card shadow">
                    <div class="card-body">
                        <?php if (empty($paseos)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-walking fa-2x mb-3"></i>
                                <p>No hay paseos pendientes con los filtros aplicados.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0" style="white-space:nowrap;">
                                    <thead class="table-dark">
                                        <tr class="text-center">
                                            <th style="width:18%">Mascota</th>
                                            <th style="width:20%">Paseador</th>
                                            <th style="width:14%">Inicio</th>
                                            <th style="width:14%">Fin</th>
                                            <th style="width:10%">Duración</th>
                                            <th style="width:12%">Precio</th>
                                            <th style="width:10%">Estado</th>
                                            <th style="width:12%" class="text-end">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paseos as $p): ?>
                                            <?php
                                            $inicio   = fmtDate($p['inicio'] ?? null);
                                            $fin      = fmtDate($p['fin'] ?? null);
                                            $durMin   = $p['duracion_min'] ?? $p['duracion'] ?? null;
                                            $duracion = fmtInt($durMin);
                                            $precio   = fmtGs($p['precio_total'] ?? null);
                                            [$badgeCls, $estadoTxt] = estadoToBadge((string)($p['estado'] ?? ''));

                                            // Habilitamos pagar si es pendiente/solicitado/confirmado y tiene paseo_id
                                            $puedePagar = in_array(mb_strtolower((string)($p['estado'] ?? '')), ['pendiente', 'solicitado', 'confirmado'], true)
                                                && !empty($p['paseo_id']);
                                            ?>
                                            <tr>
                                                <td class="text-truncate" style="max-width:180px;"><?= htmlspecialchars((string)($p['nombre_mascota'] ?? '-')) ?></td>
                                                <td class="text-truncate" style="max-width:220px;"><?= htmlspecialchars((string)($p['nombre_paseador'] ?? '-')) ?></td>
                                                <td class="text-center"><?= $inicio ?></td>
                                                <td class="text-center"><?= $fin ?></td>
                                                <td class="text-center"><?= $duracion ?></td>
                                                <td class="text-end"><?= $precio ?></td>
                                                <td class="text-center"><span class="badge bg-<?= $badgeCls ?>"><?= $estadoTxt ?></span></td>
                                                <td class="text-end">
                                                    <?php if ($puedePagar): ?>
                                                        <a href="pago_paseo_dueno.php?paseo_id=<?= (int)$p['paseo_id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-wallet me-1"></i> Pagar
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>