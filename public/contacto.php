<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Helpers\Funciones;

AppConfig::init();

$RUTA_SELF = BASE_URL . '/contacto.php';
$COOLDOWN_SECONDS = 60; // Throttle: 60s entre envíos

// ====== URLs de navegación ======
$inicioUrl = BASE_URL; // tu landing/home
$panelUrl  = null;
if (Session::isLoggedIn()) {
    // usa el helper seguro que ya tenés
    $rol = Session::getUsuarioRolSeguro();
    if ($rol) {
        $panelUrl = BASE_URL . "/features/{$rol}/Dashboard.php";
    }
}

// ====== LECTURA DE FLASHES / OLD ======
$error   = Session::getError();
$success = Session::getSuccess();
$old     = Session::get('contact_old', ['nombre' => '', 'email' => '', 'mensaje' => '']);
Session::set('contact_old', null);

// ====== POST ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot
    $honeypot = $_POST['website'] ?? '';
    $csrf     = $_POST['csrf_token'] ?? '';

    // Guardar old para repoblar si falla
    $old = [
        'nombre'  => trim($_POST['nombre'] ?? ''),
        'email'   => trim($_POST['email'] ?? ''),
        'mensaje' => trim($_POST['mensaje'] ?? ''),
    ];
    Session::set('contact_old', $old);

    if (!Validaciones::verificarCSRF($csrf)) {
        Session::setError('Token CSRF inválido. Actualizá la página e intentá de nuevo.');
    } elseif (!empty($honeypot)) {
        Session::setError('No se pudo procesar el formulario.');
    } else {
        // Throttle
        $last = (int) Session::get('contact_last_ts', 0);
        if ($last && (time() - $last) < $COOLDOWN_SECONDS) {
            $rest = $COOLDOWN_SECONDS - (time() - $last);
            Session::setError("Por favor, aguardá {$rest}s para volver a enviar.");
        } else {
            // Sanitizar + validar
            $nombre  = Validaciones::sanitizarString($old['nombre'] ?? '');
            $email   = Validaciones::sanitizarString($old['email'] ?? '');
            $mensaje = Validaciones::sanitizarString($old['mensaje'] ?? '');

            $errors = [];
            if ($nombre === '' || $email === '' || $mensaje === '') {
                $errors[] = 'Todos los campos son obligatorios.';
            }
            if ($nombre !== '' && (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100)) {
                $errors[] = 'El nombre debe tener entre 2 y 100 caracteres.';
            }
            if ($email !== '' && !Validaciones::validarEmail($email)) {
                $errors[] = 'El correo no es válido.';
            }
            if ($mensaje !== '' && (mb_strlen($mensaje) < 10 || mb_strlen($mensaje) > 2000)) {
                $errors[] = 'El mensaje debe tener entre 10 y 2000 caracteres.';
            }

            if (!empty($errors)) {
                Session::setError(implode('<br>', $errors));
            } else {
                // Email
                $to      = 'contacto@jaguata.com';
                $subject = "Nuevo mensaje de {$nombre}";
                $body    = "
                    <h2>Nuevo mensaje de contacto</h2>
                    <p><strong>Nombre:</strong> " . htmlspecialchars($nombre) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                    <hr>
                    <div style='white-space:pre-wrap;font-family:system-ui,Segoe UI,Arial,sans-serif;'>
                        " . nl2br(htmlspecialchars($mensaje)) . "
                    </div>
                ";

                $enviado = Funciones::enviarEmail($to, $subject, $body, $email);
                if ($enviado) {
                    Session::set('contact_old', null);
                    Session::set('contact_last_ts', time());
                    Session::setSuccess('¡Gracias! Tu mensaje fue enviado correctamente.');
                } else {
                    Session::setError('Ocurrió un problema al enviar el mensaje. Intentá de nuevo.');
                }
            }
        }
    }

    // PRG
    header('Location: ' . $RUTA_SELF);
    exit;
}

// ====== VISTA ======
include __DIR__ . '/../src/Templates/Header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">
            <i class="fa-regular fa-envelope me-2"></i> Contacto
        </h2>
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($inicioUrl) ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-house me-1"></i> Ir al inicio
            </a>
            <?php if ($panelUrl): ?>
                <a href="<?= htmlspecialchars($panelUrl) ?>" class="btn btn-outline-primary">
                    <i class="fa-solid fa-gauge-high me-1"></i> Panel principal
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertas -->
    <?= $error   ? Funciones::generarAlerta('error', $error)     : '' ?>
    <?= $success ? Funciones::generarAlerta('success', $success) : '' ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= Validaciones::generarCSRF(); ?>">

                <!-- Honeypot anti-spam -->
                <div style="position:absolute;left:-9999px;top:-9999px;">
                    <label>Si ves este campo, no lo completes:
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </label>
                </div>

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre completo</label>
                    <input
                        type="text"
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        required
                        minlength="2"
                        maxlength="100"
                        value="<?= htmlspecialchars($old['nombre'] ?? '') ?>">
                    <div class="form-text">Entre 2 y 100 caracteres.</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        required
                        maxlength="100"
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="mensaje" class="form-label">Mensaje</label>
                    <textarea
                        class="form-control"
                        id="mensaje"
                        name="mensaje"
                        rows="6"
                        required
                        minlength="10"
                        maxlength="2000"><?= htmlspecialchars($old['mensaje'] ?? '') ?></textarea>
                    <div class="form-text">Sé claro y conciso. Máx. 2000 caracteres.</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-regular fa-paper-plane me-1"></i> Enviar
                    </button>

                    <a href="<?= htmlspecialchars($RUTA_SELF) ?>" class="btn btn-outline-dark ms-auto">
                        <i class="fa-solid fa-eraser me-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="text--color #ffff mt-3 small">
        <i class="fa-regular fa-clock me-1"></i> Tiempo entre envíos: <?= (int)$COOLDOWN_SECONDS ?>s
    </div>
</div>

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>