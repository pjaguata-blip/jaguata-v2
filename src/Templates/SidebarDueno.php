<?php

declare(strict_types=1);

use Jaguata\Helpers\Session;

$usuarioNombre = Session::getUsuarioNombre() ?? 'Dueño/a';
$rolUsuario    = Session::getUsuarioRol() ?? 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolUsuario}";
$currentFile   = basename($_SERVER['PHP_SELF']);
?>

<!-- ✅ TOPBAR MOBILE -->
<div class="topbar-mobile d-lg-none">
    <div class="d-flex align-items-center gap-2 fw-semibold">
        <i class="fas fa-paw"></i> Jaguata
    </div>

    <!-- ✅ YA NO usamos id -->
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
        <img src="<?= ASSETS_URL; ?>/images/logojag.png"
        alt="Jaguata"
        width="70"
        class="rounded-circle border border-light p-1 mb-2">

    <h6 class="text-white fw-semibold mb-0">
        Hola, <?= htmlspecialchars($usuarioNombre, ENT_QUOTES, 'UTF-8'); ?> 👋
    </h6>
    <small class="text-light-50">Panel del Dueño</small>

    <hr class="text-secondary opacity-50 w-100">
</div>


        <!-- MENU SCROLL -->
        <div class="sidebar-menu-scroll">
            <ul class="nav nav-pills flex-column mb-auto px-2" id="sidebarMenu">

                <li class="nav-item">
                    <a class="nav-link <?= ($currentFile === 'Dashboard.php') ? 'active' : '' ?>"
                        href="<?= $baseFeatures; ?>/Dashboard.php">
                        <i class="fas fa-home me-2"></i>Inicio
                    </a>
                </li>

                <!-- RESERVAS -->
                <?php $filesPaseos = ['SolicitarPaseo.php', 'MisPaseos.php', 'BuscarPaseadores.php']; ?>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPaseos, true) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#menuPaseos"
                        aria-expanded="<?= in_array($currentFile, $filesPaseos, true) ? 'true' : 'false' ?>"
                        type="button">
                        <span><i class="fas fa-walking me-2"></i>Reservas</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesPaseos, true) ? 'show' : '' ?>" id="menuPaseos" data-bs-parent="#sidebarMenu">
                        <a class="nav-link ps-5 <?= ($currentFile === 'SolicitarPaseo.php') ? 'active' : '' ?>"
                            href="<?= $baseFeatures; ?>/SolicitarPaseo.php">
                            Solicitar Paseo
                        </a>
                        <a class="nav-link ps-5 <?= ($currentFile === 'MisPaseos.php') ? 'active' : '' ?>"
                            href="<?= $baseFeatures; ?>/MisPaseos.php">
                            Mis Paseos
                        </a>
                        <a class="nav-link ps-5 <?= ($currentFile === 'BuscarPaseadores.php') ? 'active' : '' ?>"
                            href="<?= $baseFeatures; ?>/BuscarPaseadores.php">
                            Buscar Paseadores
                        </a>
                    </div>
                </li>

                <!-- PAGOS -->
                <?php $filesPagos = ['GastosTotales.php', 'Pagos.php']; ?>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPagos, true) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#menuPagos"
                        aria-expanded="<?= in_array($currentFile, $filesPagos, true) ? 'true' : 'false' ?>"
                        type="button">
                        <span><i class="fas fa-wallet me-2"></i>Pagos</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesPagos, true) ? 'show' : '' ?>" id="menuPagos" data-bs-parent="#sidebarMenu">
                        <a class="nav-link ps-5 <?= ($currentFile === 'GastosTotales.php') ? 'active' : '' ?>"
                            href="<?= $baseFeatures; ?>/GastosTotales.php">
                            Gastos Totales
                        </a>
                        <a class="nav-link ps-5 <?= ($currentFile === 'Pagos.php') ? 'active' : '' ?>"
                            href="<?= $baseFeatures; ?>/Pagos.php">
                            Pagos
                        </a>
                    </div>
                </li>

                <!-- MASCOTAS -->
                <?php $filesMascotas = ['MisMascotas.php', 'EditarMascota.php', 'AgregarMascota.php']; ?>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesMascotas, true) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#menuMascotas"
                        aria-expanded="<?= in_array($currentFile, $filesMascotas, true) ? 'true' : 'false' ?>"
                        type="button">
                        <span><i class="fas fa-paw me-2"></i>Mascotas</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesMascotas, true) ? 'show' : '' ?>" id="menuMascotas" data-bs-parent="#sidebarMenu">
                        <a class="nav-link ps-5 <?= ($currentFile === 'MisMascotas.php') ? 'active' : '' ?>"
                            href="<?= $baseFeatures; ?>/MisMascotas.php">
                            Mis Mascotas
                        </a>
                        <a class="nav-link ps-5 <?= ($currentFile === 'AgregarMascota.php') ? 'active' : '' ?>"
                            href="<?= $baseFeatures; ?>/AgregarMascota.php">
                            Agregar Mascota
                        </a>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= ($currentFile === 'Notificaciones.php') ? 'active' : '' ?>"
                        href="<?= $baseFeatures; ?>/Notificaciones.php">
                        <i class="fas fa-bell me-2"></i>Notificaciones
                    </a>
                </li>

                <!-- PERFIL -->
                <?php $filesPerfil = ['MiPerfil.php', 'EditarPerfil.php']; ?>
                <li class="nav-item">
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPerfil, true) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#menuPerfil"
                        aria-expanded="<?= in_array($currentFile, $filesPerfil, true) ? 'true' : 'false' ?>"
                        type="button">
                        <span><i class="fas fa-user-circle me-2"></i>Perfil</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesPerfil, true) ? 'show' : '' ?>" id="menuPerfil" data-bs-parent="#sidebarMenu">
                        <a class="nav-link ps-5 <?= ($currentFile === 'MiPerfil.php') ? 'active' : '' ?>"
                            href="<?= $baseFeatures; ?>/MiPerfil.php">
                            Mi Perfil
                        </a>
                        <a class="nav-link ps-5 <?= ($currentFile === 'EditarPerfil.php') ? 'active' : '' ?>"
                            href="<?= $baseFeatures; ?>/EditarPerfil.php">
                            Editar Perfil
                        </a>
                    </div>
                </li>
                <?php $filesPuntos = ['MisPuntos.php', 'Recompensas.php']; ?>
<li class="nav-item">
    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPuntos, true) ? 'active' : '' ?>"
        data-bs-toggle="collapse" data-bs-target="#menuPuntos"
        aria-expanded="<?= in_array($currentFile, $filesPuntos, true) ? 'true' : 'false' ?>"
        type="button">
        <span><i class="fas fa-star me-2"></i>Puntos & Recompensas</span>
        <i class="fas fa-chevron-down small"></i>
    </button>

    <div class="collapse <?= in_array($currentFile, $filesPuntos, true) ? 'show' : '' ?>" id="menuPuntos" data-bs-parent="#sidebarMenu">
        <a class="nav-link ps-5 <?= ($currentFile === 'MisPuntos.php') ? 'active' : '' ?>"
            href="<?= $baseFeatures; ?>/MisPuntos.php">
            Mis Puntos
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

<!-- ✅ JS Sidebar UNIFICADO (sin depender de ids duplicados) -->
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

    // ✅ cerrar al tocar un link en mobile
    sidebar.addEventListener('click', (e) => {
        const a = e.target.closest('a.nav-link');
        if (!a) return;
        if (isMobile()) close();
    });

    // ✅ si se agranda la pantalla, resetea overlay
    window.addEventListener('resize', () => {
        if (!isMobile()) close();
    });

    // ✅ ESC cierra
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
})();
</script>
