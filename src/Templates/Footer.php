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

<!-- Footer -->
<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-lg-4 mb-4">
                <h5 class="text-primary mb-3">
                    <img src="<?php echo ASSETS_URL; ?>/images/logo-white.png" alt="Jaguata" height="30" class="me-2">

                </h5>
                <p class="text-muted">
                    Conectamos dueños de mascotas con paseadores profesionales en Paraguay.
                    Cuidamos de tu mejor amigo con amor y responsabilidad.
                </p>
                <div class="social-links">
                    <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-primary mb-3">Enlaces Rápidos</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo $inicioUrl; ?>" class="text-muted text-decoration-none">Inicio</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/sobre_nosotros.php" class="text-muted text-decoration-none">Sobre Nosotros</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/contacto.php" class="text-muted text-decoration-none">Contacto</a></li>

                    <?php if (!$usuarioLogueado): ?>
                        <li><a href="<?php echo BASE_URL; ?>/login.php" class="text-muted text-decoration-none">Iniciar Sesión</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/registro.php" class="text-muted text-decoration-none">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-primary mb-3">Servicios</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-muted text-decoration-none">Paseo de Perros</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-primary mb-3">Soporte</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-muted text-decoration-none">Centro de Ayuda</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Preguntas Frecuentes</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Política de Privacidad</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Términos de Servicio</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Reportar Problema</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-primary mb-3">Contacto</h6>
                <ul class="list-unstyled">
                    <li class="d-flex align-items-center mb-2">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                        <span class="text-muted small">Asunción, Paraguay</span>
                    </li>
                    <li class="d-flex align-items-center mb-2">
                        <i class="fas fa-phone me-2 text-primary"></i>
                        <span class="text-muted small">+595 981 123 456</span>
                    </li>
                    <li class="d-flex align-items-center mb-2">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        <span class="text-muted small">info@jaguata.com</span>
                    </li>
                    <li class="d-flex align-items-center mb-2">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        <span class="text-muted small">24/7 Disponible</span>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="my-4">

        <!-- Copyright -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-muted small mb-0">
                    &copy; <?php echo date('Y'); ?> Jaguata. Todos los derechos reservados.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="text-muted small mb-0">
                    Hecho con Amor en Paraguay <i class="fas fa-heart text-danger"></i>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="back-to-top" class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle"
    style="display: none; z-index: 1000;" title="Volver arriba">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>

<!-- Additional JS -->
<?php if (isset($js_adicional)): ?>
    <?php foreach ($js_adicional as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Inline JS -->
<?php if (isset($js_inline)): ?>
    <script>
        <?php echo $js_inline; ?>
    </script>
<?php endif; ?>

<!-- Analytics -->
<?php if (defined('GOOGLE_ANALYTICS_ID') && GOOGLE_ANALYTICS_ID): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GOOGLE_ANALYTICS_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', '<?php echo GOOGLE_ANALYTICS_ID; ?>');
    </script>
<?php endif; ?>

<!-- Error Tracking -->
<?php if (defined('SENTRY_DSN') && SENTRY_DSN): ?>
    <script src="https://browser.sentry-cdn.com/7.0.0/bundle.min.js"></script>
    <script>
        Sentry.init({
            dsn: '<?php echo SENTRY_DSN; ?>',
            environment: '<?php echo DEBUG_MODE ? "development" : "production"; ?>'
        });
    </script>
<?php endif; ?>

</body>

</html>