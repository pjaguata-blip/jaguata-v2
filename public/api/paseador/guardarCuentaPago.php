<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';
require_once __DIR__ . '/../../../src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Services\DatabaseService;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
        exit;
    }

    if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
        exit;
    }

    $usuarioId = (int)(Session::getUsuarioId() ?? 0);
    if ($usuarioId <= 0) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'mensaje' => 'Sesión inválida']);
        exit;
    }

    $raw  = (string)file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'JSON inválido']);
        exit;
    }

    $banco  = trim((string)($data['banco']  ?? ''));
    $alias  = trim((string)($data['alias']  ?? ''));
    $cuenta = trim((string)($data['cuenta'] ?? ''));

    if ($banco === '' && $alias === '' && $cuenta === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'Completá al menos un dato (banco/alias/cuenta).']);
        exit;
    }

    $db = $GLOBALS['db'] ?? null;
    if (!$db instanceof PDO) {
        $db = DatabaseService::getInstance()->getConnection();
    }

    $tabla = 'datos_pago';

    // ¿Existe?
    $q = $db->prepare("SELECT id FROM {$tabla} WHERE usuario_id = :uid LIMIT 1");
    $q->execute([':uid' => $usuarioId]);
    $existe = $q->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        $u = $db->prepare("
            UPDATE {$tabla}
               SET banco = :banco,
                   alias = :alias,
                   cuenta = :cuenta,
                   updated_at = NOW()
             WHERE usuario_id = :uid
        ");
        $u->execute([
            ':banco'  => ($banco !== '' ? $banco : null),
            ':alias'  => ($alias !== '' ? $alias : null),
            ':cuenta' => ($cuenta !== '' ? $cuenta : null),
            ':uid'    => $usuarioId
        ]);
    } else {
        $i = $db->prepare("
            INSERT INTO {$tabla} (usuario_id, banco, alias, cuenta, created_at, updated_at)
            VALUES (:uid, :banco, :alias, :cuenta, NOW(), NOW())
        ");
        $i->execute([
            ':uid'    => $usuarioId,
            ':banco'  => ($banco !== '' ? $banco : null),
            ':alias'  => ($alias !== '' ? $alias : null),
            ':cuenta' => ($cuenta !== '' ? $cuenta : null),
        ]);
    }

    echo json_encode(['ok' => true, 'mensaje' => 'Datos de cuenta guardados correctamente.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
}
