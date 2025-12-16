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

// Título por defecto
$titulo = 'Iniciar sesión - Jaguata';

// Controlador de autenticación
$auth = new AuthController();

// Si envían el formulario (POST) → intentamos loguear
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->login();
    exit;
}

// Si es GET: mostramos el formulario
$error   = Session::getError();
$success = Session::getSuccess() ?? null;

// Para rellenar el campo email SOLO si existe cookie (recordarme)
// (no usamos $_POST para que al recargar quede limpio)
$email = $_COOKIE['remember_email'] ?? '';
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --jg-green: #3c6255;
            --jg-mint: #20c997;
            --jg-ink: #24343a;
            --jg-card: #ffffff;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(160deg, var(--jg-green) 0%, var(--jg-mint) 100%);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", "Apple Color Emoji", "Segoe UI Emoji";
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
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
            width: min(1100px, 92vw);
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
            background: radial-gradient(circle at top left, rgba(255, 255, 255, .18), transparent 55%),
                linear-gradient(135deg, #3c6255 0%, #20c997 100%);
            color: #f5fbfa;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(18px, 4vw, 30px);
        }

        .illustration-inner {
            max-width: 360px;
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
            transition: transform .08s ease, filter .2s ease, box-shadow .2s ease;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .18);
        }

        .btn-jg:hover {
            filter: brightness(.95);
        }

        .btn-jg:active {
            transform: translateY(1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .22);
        }

        .btn-outline-light {
            border-width: 2px;
        }

        .feature-badges i {
            color: var(--jg-green);
        }

        .feature-badges small {
            color: #516169;
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
                justify-content: center;
                padding: 18px 0;
            }

            .illustration {
                display: none;
            }

            .auth-card {
                border-radius: 18px;
                box-shadow: 0 14px 40px rgba(0, 0, 0, .22);
            }

            .auth-shell {
                width: 100%;
                max-width: 500px;
                padding-inline: 12px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 16px 0;
            }

            .form-pane {
                padding: 20px 18px 24px;
            }

            h2 {
                font-size: 1.6rem;
            }

            .logo-circle {
                width: 76px;
                height: 76px;
            }

            .feature-badges .col-4 {
                flex: 0 0 33.3333%;
                max-width: 33.3333%;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 12px 0;
            }

            .auth-shell {
                padding-inline: 10px;
            }

            .form-pane {
                padding: 18px 14px 22px;
            }

            h2 {
                font-size: 1.5rem;
            }

            .feature-badges i {
                font-size: 1.4rem;
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
                        Plataforma para paseos caninos
                    </span>

                    <h2 class="illustration-title">
                        Organiza tus paseos en un solo lugar
                    </h2>

                    <p class="illustration-text">
                        Jaguata conecta dueños de mascotas con paseadores verificados en Asunción y Gran Asunción.
                        Reservá turnos, gestioná perfiles y mantené todo el historial de paseos desde una única plataforma.
                    </p>

                    <ul class="illustration-list">
                        <li>
                            <i class="fa-solid fa-shield-dog"></i>
                            <span>Perfiles de paseadores con datos verificados y reseñas.</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-calendar-check"></i>
                            <span>Reservas por horario y zona, con confirmación en línea.</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-map-location-dot"></i>
                            <span>Registro de ubicaciones para facilitar el encuentro y el servicio.</span>
                        </li>
                    </ul>

                    <div class="illustration-metrics">
                        <div class="illustration-metrics-item">
                            <strong>24/7</strong>
                            <small>Reservas desde la web</small>
                        </div>
                        <div class="illustration-metrics-item">
                            <strong>+incluyente</strong>
                            <small>Oportunidades laborales formales</small>
                        </div>
                        <div class="illustration-metrics-item">
                            <strong>0%</strong>
                            <small>Comisiones a paseadores</small>
                        </div>
                    </div>
                </div>

                <div class="illustration-graphic" aria-hidden="true">
                    <svg class="dog-svg" viewBox="0 0 640 480" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="leash" x1="0" x2="1">
                                <stop offset="0" stop-color="#20c997" />
                                <stop offset="1" stop-color="#3c6255" />
                            </linearGradient>
                        </defs>
                        <rect x="80" y="40" width="120" height="200" rx="20" fill="#eaf0f2" />
                        <rect x="110" y="220" width="80" height="18" rx="9" fill="#cdd8dd" />
                        <path d="M220,260 C260,250 320,250 360,260 380,265 420,260 440,270 460,280 470,305 450,312 430,319 410,300 395,305 380,310 365,332 340,332 315,332 300,312 285,305 270,298 245,310 228,300 212,291 208,270 220,260Z" fill="#1e2426" />
                        <circle cx="445" cy="275" r="12" fill="#1e2426" />
                        <circle cx="448" cy="273" r="4" fill="#fff" />
                        <path d="M360,260 Q370,240 390,235" stroke="url(#leash)" stroke-width="6" fill="none" />
                        <circle cx="360" cy="260" r="10" fill="#20c997" />
                        <ellipse cx="320" cy="360" rx="220" ry="26" fill="rgba(0,0,0,.18)" />
                    </svg>
                </div>
            </div>

            <!-- Columna formulario -->
            <div class="col-lg-6">
                <div class="form-pane">
                    <div class="text-center mb-3">
                        <div class="logo-circle">
                            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="64" height="64">
                        </div>
                        <h2>Bienvenido a Jaguata <span aria-hidden="true">🐾</span></h2>
                        <p class="text-muted mb-0">Inicia sesión para continuar</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off" novalidate>
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars(Validaciones::generarCSRF(), ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1" aria-hidden="true"></i>Email
                            </label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="tu@email.com"
                                autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1" aria-hidden="true"></i>Contraseña
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
                                    class="btn btn-outline-Black"
                                    type="button"
                                    id="togglePassword"
                                    aria-label="Mostrar/ocultar contraseña">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="remember_me"
                                name="remember_me"
                                <?= !empty($_COOKIE['remember_email']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="remember_me">Recordar mi sesión</label>
                        </div>

                        <div class="d-grid mb-2">
                            <button type="submit" class="btn btn-jg btn-lg">
                                <i class="fas fa-paw me-2" aria-hidden="true"></i> Iniciar Sesión
                            </button>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/recuperar_password.php">¿Olvidaste tu contraseña?</a>
                            <a href="<?= BASE_URL ?>/registro.php" class="fw-semibold">Crear cuenta</a>
                        </div>
                    </form>

                    <div class="my-4 paw-divider">
                        <i class="fa-solid fa-paw"></i>
                        <span class="small">Seguro • Rápido • Confiable</span>
                        <i class="fa-solid fa-bone"></i>
                    </div>

                    <div class="text-center feature-badges">
                        <div class="row g-3 justify-content-center">
                            <div class="col-4">
                                <i class="fas fa-shield-dog fa-2x mb-1"></i>
                                <small class="d-block">Protección</small>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-clock fa-2x mb-1"></i>
                                <small class="d-block">24/7</small>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-star fa-2x mb-1"></i>
                                <small class="d-block">Verificados</small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle de contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
            input.focus();
        });
    </script>
</body>

</html>