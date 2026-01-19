<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$titulo      = 'Home - Jaguata';
$descripcion = 'Jaguata conecta dueños con paseadores verificados en Paraguay.';

$logueado = Session::isLoggedIn();

/* Rol seguro (solo los 3) */
$rol = null;
if ($logueado) {
    $rolTmp = method_exists(Session::class, 'getUsuarioRolSeguro')
        ? Session::getUsuarioRolSeguro()
        : (Session::get('rol') ?? null);

    $rolTmp = strtolower(trim((string)$rolTmp));
    if (in_array($rolTmp, ['admin','dueno','paseador'], true)) {
        $rol = $rolTmp;
    }
}

$usuarioNombre = $logueado ? (Session::getUsuarioNombre() ?? 'Usuario') : 'Invitado/a';
$estadoUsuario = $logueado ? (Session::getUsuarioEstado() ?? null) : null;

$baseFeatures = $rol ? (BASE_URL . "/features/{$rol}") : null;
$panelUrl     = $rol ? (BASE_URL . "/features/{$rol}/Dashboard.php") : null;

/* Sidebar según rol */
$sidebarPath = null;
if ($rol === 'dueno')    $sidebarPath = __DIR__ . '/../src/Templates/SidebarDueno.php';
if ($rol === 'paseador') $sidebarPath = __DIR__ . '/../src/Templates/SidebarPaseador.php';
if ($rol === 'admin')    $sidebarPath = __DIR__ . '/../src/Templates/SidebarAdmin.php';

/* Assets */
$videoUrl = BASE_URL . '/assets/uploads/perfiles/gif1.mp4';
$logoUrl  = BASE_URL . '/public/assets/images/logojag.png';

/* Suscripción (UI por ahora) */
$precioSuscripcion = 50000;
$planNombre = 'Paseador Pro';
$beneficiosPlan = [
    'Paseos ilimitados (sin tope mensual)',
    'Más visibilidad en búsquedas',
    'Acceso a estadísticas avanzadas',
    'Soporte prioritario'
];

/* Links */
$urlSuscripcion = BASE_URL . '/features/paseador/Suscripcion.php'; // creás después
$urlRegistro    = BASE_URL . '/registro.php';
$urlLogin       = BASE_URL . '/login.php';
$urlContacto    = BASE_URL . '/contacto.php';
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

    <!-- ✅ tu theme (mismo que dashboards) -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html, body { height: 100%; }
        body { background: var(--gris-fondo, #f4f6f9); }

        .layout{ display:flex; min-height:100vh; }

        main.main-content{
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
            width: 100%;
        }
        body.no-sidebar main.main-content{ margin-left: 0 !important; }

        @media (max-width: 768px){
            main.main-content{
                margin-left: 0;
                margin-top: 0 !important;
                width: 100% !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        /* Cards mini */
        .dash-card{
            background:#ffffff;
            border-radius:18px;
            padding:18px 20px;
            box-shadow:0 12px 30px rgba(0,0,0,.06);
            text-align:center;
            display:flex;
            flex-direction:column;
            justify-content:center;
            gap:6px;
            height:100%;
        }
        .dash-card-icon{ font-size:2rem; margin-bottom:6px; }
        .dash-card-value{ font-size:1.2rem; font-weight:800; color:#222; }
        .dash-card-label{ font-size:.92rem; color:#555; }

        .icon-green{ color: var(--verde-jaguata, #3c6255); }
        .icon-blue{ color:#0d6efd; }
        .icon-yellow{ color:#ffc107; }
        .icon-red{ color:#dc3545; }

        /* Pills */
        .pill{
            display:inline-flex;
            align-items:center;
            gap:.4rem;
            padding:.25rem .65rem;
            border-radius:999px;
            font-size:.78rem;
            background: rgba(60, 98, 85, .10);
            color: var(--verde-jaguata, #3c6255);
            border: 1px solid rgba(60, 98, 85, .18);
            font-weight:700;
        }
        .role-chip{
            font-size:.78rem;
            border-radius: 999px;
            padding:.2rem .6rem;
            border:1px solid rgba(0,0,0,.08);
            background:#fff;
        }

        /* Mini cards */
        .mini-card{
            border-radius: 16px;
            border: 0;
            box-shadow: 0 10px 22px rgba(0,0,0,.06);
        }

        .price-badge{
            font-size: 1.6rem;
            font-weight: 900;
        }

        .check-li{
            display:flex;
            align-items:flex-start;
            gap:.55rem;
            margin-bottom:.45rem;
            color:#444;
        }
        .check-li i{ margin-top:.2rem; }

        /* ✅ HERO (video + texto) para que se vea lindo */
        .hero-home{
            display:grid;
            grid-template-columns: 300px 1fr;
            gap: 18px;
            align-items:center;
        }
        @media (max-width: 992px){
            .hero-home{
                grid-template-columns: 1fr;
                text-align:center;
            }
            .hero-actions{
                justify-content:center !important;
            }
        }

        .home-video{
            width: 300px;
            height: 300px;
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 16px 40px rgba(0,0,0,.10);
            display:flex;
            align-items:center;
            justify-content:center;
            margin: 0 auto;
        }
        .home-video video{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display:block;
        }

        .intro-text{
            max-width: 620px;
            font-size: .98rem;
            line-height: 1.65;
        }

        @media (max-width: 576px){
            .home-video{
                width: 240px;
                height: 240px;
            }
        }
    </style>
</head>

<body class="<?= $rol ? '' : 'no-sidebar' ?>">

<div class="layout">

    <?php if ($rol && $sidebarPath && file_exists($sidebarPath)): ?>
        <?php include $sidebarPath; ?>
    <?php endif; ?>

    <main class="main-content">
        <div class="py-2">

            <!-- Header (igual dashboard) -->
            <div class="header-box header-dashboard mb-2">
                <div>
                    

                    <h1 class="mb-1">
                        <?= $logueado ? ('¡Hola, ' . h($usuarioNombre) . '! 🐾') : 'Jaguata 🐾' ?>
                    </h1>
                    <p class="mb-0">
                        <?= $logueado
                            ? 'Tu centro de control: paseos, seguridad y bienestar en un solo lugar.'
                            : 'Paseos seguros, personas confiables y mascotas felices. Conectamos dueños y paseadores verificados en Paraguay.' ?>
                    </p>
                </div>
                <i class="fas fa-dog fa-3x opacity-75"></i>
            </div>

            <?php if ($logueado && $estadoUsuario && $estadoUsuario !== 'aprobado'): ?>
                <div class="alert alert-warning border d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Cuenta pendiente:</strong> tu estado es <b><?= h($estadoUsuario) ?></b>.
                        Algunas funciones pueden estar limitadas hasta aprobación.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Métricas -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-shield-dog dash-card-icon icon-green"></i>
                        <div class="dash-card-value">Seguridad</div>
                        <div class="dash-card-label">Paseadores verificados</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-calendar-check dash-card-icon icon-blue"></i>
                        <div class="dash-card-value">Reservas</div>
                        <div class="dash-card-label">Solicitá en minutos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-star dash-card-icon icon-yellow"></i>
                        <div class="dash-card-value">Reputación</div>
                        <div class="dash-card-label">Calificaciones reales</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dash-card">
                        <i class="fas fa-briefcase dash-card-icon icon-red"></i>
                        <div class="dash-card-value">Oportunidad</div>
                        <div class="dash-card-label">Trabajo flexible</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">

                <!-- Principal -->
                <div class="col-lg-8">

                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-home me-2"></i>Bienvenido a Jaguata
                        </div>
                        <div class="section-body">

                            <!-- ✅ HERO lindo (video al lado del texto) -->
                            <div class="hero-home">

                                <div class="home-video">
                                    <video
                                        src="<?= h($videoUrl) ?>"
                                        preload="metadata"
                                        autoplay
                                        muted
                                        loop
                                        playsinline
                                        controlslist="nodownload">
                                        Tu navegador no soporta videos HTML5.
                                    </video>
                                </div>

                                <div>
                                    <p class="text-muted mb-3 intro-text">
                                        Jaguata nace para ayudar a dueños con poco tiempo y a paseadores responsables que buscan
                                        ingresos extra, promoviendo bienestar y felicidad animal en cada paseo.
                                    </p>

                                    <div class="d-flex flex-wrap gap-2 hero-actions">
                                        <?php if (!$logueado): ?>
                                            <a href="<?= h($urlRegistro) ?>" class="btn btn-success px-4">
                                                <i class="fas fa-user-plus me-2"></i>Crear cuenta
                                            </a>
                                            <a href="<?= h($urlLogin) ?>" class="btn btn-outline-secondary px-4">
                                                <i class="fas fa-right-to-bracket me-2"></i>Iniciar sesión
                                            </a>
                                            <a href="<?= h($urlContacto) ?>" class="btn btn-outline-primary px-4">
                                                <i class="fas fa-envelope me-2"></i>Contacto
                                            </a>
                                        <?php else: ?>
                                            <?php if ($panelUrl): ?>
                                                <a href="<?= h($panelUrl) ?>" class="btn btn-success px-4">
                                                    <i class="fas fa-gauge-high me-2"></i>Ir a mi panel
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($baseFeatures): ?>
                                                <a href="<?= h($baseFeatures) ?>" class="btn btn-outline-primary px-4">
                                                    <i class="fas fa-layer-group me-2"></i>Mis funciones
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= h($urlContacto) ?>" class="btn btn-outline-secondary px-4">
                                                <i class="fas fa-envelope me-2"></i>Contacto
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- ✅ NUEVO: Beneficios por rol -->
                    <div class="section-card mt-3">
                        <div class="section-header">
                            <i class="fa-solid fa-people-group me-2"></i>Beneficios para todos
                        </div>
                        <div class="section-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card mini-card">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-2"><i class="fa-solid fa-dog me-2 text-success"></i>Para dueños</h6>
                                            <p class="text-muted mb-0 small">Elegí paseador por reputación y disponibilidad. Ideal si tenés poco tiempo.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card mini-card">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-2"><i class="fa-solid fa-person-walking me-2 text-primary"></i>Para paseadores</h6>
                                            <p class="text-muted mb-0 small">Conseguí más clientes, administrá tu agenda y aumentá tus ingresos.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card mini-card">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-2"><i class="fa-solid fa-heart me-2 text-danger"></i>Para mascotas</h6>
                                            <p class="text-muted mb-0 small">Rutina, bienestar, ejercicio y paseos seguros con gente responsable.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
<!-- ✅ NUEVO: Qué podés hacer en Jaguata -->
<div class="section-card mt-3">
  <div class="section-header">
    <i class="fa-solid fa-bolt me-2"></i>¿Qué podés hacer en Jaguata?
  </div>
  <div class="section-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="dash-card text-start">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa-solid fa-magnifying-glass icon-green"></i>
            <b>Buscar y elegir paseador</b>
          </div>
          <div class="dash-card-label">Filtrá por reputación, disponibilidad y zona.</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="dash-card text-start">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa-solid fa-calendar-check icon-blue"></i>
            <b>Solicitar paseos en minutos</b>
          </div>
          <div class="dash-card-label">Seleccioná mascota(s), ubicación y horario.</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="dash-card text-start">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa-solid fa-receipt icon-yellow"></i>
            <b>Pagos y comprobantes</b>
          </div>
          <div class="dash-card-label">Historial de pagos + comprobantes por paseo.</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="dash-card text-start">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa-solid fa-star icon-red"></i>
            <b>Calificar y generar reputación</b>
          </div>
          <div class="dash-card-label">Las reseñas mejoran la confianza en la comunidad.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ✅ NUEVO: Seguridad y confianza -->
<div class="section-card mt-3">
  <div class="section-header">
    <i class="fa-solid fa-shield-heart me-2"></i>Seguridad y confianza
  </div>
  <div class="section-body">
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card mini-card">
          <div class="card-body">
            <h6 class="fw-bold mb-2"><i class="fa-solid fa-id-card me-2 text-success"></i>Verificación</h6>
            <p class="text-muted mb-0 small">Los paseadores pueden cargar documentos y el admin aprueba cuentas.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card mini-card">
          <div class="card-body">
            <h6 class="fw-bold mb-2"><i class="fa-solid fa-bell me-2 text-primary"></i>Notificaciones</h6>
            <p class="text-muted mb-0 small">Te avisamos cambios de estado, confirmaciones y novedades del paseo.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card mini-card">
          <div class="card-body">
            <h6 class="fw-bold mb-2"><i class="fa-solid fa-star me-2 text-warning"></i>Reputación</h6>
            <p class="text-muted mb-0 small">Calificaciones reales para elegir con más seguridad.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ✅ NUEVO: Cobertura -->
<div class="section-card mt-3">
  <div class="section-header">
    <i class="fa-solid fa-location-dot me-2"></i>Zonas de cobertura
  </div>
  <div class="section-body">
    <p class="text-muted mb-2">
      Jaguata está pensado para operar por zonas, facilitando encontrar paseadores cercanos al punto de retiro.
    </p>
    <div class="d-flex flex-wrap gap-2">
      <span class="badge text-bg-light border">San Lorenzo</span>
      <span class="badge text-bg-light border">Asunción</span>
      <span class="badge text-bg-light border">Luque</span>
      <span class="badge text-bg-light border">Fernando de la Mora</span>
      <span class="badge text-bg-light border">Lambaré</span>
      <span class="badge text-bg-light border">Otros (según disponibilidad)</span>
    </div>
  </div>
</div>

<!-- ✅ NUEVO: Mini testimonios -->
<div class="section-card mt-3">
  <div class="section-header">
    <i class="fa-solid fa-comments me-2"></i>Opiniones de la comunidad
  </div>
  <div class="section-body">
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card mini-card">
          <div class="card-body">
            <p class="mb-2">“Ahora reservo paseos en minutos y veo el historial de todo.”</p>
            <div class="text-muted small">— Dueña</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card mini-card">
          <div class="card-body">
            <p class="mb-2">“Me ayudó a conseguir clientes y organizar mis horarios.”</p>
            <div class="text-muted small">— Paseador</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card mini-card">
          <div class="card-body">
            <p class="mb-2">“La reputación y las calificaciones hacen que confíe más.”</p>
            <div class="text-muted small">— Dueña</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

                </div>

                <!-- Lateral -->
                <div class="col-lg-4">

                    <!-- ✅ Suscripción Paseador -->
                    <div class="section-card mb-3">
                        <div class="section-header">
                            <i class="fa-solid fa-crown me-2"></i>Suscripción <?= h($planNombre) ?>
                        </div>
                        <div class="section-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="text-muted small">Paseos ilimitados para paseadores</div>
                                <span class="badge bg-success">₲<?= number_format($precioSuscripcion, 0, ',', '.') ?>/mes</span>
                            </div>

                            <div class="mb-3">
                                <div class="price-badge text-success">
                                    ₲<?= number_format($precioSuscripcion, 0, ',', '.') ?>
                                    <span class="text-muted fs-6 fw-semibold">/ mensual</span>
                                </div>
                                <div class="text-muted small">Pagás una vez por mes y podés tomar los paseos que quieras.</div>
                            </div>

                            <div class="mb-3">
                                <?php foreach ($beneficiosPlan as $b): ?>
                                    <div class="check-li">
                                        <i class="fa-solid fa-circle-check text-success"></i>
                                        <span class="small"><?= h($b) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($rol === 'paseador'): ?>
                                <a href="<?= h($urlSuscripcion) ?>" class="btn btn-success w-100">
                                    <i class="fa-solid fa-credit-card me-2"></i>Gestionar suscripción
                                </a>
                                <div class="text-muted small mt-2">
                                    * En la siguiente pantalla podrás pagar/renovar y ver tu estado.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border mb-2">
                                    <i class="fa-solid fa-circle-info me-2"></i>
                                    Disponible para <b>paseadores</b>.
                                </div>
                                <?php if (!$logueado): ?>
                                    <a href="<?= h($urlRegistro) ?>" class="btn btn-outline-success w-100">
                                        <i class="fa-solid fa-user-plus me-2"></i>Quiero ser paseador
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Nuestra Esencia -->
                    <div class="section-card mb-3">
                        <div class="section-header">
                            <i class="fas fa-bullseye me-2"></i>Nuestra Esencia
                        </div>
                        <div class="section-body">
                            <p class="mb-2"><b>Misión:</b> Conectar de forma segura a dueños y paseadores, promoviendo bienestar, confianza y felicidad animal.</p>
                            <p class="mb-2"><b>Visión:</b> Ser la plataforma más confiable en Paraguay y expandir el impacto en la región.</p>
                            <p class="mb-0"><b>Valores:</b> Amor y respeto animal · Confianza y transparencia · Seguridad y responsabilidad</p>
                        </div>
                    </div>

                    <!-- FAQ rápido -->
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-circle-question me-2"></i>Preguntas rápidas
                        </div>
                        <div class="section-body">
                            <div class="mb-2">
                                <b>¿Cómo se elige un paseador?</b>
                                <div class="text-muted small">Por disponibilidad, perfil, calificación y experiencia.</div>
                            </div>
                            <div class="mb-2">
                                <b>¿Qué incluye la suscripción?</b>
                                <div class="text-muted small">Para paseadores: paseos ilimitados y mayor visibilidad.</div>
                            </div>
                            <div>
                                <b>¿Puedo cancelar?</b>
                                <div class="text-muted small">Sí, al finalizar tu período mensual.</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
<div class="home-narrow">
            
</div>
            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Home
            </footer>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
