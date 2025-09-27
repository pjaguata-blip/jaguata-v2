<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;
use Jaguata\Models\Paseador;
use Jaguata\Models\Historial;
use Jaguata\Services\DatabaseService;

// Inicializar configuración
AppConfig::init();

// Verificar si el usuario ya está logueado
if (Session::isLoggedIn()) {
    $rol = $_SESSION['rol'];
    header('Location: ' . AppConfig::getBaseUrl() . '/features/' . $rol . '/Dashboard.php');
    exit;
}

$titulo = 'Registrarse - Jaguata';
$error = '';
$success = '';
$errores = [];
$acepto_terminos = false; // 🔹 Inicializar siempre para evitar warning

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $acepto_terminos = isset($_POST['acepto_terminos']); // 🔹 se reasigna si se marcó el checkbox

    // Validar datos
    $validacion = Validaciones::validarDatosUsuario([
        'nombre' => $nombre,
        'email' => $email,
        'pass' => $password,
        'telefono' => $telefono,
        'rol' => $rol
    ]);

    if (!$validacion['valido']) {
        $errores = $validacion['errores'];
    } elseif ($password !== $confirm_password) {
        $errores['confirm_password'] = 'Las contraseñas no coinciden';
    } elseif (!$acepto_terminos) {
        $errores['acepto_terminos'] = 'Debes aceptar los términos y condiciones';
    } else {
        // Verificar si el email ya existe
        $usuarioModel = new Usuario();
        $usuarioExistente = $usuarioModel->getByEmail($email);

        if ($usuarioExistente) {
            $errores['email'] = 'Este email ya está registrado';
        } else {
            // Crear usuario
            try {
                $usuarioId = $usuarioModel->create([
                    'nombre' => $nombre,
                    'email' => $email,
                    'pass' => password_hash($password, PASSWORD_DEFAULT),
                    'rol' => $rol,
                    'telefono' => $telefono
                ]);

                // Si es paseador, crear perfil de paseador
                if ($rol === 'paseador') {
                    $paseadorModel = new Paseador();
                    $paseadorModel->create([
                        'paseador_id' => $usuarioId,
                        'experiencia' => '',
                        'zona' => '',
                        'precio_hora' => 0,
                        'disponibilidad' => 1,
                        'calificacion' => 0,
                        'total_paseos' => 0
                    ]);
                }

                // Registrar actividad en historial
                $historialModel = new Historial();
                $historialModel->registrarActividad($usuarioId, 0, 0);

                // Login automático
                $usuario = $usuarioModel->find($usuarioId);
                Session::login($usuario);

                // Redirigir según el rol
                header('Location: ' . AppConfig::getBaseUrl() . '/features/' . $rol . '/Dashboard.php');
                exit;
            } catch (Exception $e) {
                $error = 'Error al crear la cuenta: ' . $e->getMessage();
            }
        }
    }
}

// Obtener mensajes flash
$error = $error ?: ($_SESSION['error'] ?? '');
$success = $success ?: ($_SESSION['success'] ?? '');
$rol = Session::getUsuarioRol();
?>

<?php include __DIR__ . '/../src/Templates/Header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <!-- Logo y título -->
                    <div class="text-center mb-4">
                        <img src="<?php echo AppConfig::getAssetsUrl(); ?>/images/logo.png" alt="Jaguata" height="60" class="mb-3">
                        <h2 class="fw-bold text-primary">Crear Cuenta</h2>
                        <p class="text-muted">Únete a la comunidad de Jaguata</p>
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

                    <!-- Formulario de registro -->
                    <form method="POST" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>">

                        <!-- Tipo de cuenta -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Tipo de Cuenta</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="rol" id="dueno" value="dueno"
                                            <?php echo ($rol === 'dueno' || !$rol) ? 'checked' : ''; ?>>
                                        <label class="form-check-label w-100" for="dueno">
                                            <div class="card h-100 text-center p-3">
                                                <i class="fas fa-paw fa-2x text-primary mb-2"></i>
                                                <h6 class="mb-1">Dueño de Mascota</h6>
                                                <small class="text-muted">Busco paseadores para mi mascota</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="rol" id="paseador" value="paseador"
                                            <?php echo $rol === 'paseador' ? 'checked' : ''; ?>>
                                        <label class="form-check-label w-100" for="paseador">
                                            <div class="card h-100 text-center p-3">
                                                <i class="fas fa-walking fa-2x text-success mb-2"></i>
                                                <h6 class="mb-1">Paseador</h6>
                                                <small class="text-muted">Ofrezco servicios de paseo</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php if (isset($errores['rol'])): ?>
                                <div class="text-danger small mt-1"><?php echo $errores['rol']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Nombre -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-user me-1"></i>Nombre Completo
                            </label>
                            <input type="text"
                                class="form-control form-control-lg <?php echo isset($errores['nombre']) ? 'is-invalid' : ''; ?>"
                                id="nombre"
                                name="nombre"
                                value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                                required
                                autocomplete="name"
                                placeholder="Tu nombre completo">
                            <?php if (isset($errores['nombre'])): ?>
                                <div class="invalid-feedback"><?php echo $errores['nombre']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email
                            </label>
                            <input type="email"
                                class="form-control form-control-lg <?php echo isset($errores['email']) ? 'is-invalid' : ''; ?>"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                required
                                autocomplete="email"
                                placeholder="tu@email.com">
                            <?php if (isset($errores['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errores['email']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Teléfono -->
                        <div class="mb-3">
                            <label for="telefono" class="form-label">
                                <i class="fas fa-phone me-1"></i>Teléfono
                            </label>
                            <input type="tel"
                                class="form-control form-control-lg <?php echo isset($errores['telefono']) ? 'is-invalid' : ''; ?>"
                                id="telefono"
                                name="telefono"
                                value="<?php echo htmlspecialchars($telefono ?? ''); ?>"
                                autocomplete="tel"
                                placeholder="0981-123-456">
                            <div class="form-text">Formato: 0981-123-456</div>
                            <?php if (isset($errores['telefono'])): ?>
                                <div class="invalid-feedback"><?php echo $errores['telefono']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Contraseña -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Contraseña
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control form-control-lg <?php echo isset($errores['pass']) ? 'is-invalid' : ''; ?>"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Mínimo 8 caracteres">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Mínimo 8 caracteres, incluyendo mayúsculas, minúsculas, números y símbolos
                            </div>
                            <?php if (isset($errores['pass'])): ?>
                                <div class="invalid-feedback"><?php echo $errores['pass']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Confirmar contraseña -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Confirmar Contraseña
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control form-control-lg <?php echo isset($errores['confirm_password']) ? 'is-invalid' : ''; ?>"
                                    id="confirm_password"
                                    name="confirm_password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Repite tu contraseña">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errores['confirm_password'])): ?>
                                <div class="invalid-feedback"><?php echo $errores['confirm_password']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Términos y condiciones -->
                        <div class="mb-4 form-check">
                            <input type="checkbox"
                                class="form-check-input <?php echo isset($errores['acepto_terminos']) ? 'is-invalid' : ''; ?>"
                                id="acepto_terminos"
                                name="acepto_terminos"
                                <?php echo $acepto_terminos ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="acepto_terminos">
                                Acepto los <a href="<?php echo AppConfig::getBaseUrl(); ?>/terminos.php" target="_blank" class="text-primary">términos y condiciones</a>
                                y la <a href="<?php echo AppConfig::getBaseUrl(); ?>/privacidad.php" target="_blank" class="text-primary">política de privacidad</a>
                            </label>
                            <?php if (isset($errores['acepto_terminos'])): ?>
                                <div class="invalid-feedback"><?php echo $errores['acepto_terminos']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Botón de registro -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Crear Cuenta
                            </button>
                        </div>

                        <!-- Enlaces adicionales -->
                        <div class="text-center">
                            <p class="mb-0">
                                ¿Ya tienes cuenta?
                                <a href="<?php echo AppConfig::getBaseUrl(); ?>/login.php" class="text-primary fw-bold text-decoration-none">
                                    Inicia sesión aquí
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
                            <small class="text-muted">Datos Seguros</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-user-check text-primary fa-2x mb-2"></i>
                            <small class="text-muted">Verificación</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-heart text-primary fa-2x mb-2"></i>
                            <small class="text-muted">Comunidad</small>
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

    .form-check .card {
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .form-check-input:checked+.form-check-label .card {
        border-color: #2E7D32;
        background-color: #f8f9ff;
    }

    .form-check:hover .card {
        border-color: #2E7D32;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const aceptoTerminos = document.getElementById('acepto_terminos').checked;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return;
            }

            if (!aceptoTerminos) {
                e.preventDefault();
                alert('Debes aceptar los términos y condiciones');
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando cuenta...';
            submitBtn.disabled = true;
        });

        const passwordStrengthInput = document.getElementById('password');
        passwordStrengthInput.addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);
            updatePasswordStrengthIndicator(strength);
        });

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }

        function updatePasswordStrengthIndicator(strength) {
            // Aquí podrías mostrar una barra de fuerza de contraseña
        }
    });
</script>

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>