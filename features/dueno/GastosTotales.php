<?php

/**
 * features/dueno/GastosTotales.php
 * Reporte de gastos del dueño
 */

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

// ===== Inicialización =====
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

// ===== Helpers =====
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function parseDate(?string $v): ?string
{
    if (!$v) return null;
    $ts = strtotime($v);
    return $ts ? date('Y-m-d', $ts) : null;
}
function moneyPy(float $v): string
{
    return number_format($v, 0, ',', '.');
}

// ===== ID del dueño autenticado =====
$duenoId = (int)(Session::get('usuario_id') ?? Session::get('id') ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    echo "No autenticado";
    exit;
}

// ===== Filtros =====
$from        = parseDate($_GET['from'] ?? null);
$to          = parseDate($_GET['to'] ?? null);
$mascotaId   = isset($_GET['mascota_id']) ? (int)$_GET['mascota_id'] : null;
$paseadorId  = isset($_GET['paseador_id']) ? (int)$_GET['paseador_id'] : null;
$metodo      = trim((string)($_GET['metodo'] ?? ''));
$estado      = trim((string)($_GET['estado'] ?? ''));
$exportCsv   = isset($_GET['export']) && $_GET['export'] === 'csv';

// ===== Combos =====
$paseoController = new PaseoController();
$mascotas = $paseoController->listarMascotasDeDueno($duenoId);
$paseadores = $paseoController->listarPaseadores();

// ===== Datos =====
$filters = [
    'dueno_id'    => $duenoId,
    'from'        => $from,
    'to'          => $to,
    'mascota_id'  => $mascotaId,
    'paseador_id' => $paseadorId,
    'metodo'      => $metodo,
    'estado'      => $estado,
];

$pagoController = new PagoController();
$rows = $pagoController->listarGastosDueno($filters);
$total = 0.0;

foreach ($rows as $r) {
    if ($estado) {
        $total += (float)$r['monto'];
    } elseif (strcasecmp((string)$r['estado'], 'CONFIRMADO') === 0) {
        $total += (float)$r['monto'];
    }
}

// ===== Export CSV =====
if ($exportCsv) {
    $filename = 'gastos_paseos_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para Excel
    fputcsv($out, ['ID Pago', 'Fecha pago', 'Monto (PYG)', 'Método', 'Estado', 'Mascota', 'Paseador', 'ID Paseo', 'Fecha paseo', 'Referencia', 'Observación']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'] ?? '',
            $r['fecha_pago'] ?? '',
            $r['monto'] ?? '',
            $r['metodo'] ?? '',
            $r['estado'] ?? '',
            $r['mascota'] ?? '',
            $r['paseador'] ?? '',
            $r['paseo_id'] ?? '',
            $r['fecha_paseo'] ?? '',
            $r['referencia'] ?? '',
            $r['observacion'] ?? '',
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['TOTAL', '', (string)$total, '', '', '', '', '', '', '', '']);
    fclose($out);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos Totales - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">

            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column gap-1">
                        <!-- Mi Perfil -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuPerfil" aria-expanded="false">
                                <i class="fas fa-user me-2"></i>
                                <span class="flex-grow-1">Mi Perfil</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuPerfil">
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php">
                                        <i class="fas fa-id-card me-2"></i> Ver Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">
                                        <i class="fas fa-user-edit me-2 text-warning"></i> Editar Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php">
                                        <i class="fas fa-coins me-2 text-success"></i> Gastos Totales
                                    </a>
                                </li>
                            </ul>
                        </li>




                        <!-- Mascotas -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuMascotas" aria-expanded="false">
                                <i class="fas fa-paw me-2"></i>
                                <span class="flex-grow-1">Mascotas</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuMascotas">
                                <li class="nav-item">
                                    <a class="nav-link" href="MisMascotas.php">
                                        <i class="fas fa-list-ul me-2"></i> Mis Mascotas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="AgregarMascota.php">
                                        <i class="fas fa-plus-circle me-2"></i> Agregar Mascota
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $firstMascotaId ? '' : 'disabled' ?>"
                                        href="<?= $firstMascotaId ? 'PerfilMascota.php?id=' . (int)$firstMascotaId : '#' ?>">
                                        <i class="fas fa-id-badge me-2"></i> Perfil de mi Mascota
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Paseos -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuPaseos" aria-expanded="false">
                                <i class="fas fa-walking me-2"></i>
                                <span class="flex-grow-1">Paseos</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuPaseos">
                                <li class="nav-item">
                                    <a class="nav-link" href="BuscarPaseadores.php">
                                        <i class="fas fa-search me-2"></i> Buscar Paseadores
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link d-flex align-items-center w-100 text-start"
                                        data-bs-toggle="collapse" data-bs-target="#menuMisPaseos" aria-expanded="false">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <span class="flex-grow-1">Mis Paseos</span>
                                        <i class="fas fa-chevron-right ms-2 chevron"></i>
                                    </button>
                                    <ul class="collapse ps-4 nav flex-column" id="menuMisPaseos">
                                        <li class="nav-item"><a class="nav-link" href="PaseosCompletados.php"><i class="fas fa-check-circle me-2"></i> Completados</a></li>
                                        <li class="nav-item"><a class="nav-link" href="PaseosPendientes.php"><i class="fas fa-hourglass-half me-2"></i> Pendientes</a></li>
                                        <li class="nav-item"><a class="nav-link" href="PaseosCancelados.php"><i class="fas fa-times-circle me-2"></i> Cancelados</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="SolicitarPaseo.php">
                                        <i class="fas fa-plus-circle me-2"></i> Solicitar Nuevo Paseo
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Pagos -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuPagos" aria-expanded="false">
                                <i class="fas fa-credit-card me-2"></i>
                                <span class="flex-grow-1">Pagos</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuPagos">
                                <li class="nav-item">
                                    <!-- Enviar a Pendientes (allí hay botón Pagar con paseo_id) -->
                                    <a class="nav-link" href="PaseosPendientes.php">
                                        <i class="fas fa-wallet me-2"></i> Pagar paseo
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Notificaciones -->
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" href="Notificaciones.php">
                                <i class="fas fa-bell me-2"></i>
                                <span>Notificaciones</span>
                            </a>
                        </li>

                        <!-- Configuración (solo Editar Perfil y Cerrar Sesión) -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuConfig" aria-expanded="false">
                                <i class="fas fa-gear me-2"></i>
                                <span class="flex-grow-1">Configuración</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuConfig">
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">
                                        <i class="fas fa-user-cog me-2"></i> Editar Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </li>

                    </ul>
                </div>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gastos Totales</h1>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline-success">
                        <i class="fas fa-file-csv me-1"></i> Exportar CSV
                    </a>
                </div>

                <!-- Filtros -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </div>
                    <div class="card-body">
                        <form class="row g-3" method="get">
                            <div class="col-md-3">
                                <label class="form-label">Desde</label>
                                <input type="date" class="form-control" name="from" value="<?= h($from ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hasta</label>
                                <input type="date" class="form-control" name="to" value="<?= h($to ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mascota</label>
                                <select class="form-select" name="mascota_id">
                                    <option value="">Todas</option>
                                    <?php foreach ($mascotas as $m): ?>
                                        <option value="<?= (int)$m['id'] ?>" <?= $mascotaId === (int)$m['id'] ? 'selected' : '' ?>>
                                            <?= h($m['nombre'] ?? ('#' . $m['id'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Paseador</label>
                                <select class="form-select" name="paseador_id">
                                    <option value="">Todos</option>
                                    <?php foreach ($paseadores as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>" <?= $paseadorId === (int)$p['id'] ? 'selected' : '' ?>>
                                            <?= h($p['nombre'] ?? ('#' . $p['id'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Método</label>
                                <select class="form-select" name="metodo">
                                    <option value="">Todos</option>
                                    <?php foreach (['EFECTIVO', 'TRANSFERENCIA'] as $opt): ?>
                                        <option value="<?= $opt ?>" <?= ($metodo === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado">
                                    <option value="">Todos</option>
                                    <?php foreach (['PENDIENTE', 'CONFIRMADO', 'RECHAZADO'] as $opt): ?>
                                        <option value="<?= $opt ?>" <?= ($estado === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i>Aplicar</button>
                                <a href="GastosTotales.php" class="btn btn-outline-secondary"><i class="fas fa-undo me-1"></i>Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Gastado (PYG)</div>
                                <div class="h4 mb-0 fw-bold">₲<?= moneyPy((float)$total) ?></div>
                                <small class="text-color #ffff"><?= $estado ? 'Según filtro' : 'Solo pagos confirmados' ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Registros</div>
                                <div class="h4 mb-0 fw-bold"><?= count($rows) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">Rango</div>
                                <div class="h6 mb-0"><?= h(($from ?? '—') . ' a ' . ($to ?? '—')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-list me-2"></i>Detalle de Pagos
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
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
                                            <td colspan="11" class="text-center text-color #ffff py-4">No hay registros</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rows as $r): ?>
                                            <?php
                                            $st = strtoupper((string)($r['estado'] ?? ''));
                                            $badge = [
                                                'CONFIRMADO' => 'success',
                                                'PENDIENTE'  => 'warning',
                                                'RECHAZADO'  => 'danger'
                                            ][$st] ?? 'secondary';
                                            ?>
                                            <tr>
                                                <td><?= (int)($r['id'] ?? 0) ?></td>
                                                <td><?= h($r['fecha_pago'] ?? '') ?></td>
                                                <td><strong>₲<?= moneyPy((float)($r['monto'] ?? 0)) ?></strong></td>
                                                <td><?= h($r['metodo'] ?? '') ?></td>
                                                <td><span class="badge bg-<?= $badge ?>"><?= h($st ?: '—') ?></span></td>
                                                <td><?= h($r['mascota'] ?? '') ?></td>
                                                <td><?= h($r['paseador'] ?? '') ?></td>
                                                <td><?= (int)($r['paseo_id'] ?? 0) ?></td>
                                                <td><?= h($r['fecha_paseo'] ?? '') ?></td>
                                                <td><?= h($r['referencia'] ?? '') ?></td>
                                                <td><?= h($r['observacion'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if ($rows): ?>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="2">TOTAL (<?= $estado ? h($estado) : 'CONFIRMADO' ?>)</th>
                                            <th>₲<?= moneyPy((float)$total) ?></th>
                                            <th colspan="8"></th>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>