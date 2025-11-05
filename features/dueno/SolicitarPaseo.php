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
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

AppConfig::init();

$authController = new AuthController();
$authController->checkRole('dueno');

$paseoController   = new PaseoController();
$mascotaController = new MascotaController();
$mascotas = $mascotaController->index();

if (empty($mascotas)) {
    $_SESSION['error'] = 'Debes tener al menos una mascota registrada para solicitar paseos';
    header('Location: AgregarMascota.php');
    exit;
}

$mascotaPreseleccionada  = (int)($_GET['mascota_id'] ?? 0);
$paseadorPreseleccionado = (int)($_GET['paseador_id'] ?? 0);
$fechaFiltro             = trim($_GET['fecha'] ?? '');
$ciudadSeleccionada      = strtolower(trim($_POST['ciudad_ubicacion'] ?? $_GET['ciudad'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paseoController->store();
}

$paseadorModel = new \Jaguata\Models\Paseador();
$allPaseadores = $fechaFiltro
    ? $paseadorModel->getDisponibles($fechaFiltro)
    : $paseadorModel->getDisponibles();

$paseadores = array_filter($allPaseadores, function ($p) use ($ciudadSeleccionada) {
    if ($ciudadSeleccionada === '') return true;
    $ciudadP = strtolower(trim($p['ciudad'] ?? ''));
    $zonas   = strtolower(trim($p['zona'] ?? ''));
    return $ciudadP === $ciudadSeleccionada || str_contains($zonas, $ciudadSeleccionada);
});

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Dueño');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Paseo - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        body {
            background-color: #f6f8fb;
            font-family: "Poppins", sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #2e2f45 100%);
            color: #f8f9fa;
            min-height: 100vh;
            padding-top: 1rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar .nav-link {
            color: #ddd;
            border-radius: 10px;
            padding: 10px 16px;
            margin: 4px 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: all 0.25s ease-in-out;
        }

        .sidebar .nav-link:hover {
            background-color: #40415a;
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background-color: #3c6255;
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15);
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.4rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 600;
            font-size: 1.7rem;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.07);
        }

        .card-header {
            background-color: #3c6255;
            color: #fff;
            font-weight: 600;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        label.form-label {
            font-weight: 500;
            color: #333;
        }

        .form-select,
        .form-control {
            border-radius: 10px;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
            border-radius: 8px;
            padding: 10px 18px;
        }

        .btn-gradient:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        #mapa {
            height: 320px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #dfe3e8;
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.9rem;
            padding: 1.5rem 0;
        }

        .alert {
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="text-center mb-4">
                    <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="60" class="mb-2">
                    <hr class="text-light">
                </div>
                <ul class="nav flex-column gap-1 px-2">
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Mi perfil</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis mascotas</a></li>
                    <li><a class="nav-link active" href="<?= $baseFeatures; ?>/SolicitarPaseo.php"><i class="fas fa-walking"></i> Reservar paseo</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-hourglass-half"></i> Pendientes</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosCompletados.php"><i class="fas fa-check-circle"></i> Completados</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php"><i class="fas fa-wallet"></i> Mis gastos</a></li>
                    <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                </ul>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="page-header">
                    <h2><i class="fas fa-calendar-check me-2"></i> Solicitar Paseo</h2>
                    <a href="Dashboard.php" class="btn btn-outline-light"><i class="fas fa-arrow-left me-1"></i> Volver</a>
                </div>

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

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-dog me-2"></i> Información del Paseo
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="mascota_id" class="form-label">
                                        Mascota <span class="text-danger" aria-hidden="true">*</span>
                                    </label>

                                    <select class="form-select" name="mascota_id" required>
                                        <option value="">Seleccionar mascota</option>
                                        <?php foreach ($mascotas as $m): ?>
                                            <option value="<?= (int)$m['mascota_id'] ?>" <?= ((int)$m['mascota_id'] === $mascotaPreseleccionada) ? 'selected' : '' ?>>
                                                <?= h($m['nombre']) ?> (<?= ucfirst(h($m['tamano'])) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="paseador_id" class="form-label">Paseador *</label>
                                    <select class="form-select" id="paseador_id" name="paseador_id" required>
                                        <option value="">Seleccionar paseador <?= $ciudadSeleccionada ? '(' . ucfirst($ciudadSeleccionada) . ')' : '' ?></option>
                                        <?php foreach ($paseadores as $p):
                                            $precio = is_numeric($p['precio_hora']) ? (float)$p['precio_hora'] : 0; ?>
                                            <option value="<?= (int)$p['paseador_id'] ?>" data-precio="<?= $precio ?>">
                                                <?= h($p['nombre']) ?> - <?= ucfirst(h($p['ciudad'])) ?> — ₲<?= number_format($precio, 0, ',', '.') ?>/hora (⭐ <?= (float)$p['calificacion'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Fecha y hora *</label>
                                    <input type="datetime-local" class="form-control" name="inicio" id="inicio" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Duración *</label>
                                    <select class="form-select" id="duracion" name="duracion" required>
                                        <option value="">Seleccionar duración</option>
                                        <?php foreach ([15 => '15 min', 30 => '30 min', 45 => '45 min', 60 => '1 hora', 90 => '1.5 horas', 120 => '2 horas'] as $min => $label): ?>
                                            <option value="<?= $min ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Ubicación de recogida *</label>
                                    <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Ejemplo: Asunción, Calle A Nº1234" required>
                                    <input type="hidden" name="ciudad_ubicacion" id="ciudad_ubicacion">

                                    <div class="mt-3 d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-success btn-sm" id="btnUbicacion">
                                            <i class="fas fa-location-crosshairs me-1"></i> Detectar mi ubicación actual
                                        </button>
                                        <span id="ciudadDetectada" class="text-muted small align-self-center"></span>
                                    </div>

                                    <div id="mapa" class="mt-3"></div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <div class="d-flex justify-content-between">
                                <a href="Dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Cancelar</a>
                                <button type="submit" class="btn btn-gradient"><i class="fas fa-paper-plane me-1"></i> Solicitar Paseo</button>
                            </div>
                        </form>
                    </div>
                </div>

                <footer>© <?= date('Y') ?> Jaguata — Todos los derechos reservados.</footer>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
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
            marcador = L.marker([lat, lng]).addTo(mapa)
                .bindPopup("Ubicación seleccionada").openPopup();

            // Guardar coordenadas en tu input oculto
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

        document.getElementById('btnUbicacion').addEventListener('click', () => {
            if (!navigator.geolocation) return alert("Tu navegador no soporta geolocalización.");
            navigator.geolocation.getCurrentPosition(async pos => {
                const {
                    latitude,
                    longitude
                } = pos.coords;
                document.getElementById('ubicacion').value = `${latitude},${longitude}`;
                mapa.setView([latitude, longitude], 16);
                if (marcador) mapa.removeLayer(marcador);
                marcador = L.marker([latitude, longitude]).addTo(mapa)
                    .bindPopup("Ubicación detectada").openPopup();

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