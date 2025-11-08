<?php

declare(strict_types=1);

use Jaguata\Helpers\Session;

$usuarioNombre = Session::getUsuarioNombre() ?? 'Administrador';
$rolUsuario = Session::getUsuarioRol() ?? 'admin';
$baseFeatures = BASE_URL . "/features/{$rolUsuario}";
$currentFile = basename($_SERVER['PHP_SELF']);
?>

<aside class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar">
    <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white min-vh-100">

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
                <a href="<?= $baseFeatures; ?>/Usuarios.php"
                    class="nav-link text-white <?= $currentFile === 'Usuarios.php' ? 'active' : '' ?>">
                    <i class="fas fa-users me-2"></i>Usuarios
                </a>
            </li>

            <li class="nav-item">
                <a href="<?= $baseFeatures; ?>/Reportes.php"
                    class="nav-link text-white <?= $currentFile === 'Reportes.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line me-2"></i>Estadísticas
                </a>
            </li>

            <li class="nav-item">
                <a href="<?= $baseFeatures; ?>/Notificaciones.php"
                    class="nav-link text-white <?= $currentFile === 'Notificaciones.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell me-2"></i>Notificaciones
                </a>
            </li>

            <!-- Submenú: Servicios -->
            <li class="nav-item">
                <button class="nav-link text-white w-100 text-start d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" data-bs-target="#submenuServicios" aria-expanded="<?= in_array($currentFile, ['Paseos.php', 'Servicios.php']) ? 'true' : 'false' ?>">
                    <span><i class="fas fa-dog me-2"></i>Servicios</span>
                    <i class="fas fa-chevron-down small"></i>
                </button>
                <div class="collapse <?= in_array($currentFile, ['Paseos.php', 'Servicios.php']) ? 'show' : '' ?>"
                    id="submenuServicios" data-bs-parent="#sidebarMenu">
                    <a href="<?= $baseFeatures; ?>/Paseos.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'Paseos.php' ? 'active' : '' ?>">
                        <i class="fas fa-walking me-2"></i>Paseos
                    </a>
                    <a href="<?= $baseFeatures; ?>/Servicios.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'Servicios.php' ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-list me-2"></i>Servicios
                    </a>
                </div>
            </li>

            <!-- Submenú: Pagos -->
            <li class="nav-item">
                <button class="nav-link text-white w-100 text-start d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" data-bs-target="#submenuPagos" aria-expanded="<?= in_array($currentFile, ['PagosEfectuados.php', 'PagosPendientes.php']) ? 'true' : 'false' ?>">
                    <span><i class="fas fa-wallet me-2"></i>Pagos</span>
                    <i class="fas fa-chevron-down small"></i>
                </button>
                <div class="collapse <?= in_array($currentFile, ['PagosEfectuados.php', 'PagosPendientes.php']) ? 'show' : '' ?>"
                    id="submenuPagos" data-bs-parent="#sidebarMenu">
                    <a href="<?= $baseFeatures; ?>/PagosEfectuados.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'PagosEfectuados.php' ? 'active' : '' ?>">
                        <i class="fas fa-check-circle me-2"></i>Pagos Efectuados
                    </a>
                    <a href="<?= $baseFeatures; ?>/PagosPendientes.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'PagosPendientes.php' ? 'active' : '' ?>">
                        <i class="fas fa-clock me-2"></i>Pagos Pendientes
                    </a>
                </div>
            </li>

            <li class="nav-item">
                <a href="<?= $baseFeatures; ?>/Auditoria.php"
                    class="nav-link text-white <?= $currentFile === 'Auditoria.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield me-2"></i>Auditoría
                </a>
            </li>

            <!-- Submenú: Configuraciones -->
            <li class="nav-item">
                <button class="nav-link text-white w-100 text-start d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" data-bs-target="#submenuConfig" aria-expanded="<?= in_array($currentFile, ['Configuracion.php', 'RolesPermisos.php']) ? 'true' : 'false' ?>">
                    <span><i class="fas fa-cogs me-2"></i>Configuraciones</span>
                    <i class="fas fa-chevron-down small"></i>
                </button>
                <div class="collapse <?= in_array($currentFile, ['Configuracion.php', 'RolesPermisos.php']) ? 'show' : '' ?>"
                    id="submenuConfig" data-bs-parent="#sidebarMenu">
                    <a href="<?= $baseFeatures; ?>/Configuracion.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'Configuracion.php' ? 'active' : '' ?>">
                        <i class="fas fa-sliders-h me-2"></i>Configuración General
                    </a>
                    <a href="<?= $baseFeatures; ?>/RolesPermisos.php"
                        class="nav-link text-white ps-5 <?= $currentFile === 'RolesPermisos.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-lock me-2"></i>Roles y Permisos
                    </a>
                </div>
            </li>

            <li class="nav-item">
                <a href="<?= $baseFeatures; ?>/Soporte.php"
                    class="nav-link text-white <?= $currentFile === 'Soporte.php' ? 'active' : '' ?>">
                    <i class="fas fa-headset me-2"></i>Soporte
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