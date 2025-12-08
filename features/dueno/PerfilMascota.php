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
$rolMenu = Session::getUsuarioRol() ?: 'dueno';
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
        .perfil-mascota-foto {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 14px;
            border: 3px solid rgba(32, 201, 151, 0.25);
        }
    </style>
</head>

<body>

    <!-- Sidebar unificada del dueño -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Contenido -->
    <main class="bg-light">
        <div class="container-fluid py-4">

            <!-- Header -->
            <div class="header-box header-mascotas mb-4">
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

            <!-- Mensajes flash (si los usás desde el controller) -->
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
                    <!-- Métricas -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="section-card text-center py-3">
                                <div class="text-muted small">Paseos</div>
                                <div class="fs-3 fw-bold"><?= $totalPaseos; ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="section-card text-center py-3">
                                <div class="text-muted small">Completados</div>
                                <div class="fs-3 fw-bold"><?= $totalCompleto; ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="section-card text-center py-3">
                                <div class="text-muted small">Pendientes</div>
                                <div class="fs-3 fw-bold"><?= $totalPendiente; ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="section-card text-center py-3">
                                <div class="text-muted small">Gasto total</div>
                                <div class="fs-5 fw-bold text-success">
                                    ₲<?= number_format($gastoTotal, 0, ',', '.'); ?>
                                </div>
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
</body>

</html>