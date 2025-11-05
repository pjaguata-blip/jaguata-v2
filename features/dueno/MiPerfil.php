<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

AppConfig::init();

// Solo dueño
$authController = new AuthController();
$authController->checkRole('dueno');

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

$departamento = $usuario['departamento'] ?? null;
$ciudad = $usuario['ciudad'] ?? null;
$barrio = $usuario['barrio'] ?? null;
$calle = $usuario['calle'] ?? null;
$direccionRef = $usuario['direccion'] ?? null;

$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Jaguata</title>
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>



    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 d-md-block sidebar">
        <div class="position-sticky pt-3">
            <ul class="nav flex-column gap-1">
                <li><a class="nav-link active" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Mi perfil</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis mascotas</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-hourglass-half"></i> Paseos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php"><i class="fas fa-wallet"></i> Gastos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </div>
    </div>

    <!-- Contenido principal -->

    <div class="page-header">
        <h2><i class="fas fa-user me-2"></i> Mi Perfil - Dueño</h2>
        <div class="action-box text-white p-3 rounded-3 mb-3"
            style="background: linear-gradient(90deg, #20c997, #3c6255); box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <a href="MisPuntos.php" class="btn btn-light btn-sm text-success fw-semibold">
                    <i class="fas fa-star me-1 text-warning"></i> Mis Puntos
                </a>
                <a href="EditarPerfil.php" class="btn btn-success btn-sm">
                    <i class="fas fa-edit me-1"></i> Editar
                </a>
            </div>
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
                    <span class="badge bg-primary-subtle">Dueño</span>

                    <div class="mt-3 text-start small">
                        <div class="mb-2"><i class="fa-solid fa-envelope me-2"></i><strong>Email:</strong> <?= h($usuario['email']) ?></div>
                        <div class="mb-2"><i class="fa-solid fa-phone me-2"></i><strong>Teléfono:</strong> <?= h($usuario['telefono']) ?></div>
                        <div class="mb-2"><i class="fa-solid fa-cake-candles me-2"></i><strong>Cumpleaños:</strong>
                            <?php if (!empty($usuario['fecha_nacimiento'])): ?>
                                <?= fechaLatina($usuario['fecha_nacimiento']) ?>
                                <?= $edad !== null ? " <span class=\"text-muted\">({$edad} años)</span>" : "" ?>
                            <?php else: ?><span class="text-muted">No especificado</span><?php endif; ?>
                        </div>
                        <div class="mb-2"><i class="fa-solid fa-star me-2"></i><strong>Puntos:</strong> <?= (int)($usuario['puntos'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header"><i class="fa-solid fa-location-dot me-2"></i> Dirección</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><small>Departamento</small>
                            <div class="fw-semibold"><?= h($departamento) ?></div>
                        </div>
                        <div class="col-md-6"><small>Ciudad</small>
                            <div class="fw-semibold"><?= h($ciudad) ?></div>
                        </div>
                        <div class="col-md-6"><small>Barrio</small>
                            <div class="fw-semibold"><?= h($barrio) ?></div>
                        </div>
                        <div class="col-md-6"><small>Calle</small>
                            <div class="fw-semibold"><?= h($calle) ?></div>
                        </div>
                        <div class="col-12"><small>Referencia / Complemento</small>
                            <div class="fw-semibold"><?= h($direccionRef) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header"><i class="fa-solid fa-dog me-2"></i> Preferencias</div>
                <div class="card-body">
                    <?php if (!empty($usuario['preferencias'])): ?>
                        <div class="text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($usuario['preferencias']) ?></div>
                    <?php else: ?>
                        <span class="text-muted">No especificadas.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header"><i class="fa-solid fa-notes-medical me-2"></i> Notas o Comentarios</div>
                <div class="card-body">
                    <?php if (!empty($usuario['observaciones'])): ?>
                        <div class="text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($usuario['observaciones']) ?></div>
                    <?php else: ?>
                        <span class="text-muted">Sin observaciones.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>




    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>