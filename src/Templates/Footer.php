    </main>
    </div>

    <!-- Footer -->
    <footer class="footer-jaguata mt-5 pt-5 pb-3">
        <div class="container">
            <div class="row gy-4">
                <!-- Marca -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="60" class="rounded-circle me-2 shadow-sm">
                        <h5 class="fw-bold text-success m-0">Jaguata</h5>
                    </div>
                    <p class="small text-muted">
                        Conectamos dueños de mascotas con paseadores profesionales en Paraguay.
                        Cuidamos de tu mejor amigo con amor, confianza y responsabilidad 🐾
                    </p>
                    <div class="social-links mt-3">
                        <a href="#" class="text-success me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-success me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-success me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-success me-3"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Enlaces -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-success fw-semibold mb-3">Enlaces</h6>
                    <ul class="list-unstyled small">
                        <li><a href="<?= BASE_URL; ?>" class="footer-link">Inicio</a></li>
                        <li><a href="<?= BASE_URL; ?>/sobre_nosotros.php" class="footer-link">Sobre Nosotros</a></li>
                        <li><a href="<?= BASE_URL; ?>/contacto.php" class="footer-link">Contacto</a></li>
                    </ul>
                </div>

                <!-- Servicios -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-success fw-semibold mb-3">Servicios</h6>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="footer-link">Paseo de Perros</a></li>
                    </ul>
                </div>

                <!-- Soporte -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-success fw-semibold mb-3">Soporte</h6>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="footer-link">Centro de Ayuda</a></li>
                        <li><a href="#" class="footer-link">Preguntas Frecuentes</a></li>
                        <li><a href="#" class="footer-link">Política de Privacidad</a></li>
                        <li><a href="#" class="footer-link">Términos de Servicio</a></li>
                    </ul>
                </div>

                <!-- Contacto -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-success fw-semibold mb-3">Contacto</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-map-marker-alt text-success me-2"></i> Asunción, Paraguay</li>
                        <li><i class="fas fa-phone text-success me-2"></i> +595 981 123 456</li>
                        <li><i class="fas fa-envelope text-success me-2"></i> info@jaguata.com</li>
                    </ul>
                </div>
            </div>

            <hr class="my-4 border-success-subtle">
            <div class="d-flex justify-content-between align-items-center flex-wrap small text-muted">
                <div>&copy; <?= date('Y'); ?> <strong>Jaguata</strong>. Todos los derechos reservados.</div>
                <div>Hecho con <i class="fas fa-heart text-danger"></i> en Paraguay.</div>
            </div>
        </div>
    </footer>

    <!-- Scroll Top -->
    <button id="back-to-top" class="btn btn-success position-fixed bottom-0 end-0 m-4 rounded-circle shadow-lg"
        style="display:none; z-index:9999;" title="Volver arriba">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="<?= ASSETS_URL; ?>/js/main.js"></script>

    <script>
        // === Scroll top ===
        const btnTop = document.getElementById('back-to-top');
        window.addEventListener('scroll', () => {
            btnTop.style.display = (window.scrollY > 200) ? 'block' : 'none';
        });
        btnTop.addEventListener('click', () => window.scrollTo({
            top: 0,
            behavior: 'smooth'
        }));

        // === Sidebar toggle ===
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) toggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    </script>
    </body>

    </html>