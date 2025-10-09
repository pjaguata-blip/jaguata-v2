<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// ===== Init + Auth =====
AppConfig::init();
$authController = new AuthController();
$authController->checkRole('dueno');

// ===== Controlador =====
$paseoController = new PaseoController();
$paseos = $paseoController->index();

// ===== Filtro de estado =====
$estadoFiltro = $_GET['estado'] ?? '';
if ($estadoFiltro) {
    $paseos = array_filter($paseos, fn($p) => $p['estado'] === $estadoFiltro);
}

// ===== Agrupar por estado =====
$paseosPorEstado = [
    'Pendiente'  => array_filter($paseos, fn($p) => $p['estado'] === 'Pendiente'),
    'confirmado' => array_filter($paseos, fn($p) => $p['estado'] === 'confirmado'),
    'en_curso'   => array_filter($paseos, fn($p) => $p['estado'] === 'en_curso'),
    'completo'   => array_filter($paseos, fn($p) => $p['estado'] === 'completo'),
    'cancelado'  => array_filter($paseos, fn($p) => $p['estado'] === 'cancelado'),
];

// ===== Estadísticas =====
$totalPaseos        = count($paseos);
$paseosPendientes   = count($paseosPorEstado['Pendiente']) + count($paseosPorEstado['confirmado']);
$paseosCompletados  = count($paseosPorEstado['completo']);
$paseosCancelados   = count($paseosPorEstado['cancelado']);
$gastosTotales      = array_sum(array_column($paseosPorEstado['completo'], 'precio_total'));

// ===== Mensajes =====
$mensajeExito = $_GET['exito'] ?? '';
$mensajeError = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Paseos - Jaguata</title>
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
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="Dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="MisMascotas.php"><i class="fas fa-paw me-2"></i>Mis Mascotas</a></li>
                        <li class="nav-item"><a class="nav-link" href="SolicitarPaseo.php"><i class="fas fa-plus-circle me-2"></i>Solicitar Paseo</a></li>
                        <li class="nav-item"><a class="nav-link active" href="MisPaseos.php"><i class="fas fa-walking me-2"></i>Mis Paseos</a></li>
                        <li class="nav-item"><a class="nav-link" href="MetodosPago.php"><i class="fas fa-credit-card me-2"></i>Métodos de Pago</a></li>
                        <li class="nav-item"><a class="nav-link" href="MisPuntos.php"><i class="fas fa-star me-2"></i>Mis Puntos</a></li>
                        <li class="nav-item"><a class="nav-link" href="Perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mis Paseos</h1>
                    <a href="SolicitarPaseo.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo Paseo
                    </a>
                </div>

                <!-- Alertas -->
                <?php if ($mensajeExito): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensajeExito) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($mensajeError): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensajeError) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <?php
                    $estadisticas = [
                        ['Total Paseos', $totalPaseos, 'primary', 'fa-walking'],
                        ['Pendientes', $paseosPendientes, 'warning', 'fa-clock'],
                        ['Completados', $paseosCompletados, 'success', 'fa-check-circle'],
                        ['Gastos Totales', '₲' . number_format($gastosTotales, 0, ',', '.'), 'info', 'fa-dollar-sign']
                    ];
                    foreach ($estadisticas as [$titulo, $valor, $color, $icono]): ?>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-<?= $color ?> shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="text-xs fw-bold text-<?= $color ?> text-uppercase mb-1"><?= $titulo ?></div>
                                            <div class="h5 mb-0 fw-bold text-gray-800"><?= $valor ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas <?= $icono ?> fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Lista -->
                <?php if (empty($paseos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-walking fa-5x text-gray-300 mb-4"></i>
                        <h3 class="text-muted">No tienes paseos registrados</h3>
                        <p class="text-muted mb-4">Solicita tu primer paseo para comenzar</p>
                        <a href="SolicitarPaseo.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i> Solicitar Primer Paseo
                        </a>
                    </div>
                <?php else: ?>
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-primary">Lista de Paseos</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle" width="100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mascota</th>
                                            <th>Paseador</th>
                                            <th>Fecha</th>
                                            <th>Duración</th>
                                            <th>Estado</th>
                                            <th>Precio</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paseos as $paseo): ?>
                                            <tr>
                                                <td><i class="fas fa-paw text-primary me-2"></i><?= htmlspecialchars($paseo['nombre_mascota']) ?></td>
                                                <td><i class="fas fa-user text-secondary me-2"></i><?= htmlspecialchars($paseo['nombre_paseador']) ?></td>
                                                <td>
                                                    <strong><?= date('d/m/Y', strtotime($paseo['inicio'])) ?></strong><br>
                                                    <small class="text-muted"><?= date('H:i', strtotime($paseo['inicio'])) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($paseo['duracion']) ?> min</td>
                                                <td>
                                                    <span class="badge bg-<?=
                                                                            $paseo['estado'] === 'completo' ? 'success' : ($paseo['estado'] === 'cancelado' ? 'danger' : ($paseo['estado'] === 'en_curso' ? 'info' : 'warning')) ?>">
                                                        <?= ucfirst($paseo['estado']) ?>
                                                    </span>
                                                </td>
                                                <td><strong>₲<?= number_format($paseo['precio_total'], 0, ',', '.') ?></strong></td>

                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="VerPaseo.php?id=<?= $paseo['paseo_id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>

                                                        <?php if (in_array($paseo['estado'], ['Pendiente', 'confirmado'])): ?>
                                                            <a href="CancelarPaseo.php?id=<?= $paseo['paseo_id'] ?>"
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('¿Seguro que deseas cancelar este paseo?')">
                                                                <i class="fas fa-times"></i>
                                                            </a>

                                                            <!-- Botón de pago -->
                                                            <a href="pagar_paseo.php?paseo_id=<?= $paseo['paseo_id'] ?>"
                                                                class="btn btn-sm btn-outline-success"
                                                                title="Pagar este paseo">
                                                                <i class="fas fa-dollar-sign"></i>
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($paseo['estado'] === 'en_curso'): ?>
                                                            <a href="CompletarPaseo.php?id=<?= $paseo['paseo_id'] ?>"
                                                                class="btn btn-sm btn-outline-success"
                                                                onclick="return confirm('¿Confirmas que este paseo fue completado?')">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>