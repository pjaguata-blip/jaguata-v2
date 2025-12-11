<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

// 🔹 Solo paseador
$auth = new AuthController();
$auth->checkRole('paseador');

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// 🔹 ID de paseo
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('<h3 style="color:red;text-align:center;">ID de paseo no válido.</h3>');
}

// 🔹 Cargar datos reales del paseo (vista paseador)
$paseoController = new PaseoController();
$paseo = $paseoController->show($id);   // 👈 mismo método que ya usabas

if (!$paseo) {
    die('<h3 style="color:red;text-align:center;">No se encontró el paseo solicitado.</h3>');
}

// 🔹 Validar que el paseo pertenezca al paseador logueado
$paseadorIdSesion = (int)(Session::getUsuarioId() ?? 0);
if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorIdSesion) {
    die('<h3 style="color:red;text-align:center;">No tenés permiso para ver este paseo.</h3>');
}

// =========================
// Formato de campos
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

$inicio = $paseo['inicio'] ?? '';
$updatedAt = $paseo['updated_at'] ?? '';

$direccion = $paseo['direccion'] ?? $paseo['ubicacion'] ?? '-';

// Coordenadas (si vienen del back; si no, usa centro por defecto)
$lat = $paseo['paseador_latitud']  ?? $paseo['latitud']  ?? 0;
$lon = $paseo['paseador_longitud'] ?? $paseo['longitud'] ?? 0;

// Para acciones
$paseoId = (int)($paseo['paseo_id'] ?? $id);

// =========================
// Rutas
// =========================
$baseUrl   = AppConfig::getBaseUrl();
$backUrl   = $baseUrl . "/features/paseador/MisPaseos.php";
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

    <!-- Leaflet para mapa -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        /* Aseguramos altura del mapa como en admin */
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
            /* coincide con ancho sidebar */
            padding: 1.5rem 1.5rem 2rem;
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar del paseador -->
    <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-4">

            <!-- HEADER UNIFICADO TIPO ADMIN -->
            <div class="header-box header-paseos mb-3">
                <div>
                    <h1 class="fw-bold mb-1">
                        Detalle del Paseo #<?= h((string)$id) ?>
                    </h1>
                    <p class="mb-0">Información completa del recorrido, estado y ubicación 🐾</p>
                </div>
                <i class="fas fa-map-location-dot fa-3x opacity-75"></i>
            </div>

            <!-- Botón volver -->
            <div class="mb-3">
                <a href="<?= h($backUrl) ?>" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver a mis paseos
                </a>
            </div>

            <div class="row g-3">
                <!-- Información del Paseo -->
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
                                    <p><?= $duracionMin ?> minutos</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Monto:</p>
                                    <p>₲<?= number_format($monto, 0, ',', '.') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Estado:</p>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $estado)) ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Fecha de inicio:</p>
                                    <p>
                                        <?php
                                        echo $inicio
                                            ? h(date('d/m/Y H:i', strtotime($inicio)))
                                            : '-';
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Última actualización:</p>
                                    <p>
                                        <?php
                                        echo $updatedAt
                                            ? h(date('d/m/Y H:i', strtotime($updatedAt)))
                                            : 'Sin cambios';
                                        ?>
                                    </p>
                                </div>
                                <div class="col-12">
                                    <p class="info-label">Dirección:</p>
                                    <p><?= h($direccion) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones del Paseador -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-tools me-2"></i> Acciones del paseador
                        </div>
                        <div class="card-body text-center action-buttons d-flex flex-wrap justify-content-center gap-2">
                            <?php if (in_array($estado, ['pendiente', 'solicitado'], true)): ?>
                                <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=confirmar"
                                    class="btn btn-success"
                                    onclick="return confirm('¿Confirmar este paseo?');">
                                    <i class="fas fa-check-circle me-1"></i> Confirmar paseo
                                </a>
                                <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=cancelar"
                                    class="btn btn-danger"
                                    onclick="return confirm('¿Cancelar este paseo?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar paseo
                                </a>

                            <?php elseif ($estado === 'confirmado'): ?>
                                <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=iniciar"
                                    class="btn btn-success"
                                    onclick="return confirm('¿Iniciar este paseo?');">
                                    <i class="fas fa-play me-1"></i> Iniciar paseo
                                </a>
                                <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=cancelar"
                                    class="btn btn-danger"
                                    onclick="return confirm('¿Cancelar este paseo?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar paseo
                                </a>

                            <?php elseif ($estado === 'en_curso'): ?>
                                <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=completar"
                                    class="btn btn-success"
                                    onclick="return confirm('¿Marcar este paseo como completado?');">
                                    <i class="fas fa-check-circle me-1"></i> Completar paseo
                                </a>
                                <a href="AccionPaseo.php?id=<?= $paseoId ?>&accion=cancelar"
                                    class="btn btn-danger"
                                    onclick="return confirm('¿Cancelar paseo en curso?');">
                                    <i class="fas fa-times-circle me-1"></i> Cancelar paseo
                                </a>

                            <?php else: ?>
                                <span class="text-muted">
                                    No hay acciones disponibles para este estado.
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Mapa (igual estilo admin) -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-map-marker-alt me-2"></i> Ubicación
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

    <!-- Script toggle sidebar mobile (si tu SidebarPaseador usa backdrop/btn) -->
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

    <!-- Mapa Leaflet -->
    <script>
        const lat = <?= $lat ?: 0 ?>;
        const lon = <?= $lon ?: 0 ?>;
        const map = L.map('map').setView(
            [lat || -25.3, lon || -57.6],
            13
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        if (lat && lon) {
            L.marker([lat, lon]).addTo(map)
                .bindPopup('Ubicación del paseo')
                .openPopup();
        } else {
            L.marker([-25.3, -57.6]).addTo(map)
                .bindPopup('Ubicación no disponible')
                .openPopup();
        }
    </script>
</body>

</html>