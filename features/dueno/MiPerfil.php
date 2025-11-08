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

/* 🔒 Solo rol dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

/* ===== Datos del usuario ===== */
$usuarioId = (int)(Session::get('usuario_id') ?? 0);
$usuarioModel = new Usuario();
$usuario = $usuarioModel->getById($usuarioId);

if (!$usuario) {
    http_response_code(404);
    exit('Error: No se encontró el usuario.');
}

/* ===== Helpers ===== */
function h(?string $v, string $fallback = '—'): string
{
    $v = trim((string)($v ?? ''));
    return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $fallback;
}
function fechaLatina(?string $ymd): string
{
    if (!$ymd) return '—';
    $ts = strtotime($ymd);
    return $ts ? date('d/m/Y', $ts) : htmlspecialchars($ymd, ENT_QUOTES, 'UTF-8');
}
function calcularEdad(?string $ymd): ?int
{
    if (!$ymd) return null;
    try {
        $nac = new DateTime($ymd);
        $hoy = new DateTime('today');
        return $nac->diff($hoy)->y;
    } catch (\Throwable) {
        return null;
    }
}
function esUrlAbsoluta(string $p): bool
{
    return (bool)preg_match('#^https?://#i', $p);
}

/* ===== Derivados UI ===== */
$foto = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
if ($foto && !esUrlAbsoluta($foto)) {
    $foto = rtrim(BASE_URL, '/') . $foto;
}
if (!$foto) {
    $foto = ASSETS_URL . '/images/user-placeholder.png';
}

$edad          = calcularEdad($usuario['fecha_nacimiento'] ?? null);
$departamento  = $usuario['departamento'] ?? null;
$ciudad        = $usuario['ciudad'] ?? null;
$barrio        = $usuario['barrio'] ?? null;
$calle         = $usuario['calle'] ?? null;
$direccionRef  = $usuario['direccion'] ?? null;

$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mi Perfil - Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet" />

    <style>
        :root {
            --verde: #3c6255;
            --verde-claro: #20c997;
            --fondo: #f5f7fa;
        }

        body {
            background: var(--fondo);
            font-family: "Poppins", sans-serif
        }

        /* Sidebar fija */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            min-height: 100vh;
            padding-top: 1rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, .15)
        }

        .sidebar .nav-link {
            color: #ddd;
            border-radius: 8px;
            padding: 10px 16px;
            margin: 4px 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: .2s
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px)
        }

        .page-header {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde));
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0 1.5rem
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08)
        }

        .card-header {
            background: linear-gradient(90deg, var(--verde), var(--verde-claro));
            color: #fff;
            font-weight: 600;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <aside class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar" id="sidebar">
                <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>
            </aside>

            <!-- Main -->
            <main class="col py-3">
                <div class="page-header">
                    <h2 class="m-0"><i class="fas fa-user me-2"></i> Mi Perfil — Dueño</h2>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <a href="<?= $baseFeatures; ?>/MisPuntos.php" class="btn btn-light btn-sm text-success fw-semibold">
                            <i class="fas fa-star me-1 text-warning"></i> Mis Puntos
                        </a>
                        <a href="<?= $baseFeatures; ?>/EditarPerfil.php" class="btn btn-success btn-sm">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Izquierda -->
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <img src="<?= h($foto) ?>" alt="Foto de perfil" class="rounded-circle mb-3" style="width:160px;height:160px;object-fit:cover;">
                                <h4 class="mb-1"><?= h($usuario['nombre'] ?? null, 'Sin nombre') ?></h4>
                                <span class="badge text-bg-success">Dueño</span>

                                <div class="mt-3 text-start small">
                                    <div class="mb-2"><i class="fa-solid fa-envelope me-2"></i><strong>Email:</strong> <?= h($usuario['email']) ?></div>
                                    <div class="mb-2"><i class="fa-solid fa-phone me-2"></i><strong>Teléfono:</strong> <?= h($usuario['telefono']) ?></div>
                                    <div class="mb-2">
                                        <i class="fa-solid fa-cake-candles me-2"></i><strong>Cumpleaños:</strong>
                                        <?php if (!empty($usuario['fecha_nacimiento'])): ?>
                                            <?= fechaLatina($usuario['fecha_nacimiento']) ?>
                                            <?= ($edad !== null) ? ' <span class="text-muted">(' . $edad . ' años)</span>' : '' ?>
                                        <?php else: ?>
                                            <span class="text-muted">No especificado</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-2"><i class="fa-solid fa-star me-2"></i><strong>Puntos:</strong> <?= (int)($usuario['puntos'] ?? 0) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Derecha -->
                    <div class="col-lg-8">
                        <div class="card mb-3">
                            <div class="card-header"><i class="fa-solid fa-location-dot me-2"></i> Dirección</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6"><small class="text-muted">Departamento</small>
                                        <div class="fw-semibold"><?= h($departamento) ?></div>
                                    </div>
                                    <div class="col-md-6"><small class="text-muted">Ciudad</small>
                                        <div class="fw-semibold"><?= h($ciudad) ?></div>
                                    </div>
                                    <div class="col-md-6"><small class="text-muted">Barrio</small>
                                        <div class="fw-semibold"><?= h($barrio) ?></div>
                                    </div>
                                    <div class="col-md-6"><small class="text-muted">Calle</small>
                                        <div class="fw-semibold"><?= h($calle) ?></div>
                                    </div>
                                    <div class="col-12"><small class="text-muted">Referencia / Complemento</small>
                                        <div class="fw-semibold"><?= h($direccionRef) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header"><i class="fa-solid fa-dog me-2"></i> Preferencias</div>
                            <div class="card-body">
                                <?php if (!empty($usuario['preferencias'])): ?>
                                    <div class="text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($usuario['preferencias'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php else: ?>
                                    <span class="text-muted">No especificadas.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><i class="fa-solid fa-notes-medical me-2"></i> Notas o Comentarios</div>
                            <div class="card-body">
                                <?php if (!empty($usuario['observaciones'])): ?>
                                    <div class="text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($usuario['observaciones'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php else: ?>
                                    <span class="text-muted">Sin observaciones.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>