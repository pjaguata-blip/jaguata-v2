<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Models/Calificacion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;
use Jaguata\Models\Calificacion;

AppConfig::init();

/* 🔒 Solo dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Helper */
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* =========================
   ID de paseo y rutas base
   ========================= */

$paseoId = isset($_GET['paseo_id'])
    ? (int)$_GET['paseo_id']
    : (int)($_GET['id'] ?? 0); // por si llega ?id=

if ($paseoId <= 0) {
    $_SESSION['error'] = 'ID de paseo no válido.';
    header('Location: ' . BASE_URL . '/features/dueno/MisPaseos.php');
    exit;
}

$duenoIdSesion = (int)(Session::getUsuarioId() ?? 0);
$rolUsuario    = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolUsuario}";
$backUrl       = $baseFeatures . "/MisPaseos.php";

/* =========================
   Cargar paseo + ruta
   ========================= */

$paseoCtrl = new PaseoController();
$paseo     = $paseoCtrl->show($paseoId);

if (!$paseo) {
    $_SESSION['error'] = 'No se encontró el paseo.';
    header("Location: {$backUrl}");
    exit;
}

/* Validar que el paseo sea del dueño logueado */
if ((int)($paseo['dueno_id'] ?? 0) !== $duenoIdSesion) {
    http_response_code(403);
    exit('No tenés permiso para ver este paseo.');
}

/* Ruta del paseo */
$rutaPuntos = $paseoCtrl->getRuta($paseoId);
$rutaCoords = [];
foreach ($rutaPuntos as $p) {
    $rutaCoords[] = [(float)$p['latitud'], (float)$p['longitud']];
}

/* =========================
   Normalización de campos
   ========================= */

$fechaPaseo = isset($paseo['inicio'])
    ? date('d/m/Y H:i', strtotime((string)$paseo['inicio']))
    : '—';

$estadoRaw   = trim((string)($paseo['estado'] ?? 'solicitado'));
$estadoSlug  = strtolower($estadoRaw !== '' ? $estadoRaw : 'solicitado');
$estadoLabel = ucfirst(str_replace('_', ' ', $estadoSlug));

$badgeClass = match ($estadoSlug) {
    'completo'   => 'bg-success',
    'cancelado'  => 'bg-danger',
    'en_curso'   => 'bg-info text-dark',
    'confirmado' => 'bg-primary',
    'solicitado', 'pendiente' => 'bg-warning text-dark',
    default      => 'bg-secondary',
};

$monto    = (float)($paseo['precio_total'] ?? $paseo['monto'] ?? 0);
$montoFmt = number_format($monto, 0, ',', '.');

$duracion = (int)($paseo['duracion'] ?? $paseo['duracion_min'] ?? 0);

$paseadorNombre = $paseo['paseador_nombre'] ?? $paseo['nombre_paseador'] ?? '—';
$mascotaNombre  = $paseo['mascota_nombre']  ?? $paseo['nombre_mascota']  ?? '—';
$duenoNombre    = $paseo['dueno_nombre']    ?? $paseo['nombre_dueno']    ?? '—';

$direccion = $paseo['direccion'] ?? $paseo['ubicacion'] ?? '—';

$paseoIdSeguro = (int)($paseo['paseo_id'] ?? $paseoId);

/* Punto de recogida */
$pickupLat = $paseo['pickup_lat'] ?? null;
$pickupLng = $paseo['pickup_lng'] ?? null;

/* =========================
   Calificación: dueño → paseador
   ========================= */

$paseadorId     = (int)($paseo['paseador_id'] ?? 0);
$califModel     = new Calificacion();
$yaCalifico     = $califModel->existeParaPaseo($paseoIdSeguro, 'paseador', $duenoIdSesion);
$puedeCalificar = ($estadoSlug === 'completo' && !$yaCalifico);

/* =========================
   Mensajes flash
   ========================= */

$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paseo #<?= h((string)$paseoIdSeguro) ?> - Jaguata</title>

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        html, body { height: 100%; overflow-x: hidden; }
        body { background: var(--gris-fondo, #f4f6f9); }

        main.main-content{
            margin-left: var(--sidebar-w, 260px);
            width: calc(100% - var(--sidebar-w, 260px));
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

        #map{
            width:100%;
            height: 340px;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,.08);
            overflow:hidden;
        }

        .mini-muted{ font-size: .85rem; color:#6b7280; }
        .info-label{
            font-size: .78rem;
            text-transform: uppercase;
            color:#8892a0;
            margin-bottom: .15rem;
            letter-spacing: .03em;
        }
        .value-big{
            font-size: 1.35rem;
            font-weight: 900;
            color:#111;
        }
        .chip{
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            padding:.35rem .7rem;
            border-radius: 999px;
            border:1px solid rgba(0,0,0,.08);
            background:#fff;
            font-size:.85rem;
        }
        .acciones-wrap{
            display:flex;
            gap:.5rem;
            flex-wrap:wrap;
            justify-content:center;
        }
    </style>
</head>

<body>

<?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

<main class="main-content">
    <div class="py-2">

        <!-- Header -->
        <div class="header-box header-paseos mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="fw-bold mb-1">
                    <i class="fas fa-map-location-dot me-2"></i>Detalle del Paseo #<?= h((string)$paseoIdSeguro) ?>
                </h1>
                <p class="mb-0">Información completa del recorrido, estado y ubicación 🐾</p>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <a href="<?= h($backUrl); ?>" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <!-- Flash -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= h($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?= h($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ✅ Mini resumen (stat-cards como tus otras pantallas) -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-3">
                <div class="stat-card text-center h-100">
                    <i class="fas fa-clock text-warning mb-1"></i>
                    <h4><?= (int)$duracion ?> min</h4>
                    <p class="mb-0">Duración</p>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="stat-card text-center h-100">
                    <i class="fas fa-coins text-success mb-1"></i>
                    <h4>₲<?= h($montoFmt) ?></h4>
                    <p class="mb-0">Total</p>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="stat-card text-center h-100">
                    <i class="fas fa-calendar-check text-primary mb-1"></i>
                    <h4 style="font-size:1.05rem; font-weight:900;"><?= h($fechaPaseo) ?></h4>
                    <p class="mb-0">Inicio</p>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="stat-card text-center h-100">
                    <i class="fas fa-flag text-info mb-1"></i>
                    <h4><span class="badge <?= $badgeClass ?> px-3 py-2"><?= h($estadoLabel) ?></span></h4>
                    <p class="mb-0">Estado</p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- ✅ Info (section-card) -->
            <div class="col-lg-6">
                <div class="section-card mb-3">
                    <div class="section-header">
                        <i class="fas fa-dog me-2"></i> Información del paseo
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-label">Mascota</div>
                                <div class="fw-semibold"><?= h($mascotaNombre) ?></div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-label">Paseador</div>
                                <div class="fw-semibold"><?= h($paseadorNombre) ?></div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-label">Dueño</div>
                                <div class="fw-semibold"><?= h($duenoNombre) ?></div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-label">Dirección</div>
                                <div class="fw-semibold"><?= h($direccion) ?></div>
                            </div>

                            <div class="col-12 mt-1">
                                <span class="chip">
                                    <i class="fas fa-hashtag text-muted"></i> Paseo ID: <b><?= (int)$paseoIdSeguro ?></b>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Acciones (section-card) -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-tools me-2"></i> Acciones del dueño
                    </div>
                    <div class="section-body">
                        <div class="acciones-wrap">

                            <?php if ($estadoSlug === 'completo'): ?>

                                <?php if ($puedeCalificar): ?>
                                    <button type="button"
                                        class="btn btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalCalificarPaseador">
                                        <i class="fas fa-star me-1"></i> Calificar paseador
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-success px-3 py-2 align-self-center">
                                        Ya calificaste este paseo ⭐
                                    </span>
                                <?php endif; ?>

                            <?php elseif (in_array($estadoSlug, ['solicitado', 'pendiente', 'confirmado', 'en_curso'], true)): ?>

                                <a href="<?= $baseFeatures; ?>/pago_paseo_dueno.php?paseo_id=<?= $paseoIdSeguro; ?>"
                                   class="btn btn-success">
                                    <i class="fas fa-wallet me-1"></i> Pagar paseo
                                </a>

                                <a href="<?= $baseFeatures; ?>/CancelarPaseo.php?id=<?= $paseoIdSeguro; ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('¿Seguro que deseás cancelar este paseo?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar paseo
                                </a>

                            <?php else: ?>

                                <span class="text-muted">
                                    No hay acciones disponibles para este estado.
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="mini-muted text-center mt-3">
                            Tip: si el paseo ya está completado, aparece la opción de calificar ⭐
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Mapa (section-card) -->
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-map-marker-alt me-2"></i> Ubicación y recorrido
                    </div>
                    <div class="section-body">
                        <p class="text-muted mb-2">
                            Vista del punto de recogida y del recorrido realizado 🐾
                        </p>
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-4 text-center text-muted small">
            © <?= date('Y') ?> Jaguata — Panel del Dueño
        </footer>

    </div>
</main>

<!-- MODAL: CALIFICAR PASEADOR -->
<div class="modal fade" id="modalCalificarPaseador" tabindex="-1" aria-labelledby="modalCalificarPaseadorLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCalificarPaseador">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalCalificarPaseadorLabel">
                        <i class="fas fa-star me-2"></i>Calificar paseador
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="paseo_id" value="<?= $paseoIdSeguro; ?>">
                    <input type="hidden" name="rated_id" value="<?= $paseadorId; ?>">

                    <div class="mb-3">
                        <label class="form-label">Calificación (1 a 5)</label>
                        <select name="calificacion" class="form-select" required>
                            <option value="5">⭐⭐⭐⭐⭐ (Excelente)</option>
                            <option value="4">⭐⭐⭐⭐ (Muy bueno)</option>
                            <option value="3">⭐⭐⭐ (Bueno)</option>
                            <option value="2">⭐⭐ (Regular)</option>
                            <option value="1">⭐ (Malo)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Comentario (opcional)</label>
                        <textarea name="comentario" class="form-control" rows="3"
                                  placeholder="Contá brevemente cómo fue la experiencia con el paseador..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Enviar calificación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Mapa + envío calificación -->
<script>
    const pickupLat = <?= $pickupLat !== null ? (float)$pickupLat : 'null' ?>;
    const pickupLng = <?= $pickupLng !== null ? (float)$pickupLng : 'null' ?>;
    const rutaCoords = <?= json_encode($rutaCoords, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    let initialLat = -25.3;
    let initialLon = -57.6;

    if (pickupLat && pickupLng) {
        initialLat = pickupLat;
        initialLon = pickupLng;
    } else if (rutaCoords.length > 0) {
        initialLat = rutaCoords[0][0];
        initialLon = rutaCoords[0][1];
    }

    const map = L.map('map').setView([initialLat, initialLon], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const pawIcon = L.icon({
        iconUrl: "<?= BASE_URL ?>/public/assets/images/paw.png",
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32]
    });

    if (pickupLat && pickupLng) {
        L.marker([pickupLat, pickupLng], { icon: pawIcon })
            .addTo(map)
            .bindPopup('Punto de recogida')
            .openPopup();
    }

    if (rutaCoords.length > 0) {
        const polyline = L.polyline(rutaCoords, { weight: 5, opacity: 0.8 }).addTo(map);
        map.fitBounds(polyline.getBounds());

        const paso = 5;
        for (let i = 0; i < rutaCoords.length; i += paso) {
            const [lat, lng] = rutaCoords[i];
            L.marker([lat, lng], { icon: pawIcon }).addTo(map);
        }
    }

    const formCalificarPaseador = document.getElementById('formCalificarPaseador');
    if (formCalificarPaseador) {
        formCalificarPaseador.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formCalificarPaseador);

            try {
                const resp = await fetch('<?= BASE_URL; ?>/public/api/calificar_paseador.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await resp.json();

                if (data.success) {
                    alert('✅ Calificación enviada correctamente');
                    window.location.reload();
                } else {
                    alert('⚠️ ' + (data.error || 'No se pudo guardar la calificación.'));
                }
            } catch (err) {
                console.error(err);
                alert('Ocurrió un error al enviar la calificación.');
            }
        });
    }
</script>

</body>
</html>
