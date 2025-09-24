<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

// Inicializar configuración
AppConfig::init();

$titulo = 'Sobre Nosotros - Jaguata';
$descripcion = 'Conoce más sobre Jaguata, la plataforma líder en paseo de mascotas en Paraguay. Nuestra misión es conectar dueños de mascotas con paseadores profesionales.';
?>

<?php include __DIR__ . '/../src/Templates/Header.php'; ?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Sobre Jaguata</h1>
                <p class="lead mb-4">
                    Somos la plataforma líder en paseo de mascotas en Paraguay, 
                    conectando dueños de mascotas con paseadores profesionales verificados.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-warning btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Únete a Jaguata
                    </a>
                    <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-envelope me-2"></i>Contáctanos
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="<?= ASSETS_URL; ?>/images/about-hero.png" alt="Ilustración sobre Jaguata" class="img-fluid rounded">
            </div>
        </div>
    </div>
</section>

<!-- (resto de secciones igual, solo cuidando alt y etiquetas semánticas) -->

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>
