<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Services\DatabaseService;

AppConfig::init();

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

/* Helpers */
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function formato_guarani(float $m): string { return number_format($m, 0, ',', '.'); }

$baseFeatures = BASE_URL . '/features/admin';

/* ============================
   FILTROS + TAB
   ============================ */
$desde     = $_GET['desde'] ?? '';
$hasta     = $_GET['hasta'] ?? '';
$tab       = $_GET['tab'] ?? 'sus';   // sus | paseos
$exportCsv = (($_GET['export'] ?? '') === 'csv');
$debug     = (($_GET['debug'] ?? '') === '1');

$db = DatabaseService::getInstance()->getConnection();

/* ============================
   1) INGRESOS APP = SUSCRIPCIONES
   ============================ */
$whereS  = "WHERE 1=1";
$paramsS = [];

if ($desde !== '') { $whereS .= " AND DATE(s.created_at) >= :desdeS"; $paramsS[':desdeS'] = $desde; }
if ($hasta !== '') { $whereS .= " AND DATE(s.created_at) <= :hastaS"; $paramsS[':hastaS'] = $hasta; }

$sqlSus = "
    SELECT
        s.id,
        s.paseador_id,
        u.nombre AS paseador_nombre,
        s.plan,
        s.monto,
        s.estado,
        s.created_at AS pagado_en,
        s.inicio,
        s.fin,
        s.referencia
    FROM suscripciones s
    INNER JOIN usuarios u ON u.usu_id = s.paseador_id
    $whereS
    ORDER BY s.created_at DESC
";
$stmtS = $db->prepare($sqlSus);
$stmtS->execute($paramsS);
$suscripciones = $stmtS->fetchAll(PDO::FETCH_ASSOC) ?: [];

$totSus = [
    'total_ingreso_app' => 0,
    'cant_activas'      => 0,
    'cant_pendientes'   => 0,
    'cant_vencidas'     => 0,
    'cant_rechazadas'   => 0,
    'cant_canceladas'   => 0,
    'cant_total'        => 0,
];

foreach ($suscripciones as $s) {
    $totSus['cant_total']++;
    $estado = strtolower(trim((string)($s['estado'] ?? '')));

    if ($estado === 'activa') {
        $totSus['total_ingreso_app'] += (float)($s['monto'] ?? 0);
        $totSus['cant_activas']++;
    } elseif ($estado === 'pendiente') {
        $totSus['cant_pendientes']++;
    } elseif ($estado === 'vencida') {
        $totSus['cant_vencidas']++;
    } elseif ($estado === 'rechazada') {
        $totSus['cant_rechazadas']++;
    } elseif ($estado === 'cancelada') {
        $totSus['cant_canceladas']++;
    }
}

/* ============================
   2) PAGOS DE PASEOS (DUEÑO -> PASEADOR)
   ============================ */
$whereP  = "WHERE 1=1";
$paramsP = [];

if ($desde !== '') { $whereP .= " AND DATE(pg.created_at) >= :desdeP"; $paramsP[':desdeP'] = $desde; }
if ($hasta !== '') { $whereP .= " AND DATE(pg.created_at) <= :hastaP"; $paramsP[':hastaP'] = $hasta; }

$sqlPagos = "
    SELECT
        pg.id AS pago_id,
        pg.paseo_id,
        pg.usuario_id AS dueno_id,
        dueno.nombre AS dueno_nombre,
        p.paseador_id,
        paseador.nombre AS paseador_nombre,
        pg.metodo,
        pg.monto,
        pg.estado,
        pg.created_at AS pagado_en
    FROM pagos pg
    INNER JOIN paseos p          ON p.paseo_id = pg.paseo_id
    INNER JOIN usuarios dueno    ON dueno.usu_id = pg.usuario_id
    INNER JOIN usuarios paseador ON paseador.usu_id = p.paseador_id
    $whereP
    ORDER BY pg.created_at DESC
";
$stmtP = $db->prepare($sqlPagos);
$stmtP->execute($paramsP);
$pagosPaseos = $stmtP->fetchAll(PDO::FETCH_ASSOC) ?: [];

function es_pago_confirmado(?string $estado): bool {
    $e = strtolower(trim((string)$estado));
    return in_array($e, ['confirmado_por_dueno','confirmado','pagado','completado','aprobado'], true);
}

$totPag = [
    'total_cobrado_duenos' => 0,
    'total_paseadores'     => 0,
    'monto_pendiente'      => 0,
    'cant_confirmados'     => 0,
    'cant_pendientes'      => 0,
    'cant_total'           => 0,
];

foreach ($pagosPaseos as $r) {
    $totPag['cant_total']++;
    $m = (float)($r['monto'] ?? 0);

    if (es_pago_confirmado($r['estado'] ?? '')) {
        $totPag['total_cobrado_duenos'] += $m;
        $totPag['total_paseadores']     += $m;
        $totPag['cant_confirmados']++;
    } else {
        $totPag['monto_pendiente'] += $m;
        $totPag['cant_pendientes']++;
    }
}

/* ============================
   EXPORT CSV
   ============================ */
if ($exportCsv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte_ganancias_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($out, ['REPORTE INGRESOS APP (SUSCRIPCIONES)']);
    fputcsv($out, ['ID','Fecha','Paseador','Plan','Monto','Estado','Inicio','Fin','Referencia']);
    foreach ($suscripciones as $s) {
        fputcsv($out, [
            $s['id'] ?? '',
            $s['pagado_en'] ?? '',
            $s['paseador_nombre'] ?? '',
            $s['plan'] ?? '',
            $s['monto'] ?? 0,
            $s['estado'] ?? '',
            $s['inicio'] ?? '',
            $s['fin'] ?? '',
            $s['referencia'] ?? '',
        ]);
    }

    fputcsv($out, []);
    fputcsv($out, ['TOTAL INGRESO APP (solo activas)', '₲' . formato_guarani((float)$totSus['total_ingreso_app'])]);

    fputcsv($out, []);
    fputcsv($out, []);
    fputcsv($out, ['REPORTE PAGOS DE PASEOS (DUEÑO -> PASEADOR)']);
    fputcsv($out, ['ID Pago','ID Paseo','Fecha','Dueño','Paseador','Método','Monto','Estado']);
    foreach ($pagosPaseos as $p) {
        fputcsv($out, [
            $p['pago_id'] ?? '',
            $p['paseo_id'] ?? '',
            $p['pagado_en'] ?? '',
            $p['dueno_nombre'] ?? '',
            $p['paseador_nombre'] ?? '',
            $p['metodo'] ?? '',
            $p['monto'] ?? 0,
            $p['estado'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

/* ============================
   UI helpers (badges)
   ============================ */
function badgeSus(?string $estado): array {
    $e = strtolower(trim((string)$estado));
    return match ($e) {
        'activa'    => ['bg-success', 'ACTIVA', 'fa-circle-check'],
        'pendiente' => ['bg-warning text-dark', 'PEND.', 'fa-clock'],
        'vencida'   => ['bg-secondary', 'VENC.', 'fa-hourglass-end'],
        'rechazada' => ['bg-danger', 'RECH.', 'fa-circle-xmark'],
        'cancelada' => ['bg-dark', 'CANC.', 'fa-ban'],
        default     => ['bg-light text-dark border', '—', 'fa-circle'],
    };
}
function badgePago(?string $estado): array {
    $e = strtolower(trim((string)$estado));
    if (es_pago_confirmado($e)) return ['bg-success', 'CONF.', 'fa-circle-check'];
    if ($e === 'pendiente')     return ['bg-warning text-dark', 'PEND.', 'fa-clock'];
    if ($e === 'rechazado')     return ['bg-danger', 'RECH.', 'fa-circle-xmark'];
    return ['bg-secondary', strtoupper(substr($e ?: 'N/D', 0, 5)), 'fa-circle'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte/Suscrpciones - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { overflow-x: hidden; width: 100%; }
        .table-responsive { overflow-x: auto; }
        th, td { white-space: nowrap; }

        /* Badges */
        .badge-soft{
            display:inline-flex; align-items:center; gap:6px;
            padding:8px 12px; border-radius:999px;
            font-weight:800; font-size:.82rem;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

<main>
  <div class="container-fluid px-3 px-md-2">
    <div class="header-box header-usuarios mb-3">
        <div>
            <h1 class="fw-bold mb-1">Reporte/Suscrpciones</h1>
            <p class="mb-0">Ingresos APP por Suscripciones + Flujo Dueño → Paseador 💸</p>
        </div>

        <div class="d-flex align-items-center gap-2">
        
        </div>

        <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if ($debug): ?>
      <div class="alert alert-warning">
          <div><strong>DEBUG</strong></div>

          <div class="mt-2"><strong>Estados en pagos.estado:</strong></div>
          <ul class="mb-0">
              <?php foreach ($debugEstadosPagos as $e): ?>
                  <li><?= h($e['estado'] ?? '') ?> (<?= (int)($e['c'] ?? 0) ?>)</li>
              <?php endforeach; ?>
          </ul>

          <div class="mt-3"><strong>Estados en suscripciones.estado:</strong></div>
          <ul class="mb-0">
              <?php foreach ($debugEstadosSus as $e): ?>
                  <li><?= h($e['estado'] ?? '') ?> (<?= (int)($e['c'] ?? 0) ?>)</li>
              <?php endforeach; ?>
          </ul>
      </div>
    <?php endif; ?>

    <!-- FILTROS (misma estructura que Usuarios) -->
    <div class="filtros mb-3">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Buscar</label>
          <input id="searchInput" type="text" class="form-control" placeholder="Buscar (nombre, estado, monto...)">
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Sección</label>
          <select id="filterTab" name="tab" class="form-select">
            <option value="sus" <?= ($tab === 'sus') ? 'selected' : '' ?>>Suscripciones (Ingreso APP)</option>
            <option value="paseos" <?= ($tab === 'paseos') ? 'selected' : '' ?>>Pagos de Paseos</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">Desde</label>
          <input type="date" name="desde" class="form-control" value="<?= h($desde) ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">Hasta</label>
          <input type="date" name="hasta" class="form-control" value="<?= h($hasta) ?>">
        </div>

        <div class="col-md-1">
          <button class="btn btn-success w-100" title="Aplicar filtros">
            <i class="fas fa-filter"></i>
          </button>
        </div>
      </form>
    </div>

    <!-- EXPORT + DEBUG abajo del filtro (igual a Usuarios) -->
     <div class="export-buttons mb-3">
                <a class="btn btn-excel" href="<?= BASE_URL; ?>/public/api/suscripcion/exportSuscripcion.php">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>


    <!-- =========================
         SECCIÓN SUSCRIPCIONES
         ========================= -->
    <?php if ($tab === 'sus'): ?>
      <div class="section-card mb-3">
        <div class="section-header d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <i class="fas fa-crown me-2"></i>
            <span>Ingresos de la APP (Suscripciones)</span>
          </div>
          <span class="badge bg-secondary"><?= (int)$totSus['cant_total'] ?> registro(s)</span>
        </div>

        <div class="section-body">
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <div class="stat-card">
                <i class="fas fa-sack-dollar text-success fa-2x mb-2"></i>
                <h5>₲<?= formato_guarani((float)$totSus['total_ingreso_app']); ?></h5>
                <p>Ingreso total APP (solo activas)</p>
              </div>
            </div>

            <div class="col-md-4">
              <div class="stat-card">
                <i class="fas fa-circle-check text-primary fa-2x mb-2"></i>
                <h5><?= (int)$totSus['cant_activas']; ?></h5>
                <p>Suscripciones activas</p>
              </div>
            </div>

            <div class="col-md-4">
              <div class="stat-card">
                <i class="fas fa-hourglass-half text-warning fa-2x mb-2"></i>
                <h5><?= (int)$totSus['cant_pendientes']; ?></h5>
                <p>Suscripciones pendientes</p>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table text-center align-middle table-hover" id="tablaSuscripciones">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Fecha</th>
                  <th>Paseador</th>
                  <th>Plan</th>
                  <th>Monto</th>
                  <th>Estado</th>
                  <th>Inicio</th>
                  <th>Fin</th>
                  <th>Referencia</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($suscripciones)): ?>
                  <tr><td colspan="9" class="text-muted py-3">Sin registros</td></tr>
                <?php else: ?>
                  <?php foreach ($suscripciones as $s): ?>
                    <?php [$cls,$txt,$ico] = badgeSus($s['estado'] ?? ''); ?>
                    <tr>
                      <td><strong>#<?= (int)($s['id'] ?? 0) ?></strong></td>
                      <td><?= !empty($s['pagado_en']) ? date('d/m/Y H:i', strtotime((string)$s['pagado_en'])) : '-' ?></td>
                      <td><?= h($s['paseador_nombre'] ?? '-') ?></td>
                      <td><?= h($s['plan'] ?? '-') ?></td>
                      <td><strong>₲<?= formato_guarani((float)($s['monto'] ?? 0)) ?></strong></td>
                      <td>
                        <span class="badge <?= h($cls) ?> badge-soft">
                          <i class="fa-solid <?= h($ico) ?>"></i> <?= h($txt) ?>
                        </span>
                      </td>
                      <td><?= !empty($s['inicio']) ? date('d/m/Y', strtotime((string)$s['inicio'])) : '-' ?></td>
                      <td><?= !empty($s['fin']) ? date('d/m/Y', strtotime((string)$s['fin'])) : '-' ?></td>
                      <td><?= h($s['referencia'] ?? '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <p class="text-muted small mt-2 mb-0">
            Otros estados: vencidas (<?= (int)$totSus['cant_vencidas'] ?>),
            rechazadas (<?= (int)$totSus['cant_rechazadas'] ?>),
            canceladas (<?= (int)$totSus['cant_canceladas'] ?>).
          </p>
        </div>
      </div>
    <?php endif; ?>

    <!-- =========================
         SECCIÓN PAGOS DE PASEOS
         ========================= -->
    <?php if ($tab === 'paseos'): ?>
      <div class="section-card mb-3">
        <div class="section-header d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <i class="fas fa-dog me-2"></i>
            <span>Pagos de paseos (Dueño → Paseador)</span>
          </div>
          <span class="badge bg-secondary"><?= (int)$totPag['cant_total'] ?> registro(s)</span>
        </div>

        <div class="section-body">
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <div class="stat-card">
                <i class="fas fa-money-bill-wave text-primary fa-2x mb-2"></i>
                <h5>₲<?= formato_guarani((float)$totPag['total_cobrado_duenos']); ?></h5>
                <p>Total cobrado a dueños (confirmados)</p>
              </div>
            </div>

            <div class="col-md-4">
              <div class="stat-card">
                <i class="fas fa-user-tie text-success fa-2x mb-2"></i>
                <h5>₲<?= formato_guarani((float)$totPag['total_paseadores']); ?></h5>
                <p>Total destinado a paseadores</p>
              </div>
            </div>

            <div class="col-md-4">
              <div class="stat-card">
                <i class="fas fa-triangle-exclamation text-danger fa-2x mb-2"></i>
                <h5>₲<?= formato_guarani((float)$totPag['monto_pendiente']); ?></h5>
                <p>Monto pendiente (no confirmado)</p>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table text-center align-middle table-hover" id="tablaPagos">
              <thead>
                <tr>
                  <th>ID Pago</th>
                  <th>ID Paseo</th>
                  <th>Fecha</th>
                  <th>Dueño</th>
                  <th>Paseador</th>
                  <th>Método</th>
                  <th>Monto</th>
                  <th>Estado</th>
                  <th>Confirmado</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($pagosPaseos)): ?>
                  <tr><td colspan="9" class="text-muted py-3">Sin registros</td></tr>
                <?php else: ?>
                  <?php foreach ($pagosPaseos as $p): ?>
                    <?php
                      $ok = es_pago_confirmado($p['estado'] ?? '');
                      [$cls,$txt,$ico] = badgePago($p['estado'] ?? '');
                    ?>
                    <tr>
                      <td><strong>#<?= (int)($p['pago_id'] ?? 0) ?></strong></td>
                      <td>#<?= (int)($p['paseo_id'] ?? 0) ?></td>
                      <td><?= !empty($p['pagado_en']) ? date('d/m/Y H:i', strtotime((string)$p['pagado_en'])) : '-' ?></td>
                      <td><?= h($p['dueno_nombre'] ?? '-') ?></td>
                      <td><?= h($p['paseador_nombre'] ?? '-') ?></td>
                      <td><?= h($p['metodo'] ?? '-') ?></td>
                      <td><strong>₲<?= formato_guarani((float)($p['monto'] ?? 0)) ?></strong></td>
                      <td>
                        <span class="badge <?= h($cls) ?> badge-soft">
                          <i class="fa-solid <?= h($ico) ?>"></i> <?= h($txt) ?>
                        </span>
                      </td>
                      <td>
                        <?php if ($ok): ?>
                          <span class="badge bg-success">Sí</span>
                        <?php else: ?>
                          <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="alert alert-info mt-3 mb-0">
            <b>Nota:</b> Los pagos de paseos son flujo <b>Dueño → Paseador</b>.
            Los ingresos de la aplicación provienen de <b>Suscripciones</b>.
          </div>
        </div>
      </div>
    <?php endif; ?>

    <footer class="mt-3">
      <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
    </footer>

  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const btnToggle = document.getElementById('btnSidebarToggle');

    if (btnToggle && sidebar) {
      btnToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
      });
    }
  });

  /* Tab: al cambiar sección, submit para refrescar tablas */
  const filterTab = document.getElementById('filterTab');
  if (filterTab) {
    filterTab.addEventListener('change', () => {
      filterTab.closest('form')?.submit();
    });
  }

  /* Buscar: filtra la tabla visible */
  const searchInput = document.getElementById('searchInput');

  function getActiveTable() {
    const current = (filterTab?.value || 'sus');
    return document.getElementById(current === 'paseos' ? 'tablaPagos' : 'tablaSuscripciones');
  }

  function aplicarBusqueda() {
    const q = (searchInput?.value || '').toLowerCase().trim();
    const table = getActiveTable();
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(r => {
      const text = r.textContent.toLowerCase();
      r.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', aplicarBusqueda);
  }
</script>

</body>
</html>
