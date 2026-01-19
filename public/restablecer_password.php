<?php
/* =========================================================
   C:\xampp\htdocs\jaguata\public\restablecer_password.php
   Pantalla: nueva contraseña (estilo dashboards)
========================================================= */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;

AppConfig::init();

function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ========= Estado / rol / sidebar (IGUAL HOME/LOGIN) ========= */
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
$panelUrl      = $rol ? (BASE_URL . "/features/{$rol}/Dashboard.php") : null;
$urlVolver     = BASE_URL . '/public/login.php';

$sidebarPath = null;
if ($rol === 'dueno')    $sidebarPath = __DIR__ . '/../src/Templates/SidebarDueno.php';
if ($rol === 'paseador') $sidebarPath = __DIR__ . '/../src/Templates/SidebarPaseador.php';
if ($rol === 'admin')    $sidebarPath = __DIR__ . '/../src/Templates/SidebarAdmin.php';

/* Link data */
$rid   = (int)($_GET['rid'] ?? 0);
$token = trim((string)($_GET['token'] ?? ''));

$error   = Session::getError();
$success = Session::getSuccess();

$logoUrl    = AppConfig::getAssetsUrl() . '/images/logojag.png';
$actionSave = BASE_URL . '/public/guardar_password.php';
$urlRecup   = BASE_URL . '/public/recuperar_password.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Restablecer contraseña - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
                width: 100% !important;
                padding: calc(16px + var(--topbar-h)) 16px 16px !important;
            }
        }

        .wrap{
            max-width: 980px;
            margin: 0 auto;
        }

        .grid{
            display:grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 18px;
            align-items: stretch;
        }
        @media (max-width: 992px){
            .grid{ grid-template-columns: 1fr; }
        }

        .hero{
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 12px 30px rgba(0,0,0,.06);
            background: linear-gradient(135deg, rgba(60,98,85,.10), rgba(32,201,151,.10));
            border: 1px solid rgba(0,0,0,.06);
            height: 100%;
        }
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
        .hero-title{ font-weight: 900; margin: 10px 0 6px; color: #222; }
        .hero-text{ color:#555; margin-bottom: 12px; line-height: 1.6; }

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
        .btn-eye{ border-radius: 14px; }
    </style>
</head>

<body class="<?= $rol ? '' : 'no-sidebar' ?>">

<div class="layout">
    <?php if ($rol && $sidebarPath && file_exists($sidebarPath)): ?>
        <?php include $sidebarPath; ?>
    <?php endif; ?>

    <main class="main-content">
        <div class="py-2 wrap">

            <div class="header-box header-dashboard mb-3">
                <div>
                    <h1 class="mb-1">Restablecer contraseña</h1>
                    <p class="mb-0">Elegí una nueva contraseña para tu cuenta.</p>
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

            <?php if ($success): ?>
                <div class="alert alert-success border d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-circle-check"></i>
                    <div><?= h($success) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger border d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div><?= h($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="grid">

                <div class="hero">
                    <span class="pill">
                        <i class="fa-solid fa-lock"></i> Seguridad
                    </span>

                    <h3 class="hero-title">Creá una nueva contraseña 🔐</h3>
                    <p class="hero-text">
                        Para mantener tu cuenta protegida, elegí una contraseña fuerte.
                        Este enlace es único y tiene vencimiento.
                    </p>

                    <ul class="hero-list">
                        <li><i class="fa-solid fa-key"></i><span>Mínimo 8 caracteres.</span></li>
                        <li><i class="fa-solid fa-shield-heart"></i><span>No compartas tu contraseña con nadie.</span></li>
                        <li><i class="fa-solid fa-clock"></i><span>Si el enlace expiró, solicitá uno nuevo.</span></li>
                    </ul>

                    <div class="mt-3 text-muted small">
                        ¿No te llegó el correo? <a href="<?= h($urlRecup) ?>" class="fw-bold text-success text-decoration-none">Volver a solicitar</a>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <i class="fa-solid fa-user-lock me-2"></i>Actualizar contraseña
                    </div>

                    <div class="section-body">

                        <div class="text-center mb-3">
                            <div class="logo-circle">
                                <img src="<?= h($logoUrl) ?>" alt="Jaguata" width="64" height="64">
                            </div>
                            <div class="fw-bold" style="font-size:1.15rem;">Nueva contraseña</div>
                            <div class="text-muted small">Completá los campos</div>
                        </div>

                        <?php if ($rid <= 0 || $token === ''): ?>
                            <div class="alert alert-warning border d-flex align-items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <div>Enlace inválido. Volvé a solicitar la recuperación.</div>
                            </div>
                            <div class="text-center">
                                <a href="<?= h($urlRecup) ?>">Ir a recuperar contraseña</a>
                            </div>
                        <?php else: ?>

                            <form method="POST" action="<?= h($actionSave) ?>" autocomplete="off" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= h(Validaciones::generarCSRF()); ?>">
                                <input type="hidden" name="rid" value="<?= (int)$rid; ?>">
                                <input type="hidden" name="token" value="<?= h($token); ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fa-solid fa-lock me-2"></i>Nueva contraseña
                                    </label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control"
                                               required autocomplete="new-password" placeholder="Tu nueva contraseña">
                                        <button class="btn btn-outline-secondary btn-eye" type="button" id="togglePassword">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fa-solid fa-lock me-2"></i>Confirmar contraseña
                                    </label>
                                    <input type="password" name="confirm_password" class="form-control"
                                           required autocomplete="new-password" placeholder="Repetí tu contraseña">
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-big">
                                        <i class="fa-solid fa-check me-2"></i> Guardar
                                    </button>
                                </div>

                                <div class="text-center text-muted small mt-3">
                                    Al guardar, este enlace quedará marcado como usado.
                                </div>
                            </form>

                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Restablecer
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
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    icon.classList.toggle('fa-eye', !isPass);
    icon.classList.toggle('fa-eye-slash', isPass);
    input.focus();
  });
</script>
</body>
</html>
