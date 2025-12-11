<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/ConfiguracionController.php';
require_once dirname(__DIR__, 2) . '/src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\ConfiguracionController;
use Jaguata\Services\DatabaseService;

AppConfig::init();

/* 🔒 Solo admin */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

/* Helper */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ============================
   CONFIGURACIÓN FINANCIERA
   ============================ */
$configCtrl = new ConfiguracionController();
$configDB   = $configCtrl->getAll();

$comisionPorc = (float)($configDB['comision_porcentaje'] ?? 0);
$tarifaBase   = (float)($configDB['tarifa_base'] ?? 0);

/* ============================
   FILTROS
   ============================ */
$desde     = $_GET['desde'] ?? '';
$hasta     = $_GET['hasta'] ?? '';
$exportCsv = (($_GET['export'] ?? '') === 'csv');

$db = DatabaseService::getInstance()->getConnection();

/* ============================
   CONSULTA PRINCIPAL
   ============================ */

$where  = "WHERE pg.estado = 'pagado'";
$params = [];

if ($desde !== '') {
    $where .= " AND DATE(pg.created_at) >= :desde";
    $params[':desde'] = $desde;
}
if ($hasta !== '') {
    $where .= " AND DATE(pg.created_at) <= :hasta";
    $params[':hasta'] = $hasta;
}

$sql = "
    SELECT
        p.paseo_id,
        p.inicio,
        p.duracion AS duracion_min,
        p.precio_total,
        pg.id AS pago_id,
        pg.monto AS monto_pagado,
        pg.created_at AS pagado_en,
        dueno.nombre    AS dueno_nombre,
        paseador.nombre AS paseador_nombre
    FROM paseos p
    INNER JOIN pagos pg      ON pg.paseo_id  = p.paseo_id
    INNER JOIN mascotas m    ON m.mascota_id = p.mascota_id
    INNER JOIN usuarios dueno    ON dueno.usu_id    = m.dueno_id
    INNER JOIN usuarios paseador ON paseador.usu_id = p.paseador_id
    $where
    ORDER BY pg.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* ============================
   CÁLCULOS
   ============================ */

$totales = [
    'total_cobrado'       => 0,
    'ganancia_app'        => 0,
    'ganancia_paseadores' => 0,
    'gasto_duenos'        => 0,
];

foreach ($registros as &$r) {
    $monto = (float)($r['monto_pagado'] ?? 0);

    $comision   = $monto * ($comisionPorc / 100);
    $paseador   = $monto - $comision;
    $gastoDueno = $monto;

    $r['comision_app']      = $comision;
    $r['ganancia_paseador'] = $paseador;
    $r['gasto_dueno']       = $gastoDueno;

    $totales['total_cobrado']       += $monto;
    $totales['ganancia_app']        += $comision;
    $totales['ganancia_paseadores'] += $paseador;
    $totales['gasto_duenos']        += $monto;
}
unset($r);

function formato_guarani(float $m): string
{
    return number_format($m, 0, ',', '.');
}

/* ============================
   EXPORTAR CSV
   ============================ */
if ($exportCsv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte_ganancias_jaguata_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    // BOM UTF-8
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Encabezados
    fputcsv($out, [
        'ID Pago',
        'ID Paseo',
        'Fecha pago',
        'Dueño',
        'Paseador',
        'Monto pagado',
        'Ganancia APP (comisión)',
        'Ganancia Paseador',
        'Gasto Dueño'
    ]);

    foreach ($registros as $row) {
        fputcsv($out, [
            $row['pago_id'],
            $row['paseo_id'],
            $row['pagado_en'],
            $row['dueno_nombre'],
            $row['paseador_nombre'],
            $row['monto_pagado'],
            $row['comision_app'],
            $row['ganancia_paseador'],
            $row['gasto_dueno'],
        ]);
    }

    // Línea en blanco + resumen por rol
    fputcsv($out, []);
    fputcsv($out, ['RESUMEN POR ROL']);
    fputcsv($out, ['APP (Administración)', '₲' . formato_guarani($totales['ganancia_app'])]);
    fputcsv($out, ['Paseadores', '₲' . formato_guarani($totales['ganancia_paseadores'])]);
    fputcsv($out, ['Dueños (gasto)', '₲' . formato_guarani($totales['gasto_duenos'])]);

    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Ganancias - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        /* Que toda la vista se estire como el dashboard */
        html,
        body {
            height: 100%;
        }

        body {
            background: var(--gris-fondo, #f4f6f9);
        }

        main.main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }

        @media (max-width: 768px) {
            main.main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        /* Tarjetas estilo dashboard */
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
        }

        .dash-card-icon {
            font-size: 2rem;
            margin-bottom: 6px;
        }

        .dash-card-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #222;
        }

        .dash-card-label {
            font-size: 0.9rem;
            color: #555;
        }

        .icon-blue {
            color: #0d6efd;
        }

        .icon-green {
            color: var(--verde-jaguata, #3c6255);
        }

        .icon-yellow {
            color: #ffc107;
        }

        .icon-red {
            color: #dc3545;
        }
    </style>
</head>

<body>

    <?php include dirname(__DIR__, 2) . '/src/Templates/SidebarAdmin.php'; ?>

    <!-- Botón para abrir/cerrar sidebar en mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">

        <!-- HEADER -->
        <div class="header-box header-pagos mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold">Reporte de Ganancias</h1>
                <p class="mb-0">Resumen financiero de la aplicación 💸</p>
            </div>
            <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center">

                <i class="fas fa-chart-line fa-3x opacity-75 d-none d-md-block"></i>
            </div>
        </div>

        <!-- TARJETAS RESUMEN (estilo dashboard) -->
        <div class="row g-3 mb-4">

            <div class="col-md-3">
                <div class="dash-card">
                    <i class="fas fa-money-bill-wave dash-card-icon icon-blue"></i>
                    <div class="dash-card-value">₲<?= formato_guarani($totales['total_cobrado']); ?></div>
                    <div class="dash-card-label">Total cobrado (dueños)</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dash-card">
                    <i class="fas fa-building dash-card-icon icon-green"></i>
                    <div class="dash-card-value">₲<?= formato_guarani($totales['ganancia_app']); ?></div>
                    <div class="dash-card-label">Ganancia de la aplicación</div>
                    <small class="text-muted">Comisión actual: <?= $comisionPorc; ?>%</small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dash-card">
                    <i class="fas fa-user-tie dash-card-icon icon-yellow"></i>
                    <div class="dash-card-value">₲<?= formato_guarani($totales['ganancia_paseadores']); ?></div>
                    <div class="dash-card-label">Ganancia de paseadores</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dash-card">
                    <i class="fas fa-user dash-card-icon icon-red"></i>
                    <div class="dash-card-value">₲<?= formato_guarani($totales['gasto_duenos']); ?></div>
                    <div class="dash-card-label">Gastos de dueños</div>
                </div>
            </div>

        </div>

        <!-- RESUMEN POR ROL (detallado) -->
        <div class="section-card mb-4">
            <div class="section-header">
                <i class="fas fa-users me-2"></i>Resumen por rol
            </div>
            <div class="section-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Descripción</th>
                                <th class="text-end">Monto (₲)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>APP / Administración</strong></td>
                                <td>Ingresos por comisión de cada paseo.</td>
                                <td class="text-end fw-bold text-success">
                                    ₲<?= formato_guarani($totales['ganancia_app']); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Paseadores</strong></td>
                                <td>Monto total destinado a los paseadores.</td>
                                <td class="text-end fw-bold">
                                    ₲<?= formato_guarani($totales['ganancia_paseadores']); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Dueños</strong></td>
                                <td>Gasto total realizado por los dueños en paseos.</td>
                                <td class="text-end fw-bold text-danger">
                                    ₲<?= formato_guarani($totales['gasto_duenos']); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CONFIGURACIÓN -->
        <div class="section-card mb-4">
            <div class="section-header">
                <i class="fas fa-wallet me-2"></i>Configuración financiera
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <p>Comisión del sistema:</p>
                    <span class="badge bg-success fs-6"><?= $comisionPorc ?>%</span>
                </div>

                <div class="col-md-6">
                    <p>Tarifa base por paseo:</p>
                    <span class="badge bg-primary fs-6">
                        ₲<?= formato_guarani($tarifaBase) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filtros mb-4">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Desde</label>
                    <input type="date" name="desde" class="form-control" value="<?= h($desde) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Hasta</label>
                    <input type="date" name="hasta" class="form-control" value="<?= h($hasta) ?>">
                </div>

                <div class="col-md-4 text-md-end">
                    <button class="btn btn-success mt-3 mt-md-0">
                        <i class="fas fa-filter me-1"></i>Aplicar filtros
                    </button>
                </div>
            </form>
        </div>
        <!-- EXPORT -->
        <div class="export-buttons">
            <button class="btn btn-excel"
                onclick="window.location.href='<?= BASE_URL; ?>/public/api/export_reporte_ganancias.php'">
                <i class="fas fa-file-excel"></i> Excel
            </button>
        </div>
        <!-- TABLA DETALLE -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Detalle de pagos</h5>
                <span class="badge bg-secondary"><?= count($registros) ?> registro(s)</span>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#Paseo</th>
                                <th>Fecha pago</th>
                                <th>Dueño</th>
                                <th>Paseador</th>
                                <th>Monto pagado</th>
                                <th>Comisión (APP)</th>
                                <th>Paseador</th>
                                <th>Dueño (gasto)</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($registros)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Sin registros</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registros as $r): ?>
                                    <tr>
                                        <td>#<?= (int)$r['paseo_id'] ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($r['pagado_en'])) ?></td>
                                        <td><?= h($r['dueno_nombre']) ?></td>
                                        <td><?= h($r['paseador_nombre']) ?></td>
                                        <td>₲<?= formato_guarani((float)$r['monto_pagado']) ?></td>
                                        <td>₲<?= formato_guarani((float)$r['comision_app']) ?></td>
                                        <td>₲<?= formato_guarani((float)$r['ganancia_paseador']) ?></td>
                                        <td>₲<?= formato_guarani((float)$r['gasto_dueno']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer>
            <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
        </footer>

    </main>

    <script>
        // Toggle sidebar en mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>

</body>

</html>