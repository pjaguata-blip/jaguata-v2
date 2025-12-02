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
$paseo = $paseoController->getDetalleAdmin($id);

if (!$paseo) {
    die('<h3 style="color:red;text-align:center;">No se encontró el paseo solicitado.</h3>');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paseo #<?= htmlspecialchars((string)$id) ?> - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Theme global -->
    <link href="<?= BASE_URL ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-4">

            <!-- HEADER UNIFICADO -->
            <div class="header-box header-paseos">
                <div>
                    <h1 class="fw-bold mb-1">Detalle del Paseo #<?= htmlspecialchars((string)$id) ?></h1>
                    <p class="mb-0">Información completa del recorrido, estado y ubicación 🐾</p>
                </div>
                <i class="fas fa-map-location-dot fa-3x opacity-75"></i>
            </div>

            <!-- Botón volver -->
            <div class="mb-3">
                <a href="Paseos.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver a la lista
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
                                    <?php
                                    $estado = strtolower($paseo['estado'] ?? 'pendiente');
                                    $badgeClass = match ($estado) {
                                        'pendiente'   => 'bg-warning text-dark',
                                        'confirmado'  => 'bg-primary',
                                        'en_curso'    => 'bg-info text-dark',
                                        'completo',
                                        'finalizado'  => 'bg-success',
                                        'cancelado'   => 'bg-danger',
                                        default       => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst($estado ?: 'Pendiente') ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Fecha de inicio:</p>
                                    <p>
                                        <?php
                                        $inicio = $paseo['inicio'] ?? '';
                                        echo $inicio ? htmlspecialchars(date('d/m/Y H:i', strtotime($inicio))) : '-';
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="info-label">Última actualización:</p>
                                    <p>
                                        <?php
                                        $upd = $paseo['updated_at'] ?? '';
                                        echo $upd ? htmlspecialchars(date('d/m/Y H:i', strtotime($upd))) : 'Sin cambios';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones del Admin -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-tools me-2"></i> Acciones del administrador
                        </div>
                        <div class="card-body text-center action-buttons">
                            <button class="btn btn-success me-2 mb-2 mb-md-0"
                                onclick="actualizarEstado('finalizar')">
                                <i class="fas fa-check-circle"></i> Finalizar paseo
                            </button>
                            <button class="btn btn-danger"
                                onclick="actualizarEstado('cancelar')">
                                <i class="fas fa-times-circle"></i> Cancelar paseo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mapa -->
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

            <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
        </div>
    </main>

    <!-- Script toggle sidebar mobile (si aún no lo tenés global) -->
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

    <!-- Mapa + acciones admin -->
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