<?php

declare(strict_types=1);

use Jaguata\Helpers\Session;

$usuarioNombre = Session::getUsuarioNombre() ?? 'Paseador';
$rolUsuario    = Session::getUsuarioRol() ?? 'paseador';
$baseFeatures  = BASE_URL . "/features/{$rolUsuario}";
$currentFile   = basename($_SERVER['PHP_SELF']);
?>

<!-- ✅ TOPBAR MOBILE -->
<div class="topbar-mobile d-lg-none">
    <div class="d-flex align-items-center gap-2 fw-semibold">
        <i class="fas fa-paw"></i> Jaguata
    </div>
    <button id="toggleSidebar" aria-label="Abrir menú">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- ✅ OVERLAY -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside id="sidebar" class="sidebar">
    <div class="sidebar-inner">

        <!-- HEADER -->
        <div class="text-center px-3 mb-3 pt-3">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png"
                alt="Jaguata"
                width="70"
                class="rounded-circle border border-light p-1 mb-2">

            <h6 class="text-white fw-semibold mb-0">
                Hola, <?= htmlspecialchars($usuarioNombre); ?> 👋
            </h6>
            <small class="text-light-50">Panel del Paseador</small>

            <hr class="text-secondary opacity-50 w-100">
        </div>

        <!-- MENU SCROLL -->
        <div class="sidebar-menu-scroll">
            <ul class="nav nav-pills flex-column mb-auto px-2" id="sidebarMenu">

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Dashboard.php"
                        class="nav-link <?= $currentFile === 'Dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-home me-2"></i>Inicio
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Solicitudes.php"
                        class="nav-link <?= $currentFile === 'Solicitudes.php' ? 'active' : '' ?>">
                        <i class="fas fa-envelope-open-text me-2"></i>Solicitudes
                    </a>
                </li>

                <?php $filesPaseos = ['MisPaseos.php']; ?>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPaseos) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#submenuPaseos"
                        aria-expanded="<?= in_array($currentFile, $filesPaseos) ? 'true' : 'false' ?>">
                        <span><i class="fas fa-dog me-2"></i>Paseos</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesPaseos) ? 'show' : '' ?>"
                        id="submenuPaseos" data-bs-parent="#sidebarMenu">
                        <a href="<?= $baseFeatures; ?>/MisPaseos.php"
                            class="nav-link ps-5 <?= $currentFile === 'MisPaseos.php' ? 'active' : '' ?>">
                            <i class="fas fa-walking me-2"></i>Mis Paseos
                        </a>
                    </div>
                </li>

                <?php $filesPagos = ['Pagos.php']; ?>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPagos) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#submenuPagos"
                        aria-expanded="<?= in_array($currentFile, $filesPagos) ? 'true' : 'false' ?>">
                        <span><i class="fas fa-wallet me-2"></i>Remuneraciones</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesPagos) ? 'show' : '' ?>"
                        id="submenuPagos" data-bs-parent="#sidebarMenu">
                        <a href="<?= $baseFeatures; ?>/Pagos.php"
                            class="nav-link ps-5 <?= $currentFile === 'Pagos.php' ? 'active' : '' ?>">
                            <i class="fas fa-money-bill-wave me-2"></i>Pagos
                        </a>
                    </div>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Notificaciones.php"
                        class="nav-link <?= $currentFile === 'Notificaciones.php' ? 'active' : '' ?>">
                        <i class="fas fa-bell me-2"></i>Notificaciones
                    </a>
                </li>

                <?php $filesPerfil = ['Perfil.php', 'EditarPerfil.php']; ?>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPerfil) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#submenuPerfil"
                        aria-expanded="<?= in_array($currentFile, $filesPerfil) ? 'true' : 'false' ?>">
                        <span><i class="fas fa-user me-2"></i>Perfil</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesPerfil) ? 'show' : '' ?>"
                        id="submenuPerfil" data-bs-parent="#sidebarMenu">
                        <a href="<?= $baseFeatures; ?>/Perfil.php"
                            class="nav-link ps-5 <?= $currentFile === 'Perfil.php' ? 'active' : '' ?>">
                            <i class="fas fa-id-card me-2"></i>Mi Perfil
                        </a>
                        <a href="<?= $baseFeatures; ?>/EditarPerfil.php"
                            class="nav-link ps-5 <?= $currentFile === 'EditarPerfil.php' ? 'active' : '' ?>">
                            <i class="fas fa-user-edit me-2"></i>Editar Perfil
                        </a>
                    </div>
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

<!-- ✅ JS Sidebar UNIFICADO -->
<script>
    (function() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const btn = document.getElementById('toggleSidebar');

        if (!sidebar || !backdrop || !btn) return;

        const open = () => {
            sidebar.classList.add('sidebar-open');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        };

        const close = () => {
            sidebar.classList.remove('sidebar-open');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        };

        btn.addEventListener('click', () => {
            sidebar.classList.contains('sidebar-open') ? close() : open();
        });

        backdrop.addEventListener('click', close);

        sidebar.addEventListener('click', (e) => {
            const a = e.target.closest('a.nav-link');
            if (!a) return;
            if (window.matchMedia('(max-width: 992px)').matches) close();
        });
    })();
</script>