<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/PagoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\PagoController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Auth rol dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function parseDate(?string $v): ?string
{
    return $v ? date('Y-m-d', strtotime($v)) : null;
}
function moneyPy(float $v): string
{
    return number_format($v, 0, ',', '.');
}

/* Contexto usuario */
$duenoId = (int)(Session::get('usuario_id') ?? Session::get('id') ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}

$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

/* Parámetros GET */
$from       = parseDate($_GET['from'] ?? null);
$to         = parseDate($_GET['to'] ?? null);
$mascotaId  = $_GET['mascota_id'] ?? null;
$paseadorId = $_GET['paseador_id'] ?? null;
$metodo     = trim((string)($_GET['metodo'] ?? ''));
$estado     = trim((string)($_GET['estado'] ?? ''));
$exportCsv  = (($_GET['export'] ?? '') === 'csv');

/* Datos para filtros */
$paseoController = new PaseoController();
$mascotas   = $paseoController->listarMascotasDeDueno($duenoId);
$paseadores = $paseoController->listarPaseadores();

/* Filtros para consulta */
$filters = [
    'dueno_id'    => $duenoId,
    'from'        => $from,
    'to'          => $to,
    'mascota_id'  => $mascotaId,
    'paseador_id' => $paseadorId,
    'metodo'      => $metodo,
    'estado'      => $estado,
];

/* Consulta de pagos */
$pagoController = new PagoController();
$rows = $pagoController->listarGastosDueno($filters);

/* Total: si se filtra por estado usamos ese, de lo contrario solo CONFIRMADO */
$total = 0.0;
foreach ($rows as $r) {
    if ($estado) {
        $total += (float)$r['monto'];
    } else if (strcasecmp((string)$r['estado'], 'CONFIRMADO') === 0) {
        $total += (float)$r['monto'];
    }
}

/* Export CSV */
if ($exportCsv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="gastos_jaguata_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['ID Pago', 'Fecha pago', 'Monto (PYG)', 'Método', 'Estado', 'Mascota', 'Paseador', 'ID Paseo', 'Fecha paseo', 'Referencia', 'Observación']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['fecha_pago'],
            $r['monto'],
            $r['metodo'],
            $r['estado'],
            $r['mascota'],
            $r['paseador'],
            $r['paseo_id'],
            $r['fecha_paseo'],
            $r['referencia'],
            $r['observacion']
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['TOTAL', '', '₲' . moneyPy($total)]);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        body {
            background-color: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: var(--gris-texto);
            margin: 0
        }

        /* Sidebar (unificada) */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, .2)
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: .2s;
            font-size: .95rem
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px)
        }

        /* Main */
        main {
            margin-left: 250px;
            padding: 2rem
        }

        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: var(--blanco);
            padding: 1.6rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .07)
        }

        .card-header {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            background: var(--verde-jaguata);
            color: #fff;
            font-weight: 600
        }

        .table thead {
            background: var(--verde-jaguata);
            color: #fff
        }

        .table-hover tbody tr:hover {
            background: #eef8f2
        }

        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            border: none;
            color: #fff;
            font-weight: 500
        }

        .btn-gradient:hover {
            opacity: .92
        }

        .border-left-primary {
            border-left: 5px solid var(--verde-jaguata)
        }

        .border-left-success {
            border-left: 5px solid var(--verde-claro)
        }

        .border-left-info {
            border-left: 5px solid #6cb2eb
        }

        footer {
            text-align: center;
            padding: 1rem;
            color: #777;
            font-size: .9rem;
            margin-top: 2rem
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <main>
        <!-- Header -->
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold"><i class="fas fa-coins me-2"></i>Gastos Totales</h1>
                <p>Visualizá y exportá tus pagos realizados en Jaguata.</p>
            </div>
            <a href="?<?= h(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-outline-light fw-semibold">
                <i class="fas fa-file-csv me-1"></i> Exportar CSV
            </a>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-filter me-2"></i>Filtros</div>
            <div class="card-body">
                <form class="row g-3" method="get">
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
                                <option value="<?= h((string)$mid) ?>" <?= ($mascotaId == $mid ? 'selected' : '') ?>>
                                    <?= h($m['nombre'] ?? '#' . $mid) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Paseador</label>
                        <select class="form-select" name="paseador_id">
                            <option value="">Todos</option>
                            <?php foreach ($paseadores as $p): ?>
                                <option value="<?= h((string)$p['id']) ?>" <?= ($paseadorId == $p['id'] ? 'selected' : '') ?>>
                                    <?= h($p['nombre'] ?? '#' . $p['id']) ?>
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
                            <option value="">Todos</option>
                            <option value="PENDIENTE" <?= $estado === 'PENDIENTE'   ? 'selected' : '' ?>>PENDIENTE</option>
                            <option value="CONFIRMADO" <?= $estado === 'CONFIRMADO'  ? 'selected' : '' ?>>CONFIRMADO</option>
                            <option value="RECHAZADO" <?= $estado === 'RECHAZADO'   ? 'selected' : '' ?>>RECHAZADO</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-gradient"><i class="fas fa-search me-1"></i> Aplicar</button>
                        <a href="<?= $baseFeatures ?>/GastosTotales.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Métricas -->
        <div class="row mb-4 text-center">
            <div class="col-md-4">
                <div class="card border-left-primary py-3">
                    <div class="card-body">
                        <div class="fw-bold text-primary small text-uppercase">Total Gastado (PYG)</div>
                        <div class="fs-4 fw-bold text-success mt-1">₲<?= moneyPy($total) ?></div>
                        <small class="text-muted"><?= $estado ? 'Según filtro' : 'Solo CONFIRMADOS' ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-success py-3">
                    <div class="card-body">
                        <div class="fw-bold text-success small text-uppercase">Registros</div>
                        <div class="fs-4 fw-bold"><?= count($rows) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-info py-3">
                    <div class="card-body">
                        <div class="fw-bold text-info small text-uppercase">Rango</div>
                        <div class="fs-6"><?= h(($from ?? '—') . ' a ' . ($to ?? '—')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-header"><i class="fas fa-list me-2"></i>Detalle de Pagos</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
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
                                    $badge = ['CONFIRMADO' => 'success', 'PENDIENTE' => 'warning', 'RECHAZADO' => 'danger'][$st] ?? 'secondary';
                                ?>
                                    <tr>
                                        <td><?= h((string)$r['id']) ?></td>
                                        <td><?= h($r['fecha_pago']) ?></td>
                                        <td class="fw-bold text-success">₲<?= moneyPy((float)$r['monto']) ?></td>
                                        <td><?= h($r['metodo']) ?></td>
                                        <td><span class="badge bg-<?= $badge ?>"><?= h($st) ?></span></td>
                                        <td><?= h($r['mascota']) ?></td>
                                        <td><?= h($r['paseador']) ?></td>
                                        <td><?= h((string)$r['paseo_id']) ?></td>
                                        <td><?= h($r['fecha_paseo']) ?></td>
                                        <td><?= h($r['referencia']) ?></td>
                                        <td><?= h($r['observacion']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($rows)): ?>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td colspan="2">TOTAL (<?= h($estado ?: 'CONFIRMADO') ?>)</td>
                                    <td>₲<?= moneyPy($total) ?></td>
                                    <td colspan="8"></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Dueño</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>