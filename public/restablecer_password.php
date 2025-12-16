<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;

AppConfig::init();

$rid   = (int)($_GET['rid'] ?? 0);
$token = trim($_GET['token'] ?? '');

$error = Session::getError();
$success = Session::getSuccess();

function h(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contraseña - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ✅ Mismo estilo que recuperar_password.php (idéntico) */
        :root {
            --jg-green: #3c6255;
            --jg-mint: #20c997;
            --jg-ink: #24343a;
            --jg-card: #ffffff;
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
            background: radial-gradient(circle at 20px 20px, rgba(255, 255, 255, .12) 6px, transparent 7px) 0 0 / 60px 60px, radial-gradient(circle at 50px 40px, rgba(255, 255, 255, .08) 4px, transparent 5px) 0 0 / 60px 60px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .25), rgba(0, 0, 0, .6));
            pointer-events: none;
        }

        .auth-shell {
            width: min(920px, 92vw);
            margin-inline: auto;
        }

        .auth-card {
            border: 0;
            border-radius: 22px;
            background: rgba(255, 255, 255, .85);
            backdrop-filter: saturate(140%) blur(8px);
            box-shadow: 0 18px 60px rgba(0, 0, 0, .18);
            overflow: hidden;
        }

        .illustration {
            background: radial-gradient(circle at top left, rgba(255, 255, 255, .18), transparent 55%), linear-gradient(135deg, #3c6255 0%, #20c997 100%);
            color: #f5fbfa;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(18px, 4vw, 30px);
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
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1.25;
            margin: 14px 0 8px;
        }

        .illustration-text {
            font-size: .92rem;
            opacity: .9;
            margin: 0 0 14px;
        }

        .form-pane {
            padding: clamp(18px, 4vw, 36px);
            background: rgba(255, 255, 255, .96);
        }

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

        h2 {
            color: var(--jg-green);
            font-weight: 800;
            letter-spacing: .2px;
        }

        .text-muted {
            color: #6b7b83 !important;
        }

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

        .btn-jg {
            background: var(--jg-green);
            border: 0;
            border-radius: 12px;
            padding: .9rem 1rem;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .18);
            transition: transform .08s ease, filter .2s ease, box-shadow .2s ease;
            color: #fff;
        }

        .btn-jg:hover {
            filter: brightness(.95);
            color: #fff;
        }

        .btn-jg:active {
            transform: translateY(1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .22);
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
            body {
                align-items: flex-start;
                padding: 18px 0;
            }

            .illustration {
                display: none;
            }

            .auth-shell {
                width: 100%;
                max-width: 520px;
                padding-inline: 12px;
            }

            .auth-card {
                border-radius: 18px;
                box-shadow: 0 14px 40px rgba(0, 0, 0, .22);
            }
        }
    </style>
</head>

<body>
    <main class="auth-shell">
        <div class="row g-0 auth-card">

            <div class="col-lg-6 illustration">
                <div>
                    <span class="illustration-pill"><i class="fa-solid fa-shield-dog"></i> Seguridad</span>
                    <h2 class="illustration-title">Creá una nueva contraseña</h2>
                    <p class="illustration-text">
                        Elegí una contraseña fuerte para mantener tu cuenta protegida.
                    </p>
                </div>
                <div class="text-white-50" style="font-size:.78rem;">Seguro • Rápido • Confiable 🐾</div>
            </div>

            <div class="col-lg-6">
                <div class="form-pane">
                    <div class="text-center mb-3">
                        <div class="logo-circle">
                            <img src="<?= AppConfig::getAssetsUrl(); ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="64" height="64">
                        </div>
                        <h2>Restablecer contraseña</h2>
                        <p class="text-muted mb-0">Ingresá tu nueva contraseña</p>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= h($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?= h($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($rid <= 0 || $token === ''): ?>
                        <div class="alert alert-warning">
                            Enlace inválido. Volvé a solicitar la recuperación.
                        </div>
                        <div class="text-center">
                            <a href="<?= AppConfig::getBaseUrl(); ?>/public/recuperar_password.php">Ir a recuperar contraseña</a>
                        </div>
                    <?php else: ?>

                        <form method="POST" action="<?= AppConfig::getBaseUrl(); ?>/public/guardar_password.php" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= h(Validaciones::generarCSRF()); ?>">
                            <input type="hidden" name="rid" value="<?= (int)$rid; ?>">
                            <input type="hidden" name="token" value="<?= h($token); ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold"><i class="fas fa-lock me-1"></i>Nueva contraseña</label>
                                <input type="password" name="password" class="form-control" required autocomplete="new-password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold"><i class="fas fa-lock me-1"></i>Confirmar contraseña</label>
                                <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-jg btn-lg">
                                    <i class="fas fa-check me-2"></i> Guardar
                                </button>
                            </div>

                            <div class="my-4 paw-divider">
                                <i class="fa-solid fa-paw"></i>
                                <span class="small">Seguro • Rápido • Confiable</span>
                                <i class="fa-solid fa-bone"></i>
                            </div>

                            <div class="text-center">
                                <a href="<?= AppConfig::getBaseUrl(); ?>/login.php">Volver al login</a>
                            </div>
                        </form>

                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>