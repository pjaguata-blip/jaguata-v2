<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

$titulo = 'Sobre Nosotros - Jaguata';
$descripcion = 'Conoce más sobre Jaguata, la plataforma líder en paseo de mascotas en Paraguay. Nuestra misión es conectar dueños de mascotas con paseadores profesionales.';

// URLs de navegación
$inicioUrl = BASE_URL;
$panelUrl  = null;
if (Session::isLoggedIn()) {
    $rolSeguro = method_exists(Session::class, 'getUsuarioRolSeguro')
        ? Session::getUsuarioRolSeguro()
        : (Session::get('rol') ?? null);
    if ($rolSeguro) {
        $panelUrl = BASE_URL . "/features/{$rolSeguro}/Dashboard.php";
    }
}

include __DIR__ . '/../src/Templates/Header.php';
?>

<!-- Barra de navegación secundaria (solo botones, sin duplicar en otros lados) -->
<div class="container py-3">
    <div class="d-flex justify-content-end gap-2">
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

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Sobre Jaguata</h1>
                <p class="lead mb-4">
                    Somos la plataforma líder en paseo de mascotas en Paraguay,
                    conectando dueños de mascotas con paseadores profesionales verificados.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-warning btn-lg">
                        <i class="fas fa-user-plus me-2"></i> Únete a Jaguata
                    </a>
                    <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-envelope me-2"></i> Contáctanos
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <img
                    src="<?= ASSETS_URL; ?>/images/about-hero.png"
                    alt="Ilustración sobre Jaguata: paseadores y mascotas conectados"
                    class="img-fluid rounded shadow"
                    loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- Sección Misión, Visión, Valores (ejemplo) -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="h5"><i class="fa-solid fa-bullseye me-2 text-primary"></i>Misión</h3>
                        <p class="mb-0 text-color #ffff">
                            Conectar de forma segura y rápida a dueños con paseadores confiables,
                            garantizando bienestar y felicidad para cada mascota.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="h5"><i class="fa-solid fa-eye me-2 text-primary"></i>Visión</h3>
                        <p class="mb-0 text-color #ffff">
                            Ser la plataforma de referencia en servicios para mascotas en Paraguay y la región.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="h5"><i class="fa-solid fa-heart me-2 text-primary"></i>Valores</h3>
                        <ul class="mb-0 text--color #ffff ps-3">
                            <li>Cuidado y respeto animal</li>
                            <li>Confianza y transparencia</li>
                            <li>Seguridad y responsabilidad</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA final -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h2 class="h3 mb-2">¿Listo para comenzar?</h2>
                <p class="text--color #ffff mb-0">Crea tu cuenta o hablá con nosotros para conocer más.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-primary me-2">
                    <i class="fa-solid fa-paw me-1"></i> Crear cuenta
                </a>
                <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-secondary">
                    <i class="fa-regular fa-message me-1"></i> Hablar ahora
                </a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>