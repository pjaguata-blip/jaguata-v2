<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;
use Jaguata\Services\DatabaseService;

AppConfig::init();

$RUTA_FORM = AppConfig::getBaseUrl() . '/public/recuperar_password.php';
$GENERIC   = 'Si el correo existe, te enviamos un enlace para restablecer tu contraseña.';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $RUTA_FORM);
    exit;
}

// CSRF
$csrfPost = $_POST['csrf_token'] ?? '';
if (!Validaciones::verificarCSRF($csrfPost)) {
    Session::setError('Token inválido. Recargá la página e intentá de nuevo.');
    header('Location: ' . $RUTA_FORM);
    exit;
}

$email = trim($_POST['email'] ?? '');
$emailCheck = Validaciones::validarEmail($email);
if (!$emailCheck['valido']) {
    Session::setError($emailCheck['mensaje']);
    header('Location: ' . $RUTA_FORM);
    exit;
}

try {
    $usuarioModel = new Usuario();
    $u = $usuarioModel->getByEmail($email);

    // Mensaje genérico siempre (no revelar si existe o no)
    if (!$u) {
        Session::setSuccess($GENERIC);
        header('Location: ' . $RUTA_FORM);
        exit;
    }

    $usuarioId = (int)($u['usu_id'] ?? 0);
    if ($usuarioId <= 0) {
        Session::setSuccess($GENERIC);
        header('Location: ' . $RUTA_FORM);
        exit;
    }

    $db = DatabaseService::getInstance()->getConnection();

    // Opcional: invalidar reseteos previos no usados
    $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE usuario_id = :uid AND used_at IS NULL")
        ->execute([':uid' => $usuarioId]);

    // Token + hash
    $token   = bin2hex(random_bytes(32)); // 64 chars
    $hash    = password_hash($token, PASSWORD_DEFAULT);
    $expires = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

    $stmt = $db->prepare("
        INSERT INTO password_resets (usuario_id, token_hash, expires_at)
        VALUES (:uid, :hash, :exp)
    ");
    $stmt->execute([
        ':uid'  => $usuarioId,
        ':hash' => $hash,
        ':exp'  => $expires,
    ]);

    $resetId = (int)$db->lastInsertId();

    // Link con ID + token (eficiente para validar sin recorrer toda la tabla)
    $link = AppConfig::getBaseUrl() . "/public/restablecer_password.php?rid=" . urlencode((string)$resetId) . "&token=" . urlencode($token);

    // ✅ Enviar email (mail() puede fallar en XAMPP; ideal PHPMailer/SMTP)
    $subject = "Restablecer contraseña - Jaguata";
    $message = "Hola,\n\nIngresá al siguiente enlace para restablecer tu contraseña:\n$link\n\nEste enlace vence en 30 minutos.\n\nSi no fuiste vos, ignorá este mensaje.";
    @mail($email, $subject, $message);

    Session::setSuccess($GENERIC);
    header('Location: ' . $RUTA_FORM);
    exit;
} catch (Throwable $e) {
    error_log('Reset error: ' . $e->getMessage());
    Session::setSuccess($GENERIC);
    header('Location: ' . $RUTA_FORM);
    exit;
}
