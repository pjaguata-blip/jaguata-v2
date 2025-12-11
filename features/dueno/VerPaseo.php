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

AppConfig::init();

// Solo paseador
$auth = new AuthController();
$auth->checkRole('paseador');

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$paseoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($paseoId <= 0) {
    echo "ID de paseo no válido.";
    exit;
}

// Obtener datos
$paseoController = new PaseoController();
$paseo           = $paseoController->show($paseoId);

if (!$paseo) {
    echo "No se encontró el paseo especificado.";
    exit;
}

// Validar que el paseo pertenezca al paseador logueado
$paseadorIdSesion = (int)(Session::getUsuarioId() ?? 0);
if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorIdSesion) {
    echo "No tenés permiso para ver este paseo.";
    exit;
}

// =====================
// Rutas útiles
// =====================
$baseUrl   = AppConfig::getBaseUrl();
$backUrl   = $baseUrl . "/features/paseador/MisPaseos.php";
$inicioUrl = $baseUrl;
$panelUrl  = $baseUrl . '/features/paseador/Dashboard.php';

// =====================
// Formato de campos
// =====================
$fechaPaseo = isset($paseo['inicio'])
    ? date('d/m/Y H:i', strtotime($paseo['inicio']))
    : '—';

// Normalizamos estado
$estadoRaw   = trim((string)($paseo['estado'] ?? 'solicitado'));
$estadoSlug  = strtolower($estadoRaw !== '' ? $estadoRaw : 'solicitado');   // para lógica
$estadoLabel = ucfirst(str_replace('_', ' ', $estadoSlug));                 // para mostrar

// Badge según estado
$badgeClass = match ($estadoSlug) {
    'completo'   => 'bg-success',
    'cancelado'  => 'bg-danger',
    'en_curso'   => 'bg-info',
    'confirmado' => 'bg-primary',
    'solicitado', 'pendiente' => 'bg-warning text-dark',
    default      => 'bg-secondary',
};

// Monto / precio
$monto = (float)($paseo['precio_total'] ?? $paseo['monto'] ?? 0);
$montoFmt = number_format($monto, 0, ',', '.');

// Duración
$duracion = $paseo['duracion'] ?? $paseo['duracion_min'] ?? '—';

// Observación
$observacion = $paseo['observaciones'] ?? $paseo['observacion'] ?? 'Sin observaciones.';

// Nombres
$paseadorNombre = $paseo['paseador_nombre'] ?? $paseo['nombre_paseador'] ?? '—';
$mascotaNombre  = $paseo['mascota_nombre'] ?? $paseo['nombre_mascota'] ?? '—';
$duenoNombre    = $paseo['dueno_nombre'] ?? $paseo['nombre_dueno'] ?? '—';

// Dirección / ubicación
$direccion = $paseo['direccion'] ?? $paseo['ubicacion'] ?? '—';

// ID seguro
$paseoIdSeguro = (int)($paseo['paseo_id'] ?? $paseoId);

// =====================
// Mensajes flash opcionales
// =====================
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

?>

<?php include __DIR__ . '/../../src/Templates/header.php'; ?>
<?php include __DIR__ . '/../../src/Templates/navbar.php'; ?>

<div class="container py-4">
    <!-- Encabezado -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= h($backUrl) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <h1 class="page-title mb-0 d-flex align-items-center">
                <i class="fas fa-walking me-2"></i> Detalles del Paseo
            </h1>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= h($inicioUrl) ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-house me-1"></i> Inicio
            </a>
            <a href="<?= h($panelUrl) ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-gauge-high me-1"></i> Panel
            </a>
        </div>
    </div>

    <!-- Mensajes flash -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= h($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= h($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <!-- Detalles del Paseo -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <h5><i class="fas fa-calendar-alt me-2 text-primary"></i>Fecha del Paseo</h5>
                    <p><?= h($fechaPaseo) ?></p>

                    <h5><i class="fas fa-user-tie me-2 text-primary"></i>Dueño</h5>
                    <p><?= h($duenoNombre) ?></p>

                    <h5><i class="fas fa-dog me-2 text-primary"></i>Mascota</h5>
                    <p><?= h($mascotaNombre) ?></p>
                </div>

                <div class="col-md-6">
                    <h5><i class="fas fa-map-marker-alt me-2 text-primary"></i>Dirección</h5>
                    <p><?= h($direccion) ?></p>

                    <h5><i class="fas fa-stopwatch me-2 text-primary"></i>Duración</h5>
                    <p><?= h($duracion) ?> min</p>

                    <h5><i class="fas fa-dollar-sign me-2 text-primary"></i>Monto</h5>
                    <p><?= $montoFmt ?> Gs.</p>

                    <h5><i class="fas fa-info-circle me-2 text-primary"></i>Estado</h5>
                    <span class="badge <?= $badgeClass ?>">
                        <?= h($estadoLabel) ?>
                    </span>
                </div>

                <div class="col-12 mt-3">
                    <h5><i class="fas fa-comment-dots me-2 text-primary"></i>Observaciones</h5>
                    <p class="border rounded p-2 bg-light">
                        <?= nl2br(h($observacion)) ?>
                    </p>
                </div>
            </div>

            <!-- Acciones (flujo del paseador) -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <a href="<?= h($backUrl) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>

                <div class="d-flex gap-2">
                    <?php if (in_array($estadoSlug, ['solicitado', 'pendiente'], true)): ?>
                        <!-- 🟡 solicitado / pendiente → confirmar / cancelar -->
                        <form action="AccionPaseo.php" method="post" class="d-inline">
                            <input type="hidden" name="id" value="<?= $paseoIdSeguro ?>">
                            <input type="hidden" name="accion" value="confirmar">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-1"></i> Confirmar Paseo
                            </button>
                        </form>

                        <form action="AccionPaseo.php" method="post" class="d-inline">
                            <input type="hidden" name="id" value="<?= $paseoIdSeguro ?>">
                            <input type="hidden" name="accion" value="cancelar">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </button>
                        </form>

                    <?php elseif ($estadoSlug === 'confirmado'): ?>
                        <!-- 🔵 confirmado → iniciar / cancelar -->
                        <a href="AccionPaseo.php?id=<?= $paseoIdSeguro ?>&accion=iniciar"
                            class="btn btn-success"
                            onclick="return confirm('¿Iniciar este paseo?');">
                            <i class="fas fa-play me-1"></i> Iniciar Paseo
                        </a>

                        <a href="AccionPaseo.php?id=<?= $paseoIdSeguro ?>&accion=cancelar"
                            class="btn btn-outline-danger"
                            onclick="return confirm('¿Cancelar este paseo?');">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>

                    <?php elseif ($estadoSlug === 'en_curso'): ?>
                        <!-- 🔵 en_curso → completar / cancelar -->
                        <a href="AccionPaseo.php?id=<?= $paseoIdSeguro ?>&accion=completar"
                            class="btn btn-success"
                            onclick="return confirm('¿Marcar este paseo como completado?');">
                            <i class="fas fa-check me-1"></i> Completar Paseo
                        </a>

                        <a href="AccionPaseo.php?id=<?= $paseoIdSeguro ?>&accion=cancelar"
                            class="btn btn-outline-danger"
                            onclick="return confirm('¿Cancelar este paseo en curso?');">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>

                    <?php else: ?>
                        <!-- completo / cancelado → solo ver -->
                        <span class="text-muted">
                            No hay acciones disponibles para este estado.
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/Templates/footer.php'; ?>