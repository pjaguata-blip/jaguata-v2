<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

$auth = new AuthController();
$auth->requireRole(['paseador']);

$paseoController = new PaseoController();
$paseadorId = Session::get('usuario_id');

$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin    = $_GET['fecha_fin'] ?? '';

$paseos = $paseoController->indexForPaseador($paseadorId);
$paseosCompletados = array_filter($paseos, fn($p) => $p['estado'] === 'completo');

if ($fechaInicio && $fechaFin) {
    $paseosCompletados = array_filter($paseosCompletados, function ($p) use ($fechaInicio, $fechaFin) {
        $fechaPaseo = date('Y-m-d', strtotime($p['inicio']));
        return $fechaPaseo >= $fechaInicio && $fechaPaseo <= $fechaFin;
    });
}

$gananciasTotales = array_sum(array_map(fn($p) => $p['precio_total'], $paseosCompletados));

// Exportación CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=mis_ganancias.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Mascota', 'Fecha', 'Precio (₲)']);
    foreach ($paseosCompletados as $p) {
        fputcsv($output, [
            $p['nombre_mascota'] ?? '-',
            date('d/m/Y H:i', strtotime($p['inicio'])),
            number_format($p['precio_total'], 0, ',', '.')
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
    <title>Mis Ganancias - Paseador | Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            color: #333;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.2rem 1.5rem;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 600;
            font-size: 1.3rem;
        }

        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            border-radius: 8px;
            transition: .3s;
        }

        .btn-gradient:hover {
            opacity: .9;
        }

        .table thead {
            background-color: #3c6255;
            color: #fff;
        }

        .table tbody tr:hover {
            background-color: #f0f8f5;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="page-header">
            <h2><i class="fas fa-wallet me-2"></i> Mis Ganancias</h2>
            <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <!-- FILTRO -->
        <form method="get" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="fecha_inicio" class="form-label fw-semibold">Desde</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control"
                    value="<?= htmlspecialchars($fechaInicio) ?>">
            </div>
            <div class="col-md-4">
                <label for="fecha_fin" class="form-label fw-semibold">Hasta</label>
                <input type="date" id="fecha_fin" name="fecha_fin" class="form-control"
                    value="<?= htmlspecialchars($fechaFin) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-gradient"><i class="fas fa-filter me-1"></i> Filtrar</button>
                <a href="MisGanancias.php" class="btn btn-outline-secondary">Quitar filtro</a>
            </div>
        </form>

        <!-- RESUMEN -->
        <div class="card mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-semibold mb-1 text-success">
                        <i class="fas fa-coins me-2"></i> Ganancia total <?= ($fechaInicio && $fechaFin) ? "del $fechaInicio al $fechaFin" : '' ?>
                    </h5>
                    <h2 class="fw-bold text-success">₲<?= number_format($gananciasTotales, 0, ',', '.') ?></h2>
                </div>
                <?php if (!empty($paseosCompletados)): ?>
                    <a href="?export=csv<?= $fechaInicio ? "&fecha_inicio=$fechaInicio&fecha_fin=$fechaFin" : '' ?>"
                        class="btn btn-outline-success">
                        <i class="fas fa-file-export me-1"></i> Exportar CSV
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- TABLA -->
        <div class="card">
            <div class="card-body">
                <h5 class="fw-semibold mb-3"><i class="fas fa-list-alt me-2 text-success"></i> Paseos Completados</h5>
                <?php if (empty($paseosCompletados)): ?>
                    <div class="alert alert-info mb-0">No hay paseos completados en el período seleccionado.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle text-center">
                            <thead>
                                <tr>
                                    <th>Mascota</th>
                                    <th>Fecha</th>
                                    <th>Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paseosCompletados as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['nombre_mascota'] ?? '-') ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($p['inicio'])) ?></td>
                                        <td class="fw-semibold text-success">₲<?= number_format($p['precio_total'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>