<?php
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Helpers/Validaciones.php';
require_once __DIR__ . '/../src/Models/Usuario.php';
require_once __DIR__ . '/../vendor/autoload.php';
// Inicializar configuración
use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;
use Jaguata\Services\DatabaseService;
use Jaguata\Controllers\AuthController;

AppConfig::init();

// Verificar si el usuario ya está logueado
if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRol();
    header('Location: ' . BASE_URL . '/features/' . $rol . '/Dashboard.php');
    exit;
}

$titulo = 'Iniciar Sesión - Jaguata';
$error = '';
$success = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // Validar datos
    $validacionEmail = Validaciones::validarEmail($email);
    $validacionPassword = Validaciones::validarPassword($password);

    if (!$validacionEmail['valido']) {
        $error = $validacionEmail['mensaje'];
    } elseif (!$validacionPassword['valido']) {
        $error = $validacionPassword['mensaje'];
    } else {
        // Intentar autenticar
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->authenticate($email, $password);

        if ($usuario) {
            // Login exitoso
            $usuario['remember_me'] = $remember_me;
            Session::login($usuario);

            // Redirigir según el rol
            $rol = $usuario['rol'];
            header('Location: ' . BASE_URL . '/features/' . $rol . '/Dashboard.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas. Verifica tu email y contraseña.';
        }
    }
}

// Obtener mensajes flash
$error = $error ?: Session::getError();
$success = $success ?: Session::getSuccess();
?>

<?php include __DIR__ . '/../src/Templates/Header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <!-- Logo y título -->
                    <div class="text-center mb-4">
                        <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="Jaguata" height="60" class="mb-3">
                        <h2 class="fw-bold text-primary">Iniciar Sesión</h2>
                        <p class="text-muted">Accede a tu cuenta de Jaguata</p>
                    </div>

                    <!-- Mostrar mensajes -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario de login -->
                    <form method="POST" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo Validaciones::generarCSRF(); ?>">

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email
                            </label>
                            <input type="email"
                                class="form-control form-control-lg <?php echo $error && strpos($error, 'email') !== false ? 'is-invalid' : ''; ?>"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                required
                                autocomplete="email"
                                placeholder="tu@email.com">
                            <div class="invalid-feedback">
                                Por favor ingresa un email válido.
                            </div>
                        </div>

                        <!-- Contraseña -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Contraseña
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control form-control-lg <?php echo $error && strpos($error, 'contraseña') !== false ? 'is-invalid' : ''; ?>"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Tu contraseña">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Por favor ingresa tu contraseña.
                            </div>
                        </div>

                        <!-- Recordar sesión -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">
                                Recordar mi sesión
                            </label>
                        </div>

                        <!-- Botón de login -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </button>
                        </div>

                        <!-- Enlaces adicionales -->
                        <div class="text-center">
                            <p class="mb-2">
                                <a href="<?php echo BASE_URL; ?>/recuperar-password.php" class="text-decoration-none">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </p>
                            <p class="mb-0">
                                ¿No tienes cuenta?
                                <a href="<?php echo BASE_URL; ?>/registro.php" class="text-primary fw-bold text-decoration-none">
                                    Regístrate aquí
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="text-center mt-4">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-shield-alt text-primary fa-2x mb-2"></i>
                            <small class="text-muted">100% Seguro</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-clock text-primary fa-2x mb-2"></i>
                            <small class="text-muted">Disponible 24/7</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-star text-primary fa-2x mb-2"></i>
                            <small class="text-muted">Paseadores Verificados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border-radius: 15px;
    }

    .form-control-lg {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .form-control-lg:focus {
        border-color: #2E7D32;
        box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
    }

    .btn-lg {
        border-radius: 10px;
        padding: 12px 24px;
        font-weight: 600;
    }

    .input-group .btn {
        border-radius: 0 10px 10px 0;
    }

    .alert {
        border-radius: 10px;
        border: none;
    }

    .form-check-input:checked {
        background-color: #2E7D32;
        border-color: #2E7D32;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Por favor completa todos los campos');
                return;
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Iniciando sesión...';
            submitBtn.disabled = true;
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
</script>

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>