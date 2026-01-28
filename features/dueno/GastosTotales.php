<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * ✅ ROOT robusto: sube carpetas hasta encontrar /src/Config/AppConfig.php
 * (Funciona aunque muevas el archivo de lugar)
 */
$dir = __DIR__;
$ROOT = null;
for ($i = 0; $i < 10; $i++) {
    if (file_exists($dir . '/src/Config/AppConfig.php')) {
        $ROOT = realpath($dir);
        break;
    }
    $parent = dirname($dir);
    if ($parent === $dir) break;
    $dir = $parent;
}
if (!$ROOT) {
    http_response_code(500);
    die('❌ No se encontró el ROOT del proyecto (src/Config/AppConfig.php).');
}

/** ✅ Cargar dependencias */
require_once $ROOT . '/src/Config/AppConfig.php';
require_once $ROOT . '/src/Controllers/AuthController.php';
require_once $ROOT . '/src/Controllers/PaseoController.php';
require_once $ROOT . '/src/Controllers/PagoController.php';
require_once $ROOT . '/src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\PagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('dueno');

/* Helpers */
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function parseDate(?string $v): ?string
{
    $v = $v ? trim($v) : '';
    if ($v === '') return null;
    $t = strtotime($v);
    if ($t === false) return null;
    return date('Y-m-d', $t);
}
function moneyPy(float $v): string
{
    return number_format($v, 0, ',', '.');
}

/* Contexto usuario */
$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}

$rolMenu       = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = h(Session::getUsuarioNombre() ?? 'Dueño');

/* Parámetros GET */
$from       = parseDate($_GET['from'] ?? null);
$to         = parseDate($_GET['to'] ?? null);
$mascotaId  = (string)($_GET['mascota_id'] ?? '');
$paseadorId = (string)($_GET['paseador_id'] ?? '');
$metodo     = strtoupper(trim((string)($_GET['metodo'] ?? '')));
$estado     = strtoupper(trim((string)($_GET['estado'] ?? '')));
$exportCsv  = ((string)($_GET['export'] ?? '') === 'csv');

/* Datos para filtros */
$paseoController = new PaseoController();
$mascotas        = $paseoController->listarMascotasDeDueno($duenoId) ?? [];
$paseadores      = $paseoController->listarPaseadores() ?? [];

/* Filtros para consulta */
$filters = [
    'dueno_id'    => $duenoId,
    'from'        => $from,
    'to'          => $to,
    'mascota_id'  => ($mascotaId !== '' ? $mascotaId : null),
    'paseador_id' => ($paseadorId !== '' ? $paseadorId : null),
    'metodo'      => ($metodo !== '' ? $metodo : null),
    'estado'      => ($estado !== '' ? $estado : null),
];

/* Consulta de pagos */
$pagoController = new PagoController();
$rows           = $pagoController->listarGastosDueno($filters) ?? [];

/* Total */
$total = 0.0;
foreach ($rows as $r) {
    $total += (float)($r['monto'] ?? 0);
}

/**
 * ✅ EXPORT CSV REAL (descarga)
 * URL: GastosTotales.php?....&export=csv
 */
if ($exportCsv) {
    $filename = 'gastos_jaguata_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM UTF-8 (Excel lo abre bien)
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // Encabezados
    fputcsv($out, [
        'ID', 'Fecha pago', 'Monto', 'Método', 'Estado',
        'Mascota', 'Paseador', 'ID Paseo', 'Fecha paseo',
        'Referencia', 'Observación'
    ], ';');

    foreach ($rows as $r) {
        fputcsv($out, [
            (string)($r['id'] ?? ''),
            (string)($r['fecha_pago'] ?? ''),
            (string)($r['monto'] ?? ''),
            (string)($r['metodo'] ?? ''),
            (string)($r['estado'] ?? ''),
            (string)($r['mascota'] ?? ''),
            (string)($r['paseador'] ?? ''),
            (string)($r['paseo_id'] ?? ''),
            (string)($r['fecha_paseo'] ?? ''),
            (string)($r['referencia'] ?? ''),
            (string)($r['observacion'] ?? ''),
        ], ';');
    }

    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gastos Totales - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS base -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Tema global -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height: 100%; }
        body { background: var(--gris-fondo, #f4f6f9); }
        main.main-content { margin-left: 260px; min-height: 100vh; padding: 24px; }
        @media (max-width: 768px) { main.main-content { margin-left: 0; padding: 16px; } }

        .dash-card{
            background:#fff;border-radius:18px;padding:18px 20px;
            box-shadow:0 12px 30px rgba(0,0,0,.06);
            text-align:center;display:flex;flex-direction:column;justify-content:center;gap:6px;height:100%;
        }
        .dash-card-icon{ font-size:2rem;margin-bottom:6px; }
        .dash-card-value{ font-size:1.4rem;font-weight:700;color:#222; }
        .dash-card-label{ font-size:.9rem;font-weight:400;color:#555;line-height:1.2; }

        .icon-blue{ color:#0d6efd; }
        .icon-green{ color:var(--verde-jaguata, #3c6255); }
        .icon-yellow{ color:#ffc107; }
        .icon-red{ color:#dc3545; }

        /* ===== Botón Exportar centrado ===== */
/* ===== Botón Exportar alineado a la izquierda ===== */
.text-export-left{
  display: flex;
  justify-content: flex-start; /* 👈 izquierda */
  margin: 16px 0 8px;
}



    </style>
</head>

<body class="page-gastos">

    <!-- Sidebar Dueño -->
    <?php include $ROOT . '/src/Templates/SidebarDueno.php'; ?>

    <main class="main-content">
        <div class="py-2">

            <!-- Header -->
            <div class="header-box header-pagos mb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">
                        <i class="fas fa-coins me-2"></i>Gastos Totales
                    </h1>
                    <p class="mb-0">
                        Hola, <?= $usuarioNombre; ?>. Visualizá y exportá tus pagos realizados en Jaguata.
                    </p>
                </div>

                <div class="d-none d-md-flex gap-2">
                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>

                  
                </div>
            </div>

            <!-- Botones mobile -->
            <div class="d-md-none d-flex gap-2 mb-3">
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-secondary btn-sm w-50">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <a href="<?= BASE_URL ?>/public/api/pagos/reporte_gastos_dueno.php?<?= h(http_build_query($_GET)) ?>"
                   class="btn btn-success btn-sm w-50">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-wallet dash-card-icon icon-green"></i>
                        <div class="dash-card-value">₲<?= moneyPy($total) ?></div>
                        <div class="dash-card-label">
                            <?= $estado ? 'Según filtro de estado' : 'Gastos confirmados' ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-file-invoice-dollar dash-card-icon icon-blue"></i>
                        <div class="dash-card-value"><?= (int)count($rows) ?></div>
                        <div class="dash-card-label">Pagos registrados</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-calendar-alt dash-card-icon icon-yellow"></i>
                        <div class="dash-card-value"><?= h($from ?? '—') ?></div>
                        <div class="dash-card-label">Fecha desde</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-calendar-check dash-card-icon icon-red"></i>
                        <div class="dash-card-value"><?= h($to ?? '—') ?></div>
                        <div class="dash-card-label">Fecha hasta</div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros">
                <form class="row g-3 align-items-end" method="get">
                    <div class="col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" class="form-control" name="from" value="<?= h($from) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" class="form-control" name="to" value="<?= h($to) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Mascota</label>
                        <select class="form-select" name="mascota_id">
                            <option value="">Todas</option>
                            <?php foreach ($mascotas as $m): ?>
                                <?php $mid = $m['id'] ?? $m['mascota_id'] ?? null; ?>
                                <option value="<?= h((string)$mid) ?>" <?= ((string)$mascotaId === (string)$mid ? 'selected' : '') ?>>
                                    <?= h($m['nombre'] ?? ('#' . $mid)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Paseador</label>
                        <select class="form-select" name="paseador_id">
                            <option value="">Todos</option>
                            <?php foreach ($paseadores as $p): ?>
                                <?php $pid = $p['id'] ?? $p['usu_id'] ?? null; ?>
                                <option value="<?= h((string)$pid) ?>" <?= ((string)$paseadorId === (string)$pid ? 'selected' : '') ?>>
                                    <?= h($p['nombre'] ?? ('#' . $pid)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Método</label>
                        <select class="form-select" name="metodo">
                            <option value="">Todos</option>
                            <option value="EFECTIVO" <?= $metodo === 'EFECTIVO' ? 'selected' : '' ?>>EFECTIVO</option>
                            <option value="TRANSFERENCIA" <?= $metodo === 'TRANSFERENCIA' ? 'selected' : '' ?>>TRANSFERENCIA</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="">(Por defecto: Confirmados)</option>
                            <option value="PENDIENTE" <?= $estado === 'PENDIENTE'  ? 'selected' : '' ?>>PENDIENTE</option>
                            <option value="CONFIRMADO" <?= $estado === 'CONFIRMADO' ? 'selected' : '' ?>>CONFIRMADO</option>
                            <option value="RECHAZADO" <?= $estado === 'RECHAZADO'  ? 'selected' : '' ?>>RECHAZADO</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-accion btn-activar">
                            <i class="fas fa-search me-1"></i> Aplicar
                        </button>

                        <a href="<?= $baseFeatures; ?>/GastosTotales.php" class="btn btn-accion btn-desactivar">
                            <i class="fas fa-undo me-1"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
<!-- Botón Exportar Excel -->
<!-- Botón Exportar Excel (izquierda) -->
<div class="text-export-left">
    <a href="<?= BASE_URL ?>/public/api/pagos/reporte_gastos_dueno.php?<?= h(http_build_query($_GET)) ?>"
       class="btn btn-success btn-sm fw-semibold">
        <i class="fas fa-file-excel me-1"></i> Exportar Excel
    </a>
</div>



            <!-- Tabla -->
            <div class="section-card mt-4">
                <div class="section-header">
                    <i class="fas fa-list me-2"></i>Detalle de Pagos
                </div>
                <div class="section-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha pago</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Estado</th>
                                    <th>Mascota</th>
                                    <th>Paseador</th>
                                    <th>ID Paseo</th>
                                    <th>Fecha paseo</th>
                                    <th>Referencia</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="11" class="text-muted py-4">No hay registros disponibles</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r):
                                        $st = strtoupper((string)($r['estado'] ?? ''));
                                        $badge = [
                                            'CONFIRMADO' => 'success',
                                            'PENDIENTE'  => 'warning',
                                            'RECHAZADO'  => 'danger',
                                        ][$st] ?? 'secondary';
                                    ?>
                                        <tr>
                                            <td><?= h((string)($r['id'] ?? '')) ?></td>
                                            <td><?= h((string)($r['fecha_pago'] ?? '')) ?></td>
                                            <td class="fw-bold text-success">₲<?= moneyPy((float)($r['monto'] ?? 0)) ?></td>
                                            <td><?= h((string)($r['metodo'] ?? '')) ?></td>
                                            <td><span class="badge bg-<?= $badge ?>"><?= h($st) ?></span></td>
                                            <td><?= h((string)($r['mascota'] ?? '—')) ?></td>
                                            <td><?= h((string)($r['paseador'] ?? '—')) ?></td>
                                            <td><?= h((string)($r['paseo_id'] ?? '')) ?></td>
                                            <td><?= h((string)($r['fecha_paseo'] ?? '')) ?></td>
                                            <td><?= h((string)($r['referencia'] ?? '')) ?></td>
                                            <td><?= h((string)($r['observacion'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>

                            <?php if (!empty($rows)): ?>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="2">TOTAL</td>
                                        <td>₲<?= moneyPy($total) ?></td>
                                        <td colspan="8"></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Dueño
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
