<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
AppConfig::init();
function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$error   = Session::getError();
$success = Session::getSuccess();

$titulo = 'Bases y Condiciones - Jaguata';
$descripcion = 'Términos de uso de la plataforma Jaguata, servicio de conexión entre dueños y paseadores de mascotas.';

$inicioUrl = AppConfig::getBaseUrl();
$panelUrl  = null;
$urlVolver = AppConfig::getBaseUrl() . '/registro.php';
if (Session::isLoggedIn()) {
    $rol = method_exists(Session::class, 'getUsuarioRolSeguro')
        ? Session::getUsuarioRolSeguro()
        : (Session::get('rol') ?? null);

    $rol = strtolower(trim((string)$rol));
    if (in_array($rol, ['admin','dueno','paseador'], true)) {
        $panelUrl = AppConfig::getBaseUrl() . "/features/{$rol}/Dashboard.php";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($titulo) ?></title>
  <meta name="description" content="<?= h($descripcion) ?>">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- Theme global (IGUAL que tu Registro) -->
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

    /* Estilo consistente con Registro */
    .legal-pill{
      display:inline-flex;
      align-items:center;
      gap:.45rem;
      padding:.25rem .7rem;
      border-radius:999px;
      font-size:.78rem;
      font-weight:800;
      background: rgba(60,98,85,.10);
      border: 1px solid rgba(60,98,85,.18);
      color: var(--verde-jaguata, #3c6255);
    }

    .toc a{ text-decoration:none; }
    .toc a:hover{ text-decoration:underline; }

    .section-body p, .section-body li { line-height: 1.85; }
  </style>
</head>

<body>
<div class="layout">
<main class="main-content">

<!-- HEADER (IGUAL estilo Registro) -->
<div class="header-box header-dashboard mb-3">
  <div>
    <h1 class="mb-1">Bases y Condiciones ⚖️</h1>
    <p class="mb-0">
      Conocé los términos de uso y las responsabilidades para dueños y paseadores dentro de Jaguata.
    </p>
  </div>

  <div class="d-flex gap-2">
      <a href="<?= h($urlVolver) ?>" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>

    <?php if ($panelUrl): ?>
     
    <?php endif; ?>
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

  <!-- SIDEBAR IZQ (como tu columna derecha en Registro, pero acá va a la izquierda) -->
  <div class="col-lg-4">
    <div class="section-card h-100">
      <div class="section-header">
        <i class="fa-solid fa-circle-info me-2"></i>Resumen
      </div>
      <div class="section-body">

        <span class="legal-pill mb-2">
          <i class="fa-solid fa-scale-balanced"></i> Información legal
        </span>

        <h2 class="h5 fw-bold mb-2">Uso responsable de Jaguata</h2>
        <p class="text-muted mb-3">
          Estas Bases y Condiciones regulan el uso de la plataforma por parte de dueños y paseadores.
          Al usar Jaguata, aceptás estas reglas de convivencia y responsabilidad.
        </p>

        <div class="toc text-muted small mb-3">
          <div class="fw-bold mb-2">Contenido rápido</div>
          <div class="d-flex flex-column gap-1">
            <a href="#aceptacion"><i class="fa-solid fa-chevron-right me-2"></i>Aceptación</a>
            <a href="#servicio"><i class="fa-solid fa-chevron-right me-2"></i>Servicio</a>
            <a href="#registro"><i class="fa-solid fa-chevron-right me-2"></i>Registro</a>
            <a href="#responsabilidades"><i class="fa-solid fa-chevron-right me-2"></i>Responsabilidades</a>
            <a href="#privacidad"><i class="fa-solid fa-chevron-right me-2"></i>Privacidad</a>
            <a href="#limitacion"><i class="fa-solid fa-chevron-right me-2"></i>Limitación</a>
            <a href="#ley"><i class="fa-solid fa-chevron-right me-2"></i>Ley aplicable</a>
          </div>
        </div>

        <hr>

        <ul class="list-unstyled text-muted mb-3">
          <li class="mb-2">
            <i class="fa-solid fa-circle-check me-2 text-success"></i>
            Alcance del servicio e intermediación.
          </li>
          <li class="mb-2">
            <i class="fa-solid fa-circle-check me-2 text-success"></i>
            Responsabilidades de dueños y paseadores.
          </li>
          <li class="mb-2">
            <i class="fa-solid fa-circle-check me-2 text-success"></i>
            Privacidad y limitación de responsabilidad.
          </li>
        </ul>

        <div class="d-grid gap-2">
          <a href="<?= h($inicioUrl); ?>/registro.php" class="btn btn-success">
            <i class="fa-solid fa-user-plus me-1"></i> Crear cuenta
          </a>
          <a href="<?= h($inicioUrl); ?>/contacto.php" class="btn btn-outline-secondary">
            <i class="fa-regular fa-envelope me-1"></i> Consultar soporte
          </a>
        </div>

      </div>
    </div>
  </div>

  <!-- CONTENIDO PRINCIPAL -->
  <div class="col-lg-8">
    <div class="section-card">
      <div class="section-header">
        <i class="fa-solid fa-file-contract me-2"></i>Términos y Condiciones de Uso
      </div>
      <div class="section-body">

        <p class="text-muted mb-4">
          Al registrarte o utilizar Jaguata, confirmás que leíste, comprendiste y aceptás estas condiciones.
          Si no estás de acuerdo, no debés utilizar la plataforma.
        </p>

        <ol class="text-muted">
          <li class="mb-3" id="aceptacion">
            <strong>Aceptación de los términos:</strong>
            El uso de Jaguata implica aceptación plena de estas bases y condiciones.
          </li>

          <li class="mb-3" id="servicio">
            <strong>Descripción del servicio:</strong>
            Jaguata conecta a dueños con paseadores, actuando como intermediario tecnológico.
          </li>

          <li class="mb-3" id="registro">
            <strong>Registro de usuarios:</strong>
            Los usuarios deben brindar datos reales, mantenerlos actualizados y resguardar sus credenciales.
          </li>

          <li class="mb-3" id="responsabilidades">
            <strong>Responsabilidades de los paseadores:</strong>
            Cumplir lo pactado, cuidar a las mascotas y actuar con responsabilidad durante el servicio.
          </li>

          <li class="mb-3">
            <strong>Responsabilidades de los dueños:</strong>
            Brindar información veraz de la mascota y cumplir acuerdos y pagos pactados.
          </li>

          <li class="mb-3">
            <strong>Uso adecuado:</strong>
            Prohibido el uso para fines ilegales, ofensivos o fraudulentos.
          </li>

          <li class="mb-3" id="privacidad">
            <strong>Privacidad:</strong>
            Jaguata procura proteger datos conforme a normativa vigente en Paraguay.
          </li>

          <li class="mb-3" id="limitacion">
            <strong>Limitación de responsabilidad:</strong>
            Jaguata no se responsabiliza por accidentes, extravíos o conductas indebidas durante paseos.
          </li>

          <li class="mb-3">
            <strong>Modificaciones:</strong>
            Estas condiciones pueden actualizarse; se informará mediante la plataforma.
          </li>

          <li class="mb-0" id="ley">
            <strong>Ley aplicable:</strong>
            Se rige por leyes de Paraguay, con jurisdicción en Asunción.
          </li>
        </ol>

        <div class="mt-4 d-flex flex-wrap gap-2">
          <?php if ($panelUrl): ?>
          <?php endif; ?>
        </div>

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
</body>
</html>
