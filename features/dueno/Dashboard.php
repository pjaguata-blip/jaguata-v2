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

$rolMenu     = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

// URL de inicio dinámico
$homeUrl = (Session::isLoggedIn() && Session::getUsuarioRol())
    ? BASE_URL . "/features/{$rolMenu}/Dashboard.php"
    : BASE_URL . "/public/login.php";

// Controladores
$mascotaController       = new MascotaController();
$paseoController         = new PaseoController();
$notificacionController  = new NotificacionController();

// Datos base
$mascotas       = $mascotaController->index();      // ← solo mascotas del dueño
$allPaseos      = $paseoController->index();        // ← podrían venir de todos
$notificaciones = $notificacionController->getRecientes();

// ---- FILTRAR PASEOS SOLO DE LAS MASCOTAS DEL DUEÑO ----
/**
 * Obtiene el ID de mascota desde un item, tolerando distintos nombres de clave.
 */
$extractMascotaId = function (array $row) {
    return $row['mascota_id'] ?? $row['id_mascota'] ?? $row['idMascota'] ?? null;
};

// Conjunto de IDs de mascotas del dueño
$idsMascotasDueno = [];
foreach ($mascotas as $m) {
    $mid = $m['mascota_id'] ?? $m['id'] ?? $m['id_mascota'] ?? null;
    if ($mid !== null) {
        $idsMascotasDueno[(int)$mid] = true;
    }
}

// Filtrar paseos por las mascotas del dueño
$paseos = array_values(array_filter($allPaseos, function ($p) use ($extractMascotaId, $idsMascotasDueno) {
    $mid = $extractMascotaId($p);
    return $mid !== null && isset($idsMascotasDueno[(int)$mid]);
}));

// Estadísticas (sobre paseos filtrados)
$totalMascotas = count($mascotas);

$paseosPendientes = array_filter($paseos, fn($p) => in_array(($p['estado'] ?? ''), ['Pendiente', 'confirmado'], true));
$paseosCompletados = array_filter($paseos, fn($p) => ($p['estado'] ?? '') === 'completo');
$paseosCancelados  = array_filter($paseos, fn($p) => ($p['estado'] ?? '') === 'cancelado');

$totalPaseosPendientes  = count($paseosPendientes);
$totalPaseosCompletados = count($paseosCompletados);
$totalPaseosCancelados  = count($paseosCancelados);

// Gastos totales (solo completados del dueño)
$gastosTotales = 0;
foreach ($paseosCompletados as $p) {
    $gastosTotales += (float)($p['precio_total'] ?? 0);
}

// Paseos recientes (del dueño)
$paseosRecientes = array_slice($paseos, 0, 5);

// Mascotas recientes (del dueño)
$mascotasRecientes = array_slice($mascotas, 0, 3);

// ID para menú "Perfil de mi Mascota" (primera mascota)
$firstMascotaId = 0;
foreach ($mascotas as $m) {
    $mid = $m['mascota_id'] ?? $m['id'] ?? $m['id_mascota'] ?? null;
    if (!empty($mid)) {
        $firstMascotaId = (int)$mid;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../../assets/css/style.css" rel="stylesheet" />
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
                                <span class="flex-grow-1">Configuracion</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuPerfil">
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php">
                                        <i class="fas fa-id-card me-2"></i> Mi Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">
                                        <i class="fas fa-gear me-2"></i> Editar Perfil
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>

                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i>
                                Exportar
                            </button>
                        </div>
                        <a href="SolicitarPaseo.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            Nuevo Paseo
                        </a>
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
                                            <?= $totalMascotas ?>
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
                                            <?= $totalPaseosCompletados ?>
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
                                            <?= $totalPaseosPendientes ?>
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
                                            ₲<?= number_format($gastosTotales, 0, ',', '.') ?>
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
                    <!-- Paseos Recientes (solo del dueño) -->
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Paseos Recientes</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($paseosRecientes)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-walking fa-3x text-gray-300 mb-3"></i>
                                        <p class="text-muted">No tienes paseos recientes de tus mascotas.</p>
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
                                                        <td><?= htmlspecialchars($paseo['nombre_mascota'] ?? '') ?></td>
                                                        <td>
                                                            <?= isset($paseo['nombre_paseador'])
                                                                ? htmlspecialchars($paseo['nombre_paseador'])
                                                                : '<span class="text-muted">-</span>' ?>
                                                        </td>
                                                        <td><?= !empty($paseo['inicio']) ? date('d/m/Y H:i', strtotime($paseo['inicio'])) : '-' ?></td>
                                                        <td>
                                                            <?php
                                                            $estado = $paseo['estado'] ?? '';
                                                            $cls = ($estado === 'completo') ? 'success' : (($estado === 'cancelado') ? 'danger' : 'warning');
                                                            ?>
                                                            <span class="badge badge-<?= $cls ?>"><?= htmlspecialchars(ucfirst($estado)) ?></span>
                                                        </td>
                                                        <td>₲<?= number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Mis Mascotas + Notificaciones -->
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
                                            <div class="me-3">
                                                <i class="fas fa-paw fa-2x text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?= htmlspecialchars($mascota['nombre'] ?? '') ?></h6>
                                                <small class="text-muted">
                                                    <?php
                                                    $tam = $mascota['tamano'] ?? '';
                                                    $edadMeses = $mascota['edad'] ?? $mascota['edad_meses'] ?? null;
                                                    if ($edadMeses !== null && $edadMeses !== '') {
                                                        $m = (int)$edadMeses;
                                                        $edadTxt = ($m < 12) ? "$m meses" : (intdiv($m, 12) . " años");
                                                    } else {
                                                        $edadTxt = '—';
                                                    }
                                                    echo ($tam ? ucfirst($tam) : '—') . ' • ' . $edadTxt;
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center">
                                        <a href="MisMascotas.php" class="btn btn-outline-primary btn-sm">Ver Todas</a>
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
                                    <?php foreach ($notificaciones as $n): ?>
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="me-3">
                                                <i class="fas fa-<?= ($n['tipo'] ?? '') === 'nuevo_paseo' ? 'walking' : 'info-circle'; ?> fa-lg text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?= htmlspecialchars($n['titulo'] ?? '') ?></h6>
                                                <p class="text-muted mb-0"><?= htmlspecialchars($n['mensaje'] ?? '') ?></p>
                                                <small class="text-muted">
                                                    <?= !empty($n['created_at']) ? date('d/m/Y H:i', strtotime($n['created_at'])) : '' ?>
                                                </small>
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