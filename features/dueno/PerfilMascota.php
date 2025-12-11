<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Auth dueño */
$auth = new AuthController();
$auth->checkRole('dueno');

// ====== Helpers ======
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function edadHumana($meses): string
{
    if ($meses === null || $meses <= 0) return '—';
    $m = (int)$meses;
    if ($m < 12) return "$m meses";
    $a = intdiv($m, 12);
    $r = $m % 12;
    return $r ? "{$a} años, {$r} meses" : "{$a} años";
}

function tamanoEtiqueta(?string $t): string
{
    return match ($t) {
        'pequeno' => 'Pequeño',
        'mediano' => 'Mediano',
        'grande'  => 'Grande',
        'gigante' => 'Gigante',
        default   => '—',
    };
}

function badgeEstadoHtml(string $estado): string
{
    $e = strtolower(trim($estado));
    return match ($e) {
        'completo'   => '<span class="badge bg-success">Completo</span>',
        'cancelado'  => '<span class="badge bg-danger">Cancelado</span>',
        'confirmado' => '<span class="badge bg-primary">Confirmado</span>',
        'pendiente'  => '<span class="badge bg-warning text-dark">Pendiente</span>',
        default      => '<span class="badge bg-secondary">' . h($estado) . '</span>',
    };
}

// ====== Controladores / datos base ======
$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: {$baseFeatures}/MisMascotas.php");
    exit;
}

$mascotaCtrl = new MascotaController();
$paseoCtrl   = new PaseoController();

$mascota = $mascotaCtrl->show($id);
if (isset($mascota['error'])) {
    $_SESSION['error'] = $mascota['error'];
    header("Location: MisMascotas.php");
    exit;
}

/* Datos mascota */
$nombre        = h($mascota['nombre'] ?? 'Mascota');
$raza          = $mascota['raza'] ?? null;
$peso          = $mascota['peso_kg'] ?? null;
$tamano        = $mascota['tamano'] ?? null;
$edadMeses     = $mascota['edad_meses'] ?? ($mascota['edad'] ?? null);
$observaciones = h($mascota['observaciones'] ?? '');
$foto          = $mascota['foto_url'] ?? '';
$creado        = $mascota['created_at'] ?? null;
$actualizado   = $mascota['updated_at'] ?? null;

/* Paseos de esta mascota */
$paseos        = $paseoCtrl->index();
$paseosMascota = array_filter($paseos, fn($p) => (int)($p['mascota_id'] ?? 0) === $id);

usort(
    $paseosMascota,
    fn($a, $b) => strtotime((string)($b['inicio'] ?? '')) <=> strtotime((string)($a['inicio'] ?? ''))
);

$recientes      = array_slice($paseosMascota, 0, 5);
$completados    = array_filter($paseosMascota, fn($p) => strtolower($p['estado'] ?? '') === 'completo');
$pendientes     = array_filter($paseosMascota, fn($p) => in_array(strtolower($p['estado'] ?? ''), ['pendiente', 'confirmado'], true));
$totalPaseos    = count($paseosMascota);
$totalCompleto  = count($completados);
$totalPendiente = count($pendientes);
$gastoTotal     = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $completados));

$usuarioNombre = htmlspecialchars(Session::getUsuarioNombre() ?? 'Dueño/a', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Perfil de Mascota - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS global Jaguata -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            background: var(--gris-fondo, #f4f6f9);
        }

        main.main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }

        @media (max-width: 768px) {
            main.main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        .perfil-mascota-foto {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 14px;
            border: 3px solid rgba(32, 201, 151, 0.25);
        }

        /* Tarjetas tipo dashboard para métricas */
        .dash-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 16px 18px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 4px;
            height: 100%;
        }

        .dash-card-icon {
            font-size: 1.6rem;
            margin-bottom: 4px;
        }

        .dash-card-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #222;
        }

        .dash-card-label {
            font-size: 0.85rem;
            color: #555;
        }

        .icon-green {
            color: var(--verde-jaguata, #3c6255);
        }

        .icon-blue {
            color: #0d6efd;
        }

        .icon-yellow {
            color: #ffc107;
        }

        .icon-red {
            color: #dc3545;
        }
    </style>
</head>

<body>

    <!-- Sidebar unificada del dueño -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Botón hamburguesa para mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Contenido -->
    <main class="main-content">
        <div class="container-fluid py-2">

            <!-- Header -->
            <div class="header-box header-mascotas mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-id-card me-2"></i>Perfil de <?= $nombre; ?>
                    </h1>
                    <p class="mb-0">
                        Información general y paseos recientes de tu mascota, <?= $usuarioNombre; ?> 🐾
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $baseFeatures; ?>/MisMascotas.php" class="btn btn-outline-light fw-semibold">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="<?= $baseFeatures; ?>/EditarMascota.php?id=<?= $id; ?>" class="btn btn-light fw-semibold text-success">
                        <i class="fas fa-pen me-1"></i> Editar
                    </a>
                </div>
            </div>

            <!-- Mensajes flash -->
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm">
                    <i class="fas fa-check-circle me-2"></i><?= h($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= h($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Columna izquierda: ficha de la mascota -->
                <div class="col-lg-4">
                    <div class="section-card h-100">
                        <div class="text-center mb-3">
                            <img
                                src="<?= $foto ? h($foto) : 'https://via.placeholder.com/150x150.png?text=Mascota'; ?>"
                                alt="Foto mascota"
                                class="perfil-mascota-foto mb-2">
                            <h4 class="mb-0"><?= $nombre; ?></h4>
                            <small class="text-muted">ID #<?= $id; ?></small>
                        </div>

                        <div class="row g-2 small">
                            <div class="col-12">
                                <strong>Raza:</strong>
                                <span><?= $raza ? h($raza) : '—'; ?></span>
                            </div>
                            <div class="col-12">
                                <strong>Peso:</strong>
                                <span><?= $peso ? number_format((float)$peso, 1, ',', '.') . ' kg' : '—'; ?></span>
                            </div>
                            <div class="col-12">
                                <strong>Tamaño:</strong>
                                <span><?= tamanoEtiqueta($tamano); ?></span>
                            </div>
                            <div class="col-12">
                                <strong>Edad:</strong>
                                <span><?= edadHumana((int)$edadMeses); ?></span>
                            </div>
                            <div class="col-12">
                                <strong>Creado:</strong>
                                <span><?= $creado ? date('d/m/Y H:i', strtotime((string)$creado)) : '—'; ?></span>
                            </div>
                            <div class="col-12">
                                <strong>Última actualización:</strong>
                                <span><?= $actualizado ? date('d/m/Y H:i', strtotime((string)$actualizado)) : '—'; ?></span>
                            </div>

                            <?php if ($observaciones): ?>
                                <div class="col-12 mt-3">
                                    <strong>Observaciones:</strong>
                                    <p class="mb-0"><?= nl2br($observaciones); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: estadísticas y paseos -->
                <div class="col-lg-8">
                    <!-- Métricas tipo dashboard -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="dash-card">
                                <i class="fas fa-walking dash-card-icon icon-green"></i>
                                <div class="dash-card-value"><?= $totalPaseos; ?></div>
                                <div class="dash-card-label">Paseos totales</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="dash-card">
                                <i class="fas fa-check-circle dash-card-icon icon-blue"></i>
                                <div class="dash-card-value"><?= $totalCompleto; ?></div>
                                <div class="dash-card-label">Completados</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="dash-card">
                                <i class="fas fa-hourglass-half dash-card-icon icon-yellow"></i>
                                <div class="dash-card-value"><?= $totalPendiente; ?></div>
                                <div class="dash-card-label">Pendientes / confirmados</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="dash-card">
                                <i class="fas fa-wallet dash-card-icon icon-red"></i>
                                <div class="dash-card-value">
                                    ₲<?= number_format($gastoTotal, 0, ',', '.'); ?>
                                </div>
                                <div class="dash-card-label">Gasto total</div>
                            </div>
                        </div>
                    </div>

                    <!-- Paseos recientes -->
                    <div class="section-card">
                        <div class="section-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-walking me-2"></i>Paseos recientes</span>
                            <div class="d-flex gap-2">
                                <a href="<?= $baseFeatures; ?>/MisPaseos.php?mascota_id=<?= $id; ?>" class="btn btn-sm btn-outline-light">
                                    Ver todos
                                </a>
                                <a href="<?= $baseFeatures; ?>/SolicitarPaseo.php?mascota_id=<?= $id; ?>" class="btn btn-sm btn-enviar">
                                    <i class="fas fa-plus me-1"></i> Solicitar nuevo
                                </a>
                            </div>
                        </div>

                        <div class="section-body">
                            <?php if (empty($recientes)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-calendar-xmark fa-2x mb-2"></i>
                                    <p class="mb-2">No hay paseos registrados para esta mascota.</p>
                                    <a href="<?= $baseFeatures; ?>/SolicitarPaseo.php?mascota_id=<?= $id; ?>" class="btn btn-enviar btn-sm">
                                        <i class="fas fa-plus me-1"></i>Solicitar paseo
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Paseador</th>
                                                <th>Estado</th>
                                                <th>Precio</th>
                                                <th>Duración</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recientes as $p): ?>
                                                <tr>
                                                    <td><?= !empty($p['inicio']) ? date('d/m/Y H:i', strtotime((string)$p['inicio'])) : '—'; ?></td>
                                                    <td><?= h($p['nombre_paseador'] ?? '—'); ?></td>
                                                    <td><?= badgeEstadoHtml($p['estado'] ?? ''); ?></td>
                                                    <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.'); ?></td>
                                                    <td><?= (int)($p['duracion_min'] ?? 0); ?> min</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel del Dueño
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile (usa .sidebar-open del CSS)
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>