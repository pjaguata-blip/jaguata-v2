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

// Autenticación (solo dueño)
$auth = new AuthController();
$auth->checkRole('dueno');

$paseoId = (int)($_GET['paseo_id'] ?? 0);
if ($paseoId <= 0) {
    http_response_code(400);
    exit('Paseo no especificado.');
}

$paseoCtrl = new PaseoController();
$paseo = $paseoCtrl->getPaseoById($paseoId);

if (!$paseo) {
    http_response_code(404);
    exit('No se encontró el paseo.');
}

$titulo = "Detalle del Paseo #{$paseoId} - Jaguata";

// Determinar estado visual
$estado = strtolower($paseo['estado'] ?? 'desconocido');
$badgeClass = match ($estado) {
    'solicitado', 'pendiente' => 'warning',
    'confirmado'              => 'info',
    'en_curso'                => 'primary',
    'completo'                => 'success',
    'cancelado'               => 'danger',
    default                   => 'secondary'
};
?>

<?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
<?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-success"><i class="fas fa-dog me-2"></i>Detalle del Paseo</h2>
        <a href="MisPaseos.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow border-0 mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3 text-primary">🐾 Mascota</h5>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($paseo['nombre_mascota'] ?? '-') ?></p>
                    <p><strong>Dueño:</strong> <?= htmlspecialchars($paseo['nombre_dueno'] ?? '-') ?></p>
                    <p><strong>Duración:</strong> <?= htmlspecialchars($paseo['duracion']) ?> minutos</p>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3 text-primary">👤 Paseador</h5>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($paseo['nombre_paseador'] ?? '-') ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($paseo['telefono_paseador'] ?? '-') ?></p>
                    <p><strong>Zona:</strong> <?= htmlspecialchars($paseo['zona'] ?? '-') ?></p>
                </div>
            </div>

            <hr>

            <div class="row mt-3">
                <div class="col-md-4">
                    <p><strong>Fecha de inicio:</strong> <?= date('d/m/Y H:i', strtotime($paseo['inicio'])) ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Precio Total:</strong> ₲<?= number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.') ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Estado:</strong>
                        <span class="badge bg-<?= $badgeClass ?> px-3 py-2"><?= ucfirst($estado) ?></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔹 Acciones dinámicas -->
    <div class="text-center">
        <?php if (in_array($estado, ['pendiente', 'solicitado', 'confirmado'])): ?>
            <a href="CancelarPaseo.php?id=<?= $paseoId ?>" class="btn btn-outline-danger me-2"
                onclick="return confirm('¿Seguro que deseas cancelar este paseo?');">
                <i class="fas fa-times me-1"></i> Cancelar Paseo
            </a>
            <a href="pago_paseo_dueno.php?paseo_id=<?= $paseoId ?>" class="btn btn-success">
                <i class="fas fa-wallet me-1"></i> Pagar Paseo
            </a>

        <?php elseif ($estado === 'completo'): ?>
            <a href="CalificarPaseador.php?paseo_id=<?= $paseoId ?>" class="btn btn-primary">
                <i class="fas fa-star me-1"></i> Calificar Paseador
            </a>

        <?php elseif ($estado === 'en_curso'): ?>
            <div class="alert alert-info">El paseo está actualmente en curso 🐕</div>
        <?php elseif ($estado === 'cancelado'): ?>
            <div class="alert alert-danger">Este paseo fue cancelado ❌</div>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($paseo['paseador_latitud']) && !empty($paseo['paseador_longitud'])): ?>
    <hr>
    <h4 class="mt-4 mb-3 text-primary">
        <i class="fas fa-map-marker-alt me-2"></i> Ubicación del Paseador
    </h4>
    <div id="map" style="height: 400px; border-radius: 10px; overflow: hidden;"></div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <script>
        const lat = <?= json_encode($paseo['paseador_latitud']); ?>;
        const lng = <?= json_encode($paseo['paseador_longitud']); ?>;

        const map = L.map('map').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup("<b><?= htmlspecialchars($paseo['nombre_paseador']); ?></b><br>📍 Zona: <?= htmlspecialchars($paseo['zona'] ?? 'Sin info'); ?>").openPopup();
    </script>
<?php else: ?>
    <div class="alert alert-warning mt-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        El paseador aún no compartió su ubicación.
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>