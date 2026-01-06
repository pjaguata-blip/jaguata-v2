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

/* 🔒 BLOQUEO POR ESTADO (MUY IMPORTANTE) */
if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

/* ID del dueño logueado */
$duenoId = (int)(Session::getUsuarioId() ?? 0);

/* Controladores */
$mascotaController      = new MascotaController();
$paseoController        = new PaseoController();
$notificacionController = new NotificacionController();

/* 🐶 Mascotas del dueño */
$mascotas = $mascotaController->indexByDuenoActual() ?: [];

/* 🚶 Paseos del dueño (SOLO de este usuario) */
$paseos = $duenoId > 0 ? ($paseoController->indexByDueno($duenoId) ?: []) : [];

/* 🔔 Notificaciones recientes del dueño */
$notificaciones = $duenoId > 0 ? ($notificacionController->getRecientes($duenoId, 5) ?: []) : [];

/* 🧮 Estadísticas */
$totalMascotas = count($mascotas);

$normEstado = static function (?string $s): string {
    return strtolower(trim((string)$s));
};

$paseosPendientesArr = array_filter($paseos, function (array $p) use ($normEstado): bool {
    $estado = $normEstado($p['estado'] ?? '');
    return in_array($estado, ['pendiente', 'confirmado', 'en_curso'], true);
});

$paseosCompletadosArr = array_filter($paseos, function (array $p) use ($normEstado): bool {
    $estado = $normEstado($p['estado'] ?? '');
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

/* 🐾 Mascotas recientes */
$mascotasRecientes = array_slice($mascotas, 0, 3);

/* Rutas base y nombre usuario para SidebarDueno */
$baseFeatures  = BASE_URL . "/features/dueno";
$usuarioNombre = Session::getUsuarioNombre() ?? 'Dueño/a';

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

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height: 100%; }
        body { background: var(--gris-fondo, #f4f6f9); }

       /* =========================
   Layout principal
   (IGUAL al Dashboard)
   ========================= */

/* Desktop */
main.main-content {
    margin-left: 260px;
    min-height: 100vh;
    padding: 24px;
}

/* Mobile */
@media (max-width: 768px) {
    main.main-content {
        margin-left: 0;
        margin-top: 0 !important; /* 🔥 clave */
        width: 100% !important;
        padding: calc(16px + var(--topbar-h)) 16px 16px !important;
    }
}

        .dash-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
            height: 100%;
        }

        .dash-card-icon { font-size: 2rem; margin-bottom: 6px; }

        .dash-card-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #222;
        }

        .dash-card-label { font-size: 0.9rem; color: #555; }

        .icon-blue { color: #0d6efd; }
        .icon-green { color: var(--verde-jaguata, #3c6255); }
        .icon-yellow { color: #ffc107; }
        .icon-red { color: #dc3545; }

        .badge-2masc { background: var(--verde-jaguata, #3c6255); }
    </style>
</head>

<body class="page-dashboard-dueno">

    <!-- Sidebar Dueño unificado -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- ✅ IMPORTANTE:
         - Eliminamos el botón hamburguesa EXTRA (data-toggle="sidebar")
         - Eliminamos el JS extra del toggleSidebar (SidebarDueno ya lo trae)
    -->

    <main class="main-content">
        <div class="py-0">

            <!-- Header -->
            <div class="header-box header-dashboard mb-2">
                <div>
                    <h1>¡Hola, <?= h($usuarioNombre); ?>! 🐾</h1>
                    <p>Gestioná tus paseos, disponibilidad, ganancias y estadísticas desde un solo lugar.</p>
                </div>
                <i class="fas fa-dog fa-3x opacity-75"></i>
            </div>

            <!-- Métricas -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-dog dash-card-icon icon-green"></i>
                        <div class="dash-card-value"><?= $totalMascotas ?></div>
                        <div class="dash-card-label">Mascotas registradas</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-check-circle dash-card-icon icon-blue"></i>
                        <div class="dash-card-value"><?= count($paseosCompletadosArr); ?></div>
                        <div class="dash-card-label">Paseos completados</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-hourglass-half dash-card-icon icon-yellow"></i>
                        <div class="dash-card-value"><?= count($paseosPendientesArr); ?></div>
                        <div class="dash-card-label">Paseos pendientes / en curso</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-wallet dash-card-icon icon-red"></i>
                        <div class="dash-card-value">₲<?= number_format($gastosTotales, 0, ',', '.'); ?></div>
                        <div class="dash-card-label">Gasto total en paseos</div>
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
                                                <th>Mascota(s)</th>
                                                <th>Paseador</th>
                                                <th>Inicio</th>
                                                <th>Duración</th>
                                                <th>Precio</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paseosRecientes as $p): ?>
                                                <?php
                                                $mascota1 = $p['mascota_nombre'] ?? $p['nombre_mascota'] ?? '-';
                                                $mascota2 = $p['mascota_nombre_2'] ?? $p['nombre_mascota_2'] ?? null;

                                                $cantMasc = (int)($p['cantidad_mascotas'] ?? 1);
                                                $hay2     = $cantMasc === 2 || !empty($p['mascota_id_2']) || !empty($mascota2);

                                                $textoMascotas = ($hay2 && $mascota2)
                                                    ? ($mascota1 . ' + ' . $mascota2)
                                                    : $mascota1;

                                                $paseadorNombre  = $p['paseador_nombre']  ?? $p['nombre_paseador']  ?? '-';

                                                $estado = $normEstado($p['estado'] ?? '-');
                                                $badgeClass = match ($estado) {
                                                    'pendiente'   => 'bg-warning text-dark',
                                                    'confirmado',
                                                    'en_curso'    => 'bg-info text-dark',
                                                    'completo'    => 'bg-success',
                                                    'cancelado'   => 'bg-danger',
                                                    default       => 'bg-secondary',
                                                };

                                                $dur = isset($p['duracion'])
                                                    ? (int)$p['duracion']
                                                    : (isset($p['duracion_min']) ? (int)$p['duracion_min'] : 0);
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?= h($textoMascotas); ?>
                                                        <?php if ($hay2): ?>
                                                            <span class="badge badge-2masc ms-2">2 🐾</span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <td><?= h($paseadorNombre); ?></td>

                                                    <td>
                                                        <?php if (!empty($p['inicio'])): ?>
                                                            <?= date('d/m/Y H:i', strtotime($p['inicio'])); ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>

                                                    <td><?= $dur > 0 ? h((string)$dur) . ' min' : '-'; ?></td>

                                                    <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.'); ?></td>

                                                    <td>
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
                    <div class="section-card mb-3">
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
</body>

</html>
