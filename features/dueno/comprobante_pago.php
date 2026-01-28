<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Services/DatabaseService.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Services\DatabaseService;
use Jaguata\Helpers\Session;

AppConfig::init();
(new AuthController())->checkRole('dueno');

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function money($v): string {
    return number_format((float)$v, 0, ',', '.');
}

$pagoId = (int)($_GET['pago_id'] ?? 0);
if ($pagoId <= 0) {
    http_response_code(400);
    exit('Falta pago_id');
}

$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    http_response_code(401);
    exit('No autenticado');
}

$db = DatabaseService::getInstance()->getConnection();
$sql = "
SELECT
  pg.id,
  pg.paseo_id,
  pg.monto,
  pg.metodo,
  pg.estado,
  pg.banco,
  pg.cuenta,
  pg.alias,
  pg.referencia,
  pg.observacion,
  pg.comprobante,
  pg.created_at AS fecha_pago,

  p.inicio,
  p.duracion AS duracion_min,
  p.paseador_id,

  u2.nombre AS paseador_nombre,
  u1.nombre AS dueno_nombre,

  m.dueno_id,
  m.nombre AS mascota_nombre,
  m2.nombre AS mascota2_nombre

FROM pagos pg
INNER JOIN paseos p ON p.paseo_id = pg.paseo_id
INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
LEFT JOIN mascotas m2 ON m2.mascota_id = p.mascota_id_2

LEFT JOIN usuarios u1 ON u1.usu_id = m.dueno_id
LEFT JOIN usuarios u2 ON u2.usu_id = p.paseador_id

WHERE pg.id = :id
LIMIT 1
";

$st = $db->prepare($sql);
$st->execute([':id' => $pagoId]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('Pago no encontrado');
}

if ((int)$row['dueno_id'] !== $duenoId) {
    http_response_code(403);
    exit('No autorizado');
}

$estado = strtoupper(trim((string)($row['estado'] ?? 'PENDIENTE')));
$badge = [
    'CONFIRMADO' => 'success',
    'PENDIENTE'  => 'warning',
    'RECHAZADO'  => 'danger',
    'CANCELADO'  => 'danger',
][$estado] ?? 'secondary';

$fechaPago = !empty($row['fecha_pago']) ? date('d/m/Y H:i', strtotime((string)$row['fecha_pago'])) : '—';
$fechaPaseo = !empty($row['inicio']) ? date('d/m/Y H:i', strtotime((string)$row['inicio'])) : '—';

$mascotas = trim((string)($row['mascota_nombre'] ?? ''));
$m2 = trim((string)($row['mascota2_nombre'] ?? ''));
if ($m2 !== '') $mascotas .= ' + ' . $m2;
if ($mascotas === '') $mascotas = '—';

$apiComprobanteUrl = BASE_URL . "/public/api/pagos/comprobantePago.php?pago_id=" . (int)$pagoId;

/* Detectar tipo por extensión (simple) */
$comp = (string)($row['comprobante'] ?? '');
$ext = strtolower(pathinfo($comp, PATHINFO_EXTENSION));
$esPdf = ($ext === 'pdf');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Comprobante de pago #<?= (int)$pagoId ?> - Jaguata</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

  <style>
    main.main-content{
      margin-left: var(--sidebar-w);
      width: calc(100% - var(--sidebar-w));
      min-height: 100vh;
      padding: 24px;
      box-sizing: border-box;
    }
    @media (max-width: 768px){
      main.main-content{
        margin-left: 0 !important;
        width: 100% !important;
        margin-top: 0 !important;
        padding: calc(16px + var(--topbar-h)) 16px 16px !important;
      }
    }
    .viewer{
      width:100%;
      height: 70vh;
      border:1px solid #dfe3e8;
      border-radius: 14px;
      overflow:hidden;
      background:#fff;
    }
    .viewer iframe{ width:100%; height:100%; border:0; }
    .viewer img{ width:100%; height:100%; object-fit:contain; display:block; }
  </style>
</head>
<body>

  <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

  <main class="main-content">
    <div class="py-2">

      <div class="header-box header-pagos mb-3 d-flex justify-content-between align-items-center">
        <div>
          <h1 class="fw-bold mb-1">
            <i class="fas fa-receipt me-2"></i>Comprobante de pago #<?= (int)$pagoId ?>
          </h1>
          <p class="mb-0">Vista “bonita” + archivo adjunto (PDF/imagen).</p>
        </div>
        <div class="d-flex gap-2">
          <a href="MisPagos.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver
          </a>
          <a class="btn btn-light btn-sm fw-semibold" target="_blank" rel="noopener"
             href="<?= h($apiComprobanteUrl) ?>">
            <i class="fas fa-eye me-1"></i> Abrir archivo
          </a>
          <a class="btn btn-success btn-sm fw-semibold"
             href="<?= h($apiComprobanteUrl) ?>" download>
            <i class="fas fa-download me-1"></i> Descargar
          </a>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-lg-5">
          <div class="section-card h-100">
            <div class="section-header">
              <i class="fas fa-circle-info me-2"></i> Datos del pago
            </div>
            <div class="section-body">
              <div class="row g-2">
                <div class="col-6">
                  <div class="text-muted small">Estado</div>
                  <div><span class="badge bg-<?= $badge ?>"><?= h($estado) ?></span></div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Monto</div>
                  <div class="fw-bold text-success">₲<?= money((float)$row['monto']) ?></div>
                </div>

                <div class="col-6">
                  <div class="text-muted small">Método</div>
                  <div><?= h(ucfirst((string)($row['metodo'] ?? '—'))) ?></div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Fecha pago</div>
                  <div><?= h($fechaPago) ?></div>
                </div>

                <div class="col-12"><hr class="my-2"></div>

                <div class="col-6">
                  <div class="text-muted small">Paseador</div>
                  <div><?= h($row['paseador_nombre'] ?? '—') ?></div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Fecha paseo</div>
                  <div><?= h($fechaPaseo) ?></div>
                </div>

                <div class="col-12">
                  <div class="text-muted small">Mascotas</div>
                  <div><?= h($mascotas) ?></div>
                </div>

                <?php if (strtolower((string)($row['metodo'] ?? '')) === 'transferencia'): ?>
                  <div class="col-6">
                    <div class="text-muted small">Banco</div>
                    <div><?= h($row['banco'] ?? '—') ?></div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted small">Alias/Cuenta</div>
                    <div><?= h($row['alias'] ?: ($row['cuenta'] ?? '—')) ?></div>
                  </div>
                  <div class="col-12">
                    <div class="text-muted small">Referencia</div>
                    <div><?= h($row['referencia'] ?? '—') ?></div>
                  </div>
                <?php endif; ?>

                <div class="col-12">
                  <div class="text-muted small">Observación</div>
                  <div><?= h($row['observacion'] ?? '—') ?></div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="section-card h-100">
            <div class="section-header">
              <i class="fas fa-file me-2"></i> Archivo adjunto
            </div>
            <div class="section-body">
              <div class="viewer">
                <?php if ($esPdf): ?>
                  <iframe src="<?= h($apiComprobanteUrl) ?>"></iframe>
                <?php else: ?>
                  <img src="<?= h($apiComprobanteUrl) ?>" alt="Comprobante">
                <?php endif; ?>
              </div>
              <div class="text-muted small mt-2">
                Si el navegador bloquea el PDF, usá “Abrir archivo”.
              </div>
            </div>
          </div>
        </div>
      </div>

      <footer class="mt-3 text-center text-muted small">
        © <?= date('Y') ?> Jaguata — Comprobante de pago
      </footer>

    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
