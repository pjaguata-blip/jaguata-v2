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
$inicioUrl = BASE_URL;
$panelUrl  = null;
if (Session::isLoggedIn()) {
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
    $honeypot = $_POST['website'] ?? '';
    $csrf     = $_POST['csrf_token'] ?? '';

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
        $last = (int) Session::get('contact_last_ts', 0);
        if ($last && (time() - $last) < $COOLDOWN_SECONDS) {
            $rest = $COOLDOWN_SECONDS - (time() - $last);
            Session::setError("Por favor, aguardá {$rest}s para volver a enviar.");
        } else {
            $nombre  = Validaciones::sanitizarString($old['nombre'] ?? '');
            $email   = Validaciones::sanitizarString($old['email'] ?? '');
            $mensaje = Validaciones::sanitizarString($old['mensaje'] ?? '');

            $errors = [];
            if ($nombre === '' || $email === '' || $mensaje === '') {
                $errors[] = 'Todos los campos son obligatorios.';
            }
            if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
                $errors[] = 'El nombre debe tener entre 2 y 100 caracteres.';
            }
            if (!Validaciones::validarEmail($email)) {
                $errors[] = 'El correo no es válido.';
            }
            if (mb_strlen($mensaje) < 10 || mb_strlen($mensaje) > 2000) {
                $errors[] = 'El mensaje debe tener entre 10 y 2000 caracteres.';
            }

            if (!empty($errors)) {
                Session::setError(implode('<br>', $errors));
            } else {
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

    header('Location: ' . $RUTA_SELF);
    exit;
}

// ====== VISTA ======
include __DIR__ . '/../src/Templates/Header.php';
?>

<!-- HERO -->
<section class="hero-section text-white text-center py-5" style="background:linear-gradient(135deg,#3c6255,#20c997);">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3">Contáctanos</h1>
        <p class="lead mb-4">
            Si tenés consultas, sugerencias o querés formar parte de Jaguata, completá el formulario y te responderemos pronto 🐾
        </p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="<?= htmlspecialchars($inicioUrl) ?>" class="btn btn-outline-light">
                <i class="fa-solid fa-house me-1"></i> Inicio
            </a>
            <?php if ($panelUrl): ?>
                <a href="<?= htmlspecialchars($panelUrl) ?>" class="btn btn-warning text-dark">
                    <i class="fa-solid fa-gauge-high me-1"></i> Ir al Panel
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FORMULARIO -->
<section class="py-5 bg-light">
    <div class="container">
        <?= $error   ? Funciones::generarAlerta('error', $error)     : '' ?>
        <?= $success ? Funciones::generarAlerta('success', $success) : '' ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h3 class="fw-semibold text-success mb-4 text-center">
                            <i class="fa-regular fa-envelope me-2"></i>Formulario de Contacto
                        </h3>

                        <form method="post" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= Validaciones::generarCSRF(); ?>">

                            <!-- Honeypot -->
                            <div style="position:absolute;left:-9999px;top:-9999px;">
                                <label>Si ves este campo, no lo completes:
                                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                                </label>
                            </div>

                            <div class="mb-3">
                                <label for="nombre" class="form-label fw-semibold">Nombre completo</label>
                                <input type="text" id="nombre" name="nombre"
                                    class="form-control shadow-sm"
                                    minlength="2" maxlength="100" required
                                    value="<?= htmlspecialchars($old['nombre'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                                <input type="email" id="email" name="email"
                                    class="form-control shadow-sm"
                                    maxlength="100" required
                                    value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="mensaje" class="form-label fw-semibold">Mensaje</label>
                                <textarea id="mensaje" name="mensaje" rows="6"
                                    class="form-control shadow-sm" required minlength="10" maxlength="2000"><?= htmlspecialchars($old['mensaje'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fa-regular fa-paper-plane me-1"></i> Enviar mensaje
                                </button>
                                <a href="<?= htmlspecialchars($RUTA_SELF) ?>" class="btn btn-outline-secondary btn-lg">
                                    <i class="fa-solid fa-eraser me-1"></i> Limpiar
                                </a>
                            </div>
                        </form>

                        <p class="text-muted small text-center mt-3">
                            <i class="fa-regular fa-clock me-1"></i>
                            Tiempo mínimo entre envíos: <?= (int)$COOLDOWN_SECONDS ?> segundos.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="cta-section py-5" style="background:#f6f9f7;">
    <div class="container text-center">
        <h2 class="fw-bold text-success mb-2">¿Querés unirte a Jaguata?</h2>
        <p class="text-muted mb-4">Registrate y empezá a disfrutar de nuestros servicios para mascotas.</p>
        <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-warning btn-lg me-2 shadow-sm">
            <i class="fa-solid fa-paw me-1"></i> Crear cuenta
        </a>
        <a href="<?= BASE_URL; ?>/sobre_nosotros.php" class="btn btn-outline-success btn-lg shadow-sm">
            <i class="fa-regular fa-circle-question me-1"></i> Saber más
        </a>
    </div>
</section>


<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>