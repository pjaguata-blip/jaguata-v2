<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/MascotaController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PaseoController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/NotificacionController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\NotificacionController;

AppConfig::init();

/* 🔒 Autenticación y rol */
$auth = new AuthController();
$auth->checkRole('dueno');

/* ID del dueño logueado */
$duenoId = (int)(Session::getUsuarioId() ?? 0);

/* Controladores */
$mascotaController      = new MascotaController();
$paseoController        = new PaseoController();
$notificacionController = new NotificacionController();

/* 🐶 Mascotas del dueño (método específico) */
$mascotas = $mascotaController->indexByDuenoActual() ?: [];

/* 🚶 Paseos del dueño (consulta ya filtrada en el controlador) */
$paseos = $duenoId > 0 ? ($paseoController->indexByDueno($duenoId) ?: []) : [];

/* 🔔 Notificaciones recientes del dueño */
$notificaciones = $duenoId > 0 ? ($notificacionController->getRecientes($duenoId, 5) ?: []) : [];

/* 🧮 Estadísticas */
$totalMascotas = count($mascotas);

$paseosPendientesArr = array_filter($paseos, function (array $p): bool {
    $estado = strtolower(trim($p['estado'] ?? ''));
    return in_array($estado, ['pendiente', 'confirmado', 'en curso'], true);
});

$paseosCompletadosArr = array_filter($paseos, function (array $p): bool {
    $estado = strtolower(trim($p['estado'] ?? ''));
    return $estado === 'completo';
});

$gastosTotales = array_sum(array_map(
    fn(array $p) => (float)($p['precio_total'] ?? 0),
    $paseosCompletadosArr
));

/* 📅 Ordenar paseos por fecha de inicio (más recientes primero) */
usort($paseos, function (array $a, array $b): int {
    return strtotime($b['inicio'] ?? '1970-01-01') <=> strtotime($a['inicio'] ?? '1970-01-01');
});
$paseosRecientes = array_slice($paseos, 0, 5);

/* 🐾 Mascotas recientes (para panel lateral) */
$mascotasRecientes = array_slice($mascotas, 0, 3);

/* Rutas base y nombre usuario para SidebarDueno */
$baseFeatures   = BASE_URL . "/features/dueno";
$usuarioNombre  = htmlspecialchars(Session::getUsuarioNombre() ?? 'Dueño/a', ENT_QUOTES, 'UTF-8');

/* Helper de escape rápido */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Dueño - Jaguata</title>

    <!-- CSS global (igual que Admin) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar Dueño unificado -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Botón hamburguesa para mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Contenido principal -->
    <main>
        <div class="py-4">

            <!-- Header -->
            <div class="header-box header-dashboard mb-4">
                <div>
                    <h1 class="mb-1 fw-bold">Panel del Dueño</h1>
                    <p class="mb-0">Bienvenido, <?= h($usuarioNombre); ?> 🐾</p>
                </div>
                <div class="d-none d-md-block">
                    <i class="fas fa-dog fa-3x opacity-75"></i>
                </div>
            </div>

            <!-- Métricas -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-paw"></i>
                        <h4><?= $totalMascotas; ?></h4>
                        <p>Mascotas registradas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-check-circle"></i>
                        <h4><?= count($paseosCompletadosArr); ?></h4>
                        <p>Paseos completados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-hourglass-half"></i>
                        <h4><?= count($paseosPendientesArr); ?></h4>
                        <p>Paseos pendientes / en curso</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-wallet"></i>
                        <h4>₲<?= number_format($gastosTotales, 0, ',', '.'); ?></h4>
                        <p>Gasto total en paseos</p>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Paseos recientes -->
                <div class="col-lg-8">
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-walking me-2"></i>Paseos recientes
                        </div>
                        <div class="section-body">
                            <?php if (empty($paseosRecientes)): ?>
                                <p class="text-center text-muted mb-0">No hay paseos recientes.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle text-center mb-0">
                                        <thead>
                                            <tr>
                                                <th>Mascota</th>
                                                <th>Paseador</th>
                                                <th>Inicio</th>
                                                <th>Duración</th>
                                                <th>Precio</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paseosRecientes as $p): ?>
                                                <tr>
                                                    <td><?= h($p['mascota_nombre'] ?? '-'); ?></td>
                                                    <td><?= h($p['paseador_nombre'] ?? '-'); ?></td>
                                                    <td>
                                                        <?php if (!empty($p['inicio'])): ?>
                                                            <?= date('d/m/Y H:i', strtotime($p['inicio'])); ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= isset($p['duracion']) ? (int)$p['duracion'] . ' min' : '-'; ?></td>
                                                    <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.'); ?></td>
                                                    <td>
                                                        <?php
                                                        $estado = strtolower(trim($p['estado'] ?? '-'));
                                                        $badgeClass = match ($estado) {
                                                            'pendiente'   => 'bg-warning text-dark',
                                                            'confirmado',
                                                            'en curso'    => 'bg-info text-dark',
                                                            'completo'    => 'bg-success',
                                                            'cancelado'   => 'bg-danger',
                                                            default       => 'bg-secondary',
                                                        };
                                                        ?>
                                                        <span class="badge <?= $badgeClass; ?>">
                                                            <?= ucfirst($estado ?: '-'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Panel lateral: Mascotas + Notificaciones -->
                <div class="col-lg-4">
                    <!-- Mis Mascotas -->
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-paw me-2"></i>Mis Mascotas
                        </div>
                        <div class="section-body">
                            <?php if (empty($mascotasRecientes)): ?>
                                <p class="text-center text-muted mb-0">No tienes mascotas registradas.</p>
                            <?php else: ?>
                                <?php foreach ($mascotasRecientes as $m): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-bone me-2 text-primary"></i>
                                        <span><?= h($m['nombre'] ?? '-'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-3">
                                    <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-primary btn-sm">
                                        Ver todas
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notificaciones -->
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-bell me-2"></i>Notificaciones
                        </div>
                        <div class="section-body">
                            <?php if (empty($notificaciones)): ?>
                                <p class="text-center text-muted mb-0">No tienes notificaciones.</p>
                            <?php else: ?>
                                <?php foreach ($notificaciones as $n): ?>
                                    <div class="mb-3">
                                        <h6 class="fw-bold mb-1"><?= h($n['titulo'] ?? ''); ?></h6>
                                        <p class="mb-1 small"><?= h($n['mensaje'] ?? ''); ?></p>
                                        <small class="text-muted">
                                            <?php if (!empty($n['created_at'])): ?>
                                                <?= date('d/m/Y H:i', strtotime($n['created_at'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel del Dueño
            </footer>

        </div>
    </main>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile (usa la clase .sidebar-open del CSS)
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>