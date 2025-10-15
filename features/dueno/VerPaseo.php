<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

// Solo dueño
$auth = new AuthController();
$auth->checkRole('dueno');

$paseoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($paseoId <= 0) {
    echo "ID de paseo no válido.";
    exit;
}

// Obtener datos
$paseoController = new PaseoController();
$paseo = $paseoController->getById($paseoId);

if (!$paseo) {
    echo "No se encontró el paseo especificado.";
    exit;
}

// Rutas útiles
$backUrl = BASE_URL . "/features/dueno/PaseosPendientes.php";
$inicioUrl = AppConfig::getBaseUrl();
$panelUrl = AppConfig::getBaseUrl() . '/features/dueno/Dashboard.php';

// Formato de campos
$fechaPaseo = date('d/m/Y H:i', strtotime($paseo['fecha_inicio'] ?? 'now'));
$estado = ucfirst($paseo['estado'] ?? 'Pendiente');
$monto = number_format((float)($paseo['monto'] ?? 0), 0, ',', '.');
$duracion = $paseo['duracion'] ?? '—';
$observacion = $paseo['observacion'] ?? 'Sin observaciones.';
?>

<?php include __DIR__ . '/../../src/Templates/header.php'; ?>
<?php include __DIR__ . '/../../src/Templates/navbar.php'; ?>

<div class="container py-4">
    <!-- Encabezado -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <h1 class="page-title mb-0 d-flex align-items-center">
                <i class="fas fa-walking me-2"></i> Detalles del Paseo
            </h1>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= htmlspecialchars($inicioUrl) ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-house me-1"></i> Inicio
            </a>
            <a href="<?= htmlspecialchars($panelUrl) ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-gauge-high me-1"></i> Panel
            </a>
        </div>
    </div>

    <!-- Detalles del Paseo -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <h5><i class="fas fa-calendar-alt me-2 text-primary"></i>Fecha del Paseo</h5>
                    <p><?= htmlspecialchars($fechaPaseo) ?></p>

                    <h5><i class="fas fa-user-tie me-2 text-primary"></i>Paseador</h5>
                    <p><?= htmlspecialchars($paseo['paseador_nombre'] ?? 'No asignado') ?></p>

                    <h5><i class="fas fa-dog me-2 text-primary"></i>Mascota</h5>
                    <p><?= htmlspecialchars($paseo['mascota_nombre'] ?? '—') ?></p>
                </div>

                <div class="col-md-6">
                    <h5><i class="fas fa-stopwatch me-2 text-primary"></i>Duración</h5>
                    <p><?= htmlspecialchars($duracion) ?></p>

                    <h5><i class="fas fa-dollar-sign me-2 text-primary"></i>Monto</h5>
                    <p><?= $monto ?> Gs.</p>

                    <h5><i class="fas fa-info-circle me-2 text-primary"></i>Estado</h5>
                    <span class="badge 
                        <?= $estado === 'Completado' ? 'bg-success' : ($estado === 'Cancelado' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                        <?= htmlspecialchars($estado) ?>
                    </span>
                </div>

                <div class="col-12 mt-3">
                    <h5><i class="fas fa-comment-dots me-2 text-primary"></i>Observaciones</h5>
                    <p class="border rounded p-2 bg-light"><?= nl2br(htmlspecialchars($observacion)) ?></p>
                </div>
            </div>

            <!-- Acciones -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <?php if ($estado === 'Completado'): ?>
                    <a href="<?= BASE_URL; ?>/features/dueno/CalificarPaseo.php?id=<?= (int)$paseoId ?>" class="btn btn-primary">
                        <i class="fas fa-star me-1"></i> Calificar Paseo
                    </a>
                <?php elseif ($estado === 'Pendiente'): ?>
                    <a href="<?= BASE_URL; ?>/features/dueno/PagoPaseo.php?id=<?= (int)$paseoId ?>" class="btn btn-success">
                        <i class="fas fa-wallet me-1"></i> Realizar Pago
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/Templates/footer.php'; ?>