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
$auth = new AuthController();
$auth->checkRole('dueno');

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

$duenoId = (int)(Session::get('usuario_id') ?? Session::get('id') ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}

$from = parseDate($_GET['from'] ?? null);
$to = parseDate($_GET['to'] ?? null);
$mascotaId = $_GET['mascota_id'] ?? null;
$paseadorId = $_GET['paseador_id'] ?? null;
$metodo = trim((string)($_GET['metodo'] ?? ''));
$estado = trim((string)($_GET['estado'] ?? ''));
$exportCsv = ($_GET['export'] ?? '') === 'csv';

$paseoController = new PaseoController();
$mascotas = $paseoController->listarMascotasDeDueno($duenoId);
$paseadores = $paseoController->listarPaseadores();

$filters = [
    'dueno_id' => $duenoId,
    'from' => $from,
    'to' => $to,
    'mascota_id' => $mascotaId,
    'paseador_id' => $paseadorId,
    'metodo' => $metodo,
    'estado' => $estado,
];

$pagoController = new PagoController();
$rows = $pagoController->listarGastosDueno($filters);

$total = 0;
foreach ($rows as $r) {
    if ($estado) $total += (float)$r['monto'];
    elseif (strcasecmp((string)$r['estado'], 'CONFIRMADO') === 0) $total += (float)$r['monto'];
}

if ($exportCsv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="gastos_jaguata_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['ID Pago', 'Fecha pago', 'Monto (PYG)', 'Método', 'Estado', 'Mascota', 'Paseador', 'ID Paseo', 'Fecha paseo', 'Referencia', 'Observación']);
    foreach ($rows as $r)
        fputcsv($out, [$r['id'], $r['fecha_pago'], $r['monto'], $r['metodo'], $r['estado'], $r['mascota'], $r['paseador'], $r['paseo_id'], $r['fecha_paseo'], $r['referencia'], $r['observacion']]);
    fputcsv($out, []);
    fputcsv($out, ['TOTAL', '', '₲' . moneyPy($total)]);
    fclose($out);
    exit;
}

$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
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
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            min-height: 100vh;
            padding-top: 1rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar .nav-link {
            color: #ddd;
            border-radius: 8px;
            padding: 10px 16px;
            margin: 4px 8px;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all .2s ease;
        }

        .sidebar .nav-link:hover {
            background: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        main {
            background: #f5f7fa;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-weight: 600;
            margin: 0;
        }

        .page-header .btn-light {
            background: #fff;
            color: #3c6255;
        }

        .page-header .btn-light:hover {
            background: #3c6255;
            color: #fff;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(90deg, #3c6255, #20c997);
            color: #fff;
            font-weight: 600;
        }

        .table thead th {
            background: #3c6255;
            color: #fff;
            border: none;
        }

        .table-hover tbody tr:hover {
            background: #e6f4ea;
        }

        .btn-outline-success {
            border-color: #3c6255;
            color: #3c6255;
        }

        .btn-outline-success:hover {
            background: #3c6255;
            color: #fff;
        }

        .btn-outline-secondary {
            border-color: #20c997;
            color: #20c997;
        }

        .btn-outline-secondary:hover {
            background: #20c997;
            color: #fff;
        }

        .border-left-primary {
            border-left: 5px solid #3c6255;
        }

        .border-left-success {
            border-left: 5px solid #20c997;
        }

        .border-left-info {
            border-left: 5px solid #6cb2eb;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home me-2"></i>Inicio</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw me-2"></i>Mis Mascotas</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-walking me-2"></i>Paseos</a></li>
                        <li><a class="nav-link active" href="#"><i class="fas fa-wallet me-2"></i>Gastos</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell me-2"></i>Notificaciones</a></li>
                        <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Salir</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main -->

            <div class="page-header">
                <h1><i class="fas fa-coins me-2"></i>Gastos Totales</h1>
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-file-csv me-1"></i> Exportar CSV
                </a>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-filter me-2"></i>Filtros</div>
                <div class="card-body">
                    <form class="row g-3" method="get">
                        <div class="col-md-3"><label class="form-label">Desde</label>
                            <input type="date" class="form-control" name="from" value="<?= h($from) ?>">
                        </div>
                        <div class="col-md-3"><label class="form-label">Hasta</label>
                            <input type="date" class="form-control" name="to" value="<?= h($to) ?>">
                        </div>
                        <div class="col-md-3"><label class="form-label">Mascota</label>
                            <select class="form-select" name="mascota_id">
                                <option value="">Todas</option>
                                <?php foreach ($mascotas as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= ($mascotaId == $m['id']) ? 'selected' : '' ?>><?= h($m['nombre'] ?? '#' . $m['id']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Paseador</label>
                            <select class="form-select" name="paseador_id">
                                <option value="">Todos</option>
                                <?php foreach ($paseadores as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($paseadorId == $p['id']) ? 'selected' : '' ?>><?= h($p['nombre'] ?? '#' . $p['id']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Método</label>
                            <select class="form-select" name="metodo">
                                <option value="">Todos</option>
                                <option value="EFECTIVO" <?= $metodo === 'EFECTIVO' ? 'selected' : '' ?>>EFECTIVO</option>
                                <option value="TRANSFERENCIA" <?= $metodo === 'TRANSFERENCIA' ? 'selected' : '' ?>>TRANSFERENCIA</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="">Todos</option>
                                <option value="PENDIENTE" <?= $estado === 'PENDIENTE' ? 'selected' : '' ?>>PENDIENTE</option>
                                <option value="CONFIRMADO" <?= $estado === 'CONFIRMADO' ? 'selected' : '' ?>>CONFIRMADO</option>
                                <option value="RECHAZADO" <?= $estado === 'RECHAZADO' ? 'selected' : '' ?>>RECHAZADO</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-outline-success"><i class="fas fa-search me-1"></i> Aplicar</button>
                            <a href="GastosTotales.php" class="btn btn-outline-secondary"><i class="fas fa-undo me-1"></i> Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4 text-center">
                <div class="col-md-4">
                    <div class="card border-left-primary py-3">
                        <div class="card-body">
                            <div class="fw-bold text-primary small text-uppercase">Total Gastado (PYG)</div>
                            <div class="fs-4 fw-bold text-success mt-1">₲<?= moneyPy($total) ?></div>
                            <small class="text-muted"><?= $estado ? 'Según filtro' : 'Solo confirmados' ?></small>
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
                                    <?php else: foreach ($rows as $r):
                                        $st = strtoupper((string)($r['estado'] ?? ''));
                                        $badge = ['CONFIRMADO' => 'success', 'PENDIENTE' => 'warning', 'RECHAZADO' => 'danger'][$st] ?? 'secondary';
                                    ?>
                                        <tr>
                                            <td><?= $r['id'] ?></td>
                                            <td><?= h($r['fecha_pago']) ?></td>
                                            <td class="fw-bold text-success">₲<?= moneyPy((float)$r['monto']) ?></td>
                                            <td><?= h($r['metodo']) ?></td>
                                            <td><span class="badge bg-<?= $badge ?>"><?= $st ?></span></td>
                                            <td><?= h($r['mascota']) ?></td>
                                            <td><?= h($r['paseador']) ?></td>
                                            <td><?= $r['paseo_id'] ?></td>
                                            <td><?= h($r['fecha_paseo']) ?></td>
                                            <td><?= h($r['referencia']) ?></td>
                                            <td><?= h($r['observacion']) ?></td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                            <?php if ($rows): ?>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="2">TOTAL (<?= $estado ?: 'CONFIRMADO' ?>)</td>
                                        <td>₲<?= moneyPy($total) ?></td>
                                        <td colspan="8"></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>