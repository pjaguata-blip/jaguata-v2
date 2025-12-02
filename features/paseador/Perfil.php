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
$usuarioId    = Session::get('usuario_id');
$usuario      = $usuarioModel->getById((int)$usuarioId);

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

$rolMenu      = Session::getUsuarioRol() ?: 'paseador';
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        html,
        body {
            margin: 0;
            height: 100%;
            font-family: "Poppins", sans-serif;
            background-color: var(--gris-fondo);
            color: var(--gris-texto);
        }

        .layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* === SIDEBAR unificada (igual que las demás) === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #fff;
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: #ccc;
            border-radius: 8px;
            padding: 12px 18px;
            margin: 6px 10px;
            display: flex;
            align-items: center;
            gap: .8rem;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--verde-claro);
            color: #fff;
            transform: translateX(4px);
        }

        /* === BOTÓN MENÚ MOBILE === */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background-color: #1e1e2f;
            color: #fff;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        /* === CONTENIDO === */
        main.content {
            flex-grow: 1;
            padding: 2.5rem;
            background-color: var(--gris-fondo);
            margin-left: 250px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            main.content {
                margin-left: 0;
                padding: 1.5rem;
            }

            .menu-toggle {
                display: block;
            }
        }

        /* === HEADER === */
        .page-header {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 600;
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
        }

        img.rounded-circle {
            border: 4px solid #3c6255;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }

        .badge.bg-primary-subtle {
            background-color: #e6f4ea;
            color: #3c6255;
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 0.4em 0.6em;
        }

        footer {
            background-color: #3c6255;
            color: #fff;
            text-align: center;
            padding: 1.2rem 0;
            width: 100%;
            margin-top: 3rem;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- Contenido principal -->
        <main class="content">
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
                                class="rounded-circle mb-3"
                                style="width:160px;height:160px;object-fit:cover;">
                            <h4 class="mb-1"><?= h($usuario['nombre'] ?? null, 'Sin nombre') ?></h4>
                            <span class="badge bg-primary-subtle">Paseador</span>

                            <div class="mt-3 text-start small">
                                <div class="mb-2">
                                    <i class="fa-solid fa-envelope me-2"></i>
                                    <strong>Email:</strong> <?= h($usuario['email']) ?>
                                </div>
                                <div class="mb-2">
                                    <i class="fa-solid fa-phone me-2"></i>
                                    <strong>Teléfono:</strong> <?= h($usuario['telefono']) ?>
                                </div>
                                <div class="mb-2">
                                    <i class="fa-solid fa-cake-candles me-2"></i>
                                    <strong>Cumpleaños:</strong>
                                    <?php if (!empty($usuario['fecha_nacimiento'])): ?>
                                        <?= fechaLatina($usuario['fecha_nacimiento']) ?>
                                        <?= $edad !== null ? " <span class=\"text-muted\">({$edad} años)</span>" : "" ?>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">
                            <i class="fa-solid fa-map-location-dot me-2"></i> Zonas de trabajo
                        </div>
                        <div class="card-body">
                            <?php if (empty($zonas)): ?>
                                <span class="text-muted">Sin zonas registradas.</span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($zonas as $z): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis">
                                            <?= htmlspecialchars($z) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <i class="fa-solid fa-briefcase me-2"></i> Experiencia
                        </div>
                        <div class="card-body">
                            <?php if (!empty($usuario['experiencia'])): ?>
                                <div class="text-muted" style="white-space: pre-wrap;">
                                    <?= htmlspecialchars($usuario['experiencia']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No especificada.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <footer>© <?= date('Y') ?> Jaguata — Todos los derechos reservados.</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar'); // id en SidebarPaseador.php

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    </script>
</body>

</html>