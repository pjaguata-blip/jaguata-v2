<?php
require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Services/NotificacionService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Services\NotificacionService;

AppConfig::init();

// === Datos del usuario ===
$usuarioLogueado = Session::isLoggedIn();
$usuarioNombre   = Session::getUsuarioNombre() ?? 'Usuario';
$fotoUsuario     = Session::get('usuario_foto');
$rolUsuario      = Session::getUsuarioRol() ?? 'dueno';
$baseFeatures    = BASE_URL . "/features/{$rolUsuario}";
$titulo          = $titulo ?? 'Panel - Jaguata';

// === Notificaciones ===
$totalNoLeidas = 0;
if ($usuarioLogueado) {
    $service = new NotificacionService();
    $notificaciones = $service->getNotificacionesNoLeidas(Session::getUsuarioId());
    if ($notificaciones['success']) $totalNoLeidas = $notificaciones['total'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>

    <!-- Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>
    <!-- Botón móvil -->
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="60" class="rounded-circle shadow-sm">
                <hr class="text-light">
            </div>

            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'Dashboard') ? 'active' : '' ?>" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'MiPerfil') ? 'active' : '' ?>" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'MisMascotas') ? 'active' : '' ?>" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'PaseosPendientes') ? 'active' : '' ?>" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-hourglass-half"></i> Paseos Pendientes</a></li>
                <li><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'PaseosCompletados') ? 'active' : '' ?>" href="<?= $baseFeatures; ?>/PaseosCompletados.php"><i class="fas fa-check-circle"></i> Paseos Completados</a></li>
                <li><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'Notificaciones') ? 'active' : '' ?>" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a class="nav-link <?= str_contains($_SERVER['SCRIPT_NAME'], 'GastosTotales') ? 'active' : '' ?>" href="<?= $baseFeatures; ?>/GastosTotales.php"><i class="fas fa-wallet"></i> Mis Gastos</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="content">
            <!-- Header verde -->
            <div class="welcome-box mb-4">
                <div>
                    <h1>¡Bienvenido/a, <?= htmlspecialchars($usuarioNombre); ?>!</h1>
                    <p>Gestioná tus mascotas, paseos y notificaciones fácilmente 🐾</p>
                </div>
                <i class="fas fa-dog fa-3x opacity-75"></i>
            </div>

            <!-- Navbar blanca -->
            <div class="navbar-panel mb-4">
                <div class="d-flex align-items-center gap-2">
                    <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Logo" width="35" class="rounded-circle">
                    <strong class="text-success fs-5">Jaguata</strong>
                </div>

                <div class="input-group" style="max-width: 280px;">
                    <input type="text" class="form-control form-control-sm" placeholder="Buscar paseadores...">
                    <button class="btn btn-success btn-sm"><i class="fas fa-search"></i></button>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="<?= $baseFeatures; ?>/Notificaciones.php" class="text-success position-relative">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php if ($totalNoLeidas > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $totalNoLeidas ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="dropdown">
                        <a class="text-success fw-semibold text-decoration-none dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?php if ($fotoUsuario): ?>
                                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/<?= htmlspecialchars($fotoUsuario); ?>"
                                    class="rounded-circle me-2" width="34" height="34" style="object-fit:cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-lg me-2 text-secondary"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($usuarioNombre) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user-edit me-2 text-success"></i>Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-gauge-high me-2 text-success"></i>Dashboard</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Aquí se inyecta el contenido dinámico -->
            <?= $content ?? '' ?>

            <!-- Footer -->
            <?php include __DIR__ . '/Footer.php'; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    </script>
</body>

</html>