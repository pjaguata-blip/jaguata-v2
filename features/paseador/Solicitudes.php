<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
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
        if (method_exists($subModel, 'marcarVencidas')) {
            $subModel->marcarVencidas();
        }

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

/* Helpers */
function h($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function fmtGs($n): string
{
    return number_format((float)$n, 0, ',', '.');
}
function fmtFechaHora(?string $dt): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date('d/m/Y H:i', $ts) : h($dt);
}

/* Flash */
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de Paseos - Paseador | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height: 100%; }
        body { background: var(--gris-fondo, #f4f6f9); }

        /* ✅ Layout igual dashboards */
        main.main-content{
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            min-height: 100vh;
            padding: 24px;
            box-sizing: border-box;
        }
        @media (max-width: 768px){
            main.main-content{
                margin-left: 0 !important;
                width: 100% !important;
                margin-top: 0 !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        .pill {
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            padding:.25rem .65rem;
            border-radius:999px;
            font-size:.78rem;
            background: rgba(60, 98, 85, .10);
            color: var(--verde-jaguata, #3c6255);
            border: 1px solid rgba(60, 98, 85, .18);
            font-weight:700;
            white-space: nowrap;
        }
        .pill-warning{
            background: rgba(255,193,7,.12);
            color: #7a5a00;
            border-color: rgba(255,193,7,.35);
        }
        .pill-danger{
            background: rgba(220,53,69,.10);
            color:#842029;
            border-color: rgba(220,53,69,.25);
        }

        /* tabla linda */
        .table thead th{
            font-size: .85rem;
            color:#1f2937;
        }
        .table td{
            vertical-align: middle;
        }

        /* cards móvil (cuando no querés tabla) */
        .req-card{
            border: 1px solid rgba(15,23,42,.08);
            border-radius: 16px;
            background:#fff;
            padding: 14px 14px;
        }
        .req-title{
            font-weight: 800;
            color: #0f172a;
        }
        .req-meta{
            font-size:.9rem;
            color:#475569;
        }
        .btn-icon{
            width: 38px;
            height: 38px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius: 12px;
        }
    </style>
</head>

<body>
<?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

<main class="main-content">
    <div class="py-2">

        <!-- Header -->
        <div class="header-box header-dashboard mb-2 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-1">
                    <i class="fas fa-envelope-open-text me-2"></i>Solicitudes de paseos
                </h1>
                <p class="mb-0 text-white-50">
                    Aceptá o rechazá las solicitudes que los dueños te envían 🐾
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <!-- Flash -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <i class="fas fa-check-circle me-2"></i><?= h($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                <i class="fas fa-triangle-exclamation me-2"></i><?= h($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ✅ SUSCRIPCIÓN PRO (estilo section-card) -->
        <?php if (!$tieneProActiva): ?>
            <div class="section-card mb-3">
                <div class="section-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <i class="fa-solid fa-crown me-2"></i> Suscripción PRO requerida
                    </div>
                    <span class="pill pill-warning">
                        <i class="fa-solid fa-lock"></i> Aceptar bloqueado
                    </span>
                </div>
                <div class="section-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="fs-5 text-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold mb-1">
                                Necesitás una suscripción PRO activa para aceptar paseos.
                            </div>

                            <div class="small text-muted">
                                Estado actual:
                                <span class="badge <?= $subEstado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                    <?= $subEstado ? strtoupper($subEstado) : 'SIN SUSCRIPCIÓN' ?>
                                </span>

                                <?php if ($subFin): ?>
                                    <span class="ms-2">Vence: <?= date('d/m/Y H:i', strtotime((string)$subFin)); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3 d-flex flex-wrap gap-2">
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
                </div>
            </div>
        <?php endif; ?>

        <!-- ✅ CONTENIDO -->
        <?php if (empty($solicitudes)): ?>
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-circle-info me-2"></i> Sin solicitudes pendientes
                </div>
                <div class="section-body">
                    <div class="alert alert-light border mb-0">
                        <i class="fas fa-info-circle me-2 text-success"></i>
                        No tenés solicitudes pendientes en este momento.
                        <div class="small text-muted mt-1">
                            Cuando un dueño te solicite un paseo, te va a aparecer acá.
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>

            <div class="section-card">
                <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <i class="fas fa-paw me-2"></i> Solicitudes pendientes
                    </div>
                    <span class="pill">
                        <i class="fas fa-list-check"></i> <?= count($solicitudes) ?> en espera
                    </span>
                </div>

                <div class="section-body">
                    <!-- Tabla (md+) -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table align-middle table-hover mb-0">
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
                                $duracion = (int)($s['duracion'] ?? ($s['duracion_min'] ?? 0));
                                $precio   = (float)($s['precio_total'] ?? 0);
                            ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-paw text-success me-1"></i>
                                        <?= h($s['nombre_mascota'] ?? '—') ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-user text-secondary me-1"></i>
                                        <?= h($s['nombre_dueno'] ?? '—') ?>
                                    </td>
                                    <td><?= fmtFechaHora($s['inicio'] ?? null) ?></td>
                                    <td><?= $duracion ?> min</td>
                                    <td>₲<?= fmtGs($precio) ?></td>
                                    <td class="text-end">

                                        <!-- ✅ Aceptar (bloqueado si no PRO) -->
                                        <form action="AccionPaseo.php" method="post" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $paseoId ?>">
                                            <input type="hidden" name="accion" value="confirmar">
                                            <input type="hidden" name="redirect_to" value="Solicitudes.php">

                                            <button type="submit"
                                                class="btn btn-success btn-sm btn-icon <?= !$tieneProActiva ? 'disabled' : '' ?>"
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

                                            <button type="submit"
                                                class="btn btn-danger btn-sm btn-icon"
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

                    <!-- Cards (móvil) -->
                    <div class="d-md-none d-grid gap-2">
                        <?php foreach ($solicitudes as $s):
                            $paseoId  = (int)($s['paseo_id'] ?? 0);
                            $duracion = (int)($s['duracion'] ?? ($s['duracion_min'] ?? 0));
                            $precio   = (float)($s['precio_total'] ?? 0);
                        ?>
                            <div class="req-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="req-title">
                                            <i class="fas fa-paw text-success me-1"></i>
                                            <?= h($s['nombre_mascota'] ?? '—') ?>
                                        </div>
                                        <div class="req-meta mt-1">
                                            <div><i class="fas fa-user me-1 text-secondary"></i><?= h($s['nombre_dueno'] ?? '—') ?></div>
                                            <div><i class="fas fa-calendar me-1 text-secondary"></i><?= fmtFechaHora($s['inicio'] ?? null) ?></div>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <span class="pill"><i class="fas fa-clock"></i><?= $duracion ?> min</span>
                                                <span class="pill"><i class="fas fa-money-bill-wave"></i>₲<?= fmtGs($precio) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-2">
                                        <form action="AccionPaseo.php" method="post">
                                            <input type="hidden" name="id" value="<?= $paseoId ?>">
                                            <input type="hidden" name="accion" value="confirmar">
                                            <input type="hidden" name="redirect_to" value="Solicitudes.php">

                                            <button type="submit"
                                                class="btn btn-success btn-sm btn-icon <?= !$tieneProActiva ? 'disabled' : '' ?>"
                                                <?= !$tieneProActiva ? 'disabled' : '' ?>
                                                onclick="<?= !$tieneProActiva ? 'return false;' : "return confirm('¿Aceptar esta solicitud?');" ?>"
                                                title="<?= !$tieneProActiva ? 'Requiere Suscripción PRO activa' : 'Aceptar' ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>

                                        <form action="AccionPaseo.php" method="post">
                                            <input type="hidden" name="id" value="<?= $paseoId ?>">
                                            <input type="hidden" name="accion" value="cancelar">
                                            <input type="hidden" name="redirect_to" value="Solicitudes.php">

                                            <button type="submit"
                                                class="btn btn-danger btn-sm btn-icon"
                                                onclick="return confirm('¿Rechazar esta solicitud?');"
                                                title="Rechazar">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <?php if (!$tieneProActiva): ?>
                                    <div class="small text-muted mt-2">
                                        <i class="fa-solid fa-lock me-1"></i> Aceptar bloqueado por falta de PRO.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>

        <?php endif; ?>

        <footer class="mt-4 text-center text-muted small">
            © <?= date('Y') ?> Jaguata — Panel del Paseador
        </footer>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
