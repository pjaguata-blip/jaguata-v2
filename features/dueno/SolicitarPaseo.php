<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Autenticación */
$authController = new AuthController();
$authController->checkRole('dueno');

/* 🔒 (igual dashboard) */
if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$paseoController   = new PaseoController();
$mascotaController = new MascotaController();

/* Mascotas dueño */
$mascotas = $mascotaController->indexByDuenoActual();
if (empty($mascotas)) {
    $_SESSION['error'] = 'Debes tener al menos una mascota registrada para solicitar paseos';
    header('Location: AgregarMascota.php');
    exit;
}

/* Preselecciones */
$mascotaPreseleccionada  = (int)($_GET['mascota_id'] ?? 0);
$paseadorPreseleccionado = (int)($_GET['paseador_id'] ?? 0);

/* Crear */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paseoController->store();
}

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Rutas/UI */
$rolMenu       = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = h(Session::getUsuarioNombre() ?? 'Dueño');

/* Flash */
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Solicitar Paseo - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" />
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height: 100%; }
        body { background: var(--gris-fondo, #f4f6f9); }

        /* ✅ Layout igual al Dashboard */
        main.main-content {
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            min-height: 100vh;
            padding: 24px;
            box-sizing: border-box;
        }
        @media (max-width: 768px) {
            main.main-content {
                margin-left: 0 !important;
                width: 100% !important;
                margin-top: 0 !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        #mapa {
            border: 2px solid #dfe3e8;
            border-radius: 14px;
            overflow: hidden;
            min-height: 340px;
        }

        .step-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: rgba(60,98,85,.12);
            color: var(--verde-jaguata, #3c6255);
            font-weight: 700;
            margin-right: 10px;
        }

        .total-box{
            background: rgba(66, 225, 250, 0.10);
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 14px;
        }

        .hint-pill{
            display:inline-flex;
            gap:.5rem;
            align-items:center;
            padding:.4rem .7rem;
            border-radius:999px;
            background:#fff;
            border:1px solid rgba(15,23,42,.08);
            color:#0f172a;
            font-size:.9rem;
        }
    </style>
</head>

<body class="page-solicitar-paseo page-no-topbar-margin">

<?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

<main class="main-content">
    <div class="py-2">

        <!-- Header -->
        <div class="header-box header-paseos mb-3 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-1"><i class="fas fa-calendar-check me-2"></i>Solicitar Paseo</h1>
                <p class="mb-0">Hola, <?= $usuarioNombre; ?>. Seguí los pasos para agendar el paseo 🐾</p>
            </div>
            <div class="d-none d-md-flex gap-2">
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <!-- Flash -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <i class="fas fa-check-circle me-2"></i><?= h((string)$success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                <i class="fas fa-exclamation-triangle me-2"></i><?= h((string)$error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <!-- ✅ PASO 1 -->
            <div class="section-card mb-3">
                <div class="section-header">
                    <span class="step-badge">1</span> Elegí tus mascotas
                </div>
                <div class="section-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mascota 1 <span class="text-danger">*</span></label>
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

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Mascota 2 <small class="text-muted">(opcional — 30% desc.)</small>
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
                    </div>
                </div>
            </div>

            <!-- ✅ PASO 2 -->
            <div class="section-card mb-3">
                <div class="section-header">
                    <span class="step-badge">2</span> Elegí fecha, hora y duración
                </div>
                <div class="section-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha y hora <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="inicio" name="inicio" required>
                            <small class="text-muted">Se mostrarán solo paseadores libres en ese horario.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Duración <span class="text-danger">*</span></label>
                            <select class="form-select" id="duracion" name="duracion" required>
                                <option value="">Seleccionar duración</option>
                                <?php
                                $duraciones = [
                                    15 => '15 min', 30 => '30 min', 45 => '45 min',
                                    60 => '1 hora', 90 => '1.5 horas', 120 => '2 horas',
                                ];
                                foreach ($duraciones as $min => $label): ?>
                                    <option value="<?= (int)$min ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ PASO 3 -->
            <div class="section-card mb-3">
                <div class="section-header">
                    <span class="step-badge">3</span> Seleccioná la ubicación de recogida
                </div>
                <div class="section-body">

                    <label class="form-label fw-semibold">Buscar dirección <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="ubicacion"
                               name="ubicacion"
                               placeholder="Ej.: Av. España, Asunción"
                               required>
                        <button class="btn btn-outline-primary" type="button" id="btnBuscarDir">
                            <i class="fas fa-magnifying-glass me-1"></i> Buscar
                        </button>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                        <span class="hint-pill"><i class="fas fa-hand-pointer"></i> Tocá el mapa</span>
                        <span class="hint-pill"><i class="fas fa-arrows-up-down-left-right"></i> Arrastrá el pin</span>
                        <span class="hint-pill"><i class="fas fa-location-crosshairs"></i> Usá mi ubicación</span>
                    </div>

                    <input type="hidden" id="ciudad_ubicacion" name="ciudad_ubicacion">
                    <input type="hidden" id="pickup_lat" name="pickup_lat">
                    <input type="hidden" id="pickup_lng" name="pickup_lng">

                    <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-success btn-sm" id="btnUbicacion">
                            <i class="fas fa-location-crosshairs me-1"></i> Usar mi ubicación
                        </button>
                        <span id="ciudadDetectada" class="text-muted small"></span>
                        <span id="coordInfo" class="text-muted small"></span>
                    </div>

                    <div id="mapa" class="mt-3"></div>

                </div>
            </div>

            <!-- ✅ PASO 4 -->
            <div class="section-card mb-3">
                <div class="section-header">
                    <span class="step-badge">4</span> Elegí un paseador cercano y disponible
                </div>
                <div class="section-body">
                    <label class="form-label fw-semibold">Paseador <span class="text-danger">*</span></label>
                    <select class="form-select" id="paseador_id" name="paseador_id" required>
                        <option value="">Completá el Paso 2 y 3 para ver paseadores</option>
                    </select>
                    <small class="text-muted d-block mt-1" id="msgPaseadores"></small>
                </div>
            </div>

            <!-- ✅ PASO 5 -->
            <div class="section-card">
                <div class="section-header">
                    <span class="step-badge">5</span> Confirmá el total y solicitá
                </div>
                <div class="section-body">
                    <div class="total-box p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-bold"><i class="fas fa-receipt me-2"></i>Total estimado</div>
                                <small class="text-muted">2 mascotas → -30% aplicado automáticamente.</small>
                            </div>
                            <div class="text-end">
                                <div class="h4 mb-0" id="totalEstimado">₲0</div>
                                <small class="text-muted" id="detalleTotal"></small>
                            </div>
                        </div>
                        <input type="hidden" id="total_estimado" name="total_estimado" value="0">
                    </div>

                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-enviar">
                            <i class="fas fa-paper-plane me-1"></i> Solicitar Paseo
                        </button>
                    </div>
                </div>
            </div>

        </form>

        <footer class="mt-4 text-center text-muted small">
            © <?= date('Y') ?> Jaguata — Panel del Dueño
        </footer>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // ==========================
    // Helpers UI
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

    // ==========================
    // Mascotas: no repetir
    // ==========================
    function syncMascotas() {
        const m1 = document.getElementById('mascota_id_1');
        const m2 = document.getElementById('mascota_id_2');
        const msg = document.getElementById('msgMascota2');
        if (!m1 || !m2) return;

        const val1 = m1.value;
        [...m2.options].forEach(opt => opt.disabled = false);

        if (val1) {
            [...m2.options].forEach(opt => {
                if (opt.value === val1) opt.disabled = true;
            });
        }

        if (m2.value && val1 && m2.value === val1) {
            m2.value = '';
            msg.textContent = '⚠️ No podés seleccionar la misma mascota dos veces.';
        } else {
            msg.textContent = '';
        }
    }

    // ==========================
    // Map + ubicación funcional
    // ==========================
    let mapa = L.map('mapa').setView([-25.3, -57.6], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapa);

    let marcador = null;

    function setMarker(lat, lng, zoom = 16) {
        document.getElementById('pickup_lat').value = lat;
        document.getElementById('pickup_lng').value = lng;
        document.getElementById('coordInfo').textContent = `📌 ${lat.toFixed(6)}, ${lng.toFixed(6)}`;

        if (marcador) {
            marcador.setLatLng([lat, lng]);
        } else {
            marcador = L.marker([lat, lng], { draggable: true }).addTo(mapa);
            marcador.on('dragend', async (e) => {
                const p = e.target.getLatLng();
                await reverseGeocode(p.lat, p.lng);
                await cargarPaseadoresCercanos();
            });
        }
        mapa.setView([lat, lng], zoom);
    }

    async function reverseGeocode(lat, lng) {
        try {
            const url = '<?= BASE_URL; ?>/public/api/reverse_geocode.php?lat=' + lat + '&lng=' + lng;
            const res = await fetch(url);

            const text = await res.text();
            let data = null;
            try { data = JSON.parse(text); } catch { return; }

            if (!data?.error) {
                const address = data.address || {};
                const ciudad = address.city || address.town || address.village || '';
                const display = data.display_name || '';

                if (display) document.getElementById('ubicacion').value = display;
                document.getElementById('ciudad_ubicacion').value = ciudad;
                document.getElementById('ciudadDetectada').textContent =
                    ciudad ? `📍 Ciudad detectada: ${ciudad}` : '';
            }
        } catch (e) {
            console.error('reverseGeocode error', e);
        }
    }

    // ✅ CORREGIDO: ruta singular /api/paseador/rangos.php
    async function sugerirRangos(dia) {
        try {
            const r = await fetch(`<?= BASE_URL; ?>/public/api/paseador/rangos.php?dia=${encodeURIComponent(dia)}`);
            const text = await r.text();
            const j = JSON.parse(text);

            if (!j.ok || !j.data?.length) return '';

            const txt = j.data
                .map(x => `#${x.paseador_id} (${String(x.hora_inicio).slice(0,5)}–${String(x.hora_fin).slice(0,5)})`)
                .join('  |  ');

            return `Rangos disponibles para ${dia}: ${txt}`;
        } catch {
            return '';
        }
    }

    mapa.on('click', async (e) => {
        const { lat, lng } = e.latlng;
        setMarker(lat, lng);
        await reverseGeocode(lat, lng);
        await cargarPaseadoresCercanos();
    });

    async function buscarDireccion() {
        const q = (document.getElementById('ubicacion')?.value || '').trim();
        if (!q) return;

        try {
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (!data || data.length === 0) {
                alert('No se encontró esa dirección. Probá con otra más específica.');
                return;
            }

            const lat = Number(data[0].lat);
            const lng = Number(data[0].lon);
            setMarker(lat, lng, 16);
            await reverseGeocode(lat, lng);
            await cargarPaseadoresCercanos();

        } catch (e) {
            console.error(e);
            alert('Error buscando dirección. Probá otra vez.');
        }
    }

    document.getElementById('btnBuscarDir')?.addEventListener('click', buscarDireccion);
    document.getElementById('ubicacion')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarDireccion();
        }
    });

    document.getElementById('btnUbicacion')?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            alert("Tu navegador no soporta geolocalización.");
            return;
        }
        navigator.geolocation.getCurrentPosition(async pos => {
            const { latitude, longitude } = pos.coords;
            setMarker(latitude, longitude, 16);
            await reverseGeocode(latitude, longitude);
            await cargarPaseadoresCercanos();
        }, err => alert("No se pudo obtener la ubicación: " + err.message));
    });

    // ==========================
    // Paseadores cercanos y libres
    // ==========================
    async function cargarPaseadoresCercanos() {
        const lat = Number(document.getElementById('pickup_lat')?.value || 0);
        const lng = Number(document.getElementById('pickup_lng')?.value || 0);
        const inicio = document.getElementById('inicio')?.value || '';
        const duracion = Number(document.getElementById('duracion')?.value || 0);

        const sel = document.getElementById('paseador_id');
        const msg = document.getElementById('msgPaseadores');
        if (!sel) return;

        // reset si falta algo
        if (!lat || !lng || !inicio || !duracion) {
            sel.innerHTML = `<option value="">Completá el Paso 2 y 3 para ver paseadores</option>`;
            msg.textContent = '';
            recalcularTotal();
            return;
        }

        sel.innerHTML = `<option value="">Buscando paseadores cercanos...</option>`;
        msg.textContent = '';

        try {
            // ✅ CORREGIDO: ruta singular /api/paseador/disponibles.php
            const url = `<?= BASE_URL; ?>/public/api/paseador/disponibles.php?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}&inicio=${encodeURIComponent(inicio)}&duracion=${encodeURIComponent(duracion)}&radio_km=10&limit=30`;

            const res = await fetch(url);

            // Si el servidor devuelve HTML (error), evitamos crash
            const text = await res.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch {
                console.error('Respuesta NO JSON:', text);
                sel.innerHTML = `<option value="">Error del servidor (respuesta no JSON)</option>`;
                msg.textContent = 'Revisá la consola (F12) y la ruta del endpoint.';
                recalcularTotal();
                return;
            }

            if (!data.ok) {
                sel.innerHTML = `<option value="">No se pudo cargar paseadores</option>`;
                msg.textContent = data.error || 'Error desconocido';
                recalcularTotal();
                return;
            }

            if (!data.data || data.data.length === 0) {
                sel.innerHTML = `<option value="">No hay paseadores disponibles para ese horario</option>`;
                const extra = await sugerirRangos(data.dia || '');
                msg.textContent = extra || 'Probá con otra hora o mové el punto de recogida.';
                recalcularTotal();
                return;
            }

            sel.innerHTML = `<option value="">Seleccionar paseador (cercanos y libres)</option>`;

            data.data.forEach(p => {
                const km = Number(p.distancia_km || 0).toFixed(1);
                const precio = Number(p.precio_hora || 0);

                const opt = document.createElement('option');
                opt.value = p.paseador_id;
                opt.dataset.precio = precio;

                const zona = (p.zona || '').trim();
                const labelZona = zona ? `— ${zona}` : '';
                opt.textContent = `${p.nombre} ${labelZona} (${km} km) — ${formatGs(precio)}/hora`;

                if (Number(opt.value) === Number(<?= (int)$paseadorPreseleccionado ?>)) {
                    opt.selected = true;
                }

                sel.appendChild(opt);
            });

            msg.textContent = `Mostrando ${data.data.length} paseadores cercanos disponibles.`;
            recalcularTotal();

        } catch (err) {
            console.error(err);
            sel.innerHTML = `<option value="">Error de red al cargar paseadores</option>`;
            msg.textContent = 'Revisá tu conexión o el servidor.';
            recalcularTotal();
        }
    }

    // ==========================
    // Init + eventos
    // ==========================
    document.addEventListener('DOMContentLoaded', () => {
        // Fecha/hora mínima (ahora + 2h)
        const now = new Date();
        now.setHours(now.getHours() + 2);
        const formatted = now.toISOString().slice(0, 16);

        const inicio = document.getElementById('inicio');
        inicio.min = formatted;
        inicio.value = formatted;

        // Reglas mascotas + total
        ['mascota_id_1','mascota_id_2','paseador_id','duracion'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => {
                syncMascotas();
                recalcularTotal();
            });
        });

        // Re-cargar paseadores cuando cambia horario
        document.getElementById('inicio')?.addEventListener('change', cargarPaseadoresCercanos);
        document.getElementById('duracion')?.addEventListener('change', cargarPaseadoresCercanos);

        syncMascotas();
        recalcularTotal();
        cargarPaseadoresCercanos();
    });
</script>


</body>
</html>
