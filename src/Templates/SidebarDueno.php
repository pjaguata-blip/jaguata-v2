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
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPaseos) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#menuPaseos"
                        aria-expanded="<?= in_array($currentFile, $filesPaseos) ? 'true' : 'false' ?>">
                        <span><i class="fas fa-walking me-2"></i>Reservas</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesPaseos) ? 'show' : '' ?>" id="menuPaseos" data-bs-parent="#sidebarMenu">
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
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPagos) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#menuPagos"
                        aria-expanded="<?= in_array($currentFile, $filesPagos) ? 'true' : 'false' ?>">
                        <span><i class="fas fa-wallet me-2"></i>Pagos</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesPagos) ? 'show' : '' ?>" id="menuPagos" data-bs-parent="#sidebarMenu">
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
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesMascotas) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#menuMascotas"
                        aria-expanded="<?= in_array($currentFile, $filesMascotas) ? 'true' : 'false' ?>">
                        <span><i class="fas fa-paw me-2"></i>Mascotas</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesMascotas) ? 'show' : '' ?>" id="menuMascotas" data-bs-parent="#sidebarMenu">
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
                    <button class="nav-link w-100 text-start d-flex justify-content-between align-items-center <?= in_array($currentFile, $filesPerfil) ? 'active' : '' ?>"
                        data-bs-toggle="collapse" data-bs-target="#menuPerfil"
                        aria-expanded="<?= in_array($currentFile, $filesPerfil) ? 'true' : 'false' ?>">
                        <span><i class="fas fa-user-circle me-2"></i>Perfil</span>
                        <i class="fas fa-chevron-down small"></i>
                    </button>

                    <div class="collapse <?= in_array($currentFile, $filesPerfil) ? 'show' : '' ?>" id="menuPerfil" data-bs-parent="#sidebarMenu">
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

        if (!sidebar || !backdrop || !btn){
            console.log("return");
            return;
        } 
        console.log("hola evelyn");
        const open = () => {
            console.log("hola",sidebar);
            console.log(" ejecuta abrir");
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
            console.log("abrir o cerrar click sidebar");
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