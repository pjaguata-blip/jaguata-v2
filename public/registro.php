<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;
use Jaguata\Models\Paseador;
use Jaguata\Models\Historial;

AppConfig::init();

$RUTA_SELF = AppConfig::getBaseUrl() . '/registro.php';
$ALLOWED_ROLES = ['dueno', 'paseador']; // (admin) solo por backend
$COOLDOWN_SECONDS = 30; // ventana anti reenvío rápido

// Si ya está logueado -> Dashboard por rol
if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRolSeguro() ?? 'dueno';
    header('Location: ' . AppConfig::getBaseUrl() . "/features/{$rol}/Dashboard.php");
    exit;
}

// ===== Navegación (Inicio / Panel si aplica) =====
$inicioUrl = AppConfig::getBaseUrl();
$panelUrl  = null; // no hay panel si no está logueado

// ===== Flash & OLD =====
$error   = Session::getError();
$success = Session::getSuccess();
$old     = Session::get('registro_old', [
    'rol'                => 'dueno',
    'nombre'             => '',
    'email'              => '',
    'telefono'           => '',
    'acepto_terminos'    => false,
]);

// limpiar old para no persistir siempre
Session::set('registro_old', null);

// ===== POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Anti-bot simple (honeypot)
    $hp = $_POST['website'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    // Capturar old para repoblar si falla
    $old = [
        'rol'             => $_POST['rol'] ?? 'dueno',
        'nombre'          => trim($_POST['nombre'] ?? ''),
        'email'           => trim($_POST['email'] ?? ''),
        'telefono'        => trim($_POST['telefono'] ?? ''),
        'acepto_terminos' => isset($_POST['acepto_terminos']),
    ];
    Session::set('registro_old', $old);

    // Validaciones base
    $errores = [];

    if (!Validaciones::verificarCSRF($csrf)) {
        $errores['csrf'] = 'Token CSRF inválido. Actualizá la página e intentá de nuevo.';
    }
    if (!empty($hp)) {
        $errores['hp'] = 'No se pudo procesar el formulario.';
    }

    // Rate-limit (por sesión)
    $last = (int) Session::get('register_last_ts', 0);
    if ($last && (time() - $last) < $COOLDOWN_SECONDS) {
        $rest = $COOLDOWN_SECONDS - (time() - $last);
        $errores['cooldown'] = "Por favor aguardá {$rest}s para volver a intentar.";
    }

    // Campos
    $rol      = in_array($old['rol'], $ALLOWED_ROLES, true) ? $old['rol'] : 'dueno';
    $nombre   = $old['nombre'];
    $email    = $old['email'];
    $telefono = $old['telefono'];
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['confirm_password'] ?? '';

    // Validaciones semánticas (podés delegar a Validaciones::validarDatosUsuario si querés)
    if ($nombre === '' || mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
        $errores['nombre'] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }
    if ($email === '' || !Validaciones::validarEmail($email)) {
        $errores['email'] = 'El correo no es válido.';
    }
    if ($telefono !== '' && !preg_match('/^\d{3,4}-?\d{3}-?\d{3,4}$/', $telefono)) {
        // formato flexible: 0981-123-456 o 0981123456
        $errores['telefono'] = 'Formato de teléfono no válido (ej: 0981-123-456).';
    }
    if ($pass === '' || mb_strlen($pass) < 8) {
        $errores['pass'] = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        // Fuerte: al menos 1 may., 1 min., 1 número y 1 símbolo
        $strong = preg_match('/[A-Z]/', $pass) && preg_match('/[a-z]/', $pass) &&
            preg_match('/\d/', $pass)   && preg_match('/[^A-Za-z0-9]/', $pass);
        if (!$strong) {
            $errores['pass'] = 'Usá mayúsculas, minúsculas, números y un símbolo.';
        }
    }
    if ($pass !== $pass2) {
        $errores['confirm_password'] = 'Las contraseñas no coinciden.';
    }
    if (!$old['acepto_terminos']) {
        $errores['acepto_terminos'] = 'Debes aceptar los términos y condiciones.';
    }

    // Email único
    if (empty($errores)) {
        $usuarioModel = new Usuario();
        $ya = $usuarioModel->getByEmail($email);
        if ($ya) {
            $errores['email'] = 'Este email ya está registrado.';
        }
    }

    if (!empty($errores)) {
        // Consolidar errores a un bloque (y marcar inválidos por campo en el form)
        Session::setError(implode('<br>', array_values($errores)));
        header('Location: ' . $RUTA_SELF);
        exit;
    }

    // Crear usuario
    try {
        $usuarioModel = new Usuario();
        $usuarioId = $usuarioModel->createUsuario([
            'nombre'   => $nombre,
            'email'    => $email,
            'password' => $pass,    // el modelo lo guarda en 'pass' hasheado
            'rol'      => $rol,
            'telefono' => $telefono,
        ]);

        // Si es paseador, crear perfil extendido (si corresponde a tu modelo de dominio)
        if ($rol === 'paseador' && class_exists(Paseador::class)) {
            $paseadorModel = new Paseador();
            $paseadorModel->create([
                'paseador_id'    => $usuarioId,
                'experiencia'    => '',
                'zona'           => json_encode([], JSON_UNESCAPED_UNICODE),
                'precio_hora'    => 0,
                'disponibilidad' => 1,
                'calificacion'   => 0,
                'total_paseos'   => 0
            ]);
        }

        // Historial (si lo tenés)
        if (class_exists(Historial::class)) {
            $historialModel = new Historial();
            $historialModel->registrarActividad($usuarioId, 0, 0);
        }

        // Login automático
        $usuario = $usuarioModel->getById($usuarioId);
        Session::login($usuario);

        Session::set('register_last_ts', time());
        Session::setSuccess('¡Bienvenido! Tu cuenta ha sido creada.');

        header('Location: ' . AppConfig::getBaseUrl() . "/features/{$rol}/Dashboard.php");
        exit;
    } catch (\Throwable $e) {
        error_log('Registro error: ' . $e->getMessage());
        Session::setError('Error al crear la cuenta. Intentá más tarde.');
        header('Location: ' . $RUTA_SELF);
        exit;
    }
}

include __DIR__ . '/../src/Templates/Header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-end mb-3 gap-2">
        <a href="<?= htmlspecialchars($inicioUrl) ?>" class="btn btn-outline-secondary">
            <i class="fa-solid fa-house me-1"></i> Ir al inicio
        </a>
        <?php if ($panelUrl): // no se muestra si no hay sesión 
        ?>
            <a href="<?= htmlspecialchars($panelUrl) ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-gauge-high me-1"></i> Panel principal
            </a>
        <?php endif; ?>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Mensajes -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?= AppConfig::getAssetsUrl(); ?>/images/logo.png" alt="Jaguata" height="60" class="mb-3">
                        <h2 class="fw-bold text-primary">Crear Cuenta</h2>
                        <p class="text-muted">Únete a la comunidad de Jaguata</p>
                    </div>

                    <form method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= Validaciones::generarCSRF(); ?>">
                        <!-- Honeypot -->
                        <div style="position:absolute;left:-9999px;top:-9999px;">
                            <label>Si ves este campo, no lo completes:
                                <input type="text" name="website" tabindex="-1" autocomplete="off">
                            </label>
                        </div>

                        <!-- Tipo de cuenta -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Tipo de Cuenta</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="rol" id="dueno" value="dueno"
                                            <?= ($old['rol'] ?? 'dueno') === 'dueno' ? 'checked' : '' ?>>
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
                                            <?= ($old['rol'] ?? '') === 'paseador' ? 'checked' : '' ?>>
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
                        </div>

                        <!-- Nombre -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label"><i class="fas fa-user me-1"></i>Nombre Completo</label>
                            <input type="text" class="form-control form-control-lg" id="nombre" name="nombre"
                                value="<?= htmlspecialchars($old['nombre'] ?? '') ?>" required autocomplete="name"
                                placeholder="Tu nombre completo" minlength="2" maxlength="100">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="fas fa-envelope me-1"></i>Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email"
                                value="<?= htmlspecialchars($old['email'] ?? '') ?>" required autocomplete="email"
                                placeholder="tu@email.com" maxlength="100">
                        </div>

                        <!-- Teléfono -->
                        <div class="mb-3">
                            <label for="telefono" class="form-label"><i class="fas fa-phone me-1"></i>Teléfono</label>
                            <input type="tel" class="form-control form-control-lg" id="telefono" name="telefono"
                                value="<?= htmlspecialchars($old['telefono'] ?? '') ?>" autocomplete="tel"
                                placeholder="0981-123-456" maxlength="20">
                            <div class="form-text">Formato sugerido: 0981-123-456</div>
                        </div>

                        <!-- Contraseña -->
                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="fas fa-lock me-1"></i>Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="password" name="password"
                                    required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Usá mayúsculas, minúsculas, números y símbolos.</div>
                        </div>

                        <!-- Confirmar contraseña -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><i class="fas fa-lock me-1"></i>Confirmar Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password"
                                    required autocomplete="new-password" placeholder="Repite tu contraseña">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Términos -->
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="acepto_terminos" name="acepto_terminos"
                                <?= !empty($old['acepto_terminos']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="acepto_terminos">
                                Acepto los <a href="<?= AppConfig::getBaseUrl(); ?>/terminos.php" target="_blank" class="text-primary">términos y condiciones</a>
                                y la <a href="<?= AppConfig::getBaseUrl(); ?>/privacidad.php" target="_blank" class="text-primary">política de privacidad</a>
                            </label>
                        </div>

                        <!-- Botón -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-user-plus me-2"></i> Crear Cuenta
                            </button>
                        </div>

                        <!-- Enlaces -->
                        <div class="text-center">
                            <p class="mb-0">¿Ya tienes cuenta?
                                <a href="<?= AppConfig::getBaseUrl(); ?>/login.php" class="text-primary fw-bold text-decoration-none">
                                    Inicia sesión aquí
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Beneficios -->
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
        transition: all .3s ease;
    }

    .form-control-lg:focus {
        border-color: #2E7D32;
        box-shadow: 0 0 0 .2rem rgba(46, 125, 50, .25);
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
        transition: all .3s ease;
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
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');

        function toggle(input, btn) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            const icon = btn.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }

        togglePassword.addEventListener('click', () => toggle(passwordInput, togglePassword));
        toggleConfirmPassword.addEventListener('click', () => toggle(confirmPasswordInput, toggleConfirmPassword));

        form.addEventListener('submit', function(e) {
            // Validaciones rápidas de UX
            const pass = passwordInput.value;
            const pass2 = confirmPasswordInput.value;
            const acepto = document.getElementById('acepto_terminos').checked;

            if (pass !== pass2) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return;
            }
            if (!acepto) {
                e.preventDefault();
                alert('Debes aceptar los términos y condiciones');
                return;
            }
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Creando cuenta...';
            submitBtn.disabled = true;
        });
    });
</script>

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>