<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

AppConfig::init();

// Solo paseador
$authController = new AuthController();
$authController->checkRole('paseador');

// Datos del usuario
$usuarioModel = new Usuario();
$usuarioId = Session::get('usuario_id');
$usuario = $usuarioModel->getById((int)$usuarioId);

if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

/* =========================
   Helpers
   ========================= */
function h(?string $v, string $fallback = '—'): string
{
    $v = trim((string)($v ?? ''));
    return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $fallback;
}
function fechaLatina(?string $ymd): string
{
    if (!$ymd) return '—';
    $ts = strtotime($ymd);
    return $ts ? date('d/m/Y', $ts) : htmlspecialchars($ymd);
}
function calcularEdad(?string $ymd): ?int
{
    if (!$ymd) return null;
    try {
        $nac = new DateTime($ymd);
        $hoy = new DateTime('today');
        return $nac->diff($hoy)->y;
    } catch (\Throwable $e) {
        return null;
    }
}
function esUrlAbsoluta(string $p): bool
{
    return (bool)preg_match('#^https?://#i', $p);
}

/* =========================
   Datos derivados
   ========================= */
$foto = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
if ($foto && !esUrlAbsoluta($foto)) {
    $foto = rtrim(BASE_URL, '/') . $foto;
}
if (!$foto) {
    $foto = ASSETS_URL . '/images/user-placeholder.png';
}

$edad = calcularEdad($usuario['fecha_nacimiento'] ?? null);

// Zonas (JSON o CSV)
$zonas = [];
if (!empty($usuario['zona'])) {
    $decoded = json_decode($usuario['zona'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $zonas = array_values(array_filter(array_map('trim', $decoded)));
    } else {
        $zonas = array_values(array_filter(array_map('trim', explode(',', $usuario['zona']))));
    }
}

$rolMenu = Session::getUsuarioRol() ?: 'paseador';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Paseador | Jaguata</title>
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            min-height: 100vh;
            padding-top: 1rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar .nav-link {
            color: #ddd;
            border-radius: 8px;
            padding: 10px 16px;
            margin: 4px 8px;
            display: flex;
            align-items: center;
            transition: background 0.2s, transform 0.2s;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background-color: #3c6255;
            color: #fff;
        }

        main {
            background-color: #f5f7fa;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 600;
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(90deg, #3c6255, #20c997);
            color: #fff;
            font-weight: 600;
            padding: 0.75rem 1rem;
        }

        .card-body {
            background-color: #fff;
            color: #333;
            padding: 1.25rem;
        }

        img.rounded-circle {
            border: 4px solid #3c6255;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease-in-out;
        }

        img.rounded-circle:hover {
            transform: scale(1.05);
        }

        .badge.bg-primary-subtle {
            background-color: #e6f4ea;
            color: #3c6255;
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 0.4em 0.6em;
        }

        .btn-primary {
            background-color: #3c6255;
            border: none;
        }

        .btn-primary:hover {
            background-color: #2e4d44;
        }

        .btn-outline-secondary {
            border-color: #3c6255;
            color: #3c6255;
        }

        .btn-outline-secondary:hover {
            background-color: #3c6255;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column gap-1">
                        <li><a class="nav-link active" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisPaseos.php"><i class="fas fa-list"></i> Mis Paseos</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/Disponibilidad.php"><i class="fas fa-calendar-check"></i> Disponibilidad</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/Perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                        <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contenido principal -->


            <div class="page-header">
                <h2><i class="fas fa-user me-2"></i> Mi Perfil - Paseador</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="EditarPerfil.php" class="btn btn-light btn-sm text-success">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <!-- Columna izquierda -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <img src="<?= htmlspecialchars($foto) ?>" alt="Foto de perfil"
                                class="rounded-circle mb-3" style="width:160px;height:160px;object-fit:cover;">
                            <h4 class="mb-1"><?= h($usuario['nombre'] ?? null, 'Sin nombre') ?></h4>
                            <span class="badge bg-primary-subtle">Paseador</span>

                            <div class="mt-3 text-start small">
                                <div class="mb-2"><i class="fa-solid fa-envelope me-2"></i><strong>Email:</strong> <?= h($usuario['email']) ?></div>
                                <div class="mb-2"><i class="fa-solid fa-phone me-2"></i><strong>Teléfono:</strong> <?= h($usuario['telefono']) ?></div>
                                <div class="mb-2"><i class="fa-solid fa-cake-candles me-2"></i><strong>Cumpleaños:</strong>
                                    <?php if (!empty($usuario['fecha_nacimiento'])): ?>
                                        <?= fechaLatina($usuario['fecha_nacimiento']) ?>
                                        <?= $edad !== null ? " <span class=\"text-muted\">({$edad} años)</span>" : "" ?>
                                    <?php else: ?><span class="text-muted">No especificado</span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header"><i class="fa-solid fa-map-location-dot me-2"></i> Zonas de trabajo</div>
                        <div class="card-body">
                            <?php if (empty($zonas)): ?>
                                <span class="text-muted">Sin zonas registradas.</span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($zonas as $z): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis"><?= htmlspecialchars($z) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header"><i class="fa-solid fa-briefcase me-2"></i> Experiencia</div>
                        <div class="card-body">
                            <?php if (!empty($usuario['experiencia'])): ?>
                                <div class="text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($usuario['experiencia']) ?></div>
                            <?php else: ?>
                                <span class="text-muted">No especificada.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>