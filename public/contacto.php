<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Helpers\Funciones;

AppConfig::init();

$RUTA_SELF = BASE_URL . '/contacto.php';
$COOLDOWN_SECONDS = 60;

// ====== URLs ======
$inicioUrl = BASE_URL;
$panelUrl  = null;

if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRolSeguro();
    if ($rol) {
        $panelUrl = BASE_URL . "/features/{$rol}/Dashboard.php";
    }
}

// ====== Flash ======
$error   = Session::getError();
$success = Session::getSuccess();

$old = Session::get('contact_old', [
    'nombre'  => '',
    'email'   => '',
    'mensaje' => ''
]);

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
        Session::setError('Token CSRF inválido. Actualizá la página.');
    } elseif (!empty($honeypot)) {
        Session::setError('No se pudo procesar el formulario.');
    } else {

        $last = (int) Session::get('contact_last_ts', 0);

        if ($last && (time() - $last) < $COOLDOWN_SECONDS) {
            $rest = $COOLDOWN_SECONDS - (time() - $last);
            Session::setError("Por favor, aguardá {$rest}s para volver a enviar.");
        } else {

            $nombre  = Validaciones::sanitizarString($old['nombre']);
            $email   = Validaciones::sanitizarString($old['email']);
            $mensaje = Validaciones::sanitizarString($old['mensaje']);

            $errors = [];

            if ($nombre === '' || $email === '' || $mensaje === '') {
                $errors[] = 'Todos los campos son obligatorios.';
            }
            if (!Validaciones::validarEmail($email)) {
                $errors[] = 'Correo electrónico inválido.';
            }
            if (mb_strlen($mensaje) < 10) {
                $errors[] = 'El mensaje debe tener al menos 10 caracteres.';
            }

            if (!empty($errors)) {
                Session::setError(implode('<br>', $errors));
            } else {

                $to      = 'contacto@jaguata.com';
                $subject = "Nuevo mensaje de {$nombre}";
                $body    = "
                    <h2>Nuevo mensaje de contacto</h2>
                    <p><strong>Nombre:</strong> {$nombre}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <hr>
                    <div style='white-space:pre-wrap;font-family:system-ui'>" .
                    nl2br(htmlspecialchars($mensaje)) .
                    '</div>
                ';

                $ok = Funciones::enviarEmail($to, $subject, $body, $email);

                if ($ok) {
                    Session::set('contact_old', null);
                    Session::set('contact_last_ts', time());
                    Session::setSuccess('¡Gracias! Tu mensaje fue enviado correctamente.');
                } else {
                    Session::setError('Ocurrió un error al enviar el mensaje.');
                }
            }
        }
    }

    header('Location: ' . $RUTA_SELF);
    exit;
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --jg-green: #3c6255;
            --jg-mint: #20c997;
            --jg-ink: #24343a;
            --jg-card: #ffffff;
        }

        /* Fondo similar al login (degradado + textura suave) */
        body {
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(160deg, var(--jg-green) 0%, var(--jg-mint) 100%);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", "Liberation Sans";
            color: var(--jg-ink);
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20px 20px, rgba(255, 255, 255, .13) 6px, transparent 7px) 0 0 / 60px 60px,
                radial-gradient(circle at 50px 40px, rgba(255, 255, 255, .08) 4px, transparent 5px) 0 0 / 60px 60px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .2), rgba(0, 0, 0, .7));
            pointer-events: none;
            z-index: -1;
        }

        /* Contenedor principal estilo login */
        .contact-shell {
            width: min(1100px, 94vw);
            margin: 24px auto 32px;
        }

        /* TOPBAR vidrio */
        .topbar {
            background: rgba(255, 255, 255, .88);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, .12);
            margin-bottom: 20px;
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .65rem 1.25rem;
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-weight: 800;
            color: #24343a;
            text-decoration: none;
            letter-spacing: .02em;
        }

        .topbar-logo {
            width: 36px;
            height: 36px;
            background: #f4f7f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
        }

        .topbar-actions .btn {
            font-size: .82rem;
            padding: 6px 14px;
            border-radius: 999px;
        }

        /* Card principal tipo glass, similar al login */
        .contact-card {
            border: 0;
            border-radius: 22px;
            background: rgba(255, 255, 255, .9);
            backdrop-filter: saturate(140%) blur(10px);
            box-shadow: 0 18px 60px rgba(0, 0, 0, .22);
            overflow: hidden;
        }

        .contact-left {
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, .18), transparent 55%),
                linear-gradient(135deg, #3c6255 0%, #20c997 100%);
            color: #f5fbfa;
            padding: clamp(20px, 4vw, 30px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .contact-left-inner {
            max-width: 360px;
        }

        .contact-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 11px;
            border-radius: 999px;
            background: rgba(0, 0, 0, .22);
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .contact-title {
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.2;
            margin-top: 16px;
            margin-bottom: 10px;
        }

        .contact-text {
            font-size: .94rem;
            opacity: .9;
            margin-bottom: 14px;
        }

        .contact-list {
            list-style: none;
            margin: 0 0 16px 0;
            padding: 0;
        }

        .contact-list li {
            display: flex;
            gap: 8px;
            font-size: .9rem;
            margin-bottom: 8px;
        }

        .contact-list i {
            margin-top: 2px;
        }

        .contact-meta {
            font-size: .8rem;
            opacity: .9;
        }

        /* Form pane estilo login */
        .contact-form-pane {
            padding: clamp(20px, 4vw, 34px);
            background: rgba(255, 255, 255, .96);
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f4f7f9;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .08);
            margin: 0 auto 12px;
        }

        h1,
        h2,
        h3,
        h4 {
            color: var(--jg-green);
        }

        .text-muted-soft {
            color: #6b7b83;
        }

        /* Inputs y botones igual que login */
        .form-control {
            border: 2px solid #e7ecef;
            border-radius: 12px;
            padding: .9rem 1rem;
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
            background-color: #fdfefe;
        }

        .form-control:focus {
            border-color: var(--jg-mint);
            box-shadow: 0 0 0 .18rem rgba(32, 201, 151, .25);
            background-color: #ffffff;
        }

        textarea.form-control {
            resize: vertical;
        }

        .btn-jg {
            background: var(--jg-green);
            border: 0;
            border-radius: 12px;
            padding: .85rem 1.4rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            transition: transform .08s ease, filter .2s ease, box-shadow .2s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .18);
        }

        .btn-jg:hover {
            filter: brightness(.96);
        }

        .btn-jg:active {
            transform: translateY(1px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, .2);
        }

        .btn-outline-soft {
            border-radius: 12px;
            border-width: 2px;
        }

        .paw-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #8aa3a9;
            font-size: .82rem;
            margin-top: 18px;
        }

        .paw-divider::before,
        .paw-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, #cfe6e0, transparent);
        }

        footer {
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            margin-top: 18px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .18);
        }

        @media (max-width: 992px) {
            .contact-left {
                display: none;
            }

            .contact-card {
                border-radius: 20px;
            }

            .contact-shell {
                margin-top: 16px;
            }

            .topbar {
                border-radius: 16px;
            }
        }
    </style>
</head>

<body>

    <div class="contact-shell">

        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-inner">
                <a href="<?= BASE_URL; ?>/" class="topbar-brand">
                    <div class="topbar-logo">
                        <i class="fa-solid fa-paw text-success"></i>
                    </div>
                    <span>Jaguata</span>
                </a>

                <div class="topbar-actions d-flex gap-2">
                    <a href="<?= BASE_URL; ?>/login.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-right-to-bracket me-1"></i>
                        Iniciar sesión
                    </a>

                    <?php if ($panelUrl): ?>
                        <a href="<?= $panelUrl ?>" class="btn btn-success btn-sm text-white">
                            <i class="fa-solid fa-gauge-high me-1"></i>
                            Panel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- CARD PRINCIPAL CONTACTO (2 columnas al estilo login) -->
        <main class="mt-3">
            <div class="contact-card row g-0">
                <!-- Columna izquierda tipo "hero" similar al login -->
                <div class="col-lg-5 contact-left">
                    <div class="contact-left-inner">
                        <span class="contact-pill">
                            <i class="fa-solid fa-paw"></i>
                            Soporte Jaguata
                        </span>

                        <h2 class="contact-title">
                            ¿Tenés dudas o querés sumarte?
                        </h2>

                        <p class="contact-text">
                            Escribinos para consultas sobre paseos, registro de paseadores,
                            alianzas o cualquier comentario sobre la plataforma. Te respondemos
                            a la brevedad.
                        </p>

                        <ul class="contact-list">
                            <li>
                                <i class="fa-solid fa-envelope-open-text"></i>
                                <span>Te contactamos por correo con la respuesta a tu consulta.</span>
                            </li>
                            <li>
                                <i class="fa-solid fa-user-check"></i>
                                <span>Información sobre alta de paseadores y requisitos.</span>
                            </li>
                            <li>
                                <i class="fa-solid fa-dog"></i>
                                <span>Comentarios para mejorar la experiencia de tu peludo.</span>
                            </li>
                        </ul>

                        <p class="contact-meta">
                            <i class="fa-regular fa-clock me-1"></i>
                            Tiempo mínimo entre envíos: <?= $COOLDOWN_SECONDS ?>s
                        </p>
                    </div>

                    <div class="mt-4">
                        <small class="contact-meta">
                            <i class="fa-solid fa-shield-dog me-1"></i>
                            Tu mensaje se envía de forma segura.
                        </small>
                    </div>
                </div>

                <!-- Columna formulario -->
                <div class="col-lg-7">
                    <div class="contact-form-pane">
                        <div class="text-center mb-3">
                            <div class="logo-circle">
                                <i class="fa-regular fa-envelope fa-2x text-success"></i>
                            </div>
                            <h3 class="fw-bold mb-1">Formulario de Contacto</h3>
                            <p class="text-muted-soft mb-0">
                                Completá tus datos y contanos en qué podemos ayudarte 🐾
                            </p>
                        </div>

                        <!-- Alertas -->
                        <div class="mb-3">
                            <?= $error   ? Funciones::generarAlerta('error', $error)     : '' ?>
                            <?= $success ? Funciones::generarAlerta('success', $success) : '' ?>
                        </div>

                        <form method="post" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= Validaciones::generarCSRF(); ?>">

                            <!-- Honeypot -->
                            <div style="position:absolute;left:-9999px;">
                                <input type="text" name="website" autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fa-solid fa-user me-1"></i> Nombre completo
                                </label>
                                <input
                                    type="text"
                                    name="nombre"
                                    class="form-control shadow-sm"
                                    value="<?= htmlspecialchars($old['nombre']) ?>"
                                    required
                                    minlength="2"
                                    maxlength="100"
                                    placeholder="Tu nombre y apellido">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fa-solid fa-envelope me-1"></i> Correo electrónico
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control shadow-sm"
                                    value="<?= htmlspecialchars($old['email']) ?>"
                                    required
                                    maxlength="100"
                                    placeholder="tu@email.com">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fa-solid fa-message me-1"></i> Mensaje
                                </label>
                                <textarea
                                    name="mensaje"
                                    rows="6"
                                    class="form-control shadow-sm"
                                    required
                                    minlength="10"
                                    maxlength="2000"
                                    placeholder="Contanos brevemente en qué podemos ayudarte"><?= htmlspecialchars($old['mensaje']) ?></textarea>
                            </div>

                            <div class="d-flex flex-wrap justify-content-between gap-2 mt-2">
                                <button class="btn btn-jg">
                                    <i class="fa-regular fa-paper-plane"></i>
                                    Enviar mensaje
                                </button>

                                <a href="<?= $RUTA_SELF ?>" class="btn btn-outline-light btn-outline-soft">
                                    <i class="fa-solid fa-eraser me-1"></i>
                                    Limpiar
                                </a>
                            </div>
                        </form>

                        <div class="paw-divider mt-4">
                            <i class="fa-solid fa-paw"></i>
                            <span>Seguro • Rápido • Cercano</span>
                            <i class="fa-solid fa-bone"></i>
                        </div>

                        <div class="text-center mt-3">
                            <small class="text-muted-soft">
                                ¿Preferís volver al inicio?
                                <a href="<?= $inicioUrl ?>" class="fw-semibold text-decoration-none">Ir a la página principal</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="py-3 mt-4">
            <div class="container text-center small text-muted">
                © <?= date('Y'); ?> Jaguata
            </div>
        </footer>
    </div>

</body>

</html>