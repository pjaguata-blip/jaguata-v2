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

// === Inicialización y seguridad ===
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

// === Variables base ===
$rolMenu      = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$paseadorId   = (int)(Session::getUsuarioId() ?? 0);

// === Controlador ===
$paseoController = new PaseoController();

// === Obtener paseos reales del paseador logueado ===
$paseos = $paseadorId > 0
    ? $paseoController->indexForPaseador($paseadorId)
    : [];

// Helper
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// === Construir array de pagos a partir de paseos ===
$pagos = [];
foreach ($paseos as $p) {
    $estadoPagoRaw = strtolower($p['estado_pago'] ?? '');
    // En tu sistema estás usando "procesado" como pagado
    $estadoPago = in_array($estadoPagoRaw, ['procesado', 'pagado'], true)
        ? 'pagado'
        : 'pendiente';

    $pagos[] = [
        'id'       => (int)($p['paseo_id'] ?? 0),
        // pago_id debe venir del SELECT del PaseoController::indexForPaseador
        'pago_id'  => isset($p['pago_id']) ? (int)$p['pago_id'] : null,
        'paseo'    => 'Paseo de ' . ($p['mascota_nombre'] ?? $p['nombre_mascota'] ?? 'Mascota'),
        'monto'    => (float)($p['precio_total'] ?? 0),
        'fecha'    => $p['inicio'] ?? null,
        'estado'   => $estadoPago,
    ];
}

// === Totales ===
$totalPagado    = array_sum(array_map(fn($p) => $p['estado'] === 'pagado' ? (float)$p['monto'] : 0, $pagos));
$totalPendiente = array_sum(array_map(fn($p) => $p['estado'] === 'pendiente' ? (float)$p['monto'] : 0, $pagos));
$totalPaseos    = count($pagos);

// === Filtro por fecha (para ganancias) ===
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin    = $_GET['fecha_fin'] ?? '';

$paseosCompletados = array_filter($paseos, function ($p) use ($fechaInicio, $fechaFin) {
    $estado = strtolower($p['estado'] ?? '');
    $fecha  = isset($p['inicio']) ? date('Y-m-d', strtotime($p['inicio'])) : null;

    if (!$fecha || !in_array($estado, ['completo', 'finalizado'], true)) {
        return false;
    }
    if ($fechaInicio && $fecha < $fechaInicio) {
        return false;
    }
    if ($fechaFin && $fecha > $fechaFin) {
        return false;
    }
    return true;
});

// === Calcular ganancias totales ===
$gananciasTotales = array_sum(
    array_map(fn($p) => (float)($p['precio_total'] ?? 0), $paseosCompletados)
);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos y Ganancias - Paseador | Jaguata</title>

    <!-- 🌿 Tema general Jaguata -->
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-2" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <!-- Sidebar paseador unificado -->
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- Contenido principal -->
        <main class="content bg-light">
            <div class="container-fluid py-4">

                <!-- Header con estilo global -->
                <div class="header-box header-dashboard mb-4">
                    <div>
                        <h1 class="h4 mb-1">
                            <i class="fas fa-wallet me-2"></i> Pagos y ganancias
                        </h1>
                        <p class="mb-0 text-white-50">
                            Visualizá tus ingresos, pagos pendientes y exportá tus ganancias.
                        </p>
                    </div>
                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <!-- Totales (usa estilo tipo stat-card) -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <i class="fas fa-check-circle text-success mb-2"></i>
                            <h4>₲<?= number_format($totalPagado, 0, ',', '.') ?></h4>
                            <p class="mb-0">Total recibido</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <i class="fas fa-hourglass-half text-warning mb-2"></i>
                            <h4>₲<?= number_format($totalPendiente, 0, ',', '.') ?></h4>
                            <p class="mb-0">Pendiente de cobro</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <i class="fas fa-list text-info mb-2"></i>
                            <h4><?= $totalPaseos ?></h4>
                            <p class="mb-0">Total de paseos</p>
                        </div>
                    </div>
                </div>

                <!-- Bloque: Ganancias -->
                <div class="card jag-card shadow-sm mb-4">
                    <div class="card-header bg-success text-white fw-semibold">
                        <i class="fas fa-coins me-2"></i> Mis ganancias
                    </div>
                    <div class="card-body">

                        <!-- Filtros de fecha -->
                        <form method="get" class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Desde</label>
                                <input type="date" name="fecha_inicio" class="form-control"
                                    value="<?= h($fechaInicio) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="fecha_fin" class="form-control"
                                    value="<?= h($fechaFin) ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                                <a href="Pagos.php" class="btn btn-outline-secondary">
                                    Quitar filtro
                                </a>
                            </div>
                        </form>

                        <!-- Botón Exportar Excel (API externa) -->
                        <?php if (!empty($paseosCompletados)): ?>
                            <div class="d-flex justify-content-end mb-3">
                                <a href="<?= BASE_URL ?>/public/api/pagos/exportarGananciasPaseador.php?fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                                    class="btn btn-success d-flex align-items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Exportar Excel
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Total Ganancias -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-semibold">Ganancias en el período seleccionado:</h5>
                            <h4 class="mb-0 text-success">
                                ₲<?= number_format($gananciasTotales, 0, ',', '.') ?>
                            </h4>
                        </div>

                        <?php if (empty($paseosCompletados)): ?>
                            <div class="alert alert-info text-center mb-0">
                                No hay paseos completados en el período seleccionado.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mascota</th>
                                            <th>Fecha</th>
                                            <th>Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paseosCompletados as $p): ?>
                                            <tr>
                                                <td><?= h($p['mascota_nombre'] ?? $p['nombre_mascota'] ?? '-') ?></td>
                                                <td><?= $p['inicio'] ? date('d/m/Y H:i', strtotime($p['inicio'])) : '—' ?></td>
                                                <td class="fw-semibold text-success">
                                                    ₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Historial de pagos -->
                <div class="card jag-card shadow-sm mb-4">
                    <div class="card-header bg-success text-white fw-semibold">
                        <i class="fas fa-list me-2"></i> Historial de pagos
                    </div>
                    <div class="card-body">
                        <?php if (empty($pagos)): ?>
                            <p class="text-center text-muted mb-0">
                                No hay registros de pagos aún.
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Paseo</th>
                                            <th>Monto</th>
                                            <th>Fecha</th>
                                            <th>Estado del pago</th>
                                            <th>Comprobante</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pagos as $p): ?>
                                            <?php
                                            $badgeColor       = $p['estado'] === 'pagado'
                                                ? 'bg-success'
                                                : 'bg-warning text-dark';
                                            $tieneComprobante = $p['estado'] === 'pagado' && !empty($p['pago_id']);
                                            ?>
                                            <tr>
                                                <td>#<?= (int)$p['id'] ?></td>
                                                <td><?= h($p['paseo']) ?></td>
                                                <td>₲<?= number_format($p['monto'], 0, ',', '.') ?></td>
                                                <td><?= $p['fecha'] ? date('d/m/Y', strtotime($p['fecha'])) : '—' ?></td>
                                                <td>
                                                    <span class="badge <?= $badgeColor ?>">
                                                        <?= ucfirst($p['estado']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($tieneComprobante): ?>
                                                        <a href="<?= BASE_URL ?>/public/api/pagos/comprobantePago.php?pago_id=<?= (int)$p['pago_id'] ?>"
                                                            class="btn btn-sm btn-outline-primary"
                                                            target="_blank">
                                                            <i class="fas fa-file-pdf me-1"></i> Ver
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <footer class="text-center text-muted small mt-4">
                    &copy; <?= date('Y'); ?> Jaguata — Panel de Paseador.
                </footer>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile (usa el id="sidebar" del SidebarPaseador)
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>