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

// Verificar rol paseador
$auth = new AuthController();
$auth->requireRole(['paseador']);

// Controlador
$paseoController = new PaseoController();
$paseadorId = Session::get('usuario_id');

// Fechas del filtro (si no hay, se muestran todos)
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin    = $_GET['fecha_fin'] ?? '';

// Obtener paseos
$paseos = $paseoController->indexForPaseador($paseadorId);

// Filtrar por estado = completo
$paseosCompletados = array_filter($paseos, fn($p) => $p['estado'] === 'completo');

// Filtrar por rango de fechas si se seleccionó
if ($fechaInicio && $fechaFin) {
    $paseosCompletados = array_filter($paseosCompletados, function ($p) use ($fechaInicio, $fechaFin) {
        $fechaPaseo = date('Y-m-d', strtotime($p['inicio']));
        return $fechaPaseo >= $fechaInicio && $fechaPaseo <= $fechaFin;
    });
}

// Calcular ganancias
$gananciasTotales = 0;
foreach ($paseosCompletados as $p) {
    $gananciasTotales += $p['precio_total'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Ganancias - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="fas fa-wallet me-2"></i> Mis Ganancias</h2>

        <!-- Formulario de filtro -->
        <form method="get" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="fecha_inicio" class="form-label">Desde</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control"
                    value="<?= htmlspecialchars($fechaInicio) ?>">
            </div>
            <div class="col-md-4">
                <label for="fecha_fin" class="form-label">Hasta</label>
                <input type="date" id="fecha_fin" name="fecha_fin" class="form-control"
                    value="<?= htmlspecialchars($fechaFin) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                <a href="MisGanancias.php" class="btn btn-secondary">Quitar filtro</a>
            </div>
        </form>

        <div class="card mb-4">
            <div class="card-body">
                <h5>Total acumulado <?= ($fechaInicio && $fechaFin) ? "del $fechaInicio al $fechaFin" : '' ?>:</h5>
                <p class="fs-3 fw-bold text-success">
                    ₲<?= number_format($gananciasTotales, 0, ',', '.') ?>
                </p>
            </div>
        </div>

        <h4>Paseos completados</h4>
        <?php if (empty($paseosCompletados)): ?>
            <div class="alert alert-info">No hay paseos completados en el período seleccionado.</div>
        <?php else: ?>
            <table class="table table-striped">
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
                            <td>₲<?= number_format($p['precio_total'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
</body>

</html>