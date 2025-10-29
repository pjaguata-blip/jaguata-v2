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

AppConfig::init();

// --- Seguridad ---
$auth = new AuthController();
$auth->checkRole('paseador');

// --- Variables base ---
$rolMenu = 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

// --- Simulación de pagos ---
$pagos = [
    ['id' => 1, 'paseo' => 'Paseo de Luna', 'monto' => 50000, 'fecha' => '2025-10-20', 'estado' => 'pagado'],
    ['id' => 2, 'paseo' => 'Paseo de Max', 'monto' => 35000, 'fecha' => '2025-10-22', 'estado' => 'pendiente'],
    ['id' => 3, 'paseo' => 'Paseo de Toby', 'monto' => 60000, 'fecha' => '2025-10-25', 'estado' => 'pagado'],
    ['id' => 4, 'paseo' => 'Paseo de Nala', 'monto' => 40000, 'fecha' => '2025-10-26', 'estado' => 'pendiente'],
];

// --- Cálculos ---
$totalPagado = array_sum(array_column(array_filter($pagos, fn($p) => $p['estado'] === 'pagado'), 'monto'));
$totalPendiente = array_sum(array_column(array_filter($pagos, fn($p) => $p['estado'] === 'pendiente'), 'monto'));
$totalPaseos = count($pagos);

// --- Ganancias (simulado) ---
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';
$paseosCompletados = [
    ['nombre_mascota' => 'Luna', 'inicio' => '2025-10-10 08:00', 'precio_total' => 50000],
    ['nombre_mascota' => 'Toby', 'inicio' => '2025-10-15 09:30', 'precio_total' => 60000],
    ['nombre_mascota' => 'Nala', 'inicio' => '2025-10-20 10:00', 'precio_total' => 40000],
];
if ($fechaInicio && $fechaFin) {
    $paseosCompletados = array_filter(
        $paseosCompletados,
        fn($p) =>
        date('Y-m-d', strtotime($p['inicio'])) >= $fechaInicio && date('Y-m-d', strtotime($p['inicio'])) <= $fechaFin
    );
}
$gananciasTotales = array_sum(array_column($paseosCompletados, 'precio_total'));

// --- Exportación CSV ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ganancias_paseador.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Mascota', 'Fecha', 'Monto (₲)']);
    foreach ($paseosCompletados as $p) {
        fputcsv($output, [$p['nombre_mascota'], date('d/m/Y H:i', strtotime($p['inicio'])), $p['precio_total']]);
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
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
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

        .stat-card {
            background: #fff;
            border-radius: 12px;
            text-align: center;
            padding: 1.2rem 0.8rem;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 0.4rem;
        }

        .table thead {
            background: #3c6255;
            color: #fff;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            border-radius: 8px;
            transition: 0.3s;
        }

        .btn-gradient:hover {
            opacity: 0.9;
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            padding: 1rem 0;
            margin-top: 2rem;
        }
    </style>
</head>

<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="text-center mb-4">
                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="55">
                <hr class="text-light">
            </div>
            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i>Inicio</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php"><i class="fas fa-list"></i>Mis Paseos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Disponibilidad.php"><i class="fas fa-calendar-check"></i>Disponibilidad</a></li>
                <li><a class="nav-link active" href="#"><i class="fas fa-wallet"></i>Pagos y Ganancias</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Configuracion.php"><i class="fas fa-cogs"></i>Configuración</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i>Salir</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <div class="page-header">
                <h2><i class="fas fa-wallet me-2"></i> Pagos y Ganancias</h2>
                <a href="Dashboard.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
            </div>

            <!-- Estadísticas -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-check-circle stat-icon text-success"></i>
                        <h5>₲<?= number_format($totalPagado, 0, ',', '.') ?></h5>
                        <p class="text-muted">Total recibido</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-hourglass-half stat-icon text-warning"></i>
                        <h5>₲<?= number_format($totalPendiente, 0, ',', '.') ?></h5>
                        <p class="text-muted">Pagos pendientes</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-walking stat-icon text-info"></i>
                        <h5><?= $totalPaseos ?></h5>
                        <p class="text-muted">Paseos totales</p>
                    </div>
                </div>
            </div>

            <!-- Tabla de pagos -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white fw-bold"><i class="fas fa-list me-2"></i>Historial de Pagos</div>
                <div class="card-body">
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
                                <tr>
                                    <td>#<?= $p['id'] ?></td>
                                    <td><?= htmlspecialchars($p['paseo']) ?></td>
                                    <td>₲<?= number_format($p['monto'], 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                                    <td>
                                        <span class="badge <?= $p['estado'] === 'pagado' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= ucfirst($p['estado']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sección Ganancias -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white fw-bold"><i class="fas fa-coins me-2"></i>Mis Ganancias</div>
                <div class="card-body">
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
                            <button type="submit" class="btn btn-gradient"><i class="fas fa-filter me-1"></i> Filtrar</button>
                            <a href="Pagos.php" class="btn btn-outline-secondary">Quitar filtro</a>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold text-success">₲<?= number_format($gananciasTotales, 0, ',', '.') ?></h4>
                        <?php if (!empty($paseosCompletados)): ?>
                            <a href="?export=csv<?= $fechaInicio ? "&fecha_inicio=$fechaInicio&fecha_fin=$fechaFin" : '' ?>"
                                class="btn btn-outline-success btn-sm">
                                <i class="fas fa-file-export me-1"></i> Exportar CSV
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($paseosCompletados)): ?>
                        <div class="alert alert-info mb-0 text-center">No hay paseos completados en el período seleccionado.</div>
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
                                        <td><?= htmlspecialchars($p['nombre_mascota']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($p['inicio'])) ?></td>
                                        <td class="fw-semibold text-success">₲<?= number_format($p['precio_total'], 0, ',', '.') ?></td>
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