<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

// 🔹 Inicialización
AppConfig::init();

// 🔹 Datos del usuario y rol
$usuarioNombre = Session::getUsuarioNombre() ?? 'Usuario';
$rolUsuario = Session::getUsuarioRol() ?? 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolUsuario}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $titulo ?? 'Panel - Jaguata'; ?></title>

    <!-- Bootstrap y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- CSS global -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css?v=<?= time(); ?>" rel="stylesheet">
</head>

<body>
    <!-- Botón hamburguesa (solo móvil) -->
    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Logo Jaguata" width="60" class="mb-2">
                <h6 class="text-white mb-3">Hola, <?= htmlspecialchars($usuarioNombre); ?> 👋</h6>
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
                        <span><i class="fas fa-walking me-2"></i> Reservas / Paseos</span>
                        <i class="fas fa-chevron-down small"></i>
                    </a>
                    <ul class="collapse ps-3" id="menuPaseos">
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/SolicitarPaseo.php">Solicitar Paseo</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php">Mis Paseos</a></li>
                        <li>
                            <a class="nav-link collapsed d-flex justify-content-between align-items-center"
                                data-bs-toggle="collapse" href="#submenuSeguimiento" role="button" aria-expanded="false">
                                <span>Seguimiento</span>
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

                <!-- AYUDA -->
                <li>
                    <a class="nav-link" href="<?= $baseFeatures; ?>/AyudaDueno.php">
                        <i class="fas fa-circle-question me-2"></i> Ayuda
                    </a>
                </li>

                <!-- CERRAR SESIÓN -->
                <li>
                    <a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión
                    </a>
                </li>

            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h1 class="h4 mb-1">¡Bienvenido, <?= htmlspecialchars($usuarioNombre); ?>!</h1>
                    <p class="mb-0">Gestioná tus mascotas, paseos y notificaciones 🐾</p>
                </div>
                <i class="fas fa-dog fa-3x opacity-75"></i>
            </div>