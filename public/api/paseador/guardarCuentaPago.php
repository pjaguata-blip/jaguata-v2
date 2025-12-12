<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
        exit;
    }

    if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
        echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
        exit;
    }

    $usuarioId = (int)(Session::getUsuarioId() ?? 0);
    if ($usuarioId <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'Sesión inválida']);
        exit;
    }

    $raw  = (string)file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        echo json_encode(['ok' => false, 'mensaje' => 'JSON inválido']);
        exit;
    }

    $banco  = trim((string)($data['banco']  ?? ''));
    $alias  = trim((string)($data['alias']  ?? ''));
    $cuenta = trim((string)($data['cuenta'] ?? ''));

    $db = $GLOBALS['db']; // ✅ tu proyecto usa conexión global

    // ⚠️ Si tu tabla se llama distinto, cambiá acá:
    $tabla = 'cuentas_pago';

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
            ':banco' => $banco,
            ':alias' => $alias,
            ':cuenta' => $cuenta,
            ':uid'   => $usuarioId
        ]);
    } else {
        $i = $db->prepare("
            INSERT INTO {$tabla} (usuario_id, banco, alias, cuenta, created_at, updated_at)
            VALUES (:uid, :banco, :alias, :cuenta, NOW(), NOW())
        ");
        $i->execute([
            ':uid'   => $usuarioId,
            ':banco' => $banco,
            ':alias' => $alias,
            ':cuenta' => $cuenta
        ]);
    }

    echo json_encode(['ok' => true, 'mensaje' => 'Datos de cuenta guardados correctamente.']);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
}
