<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Autenticación */
$authController = new AuthController();
$authController->checkRole('dueno');

/* Controladores */
$paseoController   = new PaseoController();
$mascotaController = new MascotaController();

/* Datos base */
$mascotas = $mascotaController->index();

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
$paseadorModel = new \Jaguata\Models\Paseador();
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
$baseFeatures  = BASE_URL . "/features/dueno";
$usuarioNombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Dueño', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Solicitar Paseo - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet/dist/leaflet.css" rel="stylesheet" />
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        body {
            font-family: "Poppins", sans-serif;
            background: var(--gris-fondo);
            color: var(--gris-texto)
        }

        /* Sidebar (igual que admin) */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, .2)
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: .2s;
            font-size: .95rem
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px)
        }

        /* Main */
        main {
            margin-left: 250px;
            padding: 2rem
        }

        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: var(--blanco);
            padding: 1.8rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .07)
        }

        .card-header {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            background: var(--verde-jaguata);
            color: #fff;
            font-weight: 600
        }

        .form-select,
        .form-control {
            border-radius: 10px
        }

        .btn-gradient {
            background: linear-gradient(90deg, var(--verde-jaguata), var(--verde-claro));
            border: none;
            color: #fff;
            font-weight: 500;
            border-radius: 8px;
            padding: 10px 18px
        }

        .btn-gradient:hover {
            opacity: .92;
            transform: translateY(-1px)
        }

        #mapa {
            height: 320px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #dfe3e8
        }

        footer {
            text-align: center;
            padding: 1rem;
            color: #777;
            font-size: .9rem;
            margin-top: 2rem
        }

        .alert {
            border-radius: 10px
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Contenido -->
    <main>
        <!-- Header -->
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold"><i class="fas fa-calendar-check me-2"></i>Solicitar Paseo</h1>
                <p>Hola, <?= $usuarioNombre; ?>. Completá los datos para agendar el paseo de tu mascota.</p>
            </div>
            <a href="Dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <!-- Flash messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success shadow-sm">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'];
                                                        unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger shadow-sm">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['error'];
                                                                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-dog me-2"></i>Información del paseo</div>
            <div class="card-body p-4">
                <form method="POST" novalidate>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="mascota_id" class="form-label">Mascota <span class="text-danger">*</span></label>
                            <select class="form-select" id="mascota_id" name="mascota_id" required>
                                <option value="">Seleccionar mascota</option>
                                <?php foreach ($mascotas as $m): ?>
                                    <?php $idM = (int)($m['mascota_id'] ?? $m['id'] ?? 0); ?>
                                    <option value="<?= $idM ?>" <?= $idM === $mascotaPreseleccionada ? 'selected' : '' ?>>
                                        <?= h($m['nombre'] ?? '') ?><?= isset($m['tamano']) ? ' (' . ucfirst(h((string)$m['tamano'])) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="paseador_id" class="form-label">Paseador <span class="text-danger">*</span></label>
                            <select class="form-select" id="paseador_id" name="paseador_id" required>
                                <option value="">
                                    Seleccionar paseador<?= $ciudadSeleccionada ? ' (' . ucfirst(h($ciudadSeleccionada)) . ')' : '' ?>
                                </option>
                                <?php foreach ($paseadores as $p): ?>
                                    <?php
                                    $pid   = (int)($p['paseador_id'] ?? 0);
                                    $precio = is_numeric($p['precio_hora'] ?? null) ? (float)$p['precio_hora'] : 0.0;
                                    $sel   = $pid === $paseadorPreseleccionado ? 'selected' : '';
                                    ?>
                                    <option value="<?= $pid ?>" data-precio="<?= $precio ?>" <?= $sel ?>>
                                        <?= h($p['nombre'] ?? '') ?> - <?= ucfirst(h((string)($p['ciudad'] ?? ''))) ?>
                                        — ₲<?= number_format($precio, 0, ',', '.') ?>/hora
                                        <?php if (isset($p['calificacion'])): ?>(⭐ <?= (float)$p['calificacion'] ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="inicio" class="form-label">Fecha y hora <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="inicio" name="inicio" required>
                        </div>

                        <div class="col-md-6">
                            <label for="duracion" class="form-label">Duración <span class="text-danger">*</span></label>
                            <select class="form-select" id="duracion" name="duracion" required>
                                <option value="">Seleccionar duración</option>
                                <?php foreach ([15 => '15 min', 30 => '30 min', 45 => '45 min', 60 => '1 hora', 90 => '1.5 horas', 120 => '2 horas'] as $min => $label): ?>
                                    <option value="<?= (int)$min ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="ubicacion" class="form-label">Ubicación de recogida <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Ej.: Asunción, Calle A Nº1234" required>
                            <input type="hidden" id="ciudad_ubicacion" name="ciudad_ubicacion">
                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-success btn-sm" id="btnUbicacion">
                                    <i class="fas fa-location-crosshairs me-1"></i> Detectar mi ubicación
                                </button>
                                <span id="ciudadDetectada" class="text-muted small align-self-center"></span>
                            </div>
                            <div id="mapa" class="mt-3"></div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="Dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-gradient">
                            <i class="fas fa-paper-plane me-1"></i> Solicitar Paseo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Dueño</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        // Leaflet
        let mapa = L.map('mapa').setView([-25.3, -57.6], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapa);
        let marcador;

        mapa.on('click', async function(e) {
            const {
                lat,
                lng
            } = e.latlng;
            if (marcador) mapa.removeLayer(marcador);
            marcador = L.marker([lat, lng]).addTo(mapa).bindPopup("Ubicación seleccionada").openPopup();

            document.getElementById('ubicacion').value = `${lat},${lng}`;
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`);
                const data = await res.json();
                const ciudad = data.address.city || data.address.town || data.address.village || '';
                document.getElementById('ciudad_ubicacion').value = ciudad;
                document.getElementById('ciudadDetectada').textContent = ciudad ? `📍 Ciudad detectada: ${ciudad}` : '';
            } catch (err) {
                console.error("Error al obtener ciudad:", err);
            }
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
                document.getElementById('ubicacion').value = `${latitude},${longitude}`;
                mapa.setView([latitude, longitude], 16);
                if (marcador) mapa.removeLayer(marcador);
                marcador = L.marker([latitude, longitude]).addTo(mapa).bindPopup("Ubicación detectada").openPopup();
                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=json`);
                    const data = await res.json();
                    const ciudad = data.address.city || data.address.town || data.address.village || '';
                    document.getElementById('ciudad_ubicacion').value = ciudad;
                    document.getElementById('ciudadDetectada').textContent = ciudad ? `📍 Ciudad detectada: ${ciudad}` : '';
                } catch (err) {
                    console.error("Error al obtener ciudad:", err);
                }
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
    </script>
</body>

</html>