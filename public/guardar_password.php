<?php


declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Services\DatabaseService;
use PDO;

AppConfig::init();

$RUTA_RECUPERAR = BASE_URL . '/public/recuperar_password.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $RUTA_RECUPERAR);
    exit;
}

/* CSRF */
$csrfPost = $_POST['csrf_token'] ?? '';
if (!Validaciones::verificarCSRF($csrfPost)) {
    Session::setError('Token inválido. Recargá la página e intentá de nuevo.');
    header('Location: ' . $RUTA_RECUPERAR);
    exit;
}

$rid   = (int)($_POST['rid'] ?? 0);
$token = trim((string)($_POST['token'] ?? ''));
$pass  = (string)($_POST['password'] ?? '');
$pass2 = (string)($_POST['confirm_password'] ?? '');

if ($rid <= 0 || $token === '') {
    Session::setError('Enlace inválido.');
    header('Location: ' . $RUTA_RECUPERAR);
    exit;
}

$backToForm = BASE_URL . '/public/restablecer_password.php?rid=' . urlencode((string)$rid) . '&token=' . urlencode($token);

if ($pass !== $pass2) {
    Session::setError('Las contraseñas no coinciden.');
    header('Location: ' . $backToForm);
    exit;
}

$passCheck = Validaciones::validarPassword($pass);
if (!$passCheck['valido']) {
    Session::setError($passCheck['mensaje']);
    header('Location: ' . $backToForm);
    exit;
}

try {
    $db = DatabaseService::getInstance()->getConnection();

    /* Traer reset por ID */
    $stmt = $db->prepare("
        SELECT id, usuario_id, token_hash, expires_at, used_at
        FROM password_resets
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $rid]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        Session::setError('El enlace es inválido.');
        header('Location: ' . $RUTA_RECUPERAR);
        exit;
    }

    if (!empty($reset['used_at'])) {
        Session::setError('Este enlace ya fue utilizado. Solicitá uno nuevo.');
        header('Location: ' . $RUTA_RECUPERAR);
        exit;
    }

    $expiresAt = strtotime((string)$reset['expires_at']);
    if ($expiresAt !== false && $expiresAt < time()) {
        Session::setError('El enlace expiró. Solicitá uno nuevo.');
        header('Location: ' . $RUTA_RECUPERAR);
        exit;
    }

    if (!password_verify($token, (string)$reset['token_hash'])) {
        Session::setError('El enlace es inválido o fue alterado.');
        header('Location: ' . $RUTA_RECUPERAR);
        exit;
    }

    $usuarioId = (int)($reset['usuario_id'] ?? 0);
    if ($usuarioId <= 0) {
        Session::setError('El enlace es inválido.');
        header('Location: ' . $RUTA_RECUPERAR);
        exit;
    }

    /* Actualizar contraseña (columna correcta: pass, PK: usu_id) */
    $hashPass = password_hash($pass, PASSWORD_DEFAULT);
    $up = $db->prepare("UPDATE usuarios SET pass = :p WHERE usu_id = :id");
    $up->execute([
        ':p'  => $hashPass,
        ':id' => $usuarioId,
    ]);

    /* Marcar reset como usado */
    $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id")
        ->execute([':id' => $rid]);

    Session::setSuccess('Contraseña actualizada ✅ Ya podés iniciar sesión.');
    header('Location: ' . BASE_URL . '/login.php');
    exit;

} catch (Throwable $e) {
    error_log('Guardar password error: ' . $e->getMessage());
    Session::setError('Ocurrió un error. Intentá de nuevo.');
    header('Location: ' . $RUTA_RECUPERAR);
    exit;
}
