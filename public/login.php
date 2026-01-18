<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Helpers/Validaciones.php';
require_once __DIR__ . '/../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;

AppConfig::init();

function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$titulo = 'Iniciar sesión - Jaguata';

/* ========= Estado / rol / sidebar (IGUAL HOME) ========= */
$logueado = Session::isLoggedIn();

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

$panelUrl   = $rol ? (BASE_URL . "/features/{$rol}/Dashboard.php") : null;
$urlVolver  = BASE_URL . '/public/index.php'; // o sobre-nosotros.php si querés

/* Sidebar según rol */
$sidebarPath = null;
if ($rol === 'dueno')    $sidebarPath = __DIR__ . '/../src/Templates/SidebarDueno.php';
if ($rol === 'paseador') $sidebarPath = __DIR__ . '/../src/Templates/SidebarPaseador.php';
if ($rol === 'admin')    $sidebarPath = __DIR__ . '/../src/Templates/SidebarAdmin.php';

/* ========= Auth ========= */
$auth = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->login();
    exit;
}

$error   = Session::getError();
$success = Session::getSuccess() ?? null;

$email = $_COOKIE['remember_email'] ?? '';

/* Assets */
$logoUrl = AppConfig::getAssetsUrl() . '/images/logojag.png';

/* Links */
$urlRegistro  = BASE_URL . '/registro.php';
$urlRecuperar = BASE_URL . '/recuperar_password.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= h($titulo) ?></title>

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

        /* ✅ CARD del login (misma vibra que tus cards) */
        .login-wrap{
            max-width: 980px;
            margin: 0 auto;
        }

        .login-grid{
            display:grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 18px;
            align-items: stretch;
        }
        @media (max-width: 992px){
            .login-grid{ grid-template-columns: 1fr; }
        }

        .login-hero{
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 12px 30px rgba(0,0,0,.06);
            background: linear-gradient(135deg, rgba(60,98,85,.10), rgba(32,201,151,.10));
            border: 1px solid rgba(0,0,0,.06);
            height: 100%;
        }
        .hero-pill{
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
        .hero-title{
            font-weight: 900;
            margin: 10px 0 6px;
            color: #222;
        }
        .hero-text{
            color:#555;
            margin-bottom: 12px;
            line-height: 1.6;
        }
        .hero-list{
            padding-left: 0;
            list-style: none;
            margin: 0;
        }
        .hero-list li{
            display:flex;
            gap:.55rem;
            align-items:flex-start;
            margin-bottom:.45rem;
            color:#444;
        }
        .hero-list i{ margin-top:.2rem; color: var(--verde-jaguata, #3c6255); }

        .logo-circle{
            width: 86px;
            height: 86px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 10px 22px rgba(0,0,0,.06);
            display:flex;
            align-items:center;
            justify-content:center;
            margin: 0 auto 10px;
        }

        .form-control{ border-radius: 14px; }
        .btn-big{
            border-radius: 14px;
            font-weight: 800;
            padding: .9rem 1rem;
        }
        .btn-eye{
            border-radius: 14px;
        }

        .mini-links a{
            text-decoration:none;
            font-weight: 700;
        }
    </style>
</head>

<body class="<?= $rol ? '' : 'no-sidebar' ?>">

<div class="layout">

    <?php if ($rol && $sidebarPath && file_exists($sidebarPath)): ?>
        <?php include $sidebarPath; ?>
    <?php endif; ?>

    <main class="main-content">
        <div class="py-2 login-wrap">

            <!-- Header (igual dashboard) -->
            <div class="header-box header-dashboard mb-3">
                <div>
                    <h1 class="mb-1">Iniciar sesión</h1>
                    <p class="mb-0">
                        Accedé a Jaguata para gestionar paseos, calificaciones y seguridad.
                    </p>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <?php if ($panelUrl): ?>
                        <a href="<?= h($panelUrl) ?>" class="btn btn-outline-light border">
                            <i class="fa-solid fa-gauge-high me-2"></i>Panel
                        </a>
                    <?php endif; ?>

                    <a href="<?= h($urlVolver) ?>" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div><?= h($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success border d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-circle-check"></i>
                    <div><?= h($success) ?></div>
                </div>
            <?php endif; ?>

            <div class="login-grid">

                <!-- Izquierda: beneficios (como tu home, cards y texto) -->
                <div class="login-hero">
                    <span class="hero-pill">
                        <i class="fa-solid fa-paw"></i> Jaguata
                    </span>

                    <h3 class="hero-title">¡Hola, <?= h($usuarioNombre) ?>! 🐾</h3>
                    <p class="hero-text">
                        Iniciá sesión para acceder a tu panel y gestionar tus actividades:
                        paseos, estados, comprobantes, notificaciones y reputación.
                    </p>

                    <ul class="hero-list">
                        <li><i class="fa-solid fa-shield-dog"></i><span>Paseadores verificados y cuentas aprobadas por el administrador.</span></li>
                        <li><i class="fa-solid fa-calendar-check"></i><span>Solicitudes y confirmaciones con estados en tiempo real.</span></li>
                        <li><i class="fa-solid fa-star"></i><span>Calificaciones reales para mayor confianza en la comunidad.</span></li>
                    </ul>

                    <div class="mt-3 text-muted small">
                        ¿No tenés cuenta? <a href="<?= h($urlRegistro) ?>" class="fw-bold text-success text-decoration-none">Crear cuenta</a>
                    </div>
                </div>

                <!-- Derecha: Formulario (en section-card como tus pantallas) -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Acceso a la plataforma
                    </div>
                    <div class="section-body">

                        <div class="text-center mb-3">
                            <div class="logo-circle">
                                <img src="<?= h($logoUrl) ?>" alt="Jaguata" width="64" height="64">
                            </div>
                            <div class="fw-bold" style="font-size:1.15rem;">Bienvenido a Jaguata</div>
                            <div class="text-muted small">Ingresá con tu email y contraseña</div>
                        </div>

                        <form method="POST" action="" autocomplete="off" novalidate>
                            <input type="hidden" name="csrf_token"
                                value="<?= h(Validaciones::generarCSRF()); ?>">

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fa-solid fa-envelope me-2"></i>Email
                                </label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    value="<?= h($email) ?>"
                                    placeholder="tu@email.com"
                                    autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fa-solid fa-lock me-2"></i>Contraseña
                                </label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password"
                                        name="password"
                                        placeholder="Tu contraseña"
                                        required
                                        autocomplete="off"
                                        aria-describedby="togglePassword">
                                    <button
                                        class="btn btn-outline-secondary btn-eye"
                                        type="button"
                                        id="togglePassword"
                                        aria-label="Mostrar/ocultar contraseña">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="remember_me"
                                        name="remember_me"
                                        <?= !empty($_COOKIE['remember_email']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="remember_me">Recordar mi sesión</label>
                                </div>

                                <a class="small fw-semibold" href="<?= h($urlRecuperar) ?>">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </div>

                            <div class="d-grid mb-2">
                                <button type="submit" class="btn btn-success btn-big">
                                    <i class="fa-solid fa-paw me-2"></i> Iniciar sesión
                                </button>
                            </div>

                            <div class="mini-links d-flex justify-content-between flex-wrap gap-2 mt-3">
                                <a href="<?= h($urlRegistro) ?>">
                                    <i class="fa-solid fa-user-plus me-2"></i>Crear cuenta
                                </a>
                                <a href="<?= h(BASE_URL . '/contacto.php') ?>">
                                    <i class="fa-solid fa-headset me-2"></i>Soporte
                                </a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Login
            </footer>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle contraseña
  document.getElementById('togglePassword')?.addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = this.querySelector('i');
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    icon.classList.toggle('fa-eye', !isPass);
    icon.classList.toggle('fa-eye-slash', isPass);
    input.focus();
  });
</script>
</body>
</html>
