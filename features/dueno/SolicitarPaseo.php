<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Models/Paseador.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;
use Jaguata\Models\Paseador;

AppConfig::init();

/* 🔒 Autenticación */
$authController = new AuthController();
$authController->checkRole('dueno');

/* Controladores */
$paseoController   = new PaseoController();
$mascotaController = new MascotaController();

/* 🐶 Mascotas DEL DUEÑO logueado */
$mascotas = $mascotaController->indexByDuenoActual();

/* Redirección si no hay mascotas */
if (empty($mascotas)) {
    $_SESSION['error'] = 'Debes tener al menos una mascota registrada para solicitar paseos';
    header('Location: AgregarMascota.php');
    exit;
}

/* Parámetros de entrada */
$mascotaPreseleccionada  = (int)($_GET['mascota_id'] ?? 0);
$paseadorPreseleccionado = (int)($_GET['paseador_id'] ?? 0);
$fechaFiltro             = trim((string)($_GET['fecha'] ?? ''));
$ciudadSeleccionada      = strtolower(trim((string)($_POST['ciudad_ubicacion'] ?? $_GET['ciudad'] ?? '')));

/* Crear reserva */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paseoController->store();
}

/* Paseadores disponibles (con/sin filtro por fecha) */
$paseadorModel = new Paseador();
$allPaseadores = $fechaFiltro
    ? $paseadorModel->getDisponibles($fechaFiltro)
    : $paseadorModel->getDisponibles();

/* Filtro por ciudad/zona */
$paseadores = array_values(array_filter($allPaseadores, function ($p) use ($ciudadSeleccionada) {
    if ($ciudadSeleccionada === '') return true;
    $ciudadP = strtolower(trim((string)($p['ciudad'] ?? '')));
    $zonas   = strtolower(trim((string)($p['zona'] ?? '')));
    return $ciudadP === $ciudadSeleccionada || str_contains($zonas, $ciudadSeleccionada);
}));

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Rutas/UI */
$rolMenu       = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = h(Session::getUsuarioNombre() ?? 'Dueño');

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Solicitar Paseo - Jaguata</title>

    <!-- CSS base -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" />
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Contenido -->
    <main>
        <div class="py-4">

            <!-- Header unificado -->
            <div class="header-box header-paseos mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">
                        <i class="fas fa-calendar-check me-2"></i>Solicitar Paseo
                    </h1>
                    <p class="mb-0">
                        Hola, <?= $usuarioNombre; ?>. Completá los datos para agendar el paseo de tu mascota 🐾
                    </p>
                </div>
                <div class="d-none d-md-block">
                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver al panel
                    </a>
                </div>
            </div>

            <!-- Flash messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success shadow-sm">
                    <i class="fas fa-check-circle me-2"></i><?= h($_SESSION['success']); ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= h($_SESSION['error']); ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Bloque principal -->
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-dog me-2"></i>Información del paseo
                </div>

                <div class="section-body">
                    <form method="POST" novalidate>
                        <div class="row g-4">

                            <!-- Mascota 1 -->
                            <div class="col-md-6">
                                <label for="mascota_id_1" class="form-label">
                                    Mascota 1 <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="mascota_id_1" name="mascota_id_1" required>
                                    <option value="">Seleccionar mascota</option>
                                    <?php foreach ($mascotas as $m): ?>
                                        <?php $idM = (int)($m['mascota_id'] ?? $m['id'] ?? 0); ?>
                                        <option value="<?= $idM ?>" <?= $idM === $mascotaPreseleccionada ? 'selected' : '' ?>>
                                            <?= h($m['nombre'] ?? '') ?>
                                            <?php if (!empty($m['tamano'])): ?>
                                                (<?= ucfirst(h((string)$m['tamano'])) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Mascota 2 (opcional) -->
                            <div class="col-md-6">
                                <label for="mascota_id_2" class="form-label">
                                    Mascota 2 <small class="text-muted">(opcional — hasta 2 por paseo)</small>
                                </label>
                                <select class="form-select" id="mascota_id_2" name="mascota_id_2">
                                    <option value="">No agregar segunda mascota</option>
                                    <?php foreach ($mascotas as $m): ?>
                                        <?php $idM = (int)($m['mascota_id'] ?? $m['id'] ?? 0); ?>
                                        <option value="<?= $idM ?>">
                                            <?= h($m['nombre'] ?? '') ?>
                                            <?php if (!empty($m['tamano'])): ?>
                                                (<?= ucfirst(h((string)$m['tamano'])) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1" id="msgMascota2"></small>
                            </div>

                            <!-- Paseador -->
                            <div class="col-md-6">
                                <label for="paseador_id" class="form-label">Paseador <span class="text-danger">*</span></label>
                                <select class="form-select" id="paseador_id" name="paseador_id" required>
                                    <option value="">
                                        Seleccionar paseador<?= $ciudadSeleccionada ? ' (' . ucfirst(h($ciudadSeleccionada)) . ')' : '' ?>
                                    </option>
                                    <?php foreach ($paseadores as $p): ?>
                                        <?php
                                        $pid    = (int)($p['paseador_id'] ?? 0);
                                        $precio = is_numeric($p['precio_hora'] ?? null) ? (float)$p['precio_hora'] : 0.0;
                                        $sel    = $pid === $paseadorPreseleccionado ? 'selected' : '';
                                        $nombre = $p['nombre'] ?? $p['usuario_nombre'] ?? '';
                                        ?>
                                        <option value="<?= $pid ?>" data-precio="<?= $precio ?>" <?= $sel ?>>
                                            <?= h($nombre) ?> - <?= ucfirst(h((string)($p['ciudad'] ?? ''))) ?>
                                            — ₲<?= number_format($precio, 0, ',', '.') ?>/hora
                                            <?php if (isset($p['calificacion'])): ?>
                                                (⭐ <?= (float)$p['calificacion'] ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Fecha / hora -->
                            <div class="col-md-6">
                                <label for="inicio" class="form-label">Fecha y hora <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="inicio" name="inicio" required>
                            </div>

                            <!-- Duración -->
                            <div class="col-md-6">
                                <label for="duracion" class="form-label">Duración <span class="text-danger">*</span></label>
                                <select class="form-select" id="duracion" name="duracion" required>
                                    <option value="">Seleccionar duración</option>
                                    <?php
                                    $duraciones = [
                                        15  => '15 min',
                                        30  => '30 min',
                                        45  => '45 min',
                                        60  => '1 hora',
                                        90  => '1.5 horas',
                                        120 => '2 horas',
                                    ];
                                    foreach ($duraciones as $min => $label): ?>
                                        <option value="<?= (int)$min ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Total estimado -->
                            <div class="col-12">
                                <div class="p-3 rounded-3" style="background:#f4f6f9;border:1px solid #dee2e6;">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div>
                                            <div class="fw-semibold">
                                                <i class="fas fa-receipt me-2"></i>Total estimado
                                            </div>
                                            <small class="text-muted">
                                                Si elegís 2 mascotas, se aplica 30% de descuento.
                                            </small>
                                        </div>

                                        <div class="text-end">
                                            <div class="h4 mb-0" id="totalEstimado">₲0</div>
                                            <small class="text-muted" id="detalleTotal"></small>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" id="total_estimado" name="total_estimado" value="0">
                            </div>

                            <!-- Ubicación / punto de recogida -->
                            <div class="col-12">
                                <label for="ubicacion" class="form-label">Ubicación de recogida <span class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control"
                                    id="ubicacion"
                                    name="ubicacion"
                                    placeholder="Ej.: Asunción, Calle A Nº1234"
                                    required>

                                <!-- Campos ocultos -->
                                <input type="hidden" id="ciudad_ubicacion" name="ciudad_ubicacion">
                                <input type="hidden" id="pickup_lat" name="pickup_lat">
                                <input type="hidden" id="pickup_lng" name="pickup_lng">

                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-success btn-sm" id="btnUbicacion">
                                        <i class="fas fa-location-crosshairs me-1"></i> Detectar mi ubicación
                                    </button>
                                    <span id="ciudadDetectada" class="text-muted small align-self-center"></span>
                                </div>

                                <div id="mapa" class="mt-3"
                                    style="height: 320px; border-radius: 12px; overflow: hidden; border: 2px solid #dfe3e8;"></div>
                            </div>

                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-gradient">
                                <i class="fas fa-paper-plane me-1"></i> Solicitar Paseo
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Dueño
            </footer>
        </div>
    </main>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Leaflet: mapa para elegir punto de recogida
        let mapa = L.map('mapa').setView([-25.3, -57.6], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapa);
        let marcador;

        async function actualizarDireccionYCiudad(lat, lng) {
            document.getElementById('pickup_lat').value = lat;
            document.getElementById('pickup_lng').value = lng;

            document.getElementById('ubicacion').value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

            try {
                const url = '<?= BASE_URL; ?>/api/reverse_geocode.php?lat=' + lat + '&lng=' + lng;
                const res = await fetch(url);
                const data = await res.json();

                if (data.error) {
                    console.error('Error reverse_geocode:', data.error);
                    return;
                }

                const address = data.address || {};
                const ciudad = address.city || address.town || address.village || '';

                document.getElementById('ciudad_ubicacion').value = ciudad;
                document.getElementById('ciudadDetectada').textContent =
                    ciudad ? `📍 Ciudad detectada: ${ciudad}` : '';
            } catch (err) {
                console.error("Error al obtener ciudad:", err);
            }
        }

        mapa.on('click', function(e) {
            const {
                lat,
                lng
            } = e.latlng;
            if (marcador) mapa.removeLayer(marcador);
            marcador = L.marker([lat, lng]).addTo(mapa).bindPopup("Ubicación seleccionada").openPopup();
            actualizarDireccionYCiudad(lat, lng);
        });

        document.getElementById('btnUbicacion')?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert("Tu navegador no soporta geolocalización.");
                return;
            }
            navigator.geolocation.getCurrentPosition(async pos => {
                const {
                    latitude,
                    longitude
                } = pos.coords;
                mapa.setView([latitude, longitude], 16);
                if (marcador) mapa.removeLayer(marcador);
                marcador = L.marker([latitude, longitude]).addTo(mapa).bindPopup("Ubicación detectada").openPopup();
                actualizarDireccionYCiudad(latitude, longitude);
            }, err => alert("No se pudo obtener la ubicación: " + err.message));
        });

        // Fecha/hora mínima (ahora + 2h)
        document.addEventListener('DOMContentLoaded', () => {
            const now = new Date();
            now.setHours(now.getHours() + 2);
            const formatted = now.toISOString().slice(0, 16);
            const inicio = document.getElementById('inicio');
            inicio.min = formatted;
            inicio.value = formatted;
        });

        // ==========================
        // Mascotas: no permitir repetir
        // ==========================
        function syncMascotas() {
            const m1 = document.getElementById('mascota_id_1');
            const m2 = document.getElementById('mascota_id_2');
            const msg = document.getElementById('msgMascota2');
            if (!m1 || !m2) return;

            const val1 = m1.value;

            // reset disabled
            [...m2.options].forEach(opt => opt.disabled = false);

            // deshabilitar en Mascota 2 la elegida en Mascota 1
            if (val1) {
                [...m2.options].forEach(opt => {
                    if (opt.value === val1) opt.disabled = true;
                });
            }

            // si eran iguales -> limpiar m2
            if (m2.value && val1 && m2.value === val1) {
                m2.value = '';
                msg.textContent = '⚠️ No podés seleccionar la misma mascota dos veces.';
            } else {
                msg.textContent = '';
            }
        }

        // ==========================
        // Total estimado con descuento
        // ==========================
        function formatGs(n) {
            const v = Math.max(0, Math.round(Number(n) || 0));
            return '₲' + v.toLocaleString('es-PY');
        }

        function getPrecioHora() {
            const sel = document.getElementById('paseador_id');
            const opt = sel?.selectedOptions?.[0];
            const precio = opt ? Number(opt.dataset.precio || 0) : 0;
            return isFinite(precio) ? precio : 0;
        }

        function getDuracionMin() {
            return Number(document.getElementById('duracion')?.value || 0) || 0;
        }

        function getCantidadMascotas() {
            const m1 = Number(document.getElementById('mascota_id_1')?.value || 0) || 0;
            const m2 = Number(document.getElementById('mascota_id_2')?.value || 0) || 0;
            let cant = 0;
            if (m1 > 0) cant++;
            if (m2 > 0) cant++;
            return cant;
        }

        function recalcularTotal() {
            const precioHora = getPrecioHora();
            const durMin = getDuracionMin();
            const cant = getCantidadMascotas();
            const horas = durMin > 0 ? (durMin / 60) : 0;

            let total = 0;
            let detalle = '';

            if (cant === 2) {
                total = (precioHora * 2) * horas * 0.70;
                detalle = `${formatGs(precioHora)} x ${horas.toFixed(2)}h x 2 mascotas -30%`;
            } else if (cant === 1) {
                total = precioHora * horas;
                detalle = `${formatGs(precioHora)} x ${horas.toFixed(2)}h`;
            } else {
                total = 0;
                detalle = 'Seleccioná al menos una mascota.';
            }

            document.getElementById('totalEstimado').textContent = formatGs(total);
            document.getElementById('detalleTotal').textContent = detalle;
            document.getElementById('total_estimado').value = String(Math.round(total));
        }

        document.addEventListener('DOMContentLoaded', () => {
            ['mascota_id_1', 'mascota_id_2', 'paseador_id', 'duracion'].forEach(id => {
                document.getElementById(id)?.addEventListener('change', () => {
                    syncMascotas();
                    recalcularTotal();
                });
            });

            syncMascotas();
            recalcularTotal();
        });
    </script>
</body>

</html>