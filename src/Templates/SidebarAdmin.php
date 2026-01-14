<?php

declare(strict_types=1);

use Jaguata\Helpers\Session;

$usuarioNombre = Session::getUsuarioNombre() ?? 'Administrador';
$rolUsuario    = Session::getUsuarioRol() ?? 'admin';
$baseFeatures  = BASE_URL . "/features/{$rolUsuario}";
$currentFile   = basename($_SERVER['PHP_SELF']);
?>

<!-- ✅ TOPBAR MOBILE -->
<div class="topbar-mobile d-lg-none">
    <div class="d-flex align-items-center gap-2 fw-semibold">
        <i class="fas fa-paw"></i> Jaguata
    </div>

    <!-- ✅ SIN ID: unificado -->
    <button type="button" data-toggle="sidebar" aria-label="Abrir menú">
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
                Hola, <?= htmlspecialchars($usuarioNombre, ENT_QUOTES, 'UTF-8'); ?> 👋
            </h6>
            <small class="text-light-50">Administrador General</small>

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
                    <a href="<?= $baseFeatures; ?>/Usuarios.php"
                        class="nav-link <?= $currentFile === 'Usuarios.php' ? 'active' : '' ?>">
                        <i class="fas fa-users me-2"></i>Usuarios
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Mascotas.php"
                        class="nav-link <?= $currentFile === 'Mascotas.php' ? 'active' : '' ?>">
                        <i class="fas fa-dog me-2"></i>Mascotas
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $baseFeatures; ?>/Paseos.php"
                        class="nav-link <?= $currentFile === 'Paseos.php' ? 'active' : '' ?>">
                        <i class="fas fa-walking me-2"></i>Paseos
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
                    <a href="<?= $baseFeatures; ?>/ReporteGanancias.php"
                        class="nav-link <?= $currentFile === 'ReporteGanancias.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie me-2"></i>Reporte de Ganancias
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

<!-- ✅ JS Sidebar UNIFICADO (igual al de Paseador/Dueño) -->
<script>
(function () {
    const sidebar   = document.getElementById('sidebar');
    const backdrop  = document.getElementById('sidebarBackdrop');
    const toggles   = document.querySelectorAll('[data-toggle="sidebar"]');

    if (!sidebar || !backdrop || !toggles.length) {
        console.log('Sidebar init: faltan nodos', {sidebar: !!sidebar, backdrop: !!backdrop, toggles: toggles.length});
        return;
    }

    const isMobile = () => window.matchMedia('(max-width: 992px)').matches;

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

    const toggle = () => sidebar.classList.contains('sidebar-open') ? close() : open();

    toggles.forEach(btn => btn.addEventListener('click', toggle));
    backdrop.addEventListener('click', close);

    sidebar.addEventListener('click', (e) => {
        const a = e.target.closest('a.nav-link');
        if (!a) return;
        if (isMobile()) close();
    });

    window.addEventListener('resize', () => {
        if (!isMobile()) close();
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
})();
</script>
