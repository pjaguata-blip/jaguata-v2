<?php

declare(strict_types=1);

use Jaguata\Helpers\Session;

$usuarioNombre = Session::getUsuarioNombre() ?? 'Paseador';
$rolUsuario = Session::getUsuarioRol() ?? 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolUsuario}";
$currentFile = basename($_SERVER['PHP_SELF']);

?>

<aside id="sidebar" class="sidebar">
    <div class="sidebar-scroll d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white">

        <!-- Logo -->
        <div class="text-center mb-4 w-100">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png"
                alt="Jaguata"
                width="60"
                class="rounded-circle border border-light p-1 mb-2">
            <h6 class="text-white mb-0">Hola, <?= htmlspecialchars($usuarioNombre); ?> 👋</h6>
            <hr class="text-secondary opacity-50 w-100">
        </div>

        <!-- Menú -->
        <ul class="nav nav-pills flex-column mb-auto w-100" id="sidebarMenu">

            <li class="nav-item">
                <a href="<?= $baseFeatures; ?>/Dashboard.php"
                    class="nav-link text-white <?= $currentFile === 'Dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home me-2"></i>Inicio
                </a>
            </li>

            <li class="nav-item">
                <a href="<?= $baseFeatures; ?>/Estadisticas.php"
                    class="nav-link text-white <?= $currentFile === 'Estadisticas.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line me-2"></i>Estadísticas
                </a>
            </li>

            <li class="nav-item">
                <a href="<?= $baseFeatures; ?>/Solicitudes.php"
                    class="nav-link text-white <?= $currentFile === 'Solicitudes.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope-open-text me-2"></i>Solicitudes
                </a>
            </li>

            <!-- Submenú Paseos -->
            <?php $filesPaseos = ['MisPaseos.php']; ?>
            <li class="nav-item">
                <button class="nav-link text-white w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPaseos) ? 'active' : '' ?>"
                    data-bs-toggle="collapse"
                    data-bs-target="#submenuPaseos"
                    aria-expanded="<?= in_array($currentFile, $filesPaseos) ? 'true' : 'false' ?>">
                    <span><i class="fas fa-dog me-2"></i>Paseos</span>
                    <i class="fas fa-chevron-down small"></i>
                </button>

                <div class="collapse <?= in_array($currentFile, $filesPaseos) ? 'show' : '' ?>"
                    id="submenuPaseos"
                    data-bs-parent="#sidebarMenu">
                    <a href="<?= $baseFeatures; ?>/MisPaseos.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'MisPaseos.php' ? 'active' : '' ?>">
                        <i class="fas fa-walking me-2"></i>Mis Paseos
                    </a>
                </div>
            </li>

            <!-- Submenú Remuneraciones -->
            <?php $filesPagos = ['Pagos.php']; ?>
            <li class="nav-item">
                <button class="nav-link text-white w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPagos) ? 'active' : '' ?>"
                    data-bs-toggle="collapse"
                    data-bs-target="#submenuPagos"
                    aria-expanded="<?= in_array($currentFile, $filesPagos) ? 'true' : 'false' ?>">
                    <span><i class="fas fa-wallet me-2"></i>Remuneraciones</span>
                    <i class="fas fa-chevron-down small"></i>
                </button>

                <div class="collapse <?= in_array($currentFile, $filesPagos) ? 'show' : '' ?>"
                    id="submenuPagos"
                    data-bs-parent="#sidebarMenu">
                    <a href="<?= $baseFeatures; ?>/Pagos.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'Pagos.php' ? 'active' : '' ?>">
                        <i class="fas fa-money-bill-wave me-2"></i>Pagos
                    </a>
                </div>
            </li>

            <li class="nav-item">
                <a href="<?= $baseFeatures; ?>/Notificaciones.php"
                    class="nav-link text-white <?= $currentFile === 'Notificaciones.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell me-2"></i>Notificaciones
                </a>
            </li>

            <!-- Submenú Perfil -->
            <?php $filesPerfil = ['Perfil.php', 'EditarPerfil.php', 'Disponibilidad.php']; ?>
            <li class="nav-item">
                <button class="nav-link text-white w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPerfil) ? 'active' : '' ?>"
                    data-bs-toggle="collapse"
                    data-bs-target="#submenuPerfil"
                    aria-expanded="<?= in_array($currentFile, $filesPerfil) ? 'true' : 'false' ?>">
                    <span><i class="fas fa-user me-2"></i>Perfil</span>
                    <i class="fas fa-chevron-down small"></i>
                </button>

                <div class="collapse <?= in_array($currentFile, $filesPerfil) ? 'show' : '' ?>"
                    id="submenuPerfil"
                    data-bs-parent="#sidebarMenu">
                    <a href="<?= $baseFeatures; ?>/Perfil.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'Perfil.php' ? 'active' : '' ?>">
                        <i class="fas fa-id-card me-2"></i>Mi Perfil
                    </a>

                    <a href="<?= $baseFeatures; ?>/EditarPerfil.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'EditarPerfil.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-edit me-2"></i>Editar Perfil
                    </a>

                    <a href="<?= $baseFeatures; ?>/Disponibilidad.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'Disponibilidad.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-check me-2"></i>Disponibilidad
                    </a>
                </div>
            </li>

            <li class="nav-item">
                <a href="<?= $baseFeatures; ?>/Configuracion.php"
                    class="nav-link text-white <?= $currentFile === 'Configuracion.php' ? 'active' : '' ?>">
                    <i class="fas fa-cogs me-2"></i>Configuraciones
                </a>
            </li>

            <li>
                <hr class="text-secondary opacity-50 w-100">
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL; ?>/logout.php"
                    class="nav-link text-danger fw-semibold">
                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                </a>
            </li>

        </ul>
    </div>
</aside>