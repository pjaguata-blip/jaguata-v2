<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Models/Calificacion.php';
require_once __DIR__ . '/../../src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;
use Jaguata\Models\Calificacion;
use Jaguata\Services\DatabaseService;

AppConfig::init();

// 🔹 Solo paseador
$auth = new AuthController();
$auth->checkRole('paseador');

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

// 🔹 ID de paseo
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('<h3 style="color:red;text-align:center;">ID de paseo no válido.</h3>');
}

// 🔹 Cargar datos reales del paseo (vista paseador)
$paseoController = new PaseoController();
$paseo = $paseoController->show($id);   // joins con mascota/dueño/paseador

if (!$paseo) {
    die('<h3 style="color:red;text-align:center;">No se encontró el paseo solicitado.</h3>');
}

// 🔹 Validar que el paseo pertenezca al paseador logueado
$paseadorIdSesion = (int)(Session::getUsuarioId() ?? 0);
if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorIdSesion) {
    die('<h3 style="color:red;text-align:center;">No tenés permiso para ver este paseo.</h3>');
}

// =========================
// Normalización / datos base
// =========================
$mascotaNombre  = $paseo['mascota_nombre']  ?? $paseo['nombre_mascota'] ?? '-';
$paseadorNombre = $paseo['paseador_nombre'] ?? $paseo['nombre_paseador'] ?? '-';
$duenoNombre    = $paseo['dueno_nombre']    ?? $paseo['nombre_dueno'] ?? '-';

$duracionMin = (int)($paseo['duracion'] ?? $paseo['duracion_min'] ?? 0);
$monto       = (float)($paseo['precio_total'] ?? $paseo['monto'] ?? 0);

$estadoRaw  = strtolower(trim((string)($paseo['estado'] ?? 'pendiente')));
$estado     = $estadoRaw ?: 'pendiente';

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

// 🔹 Punto de recogida
$pickupLat = $paseo['pickup_lat'] ?? null;
$pickupLng = $paseo['pickup_lng'] ?? null;

// 🔹 Ruta del paseo (hasta el momento)
$rutaPuntos = $paseoController->getRuta($id);
$rutaCoords = [];
foreach ($rutaPuntos as $punto) {
    $rutaCoords[] = [(float)$punto['latitud'], (float)$punto['longitud']];
}

// Para acciones
$paseoId = (int)($paseo['paseo_id'] ?? $id);

// =========================
// DUEÑO ID (CRÍTICO PARA FK)
// =========================
// 1) Intento directo desde show()
$duenoId = (int)($paseo['dueno_id'] ?? 0);

// 2) Fallback SQL si no viene en show()
if ($duenoId <= 0) {
    $db = DatabaseService::getInstance()->getConnection();
    $st = $db->prepare("
        SELECT m.dueno_id
        FROM paseos p
        INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
        WHERE p.paseo_id = :paseo_id
        LIMIT 1
    ");
    $st->execute([':paseo_id' => $paseoId]);
    $duenoId = (int)($st->fetch(PDO::FETCH_ASSOC)['dueno_id'] ?? 0);
}

// =========================
// Calificación del paseador
// =========================
// Nota: tu BD exige rated_id -> usuarios.usu_id
$califModel = new Calificacion();
$yaCalifico = $califModel->existeParaPaseo($paseoId, 'mascota', $paseadorIdSesion);

// Solo permitir si paseo finalizado y duenoId válido
$puedeCalificar = $duenoId > 0 && in_array($estado, ['completo', 'finalizado'], true) && !$yaCalifico;

// =========================
// Rutas
// =========================
$baseUrl = AppConfig::getBaseUrl();
$backUrl = $baseUrl . "/features/paseador/MisPaseos.php";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paseo #<?= h((string)$id) ?> - Paseador | Jaguata</title>

    <!-- Bootstrap / FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Theme global -->
    <link href="<?= $baseUrl ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        #map {
            width: 100%;
            height: 320px;
            border-radius: 0.75rem;
        }

        .btn-volver {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .4rem .9rem;
            border-radius: 999px;
            border: 1px solid #ddd;
            font-size: .9rem;
            text-decoration: none;
            color: #333;
            background-color: #fff;
        }

        .btn-volver:hover {
            background-color: #f1f1f1;
        }

        .info-label {
            font-size: .85rem;
            text-transform: uppercase;
            color: #888;
            margin-bottom: .2rem;
        }

        main {
            margin-left: 260px;
            padding: 1.5rem 1.5rem 2rem;
        }

        @media (max-width:768px) {
            main {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-4">

            <?php if ($debug): ?>
                <div class="alert alert-warning">
                    <strong>DEBUG:</strong>
                    paseo_id=<?= (int)$paseoId ?> |
                    paseador_sesion=<?= (int)$paseadorIdSesion ?> |
                    dueno_id=<?= (int)$duenoId ?> |
                    estado="<?= h($estado) ?>"
                </div>
            <?php endif; ?>

            <div class="header-box header-paseos mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">Detalle del Paseo #<?= h((string)$id) ?></h1>
                    <p class="mb-0">Información completa del recorrido, estado y ubicación 🐾</p>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <i class="fas fa-map-location-dot fa-3x opacity-75"></i>
                    <a href="#map" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-map"></i> Ver mapa
                    </a>
                </div>
            </div>

            <div class="mb-3">
                <a href="<?= h($backUrl) ?>" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver a mis paseos
                </a>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-dog me-2"></i> Información del Paseo
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <p class="info-label">Mascota:</p>
                                    <p><?= h($mascotaNombre) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Dueño:</p>
                                    <p><?= h($duenoNombre) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Paseador:</p>
                                    <p><?= h($paseadorNombre) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Duración:</p>
                                    <p><?= (int)$duracionMin ?> minutos</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Monto:</p>
                                    <p>₲<?= number_format((float)$monto, 0, ',', '.') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Estado:</p>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= h(ucfirst(str_replace('_', ' ', $estado))) ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Fecha de inicio:</p>
                                    <p><?= $inicio ? h(date('d/m/Y H:i', strtotime($inicio))) : '-' ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Última actualización:</p>
                                    <p><?= $updatedAt ? h(date('d/m/Y H:i', strtotime($updatedAt))) : 'Sin cambios' ?></p>
                                </div>
                                <div class="col-12">
                                    <p class="info-label">Dirección:</p>
                                    <p><?= h($direccion) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-tools me-2"></i> Acciones del paseador
                        </div>
                        <div class="card-body text-center d-flex flex-wrap justify-content-center gap-2">

                            <?php if (in_array($estado, ['pendiente', 'solicitado'], true)): ?>
                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=confirmar"
                                    class="btn btn-success"
                                    onclick="return confirm('¿Confirmar este paseo?');">
                                    <i class="fas fa-check-circle me-1"></i> Confirmar paseo
                                </a>
                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=cancelar"
                                    class="btn btn-danger"
                                    onclick="return confirm('¿Cancelar este paseo?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar paseo
                                </a>

                            <?php elseif ($estado === 'confirmado'): ?>
                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=iniciar"
                                    class="btn btn-success"
                                    onclick="return confirm('¿Iniciar este paseo?');">
                                    <i class="fas fa-play me-1"></i> Iniciar paseo
                                </a>
                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=cancelar"
                                    class="btn btn-danger"
                                    onclick="return confirm('¿Cancelar este paseo?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar paseo
                                </a>

                            <?php elseif ($estado === 'en_curso'): ?>
                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=completar"
                                    class="btn btn-success"
                                    onclick="return confirm('¿Marcar este paseo como completado?');">
                                    <i class="fas fa-check-circle me-1"></i> Completar paseo
                                </a>
                                <a href="AccionPaseo.php?id=<?= (int)$paseoId ?>&accion=cancelar"
                                    class="btn btn-danger"
                                    onclick="return confirm('¿Cancelar paseo en curso?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar paseo
                                </a>

                            <?php elseif (in_array($estado, ['completo', 'finalizado'], true)): ?>
                                <?php if ($puedeCalificar): ?>
                                    <button type="button"
                                        class="btn btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalCalificarMascota">
                                        <i class="fas fa-star me-1"></i> Calificar dueño / mascota
                                    </button>
                                <?php elseif ($yaCalifico): ?>
                                    <span class="badge bg-success align-self-center">
                                        Ya enviaste tu calificación ⭐
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">
                                        No se puede calificar (dueño inválido o estado no permitido).
                                    </span>
                                <?php endif; ?>

                            <?php else: ?>
                                <span class="text-muted">No hay acciones disponibles para este estado.</span>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-map-marker-alt me-2"></i> Ubicación y recorrido
                        </div>
                        <div class="card-body">
                            <div id="map"></div>
                        </div>
                    </div>
                </div>
            </div>

            <footer><small>© <?= date('Y') ?> Jaguata — Panel Paseador</small></footer>
        </div>
    </main>

    <!-- MODAL: CALIFICAR DUEÑO -->
    <div class="modal fade" id="modalCalificarMascota" tabindex="-1" aria-labelledby="modalCalificarMascotaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formCalificarMascota">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalCalificarMascotaLabel">
                            <i class="fas fa-star me-2"></i>Calificar dueño / mascota
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="paseo_id" value="<?= (int)$paseoId; ?>">
                        <!-- CLAVE: rated_id debe ser USUARIO (dueño) -->
                        <input type="hidden" name="rated_id" value="<?= (int)$duenoId; ?>">

                        <div class="mb-2 small text-muted">
                            (Se guardará como calificación del servicio del dueño/mascota, asociada al dueño.)
                        </div>

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
                                placeholder="Comentá brevemente cómo fue la experiencia con el dueño y la mascota..."></textarea>
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

    <!-- Bootstrap bundle (una sola vez) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar toggle (si tu template tiene btnSidebarToggle/backdrop) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.querySelector('.sidebar-backdrop');
            const btnToggle = document.getElementById('btnSidebarToggle');

            if (btnToggle && sidebar && backdrop) {
                btnToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('show');
                    backdrop.classList.toggle('show');
                });

                backdrop.addEventListener('click', () => {
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                });
            }
        });
    </script>

    <!-- Mapa + tracking + envío calificación -->
    <script>
        // === Datos para el mapa ===
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
            iconUrl: "<?= $baseUrl ?>/public/assets/images/paw.png",
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        if (pickupLat && pickupLng) {
            L.marker([pickupLat, pickupLng], {
                    icon: pawIcon
                })
                .addTo(map)
                .bindPopup('Punto de recogida')
                .openPopup();
        }

        if (rutaCoords.length > 0) {
            const polyline = L.polyline(rutaCoords, {
                weight: 5,
                opacity: 0.8
            }).addTo(map);
            map.fitBounds(polyline.getBounds());

            const paso = 5;
            for (let i = 0; i < rutaCoords.length; i += paso) {
                const [lat, lng] = rutaCoords[i];
                L.marker([lat, lng], {
                    icon: pawIcon
                }).addTo(map);
            }
        }

        // Tracking si está EN CURSO
        const estadoActual = "<?= h($estado) ?>";
        const paseoIdJs = <?= (int)$paseoId ?>;

        if (estadoActual === 'en_curso' && "geolocation" in navigator) {
            navigator.geolocation.watchPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;

                    fetch("<?= $baseUrl ?>/public/api/paseos/registrarPosicion.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `paseo_id=${encodeURIComponent(paseoIdJs)}&lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`
                    }).catch(console.error);
                },
                (err) => console.error("Error geolocalización:", err), {
                    enableHighAccuracy: true,
                    maximumAge: 5000,
                    timeout: 10000
                }
            );
        }

        // Envío AJAX de la calificación (paseador → dueño, tipo 'mascota')
        const formCalificarMascota = document.getElementById('formCalificarMascota');
        if (formCalificarMascota) {
            formCalificarMascota.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(formCalificarMascota);

                // Debug en consola
                <?php if ($debug): ?>
                    console.log("POST calificación:", Object.fromEntries(formData.entries()));
                <?php endif; ?>

                try {
                    const resp = await fetch('<?= $baseUrl; ?>/public/api/calificar_mascota.php', {
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