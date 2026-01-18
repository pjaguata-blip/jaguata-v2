<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Session;
use Jaguata\Services\DatabaseService;

AppConfig::init();

/* 🔒 Solo dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

/* 🔒 BLOQUEO POR ESTADO (MUY IMPORTANTE) */
if (Session::getUsuarioEstado() !== 'aprobado') {
    Session::setError('Tu cuenta aún no fue aprobada.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

/* Helper escape */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* Base */
$baseFeatures  = BASE_URL . "/features/dueno";
$usuarioNombre = Session::getUsuarioNombre() ?? 'Dueño/a';
$duenoId       = (int)(Session::getUsuarioId() ?? 0);

/* =========================
   ✅ Datos de puntos
   ========================= */
$saldoPuntos = 0;
$puntosMes   = 0;
$totalMovs   = 0;
$ultimoMov   = null;
$movimientos = [];

try {
    if ($duenoId > 0) {
        $db = DatabaseService::getInstance();

        // Saldo actual
        $rowSaldo = $db->fetchOne(
            "SELECT COALESCE(puntos,0) AS puntos
             FROM usuarios
             WHERE usu_id = :id",
            [':id' => $duenoId]
        );
        $saldoPuntos = (int)($rowSaldo['puntos'] ?? 0);

        // Historial
        $movimientos = $db->fetchAll(
            "SELECT id, descripcion, puntos, fecha
             FROM puntos
             WHERE usuario_id = :id
             ORDER BY fecha DESC",
            [':id' => $duenoId]
        );

        // Total movs y último
        $totalMovs = count($movimientos);
        $ultimoMov = $movimientos[0]['fecha'] ?? null;

        // Puntos del mes
        $rowMes = $db->fetchOne(
            "SELECT COALESCE(SUM(puntos),0) AS total_mes
             FROM puntos
             WHERE usuario_id = :id
               AND YEAR(fecha) = YEAR(CURRENT_DATE())
               AND MONTH(fecha) = MONTH(CURRENT_DATE())",
            [':id' => $duenoId]
        );
        $puntosMes = (int)($rowMes['total_mes'] ?? 0);
    }
} catch (Throwable $e) {
    $movimientos = [];
    $totalMovs   = 0;
}

/* =========================
   🎁 Recompensas (mock visual)
   - luego lo hacemos real con canje + descuento
   ========================= */
$recompensas = [
    [
        'icon' => 'fa-ticket',
        'iconClass' => 'icon-blue',
        'titulo' => '10% de descuento',
        'desc' => 'Aplicable en tu próximo paseo',
        'costo' => 50
    ],
    [
        'icon' => 'fa-dog',
        'iconClass' => 'icon-green',
        'titulo' => '20% OFF en paseo',
        'desc' => 'Cualquier duración, 1 mascota',
        'costo' => 80
    ],
    [
        'icon' => 'fa-crown',
        'iconClass' => 'icon-yellow',
        'titulo' => 'Paseo GRATIS',
        'desc' => 'Para una mascota (promo)',
        'costo' => 150
    ],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mis Puntos - Jaguata</title>

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height: 100%; }
        body { background: var(--gris-fondo, #f4f6f9); }

        /* ✅ IGUAL al Dashboard Dueño */
        main.main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }
        @media (max-width: 768px) {
            main.main-content {
                margin-left: 0;
                margin-top: 0 !important;
                width: 100% !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        /* ✅ Cards IGUALES */
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
        .dash-card-value { font-size: 1.4rem; font-weight: 700; color: #222; }
        .dash-card-label { font-size: 0.9rem; color: #555; }

        .icon-blue { color: #0d6efd; }
        .icon-green { color: var(--verde-jaguata, #3c6255); }
        .icon-yellow { color: #ffc107; }
        .icon-red { color: #dc3545; }

        /* Badge puntos */
        .puntos-badge{
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            padding:.42rem .7rem;
            border-radius:999px;
            background:#20c99722;
            border:1px solid #20c99755;
            color: var(--verde-jaguata);
            font-weight:800;
            white-space:nowrap;
        }

        /* Ajuste tabla */
        .table td { vertical-align: middle; }

        /* Recompensas: botón */
        .btn-canjear{
            border:none;
            color:#0f172a;
            font-weight:700;
            border-radius:10px;
            padding:.6rem .9rem;
            background: linear-gradient(90deg, #31c48d 0%, #0ea5e9 100%);
            box-shadow:0 10px 22px rgba(14,165,233,.14);
            transition:transform .18s ease, box-shadow .18s ease;
        }
        .btn-canjear:hover{
            transform:translateY(-2px);
            box-shadow:0 14px 26px rgba(14,165,233,.22);
        }
    </style>
</head>

<body class="page-mis-puntos">

    <!-- Sidebar Dueño -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <main class="main-content">
        <div class="py-2">

            <!-- Header (igual estilo dashboard) -->
            <div class="header-box header-dashboard mb-2">
                <div>
                    <h1>Mis puntos, <?= h($usuarioNombre); ?> ⭐</h1>
                    <p>Revisá tu saldo, cómo ganaste puntos y canjeá recompensas.</p>
                </div>
                <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
            </div>

            <!-- Métricas (cards tipo dashboard) -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-coins dash-card-icon icon-green"></i>
                        <div class="dash-card-value"><?= (int)$saldoPuntos ?></div>
                        <div class="dash-card-label">Puntos disponibles</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-calendar-check dash-card-icon icon-blue"></i>
                        <div class="dash-card-value"><?= (int)$puntosMes ?></div>
                        <div class="dash-card-label">Puntos este mes</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-list-ol dash-card-icon icon-yellow"></i>
                        <div class="dash-card-value"><?= (int)$totalMovs ?></div>
                        <div class="dash-card-label">Movimientos</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-clock dash-card-icon icon-red"></i>
                        <div class="dash-card-value"><?= $ultimoMov ? h(date('d/m/Y', strtotime((string)$ultimoMov))) : '—' ?></div>
                        <div class="dash-card-label">Último movimiento</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">

                <!-- Col izquierda (Historial) -->
                <div class="col-lg-8">

                    <!-- FILTROS (igual estética con .filtros) -->
                    <div class="filtros mb-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Buscar</label>
                                <input type="text" id="searchInput" class="form-control" placeholder="Ej: paseo, bono...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Desde</label>
                                <input type="date" id="filterDesde" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Hasta</label>
                                <input type="date" id="filterHasta" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Historial (section-card igual dashboard) -->
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-history me-2"></i>Historial de puntos
                        </div>

                        <div class="section-body">
                            <?php if (empty($movimientos)): ?>
                                <p class="text-center text-muted mb-0">Aún no tenés puntos registrados.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle text-center mb-0" id="tablaPuntos">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th class="text-start">¿Dónde se ganaron?</th>
                                                <th>Puntos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($movimientos as $m):
                                                $fec = (string)($m['fecha'] ?? '');
                                                $fechaShow = $fec ? date('d/m/Y H:i', strtotime($fec)) : '-';
                                                $fechaData = $fec ? date('Y-m-d', strtotime($fec)) : '';
                                                $desc = (string)($m['descripcion'] ?? '');
                                                $pts  = (int)($m['puntos'] ?? 0);
                                                $texto = strtolower(trim($desc . ' ' . $pts));
                                            ?>
                                                <tr data-texto="<?= h($texto) ?>" data-fecha="<?= h($fechaData) ?>">
                                                    <td style="white-space:nowrap;"><?= h($fechaShow) ?></td>
                                                    <td class="text-start">
                                                        <div class="fw-semibold"><?= h($desc !== '' ? $desc : 'Movimiento de puntos') ?></div>
                                                        <div class="text-muted small">Registro #<?= (int)($m['id'] ?? 0) ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="puntos-badge">
                                                            <i class="fa-solid fa-star"></i> <?= $pts ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <p class="text-muted small mt-2 mb-0">
                                    Tip: combiná búsqueda + rango de fechas para encontrar un movimiento específico.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Col derecha (Recompensas) -->
                <div class="col-lg-4">
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-gift me-2"></i>Recompensas
                        </div>

                        <div class="section-body">
                            <p class="text-muted small mb-3">
                                Canjeá tus puntos por beneficios. (Por ahora visual; luego lo hacemos real con canje.)
                            </p>

                            <div class="row g-3">
                                <?php foreach ($recompensas as $r): ?>
                                    <div class="col-12">
                                        <div class="dash-card text-start">
                                            <div class="d-flex align-items-start justify-content-between">
                                                <div class="d-flex gap-3">
                                                    <i class="fa-solid <?= h($r['icon']) ?> dash-card-icon <?= h($r['iconClass']) ?>"></i>
                                                    <div>
                                                        <div class="fw-bold"><?= h($r['titulo']) ?></div>
                                                        <div class="dash-card-label"><?= h($r['desc']) ?></div>
                                                    </div>
                                                </div>

                                                <span class="badge bg-secondary" style="height:fit-content;">
                                                    <?= (int)$r['costo'] ?> pts
                                                </span>
                                            </div>

                                            <div class="mt-2">
                                                <?php if ($saldoPuntos >= (int)$r['costo']): ?>
                                                    <button class="btn-canjear w-100" type="button">
                                                        Canjear
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary w-100" type="button" disabled>
                                                        Puntos insuficientes
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="text-muted small mt-3">
                                * Las recompensas se pueden aplicar al confirmar un paseo (cuando activemos el canje real).
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel del Dueño
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Filtros (igual lógica que usás)
        const search = document.getElementById('searchInput');
        const desde  = document.getElementById('filterDesde');
        const hasta  = document.getElementById('filterHasta');
        const rows   = document.querySelectorAll('#tablaPuntos tbody tr');

        function filtrar(){
            rows.forEach(r => {
                const txt = (r.dataset.texto || '');
                const f   = (r.dataset.fecha || '');
                let ok = true;

                if (search?.value && !txt.includes(search.value.toLowerCase())) ok = false;
                if (desde?.value && f && f < desde.value) ok = false;
                if (hasta?.value && f && f > hasta.value) ok = false;

                r.style.display = ok ? '' : 'none';
            });
        }

        [search, desde, hasta].forEach(el => el?.addEventListener('input', filtrar));
    </script>

</body>
</html>
