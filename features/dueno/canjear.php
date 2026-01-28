<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json; charset=UTF-8');

$ROOT = __DIR__ . '/../../';

require_once $ROOT . 'src/Config/AppConfig.php';
require_once $ROOT . 'src/Helpers/Session.php';
require_once $ROOT . 'src/Services/DatabaseService.php';

require_once $ROOT . 'src/Models/Recompensa.php';
require_once $ROOT . 'src/Controllers/CanjeController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\CanjeController;
use Jaguata\Helpers\Session;

AppConfig::init();

if (!Session::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado (sin sesión).']);
    exit;
}

/* Rol seguro */
$rolTmp = method_exists(Session::class, 'getUsuarioRolSeguro')
    ? Session::getUsuarioRolSeguro()
    : (Session::getUsuarioRol() ?? (Session::get('rol') ?? ''));

$rol = strtolower(trim((string)$rolTmp));
if ($rol !== 'dueno') {
    echo json_encode(['success' => false, 'error' => 'No autorizado (rol inválido).', 'rol' => $rol]);
    exit;
}

$estado = strtolower(trim((string)(Session::getUsuarioEstado() ?? '')));
if ($estado !== 'aprobado') {
    echo json_encode(['success' => false, 'error' => 'Cuenta no aprobada.']);
    exit;
}

$duenoId = (int)(Session::getUsuarioId() ?? 0);
if ($duenoId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Sesión inválida (sin id).']);
    exit;
}

/* CSRF */
$csrf  = (string)($_POST['csrf'] ?? '');
$token = (string)($_SESSION['csrf_token'] ?? '');
if ($csrf === '' || $token === '' || !hash_equals($token, $csrf)) {
    echo json_encode(['success' => false, 'error' => 'CSRF inválido.']);
    exit;
}

$recompensaId = (int)($_POST['recompensa_id'] ?? 0);
if ($recompensaId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Recompensa inválida.']);
    exit;
}

$ctrl = new CanjeController();
echo json_encode($ctrl->canjear($duenoId, $recompensaId));
