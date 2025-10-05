<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/NotificacionController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\NotificacionController;
use Jaguata\Helpers\Session;

// Inicializar aplicación
AppConfig::init();

// Verificar autenticación
$authController = new AuthController();
$authController->checkRole('dueno');

// 🔹 Definir URL dinámico de Inicio
$usuarioLogueado = Session::isLoggedIn();
$rolUsuario      = Session::getUsuarioRol();
if ($usuarioLogueado && $rolUsuario) {
    $homeUrl = BASE_URL . "/features/{$rolUsuario}/Dashboard.php";
} else {
    $homeUrl = BASE_URL . "/public/login.php";
}

// Obtener controladores
$mascotaController = new MascotaController();
$paseoController = new PaseoController();
$notificacionController = new NotificacionController();

// Obtener datos del dashboard
$mascotas = $mascotaController->index();
$paseos = $paseoController->index();
$notificaciones = $notificacionController->getRecientes();

// Estadísticas
$totalMascotas = count($mascotas);
$paseosPendientes = array_filter($paseos, function ($paseo) {
    return in_array($paseo['estado'], ['Pendiente', 'confirmado']);
});
$paseosCompletados = array_filter($paseos, function ($paseo) {
    return $paseo['estado'] === 'completo';
});
$paseosCancelados = array_filter($paseos, function ($paseo) {
    return $paseo['estado'] === 'cancelado';
});

$totalPaseosPendientes = count($paseosPendientes);
$totalPaseosCompletados = count($paseosCompletados);
$totalPaseosCancelados = count($paseosCancelados);

// Calcular gastos totales
$gastosTotales = 0;
foreach ($paseosCompletados as $paseo) {
    $gastosTotales += $paseo['precio_total'];
}

// Obtener paseos recientes (últimos 5)
$paseosRecientes = array_slice($paseos, 0, 5);

// Obtener mascotas recientes (últimas 3)
$mascotasRecientes = array_slice($mascotas, 0, 3);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Jaguata</title>
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

                                <!-- Mis Paseos (2º nivel) -->
                                <li class="nav-item">
                                    <button class="nav-link d-flex align-items-center w-100 text-start"
                                        data-bs-toggle="collapse" data-bs-target="#menuMisPaseos" aria-expanded="false">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <span class="flex-grow-1">Mis Paseos</span>
                                        <i class="fas fa-chevron-right ms-2 chevron"></i>
                                    </button>
                                    <ul class="collapse ps-4 nav flex-column" id="menuMisPaseos">
                                        <li class="nav-item">
                                            <a class="nav-link" href="PaseosCompletados.php">
                                                <i class="fas fa-check-circle me-2"></i> Paseos Completados
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="PaseosPendientes.php">
                                                <i class="fas fa-hourglass-half me-2"></i> Paseos Pendientes
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="PaseosCancelados.php">
                                                <i class="fas fa-times-circle me-2"></i> Paseos Cancelados
                                            </a>
                                        </li>
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
                                    <a class="nav-link" href="MetodosPago.php">
                                        <i class="fas fa-wallet me-2"></i> Método de Pago
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="GastosTotales.php">
                                        <i class="fas fa-chart-line me-2"></i> Gastos Totales
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
                                    <a class="nav-link" href="MisPuntos.php">
                                        <i class="fas fa-star me-2"></i> Mis Puntos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="Perfil.php">
                                        <i class="fas fa-id-card me-2"></i> Configuración
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-danger" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- (Opcional) Dashboard suelto arriba
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center" href="Dashboard.php">
          <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
      </li> -->

                    </ul>
                </div>
            </div>


            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>

                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i>
                                Exportar
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            Nuevo Paseo
                        </button>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <!-- Total Mascotas -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Mascotas
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $totalMascotas; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-paw fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Paseos Completados -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Paseos Completados
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $totalPaseosCompletados; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Paseos Pendientes -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Paseos Pendientes
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $totalPaseosPendientes; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Gastos Totales -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Gastos Totales
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            ₲<?php echo number_format($gastosTotales, 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenido principal -->
                <div class="row">
                    <!-- Paseos Recientes -->
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Paseos Recientes</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($paseosRecientes)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-walking fa-3x text-gray-300 mb-3"></i>
                                        <p class="text-muted">No tienes paseos recientes</p>
                                        <a href="SolicitarPaseo.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>
                                            Solicitar Primer Paseo
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Mascota</th>
                                                    <th>Paseador</th>
                                                    <th>Fecha</th>
                                                    <th>Estado</th>
                                                    <th>Precio</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($paseosRecientes as $paseo): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($paseo['nombre_mascota']); ?></td>
                                                        <td>
                                                            <?php echo isset($paseo['nombre_paseador'])
                                                                ? htmlspecialchars($paseo['nombre_paseador'])
                                                                : '<span class="text-muted">-</span>'; ?>
                                                        </td>

                                                        <td><?php echo date('d/m/Y H:i', strtotime($paseo['inicio'])); ?></td>
                                                        <td>
                                                            <span class="badge badge-<?php echo $paseo['estado'] === 'completo' ? 'success' : ($paseo['estado'] === 'cancelado' ? 'danger' : 'warning'); ?>">
                                                                <?php echo ucfirst($paseo['estado']); ?>
                                                            </span>
                                                        </td>
                                                        <td>₲<?php echo number_format($paseo['precio_total'], 0, ',', '.'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Mascotas y Notificaciones -->
                    <div class="col-lg-4">
                        <!-- Mis Mascotas -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Mis Mascotas</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($mascotasRecientes)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-paw fa-2x text-gray-300 mb-2"></i>
                                        <p class="text-muted mb-3">No tienes mascotas registradas</p>
                                        <a href="AgregarMascota.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>
                                            Agregar Mascota
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($mascotasRecientes as $mascota): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="mr-3">
                                                <i class="fas fa-paw fa-2x text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($mascota['nombre']); ?></h6>
                                                <small class="text-muted"><?php echo ucfirst($mascota['tamano']); ?> • <?php echo $mascota['edad']; ?> años</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center">
                                        <a href="MisMascotas.php" class="btn btn-outline-primary btn-sm">
                                            Ver Todas
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Notificaciones -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Notificaciones</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($notificaciones)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-bell fa-2x text-gray-300 mb-2"></i>
                                        <p class="text-muted">No tienes notificaciones</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notificaciones as $notificacion): ?>
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="mr-3">
                                                <i class="fas fa-<?php echo $notificacion['tipo'] === 'nuevo_paseo' ? 'walking' : 'info-circle'; ?> fa-lg text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($notificacion['titulo']); ?></h6>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($notificacion['mensaje']); ?></p>
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notificacion['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
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