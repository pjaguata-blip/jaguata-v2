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
$ALLOWED_ROLES = ['dueno', 'paseador'];
$COOLDOWN_SECONDS = 30;

if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRolSeguro() ?? 'dueno';
    header('Location: ' . AppConfig::getBaseUrl() . "/features/{$rol}/Dashboard.php");
    exit;
}

$inicioUrl = AppConfig::getBaseUrl();
$panelUrl  = null;

$error   = Session::getError();
$success = Session::getSuccess();
$old     = Session::get('registro_old', [
    'rol' => 'dueno',
    'nombre' => '',
    'email' => '',
    'telefono' => '',
    'acepto_terminos' => false,
]);
Session::set('registro_old', null);

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hp = $_POST['website'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    $old = [
        'rol' => $_POST['rol'] ?? 'dueno',
        'nombre' => trim($_POST['nombre'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'acepto_terminos' => isset($_POST['acepto_terminos']),
    ];
    Session::set('registro_old', $old);

    $errores = [];
    if (!Validaciones::verificarCSRF($csrf)) $errores['csrf'] = 'Token CSRF inválido.';
    if (!empty($hp)) $errores['hp'] = 'No se pudo procesar el formulario.';

    $last = (int) Session::get('register_last_ts', 0);
    if ($last && (time() - $last) < $COOLDOWN_SECONDS) {
        $rest = $COOLDOWN_SECONDS - (time() - $last);
        $errores['cooldown'] = "Aguardá {$rest}s antes de volver a intentar.";
    }

    $rol = in_array($old['rol'], $ALLOWED_ROLES, true) ? $old['rol'] : 'dueno';
    $nombre = $old['nombre'];
    $email = $old['email'];
    $telefono = $old['telefono'];
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['confirm_password'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) < 2) $errores['nombre'] = 'El nombre es muy corto.';
    if (!Validaciones::validarEmail($email)) $errores['email'] = 'Correo inválido.';
    if ($telefono !== '' && !preg_match('/^\d{3,4}-?\d{3}-?\d{3,4}$/', $telefono)) $errores['telefono'] = 'Formato inválido (0981-123-456).';
    if ($pass === '' || mb_strlen($pass) < 8) $errores['pass'] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($pass !== $pass2) $errores['confirm'] = 'Las contraseñas no coinciden.';
    if (!$old['acepto_terminos']) $errores['terminos'] = 'Debes aceptar los términos.';

    if (empty($errores)) {
        $usuarioModel = new Usuario();
        $ya = $usuarioModel->getByEmail($email);
        if ($ya) $errores['email'] = 'Este email ya está registrado.';
    }

    if (!empty($errores)) {
        Session::setError(implode(' | ', array_values($errores)));
        header('Location: ' . $RUTA_SELF);
        exit;
    }

    try {
        $usuarioModel = new Usuario();
        $usuarioId = $usuarioModel->createUsuario([
            'nombre'   => $nombre,
            'email'    => $email,
            'password' => $pass,
            'rol'      => $rol,
            'telefono' => $telefono,
        ]);

        if ($rol === 'paseador' && class_exists(Paseador::class)) {
            $paseadorModel = new Paseador();
            $paseadorModel->create([
                'paseador_id' => $usuarioId,
                'experiencia' => '',
                'zona' => json_encode([]),
                'precio_hora' => 0,
                'disponibilidad' => 1,
                'calificacion' => 0,
                'total_paseos' => 0
            ]);
        }

        if (class_exists(Historial::class)) {
            $historialModel = new Historial();
            $historialModel->registrarActividad($usuarioId, 0, 0);
        }

        $usuario = $usuarioModel->getById($usuarioId);
        Session::login($usuario);
        Session::set('register_last_ts', time());
        Session::setSuccess('¡Bienvenido! Tu cuenta ha sido creada 🐾');
        header('Location: ' . AppConfig::getBaseUrl() . "/features/{$rol}/Dashboard.php");
        exit;
    } catch (Throwable $e) {
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
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?= AppConfig::getAssetsUrl(); ?>/images/logo.png" alt="Jaguata" height="60" class="mb-3">
                        <h2 class="fw-bold text-success">Crear Cuenta</h2>
                        <p class="text-muted">Únete a la comunidad de Jaguata</p>
                    </div>

                    <form method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= Validaciones::generarCSRF(); ?>">
                        <div style="position:absolute;left:-9999px;"><input type="text" name="website" autocomplete="off"></div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Tipo de Cuenta</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="w-100">
                                        <input type="radio" class="btn-check" name="rol" id="dueno" value="dueno" <?= ($old['rol'] ?? 'dueno') === 'dueno' ? 'checked' : ''; ?>>
                                        <div class="card p-3 text-center border-2 <?= ($old['rol'] ?? 'dueno') === 'dueno' ? 'border-success' : 'border-light'; ?>">
                                            <i class="fas fa-paw fa-2x text-success mb-2"></i>
                                            <h6>Dueño</h6>
                                            <small class="text-muted">Busco paseadores</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <label class="w-100">
                                        <input type="radio" class="btn-check" name="rol" id="paseador" value="paseador" <?= ($old['rol'] ?? '') === 'paseador' ? 'checked' : ''; ?>>
                                        <div class="card p-3 text-center border-2 <?= ($old['rol'] ?? '') === 'paseador' ? 'border-success' : 'border-light'; ?>">
                                            <i class="fas fa-walking fa-2x text-success mb-2"></i>
                                            <h6>Paseador</h6>
                                            <small class="text-muted">Ofrezco paseos</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nombre" class="form-label"><i class="fas fa-user me-1"></i> Nombre</label>
                            <input type="text" class="form-control form-control-lg" id="nombre" name="nombre"
                                value="<?= htmlspecialchars($old['nombre'] ?? '') ?>" required placeholder="Tu nombre completo">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="fas fa-envelope me-1"></i> Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email"
                                value="<?= htmlspecialchars($old['email'] ?? '') ?>" required placeholder="tu@email.com">
                        </div>

                        <div class="mb-3">
                            <label for="telefono" class="form-label"><i class="fas fa-phone me-1"></i> Teléfono</label>
                            <input type="tel" class="form-control form-control-lg" id="telefono" name="telefono"
                                value="<?= htmlspecialchars($old['telefono'] ?? '') ?>" placeholder="0981-123-456">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="fas fa-lock me-1"></i> Contraseña</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required placeholder="Mínimo 8 caracteres">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><i class="fas fa-lock me-1"></i> Confirmar Contraseña</label>
                            <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required placeholder="Repite tu contraseña">
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="acepto_terminos" name="acepto_terminos" <?= !empty($old['acepto_terminos']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="acepto_terminos">
                                Acepto los <a href="<?= AppConfig::getBaseUrl(); ?>/terminos.php" target="_blank" class="text-success">términos y condiciones</a>
                                y la <a href="<?= AppConfig::getBaseUrl(); ?>/privacidad.php" target="_blank" class="text-success">política de privacidad</a>.
                            </label>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                                <i class="fas fa-user-plus me-2"></i> Crear Cuenta
                            </button>
                        </div>

                        <p class="text-center mb-0">¿Ya tienes cuenta?
                            <a href="<?= AppConfig::getBaseUrl(); ?>/login.php" class="text-success fw-semibold">Inicia sesión</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if ($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Ups...',
            text: '<?= addslashes($error) ?>',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#3c6255'
        });
    <?php endif; ?>

    <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: '¡Listo!',
            text: '<?= addslashes($success) ?>',
            showConfirmButton: false,
            timer: 2500,
            background: '#f6f9f7'
        });
    <?php endif; ?>
</script>

<?php include __DIR__ . '/../src/Templates/Footer.php'; ?>