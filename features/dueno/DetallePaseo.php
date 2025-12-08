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

// ✅ Helper de sanitización
function h(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// === Init + auth ===
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

$rolUsuario   = Session::getUsuarioRol() ?: 'dueno';
$duenoId      = (int)(Session::getUsuarioId() ?? 0);
$baseFeatures = BASE_URL . "/features/{$rolUsuario}";
$backUrl      = $baseFeatures . "/MisPaseos.php";
$panelUrl     = $baseFeatures . "/Dashboard.php";

// === Paseo ID ===
$paseoId = isset($_GET['paseo_id']) ? (int)$_GET['paseo_id'] : 0;
if ($paseoId <= 0) {
    $_SESSION['error'] = 'ID de paseo no válido.';
    header("Location: {$backUrl}");
    exit;
}

$paseoCtrl = new PaseoController();
$paseo     = $paseoCtrl->getById($paseoId);   // 👈 usa tu método actual

if (!$paseo) {
    $_SESSION['error'] = 'No se encontró el paseo.';
    header("Location: {$backUrl}");
    exit;
}

// (Opcional pero recomendado) validar que el paseo pertenece al dueño logueado
if (isset($paseo['dueno_id']) && (int)$paseo['dueno_id'] !== $duenoId) {
    http_response_code(403);
    exit('No tienes permiso para ver este paseo.');
}

/* ==========================
   Normalización de campos
   ========================== */

// Fecha de inicio
$fechaInicioRaw = $paseo['inicio'] ?? ($paseo['fecha_inicio'] ?? null);
$fecha          = $fechaInicioRaw
    ? date('d/m/Y H:i', strtotime((string)$fechaInicioRaw))
    : '—';

// Estado
$estadoRaw = (string)($paseo['estado'] ?? 'pendiente');
$estado    = ucfirst(strtolower($estadoRaw));
$estadoNorm = strtolower(trim($estadoRaw));

// Monto
$montoRaw = (float)($paseo['precio_total'] ?? $paseo['monto'] ?? 0);
$monto    = number_format($montoRaw, 0, ',', '.');

// Duración
$duracionMin = (int)($paseo['duracion_min'] ?? $paseo['duracion'] ?? 0);
$duracion    = $duracionMin > 0 ? "{$duracionMin} minutos" : '—';

// Observaciones
$observacion = nl2br(h($paseo['observaciones'] ?? $paseo['observacion'] ?? 'Sin observaciones.'));

// Paseador / Mascota
$paseador = h($paseo['nombre_paseador'] ?? $paseo['paseador_nombre'] ?? 'No asignado');
$mascota  = h($paseo['nombre_mascota'] ?? $paseo['mascota_nombre'] ?? '—');

// Badge estado (usamos Bootstrap + lógica simple)
$badgeClass = match ($estadoNorm) {
    'completo'  => 'bg-success',
    'cancelado' => 'bg-danger',
    'en_curso'  => 'bg-info text-dark',
    'confirmado' => 'bg-primary',
    default     => 'bg-warning text-dark', // pendiente u otros
};

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Paseo - Jaguata</title>

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <!-- Sidebar dueño unificada -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Contenido principal -->
    <main>
        <div class="py-4">

            <!-- Header (usa .header-box + .header-paseos del CSS) -->
            <div class="header-box header-paseos mb-4">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-walking me-2"></i>Detalle del paseo
                    </h1>
                    <p class="mb-0">Información completa sobre tu paseo 🐾</p>
                </div>
                <div class="text-end">

                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <!-- Mensajes flash (si los usás) -->
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= h($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Contenido detalle -->
            <div class="section-card">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-info-circle me-2"></i>Datos del paseo
                    </span>
                    <span class="badge <?= $badgeClass; ?> px-3 py-2">
                        <?= $estado; ?>
                    </span>
                </div>

                <div class="section-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <p class="info-label mb-1"><i class="fas fa-calendar-alt me-2 text-success"></i>Fecha del paseo</p>
                            <p class="mb-3"><?= $fecha; ?></p>

                            <p class="info-label mb-1"><i class="fas fa-user-tie me-2 text-success"></i>Paseador</p>
                            <p class="mb-3"><?= $paseador; ?></p>

                            <p class="info-label mb-1"><i class="fas fa-dog me-2 text-success"></i>Mascota</p>
                            <p class="mb-3"><?= $mascota; ?></p>
                        </div>

                        <div class="col-md-6">
                            <p class="info-label mb-1"><i class="fas fa-stopwatch me-2 text-success"></i>Duración</p>
                            <p class="mb-3"><?= $duracion; ?></p>

                            <p class="info-label mb-1"><i class="fas fa-wallet me-2 text-success"></i>Monto</p>
                            <p class="mb-3">₲ <?= $monto; ?></p>

                            <p class="info-label mb-1"><i class="fas fa-hashtag me-2 text-success"></i>ID de paseo</p>
                            <p class="mb-3">#<?= $paseoId; ?></p>
                        </div>

                        <div class="col-12 mt-2">
                            <p class="info-label mb-1"><i class="fas fa-comment-dots me-2 text-success"></i>Observaciones</p>
                            <p class="border rounded p-2 bg-light mb-0"><?= $observacion; ?></p>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="action-buttons d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <?php if (in_array($estadoNorm, ['pendiente', 'confirmado', 'en_curso'], true)): ?>
                            <a href="<?= $baseFeatures; ?>/CancelarPaseo.php?id=<?= $paseoId; ?>"
                                class="btn btn-accion btn-rechazar"
                                onclick="return confirm('¿Seguro que deseas cancelar este paseo?');">
                                <i class="fas fa-ban me-1"></i> Cancelar paseo
                            </a>

                            <a href="<?= $baseFeatures; ?>/pago_paseo_dueno.php?paseo_id=<?= $paseoId; ?>"
                                class="btn btn-accion btn-activar">
                                <i class="fas fa-wallet me-1"></i> Pagar paseo
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel del Dueño
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile (usa .sidebar-open del CSS global)
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>