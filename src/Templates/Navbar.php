<?php
// Navbar component
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Services/NotificacionService.php';

use Jaguata\Helpers\Session;
use Jaguata\Services\NotificacionService;

$usuarioLogueado = Session::isLoggedIn();
$rolUsuario      = Session::getUsuarioRol();
$nombreUsuario   = Session::getUsuarioNombre();

// 🔹 URL dinámica de inicio
$inicioUrl = BASE_URL . "/index.php";
if ($usuarioLogueado && $rolUsuario) {
    $inicioUrl = BASE_URL . "/features/{$rolUsuario}/Dashboard.php";
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $inicioUrl; ?>">
            <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="Jaguata" height="40" class="me-2">
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>"
                        href="<?php echo $inicioUrl; ?>">
                        <i class="fas fa-home me-1"></i>Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'sobre_nosotros.php' ? 'active' : ''; ?>"
                        href="<?php echo BASE_URL; ?>/sobre_nosotros.php">
                        <i class="fas fa-info-circle me-1"></i>Sobre Nosotros
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'contacto.php' ? 'active' : ''; ?>"
                        href="<?php echo BASE_URL; ?>/contacto.php">
                        <i class="fas fa-envelope me-1"></i>Contacto
                    </a>
                </li>

                <?php if ($usuarioLogueado): ?>
                    <?php if ($rolUsuario === 'dueno'): ?>

                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'BuscarPaseadores.php' ? 'active' : ''; ?>"
                                href="<?php echo BASE_URL; ?>/features/dueno/BuscarPaseadores.php">
                                <i class="fas fa-search me-1"></i>Buscar Paseadores
                            </a>
                        </li>
                    <?php elseif ($rolUsuario === 'paseador'): ?>

                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'Solicitudes.php' ? 'active' : ''; ?>"
                                href="<?php echo BASE_URL; ?>/features/paseador/Solicitudes.php">
                                <i class="fas fa-bell me-1"></i>Solicitudes
                                <?php
                                $notificacionService = new NotificacionService();
                                $notificaciones = $notificacionService->getNotificacionesNoLeidas(Session::getUsuarioId());
                                if ($notificaciones['success'] && $notificaciones['total'] > 0) {
                                    echo '<span class="badge bg-danger ms-1">' . $notificaciones['total'] . '</span>';
                                }
                                ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'MisPaseos.php' ? 'active' : ''; ?>"
                                href="<?php echo BASE_URL; ?>/features/paseador/MisPaseos.php">
                                <i class="fas fa-walking me-1"></i>Mis Paseos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'MisGanancias.php' ? 'active' : ''; ?>"
                                href="<?php echo BASE_URL; ?>/features/paseador/MisGanancias.php">
                                <i class="fas fa-dollar-sign me-1"></i>Mis Ganancias
                            </a>
                        </li>
                    <?php elseif ($rolUsuario === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'Dashboard.php' ? 'active' : ''; ?>"
                                href="<?php echo BASE_URL; ?>/features/admin/Dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'GestionUsuarios.php' ? 'active' : ''; ?>"
                                href="<?php echo BASE_URL; ?>/features/admin/GestionUsuarios.php">
                                <i class="fas fa-users me-1"></i>Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'Reportes.php' ? 'active' : ''; ?>"
                                href="<?php echo BASE_URL; ?>/features/admin/Reportes.php">
                                <i class="fas fa-chart-bar me-1"></i>Reportes
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <!-- User menu -->
            <ul class="navbar-nav">
                <?php if ($usuarioLogueado): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?php if (Session::get('usuario_foto')): ?>
                                <img src="<?php echo ASSETS_URL; ?>/uploads/perfiles/<?php echo Session::get('usuario_foto'); ?>"
                                    alt="Foto de perfil" class="rounded-circle me-2" width="32" height="32">
                            <?php else: ?>
                                <i class="fas fa-user-circle me-2"></i>
                            <?php endif; ?>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombreUsuario); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/features/<?php echo $rolUsuario; ?>/Perfil.php"><i class="fas fa-user-edit me-2"></i>Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/features/<?php echo $rolUsuario; ?>/Dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/login.php"><i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión</a></li>
                    <li class="nav-item"><a class="btn btn-primary ms-2" href="<?php echo BASE_URL; ?>/registro.php"><i class="fas fa-user-plus me-1"></i>Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>