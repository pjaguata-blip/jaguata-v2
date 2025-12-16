<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;
use Jaguata\Models\Paseador;

AppConfig::init();

$RUTA_SELF = AppConfig::getBaseUrl() . '/registro.php';

/* 🔒 Redirigir si ya está logueado */
if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRol() ?? 'dueno';
    header('Location: ' . AppConfig::getBaseUrl() . "/features/{$rol}/Dashboard.php");
    exit;
}

$error   = Session::getError();
$success = Session::getSuccess();

/* Carpeta uploads */
$uploadDir = __DIR__ . '/../uploads/verificaciones/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function validarArchivo(string $campo, array &$errores, int $maxSizeBytes = 5242880): void
{
    if (empty($_FILES[$campo]['name'])) {
        $errores[] = "El archivo de " . str_replace('_', ' ', $campo) . " es obligatorio.";
        return;
    }

    if (!empty($_FILES[$campo]['error']) && $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        $errores[] = "Error al subir el archivo: $campo.";
        return;
    }

    if (!empty($_FILES[$campo]['size']) && $_FILES[$campo]['size'] > $maxSizeBytes) {
        $errores[] = "El archivo $campo supera 5MB.";
        return;
    }

    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($ext, $allowedExt, true)) {
        $errores[] = "Formato inválido para $campo (solo JPG, PNG o PDF).";
        return;
    }

    // Validar MIME real
    $tmp = $_FILES[$campo]['tmp_name'] ?? '';
    if ($tmp && is_file($tmp)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmp);

        $allowedMime = [
            'image/jpeg',
            'image/png',
            'application/pdf',
        ];

        if (!in_array($mime, $allowedMime, true)) {
            $errores[] = "Tipo de archivo no permitido para $campo.";
            return;
        }
    }
}

function subirArchivo(string $campo, string $uploadDir): ?string
{
    if (empty($_FILES[$campo]['name'])) return null;

    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
    $filename = uniqid($campo . '_', true) . '.' . $ext;

    $dest = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $dest)) {
        return null;
    }

    return $filename;
}

/* =========================
   PROCESAR FORMULARIO
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    $csrfPost = $_POST['csrf_token'] ?? '';
    if (!Validaciones::verificarCSRF($csrfPost)) {
        Session::setError('Token inválido. Recargá la página e intentá de nuevo.');
        header('Location: ' . $RUTA_SELF);
        exit;
    }


    // Base
    $rol      = $_POST['rol'] ?? 'dueno';
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['confirm_password'] ?? '';

    // Nuevos campos (tabla usuario)
    $fechaNacimiento  = $_POST['fecha_nacimiento'] ?? null;
    $sexo             = $_POST['sexo'] ?? null;
    $tipoDocumento    = $_POST['tipo_documento'] ?? null;
    $numeroDocumento  = trim($_POST['numero_documento'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $experiencia = trim($_POST['experiencia'] ?? '');
    // Dirección (opcionales)
    $direccion    = trim($_POST['direccion'] ?? '');
    $departamento = $_POST['departamento'] ?? null;
    $ciudad       = $_POST['ciudad'] ?? null;
    $barrio       = $_POST['barrio'] ?? null;
    $calle        = trim($_POST['calle'] ?? '');

    $errores = [];

    // Validaciones base
    if ($nombre === '' || mb_strlen($nombre) < 3) $errores[] = 'El nombre debe tener al menos 3 caracteres.';
    $emailCheck = Validaciones::validarEmail($email);
    if (!$emailCheck['valido']) {
        $errores[] = $emailCheck['mensaje'];
    }

    if ($pass !== $pass2) $errores[] = 'Las contraseñas no coinciden.';
    $passCheck = Validaciones::validarPassword($pass);
    if (!$passCheck['valido']) {
        $errores[] = $passCheck['mensaje'];
    }

    if (!Validaciones::validarTelefono($telefono)) {
        $errores[] = 'Teléfono inválido (ej: 0981-123-456 o solo dígitos).';
    }


    // Validaciones nuevas
    if (!$fechaNacimiento) $errores[] = 'Fecha de nacimiento obligatoria.';
    if (!$sexo || !in_array($sexo, ['F', 'M', 'X'], true)) $errores[] = 'Sexo inválido.';
    if (!$tipoDocumento || !in_array($tipoDocumento, ['ci', 'pasaporte'], true)) $errores[] = 'Tipo de documento inválido.';
    if ($numeroDocumento === '' || mb_strlen($numeroDocumento) < 3) $errores[] = 'Número de documento obligatorio.';

    // Bases y condiciones
    if (!isset($_POST['acepta_condiciones'])) {
        $errores[] = 'Debes aceptar las Bases y Condiciones para continuar.';
    }

    $files   = ['cedula_frente', 'cedula_dorso', 'selfie', 'antecedentes'];
    $uploads = [];

    // Archivos requeridos SOLO para paseadores
    if ($rol === 'paseador') {
        foreach ($files as $f) {
            validarArchivo($f, $errores);
        }
    }

    if (empty($errores)) {
        try {
            $usuarioModel = new Usuario();

            // Email repetido
            if ($usuarioModel->getByEmail($email)) {
                $errores[] = 'El correo ya está registrado.';
            } else {
                // Subidas (solo si se cargaron)
                foreach ($files as $f) {
                    $uploads[$f] = subirArchivo($f, $uploadDir);
                }

                // Crear usuario
                $usuarioId = $usuarioModel->createUsuario([
                    'nombre' => $nombre,
                    'email'  => $email,
                    'pass' => $pass,
                    'rol' => $rol,
                    'estado' => 'pendiente',
                    'telefono' => $telefono,

                    // 👤 Datos personales
                    'sexo'             => $sexo,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'tipo_documento'   => $tipoDocumento,
                    'numero_documento' => $numeroDocumento,

                    // 📍 Dirección
                    'departamento' => $departamento,
                    'ciudad'       => $ciudad,
                    'barrio'       => $barrio,
                    'calle'        => $calle,

                    // 📝 Extra
                    'descripcion' => $descripcion,
                    'experiencia' => $experiencia,

                    // 📂 Documentos
                    'foto_cedula_frente' => $uploads['cedula_frente'] ?? null,
                    'foto_cedula_dorso'  => $uploads['cedula_dorso'] ?? null,
                    'foto_selfie'        => $uploads['selfie'] ?? null,
                    'certificado_antecedentes' => $uploads['antecedentes'] ?? null,

                    // ⚖️ Sistema
                    'acepto_terminos'  => 1,
                    'fecha_aceptacion' => date('Y-m-d H:i:s'),
                    'ip_registro'      => $_SERVER['REMOTE_ADDR'] ?? null,
                    'puntos'           => 0,
                ]);

                // Crear registro paseador (si corresponde)
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
            error_log('❌ Registro error: ' . $e->getMessage());
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
        }

        html {
            font-size: clamp(13.2px, 0.78vw, 14.8px);
        }

        body {
            min-height: 100vh;
            background: linear-gradient(160deg, var(--jg-green) 0%, var(--jg-mint) 100%);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
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

        .auth-shell {
            width: min(860px, 92vw);
        }

        .auth-card {
            border: 0;
            border-radius: 20px;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: saturate(140%) blur(8px);
            box-shadow: 0 18px 60px rgba(0, 0, 0, .18);
            overflow: hidden;
        }

        .illustration {
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, .18), transparent 55%),
                linear-gradient(135deg, #3c6255 0%, #20c997 100%);
            color: #f5fbfa;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 22px;
        }

        .illustration-title {
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.25;
            margin: 12px 0 8px;
        }

        .illustration-text {
            font-size: .84rem;
            opacity: .92;
            margin: 0 0 10px;
        }

        .illustration-list {
            list-style: none;
            padding-left: 0;
            margin: 0 0 10px;
        }

        .illustration-list li {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            font-size: .83rem;
            margin-bottom: 6px;
        }

        .illustration-list i {
            margin-top: 2px;
        }

        .form-pane {
            padding: 22px;
        }

        .logo-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #f4f7f9;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .08);
            margin: 0 auto 8px;
        }

        .logo-circle img {
            width: 56px;
            height: 56px;
        }

        h2 {
            color: var(--jg-green);
            font-weight: 800;
            letter-spacing: .2px;
            font-size: 1.3rem;
        }

        .text-muted {
            color: #6b7b83 !important;
        }

        .form-control,
        .form-select {
            border: 2px solid #e7ecef;
            border-radius: 10px;
            padding: .62rem .82rem;
            font-size: .88rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--jg-mint);
            box-shadow: 0 0 0 .2rem rgba(32, 201, 151, .18);
        }

        .btn-jg {
            background: var(--jg-green);
            border: 0;
            border-radius: 10px;
            padding: .65rem 1rem;
            font-weight: 700;
            font-size: .9rem;
            transition: transform .08s ease, filter .2s ease;
            color: #fff;
        }

        .btn-jg:hover {
            filter: brightness(.96);
            color: #fff;
        }

        .btn-jg:active {
            transform: translateY(1px);
        }

        .btn-outline-success {
            border-radius: 999px;
            border-width: 2px;
        }

        .hidden {
            display: none !important;
        }

        .preview-img {
            border-radius: 10px;
            max-height: 110px;
            object-fit: cover;
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

            .auth-shell {
                width: min(520px, 94vw);
            }
        }
    </style>
</head>

<body>
    <main class="auth-shell">
        <div class="row g-0 auth-card">

            <div class="col-lg-6 illustration">
                <div>
                    <span class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded-pill"
                        style="background: rgba(0,0,0,.18); font-size:.75rem; letter-spacing:.04em; text-transform:uppercase;">
                        <i class="fa-solid fa-paw"></i> Registro en Jaguata
                    </span>

                    <h2 class="illustration-title">Sé parte de la comunidad que cuida a las mascotas</h2>

                    <p class="illustration-text">
                        Creá tu cuenta como dueño o paseador y gestioná los paseos de manera segura y transparente.
                    </p>

                    <ul class="illustration-list">
                        <li><i class="fa-solid fa-user-shield"></i> <span>Validación documental para paseadores.</span></li>
                        <li><i class="fa-solid fa-people-group"></i> <span>Conexión por disponibilidad y zona.</span></li>
                        <li><i class="fa-solid fa-file-shield"></i> <span>Historial y trazabilidad de paseos.</span></li>
                    </ul>
                </div>

                <div class="text-white-50" style="font-size:.78rem;">
                    Seguro • Verificado • Comunitario 🐾
                </div>
            </div>

            <div class="col-lg-6">
                <div class="form-pane">
                    <div class="text-center mb-3">
                        <div class="logo-circle">
                            <img src="<?= AppConfig::getAssetsUrl(); ?>/uploads/perfiles/logojag.png" alt="Jaguata">
                        </div>
                        <h2 class="mb-1">Crear cuenta</h2>
                        <p class="text-muted mb-0">Únete a la comunidad Jaguata</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?= h($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?= h($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= h(Validaciones::generarCSRF()); ?>">

                        <div class="text-center mb-3">
                            <label class="form-label fw-semibold mb-2">Tipo de cuenta</label><br>
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
                                <input type="text" name="nombre" class="form-control" required autocomplete="off">

                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control" required autocomplete="off">

                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Teléfono</label>
                                <input type="tel" name="telefono" class="form-control" required autocomplete="off" placeholder="0981-123-456">

                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contraseña</label>
                                <input type="password" name="password" class="form-control" required autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirmar contraseña</label>
                                <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="fw-bold text-success mb-2">Datos personales</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha de nacimiento</label>
                                <input type="date" name="fecha_nacimiento" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Sexo</label>
                                <select name="sexo" class="form-select" required>
                                    <option value="">Seleccionar</option>
                                    <option value="F">Femenino</option>
                                    <option value="M">Masculino</option>
                                    <option value="X">Otro</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo de documento</label>
                                <select name="tipo_documento" class="form-select" required>
                                    <option value="">Seleccionar</option>
                                    <option value="ci">Cédula</option>
                                    <option value="pasaporte">Pasaporte</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Número de documento</label>
                                <input type="text" name="numero_documento" class="form-control" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold text-success">Dirección</h6>

                        <div class="row g-3">
                            <!-- Departamento -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Departamento</label>
                                <select name="departamento" id="departamento" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Central">Central</option>
                                    <option value="Asunción">Asunción</option>
                                    <option value="Alto Paraná">Alto Paraná</option>
                                    <option value="Itapúa">Itapúa</option>
                                    <option value="Cordillera">Cordillera</option>
                                </select>
                            </div>

                            <!-- Ciudad -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ciudad</label>
                                <select name="ciudad" id="ciudad" class="form-select" required disabled>
                                    <option value="">Seleccionar departamento primero</option>
                                </select>
                            </div>

                            <!-- Barrio -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Barrio</label>
                                <select name="barrio" id="barrio" class="form-select" disabled>
                                    <option value="">Seleccionar ciudad primero</option>
                                </select>
                            </div>

                            <!-- Calle -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Calle</label>
                                <input type="text" name="calle" class="form-control" placeholder="Ej: Av. España 123">
                            </div>
                        </div>


                        <div id="paseadorExtra" class="hidden mt-4">
                            <hr>
                            <h6 class="fw-bold text-success mb-3">Documentos obligatorios para paseadores</h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cédula (frente)</label>
                                    <input type="file" name="cedula_frente" class="form-control" accept="image/*,.pdf">
                                    <img id="preview_frente" class="preview-img mt-2 w-100 hidden" alt="preview frente">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cédula (dorso)</label>
                                    <input type="file" name="cedula_dorso" class="form-control" accept="image/*,.pdf">
                                    <img id="preview_dorso" class="preview-img mt-2 w-100 hidden" alt="preview dorso">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Selfie con cédula</label>
                                    <input type="file" name="selfie" class="form-control" accept="image/*,.pdf">
                                    <img id="preview_selfie" class="preview-img mt-2 w-100 hidden" alt="preview selfie">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Certificado de antecedentes</label>
                                    <input type="file" name="antecedentes" class="form-control" accept=".pdf,image/*">
                                </div>
                                <small class="text-muted mt-1">Máximo 5MB por archivo.</small>
                            </div>
                        </div>

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
                            <button type="submit" class="btn btn-jg">
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

        function toggleDocs() {
            if (paseador.checked) extra.classList.remove('hidden');
            else extra.classList.add('hidden');
        }
        dueno.addEventListener('change', toggleDocs);
        paseador.addEventListener('change', toggleDocs);
        toggleDocs();

        function previewFile(input, id) {
            const file = input.files[0];
            const preview = document.getElementById(id);
            if (!preview) return;

            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
            }
        }

        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', () => {
                const key = input.name.split('_')[1] || input.name.split('_')[0];
                const id = 'preview_' + key;
                if (document.getElementById(id)) previewFile(input, id);
            });
        });
    </script>
    <script>
        const dataUbicacion = {
            "Central": {
                "San Lorenzo": ["Centro", "Barcequillo", "Villa Industrial"],
                "Lambaré": ["Centro", "Valle Apu’a", "Santa Lucía"],
                "Luque": ["Centro", "Mora Cué", "Itapuamí"]
            },
            "Asunción": {
                "Asunción": ["Villa Morra", "Recoleta", "San Vicente", "Trinidad"]
            },
            "Alto Paraná": {
                "Ciudad del Este": ["Microcentro", "Área 1", "Área 4"],
                "Hernandarias": ["Centro", "Puerta del Sol"]
            }
        };

        const depSelect = document.getElementById('departamento');
        const ciudadSelect = document.getElementById('ciudad');
        const barrioSelect = document.getElementById('barrio');

        depSelect.addEventListener('change', () => {
            ciudadSelect.innerHTML = '<option value="">Seleccionar...</option>';
            barrioSelect.innerHTML = '<option value="">Seleccionar ciudad primero</option>';
            barrioSelect.disabled = true;

            const dep = depSelect.value;
            if (!dataUbicacion[dep]) {
                ciudadSelect.disabled = true;
                return;
            }

            ciudadSelect.disabled = false;
            Object.keys(dataUbicacion[dep]).forEach(ciudad => {
                ciudadSelect.innerHTML += `<option value="${ciudad}">${ciudad}</option>`;
            });
        });

        ciudadSelect.addEventListener('change', () => {
            barrioSelect.innerHTML = '<option value="">Seleccionar...</option>';
            const dep = depSelect.value;
            const ciudad = ciudadSelect.value;

            if (!dataUbicacion[dep]?.[ciudad]) {
                barrioSelect.disabled = true;
                return;
            }

            barrioSelect.disabled = false;
            dataUbicacion[dep][ciudad].forEach(barrio => {
                barrioSelect.innerHTML += `<option value="${barrio}">${barrio}</option>`;
            });
        });
    </script>

</body>

</html>