<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;

AppConfig::init();

function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* 🔒 Seguridad */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}
$auth = new AuthController();
$auth->checkRole('admin');

/* ✅ baseFeatures */
$baseFeatures = BASE_URL . '/features/admin';

/* ID */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('<h3 style="color:red;text-align:center;margin-top:2rem;">ID de paseo no válido.</h3>');
}

/* Datos */
$paseoController = new PaseoController();
$paseo = $paseoController->getDetalleAdmin($id);

if (!$paseo) {
    http_response_code(404);
    exit('<h3 style="color:red;text-align:center;margin-top:2rem;">No se encontró el paseo solicitado.</h3>');
}

/* Punto recogida */
$pickupLat = $paseo['pickup_lat'] ?? null;
$pickupLng = $paseo['pickup_lng'] ?? null;

/* Ruta */
$rutaPuntos = $paseoController->getRuta($id) ?: [];
$rutaCoords = [];
foreach ($rutaPuntos as $punto) {
    $rutaCoords[] = [(float)$punto['latitud'], (float)$punto['longitud']];
}

/* Estado + badge (unificado a tu theme) */
$estado = strtolower(trim((string)($paseo['estado'] ?? 'pendiente')));
$estadoLabel = match ($estado) {
    'pendiente'   => 'Pendiente',
    'confirmado'  => 'Confirmado',
    'en_curso'    => 'En curso',
    'completo'    => 'Completo',
    'finalizado'  => 'Finalizado',
    'cancelado'   => 'Cancelado',
    default       => ucfirst($estado),
};

$badgeEstado = match ($estado) {
    'pendiente'  => 'estado-pendiente',
    'confirmado' => 'estado-activo',
    'en_curso'   => 'estado-activo',
    'finalizado' => 'estado-aprobado',
    'completo'   => 'estado-aprobado',
    'cancelado'  => 'estado-rechazado',
    default      => 'estado-pendiente',
};

/* Helpers fechas */
$inicio = (string)($paseo['inicio'] ?? '');
$upd    = (string)($paseo['updated_at'] ?? '');

$inicioFmt = $inicio ? date('d/m/Y H:i', strtotime($inicio)) : '—';
$updFmt    = $upd ? date('d/m/Y H:i', strtotime($upd)) : 'Sin cambios';

/* URL volver */
$urlVolver = $baseFeatures . '/Paseos.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paseo #<?= (int)$id; ?> - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        html, body { overflow-x: hidden; width: 100%; }

        #map{
            width:100%;
            height:340px;
            border-radius: 1rem;
            overflow:hidden;
            border: 1px solid rgba(255,255,255,.10);
        }

        .kv{
            padding:.55rem 0;
            border-bottom: 1px dashed rgba(255,255,255,.14);
        }
        .kv:last-child{ border-bottom:0; }
        .kv .k{ font-size:.85rem; text-transform:uppercase; opacity:.70; margin-bottom:.15rem; }
        .kv .v{ margin:0; font-weight:600; }

        .action-bar{
            display:flex;
            gap:.6rem;
            flex-wrap:wrap;
            justify-content:center;
        }

        .mini-note{
            font-size:.9rem;
            opacity:.80;
        }

        /* chip con dot tipo pro */
        .estado-chip{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            justify-content:center;
            min-width:120px;
        }
        .estado-dot{
            width:10px;height:10px;border-radius:999px;display:inline-block;
        }
        .estado-dot.pendiente{ background:#f0ad4e; }
        .estado-dot.confirmado{ background:#0d6efd; }
        .estado-dot.en_curso{ background:#0dcaf0; }
        .estado-dot.finalizado{ background:#198754; }
        .estado-dot.completo{ background:#198754; }
        .estado-dot.cancelado{ background:#dc3545; }
    </style>
</head>

<body>

<?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

<main>
    <div class="container-fluid px-3 px-md-2">

        <!-- ✅ HEADER (estilo Usuarios.php) -->
        <div class="header-box header-paseos mb-3">
            <div>
                <h1 class="fw-bold mb-1">Detalle del Paseo #<?= (int)$id; ?></h1>
                <p class="mb-0">Información completa del recorrido, estado y ubicación 🐾</p>
            </div>

            

            <a href="<?= h($urlVolver); ?>" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <div class="row g-3">

            <!-- ✅ Info paseo -->
            <div class="col-lg-6">
                <div class="section-card mb-3">
                    <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-dog me-2"></i>
                            <span>Información del paseo</span>
                        </div>
                        <span class="badge bg-secondary">ID #<?= (int)$id; ?></span>
                    </div>

                    <div class="section-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Mascota</div>
                                    <p class="v"><?= h($paseo['nombre_mascota'] ?? '—'); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Paseador</div>
                                    <p class="v"><?= h($paseo['nombre_paseador'] ?? '—'); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Dueño</div>
                                    <p class="v"><?= h($paseo['nombre_dueno'] ?? '—'); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Duración</div>
                                    <p class="v"><?= (int)($paseo['duracion'] ?? 0); ?> min</p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Monto</div>
                                    <p class="v">₲<?= number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.'); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Estado</div>
                                    <p class="v mb-0">
                                        <span class="badge-estado <?= h($badgeEstado); ?> estado-chip">
                                            <span class="estado-dot <?= h($estado); ?>"></span>
                                            <?= h($estadoLabel); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Fecha de inicio</div>
                                    <p class="v"><?= h($inicioFmt); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="kv">
                                    <div class="k">Última actualización</div>
                                    <p class="v"><?= h($updFmt); ?></p>
                                </div>
                            </div>

                        </div>

                        <p class="text-muted small mt-2 mb-0">
                            Tip: usá “Ver mapa” para saltar directo al recorrido.
                        </p>
                    </div>
                </div>

                <!-- ✅ Acciones -->
                <div class="section-card mb-3">
                    <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tools me-2"></i>
                            <span>Acciones del administrador</span>
                        </div>
                        <span class="badge bg-secondary">Control</span>
                    </div>

                    <div class="section-body">
                        <p class="mini-note text-muted mb-3">
                            Estas acciones cambian el estado del paseo. Confirmá antes de ejecutar.
                        </p>

                        <div class="action-bar">
                            <button class="btn btn-success"
                                type="button"
                                onclick="actualizarEstado('finalizar')">
                                <i class="fas fa-check-circle me-1"></i> Finalizar
                            </button>

                            <button class="btn btn-danger"
                                type="button"
                                onclick="actualizarEstado('cancelar')">
                                <i class="fas fa-times-circle me-1"></i> Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Mapa -->
            <div class="col-lg-6">
                <div class="section-card mb-3">
                    <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-map-location-dot me-2"></i>
                            <span>Ubicación y recorrido</span>
                        </div>
                        <span class="badge bg-secondary"><?= count($rutaCoords); ?> punto(s)</span>
                    </div>

                    <div class="section-body">
                        <p class="text-muted mb-2">
                            Punto de recogida y ruta registrada del paseo 🐾
                        </p>
                        <div id="map"></div>

                        <p class="text-muted small mt-2 mb-0">
                            Si no hay ruta guardada, se mostrará el punto disponible (o una ubicación genérica).
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <footer class="mt-3">
            <small>© <?= date('Y'); ?> Jaguata — Panel de Administración</small>
        </footer>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /* ✅ Toggle sidebar en mobile (IGUAL Usuarios.php) */
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('.sidebar');
        const btnToggle = document.getElementById('btnSidebarToggle');

        if (btnToggle && sidebar) {
            btnToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
        }
    });
</script>

<script>
    // === Datos mapa ===
    const pickupLat  = <?= $pickupLat !== null ? (float)$pickupLat : 'null' ?>;
    const pickupLng  = <?= $pickupLng !== null ? (float)$pickupLng : 'null' ?>;
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
        iconUrl: "<?= BASE_URL; ?>/public/assets/images/paw.png",
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32]
    });

    if (pickupLat && pickupLng) {
        L.marker([pickupLat, pickupLng], { icon: pawIcon })
            .addTo(map)
            .bindPopup('Punto de recogida');
    }

    if (rutaCoords.length > 0) {
        const polyline = L.polyline(rutaCoords, { weight: 5, opacity: 0.8 }).addTo(map);
        map.fitBounds(polyline.getBounds(), { padding: [18, 18] });

        const paso = 5;
        for (let i = 0; i < rutaCoords.length; i += paso) {
            const [lat, lng] = rutaCoords[i];
            L.marker([lat, lng], { icon: pawIcon }).addTo(map);
        }
    } else if (!pickupLat || !pickupLng) {
        L.marker([-25.3, -57.6]).addTo(map).bindPopup('Ubicación no disponible');
    }

    async function actualizarEstado(accion) {
        if (!confirm(`¿Seguro que deseas ${accion} este paseo?`)) return;

        try {
            const res = await fetch('<?= BASE_URL; ?>/public/api/paseos/accionPaseo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=<?= (int)$id; ?>&accion=${encodeURIComponent(accion)}`
            });

            const data = await res.json();
            alert(data.mensaje || 'Operación realizada');
            if (data.ok) location.reload();
        } catch (e) {
            alert('Error al procesar la acción');
        }
    }
</script>

</body>
</html>
