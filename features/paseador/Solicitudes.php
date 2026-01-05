<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// === Inicialización y seguridad ===
AppConfig::init();
$authController = new AuthController();
$authController->checkRole('paseador');

// === Variables base ===
$rolMenu      = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$paseadorId   = (int)(Session::getUsuarioId() ?? 0);

// === Controlador ===
$paseoController = new PaseoController();

// === Datos reales ===
// Obtener solo solicitudes en estado "pendiente/solicitado" para este paseador
$solicitudes = $paseoController->getSolicitudesPendientes($paseadorId) ?? [];

// Helper para escapar
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de Paseos - Paseador | Jaguata</title>

    <!-- CSS global Jaguata -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <!-- Bootstrap y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-2" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <!-- Sidebar unificado -->
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- Contenido -->
        <main class="content bg-light">
            <div class="container-fluid py-1">

                <!-- Header usando estilos globales -->
                <div class="header-box header-dashboard mb-2">
                    <div>
                        <h1 class="h4 mb-1">
                            <i class="fas fa-envelope-open-text me-2"></i>
                            Solicitudes de paseos
                        </h1>
                        <p class="mb-0 text-white-50">
                            Aceptá o rechazá las solicitudes que los dueños te envían.
                        </p>
                    </div>
                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <!-- Mensajes de estado -->
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-1"></i> <?= h($_SESSION['success']); ?>
                        <?php unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-triangle-exclamation me-1"></i> <?= h($_SESSION['error']); ?>
                        <?php unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($solicitudes)): ?>
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <span>No tenés solicitudes pendientes en este momento.</span>
                    </div>
                <?php else: ?>
                    <div class="card jag-card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-paw me-2"></i>Solicitudes pendientes
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mascota</th>
                                            <th>Dueño</th>
                                            <th>Fecha</th>
                                            <th>Duración</th>
                                            <th>Precio</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($solicitudes as $s):
                                            $paseoId  = (int)($s['paseo_id'] ?? 0);
                                            $duracion = $s['duracion'] ?? ($s['duracion_min'] ?? 0);
                                        ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-paw text-success me-1"></i>
                                                    <?= h($s['nombre_mascota'] ?? '-') ?>
                                                </td>
                                                <td>
                                                    <i class="fas fa-user text-secondary me-1"></i>
                                                    <?= h($s['nombre_dueno'] ?? '-') ?>
                                                </td>
                                                <td>
                                                    <?= isset($s['inicio'])
                                                        ? date('d/m/Y H:i', strtotime($s['inicio']))
                                                        : '—' ?>
                                                </td>
                                                <td><?= (int)$duracion ?> min</td>
                                                <td>₲<?= number_format((float)($s['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                                <td class="text-end">
                                                    <!-- Aceptar -->
                                                    <form action="AccionPaseo.php" method="post" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                        <input type="hidden" name="accion" value="confirmar">
                                                        <input type="hidden" name="redirect_to" value="Solicitudes.php">
                                                        <button type="submit" class="btn btn-sm btn-success"
                                                            onclick="return confirm('¿Aceptar esta solicitud?');"
                                                            title="Aceptar">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <!-- Rechazar -->
                                                    <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                        <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                        <input type="hidden" name="accion" value="cancelar">
                                                        <input type="hidden" name="redirect_to" value="Solicitudes.php">
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('¿Rechazar esta solicitud?');"
                                                            title="Rechazar">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <footer class="text-center text-muted small mt-4">
                    &copy; <?= date('Y'); ?> Jaguata — Panel de Paseador
                </footer>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>