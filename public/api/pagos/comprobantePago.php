<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';
require_once __DIR__ . '/../../../src/Services/PagoViewService.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Session;
use Jaguata\Services\PagoViewService;
use Dompdf\Dompdf;

AppConfig::init();
(new AuthController())->checkRole('paseador');

$pagoId = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;
if ($pagoId <= 0) {
    http_response_code(400);
    exit('Falta pago_id');
}

$svc = new PagoViewService();
$d   = $svc->getPagoFull($pagoId);

if (!$d) {
    http_response_code(404);
    exit('Pago no encontrado');
}

// 🔒 Solo el paseador dueño del pago puede verlo
if ((int)($d['paseador_id'] ?? 0) !== (int)Session::getUsuarioId()) {
    http_response_code(403);
    exit('No autorizado');
}

// Campos con fallback por si falta algo
$duenoNombre    = $d['dueno_nombre']    ?? $d['dueno_email']    ?? '—';
$paseadorNombre = $d['paseador_nombre'] ?? '—';
$metodo         = $d['metodo']          ?? '—';
$monto          = (float)($d['monto'] ?? $d['precio_total'] ?? 0);
$estadoPago     = ucfirst($d['estado_pago'] ?? 'pendiente');
$fechaPaseo     = $d['inicio']    ? date('d/m/Y H:i', strtotime($d['inicio']))    : '—';
$fechaPago      = $d['pagado_en'] ? date('d/m/Y H:i', strtotime($d['pagado_en'])) : '—';
$duracionMin    = $d['duracion_min'] ?? $d['duracion'] ?? null;
$referencia     = $d['referencia'] ?? '';

$html = '
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
  h1 { font-size: 18px; margin: 0 0 12px; color: #3c6255; }
  .section { border: 1px solid #ddd; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
  .row { display: flex; flex-wrap: wrap; }
  .col { width: 50%; padding: 4px 8px; box-sizing: border-box; }
  .label { font-weight: bold; }
  .badge { display:inline-block; padding: 2px 6px; border-radius: 4px; border: 1px solid #3c6255; color:#3c6255; }
  small { color: #555; }
</style>
</head>
<body>

<h1>Comprobante de pago</h1>

<div class="section">
  <div class="row">
    <div class="col"><span class="label">Dueño:</span> ' . htmlspecialchars($duenoNombre) . '</div>
    <div class="col"><span class="label">Paseador:</span> ' . htmlspecialchars($paseadorNombre) . '</div>
    <div class="col"><span class="label">Fecha del paseo:</span> ' . $fechaPaseo . '</div>
    <div class="col"><span class="label">Duración:</span> ' . ($duracionMin ? $duracionMin . ' min' : '—') . '</div>
    <div class="col"><span class="label">Método:</span> ' . htmlspecialchars(ucfirst($metodo)) . '</div>
    <div class="col"><span class="label">Monto:</span> ₲ ' . number_format($monto, 0, ',', '.') . '</div>';

if (($d['metodo'] ?? '') === 'transferencia') {
    $alias = $d['alias_transferencia'] ?? $d['alias_cuenta'] ?? $d['cuenta_numero'] ?? '';
    $banco = $d['banco_nombre'] ?? '—';
    $html .= '
    <div class="col"><span class="label">Banco:</span> ' . htmlspecialchars($banco) . '</div>
    <div class="col"><span class="label">Alias/Cuenta:</span> ' . htmlspecialchars($alias ?: '—') . '</div>';
}

$html .= '
    <div class="col"><span class="label">Estado:</span> <span class="badge">' . htmlspecialchars($estadoPago) . '</span></div>
    <div class="col"><span class="label">Fecha del pago:</span> ' . $fechaPago . '</div>';

if ($referencia) {
    $html .= '
    <div class="col" style="width:100%"><span class="label">Referencia:</span> ' . htmlspecialchars($referencia) . '</div>';
}

$html .= '
  </div>
</div>

<small>Documento emitido por Jaguata • ' . date('d/m/Y H:i') . '</small>

</body>
</html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 🔽 Abrir en nueva pestaña (el navegador permite descargar)
$dompdf->stream('comprobante_pago_' . $pagoId . '.pdf', ['Attachment' => false]);
