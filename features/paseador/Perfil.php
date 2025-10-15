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

// Solo paseador
$authController = new AuthController();
$authController->checkRole('paseador');

// Datos del usuario
$usuarioModel = new Usuario();
$usuarioId    = Session::get('usuario_id');
$usuario      = $usuarioModel->getById($usuarioId);
if (!$usuario) {
    echo "Error: No se encontró el usuario.";
    exit;
}

// Helpers de presentación
function h(?string $v, string $fallback = 'No especificado')
{
    $v = (string)($v ?? '');
    return $v !== '' ? htmlspecialchars($v) : $fallback;
}
function fechaLatina(?string $ymd): string
{
    if (!$ymd) return '';
    $ts = strtotime($ymd);
    return $ts ? date('d/m/Y', $ts) : htmlspecialchars($ymd);
}
function calcularEdad(?string $ymd): ?int
{
    if (!$ymd) return null;
    try {
        $nac = new DateTime($ymd);
        $hoy = new DateTime('today');
        $diff = $nac->diff($hoy);
        return (int)$diff->y;
    } catch (\Throwable $e) {
        return null;
    }
}

// Foto de perfil (toma foto_perfil o perfil_foto)
$foto = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
if ($foto && !str_starts_with($foto, 'http')) {
    // Si es ruta relativa, prepende BASE_URL
    $foto = rtrim(BASE_URL, '/') . $foto;
}
if (!$foto) {
    $foto = ASSETS_URL . '/images/user-placeholder.png';
}

// Zonas de trabajo (JSON o CSV)
$zonas = [];
if (!empty($usuario['zona'])) {
    $decoded = json_decode($usuario['zona'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $zonas = array_values(array_filter(array_map('trim', $decoded)));
    } else {
        $zonas = array_values(array_filter(array_map('trim', explode(',', $usuario['zona']))));
    }
}

// Edad
$edad = calcularEdad($usuario['fecha_nacimiento'] ?? null);

$titulo = "Mi Perfil (Paseador) - Jaguata";
?>

<?php include __DIR__ . '/../../src/Templates/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0"><i class="fas fa-user me-2"></i> Mi Perfil - Paseador</h2>
        <div class="d-flex gap-2">
            <a href="Dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a href="EditarPerfil.php" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Editar Perfil
            </a>
        </div>
    </div>

    <div class="row g-3">
        <!-- Columna izquierda: foto + datos principales -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <img src="<?= htmlspecialchars($foto) ?>" alt="Foto de perfil"
                        class="rounded-circle mb-3" style="width: 160px; height: 160px; object-fit: cover;">
                    <h4 class="mb-1"><?= h($usuario['nombre'], 'Sin nombre') ?></h4>
                    <span class="badge bg-success-subtle text-success-emphasis">Paseador</span>

                    <div class="mt-3 text-start small">
                        <div class="mb-2">
                            <i class="fa-solid fa-envelope me-2"></i>
                            <strong>Email:</strong> <?= h($usuario['email'], 'No registrado') ?>
                        </div>
                        <div class="mb-2">
                            <i class="fa-solid fa-phone me-2"></i>
                            <strong>Teléfono:</strong> <?= h($usuario['telefono'], 'No registrado') ?>
                        </div>
                        <div class="mb-2">
                            <i class="fa-solid fa-cake-candles me-2"></i>
                            <strong>Cumpleaños:</strong>
                            <?php if (!empty($usuario['fecha_nacimiento'])): ?>
                                <?= fechaLatina($usuario['fecha_nacimiento']) ?>
                                <?= $edad !== null ? " <span class=\"text--color #ffff\">({$edad} años)</span>" : "" ?>
                            <?php else: ?>
                                <span class="text--color #ffff">No especificado</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <i class="fa-solid fa-star me-2"></i>
                            <strong>Puntos:</strong> <?= (int)($usuario['puntos'] ?? 0) ?>
                        </div>
                        <div class="text--color #ffff mt-3">
                            <div><small><i class="fa-regular fa-clock me-1"></i> Creado: <?= h($usuario['created_at'] ?? '') ?></small></div>
                            <div><small><i class="fa-regular fa-pen-to-square me-1"></i> Actualizado: <?= h($usuario['updated_at'] ?? '') ?></small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha: dirección, experiencia, zonas -->
        <div class="col-lg-8">
            <!-- Dirección -->

            <!-- Zonas de trabajo -->
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <strong><i class="fa-solid fa-map-location-dot me-2"></i> Zonas de trabajo</strong>
                </div>
                <div class="card-body">
                    <?php if (empty($zonas)): ?>
                        <span class="text--color #ffff">Sin zonas registradas.</span>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($zonas as $z): ?>
                                <span class="badge bg-primary"><?= htmlspecialchars($z) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Experiencia -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong><i class="fa-solid fa-briefcase me-2"></i> Experiencia</strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($usuario['experiencia'])): ?>
                        <div class="text-color #ffff" style="white-space: pre-wrap;"><?= htmlspecialchars($usuario['experiencia']) ?></div>
                    <?php else: ?>
                        <span class="text--color #ffff">No especificada.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/Templates/footer.php'; ?>