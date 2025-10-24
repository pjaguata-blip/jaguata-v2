<?php
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Services/NotificacionService.php';

use Jaguata\Helpers\Session;
use Jaguata\Services\NotificacionService;

$usuarioLogueado = Session::isLoggedIn();
$rolUsuario      = Session::getUsuarioRol();
$nombreUsuario   = Session::getUsuarioNombre();
$fotoUsuario     = Session::get('usuario_foto');

// 🔹 URL dinámica de inicio
$inicioUrl = BASE_URL . "/index.php";
if ($usuarioLogueado && $rolUsuario) {
    $inicioUrl = BASE_URL . "/features/{$rolUsuario}/Dashboard.php";
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center fw-semibold text-success" href="<?= $inicioUrl; ?>">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logo.png" alt="Jaguata" height="42" class="me-2 rounded">
            Jaguata
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($usuarioLogueado): ?>
                    <?php if ($rolUsuario === 'dueno'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'BuscarPaseadores.php' ? 'active text-success fw-semibold' : ''; ?>"
                                href="<?= BASE_URL; ?>/features/dueno/BuscarPaseadores.php">
                                <i class="fas fa-search me-1"></i> Buscar Paseadores
                            </a>
                        </li>

                    <?php elseif ($rolUsuario === 'paseador'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'Solicitudes.php' ? 'active text-success fw-semibold' : ''; ?>"
                                href="<?= BASE_URL; ?>/features/paseador/Solicitudes.php">
                                <i class="fas fa-bell me-1"></i> Solicitudes
                                <?php
                                $notificacionService = new NotificacionService();
                                $notificaciones = $notificacionService->getNotificacionesNoLeidas(Session::getUsuarioId());
                                if ($notificaciones['success'] && $notificaciones['total'] > 0): ?>
                                    <span class="badge bg-danger ms-1"><?= $notificaciones['total']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'MisPaseos.php' ? 'active text-success fw-semibold' : ''; ?>"
                                href="<?= BASE_URL; ?>/features/paseador/MisPaseos.php">
                                <i class="fas fa-walking me-1"></i> Mis Paseos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'MisGanancias.php' ? 'active text-success fw-semibold' : ''; ?>"
                                href="<?= BASE_URL; ?>/features/paseador/MisGanancias.php">
                                <i class="fas fa-dollar-sign me-1"></i> Mis Ganancias
                            </a>
                        </li>

                    <?php elseif ($rolUsuario === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'Dashboard.php' ? 'active text-success fw-semibold' : ''; ?>"
                                href="<?= BASE_URL; ?>/features/admin/Dashboard.php">
                                <i class="fas fa-gauge-high me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'GestionUsuarios.php' ? 'active text-success fw-semibold' : ''; ?>"
                                href="<?= BASE_URL; ?>/features/admin/GestionUsuarios.php">
                                <i class="fas fa-users me-1"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'Reportes.php' ? 'active text-success fw-semibold' : ''; ?>"
                                href="<?= BASE_URL; ?>/features/admin/Reportes.php">
                                <i class="fas fa-chart-bar me-1"></i> Reportes
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <!-- User Menu -->
            <ul class="navbar-nav">
                <?php if ($usuarioLogueado): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?php if ($fotoUsuario): ?>
                                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/<?= htmlspecialchars($fotoUsuario); ?>"
                                    alt="Foto de perfil"
                                    class="rounded-circle me-2"
                                    style="width:36px;height:36px;object-fit:cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-lg me-2 text-secondary"></i>
                            <?php endif; ?>
                            <span class="d-none d-md-inline fw-medium"><?= htmlspecialchars($nombreUsuario); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL; ?>/features/<?= $rolUsuario; ?>/Perfil.php">
                                    <i class="fas fa-user-edit me-2"></i> Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL; ?>/features/<?= $rolUsuario; ?>/Dashboard.php">
                                    <i class="fas fa-gauge-high me-2"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL; ?>/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-success ms-2 rounded-pill px-3" href="<?= BASE_URL; ?>/registro.php">
                            <i class="fas fa-user-plus me-1"></i> Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- SweetAlert Global Notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if (!empty($_SESSION['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: '¡Listo!',
            text: '<?= addslashes($_SESSION['success']) ?> 🐾',
            showConfirmButton: false,
            timer: 2500,
            background: '#f6f9f7'
        });
    <?php unset($_SESSION['success']);
    endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Ups...',
            text: '<?= addslashes($_SESSION['error']) ?>',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#3c6255'
        });
    <?php unset($_SESSION['error']);
    endif; ?>
</script>

<style>
    .navbar .nav-link {
        color: #3c6255 !important;
        transition: color .3s;
    }

    .navbar .nav-link:hover {
        color: #20c997 !important;
    }

    .navbar-brand {
        font-weight: 700;
        letter-spacing: .3px;
    }

    .dropdown-menu {
        border-radius: 12px;
        font-size: 0.95rem;
    }
</style>