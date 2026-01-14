<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

/* ✅ Suscripción */
require_once __DIR__ . '/../../src/Models/Suscripcion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;
use Jaguata\Models\Suscripcion;

AppConfig::init();

/* 🔒 Seguridad */
$authController = new AuthController();
$authController->checkRole('paseador');

/* 🔒 BLOQUEO POR ESTADO */
if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

/* Base */
$rolMenu      = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$paseadorId   = (int)(Session::getUsuarioId() ?? 0);

/* Datos */
$paseoController = new PaseoController();
$solicitudes     = $paseoController->getSolicitudesPendientes($paseadorId) ?? [];

/* ✅ Suscripción */
$tieneProActiva = false;
$subEstado = null;
$subFin    = null;

try {
    if ($paseadorId > 0) {
        $subModel = new Suscripcion();
        if (method_exists($subModel, 'marcarVencidas')) $subModel->marcarVencidas();

        $ultima = method_exists($subModel, 'getUltimaPorPaseador')
            ? $subModel->getUltimaPorPaseador($paseadorId)
            : null;

        if ($ultima) {
            $subEstado = strtolower(trim((string)($ultima['estado'] ?? '')));
            $subFin    = $ultima['fin'] ?? null;
            $tieneProActiva = ($subEstado === 'activa');
        }
    }
} catch (Throwable $e) {
    $tieneProActiva = false;
}

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

    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>

<body>
<div class="layout">
    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <main class="content bg-light">
        <div class="container-fluid py-2">

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

            <!-- ✅ ALERTA SUSCRIPCIÓN -->
            <?php if (!$tieneProActiva): ?>
                <div class="alert alert-warning border d-flex align-items-start gap-3 mb-3">
                    <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold">Suscripción PRO requerida para aceptar paseos</div>
                        <div class="small mt-1">
                            Estado actual:
                            <span class="badge <?= $subEstado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                <?= $subEstado ? strtoupper($subEstado) : 'SIN SUSCRIPCIÓN' ?>
                            </span>
                            <?php if ($subFin): ?>
                                <span class="text-muted ms-2">Vence: <?= date('d/m/Y H:i', strtotime($subFin)); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <a href="<?= $baseFeatures; ?>/Suscripcion.php" class="btn btn-warning btn-sm fw-semibold">
                                <i class="fa-solid fa-crown me-1"></i> Activar / Renovar
                            </a>
                            <a href="<?= $baseFeatures; ?>/Suscripcion.php#subir-comprobante" class="btn btn-outline-dark btn-sm">
                                <i class="fa-solid fa-upload me-1"></i> Subir comprobante
                            </a>
                        </div>

                        <div class="small text-muted mt-2">
                            * Mientras no esté activa, el botón “Aceptar” queda bloqueado.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Mensajes -->
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
                                        <td><i class="fas fa-paw text-success me-1"></i><?= h($s['nombre_mascota'] ?? '-') ?></td>
                                        <td><i class="fas fa-user text-secondary me-1"></i><?= h($s['nombre_dueno'] ?? '-') ?></td>
                                        <td><?= isset($s['inicio']) ? date('d/m/Y H:i', strtotime($s['inicio'])) : '—' ?></td>
                                        <td><?= (int)$duracion ?> min</td>
                                        <td>₲<?= number_format((float)($s['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                        <td class="text-end">

                                            <!-- ✅ Aceptar (bloqueado si no PRO) -->
                                            <form action="AccionPaseo.php" method="post" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                <input type="hidden" name="accion" value="confirmar">
                                                <input type="hidden" name="redirect_to" value="Solicitudes.php">

                                                <button type="submit"
                                                    class="btn btn-sm btn-success <?= !$tieneProActiva ? 'disabled' : '' ?>"
                                                    <?= !$tieneProActiva ? 'disabled' : '' ?>
                                                    onclick="<?= !$tieneProActiva ? 'return false;' : "return confirm('¿Aceptar esta solicitud?');" ?>"
                                                    title="<?= !$tieneProActiva ? 'Requiere Suscripción PRO activa' : 'Aceptar' ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>

                                            <!-- Rechazar (SIEMPRE permitido) -->
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
<!-- ❌ quitamos toggleSidebar (ya lo maneja SidebarPaseador con data-toggle="sidebar") -->
</body>
</html>
