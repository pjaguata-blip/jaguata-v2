<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

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

/* 🔒 Solo paseador */
$auth = new AuthController();
$auth->checkRole('paseador');

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

/* ID paseo */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('<h3 style="color:red;text-align:center;">ID de paseo no válido.</h3>');
}

/* Cargar paseo */
$paseoController = new PaseoController();
$paseo = $paseoController->show($id);

if (!$paseo) {
    die('<h3 style="color:red;text-align:center;">No se encontró el paseo solicitado.</h3>');
}

/* Validar pertenencia */
$paseadorIdSesion = (int)(Session::getUsuarioId() ?? 0);
if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorIdSesion) {
    die('<h3 style="color:red;text-align:center;">No tenés permiso para ver este paseo.</h3>');
}

/* ✅ IDs de mascotas (CLAVE para calificar) */
$mascota1Id = (int)($paseo['mascota_id'] ?? 0);
$mascota2Id = (int)($paseo['mascota_id_2'] ?? 0);

/* Mascotas */
$cantidadMascotas = (int)($paseo['cantidad_mascotas'] ?? 1);

$mascota1Nombre = $paseo['mascota_nombre'] ?? $paseo['nombre_mascota'] ?? '-';
$mascota1Foto   = $paseo['mascota_foto'] ?? null;

$mascota2Nombre = $paseo['mascota2_nombre'] ?? $paseo['nombre_mascota_2'] ?? null;
$mascota2Foto   = $paseo['mascota2_foto'] ?? $paseo['mascota_foto_2'] ?? null;

$hayMascota2 = ($cantidadMascotas === 2) || ($mascota2Id > 0) || (!empty($mascota2Nombre));

/* Datos base */
$paseadorNombre = $paseo['paseador_nombre'] ?? $paseo['nombre_paseador'] ?? '-';
$duenoNombre    = $paseo['dueno_nombre']    ?? $paseo['nombre_dueno'] ?? '-';

$duracionMin = (int)($paseo['duracion'] ?? $paseo['duracion_min'] ?? 0);
$monto       = (float)($paseo['precio_total'] ?? $paseo['monto'] ?? 0);

$estadoRaw  = strtolower(trim((string)($paseo['estado'] ?? 'pendiente')));
$estado     = $estadoRaw !== '' ? $estadoRaw : 'pendiente';

$badgeClass = match ($estado) {
    'pendiente', 'solicitado' => 'bg-warning text-dark',
    'confirmado'              => 'bg-primary',
    'en_curso'                => 'bg-info text-dark',
    'completo', 'finalizado'  => 'bg-success',
    'cancelado'               => 'bg-danger',
    default                   => 'bg-secondary'
};

$inicio    = $paseo['inicio']     ?? '';
$updatedAt = $paseo['updated_at'] ?? '';
$direccion = $paseo['direccion'] ?? $paseo['ubicacion'] ?? '-';

/* Punto de recogida */
$pickupLat = $paseo['pickup_lat'] ?? null;
$pickupLng = $paseo['pickup_lng'] ?? null;

/* Ruta */
$rutaPuntos = $paseoController->getRuta($id) ?: [];
$rutaCoords = [];
foreach ($rutaPuntos as $punto) {
    $rutaCoords[] = [(float)$punto['latitud'], (float)$punto['longitud']];
}

$paseoId = (int)($paseo['paseo_id'] ?? $id);

/* Calificación (solo 1 por paseo para el paseador) */
$califModel = new Calificacion();
$yaCalifico = $califModel->existeParaPaseo($paseoId, 'mascota', $paseadorIdSesion);

$puedeCalificar = in_array($estado, ['completo', 'finalizado'], true)
    && !$yaCalifico
    && $mascota1Id > 0;

/* Rutas UI */
$baseUrl = AppConfig::getBaseUrl();
$backUrl = $baseUrl . "/features/paseador/MisPaseos.php";

/* Fotos */
$placeholderDog = $baseUrl . "/public/assets/images/dog-placeholder.png";

function fotoUrl(?string $raw, string $baseUrl, string $placeholder): string
{
    $raw = trim((string)$raw);
    if ($raw === '') return $placeholder;
    if (preg_match('~^https?://~i', $raw)) return $raw;
    if (preg_match('~^localhost/~i', $raw)) return 'http://' . $raw;
    return rtrim($baseUrl, '/') . '/' . ltrim($raw, '/');
}

$foto1 = fotoUrl($mascota1Foto, $baseUrl, $placeholderDog);
$foto2 = $hayMascota2 ? fotoUrl($mascota2Foto, $baseUrl, $placeholderDog) : $placeholderDog;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paseo #<?= h((string)$id) ?> - Paseador | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        html, body { height: 100%; overflow-x: hidden; }
        body { background: var(--gris-fondo, #f4f6f9); }

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

        #map { width:100%; height: 340px; border-radius: 14px; overflow:hidden; border: 1px solid rgba(0,0,0,.08); }

        .info-label {
            font-size: .80rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #8a8a8a;
            margin-bottom: .2rem;
        }

        .pet-chip{
            display:flex;
            align-items:center;
            gap:.75rem;
            padding:.65rem .75rem;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 14px;
            background: #fff;
        }
        .pet-chip img{
            width: 46px; height:46px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(0,0,0,.08);
        }

        .acciones-wrap{
            display:flex;
            flex-wrap:wrap;
            gap:.5rem;
            justify-content:center;
        }

        .pill{
            display:inline-flex;
            align-items:center;
            gap:.4rem;
            padding:.25rem .65rem;
            border-radius:999px;
            font-size:.78rem;
            background: rgba(60, 98, 85, .10);
            color: var(--verde-jaguata, #3c6255);
            border: 1px solid rgba(60, 98, 85, .18);
            font-weight:700;
            white-space: nowrap;
        }
    </style>
</head>

<body>

<?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

<main class="main-content">
    <div class="py-2">

        <?php if ($debug): ?>
            <div class="alert alert-warning">
                <strong>DEBUG:</strong>
                paseo_id=<?= (int)$paseoId ?> |
                paseador_sesion=<?= (int)$paseadorIdSesion ?> |
                mascota1Id=<?= (int)$mascota1Id ?> |
                mascota2Id=<?= (int)$mascota2Id ?> |
                cantidad_mascotas=<?= (int)$cantidadMascotas ?> |
                estado="<?= h($estado) ?>"
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="header-box header-paseos mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="fw-bold mb-1">
                    <i class="fas fa-route me-2"></i>Detalle del Paseo #<?= h((string)$id) ?>
                </h1>
                <p class="mb-0">Información completa del recorrido, estado y ubicación 🐾</p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <?php if ($hayMascota2): ?>
                    <span class="pill"><i class="fas fa-dog"></i> 2 mascotas</span>
                <?php endif; ?>

                <span class="badge <?= $badgeClass ?> px-3 py-2">
                    <?= h(ucfirst(str_replace('_', ' ', $estado))) ?>
                </span>

                <a href="<?= h($backUrl) ?>" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <div class="row g-3">

            <!-- Izq: info + acciones -->
            <div class="col-lg-6">

                <div class="section-card mb-3">
                    <div class="section-header">
                        <i class="fas fa-circle-info me-2"></i> Información del paseo
                    </div>
                    <div class="section-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <div class="info-label">Mascotas</div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="pet-chip">
                                        <img src="<?= h($foto1) ?>" alt="Mascota 1">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?= h((string)$mascota1Nombre) ?></div>
                                            <div class="text-muted small">Mascota 1</div>
                                        </div>
                                    </div>

                                    <?php if ($hayMascota2): ?>
                                        <div class="pet-chip">
                                            <img src="<?= h($foto2) ?>" alt="Mascota 2">
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold"><?= h((string)($mascota2Nombre ?: 'Mascota 2')) ?></div>
                                                <div class="text-muted small">Mascota 2</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-label">Dueño</div>
                                <div class="fw-semibold"><?= h($duenoNombre) ?></div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-label">Paseador</div>
                                <div class="fw-semibold"><?= h($paseadorNombre) ?></div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-label">Duración</div>
                                <div><?= (int)$duracionMin ?> minutos</div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-label">Monto</div>
                                <div class="fw-semibold">₲<?= number_format((float)$monto, 0, ',', '.') ?></div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-label">Inicio</div>
                                <div><?= $inicio ? h(date('d/m/Y H:i', strtotime((string)$inicio))) : '—' ?></div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-label">Última actualización</div>
                                <div><?= $updatedAt ? h(date('d/m/Y H:i', strtotime((string)$updatedAt))) : 'Sin cambios' ?></div>
                            </div>

                            <div class="col-12">
                                <div class="info-label">Dirección</div>
                                <div><?= h($direccion) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-bolt me-2"></i> Acciones del paseador
                    </div>
                    <div class="section-body">
                        <div class="acciones-wrap">

                            <?php if (in_array($estado, ['pendiente', 'solicitado'], true)): ?>
                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=confirmar"
                                   class="btn btn-success"
                                   onclick="return confirm('¿Confirmar este paseo?');">
                                    <i class="fas fa-check-circle me-1"></i> Confirmar
                                </a>

                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=cancelar"
                                   class="btn btn-danger"
                                   onclick="return confirm('¿Cancelar este paseo?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar
                                </a>

                            <?php elseif ($estado === 'confirmado'): ?>
                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=iniciar"
                                   class="btn btn-success"
                                   onclick="return confirm('¿Iniciar este paseo?');">
                                    <i class="fas fa-play me-1"></i> Iniciar
                                </a>

                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=cancelar"
                                   class="btn btn-danger"
                                   onclick="return confirm('¿Cancelar este paseo?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar
                                </a>

                            <?php elseif ($estado === 'en_curso'): ?>
                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=completar"
                                   class="btn btn-success"
                                   onclick="return confirm('¿Marcar este paseo como completado?');">
                                    <i class="fas fa-check-circle me-1"></i> Completar
                                </a>

                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=cancelar"
                                   class="btn btn-danger"
                                   onclick="return confirm('¿Cancelar paseo en curso?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar
                                </a>

                            <?php elseif (in_array($estado, ['completo', 'finalizado'], true)): ?>
                                <?php if ($puedeCalificar): ?>
                                    <button type="button"
                                            class="btn btn-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalCalificarMascota">
                                        <i class="fas fa-star me-1"></i> Calificar mascota
                                    </button>
                                <?php elseif ($yaCalifico): ?>
                                    <span class="badge bg-success align-self-center px-3 py-2">
                                        Ya enviaste tu calificación ⭐
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">No se puede calificar (estado o mascota inválida).</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No hay acciones disponibles para este estado.</span>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            </div>

            <!-- Der: mapa -->
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-map-location-dot me-2"></i> Ubicación y recorrido
                    </div>
                    <div class="section-body">
                        <div id="map"></div>
                        <p class="text-muted small mt-2 mb-0">
                            Si el paseo está <b>en curso</b>, se guardará tu ubicación automáticamente.
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <footer class="mt-4 text-center text-muted small">
            © <?= date('Y') ?> Jaguata — Panel Paseador
        </footer>
    </div>
</main>

<!-- MODAL: CALIFICAR MASCOTA -->
<div class="modal fade" id="modalCalificarMascota" tabindex="-1" aria-labelledby="modalCalificarMascotaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formCalificarMascota">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="modalCalificarMascotaLabel">
            <i class="fas fa-star me-2"></i>Calificar mascota
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="paseo_id" value="<?= (int)$paseoId; ?>">

          <?php if ($hayMascota2 && $mascota2Id > 0): ?>
            <div class="mb-3">
              <label class="form-label">¿Qué mascota querés calificar?</label>
              <select name="rated_id" class="form-select" required>
                <option value="<?= (int)$mascota1Id ?>"><?= h((string)$mascota1Nombre) ?></option>
                <option value="<?= (int)$mascota2Id ?>"><?= h((string)($mascota2Nombre ?: 'Mascota 2')) ?></option>
              </select>
              <div class="form-text text-muted">Se guarda como calificación tipo “mascota”.</div>
            </div>
          <?php else: ?>
            <input type="hidden" name="rated_id" value="<?= (int)$mascota1Id; ?>">
            <div class="mb-2 small text-muted">
              Mascota a calificar: <b><?= h((string)$mascota1Nombre) ?></b>
            </div>
          <?php endif; ?>

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
              placeholder="Comentá brevemente cómo fue la experiencia con la mascota..."></textarea>
          </div>

          <div id="califError" class="alert alert-danger d-none"></div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const pawIcon = L.icon({
        iconUrl: "<?= $baseUrl ?>/public/assets/images/paw.png",
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
        const polyline = L.polyline(rutaCoords, { weight: 5, opacity: 0.85 }).addTo(map);
        map.fitBounds(polyline.getBounds());

        const paso = 5;
        for (let i = 0; i < rutaCoords.length; i += paso) {
            const [lat, lng] = rutaCoords[i];
            L.marker([lat, lng], { icon: pawIcon }).addTo(map);
        }
    }

    const estadoActual = "<?= h($estado) ?>";
    const paseoIdJs = <?= (int)$paseoId ?>;

    if (estadoActual === 'en_curso' && "geolocation" in navigator) {
        navigator.geolocation.watchPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                fetch("<?= $baseUrl ?>/public/api/paseos/registrarPosicion.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `paseo_id=${encodeURIComponent(paseoIdJs)}&lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`
                }).catch(console.error);
            },
            (err) => console.error("Error geolocalización:", err),
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 }
        );
    }

    // ✅ Enviar calificación
    const formCalificarMascota = document.getElementById('formCalificarMascota');
    const califError = document.getElementById('califError');

    if (formCalificarMascota) {
        formCalificarMascota.addEventListener('submit', async (e) => {
            e.preventDefault();
            califError?.classList.add('d-none');
            if (califError) califError.textContent = '';

            const formData = new FormData(formCalificarMascota);

            try {
                const resp = await fetch('<?= $baseUrl; ?>/public/api/calificar_mascota.php', {
                    method: 'POST',
                    body: formData
                });

                const raw = await resp.text();
                let data;
                try { data = JSON.parse(raw); }
                catch {
                    alert('⚠️ Respuesta inválida del servidor');
                    console.error(raw);
                    return;
                }

                if (data.success) {
                    alert('✅ Calificación enviada correctamente');
                    window.location.reload();
                } else {
                    const msg = data.error || data.mensaje || 'No se pudo guardar la calificación.';
                    if (califError) {
                        califError.textContent = msg;
                        califError.classList.remove('d-none');
                    } else {
                        alert('⚠️ ' + msg);
                    }
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
