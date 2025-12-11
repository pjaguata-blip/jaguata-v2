<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';
require_once __DIR__ . '/../../../src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Services\DatabaseService;

AppConfig::init();

header('Content-Type: application/json; charset=UTF-8');

// 🔒 Solo paseadores logueados
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
    http_response_code(401);
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'No autorizado.'
    ]);
    exit;
}

$usuarioId = (int)(Session::getUsuarioId() ?? 0);
if ($usuarioId <= 0) {
    http_response_code(401);
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Usuario no válido.'
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Datos inválidos.'
    ]);
    exit;
}

$banco  = trim((string)($data['banco']  ?? ''));
$alias  = trim((string)($data['alias']  ?? ''));
$cuenta = trim((string)($data['cuenta'] ?? ''));

// (Opcional) Validaciones mínimas
if ($banco === '' && $alias === '' && $cuenta === '') {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Completá al menos un dato de cuenta.'
    ]);
    exit;
}

try {
    $db = DatabaseService::getInstance()->getConnection();

    /* 
       ⚠️ IMPORTANTE:
       Ajustá el nombre de la tabla, la PK y las columnas según tu esquema real.

       - Tabla:      usuarios
       - PK:         usu_id
       - Columnas:   banco_pago, alias_pago, cuenta_pago

       Si en tu BD se llaman, por ejemplo:
       - banco, alias, nro_cuenta
       cambiá el UPDATE por esos nombres.
    */
    $sql = "
        UPDATE usuarios
        SET 
            banco_pago  = :banco,
            alias_pago  = :alias,
            cuenta_pago = :cuenta,
            updated_at  = NOW()
        WHERE usu_id = :id
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':banco'  => $banco !== ''  ? $banco  : null,
        ':alias'  => $alias !== ''  ? $alias  : null,
        ':cuenta' => $cuenta !== '' ? $cuenta : null,
        ':id'     => $usuarioId,
    ]);

    echo json_encode([
        'ok'      => true,
        'mensaje' => 'Datos de cuenta guardados correctamente.'
    ]);
    exit;
} catch (\Throwable $e) {
    error_log('Error guardarCuentaPago.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error interno al guardar los datos.'
    ]);
    exit;
}
