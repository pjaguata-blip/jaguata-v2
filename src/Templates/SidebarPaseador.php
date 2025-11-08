<?php

declare(strict_types=1);

use Jaguata\Helpers\Session;

$usuarioNombre = Session::getUsuarioNombre() ?? 'Paseador';
$rolUsuario = Session::getUsuarioRol() ?? 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolUsuario}";
?>

<aside class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar">
    <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white min-vh-100">

        <!-- Logo y nombre -->
        <div class="text-center mb-4 w-100">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png"
                alt="Jaguata"
                width="60"
                class="rounded-circle border border-light p-1 mb-2">
            <h6 class="text-white mb-0">Hola, <?= htmlspecialchars($usuarioNombre); ?> 👋</h6>
            <hr class="text-secondary opacity-50 w-100">
        </div>

        <!-- Navegación -->
        <ul class="nav nav-pills flex-column mb-auto w-100">

            <li class="nav-item w-100">
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="nav-link text-white px-3 py-2">
                    <i class="fas fa-home me-2"></i>Inicio
                </a>
            </li>

            <li class="nav-item w-100">
                <a href="<?= $baseFeatures; ?>/Estadisticas.php" class="nav-link text-white px-3 py-2">
                    <i class="fas fa-chart-line me-2"></i>Estadísticas
                </a>
            </li>

            <li class="nav-item w-100">
                <a href="<?= $baseFeatures; ?>/Solicitudes.php" class="nav-link text-white px-3 py-2">
                    <i class="fas fa-envelope-open-text me-2"></i>Solicitudes
                </a>
            </li>

            <!-- Submenú: Paseos -->
            <li class="nav-item w-100">
                <a class="nav-link text-white px-3 py-2 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuPaseos" role="button" aria-expanded="false">
                    <span><i class="fas fa-dog me-2"></i>Paseos</span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse ps-4" id="submenuPaseos">
                    <a href="<?= $baseFeatures; ?>/MisPaseos.php" class="nav-link text-white px-3 py-2">
                        <i class="fas fa-walking me-2"></i>Mis Paseos
                    </a>
                </div>
            </li>

            <!-- Submenú: Remuneraciones -->
            <li class="nav-item w-100">
                <a class="nav-link text-white px-3 py-2 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuPagos" role="button" aria-expanded="false">
                    <span><i class="fas fa-wallet me-2"></i>Remuneraciones</span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse ps-4" id="submenuPagos">
                    <a href="<?= $baseFeatures; ?>/Pagos.php" class="nav-link text-white px-3 py-2">
                        <i class="fas fa-money-bill-wave me-2"></i>Pagos
                    </a>

                </div>
            </li>

            <li class="nav-item w-100">
                <a href="<?= $baseFeatures; ?>/Notificaciones.php" class="nav-link text-white px-3 py-2">
                    <i class="fas fa-bell me-2"></i>Notificaciones
                </a>
            </li>

            <!-- Submenú: Perfil -->
            <li class="nav-item w-100">
                <a class="nav-link text-white px-3 py-2 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuPerfil" role="button" aria-expanded="false">
                    <span><i class="fas fa-user me-2"></i>Perfil</span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse ps-4" id="submenuPerfil">
                    <a href="<?= $baseFeatures; ?>/MiPerfil.php" class="nav-link text-white px-3 py-2">
                        <i class="fas fa-id-card me-2"></i>Mi Perfil
                    </a>
                    <a href="<?= $baseFeatures; ?>/EditarPerfil.php" class="nav-link text-white px-3 py-2">
                        <i class="fas fa-user-edit me-2"></i>Editar Perfil
                    </a>
                    <a href="<?= $baseFeatures; ?>/Disponibilidad.php" class="nav-link text-white px-3 py-2">
                        <i class="fas fa-calendar-check me-2"></i>Disponibilidad
                    </a>
                </div>
            </li>

            <li class="nav-item w-100">
                <a href="<?= $baseFeatures; ?>/Configuracion.php" class="nav-link text-white px-3 py-2">
                    <i class="fas fa-cogs me-2"></i>Configuraciones
                </a>
            </li>

            <li class="nav-item w-100">
                <a href="<?= $baseFeatures; ?>/Soporte_paseador.php" class="nav-link text-white px-3 py-2">
                    <i class="fas fa-headset me-2"></i>Soporte
                </a>
            </li>

            <li>
                <hr class="text-secondary opacity-50 w-100">
            </li>

            <li class="nav-item w-100">
                <a href="<?= BASE_URL; ?>/logout.php" class="nav-link text-danger fw-semibold px-3 py-2">
                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                </a>
            </li>

        </ul>
    </div>
</aside>