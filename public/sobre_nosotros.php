<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

$titulo = 'Sobre Nosotros - Jaguata';
$descripcion = 'Conoce más sobre Jaguata, la plataforma líder en paseo de mascotas en Paraguay. Nuestra misión es conectar dueños de mascotas con paseadores profesionales.';

$panelUrl = null;
if (Session::isLoggedIn()) {
    $rolSeguro = method_exists(Session::class, 'getUsuarioRolSeguro')
        ? Session::getUsuarioRolSeguro()
        : (Session::get('rol') ?? null);

    if ($rolSeguro && preg_match('/^[A-Za-z0-9_-]+$/', $rolSeguro)) {
        $panelUrl = BASE_URL . '/features/' . rawurlencode($rolSeguro) . '/Dashboard.php';
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?></title>
    <meta name="description" content="<?= htmlspecialchars($descripcion) ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        crossorigin="anonymous">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        rel="stylesheet"
        referrerpolicy="no-referrer" />

    <style>
        :root {
            --jg-green: #3c6255;
            --jg-mint: #20c997;
            --jg-ink: #24343a;
            --jg-card: #ffffff;
        }

        /* Fondo similar al login (degradado + textura) */
        body {
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(160deg, var(--jg-green) 0%, var(--jg-mint) 100%);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans";
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

        .about-shell {
            width: min(1100px, 94vw);
            margin: 24px auto 32px;
        }

        /* TOPBAR estilo glass como login/contacto */
        .topbar {
            background: rgba(255, 255, 255, .9);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, .12);
            margin-bottom: 20px;
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .6rem 1.25rem;
        }

        .topbar-brand {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            text-decoration: none;
            color: #24343a;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .topbar-logo {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f4f7f9;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
        }

        .topbar-brand span {
            font-size: 1.05rem;
        }

        .topbar-actions .btn {
            font-size: .82rem;
            padding: 6px 14px;
            border-radius: 999px;
        }

        /* Card principal tipo glass (similar login/contacto) */
        .about-card,
        .about-info-card {
            border: 0;
            border-radius: 22px;
            background: rgba(255, 255, 255, .9);
            backdrop-filter: saturate(140%) blur(10px);
            box-shadow: 0 18px 60px rgba(0, 0, 0, .22);
            overflow: hidden;
        }

        .about-main-pane {
            padding: clamp(22px, 4vw, 32px);
        }

        /* Columna izquierda tipo hero */
        .about-left {
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, .16), transparent 55%),
                linear-gradient(135deg, #3c6255 0%, #20c997 100%);
            color: #f5fbfa;
            padding: clamp(22px, 4vw, 32px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .about-left-inner {
            max-width: 390px;
        }

        .about-pill {
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

        .about-title {
            font-size: 1.9rem;
            font-weight: 800;
            line-height: 1.2;
            margin-top: 16px;
            margin-bottom: 12px;
        }

        .about-text {
            font-size: .96rem;
            opacity: .92;
            margin-bottom: 16px;
        }

        .about-highlights {
            list-style: none;
            padding: 0;
            margin: 0 0 16px 0;
            font-size: .9rem;
        }

        .about-highlights li {
            display: flex;
            gap: 8px;
            margin-bottom: 6px;
        }

        .about-highlights i {
            margin-top: 2px;
        }

        .about-meta {
            font-size: .8rem;
            opacity: .9;
        }

        /* Video / media */
        .media-wrapper {
            aspect-ratio: 16/9;
            max-height: 380px;
            background: rgba(0, 0, 0, .04);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .18);
        }

        .media-wrapper video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-jg-main {
            border-radius: 12px;
            padding: .85rem 1.5rem;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .2);
        }

        .btn-outline-soft {
            border-radius: 12px;
            border-width: 2px;
        }

        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .hover-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .hover-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(0, 0, 0, .14);
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
            background: rgba(255, 255, 255, .94);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            margin-top: 18px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .18);
        }

        /* ====== RESPONSIVE ====== */

        /* Tablets y abajo */
        @media (max-width: 992px) {
            .about-left {
                display: none;
            }

            .about-card {
                border-radius: 20px;
            }

            .about-shell {
                margin-top: 16px;
                margin-bottom: 20px;
                width: 100%;
                padding-inline: 12px;
            }

            .topbar {
                border-radius: 16px;
            }

            .topbar-inner {
                padding-inline: .9rem;
            }

            .about-main-pane {
                padding: 18px 18px 22px;
            }
        }

        /* Móviles medianos */
        @media (max-width: 768px) {
            .topbar-inner {
                flex-wrap: wrap;
                gap: 8px;
            }

            .topbar-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .about-title {
                font-size: 1.6rem;
            }

            .media-wrapper {
                max-height: none;
            }

            .btn-jg-main {
                width: 100%;
                justify-content: center;
            }

            .btn-outline-soft {
                width: 100%;
                margin-top: 8px;
            }

            .about-info-card {
                border-radius: 18px;
            }
        }

        /* Móviles pequeños */
        @media (max-width: 576px) {
            .topbar-inner {
                flex-direction: column;
                align-items: flex-start;
            }

            .topbar-actions {
                justify-content: flex-start;
            }

            .about-main-pane {
                padding: 16px 14px 20px;
            }

            .about-info-card {
                padding: 16px 14px !important;
            }

            .media-wrapper {
                aspect-ratio: 4/3;
            }

            .icon-circle {
                width: 64px;
                height: 64px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            video {
                animation: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="about-shell">

        <!-- Topbar estilo login/contacto -->
        <header class="topbar" role="banner">
            <div class="topbar-inner">
                <a href="<?= BASE_URL; ?>/" class="topbar-brand">
                    <div class="topbar-logo">
                        <i class="fa-solid fa-paw text-success" aria-hidden="true"></i>
                    </div>
                    <span>Jaguata</span>
                </a>

                <div class="topbar-actions d-flex align-items-center gap-2">
                    <a href="<?= BASE_URL; ?>/login.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-right-to-bracket me-1" aria-hidden="true"></i>
                        Iniciar sesión
                    </a>
                    <?php if ($panelUrl): ?>
                        <a href="<?= htmlspecialchars($panelUrl, ENT_QUOTES, 'UTF-8') ?>"
                            class="btn btn-success text-white btn-sm">
                            <i class="fa-solid fa-gauge-high me-1" aria-hidden="true"></i>
                            Panel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Card principal "Sobre nosotros" -->
        <main class="mt-3">
            <div class="about-card row g-0 mb-4">
                <!-- Columna izquierda (hero texto) - se oculta en móviles -->
                <div class="col-lg-5 about-left">
                    <div class="about-left-inner">
                        <span class="about-pill">
                            <i class="fa-solid fa-paw"></i>
                            Comunidad de paseos caninos
                        </span>

                        <h1 class="about-title">
                            Sobre <span class="text-warning">Jaguata</span>
                        </h1>

                        <p class="about-text">
                            Somos la comunidad líder en paseo de mascotas en Paraguay, conectando dueños de mascotas con
                            paseadores verificados y comprometidos con el bienestar animal.
                        </p>

                        <ul class="about-highlights">
                            <li>
                                <i class="fa-solid fa-shield-dog" aria-hidden="true"></i>
                                <span>Perfiles de paseadores con datos verificados y enfoque en seguridad.</span>
                            </li>
                            <li>
                                <i class="fa-solid fa-users" aria-hidden="true"></i>
                                <span>Una red que genera oportunidades laborales formales y flexibles.</span>
                            </li>
                            <li>
                                <i class="fa-solid fa-heart" aria-hidden="true"></i>
                                <span>Amor, respeto y responsabilidad hacia cada mascota.</span>
                            </li>
                        </ul>

                        <p class="about-meta">
                            <i class="fa-regular fa-clock me-1"></i>
                            Operando inicialmente en Asunción y Gran Asunción, con proyección regional.
                        </p>
                    </div>

                    <div class="mt-4">
                        <small class="about-meta">
                            <i class="fa-solid fa-shield-halved me-1"></i>
                            Diseñado para ser transparente, seguro y cercano a las familias.
                        </small>
                    </div>
                </div>

                <!-- Columna derecha (video + CTA) -->
                <div class="col-lg-7">
                    <div class="about-main-pane">
                        <div class="row g-4 align-items-center">
                            <div class="col-12">
                                <div class="media-wrapper mx-auto">
                                    <video
                                        src="<?= BASE_URL; ?>/assets/uploads/perfiles/gif1.mp4"
                                        poster="<?= BASE_URL; ?>/assets/img/jaguata-promo-poster.jpg"
                                        preload="metadata"
                                        autoplay muted loop playsinline
                                        controlslist="nodownload"
                                        aria-label="Promoción de Jaguata">
                                        Tu navegador no soporta videos HTML5.
                                    </video>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <p class="mb-3 text-muted">
                                    Jaguata nace con el objetivo de ofrecer una solución sencilla y confiable para quienes
                                    necesitan ayuda con los paseos diarios de sus mascotas, al mismo tiempo que brinda
                                    oportunidades a personas responsables que desean trabajar cuidando animales.
                                </p>

                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                    <a href="<?= BASE_URL; ?>/registro.php"
                                        class="btn btn-warning btn-jg-main text-dark">
                                        <i class="fas fa-user-plus me-2" aria-hidden="true"></i>
                                        Únete a Jaguata
                                    </a>
                                    <a href="<?= BASE_URL; ?>/contacto.php"
                                        class="btn btn-outline-black btn-outline-soft">
                                        <i class="fas fa-envelope me-2" aria-hidden="true"></i>
                                        Contáctanos
                                    </a>
                                </div>

                                <div class="paw-divider">
                                    <i class="fa-solid fa-paw" aria-hidden="true"></i>
                                    <span>Personas, mascotas y ciudad en equilibrio</span>
                                    <i class="fa-solid fa-bone" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card secundaria con misión / visión / valores -->
            <section class="about-info-card p-4">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-success">Nuestra Esencia</h2>
                    <p class="text-muted mb-0">
                        Lo que nos motiva cada día a cuidar de tu mejor amigo 🐶
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm hover-card">
                            <div class="card-body text-center p-4">
                                <div class="icon-circle bg-success-subtle mb-3">
                                    <i class="fa-solid fa-bullseye fa-2x text-success" aria-hidden="true"></i>
                                </div>
                                <h5 class="fw-semibold mb-2">Misión</h5>
                                <p class="text-muted mb-0">
                                    Conectar de forma segura a dueños y paseadores, promoviendo bienestar, confianza y
                                    felicidad animal en cada paseo.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm hover-card">
                            <div class="card-body text-center p-4">
                                <div class="icon-circle bg-success-subtle mb-3">
                                    <i class="fa-solid fa-eye fa-2x text-success" aria-hidden="true"></i>
                                </div>
                                <h5 class="fw-semibold mb-2">Visión</h5>
                                <p class="text-muted mb-0">
                                    Ser la plataforma más confiable de servicios para mascotas en Paraguay y expandir
                                    nuestro impacto en la región, con foco en inclusión y formalización laboral.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm hover-card">
                            <div class="card-body text-center p-4">
                                <div class="icon-circle bg-success-subtle mb-3">
                                    <i class="fa-solid fa-heart fa-2x text-success" aria-hidden="true"></i>
                                </div>
                                <h5 class="fw-semibold mb-2">Valores</h5>
                                <ul class="list-unstyled text-muted mb-0">
                                    <li>
                                        <i class="fa-solid fa-paw me-2 text-success" aria-hidden="true"></i>
                                        Amor y respeto animal
                                    </li>
                                    <li>
                                        <i class="fa-solid fa-handshake me-2 text-success" aria-hidden="true"></i>
                                        Confianza y transparencia
                                    </li>
                                    <li>
                                        <i class="fa-solid fa-shield-dog me-2 text-success" aria-hidden="true"></i>
                                        Seguridad y responsabilidad
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA final simple -->
            <section class="mt-4">
                <div class="about-info-card p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-8">
                            <h3 class="fw-bold text-success mb-2">¿Listo para comenzar?</h3>
                            <p class="text-muted mb-0">
                                Creá tu cuenta o hablá con nosotros para saber cómo Jaguata puede ayudarte a organizar los
                                paseos de tu mascota.
                            </p>
                        </div>
                        <div class="col-lg-4 text-lg-end text-center mt-3 mt-lg-0 d-flex flex-wrap justify-content-center justify-content-lg-end gap-2">
                            <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-success btn-lg">
                                <i class="fa-solid fa-paw me-1" aria-hidden="true"></i> Crear cuenta
                            </a>
                            <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fa-regular fa-message me-1" aria-hidden="true"></i> Hablar ahora
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="py-3 mt-4">
            <div class="container small text-muted text-center">
                © <?= date('Y') ?> Jaguata
                <?php if ($panelUrl): ?>
                    · <a href="<?= htmlspecialchars($panelUrl, ENT_QUOTES, 'UTF-8') ?>"
                        class="text-decoration-none text-success">
                        Ir a mi panel
                    </a>
                <?php endif; ?>
            </div>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>

</html>