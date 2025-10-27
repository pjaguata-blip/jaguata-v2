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

include __DIR__ . '/../src/Templates/Header.php';
?>

<!-- HERO -->
<section class="hero-section py-5 text-white text-center" style="background:linear-gradient(135deg,#3c6255,#20c997);">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3">Bases y Condiciones</h1>
        <p class="lead mb-4">Conocé los términos de uso de la plataforma Jaguata y los derechos y responsabilidades de nuestros usuarios.</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="<?= htmlspecialchars($inicioUrl) ?>" class="btn btn-outline-light">
                <i class="fa-solid fa-house me-1"></i> Inicio
            </a>
            <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-warning text-dark">
                <i class="fa-regular fa-envelope me-1"></i> Contacto
            </a>
        </div>
    </div>
</section>

<!-- CONTENIDO -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="card shadow-sm border-0 p-4">
            <h2 class="fw-bold text-success mb-4">Términos y Condiciones de Uso</h2>

            <p><strong>Última actualización:</strong> Octubre 2025</p>

            <ol class="text-muted lh-lg">
                <li><strong>Aceptación de los Términos:</strong> El uso de Jaguata implica la aceptación plena y sin reservas de las presentes Bases y Condiciones de Uso.</li>
                <li><strong>Descripción del Servicio:</strong> Jaguata es una plataforma digital que conecta a dueños de mascotas con paseadores de perros, actuando únicamente como intermediario tecnológico.</li>
                <li><strong>Registro de Usuarios:</strong> Los usuarios deben registrarse con datos reales y mantener la confidencialidad de su cuenta.</li>
                <li><strong>Responsabilidades de los Paseadores:</strong> Cumplir con los servicios pactados, cuidar a los animales y respetar las normas éticas de la comunidad.</li>
                <li><strong>Responsabilidades de los Dueños:</strong> Proporcionar información veraz sobre su mascota y cumplir con los pagos y acuerdos.</li>
                <li><strong>Uso Adecuado:</strong> Está prohibido usar la plataforma para actividades ilegales, ofensivas o fraudulentas.</li>
                <li><strong>Privacidad:</strong> Jaguata protege los datos personales conforme a la Ley 6534/20 y otras normas de protección de datos en Paraguay.</li>
                <li><strong>Limitación de Responsabilidad:</strong> Jaguata no se responsabiliza por accidentes, extravíos o conductas indebidas durante los paseos.</li>
                <li><strong>Modificaciones:</strong> La plataforma podrá actualizar estas condiciones en cualquier momento, notificando a través del sitio web.</li>
                <li><strong>Ley Aplicable:</strong> Estas condiciones se rigen por las leyes de la República del Paraguay, con jurisdicción en Asunción.</li>
            </ol>

            <div class="mt-4">
                <a href="<?= BASE_URL; ?>/registro.php" class="btn btn-success btn-lg me-2">
                    <i class="fa-solid fa-user-plus me-1"></i> Crear cuenta
                </a>
                <a href="<?= BASE_URL; ?>/contacto.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fa-regular fa-envelope me-1"></i> Contactar soporte
                </a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>

<style>
    ol li {
        margin-bottom: 0.8rem;
    }
</style>