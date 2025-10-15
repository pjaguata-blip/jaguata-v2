<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Services/PagoViewService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Session;
use Jaguata\Services\PagoViewService;
use Dompdf\Dompdf;

AppConfig::init();
(new AuthController())->checkRole('dueno');

$pagoId = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;
if ($pagoId <= 0) {
  http_response_code(400);
  exit('Falta pago_id');
}

$svc = new PagoViewService();
$d = $svc->getPagoFull($pagoId);
if (!$d) {
  http_response_code(404);
  exit('Pago no encontrado');
}
if ((int)$d['dueno_id'] !== (int)Session::getUsuarioId()) {
  http_response_code(403);
  exit('No autorizado');
}

$html = '
<html><head><meta charset="UTF-8"><style>
  body{font-family: DejaVu Sans, Arial, sans-serif; font-size:12px;}
  h1{font-size:16px;margin:0 0 10px;}
  .row{display:flex;flex-wrap:wrap;}
  .col{width:50%;padding:4px 8px;box-sizing:border-box;}
  .badge{display:inline-block;padding:2px 6px;border:1px solid #333;border-radius:4px;}
  .section{border:1px solid #ddd;padding:10px;border-radius:8px;margin-bottom:10px;}
</style></head><body>
<h1>Comprobante de pago</h1>
<div class="section">
  <div class="row">
    <div class="col"><strong>Dueño:</strong> ' . htmlspecialchars($d['dueno_nombre']) . '</div>
    <div class="col"><strong>Paseador:</strong> ' . htmlspecialchars($d['paseador_nombre']) . '</div>
    <div class="col"><strong>Fecha del paseo:</strong> ' . ($d['inicio'] ? date('d/m/Y H:i', strtotime($d['inicio'])) : '-') . '</div>
    <div class="col"><strong>Duración:</strong> ' . ($d['duracion_min'] ? $d['duracion_min'] . ' min' : '—') . '</div>
    <div class="col"><strong>Método:</strong> ' . ucfirst($d['metodo']) . '</div>
    <div class="col"><strong>Monto:</strong> ₲ ' . number_format((float)$d['monto'], 0, ',', '.') . '</div>';
if ($d['metodo'] === 'transferencia') {
  $alias = $d['alias_transferencia'] ?: ($d['alias_cuenta'] ?: $d['cuenta_numero']);
  $banco = $d['banco_nombre'] ?: '—';
  $html .= '
    <div class="col"><strong>Banco:</strong> ' . htmlspecialchars($banco) . '</div>
    <div class="col"><strong>Alias/Cuenta:</strong> ' . htmlspecialchars($alias ?: '—') . '</div>';
}
$html .= '
    <div class="col"><strong>Estado:</strong> <span class="badge">' . ucfirst($d['estado_pago']) . '</span></div>
    <div class="col"><strong>Fecha del pago:</strong> ' . ($d['pagado_en'] ? date('d/m/Y H:i', strtotime($d['pagado_en'])) : '-') . '</div>';
if ($d['referencia']) {
  $html .= '<div class="col" style="width:100%"><strong>Referencia:</strong> ' . htmlspecialchars($d['referencia']) . '</div>';
}
$html .= '
  </div>
</div>
<small>Documento emitido por Jaguata • ' . date('d/m/Y H:i') . '</small>
</body></html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('comprobante_pago_' . $pagoId . '.pdf', ['Attachment' => true]);
