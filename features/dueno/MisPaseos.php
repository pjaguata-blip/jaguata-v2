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

// ✅ Obtener solo los paseos del dueño actual
$duenoId = (int)Session::get('usuario_id');
$paseos = $paseoController->indexByDueno($duenoId);

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
                <!-- Filtros -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">Filtros</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" onchange="filtrarPorEstado()">
                                    <option value="">Todos los estados</option>
                                    <?php
                                    foreach ($estadosValidos as $v) {
                                        $sel = ($estadoFiltro === $v) ? 'selected' : '';
                                        echo "<option value=\"{$v}\" {$sel}>" . ucfirst(str_replace('_', ' ', $v)) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
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
                                                <td><i class="fas fa-paw text-primary me-2"></i><?= htmlspecialchars((string)$paseo['nombre_mascota']) ?></td>
                                                <td><i class="fas fa-user text-secondary me-2"></i><?= htmlspecialchars((string)$paseo['nombre_paseador']) ?></td>
                                                <td>
                                                    <strong><?= date('d/m/Y', strtotime($paseo['inicio'])) ?></strong><br>
                                                    <small class="text-muted"><?= date('H:i', strtotime($paseo['inicio'])) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars((string)$paseo['duracion']) ?> min</td>
                                                <td>
                                                    <span class="badge bg-<?=
                                                                            $paseo['estado'] === 'completo' ? 'success' : ($paseo['estado'] === 'cancelado' ? 'danger' : ($paseo['estado'] === 'en_curso' ? 'info' : 'warning')) ?>">
                                                        <?= ucfirst($paseo['estado']) ?>
                                                    </span>
                                                </td>
                                                <td><strong>₲<?= number_format((float)$paseo['precio_total'], 0, ',', '.') ?></strong></td>

                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <!-- Ver Paseo -->
                                                        <a href="DetallePaseo.php?paseo_id=<?= $paseo['paseo_id'] ?>"
                                                            class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="tooltip"
                                                            title="Ver detalles del paseo">
                                                            <i class="fas fa-eye"></i>
                                                        </a>

                                                        <!-- Cancelar (si aplica) -->
                                                        <?php if (in_array($paseo['estado'], ['Pendiente', 'confirmado'])): ?>
                                                            <a href="CancelarPaseo.php?id=<?= $paseo['paseo_id'] ?>"
                                                                class="btn btn-sm btn-outline-danger"
                                                                data-bs-toggle="tooltip"
                                                                title="Cancelar paseo"
                                                                onclick="return confirm('¿Seguro que deseas cancelar este paseo?')">
                                                                <i class="fas fa-times"></i>
                                                            </a>

                                                            <!-- Botón de pago -->
                                                            <a href="pago_paseo_dueno.php?paseo_id=<?= $paseo['paseo_id'] ?>"
                                                                class="btn btn-sm btn-outline-success"
                                                                data-bs-toggle="tooltip"
                                                                title="Pagar paseo">
                                                                <i class="fas fa-wallet"></i>
                                                            </a>
                                                        <?php endif; ?>

                                                        <!-- Completar (si en curso) -->
                                                        <?php if ($paseo['estado'] === 'en_curso'): ?>
                                                            <a href="CompletarPaseo.php?id=<?= $paseo['paseo_id'] ?>"
                                                                class="btn btn-sm btn-outline-success"
                                                                data-bs-toggle="tooltip"
                                                                title="Marcar como completado"
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