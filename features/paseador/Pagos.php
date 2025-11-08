<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// === Inicialización y seguridad ===
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

// === Variables base ===
$rolMenu = 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$paseadorId = Session::getUsuarioId();

// === Controlador ===
$paseoController = new PaseoController();

// === Obtener paseos reales del paseador logueado ===
$paseos = $paseoController->indexForPaseador($paseadorId);

// === Filtrar por pagos realizados o pendientes ===
$pagos = [];
foreach ($paseos as $p) {
    $pagos[] = [
        'id' => $p['paseo_id'] ?? 0,
        'paseo' => 'Paseo de ' . ($p['nombre_mascota'] ?? 'Mascota'),
        'monto' => (float)($p['precio_total'] ?? 0),
        'fecha' => $p['inicio'] ?? null,
        'estado' => strtolower($p['estado_pago'] ?? 'pendiente')
    ];
}

// === Totales ===
$totalPagado = array_sum(array_map(fn($p) => $p['estado'] === 'pagado' ? (float)$p['monto'] : 0, $pagos));
$totalPendiente = array_sum(array_map(fn($p) => $p['estado'] === 'pendiente' ? (float)$p['monto'] : 0, $pagos));
$totalPaseos = count($pagos);

// === Filtro por fecha (para ganancias) ===
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';

$paseosCompletados = array_filter($paseos, function ($p) use ($fechaInicio, $fechaFin) {
    $estado = strtolower($p['estado'] ?? '');
    $fecha = isset($p['inicio']) ? date('Y-m-d', strtotime($p['inicio'])) : null;
    if (!$fecha || !in_array($estado, ['completo', 'finalizado'])) return false;
    if ($fechaInicio && $fecha < $fechaInicio) return false;
    if ($fechaFin && $fecha > $fechaFin) return false;
    return true;
});

// === Calcular ganancias totales ===
$gananciasTotales = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $paseosCompletados));

// === Exportar CSV ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ganancias_paseador.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Mascota', 'Fecha', 'Monto (₲)']);
    foreach ($paseosCompletados as $p) {
        fputcsv($output, [
            $p['nombre_mascota'] ?? '-',
            date('d/m/Y H:i', strtotime($p['inicio'] ?? '')),
            (float)($p['precio_total'] ?? 0)
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos y Ganancias - Paseador | Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            color: #333;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            transition: background 0.2s, transform 0.2s;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        main.content {
            margin-left: 240px;
            padding: 2rem 2.5rem;
            background-color: #f5f7fa;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 600;
            font-size: 1.3rem;
        }

        .table thead {
            background: #3c6255;
            color: #fff;
        }

        .badge {
            display: inline-block;
            min-width: 80px;
            text-align: center;
            padding: 0.4em 0.6em;
            border-radius: 8px;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        /* === Botón de exportar (igual que las otras pantallas) === */
        .export-buttons {
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            margin: 1.2rem 0;
        }

        .export-buttons .btn {
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #fff;
            transition: 0.25s;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .btn-excel {
            background: #198754;
        }

        .btn-excel:hover {
            background: #157347;
        }
    </style>
</head>

<body>
    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- Contenido -->
        <main class="content">
            <div class="page-header">
                <h2><i class="fas fa-wallet me-2"></i> Pagos y Ganancias</h2>
                <a href="Dashboard.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
            </div>

            <!-- Totales -->
            <div class="row g-3 mb-4 text-center">
                <div class="col-md-4">
                    <div class="p-3 bg-white rounded shadow-sm">
                        <h6 class="text-success"><i class="fas fa-check-circle me-1"></i> Total recibido</h6>
                        <h4>₲<?= number_format((float)$totalPagado, 0, ',', '.') ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-white rounded shadow-sm">
                        <h6 class="text-warning"><i class="fas fa-hourglass-half me-1"></i> Pendiente</h6>
                        <h4>₲<?= number_format((float)$totalPendiente, 0, ',', '.') ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-white rounded shadow-sm">
                        <h6 class="text-info"><i class="fas fa-list me-1"></i> Total Paseos</h6>
                        <h4><?= (int)$totalPaseos ?></h4>
                    </div>
                </div>
            </div>

            <!-- Ganancias -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white fw-bold"><i class="fas fa-coins me-2"></i>Mis Ganancias</div>
                <div class="card-body">

                    <!-- Filtros -->
                    <form method="get" class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fechaInicio) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fechaFin) ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-success"><i class="fas fa-filter me-1"></i> Filtrar</button>
                            <a href="Pagos.php" class="btn btn-outline-secondary">Quitar filtro</a>
                        </div>
                    </form>

                    <!-- Botón Exportar -->
                    <?php if (!empty($paseosCompletados)): ?>
                        <div class="export-buttons">
                            <a href="?export=csv<?= $fechaInicio ? "&fecha_inicio=$fechaInicio&fecha_fin=$fechaFin" : '' ?>" class="btn btn-excel">
                                <i class="fas fa-file-excel"></i> Exportar CSV
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Total de Ganancias -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold text-success">₲<?= number_format((float)$gananciasTotales, 0, ',', '.') ?></h4>
                    </div>

                    <?php if (empty($paseosCompletados)): ?>
                        <div class="alert alert-info text-center">No hay paseos completados en el período seleccionado.</div>
                    <?php else: ?>
                        <table class="table table-hover align-middle text-center">
                            <thead>
                                <tr>
                                    <th>Mascota</th>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paseosCompletados as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['nombre_mascota'] ?? '-') ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($p['inicio'])) ?></td>
                                        <td class="fw-semibold text-success">₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white fw-bold"><i class="fas fa-list me-2"></i>Historial de Pagos</div>
                <div class="card-body">
                    <?php if (empty($pagos)): ?>
                        <p class="text-center text-muted mb-0">No hay registros de pagos aún.</p>
                    <?php else: ?>
                        <table class="table table-hover align-middle text-center">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Paseo</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos as $p): ?>
                                    <?php $badgeColor = $p['estado'] === 'pagado' ? 'bg-success' : 'bg-warning text-dark'; ?>
                                    <tr>
                                        <td>#<?= (int)$p['id'] ?></td>
                                        <td><?= htmlspecialchars($p['paseo']) ?></td>
                                        <td>₲<?= number_format((float)($p['monto'] ?? 0), 0, ',', '.') ?></td>
                                        <td><?= $p['fecha'] ? date('d/m/Y', strtotime($p['fecha'])) : '-' ?></td>
                                        <td><span class="badge <?= $badgeColor ?>"><?= ucfirst($p['estado']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <footer>© <?= date('Y') ?> Jaguata — Panel de Paseador</footer>
        </main>
    </div>
</body>

</html>