<?php

declare(strict_types=1);

use Jaguata\Helpers\Session;

$usuarioNombre = Session::getUsuarioNombre() ?? 'Administrador';
$rolUsuario    = Session::getUsuarioRol() ?? 'admin';
$baseFeatures  = BASE_URL . "/features/{$rolUsuario}";
$currentFile   = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="sidebar-inner">
        <!-- HEADER DEL SIDEBAR -->
        <div class="text-center px-3 mb-3">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png"
                alt="Jaguata"
                width="70"
                class="rounded-circle border border-light p-1 mb-2">

            <h6 class="text-white fw-semibold mb-0">
                Hola, <?= htmlspecialchars($usuarioNombre); ?> 👋
            </h6>
            <small class="text-light-50">Administrador General</small>

            <hr class="text-secondary opacity-50 w-100">
        </div>

        <!-- MENÚ CON SCROLL -->
        <div class="sidebar-menu-scroll">
            <ul class="nav nav-pills flex-column mb-auto px-2" id="sidebarMenu">
                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Dashboard.php"
                        class="nav-link <?= $currentFile === 'Dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-home me-2"></i>Inicio
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Usuarios.php"
                        class="nav-link <?= $currentFile === 'Usuarios.php' ? 'active' : '' ?>">
                        <i class="fas fa-users me-2"></i>Usuarios
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Paseos.php"
                        class="nav-link <?= $currentFile === 'Paseos.php' ? 'active' : '' ?>">
                        <i class="fas fa-dog me-2"></i>Paseos
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Pagos.php"
                        class="nav-link <?= $currentFile === 'Pagos.php' ? 'active' : '' ?>">
                        <i class="fas fa-wallet me-2"></i>Pagos
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Notificaciones.php"
                        class="nav-link <?= $currentFile === 'Notificaciones.php' ? 'active' : '' ?>">
                        <i class="fas fa-bell me-2"></i>Notificaciones
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Auditoria.php"
                        class="nav-link <?= $currentFile === 'Auditoria.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-shield me-2"></i>Auditoría
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Configuracion.php"
                        class="nav-link <?= $currentFile === 'Configuracion.php' ? 'active' : '' ?>">
                        <i class="fas fa-cogs me-2"></i>Configuración
                    </a>
                </li>

                <hr class="text-secondary opacity-50">

                <li class="nav-item mb-3">
                    <a href="<?= BASE_URL; ?>/logout.php" class="nav-link text-danger fw-semibold">
                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>