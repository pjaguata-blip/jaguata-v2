<?php

use Jaguata\Helpers\Session;

$usuarioLogueado = Session::isLoggedIn();
$rolUsuario      = Session::getUsuarioRol();

// 🔹 URL dinámica de inicio
$inicioUrl = BASE_URL;
if ($usuarioLogueado && $rolUsuario) {
    $inicioUrl = BASE_URL . "/features/{$rolUsuario}/Dashboard.php";
}
?>
</main>

<footer class="footer-jaguata py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-lg-4 mb-4">
                <h5 class="mb-3 d-flex align-items-center fw-bold text-success">
                    <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logoo.jpg" alt="Jaguata" height="45" class="me-2 rounded-circle shadow-sm">
                    Jaguata
                </h5>
                <p class="text-muted small">
                    Conectamos dueños de mascotas con paseadores profesionales en Paraguay.
                    Cuidamos de tu mejor amigo con amor, confianza y responsabilidad 🐾
                </p>
                <div class="social-links mt-3">
                    <a href="#" class="text-success me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="text-success me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-success me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-success me-3"><i class="fab fa-linkedin-in fa-lg"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-success mb-3 fw-semibold">Enlaces</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= $inicioUrl; ?>" class="footer-link">Inicio</a></li>
                    <li><a href="<?= BASE_URL; ?>/sobre_nosotros.php" class="footer-link">Sobre Nosotros</a></li>
                    <li><a href="<?= BASE_URL; ?>/contacto.php" class="footer-link">Contacto</a></li>
                    <?php if (!$usuarioLogueado): ?>
                        <li><a href="<?= BASE_URL; ?>/login.php" class="footer-link">Iniciar Sesión</a></li>
                        <li><a href="<?= BASE_URL; ?>/registro.php" class="footer-link">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-success mb-3 fw-semibold">Servicios</h6>
                <ul class="list-unstyled small">
                    <li><a href="#" class="footer-link">Paseo de Perros</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-success mb-3 fw-semibold">Soporte</h6>
                <ul class="list-unstyled small">
                    <li><a href="#" class="footer-link">Centro de Ayuda</a></li>
                    <li><a href="#" class="footer-link">Preguntas Frecuentes</a></li>
                    <li><a href="#" class="footer-link">Política de Privacidad</a></li>
                    <li><a href="#" class="footer-link">Términos de Servicio</a></li>
                    <li><a href="#" class="footer-link">Reportar Problema</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-success mb-3 fw-semibold">Contacto</h6>
                <ul class="list-unstyled small">
                    <li><i class="fas fa-map-marker-alt text-success me-2"></i> Asunción, Paraguay</li>
                    <li><i class="fas fa-phone text-success me-2"></i> +595 981 123 456</li>
                    <li><i class="fas fa-envelope text-success me-2"></i> info@jaguata.com</li>
                    <li><i class="fas fa-clock text-success me-2"></i> 24/7 Disponible</li>
                </ul>
            </div>
        </div>

        <hr class="border-success-subtle my-4">

        <div class="row align-items-center">
            <div class="col-md-6 small text-muted">
                &copy; <?= date('Y'); ?> <strong>Jaguata</strong>. Todos los derechos reservados.
            </div>
            <div class="col-md-6 small text-md-end text-muted">
                Hecho con <i class="fas fa-heart text-danger"></i> en Paraguay.
            </div>
        </div>
    </div>
</footer>

<!-- Scroll Top -->
<button id="back-to-top" class="btn btn-success position-fixed bottom-0 end-0 m-4 rounded-circle shadow-lg"
    style="display:none; z-index:9999;" title="Volver arriba">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="<?= ASSETS_URL; ?>/js/main.js"></script>

<script>
    /* Scroll top */
    const btnTop = document.getElementById('back-to-top');
    window.addEventListener('scroll', () => {
        btnTop.style.display = (window.scrollY > 200) ? 'block' : 'none';
    });
    btnTop.addEventListener('click', () => window.scrollTo({
        top: 0,
        behavior: 'smooth'
    }));

    /* SweetAlert global */
    <?php if (!empty($_SESSION['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: '¡Listo!',
            text: '<?= addslashes($_SESSION['success']) ?> 🐾',
            showConfirmButton: false,
            timer: 2500,
            background: '#f6f9f7'
        });
    <?php unset($_SESSION['success']);
    endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Ups...',
            text: '<?= addslashes($_SESSION['error']) ?>',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#3c6255'
        });
    <?php unset($_SESSION['error']);
    endif; ?>
</script>

<style>
    .footer-jaguata {
        background: #f6f9f7;
        color: #3c6255;
        border-top: 3px solid #20c997;
    }

    .footer-link {
        color: #3c6255;
        text-decoration: none;
        transition: all .3s ease;
    }

    .footer-link:hover {
        color: #20c997;
        text-decoration: underline;
    }

    .social-links a:hover {
        color: #20c997 !important;
    }

    hr {
        opacity: .2;
    }
</style>

</body>

</html>