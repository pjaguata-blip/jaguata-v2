<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;
use Jaguata\Models\Paseador;

AppConfig::init();

$RUTA_SELF = AppConfig::getBaseUrl() . '/registro.php';

// Redirigir si ya está logueado
if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRol() ?? 'dueno';
    header('Location: ' . AppConfig::getBaseUrl() . "/features/{$rol}/Dashboard.php");
    exit;
}

$error = Session::getError();
$success = Session::getSuccess();

// Carpeta de subida
$uploadDir = __DIR__ . '/../uploads/verificaciones/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// --- Procesar formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rol = $_POST['rol'] ?? 'dueno';
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['confirm_password'] ?? '';
    $errores = [];

    // Validaciones base
    if ($nombre === '' || mb_strlen($nombre) < 3) $errores[] = 'El nombre debe tener al menos 3 caracteres.';
    if (!Validaciones::validarEmail($email)) $errores[] = 'Correo electrónico inválido.';
    if ($pass !== $pass2) $errores[] = 'Las contraseñas no coinciden.';
    if (mb_strlen($pass) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if (empty($telefono)) $errores[] = 'El teléfono es obligatorio.';

    // Aceptación de bases y condiciones
    $aceptaCondiciones = isset($_POST['acepta_condiciones']);
    if (!$aceptaCondiciones) {
        $errores[] = 'Debes aceptar las Bases y Condiciones para continuar.';
    }

    $files = ['cedula_frente', 'cedula_dorso', 'selfie', 'antecedentes'];
    $uploads = [];

    // Archivos requeridos para paseadores
    if ($rol === 'paseador') {
        foreach ($files as $f) {
            if (empty($_FILES[$f]['name'])) {
                $errores[] = "El archivo de " . str_replace('_', ' ', $f) . " es obligatorio.";
            } else {
                $ext = strtolower(pathinfo($_FILES[$f]['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $errores[] = "Formato inválido para $f (solo JPG, PNG o PDF).";
                }
            }
        }
    }

    if (empty($errores)) {
        try {
            $usuarioModel = new Usuario();
            if ($usuarioModel->getByEmail($email)) {
                $errores[] = 'El correo ya está registrado.';
            } else {
                // Subir archivos
                foreach ($files as $f) {
                    if (!empty($_FILES[$f]['name'])) {
                        $filename = uniqid($f . '_') . '.' . strtolower(pathinfo($_FILES[$f]['name'], PATHINFO_EXTENSION));
                        move_uploaded_file($_FILES[$f]['tmp_name'], $uploadDir . $filename);
                        $uploads[$f] = $filename;
                    }
                }

                // Crear usuario pendiente
                $usuarioId = $usuarioModel->createUsuario([
                    'nombre' => $nombre,
                    'email' => $email,
                    'password' => $pass,
                    'rol' => $rol,
                    'telefono' => $telefono,
                    'estado' => 'pendiente',
                    'foto_cedula_frente' => $uploads['cedula_frente'] ?? null,
                    'foto_cedula_dorso' => $uploads['cedula_dorso'] ?? null,
                    'foto_selfie' => $uploads['selfie'] ?? null,
                    'certificado_antecedentes' => $uploads['antecedentes'] ?? null,
                    'acepto_terminos' => 1,
                    'fecha_aceptacion' => date('Y-m-d H:i:s'),
                    'ip_registro' => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);

                if ($rol === 'paseador') {
                    $paseadorModel = new Paseador();
                    $paseadorModel->create([
                        'paseador_id' => $usuarioId,
                        'nombre' => $nombre,
                        'disponible' => 0,
                        'calificacion' => 0,
                        'total_paseos' => 0,
                    ]);
                }

                Session::setSuccess('¡Tu cuenta fue registrada! El administrador revisará tus documentos 🐾');
                header('Location: ' . AppConfig::getBaseUrl() . '/login.php');
                exit;
            }
        } catch (Throwable $e) {
            $errores[] = 'Ocurrió un error al registrar la cuenta.';
        }
    }

    if (!empty($errores)) {
        Session::setError(implode(' | ', $errores));
        header('Location: ' . $RUTA_SELF);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --jg-green: #3c6255;
            --jg-mint: #20c997;
            --jg-ink: #24343a;
            --jg-card: #ffffff;
        }

        /* Fondo degradado + huellitas */
        body {
            min-height: 100vh;
            background: linear-gradient(160deg, var(--jg-green) 0%, var(--jg-mint) 100%);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", "Apple Color Emoji", "Segoe UI Emoji";
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            margin: 0;
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20px 20px, rgba(255, 255, 255, .12) 6px, transparent 7px) 0 0 / 60px 60px,
                radial-gradient(circle at 50px 40px, rgba(255, 255, 255, .08) 4px, transparent 5px) 0 0 / 60px 60px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .25), rgba(0, 0, 0, .6));
            pointer-events: none;
        }

        /* Contenedor principal */
        .auth-shell {
            width: min(1150px, 96vw);
        }

        /* Card glass + layout 2 columnas */
        .auth-card {
            border: 0;
            border-radius: 22px;
            background: rgba(255, 255, 255, .9);
            backdrop-filter: saturate(140%) blur(8px);
            box-shadow: 0 18px 60px rgba(0, 0, 0, .18);
            overflow: hidden;
        }

        /* COLUMNA ILUSTRACIÓN / BENEFICIOS */
        .illustration {
            background: radial-gradient(circle at top left, rgba(255, 255, 255, .18), transparent 55%),
                linear-gradient(135deg, #3c6255 0%, #20c997 100%);
            color: #f5fbfa;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(18px, 4vw, 30px);
        }

        .illustration-inner {
            max-width: 380px;
        }

        .illustration-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(0, 0, 0, .18);
            font-size: .78rem;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .illustration-title {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1.25;
            margin-top: 14px;
            margin-bottom: 8px;
        }

        .illustration-text {
            font-size: .92rem;
            opacity: .9;
            margin-bottom: 14px;
        }

        .illustration-list {
            list-style: none;
            padding-left: 0;
            margin: 0 0 16px 0;
        }

        .illustration-list li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: .9rem;
            margin-bottom: 6px;
        }

        .illustration-list i {
            font-size: .95rem;
            margin-top: 2px;
        }

        .illustration-metrics {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .illustration-metrics-item strong {
            display: block;
            font-size: 1.1rem;
            line-height: 1.1;
        }

        .illustration-metrics-item small {
            font-size: .75rem;
            opacity: .85;
        }

        .illustration-graphic {
            display: flex;
            justify-content: center;
            margin-top: 18px;
        }

        .dog-svg {
            max-width: 420px;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 10px 22px rgba(0, 0, 0, .22));
        }

        /* Columna formulario */
        .form-pane {
            padding: clamp(18px, 4vw, 36px);
        }

        /* Logo circular */
        .logo-circle {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: #f4f7f9;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .08);
            margin: 0 auto 12px;
        }

        /* Títulos y micro-detalles */
        h2 {
            color: var(--jg-green);
            font-weight: 800;
            letter-spacing: .2px;
        }

        .text-muted {
            color: #6b7b83 !important;
        }

        /* Inputs y botones */
        .form-control {
            border: 2px solid #e7ecef;
            border-radius: 12px;
            padding: .9rem 1rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: var(--jg-mint);
            box-shadow: 0 0 0 .2rem rgba(32, 201, 151, .2);
        }

        .input-group .btn {
            border-radius: 12px;
            border: 2px solid #e7ecef;
        }

        .btn-jg {
            background: var(--jg-green);
            border: 0;
            border-radius: 12px;
            padding: .9rem 1rem;
            font-weight: 700;
            transition: transform .08s ease, filter .2s ease;
        }

        .btn-jg:hover {
            filter: brightness(.95);
        }

        .btn-jg:active {
            transform: translateY(1px);
        }

        .btn-outline-success {
            border-radius: 999px;
            border-width: 2px;
        }

        /* Extras registro */
        .hidden {
            display: none !important;
        }

        .preview-img {
            border-radius: 10px;
            max-height: 110px;
            object-fit: cover;
        }

        a {
            color: #3c6255;
            text-decoration: none;
        }

        a:hover {
            color: #20c997;
        }

        .paw-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #8aa3a9;
        }

        .paw-divider::before,
        .paw-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, #cfe6e0, transparent);
        }

        @media (max-width: 992px) {
            .illustration {
                display: none;
            }

            .auth-card {
                border-radius: 18px;
            }
        }
    </style>
</head>

<body>

    <main class="auth-shell">
        <div class="row g-0 auth-card">
            <!-- Columna ilustración / beneficios -->
            <div class="col-lg-6 illustration">
                <div class="illustration-inner">
                    <span class="illustration-pill">
                        <i class="fa-solid fa-paw"></i>
                        Registro en Jaguata
                    </span>

                    <h2 class="illustration-title">
                        Sé parte de la comunidad que cuida a las mascotas
                    </h2>

                    <p class="illustration-text">
                        Creá tu cuenta como dueño o paseador y gestioná los paseos de manera segura,
                        organizada y transparente. Toda la información queda centralizada en Jaguata.
                    </p>

                    <ul class="illustration-list">
                        <li>
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Validación de documentación para paseadores antes de habilitar servicios.</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-people-group"></i>
                            <span>Dueños y paseadores conectados según disponibilidad, zona y horarios.</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-file-shield"></i>
                            <span>Historial de paseos y registros para mayor confianza entre usuarios.</span>
                        </li>
                    </ul>

                    <div class="illustration-metrics">
                        <div class="illustration-metrics-item">
                            <strong>2 roles</strong>
                            <small>Dueños y paseadores</small>
                        </div>
                        <div class="illustration-metrics-item">
                            <strong>Verificación</strong>
                            <small>Documental previa</small>
                        </div>
                        <div class="illustration-metrics-item">
                            <strong>Online</strong>
                            <small>Gestión de paseos</small>
                        </div>
                    </div>
                </div>

                <div class="illustration-graphic" aria-hidden="true">
                    <!-- Ilustración SVG -->
                    <svg class="dog-svg" viewBox="0 0 640 480" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="leash" x1="0" x2="1">
                                <stop offset="0" stop-color="#20c997" />
                                <stop offset="1" stop-color="#3c6255" />
                            </linearGradient>
                        </defs>
                        <!-- persona simplificada -->
                        <rect x="80" y="40" width="120" height="200" rx="20" fill="#eaf0f2" />
                        <rect x="110" y="220" width="80" height="18" rx="9" fill="#cdd8dd" />
                        <!-- perro -->
                        <path d="M220,260 C260,250 320,250 360,260 380,265 420,260 440,270 460,280 470,305 450,312 430,319 410,300 395,305 380,310 365,332 340,332 315,332 300,312 285,305 270,298 245,310 228,300 212,291 208,270 220,260Z" fill="#1e2426" />
                        <circle cx="445" cy="275" r="12" fill="#1e2426" />
                        <circle cx="448" cy="273" r="4" fill="#fff" />
                        <!-- collar y correa -->
                        <path d="M360,260 Q370,240 390,235" stroke="url(#leash)" stroke-width="6" fill="none" />
                        <circle cx="360" cy="260" r="10" fill="#20c997" />
                        <!-- suelo -->
                        <ellipse cx="320" cy="360" rx="220" ry="26" fill="rgba(0,0,0,.18)" />
                    </svg>
                </div>
            </div>

            <!-- Columna formulario -->
            <div class="col-lg-6">
                <div class="form-pane">
                    <div class="text-center mb-3">
                        <div class="logo-circle">
                            <img src="<?= AppConfig::getAssetsUrl(); ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="80" height="80">
                        </div>
                        <h2>Crear cuenta</h2>
                        <p class="text-muted">Únete a la comunidad Jaguata</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= Validaciones::generarCSRF(); ?>">

                        <div class="text-center mb-3">
                            <label class="form-label fw-semibold">Tipo de cuenta</label><br>
                            <input type="radio" class="btn-check" name="rol" id="dueno" value="dueno" checked>
                            <label for="dueno" class="btn btn-outline-success me-2">
                                <i class="fas fa-paw me-1"></i> Dueño
                            </label>

                            <input type="radio" class="btn-check" name="rol" id="paseador" value="paseador">
                            <label for="paseador" class="btn btn-outline-success">
                                <i class="fas fa-walking me-1"></i> Paseador
                            </label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre completo</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Teléfono</label>
                                <input type="tel" name="telefono" class="form-control" required placeholder="0981-123-456">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirmar contraseña</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>

                        <div id="paseadorExtra" class="hidden mt-4">
                            <hr>
                            <h6 class="fw-bold text-success mb-3">Documentos obligatorios para paseadores</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cédula (frente)</label>
                                    <input type="file" name="cedula_frente" class="form-control" accept="image/*" required>
                                    <img id="preview_frente" class="preview-img mt-2 w-100 hidden">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cédula (dorso)</label>
                                    <input type="file" name="cedula_dorso" class="form-control" accept="image/*" required>
                                    <img id="preview_dorso" class="preview-img mt-2 w-100 hidden">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Selfie con cédula</label>
                                    <input type="file" name="selfie" class="form-control" accept="image/*" required>
                                    <img id="preview_selfie" class="preview-img mt-2 w-100 hidden">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Certificado de antecedentes</label>
                                    <input type="file" name="antecedentes" class="form-control" accept=".pdf,image/*" required>
                                </div>
                            </div>
                        </div>

                        <!-- Checkbox de Bases y Condiciones -->
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="acepta_condiciones" name="acepta_condiciones" required>
                            <label class="form-check-label" for="acepta_condiciones">
                                Acepto las
                                <a href="<?= AppConfig::getBaseUrl(); ?>/bases-y-condiciones.php" target="_blank" class="fw-semibold text-success">
                                    Bases y Condiciones
                                </a>
                                de uso de Jaguata.
                            </label>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-jg btn-lg">
                                <i class="fas fa-user-plus me-2"></i> Crear cuenta
                            </button>
                        </div>

                        <div class="my-4 paw-divider">
                            <i class="fa-solid fa-paw"></i>
                            <span class="small">Seguro • Verificado • Comunitario</span>
                            <i class="fa-solid fa-bone"></i>
                        </div>

                        <p class="text-center mt-2 mb-0">
                            ¿Ya tienes cuenta?
                            <a href="<?= AppConfig::getBaseUrl(); ?>/login.php" class="fw-bold">Inicia sesión</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dueno = document.getElementById('dueno');
        const paseador = document.getElementById('paseador');
        const extra = document.getElementById('paseadorExtra');

        dueno.addEventListener('change', () => extra.classList.add('hidden'));
        paseador.addEventListener('change', () => extra.classList.remove('hidden'));

        function previewFile(input, id) {
            const file = input.files[0];
            const preview = document.getElementById(id);
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else if (preview) {
                preview.classList.add('hidden');
            }
        }

        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', () => {
                const parts = input.name.split('_'); // cedula_frente -> ['cedula', 'frente']
                const key = parts[1] || parts[0];
                const id = 'preview_' + key;
                if (document.getElementById(id)) previewFile(input, id);
            });
        });
    </script>
</body>

</html>