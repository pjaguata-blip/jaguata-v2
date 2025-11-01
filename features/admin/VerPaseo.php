<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\PaseoController;

AppConfig::init();

// 🔹 Seguridad
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

// 🔹 Obtener ID del paseo
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('<h3 style="color:red;text-align:center;">ID de paseo no válido.</h3>');
}

// 🔹 Cargar datos reales
$paseoController = new PaseoController();
$paseo = $paseoController->getById($id);

if (!$paseo) {
    die('<h3 style="color:red;text-align:center;">No se encontró el paseo solicitado.</h3>');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paseo #<?= htmlspecialchars($id) ?> - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f5f7fa;
            --blanco: #fff;
        }

        body {
            font-family: "Poppins", sans-serif;
            background: var(--gris-fondo);
            color: #333;
        }

        main {
            margin-left: 250px;
            padding: 2rem;
        }

        /* === Sidebar === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            color: #fff;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.25);
        }

        .sidebar .nav-link {
            color: #ddd;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: #fff;
            transform: translateX(4px);
        }

        /* === Contenido === */
        .card {
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: var(--verde-jaguata);
            color: white;
            font-weight: 600;
        }

        .info-label {
            font-weight: 600;
            color: var(--verde-jaguata);
        }

        .btn-volver {
            background: var(--verde-claro);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-volver:hover {
            background: var(--verde-jaguata);
            color: #fff;
        }

        #map {
            height: 350px;
            border-radius: 12px;
        }

        .action-buttons .btn {
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 500;
        }

        .badge {
            font-size: 0.9rem;
            padding: 0.4em 0.7em;
            border-radius: 8px;
        }

        footer {
            text-align: center;
            color: #777;
            margin-top: 2rem;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0;
                padding: 1rem;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="text-center mb-4">
            <img src="<?= ASSETS_URL ?>/uploads/perfiles/logojag.png" alt="Logo" width="60" class="bg-light p-2 rounded-circle">
            <h6 class="mt-2 fw-bold text-success">Jaguata Admin</h6>
            <hr class="text-light">
        </div>
        <ul class="nav flex-column gap-1 px-2">
            <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a class="nav-link" href="Usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a class="nav-link active" href="Paseos.php"><i class="fas fa-dog"></i> Paseos</a></li>
            <li><a class="nav-link" href="Pagos.php"><i class="fas fa-wallet"></i> Pagos</a></li>
            <li><a class="nav-link" href="Reportes.php"><i class="fas fa-file-alt"></i> Reportes</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
        </ul>
    </aside>

    <main>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-success mb-0">Detalle del Paseo #<?= htmlspecialchars($id) ?></h2>
                <a href="Paseos.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>

            <!-- Información del Paseo -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-dog me-2"></i> Información del Paseo</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="info-label">Mascota:</p>
                            <p><?= htmlspecialchars($paseo['nombre_mascota'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="info-label">Paseador:</p>
                            <p><?= htmlspecialchars($paseo['nombre_paseador'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="info-label">Dueño:</p>
                            <p><?= htmlspecialchars($paseo['nombre_dueno'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="info-label">Duración:</p>
                            <p><?= (int)($paseo['duracion'] ?? 0) ?> minutos</p>
                        </div>
                        <div class="col-md-6">
                            <p class="info-label">Monto:</p>
                            <p>₲<?= number_format((float)($paseo['precio_total'] ?? 0), 0, ',', '.') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="info-label">Estado:</p>
                            <span class="badge bg-<?= match (strtolower($paseo['estado'] ?? 'pendiente')) {
                                                        'pendiente' => 'warning text-dark',
                                                        'confirmado' => 'primary',
                                                        'en_curso' => 'info text-dark',
                                                        'completo', 'finalizado' => 'success',
                                                        'cancelado' => 'danger',
                                                        default => 'secondary'
                                                    } ?>"><?= ucfirst($paseo['estado'] ?? 'Pendiente') ?></span>
                        </div>
                        <div class="col-md-6">
                            <p class="info-label">Fecha de inicio:</p>
                            <p><?= htmlspecialchars(date('d/m/Y H:i', strtotime($paseo['inicio'] ?? ''))) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="info-label">Última actualización:</p>
                            <p><?= !empty($paseo['updated_at']) ? date('d/m/Y H:i', strtotime($paseo['updated_at'])) : 'Sin cambios' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones del Admin -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-tools me-2"></i> Acciones del administrador</div>
                <div class="card-body text-center action-buttons">
                    <button class="btn btn-success me-2" onclick="actualizarEstado('finalizar')">
                        <i class="fas fa-check-circle"></i> Finalizar paseo
                    </button>
                    <button class="btn btn-danger" onclick="actualizarEstado('cancelar')">
                        <i class="fas fa-times-circle"></i> Cancelar paseo
                    </button>
                </div>
            </div>

            <!-- Mapa -->
            <div class="card">
                <div class="card-header"><i class="fas fa-map-marker-alt me-2"></i> Ubicación</div>
                <div class="card-body">
                    <div id="map"></div>
                </div>
            </div>

            <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
        </div>
    </main>

    <script>
        // === Mapa con Leaflet ===
        const lat = <?= $paseo['paseador_latitud'] ?? 0 ?>;
        const lon = <?= $paseo['paseador_longitud'] ?? 0 ?>;
        const map = L.map('map').setView([lat || -25.3, lon || -57.6], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        if (lat && lon) {
            L.marker([lat, lon]).addTo(map)
                .bindPopup('Ubicación del paseador')
                .openPopup();
        } else {
            L.marker([-25.3, -57.6]).addTo(map)
                .bindPopup('Ubicación no disponible')
                .openPopup();
        }

        // === Acciones del Admin ===
        async function actualizarEstado(accion) {
            if (!confirm(`¿Seguro que deseas ${accion} este paseo?`)) return;
            try {
                const res = await fetch(`/jaguata/public/api/paseos/accionPaseo.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=<?= $id ?>&accion=${accion}`
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