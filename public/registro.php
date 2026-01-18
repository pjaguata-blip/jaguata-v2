<?php
declare(strict_types=1);

/* ================== BOOTSTRAP ================== */
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
/* ================== URL VOLVER ================== */
$urlVolver = AppConfig::getBaseUrl() . '/sobre_nosotros.php';

AppConfig::init();

/* ================== HELPERS ================== */
function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ================== ESTADO ================== */
$error   = Session::getError();
$success = Session::getSuccess();

$titulo = 'Registro - Jaguata';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($titulo) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- Theme global -->
  <link href="<?= AppConfig::getAssetsUrl(); ?>/css/jaguata-theme.css" rel="stylesheet">

  <style>
    html, body { height: 100%; }
    body { background: var(--gris-fondo, #f4f6f9); }

    .layout { display: flex; min-height: 100vh; }
    main.main-content{
      margin-left: 0;
      min-height: 100vh;
      padding: 24px;
      width: 100%;
    }

    .form-control, .form-select{
      border: 2px solid #e7ecef;
      border-radius: 12px;
    }
    .form-control:focus, .form-select:focus{
      border-color: var(--verde-claro, #20c997);
      box-shadow: 0 0 0 .2rem rgba(32,201,151,.18);
    }

    .btn-jg{
      background: var(--verde-jaguata, #3c6255);
      border-radius: 12px;
      font-weight: 900;
      color:#fff;
    }
    .btn-jg:hover{ filter: brightness(.95); color:#fff; }

    .hidden{ display:none!important; }
    .preview-img{ border-radius:12px; max-height:120px; object-fit:cover; }

    .sub-card{
      border-radius:16px;
      box-shadow:0 10px 22px rgba(0,0,0,.06);
      background:#fff;
    }
  </style>
</head>

<body>
<div class="layout">
<main class="main-content">

<!-- HEADER -->
<div class="header-box header-dashboard mb-3">
  <div>
    <h1 class="mb-1">Crear cuenta 🐾</h1>
    <p class="mb-0">
      Registrate como <b>dueño</b> o <b>paseador</b>.  
      Los paseadores deben subir documentos para verificación.
    </p>
  </div>

  <div class="d-flex gap-2">
    <a class="btn btn-outline-light border" href="<?= AppConfig::getBaseUrl(); ?>/login.php">
      <i class="fa-solid fa-right-to-bracket me-1"></i> Iniciar sesión
    </a>
    <a href="<?= h($urlVolver) ?>" class="btn btn-outline-light">
    <i class="fas fa-arrow-left me-1"></i> Volver
</a>

  </div>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger border">
    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= h($error) ?>
  </div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="alert alert-success border">
    <i class="fa-solid fa-circle-check me-2"></i><?= h($success) ?>
  </div>
<?php endif; ?>

<div class="row g-3">

<!-- FORMULARIO -->
<div class="col-lg-8">
<div class="section-card">
<div class="section-header">
  <i class="fa-solid fa-user-plus me-2"></i>Formulario de registro
</div>
<div class="section-body">

<form method="POST" enctype="multipart/form-data" autocomplete="off">
<input type="hidden" name="csrf_token" value="<?= h(Validaciones::generarCSRF()) ?>">

<!-- ROL -->
<div class="text-center mb-3">
  <input type="radio" class="btn-check" name="rol" id="dueno" value="dueno" checked>
  <label for="dueno" class="btn btn-outline-success me-2">
    <i class="fa-solid fa-paw me-1"></i> Dueño
  </label>

  <input type="radio" class="btn-check" name="rol" id="paseador" value="paseador">
  <label for="paseador" class="btn btn-outline-success">
    <i class="fa-solid fa-person-walking me-1"></i> Paseador
  </label>
</div>

<!-- DATOS -->
<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label fw-semibold">Nombre completo</label>
    <input name="nombre" class="form-control" required>
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold">Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold">Teléfono</label>
    <input name="telefono" class="form-control" required>
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold">Contraseña</label>
    <input type="password" name="password" class="form-control" required>
  </div>
</div>

<hr>

<!-- PASEADOR -->
<div id="paseadorExtra" class="hidden mt-3">
  <h6 class="fw-bold text-success">Documentos obligatorios</h6>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Cédula (frente)</label>
      <input type="file" name="cedula_frente" class="form-control">
    </div>

    <div class="col-md-6">
      <label class="form-label">Cédula (dorso)</label>
      <input type="file" name="cedula_dorso" class="form-control">
    </div>

    <div class="col-md-6">
      <label class="form-label">Selfie con cédula</label>
      <input type="file" name="selfie" class="form-control">
    </div>

    <div class="col-md-6">
      <label class="form-label">Antecedentes</label>
      <input type="file" name="antecedentes" class="form-control">
    </div>
  </div>
</div>

<div class="form-check mt-4">
  <input class="form-check-input" type="checkbox" required>
  <label class="form-check-label">
    Acepto las
    <a href="<?= AppConfig::getBaseUrl(); ?>/bases-y-condiciones.php" target="_blank">
      Bases y Condiciones
    </a>
  </label>
</div>

<div class="d-grid mt-4">
  <button class="btn btn-jg">
    <i class="fa-solid fa-user-plus me-2"></i> Crear cuenta
  </button>
</div>

</form>
</div>
</div>
</div>

<!-- SIDEBAR DERECHO -->
<div class="col-lg-4">
  <div class="section-card">
    <div class="section-header">
      <i class="fa-solid fa-shield-heart me-2"></i>Consejos
    </div>
    <div class="section-body">
      <ul>
        <li>Usá un email real</li>
        <li>Contraseña segura</li>
        <li>Documentos claros</li>
      </ul>
    </div>
  </div>
</div>

</div>

<footer class="mt-4 text-center text-muted small">
  © <?= date('Y') ?> Jaguata
</footer>

</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const dueno = document.getElementById('dueno');
const paseador = document.getElementById('paseador');
const extra = document.getElementById('paseadorExtra');

function toggleDocs(){
  extra.classList.toggle('hidden', !paseador.checked);
}
dueno.addEventListener('change', toggleDocs);
paseador.addEventListener('change', toggleDocs);
toggleDocs();
</script>

</body>
</html>
