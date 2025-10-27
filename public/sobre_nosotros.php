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

<!-- Barra de navegación secundaria -->
<div class="container py-3">
    <div class="d-flex justify-content-end gap-2">
        <a href="<?= BASE_URL; ?>/login.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-right-to-bracket me-1"></i> Iniciar sesión
        </a>
        <?php if ($panelUrl): ?>
            <a href="<?= htmlspecialchars($panelUrl) ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-gauge-high me-1"></i> Panel principal
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- HERO -->
<section class="hero-section py-5 text-white" style="background:linear-gradient(135deg,#3c6255,#20c997);">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6 text-center text-lg-start">
                <h1 class="display-5 fw-bold mb-3">Sobre <span class="text-warning">Jaguata</span></h1>
                <p class="lead mb-4">
                    Somos la comunidad líder en paseo de mascotas en Paraguay,
                    conectando dueños de mascotas con paseadores verificados y comprometidos.
                </p>
                <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3">
                    <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-warning btn-lg shadow-sm">
                        <i class="fas fa-user-plus me-2"></i> Únete a Jaguata
                    </a>
                    <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-light btn-lg shadow-sm">
                        <i class="fas fa-envelope me-2"></i> Contáctanos
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <video
                    src="http://localhost/jaguata/assets/uploads/perfiles/gif1.mp4"
                    autoplay
                    loop
                    muted
                    playsinline
                    class="img-fluid rounded-4 shadow-lg"
                    style="max-height:380px; object-fit:contain;">
                    Tu navegador no soporta videos HTML5.
                </video>
            </div>
        </div>
    </div>
</section>

<!-- MISIÓN / VISIÓN / VALORES -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-success">Nuestra Esencia</h2>
            <p class="text-muted">Lo que nos motiva cada día a cuidar de tu mejor amigo 🐶</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-card">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle bg-success-subtle mb-3">
                            <i class="fa-solid fa-bullseye fa-2x text-success"></i>
                        </div>
                        <h5 class="fw-semibold mb-2">Misión</h5>
                        <p class="text-muted mb-0">
                            Conectar de forma segura a dueños y paseadores,
                            promoviendo bienestar, confianza y felicidad animal.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-card">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle bg-success-subtle mb-3">
                            <i class="fa-solid fa-eye fa-2x text-success"></i>
                        </div>
                        <h5 class="fw-semibold mb-2">Visión</h5>
                        <p class="text-muted mb-0">
                            Ser la plataforma más confiable de servicios para mascotas
                            en Paraguay y expandir nuestro impacto en toda la región.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-card">
                    <div class="card-body text-center p-4">
                        <div class="icon-circle bg-success-subtle mb-3">
                            <i class="fa-solid fa-heart fa-2x text-success"></i>
                        </div>
                        <h5 class="fw-semibold mb-2">Valores</h5>
                        <ul class="list-unstyled text-muted mb-0">
                            <li><i class="fa-solid fa-paw me-2 text-success"></i>Amor y respeto animal</li>
                            <li><i class="fa-solid fa-handshake me-2 text-success"></i>Confianza y transparencia</li>
                            <li><i class="fa-solid fa-shield-dog me-2 text-success"></i>Seguridad y responsabilidad</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="cta-section py-5" style="background:#f6f9f7;">
    <div class="container text-center text-lg-start">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h2 class="fw-bold mb-2 text-success">¿Listo para comenzar?</h2>
                <p class="text-muted mb-0">
                    Crea tu cuenta o hablá con nosotros para saber cómo Jaguata puede ayudarte.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-success btn-lg me-2">
                    <i class="fa-solid fa-paw me-1"></i> Crear cuenta
                </a>
                <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fa-regular fa-message me-1"></i> Hablar ahora
                </a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>

<style>
    .hover-card {
        transition: transform .3s ease, box-shadow .3s ease;
    }

    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
    }

    .icon-circle {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }
</style>