<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

$titulo = 'Bases y Condiciones - Jaguata';
$descripcion = 'Términos de uso de la plataforma Jaguata, servicio de conexión entre dueños y paseadores de mascotas.';

$inicioUrl = BASE_URL;
$panelUrl  = null;
if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRolSeguro();
    if ($rol) {
        $panelUrl = BASE_URL . "/features/{$rol}/Dashboard.php";
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --jg-green: #3c6255;
            --jg-mint: #20c997;
        }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", "Apple Color Emoji", "Segoe UI Emoji";
            background-color: #f3f5f6;
            margin: 0;
        }

        .terms-hero {
            position: relative;
            padding: 4rem 0 3.5rem;
            text-align: center;
            color: #ffffff;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, .1), transparent 55%),
                linear-gradient(135deg, #3c6255, #20c997);
            overflow: hidden;
        }

        .terms-hero-overlay {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20px 20px, rgba(255, 255, 255, .15) 6px, transparent 7px) 0 0 / 60px 60px,
                radial-gradient(circle at 50px 40px, rgba(255, 255, 255, .08) 4px, transparent 5px) 0 0 / 60px 60px;
            opacity: 0.5;
            pointer-events: none;
        }

        .terms-card {
            border-radius: 20px;
            overflow: hidden;
            background: transparent;
        }

        .terms-sidebar {
            background: linear-gradient(145deg, #3c6255, #20c997);
            color: #f5fbfa;
        }

        .terms-sidebar-inner {
            padding: 2rem 1.75rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1.5rem;
        }

        .terms-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(0, 0, 0, .18);
            font-size: .78rem;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .terms-sidebar-list li+li {
            margin-top: 0.4rem;
        }

        .terms-meta {
            border-top: 1px solid rgba(255, 255, 255, .25);
            padding-top: 0.75rem;
        }

        .terms-cta .btn {
            font-size: .9rem;
        }

        .terms-content {
            background-color: #ffffff;
        }

        .terms-list li {
            margin-bottom: 0.8rem;
        }

        footer {
            border-top: 1px solid #dee2e6;
            background: #ffffff;
        }

        footer p {
            margin: 0.25rem 0;
        }

        @media (max-width: 991.98px) {
            .terms-sidebar-inner {
                padding: 1.5rem;
            }
        }

        @media (max-width: 575.98px) {
            .terms-hero {
                padding: 3rem 0 2.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- HERO -->
    <section class="terms-hero">
        <div class="terms-hero-overlay"></div>
        <div class="container position-relative">
            <h1 class="display-5 fw-bold mb-3">Bases y Condiciones</h1>
            <p class="lead mb-4">
                Conocé los términos de uso de Jaguata y los derechos y responsabilidades de quienes usan la plataforma.
            </p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="<?= htmlspecialchars($inicioUrl) ?>" class="btn btn-outline-light">
                    <i class="fa-solid fa-house me-1"></i> Inicio
                </a>
                <?php if ($panelUrl): ?>
                    <a href="<?= htmlspecialchars($panelUrl) ?>" class="btn btn-light text-success">
                        <i class="fa-solid fa-gauge-high me-1"></i> Ir a mi panel
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-warning text-dark">
                    <i class="fa-regular fa-envelope me-1"></i> Contacto
                </a>
            </div>
        </div>
    </section>

    <!-- CONTENIDO -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-0 terms-card shadow-sm">
                <!-- Sidebar resumen -->
                <aside class="col-lg-4 terms-sidebar">
                    <div class="terms-sidebar-inner">
                        <div>
                            <span class="terms-pill">
                                <i class="fa-solid fa-paw"></i>
                                Información legal
                            </span>

                            <h2 class="h4 fw-bold mt-3 mb-2">Uso responsable de Jaguata</h2>
                            <p class="small mb-3">
                                Estas Bases y Condiciones regulan el uso de la plataforma por parte de dueños y paseadores
                                de mascotas. Te recomendamos leerlas atentamente antes de continuar.
                            </p>

                            <ul class="list-unstyled small mb-3 terms-sidebar-list">
                                <li>
                                    <i class="fa-solid fa-circle-check me-2"></i>
                                    Alcance del servicio e intermediación.
                                </li>
                                <li>
                                    <i class="fa-solid fa-circle-check me-2"></i>
                                    Responsabilidades de dueños y paseadores.
                                </li>
                                <li>
                                    <i class="fa-solid fa-circle-check me-2"></i>
                                    Privacidad, datos personales y limitación de responsabilidad.
                                </li>
                            </ul>
                        </div>

                        <div>
                            <div class="terms-meta small mb-3">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fa-regular fa-clock me-2"></i>
                                    <span><strong>Última actualización:</strong> Octubre 2025</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-scale-balanced me-2"></i>
                                    <span>Ley aplicable: República del Paraguay</span>
                                </div>
                            </div>

                            <div class="terms-cta">
                                <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-success w-100 mb-2">
                                    <i class="fa-solid fa-user-plus me-1"></i> Crear cuenta
                                </a>
                                <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-light w-100">
                                    <i class="fa-regular fa-envelope me-1"></i> Contactar soporte
                                </a>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Contenido principal -->
                <article class="col-lg-8 terms-content bg-white">
                    <div class="p-4 p-lg-5">
                        <h2 class="fw-bold text-success mb-3">Términos y Condiciones de Uso</h2>
                        <p class="text-muted mb-4">
                            Al registrarte o utilizar Jaguata, confirmás que leíste, comprendiste y aceptás las siguientes
                            condiciones. Si no estás de acuerdo con ellas, no debés utilizar la plataforma.
                        </p>

                        <ol class="text-muted lh-lg terms-list">
                            <li>
                                <strong>Aceptación de los Términos:</strong>
                                El uso de Jaguata implica la aceptación plena y sin reservas de las presentes Bases y Condiciones de Uso.
                            </li>
                            <li>
                                <strong>Descripción del Servicio:</strong>
                                Jaguata es una plataforma digital que conecta a dueños de mascotas con paseadores de perros,
                                actuando únicamente como intermediario tecnológico.
                            </li>
                            <li>
                                <strong>Registro de Usuarios:</strong>
                                Los usuarios deben registrarse con datos reales y mantener la confidencialidad de su cuenta.
                            </li>
                            <li>
                                <strong>Responsabilidades de los Paseadores:</strong>
                                Cumplir con los servicios pactados, cuidar a los animales y respetar las normas éticas de la comunidad.
                            </li>
                            <li>
                                <strong>Responsabilidades de los Dueños:</strong>
                                Proporcionar información veraz sobre su mascota y cumplir con los pagos y acuerdos.
                            </li>
                            <li>
                                <strong>Uso Adecuado:</strong>
                                Está prohibido usar la plataforma para actividades ilegales, ofensivas o fraudulentas.
                            </li>
                            <li>
                                <strong>Privacidad:</strong>
                                Jaguata protege los datos personales conforme a la Ley 6534/20 y otras normas de protección de datos en Paraguay.
                            </li>
                            <li>
                                <strong>Limitación de Responsabilidad:</strong>
                                Jaguata no se responsabiliza por accidentes, extravíos o conductas indebidas durante los paseos.
                            </li>
                            <li>
                                <strong>Modificaciones:</strong>
                                La plataforma podrá actualizar estas condiciones en cualquier momento, notificando a través del sitio web.
                            </li>
                            <li>
                                <strong>Ley Aplicable:</strong>
                                Estas condiciones se rigen por las leyes de la República del Paraguay, con jurisdicción en Asunción.
                            </li>
                        </ol>

                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-success">
                                <i class="fa-solid fa-user-plus me-1"></i> Acepto y deseo registrarme
                            </a>
                            <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-secondary">
                                <i class="fa-regular fa-envelope me-1"></i> Consultar sobre estos términos
                            </a>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <footer class="py-3 mt-4">
        <div class="container text-center small text-muted">
            <p>© <?= date('Y'); ?> Jaguata. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>