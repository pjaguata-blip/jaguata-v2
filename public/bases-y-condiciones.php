<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

$titulo      = 'Bases y Condiciones - Jaguata';
$descripcion = 'Términos de uso de la plataforma Jaguata, servicio de conexión entre dueños y paseadores de mascotas.';

$inicioUrl = BASE_URL;
$panelUrl  = null;

if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRolSeguro();
    if ($rol) {
        $panelUrl = BASE_URL . "/features/{$rol}/Dashboard.php";
    }
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo) ?></title>
    <meta name="description" content="<?= h($descripcion) ?>">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- ✅ Tu tema -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <!-- ✅ Topbar simple (estilo Jaguata) -->
    <div class="topbar-mobile d-lg-none">
        <div class="d-flex align-items-center gap-2 fw-semibold">
            <i class="fas fa-paw"></i> Jaguata
        </div>
        <a class="btn btn-sm btn-light" href="<?= h($inicioUrl) ?>" aria-label="Volver al inicio">
            <i class="fa-solid fa-house"></i>
        </a>
    </div>

    <main>
        <!-- ✅ Header tipo Dashboard -->
        <div class="header-box header-dashboard">
            <div class="d-flex align-items-center gap-3">
                <img
                    src="<?= ASSETS_URL; ?>/images/logojag.png"
                    alt="Jaguata"
                    width="52"
                    height="52"
                    class="rounded-circle border border-light p-1"
                >
                <div>
                    <h1 class="fw-bold mb-1">Bases y Condiciones</h1>
                    <p class="mb-0 opacity-75">
                        Conocé los términos de uso y las responsabilidades en la plataforma.
                    </p>
                </div>
            </div>

            <div class="d-none d-md-flex align-items-center gap-2">
                <a href="<?= h($inicioUrl) ?>" class="btn btn-light">
                    <i class="fa-solid fa-house me-1"></i> Inicio
                </a>

                <?php if ($panelUrl): ?>
                    <a href="<?= h($panelUrl) ?>" class="btn btn-success">
                        <i class="fa-solid fa-gauge-high me-1"></i> Ir a mi panel
                    </a>
                <?php endif; ?>

                <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-warning text-dark">
                    <i class="fa-regular fa-envelope me-1"></i> Contacto
                </a>
            </div>

            <i class="fas fa-scale-balanced fa-3x opacity-75 d-none d-md-block"></i>
        </div>

        <div class="container py-4">

            <div class="row g-3">
                <!-- ✅ Resumen lateral (card estilo theme) -->
                <div class="col-lg-4">
                    <div class="section-card h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-pill text-bg-success">
                                <i class="fa-solid fa-paw me-1"></i> Información legal
                            </span>
                        </div>

                        <h2 class="h5 fw-bold mb-2">Uso responsable de Jaguata</h2>
                        <p class="text-muted mb-3">
                            Estas Bases y Condiciones regulan el uso de Jaguata por parte de dueños y paseadores.
                            Leelas atentamente antes de continuar.
                        </p>

                        <ul class="list-unstyled text-muted mb-3">
                            <li class="mb-2">
                                <i class="fa-solid fa-circle-check me-2 text-success"></i>
                                Alcance del servicio e intermediación.
                            </li>
                            <li class="mb-2">
                                <i class="fa-solid fa-circle-check me-2 text-success"></i>
                                Responsabilidades de dueños y paseadores.
                            </li>
                            <li class="mb-2">
                                <i class="fa-solid fa-circle-check me-2 text-success"></i>
                                Privacidad, datos personales y limitación de responsabilidad.
                            </li>
                        </ul>

                        <hr>

                        <div class="text-muted small mb-3">
                            <div class="d-flex align-items-center mb-1">
                                <i class="fa-regular fa-clock me-2"></i>
                                <span><strong>Última actualización:</strong> Octubre 2025</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-scale-balanced me-2"></i>
                                <span>Ley aplicable: República del Paraguay</span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-success">
                                <i class="fa-solid fa-user-plus me-1"></i> Crear cuenta
                            </a>
                            <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-secondary">
                                <i class="fa-regular fa-envelope me-1"></i> Contactar soporte
                            </a>
                        </div>
                    </div>
                </div>

                <!-- ✅ Contenido principal -->
                <div class="col-lg-8">
                    <div class="section-card">
                        <h2 class="h4 fw-bold mb-3">
                            <i class="fa-solid fa-file-contract me-2 text-success"></i>
                            Términos y Condiciones de Uso
                        </h2>

                        <p class="text-muted mb-4">
                            Al registrarte o utilizar Jaguata, confirmás que leíste, comprendiste y aceptás estas condiciones.
                            Si no estás de acuerdo, no debés utilizar la plataforma.
                        </p>

                        <ol class="text-muted lh-lg">
                            <li class="mb-3">
                                <strong>Aceptación de los términos:</strong>
                                El uso de Jaguata implica aceptación plena de estas bases.
                            </li>
                            <li class="mb-3">
                                <strong>Descripción del servicio:</strong>
                                Jaguata conecta a dueños con paseadores, actuando como intermediario tecnológico.
                            </li>
                            <li class="mb-3">
                                <strong>Registro de usuarios:</strong>
                                Deben brindarse datos reales y resguardar credenciales de acceso.
                            </li>
                            <li class="mb-3">
                                <strong>Responsabilidades de los paseadores:</strong>
                                Cumplir lo pactado, cuidar a las mascotas y actuar con responsabilidad.
                            </li>
                            <li class="mb-3">
                                <strong>Responsabilidades de los dueños:</strong>
                                Brindar información veraz de la mascota y cumplir acuerdos/pagos.
                            </li>
                            <li class="mb-3">
                                <strong>Uso adecuado:</strong>
                                Prohibido el uso para fines ilegales, ofensivos o fraudulentos.
                            </li>
                            <li class="mb-3">
                                <strong>Privacidad:</strong>
                                Jaguata protege datos conforme a normativa vigente en Paraguay (incl. Ley 6534/20).
                            </li>
                            <li class="mb-3">
                                <strong>Limitación de responsabilidad:</strong>
                                Jaguata no se responsabiliza por accidentes, extravíos o conductas indebidas durante paseos.
                            </li>
                            <li class="mb-3">
                                <strong>Modificaciones:</strong>
                                Estas condiciones pueden actualizarse; se informará mediante la plataforma.
                            </li>
                            <li class="mb-0">
                                <strong>Ley aplicable:</strong>
                                Se rige por leyes de Paraguay, con jurisdicción en Asunción.
                            </li>
                        </ol>

                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-success">
                                <i class="fa-solid fa-user-plus me-1"></i> Acepto y deseo registrarme
                            </a>
                            <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-secondary">
                                <i class="fa-regular fa-envelope me-1"></i> Consultar sobre estos términos
                            </a>
                            <?php if ($panelUrl): ?>
                                <a href="<?= h($panelUrl) ?>" class="btn btn-light">
                                    <i class="fa-solid fa-gauge-high me-1"></i> Ir a mi panel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                <div class="py-2">
                    © <?= date('Y'); ?> Jaguata. Todos los derechos reservados.
                </div>
            </footer>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
