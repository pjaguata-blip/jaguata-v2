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
function h(?string $v): string {
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
    $descripcion      = trim($_POST['descripcion'] ?? '');
    $experiencia      = trim($_POST['experiencia'] ?? '');

    // Dirección
    $direccion    = trim($_POST['direccion'] ?? '');
    $departamento = $_POST['departamento'] ?? null;
    $ciudad       = $_POST['ciudad'] ?? null;
    $barrio       = $_POST['barrio'] ?? null;
    $calle        = trim($_POST['calle'] ?? '');

    $errores = [];

    // Validaciones base
    if ($nombre === '' || mb_strlen($nombre) < 3) $errores[] = 'El nombre debe tener al menos 3 caracteres.';
    $emailCheck = Validaciones::validarEmail($email);
    if (!$emailCheck['valido']) $errores[] = $emailCheck['mensaje'];

    if ($pass !== $pass2) $errores[] = 'Las contraseñas no coinciden.';
    $passCheck = Validaciones::validarPassword($pass);
    if (!$passCheck['valido']) $errores[] = $passCheck['mensaje'];

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
        foreach ($files as $f) validarArchivo($f, $errores);
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

    <!-- ✅ tu theme para que se parezca a dashboards -->
    <link href="<?= AppConfig::getBaseUrl(); ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        :root{
            --jg-green: #3c6255;
            --jg-mint: #20c997;
        }

        body{
            min-height: 100vh;
            background: var(--gris-fondo, #f4f6f9);
        }

        /* “Home/dashboard feel” */
        .auth-shell{
            width: min(1100px, 94vw);
            margin: 22px auto 32px;
        }

        .auth-topbar{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            margin-bottom: 14px;
        }

        .brand{
            display:flex;
            align-items:center;
            gap:.6rem;
            text-decoration:none;
            color:#222;
            font-weight:900;
        }
        .brand img{
            width:44px; height:44px;
            border-radius:50%;
            object-fit:cover;
            background:#fff;
            box-shadow: 0 8px 18px rgba(0,0,0,.10);
        }

        /* panel similar section-card */
        .register-card{
            border:0;
            border-radius:18px;
            overflow:hidden;
            box-shadow:0 12px 30px rgba(0,0,0,.08);
            background:#fff;
        }

        .side-info{
            background: linear-gradient(135deg, #3c6255 0%, #20c997 100%);
            color:#f5fbfa;
            padding: 22px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            min-height: 100%;
        }

        .side-pill{
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(0,0,0,.20);
            font-size:.78rem;
            font-weight:800;
            letter-spacing:.03em;
            text-transform:uppercase;
            width: fit-content;
        }

        .side-title{
            font-size: 1.35rem;
            font-weight: 900;
            line-height: 1.15;
            margin: 12px 0 8px;
        }

        .side-text{
            font-size: .9rem;
            opacity:.95;
            margin: 0 0 14px;
        }

        .side-list{
            list-style:none;
            padding-left:0;
            margin:0;
            font-size:.9rem;
        }
        .side-list li{
            display:flex;
            gap:10px;
            margin-bottom: 8px;
        }
        .side-list i{
            margin-top: 2px;
        }

        .form-pane{
            padding: 22px;
        }

        .form-control, .form-select{
            border: 2px solid #e7ecef;
            border-radius: 12px;
            padding: .62rem .82rem;
            font-size: .92rem;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-control:focus, .form-select:focus{
            border-color: var(--jg-mint);
            box-shadow: 0 0 0 .2rem rgba(32, 201, 151, .18);
        }

        .btn-jg{
            background: var(--jg-green);
            border: 0;
            border-radius: 12px;
            padding: .7rem 1rem;
            font-weight: 800;
            color:#fff;
        }
        .btn-jg:hover{ filter: brightness(.96); color:#fff; }

        .btn-outline-success{
            border-radius: 999px;
            border-width: 2px;
        }

        .hidden{ display:none !important; }

        .preview-img{
            border-radius: 12px;
            max-height: 110px;
            object-fit: cover;
        }

        /* tarjeta de suscripción */
        .sub-card{
            border: 0;
            border-radius: 16px;
            background: rgba(255,255,255,.92);
            box-shadow: 0 10px 22px rgba(0,0,0,.10);
        }
        .price{
            font-size: 1.55rem;
            font-weight: 900;
            color: #0f5132;
        }
        .check-li{
            display:flex;
            gap:.55rem;
            align-items:flex-start;
            margin-bottom:.35rem;
        }
        .check-li i{ margin-top:.2rem; }

        @media (max-width: 992px){
            .side-info{ display:none; }
            .form-pane{ padding: 18px; }
        }
    </style>
</head>

<body>

<div class="auth-shell">

    <!-- Topbar simple (como tus pantallas) -->
    <div class="auth-topbar">
        <a class="brand" href="<?= AppConfig::getBaseUrl(); ?>/">
            <img src="<?= AppConfig::getAssetsUrl(); ?>/images/logojag.png" alt="Jaguata">
            <span>Jaguata</span>
        </a>

        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-secondary" href="<?= AppConfig::getBaseUrl(); ?>/login.php">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Iniciar sesión
            </a>
            <a class="btn btn-outline-primary" href="<?= AppConfig::getBaseUrl(); ?>/">
                <i class="fa-solid fa-house me-1"></i> Volver al Home
            </a>
        </div>
    </div>

    <!-- Header box (estilo dashboard) -->
    <div class="header-box header-dashboard mb-3">
        <div>
            <h1>Crear cuenta 🐾</h1>
            <p class="mb-0">Registrate como dueño o paseador. Si elegís paseador, vas a subir documentos para verificación.</p>
        </div>
        <i class="fa-solid fa-user-plus fa-3x opacity-75"></i>
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

    <div class="row g-3">
        <!-- Form -->
        <div class="col-lg-8">
            <div class="register-card">
                <div class="row g-0">
                    <div class="col-lg-5 side-info">
                        <div>
                            <span class="side-pill"><i class="fa-solid fa-paw"></i> Registro Jaguata</span>
                            <div class="side-title">Sé parte de la comunidad que cuida a las mascotas</div>
                            <p class="side-text">Cuentas verificadas, paseos seguros y trazabilidad para dueños y paseadores.</p>

                            <ul class="side-list">
                                <li><i class="fa-solid fa-user-shield"></i><span>Validación documental para paseadores.</span></li>
                                <li><i class="fa-solid fa-people-group"></i><span>Conexión por disponibilidad y zona.</span></li>
                                <li><i class="fa-solid fa-file-shield"></i><span>Historial y trazabilidad de paseos.</span></li>
                            </ul>
                        </div>

                        <div class="text-white-50" style="font-size:.82rem;">
                            Seguro • Verificado • Comunitario 🐾
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="form-pane">
                            <form method="POST" enctype="multipart/form-data" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?= h(Validaciones::generarCSRF()); ?>">

                                <div class="text-center mb-3">
                                    <label class="form-label fw-bold mb-2">Tipo de cuenta</label><br>
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

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Ciudad</label>
                                        <select name="ciudad" id="ciudad" class="form-select" required disabled>
                                            <option value="">Seleccionar departamento primero</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Barrio</label>
                                        <select name="barrio" id="barrio" class="form-select" disabled>
                                            <option value="">Seleccionar ciudad primero</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Calle</label>
                                        <input type="text" name="calle" class="form-control" placeholder="Ej: Av. España 123">
                                    </div>
                                </div>

                                <!-- ✅ NUEVO: caja suscripción (solo visual cuando es paseador) -->
                                <div id="suscripcionBox" class="hidden mt-4">
                                    <div class="sub-card p-3">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="fw-bold">
                                                <i class="fa-solid fa-crown text-warning me-2"></i> Suscripción Paseador Pro
                                            </div>
                                            <span class="badge bg-success">₲50.000 / mes</span>
                                        </div>
                                        <div class="price mt-2">₲50.000 <span class="text-muted fs-6 fw-semibold">mensual</span></div>
                                        <div class="text-muted small mb-2">
                                            Pagás una vez al mes y podrás realizar los paseos que quieras (ilimitado).
                                        </div>

                                        <div class="check-li small">
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                            <span>Paseos ilimitados</span>
                                        </div>
                                        <div class="check-li small">
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                            <span>Más visibilidad en búsquedas</span>
                                        </div>
                                        <div class="check-li small mb-0">
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                            <span>Estadísticas avanzadas</span>
                                        </div>

                                        <div class="alert alert-light border small mt-3 mb-0">
                                            <i class="fa-solid fa-circle-info me-2"></i>
                                            La suscripción se activa luego de la aprobación del administrador.
                                        </div>
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

                                <p class="text-center mt-3 mb-0">
                                    ¿Ya tienes cuenta?
                                    <a href="<?= AppConfig::getBaseUrl(); ?>/login.php" class="fw-bold">Inicia sesión</a>
                                </p>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tips / Ayuda lateral (tipo cards) -->
        <div class="col-lg-4">
            <div class="section-card mb-3">
                <div class="section-header">
                    <i class="fa-solid fa-shield-heart me-2"></i>Consejos de seguridad
                </div>
                <div class="section-body">
                    <ul class="mb-0">
                        <li>Usá un email real para recuperar tu cuenta.</li>
                        <li>Elegí una contraseña segura.</li>
                        <li>Si sos paseador, subí documentos claros y legibles.</li>
                    </ul>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <i class="fa-solid fa-circle-question me-2"></i>¿Necesitás ayuda?
                </div>
                <div class="section-body">
                    <p class="text-muted mb-2">Si tenés dudas sobre el registro o documentos, escribinos.</p>
                    <a href="<?= AppConfig::getBaseUrl(); ?>/contacto.php" class="btn btn-outline-primary w-100">
                        <i class="fa-solid fa-envelope me-2"></i>Contactar
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const dueno = document.getElementById('dueno');
    const paseador = document.getElementById('paseador');
    const extra = document.getElementById('paseadorExtra');
    const subBox = document.getElementById('suscripcionBox');

    function toggleDocs() {
        const isPaseador = paseador.checked;
        if (isPaseador) {
            extra.classList.remove('hidden');
            subBox.classList.remove('hidden'); // ✅ muestra suscripción
        } else {
            extra.classList.add('hidden');
            subBox.classList.add('hidden');
        }
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
