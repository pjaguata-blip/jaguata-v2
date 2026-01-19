<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PaseoController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/MascotaController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/ReporteController.php';
require_once dirname(__DIR__, 2) . '/src/Models/Calificacion.php';
require_once dirname(__DIR__, 2) . '/src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\UsuarioController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Controllers\ReporteController;
use Jaguata\Models\Calificacion;
use Jaguata\Services\DatabaseService;

AppConfig::init();

/* 🔒 Autenticación */
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}
if (Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ✅ (por consistencia con otras pantallas) */
$baseFeatures = BASE_URL . '/features/admin';

/* Controladores base */
$usuarioController = new UsuarioController();
$paseoController   = new PaseoController();
$mascotaController = new MascotaController();

/* Datos base */
$usuarios = $usuarioController->index() ?: [];
$paseos   = $paseoController->index() ?: [];
$mascotas = $mascotaController->index() ?: [];

/* Métricas base */
$totalUsuarios = count($usuarios);
$totalPaseos   = count($paseos);
$totalMascotas = count($mascotas);

$paseosActivos = array_filter($paseos, fn($p) => in_array(strtolower((string)($p['estado'] ?? '')), ['pendiente', 'solicitado', 'confirmado', 'en_curso'], true));
$paseosCompletos = array_filter($paseos, fn($p) => in_array(strtolower((string)($p['estado'] ?? '')), ['completo', 'finalizado'], true));

$db = DatabaseService::getInstance()->getConnection();

/* ==========================================
   ✅ INGRESOS TOTALES = SUSCRIPCIONES
   - Solo aprobadas (activa + vencida)
   ========================================== */
$sqlSubs = "
    SELECT 
        COUNT(*) AS cantidad,
        COALESCE(SUM(monto), 0) AS total
    FROM suscripciones
    WHERE estado IN ('activa','vencida')
";
$stmtSubs = $db->query($sqlSubs);
$subsRow  = $stmtSubs ? ($stmtSubs->fetch(PDO::FETCH_ASSOC) ?: []) : [];

$cantSuscripcionesPagadas = (int)($subsRow['cantidad'] ?? 0);
$ingresosSuscripciones    = (int)($subsRow['total'] ?? 0);

/* ==========================================
   PAGOS (tu tabla pagos) - métricas aparte
   ========================================== */
$sql = "
    SELECT TRIM(LOWER(estado)) AS estado, COUNT(*) AS c
    FROM pagos
    GROUP BY TRIM(LOWER(estado))
";
$stmt = $db->query($sql);
$rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

$map = [];
foreach ($rows as $r) {
    $map[(string)($r['estado'] ?? '')] = (int)($r['c'] ?? 0);
}

$totalPagosReal = $map['confirmado_por_dueno'] ?? 0; // ✅ tu “pagado”
$totalPagosPend = $map['pendiente'] ?? 0;

/* =============================
   REPORTES (ReporteController)
   ============================= */
$reporteController = new ReporteController();
$estadisticas      = $reporteController->getEstadisticas() ?: [];

/* =============================
   CALIFICACIONES POR ROL ⭐
   ============================= */
$calModel = new Calificacion();
$promedioPaseadores = $calModel->promedioGlobalPorTipo('paseador');
$promedioDuenos     = $calModel->promedioGlobalPorTipo('mascota');

/* Merge con defaults + tus métricas */
$estadisticas = array_merge([
    'usuarios'              => $totalUsuarios,
    'paseos_total'          => $totalPaseos,
    'paseos_completos'      => count($paseosCompletos),
    'paseos_pendientes'     => $totalPaseos - count($paseosCompletos),

    'ingresos_totales'      => $ingresosSuscripciones,

    'roles'                 => [],
    'paseos_por_dia'        => [],
    'ingresos_por_mes'      => [],
    'ingresos_por_paseador' => [],
], $estadisticas);

$estadisticas['promedio_paseadores']   = $promedioPaseadores;
$estadisticas['promedio_duenos']       = $promedioDuenos;
$estadisticas['suscripciones_pagadas'] = $cantSuscripcionesPagadas;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ✅ igual que tus otras pantallas: sin scroll horizontal */
        html, body { overflow-x: hidden; width: 100%; }
        .chart-card canvas { width: 100% !important; height: 260px !important; }
        @media (max-width: 768px){
            .chart-card canvas { height: 240px !important; }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <main>
        <div class="container-fluid px-3 px-md-2">

            <!-- ✅ HEADER estilo igual a las otras pantallas -->
            <div class="header-box header-dashboard mb-3">
                <div>
                    <h1 class="fw-bold mb-1">Panel de Administración</h1>
                    <p class="mb-0">Bienvenido, <?= h(Session::getUsuarioNombre() ?? 'Administrador'); ?> 👋</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-user-shield fa-2x opacity-75 d-none d-md-inline"></i>
                </div>
            </div>

            <!-- ✅ MÉTRICAS (con mismo look “cards” del dashboard) -->
            <div class="row g-3 mb-2">
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-users text-primary"></i>
                        <h4><?= (int)$totalUsuarios ?></h4>
                        <p>Usuarios registrados</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-dog text-success"></i>
                        <h4><?= (int)$totalMascotas ?></h4>
                        <p>Mascotas registradas</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-walking text-warning"></i>
                        <h4><?= (int)$totalPaseos ?></h4>
                        <p>Paseos totales</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-wallet text-info"></i>
                        <h4>₲<?= number_format((float)$estadisticas['ingresos_totales'], 0, ',', '.') ?></h4>
                        <p>Ingresos por suscripciones</p>
                        <small class="text-muted">
                            <?= (int)($estadisticas['suscripciones_pagadas'] ?? 0); ?> suscripciones aprobadas
                        </small>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-check-circle text-success"></i>
                        <h4><?= (int)$estadisticas['paseos_completos'] ?></h4>
                        <p>Paseos completados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-hourglass-half text-warning"></i>
                        <h4><?= (int)$estadisticas['paseos_pendientes'] ?></h4>
                        <p>Paseos pendientes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-money-check-alt text-success"></i>
                        <h4><?= (int)$totalPagosReal ?></h4>
                        <p>Pagos realizados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-hourglass text-danger"></i>
                        <h4><?= (int)$totalPagosPend ?></h4>
                        <p>Pagos pendientes</p>
                    </div>
                </div>
            </div>

            <!-- ✅ REPUTACIÓN (igual estilo “stat-card”) -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="stat-card">
                        <i class="fas fa-star text-warning"></i>
                        <h4>
                            <?php if ($estadisticas['promedio_paseadores'] !== null): ?>
                                <?= number_format((float)$estadisticas['promedio_paseadores'], 1, ',', '.'); ?> ★
                            <?php else: ?>—<?php endif; ?>
                        </h4>
                        <p>Promedio de calificaciones de paseadores</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <i class="fas fa-paw text-success"></i>
                        <h4>
                            <?php if ($estadisticas['promedio_duenos'] !== null): ?>
                                <?= number_format((float)$estadisticas['promedio_duenos'], 1, ',', '.'); ?> ★
                            <?php else: ?>—<?php endif; ?>
                        </h4>
                        <p>Promedio de calificaciones de dueños/mascotas</p>
                    </div>
                </div>
            </div>

            <!-- ✅ FILTROS (misma caja .filtros que usás en listas) -->
            <div class="filtros mb-4">
                <form class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Buscar</label>
                        <input type="text" id="searchReportInput" class="form-control"
                               placeholder="Escribí: usuarios, paseos, ingresos...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Mostrar gráfico de</label>
                        <select id="filterGrafico" class="form-select">
                            <option value="todos">Todos</option>
                            <option value="dia">Paseos por día</option>
                            <option value="mes">Ingresos por mes</option>
                            <option value="roles">Usuarios por rol</option>
                            <option value="paseadores">Ingresos por paseador</option>
                            <option value="resumen">Resumen general</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Fecha desde</label>
                        <input type="date" class="form-control" disabled>
                    </div>
                </form>
            </div>

            <!-- ✅ GRÁFICOS con el mismo “card” que usás en tus listas (section-card) -->
            <div class="row" id="chartGroup">

                <div class="col-lg-6 mb-3 chart-item" data-type="dia">
                    <div class="section-card chart-card h-100">
                        <div class="section-header d-flex align-items-center">
                            <i class="fas fa-chart-bar me-2"></i>
                            <span>Paseos por día</span>
                        </div>
                        <div class="section-body">
                            <canvas id="chartPaseosPorDia"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3 chart-item" data-type="mes">
                    <div class="section-card chart-card h-100">
                        <div class="section-header d-flex align-items-center">
                            <i class="fas fa-chart-line me-2"></i>
                            <span>Ingresos por mes</span>
                        </div>
                        <div class="section-body">
                            <canvas id="chartIngresosMes"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3 chart-item" data-type="roles">
                    <div class="section-card chart-card h-100">
                        <div class="section-header d-flex align-items-center">
                            <i class="fas fa-user-group me-2"></i>
                            <span>Usuarios por rol</span>
                        </div>
                        <div class="section-body">
                            <canvas id="chartRoles"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3 chart-item" data-type="paseadores">
                    <div class="section-card chart-card h-100">
                        <div class="section-header d-flex align-items-center">
                            <i class="fas fa-user-tie me-2"></i>
                            <span>Ingresos por paseador</span>
                        </div>
                        <div class="section-body">
                            <canvas id="chartPaseadores"></canvas>
                            <p class="text-muted small mt-2 mb-0">
                                Mostrando distribución porcentual de ingresos entre paseadores.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3 chart-item" data-type="resumen">
                    <div class="section-card h-100">
                        <div class="section-header d-flex align-items-center">
                            <i class="fas fa-table me-2"></i>
                            <span>Resumen general</span>
                        </div>
                        <div class="section-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Concepto</th>
                                            <th class="text-end">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Usuarios totales</td>
                                            <td class="text-end"><?= (int)$estadisticas['usuarios'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Paseos totales</td>
                                            <td class="text-end"><?= (int)$estadisticas['paseos_total'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Paseos completados</td>
                                            <td class="text-end"><?= (int)$estadisticas['paseos_completos'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Paseos pendientes</td>
                                            <td class="text-end"><?= (int)$estadisticas['paseos_pendientes'] ?></td>
                                        </tr>
                                        <tr>
                                            <td>Ingresos por suscripciones</td>
                                            <td class="text-end">₲<?= number_format((float)$estadisticas['ingresos_totales'], 0, ',', '.') ?></td>
                                        </tr>
                                        <tr>
                                            <td>Suscripciones aprobadas</td>
                                            <td class="text-end"><?= (int)($estadisticas['suscripciones_pagadas'] ?? 0) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <footer class="mt-3">
                <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /* ✅ Toggle sidebar (igual a tus pantallas) */
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const btnToggle = document.getElementById('btnSidebarToggle');
            if (btnToggle && sidebar) {
                btnToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
            }
        });

        const paseosPorDiaLabels = <?= json_encode(array_values(array_keys($estadisticas['paseos_por_dia'])), JSON_UNESCAPED_UNICODE); ?>;
        const paseosPorDiaData   = <?= json_encode(array_values($estadisticas['paseos_por_dia']), JSON_UNESCAPED_UNICODE); ?>;

        const ingresosMesLabels  = <?= json_encode(array_values(array_keys($estadisticas['ingresos_por_mes'])), JSON_UNESCAPED_UNICODE); ?>;
        const ingresosMesData    = <?= json_encode(array_values($estadisticas['ingresos_por_mes']), JSON_UNESCAPED_UNICODE); ?>;

        const rolesLabels        = <?= json_encode(array_values(array_keys($estadisticas['roles'])), JSON_UNESCAPED_UNICODE); ?>;
        const rolesData          = <?= json_encode(array_values($estadisticas['roles']), JSON_UNESCAPED_UNICODE); ?>;

        const paseadoresLabels   = <?= json_encode(array_values(array_keys($estadisticas['ingresos_por_paseador'])), JSON_UNESCAPED_UNICODE); ?>;
        const paseadoresData     = <?= json_encode(array_values($estadisticas['ingresos_por_paseador']), JSON_UNESCAPED_UNICODE); ?>;

        // Paseos por día
        if (document.getElementById('chartPaseosPorDia')) {
            new Chart(document.getElementById('chartPaseosPorDia'), {
                type: 'bar',
                data: {
                    labels: paseosPorDiaLabels,
                    datasets: [{
                        label: 'Paseos',
                        data: paseosPorDiaData,
                        backgroundColor: '#3c6255',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Ingresos por mes
        if (document.getElementById('chartIngresosMes')) {
            new Chart(document.getElementById('chartIngresosMes'), {
                type: 'line',
                data: {
                    labels: ingresosMesLabels,
                    datasets: [{
                        label: 'Ingresos (₲)',
                        data: ingresosMesData,
                        borderColor: '#20c997',
                        backgroundColor: 'rgba(32,201,151,0.25)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Usuarios por rol
        if (document.getElementById('chartRoles')) {
            new Chart(document.getElementById('chartRoles'), {
                type: 'doughnut',
                data: {
                    labels: rolesLabels,
                    datasets: [{
                        data: rolesData,
                        backgroundColor: ['#3c6255', '#20c997', '#f6c23e', '#ff7f50', '#6c757d']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Ingresos por paseador (%)
        if (document.getElementById('chartPaseadores')) {
            const totalIngresosPaseadores = paseadoresData.reduce((a, b) => a + Number(b || 0), 0);

            new Chart(document.getElementById('chartPaseadores'), {
                type: 'doughnut',
                data: {
                    labels: paseadoresLabels,
                    datasets: [{
                        data: paseadoresData,
                        backgroundColor: ['#3c6255', '#20c997', '#f6c23e', '#ff7f50', '#6c757d', '#0d6efd', '#198754']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const valor = Number(ctx.parsed || 0);
                                    const porcentaje = totalIngresosPaseadores > 0
                                        ? ((valor / totalIngresosPaseadores) * 100).toFixed(1)
                                        : 0;
                                    const label = ctx.label || '';
                                    return `${label}: ₲${valor.toLocaleString('es-PY')} (${porcentaje}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Filtros de gráficos
        const searchReportInput = document.getElementById('searchReportInput');
        const filterGrafico     = document.getElementById('filterGrafico');
        const chartItems        = document.querySelectorAll('.chart-item');

        function aplicarFiltroTipo() {
            const val = (filterGrafico?.value || 'todos');
            chartItems.forEach(c => {
                c.style.display = (val === 'todos' || c.dataset.type === val) ? '' : 'none';
            });
        }

        function aplicarFiltroTexto() {
            const text = (searchReportInput?.value || '').toLowerCase();
            chartItems.forEach(c => {
                const visible = c.textContent.toLowerCase().includes(text);
                c.style.display = visible ? '' : 'none';
            });
        }

        if (filterGrafico) filterGrafico.addEventListener('change', aplicarFiltroTipo);
        if (searchReportInput) searchReportInput.addEventListener('input', aplicarFiltroTexto);
    </script>

</body>
</html>
