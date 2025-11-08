<?php

/**
 * Sidebar del rol DUEÑO 🐾
 * Estructura jerárquica completa, responsive y sin warnings.
 */

use Jaguata\Helpers\Session;

// 🔹 Fallbacks seguros (evita warnings si no vienen definidas)
if (!isset($usuarioNombre)) {
    $usuarioNombre = Session::getUsuarioNombre() ?? 'Dueño/a';
}

if (!isset($baseFeatures)) {
    $rol = Session::getUsuarioRol() ?? 'dueno';
    $baseFeatures = BASE_URL . "/features/{$rol}";
}
?>

<aside class="sidebar" id="sidebar">
    <div class="text-center mb-4">
        <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png"
            alt="Logo Jaguata" width="60" class="mb-2">
        <h6 class="text-white mb-3">
            Hola, <?= htmlspecialchars($usuarioNombre); ?> 👋
        </h6>
        <hr class="text-light">
    </div>

    <ul class="nav flex-column gap-1 px-2">

        <!-- INICIO -->
        <li>
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'Dashboard.php') ? 'active' : '' ?>"
                href="<?= $baseFeatures; ?>/Dashboard.php">
                <i class="fas fa-home me-2"></i> Inicio
            </a>
        </li>

        <!-- RESERVAS / PASEOS -->
        <li>
            <a class="nav-link collapsed d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse" href="#menuPaseos" role="button" aria-expanded="false">
                <span><i class="fas fa-walking me-2"></i> Reservas</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <ul class="collapse ps-3" id="menuPaseos">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/SolicitarPaseo.php">Solicitar Paseo</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php">Mis Paseos</a></li>
                <li>
                    <a class="nav-link collapsed d-flex justify-content-between align-items-center"
                        data-bs-toggle="collapse" href="#submenuSeguimiento" role="button" aria-expanded="false">
                        <span>Seguimiento de Paseos</span>
                        <i class="fas fa-chevron-down small"></i>
                    </a>
                    <ul class="collapse ps-3" id="submenuSeguimiento">
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosCompletos.php">Paseos Completos</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosPendientes.php">Paseos Pendientes</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosCancelados.php">Paseos Cancelados</a></li>
                    </ul>
                </li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/BuscarPaseadores.php">Buscar Paseadores</a></li>
            </ul>
        </li>

        <!-- PAGOS -->
        <li>
            <a class="nav-link collapsed d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse" href="#menuPagos" role="button" aria-expanded="false">
                <span><i class="fas fa-wallet me-2"></i> Pagos</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <ul class="collapse ps-3" id="menuPagos">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php">Gastos Totales</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Pagos.php">Pagos</a></li>
                <li>
                    <a class="nav-link collapsed d-flex justify-content-between align-items-center"
                        data-bs-toggle="collapse" href="#submenuComprobantes" role="button" aria-expanded="false">
                        <span>Comprobantes</span>
                        <i class="fas fa-chevron-down small"></i>
                    </a>
                    <ul class="collapse ps-3" id="submenuComprobantes">
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/Comprobante_pago.php">Comprobante de Pago</a></li>
                    </ul>
                </li>
            </ul>
        </li>

        <!-- NOTIFICACIONES -->
        <li>
            <a class="nav-link" href="<?= $baseFeatures; ?>/Notificaciones.php">
                <i class="fas fa-bell me-2"></i> Notificaciones
            </a>
        </li>

        <!-- MASCOTAS -->
        <li>
            <a class="nav-link collapsed d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse" href="#menuMascotas" role="button" aria-expanded="false">
                <span><i class="fas fa-paw me-2"></i> Mascotas</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <ul class="collapse ps-3" id="menuMascotas">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php">Mis Mascotas</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/EditarMascota.php">Editar Mascota</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/AgregarMascota.php">Agregar Mascota</a></li>
            </ul>
        </li>

        <!-- PERFIL -->
        <li>
            <a class="nav-link collapsed d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse" href="#menuPerfil" role="button" aria-expanded="false">
                <span><i class="fas fa-user-circle me-2"></i> Perfil</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <ul class="collapse ps-3" id="menuPerfil">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php">Mi Perfil</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">Editar Perfil</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPuntos.php">Mis Puntos</a></li>
            </ul>
        </li>

        <!-- Soporte -->
        <li>
            <a class="nav-link" href="<?= $baseFeatures; ?>/Soporte_dueno.php">
                <i class="fas fa-circle-question me-2"></i> Soporte
            </a>
        </li>

        <!-- CERRAR SESIÓN -->
        <li>
            <a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
            </a>
        </li>
    </ul>
</aside>