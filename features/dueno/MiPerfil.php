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

// Habilita errores en desarrollo (opcional)
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

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
   Helpers seguros
   ========================= */
if (!function_exists('h')) {
    function h(?string $v, string $fallback = 'No especificado')
    {
        $v = (string)($v ?? '');
        return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $fallback;
    }
}
if (!function_exists('fechaLatina')) {
    function fechaLatina(?string $ymd): string
    {
        if (!$ymd) return '';
        $ts = strtotime($ymd);
        return $ts ? date('d/m/Y', $ts) : htmlspecialchars($ymd);
    }
}
if (!function_exists('calcularEdad')) {
    function calcularEdad(?string $ymd): ?int
    {
        if (!$ymd) return null;
        try {
            $nac  = new DateTime($ymd);
            $hoy  = new DateTime('today');
            $diff = $nac->diff($hoy);
            return (int)$diff->y;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
/* Compat: PHP 7.x (no hay str_starts_with) */
if (!function_exists('esUrlAbsoluta')) {
    function esUrlAbsoluta(string $p): bool
    {
        return (bool)preg_match('#^https?://#i', $p);
    }
}

/* =========================
   Foto de perfil
   ========================= */
$foto = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
// si es ruta relativa, prepende BASE_URL
if ($foto) {
    if (!esUrlAbsoluta($foto)) {
        $foto = rtrim(BASE_URL, '/') . $foto;
    }
} else {
    $foto = ASSETS_URL . '/images/user-placeholder.png';
}

/* =========================
   Zonas (JSON o CSV)
   ========================= */
$zonas = [];
if (!empty($usuario['zona'])) {
    $decoded = json_decode($usuario['zona'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $zonas = array_values(array_filter(array_map('trim', $decoded)));
    } else {
        $zonas = array_values(array_filter(array_map('trim', explode(',', $usuario['zona']))));
    }
}

/* =========================
   Edad
   ========================= */
$edad = calcularEdad($usuario['fecha_nacimiento'] ?? null);

$titulo = "Mi Perfil (Paseador) - Jaguata";

// Normaliza campos de dirección para evitar warnings si tu SELECT no los trae
$departamento = $usuario['departamento'] ?? null;
$ciudad       = $usuario['ciudad'] ?? null;
$barrio       = $usuario['barrio'] ?? null;
$calle        = $usuario['calle'] ?? null;
$direccionRef = $usuario['direccion'] ?? null;

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
                    <h4 class="mb-1"><?= h($usuario['nombre'] ?? null, 'Sin nombre') ?></h4>
                    <span class="badge bg-success-subtle text-success-emphasis">Paseador</span>

                    <div class="mt-3 text-start small">
                        <div class="mb-2">
                            <i class="fa-solid fa-envelope me-2"></i>
                            <strong>Email:</strong> <?= h($usuario['email'] ?? null, 'No registrado') ?>
                        </div>
                        <div class="mb-2">
                            <i class="fa-solid fa-phone me-2"></i>
                            <strong>Teléfono:</strong> <?= h($usuario['telefono'] ?? null, 'No registrado') ?>
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
                        <div class="mb-2">
                            <i class="fa-solid fa-star me-2"></i>
                            <strong>Puntos:</strong> <?= (int)($usuario['puntos'] ?? 0) ?>
                        </div>
                        <div class="text-muted mt-3">
                            <div><small><i class="fa-regular fa-clock me-1"></i> Creado: <?= h($usuario['created_at'] ?? '') ?></small></div>
                            <div><small><i class="fa-regular fa-pen-to-square me-1"></i> Actualizado: <?= h($usuario['updated_at'] ?? '') ?></small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha -->
        <div class="col-lg-8">
            <!-- Dirección -->
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <strong><i class="fa-solid fa-location-dot me-2"></i> Dirección</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Departamento</div>
                            <div class="fw-semibold"><?= h($departamento) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Ciudad</div>
                            <div class="fw-semibold"><?= h($ciudad) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Barrio</div>
                            <div class="fw-semibold"><?= h($barrio) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Calle</div>
                            <div class="fw-semibold"><?= h($calle) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Referencia / Complemento</div>
                            <div class="fw-semibold"><?= h($direccionRef, '—') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zonas de trabajo -->
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <strong><i class="fa-solid fa-map-location-dot me-2"></i> Zonas de trabajo</strong>
                </div>
                <div class="card-body">
                    <?php if (empty($zonas)): ?>
                        <span class="text-muted">Sin zonas registradas.</span>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($zonas as $z): ?>
                                <span class="badge bg-primary"><?= htmlspecialchars($z, ENT_QUOTES, 'UTF-8') ?></span>
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
                        <div class="text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($usuario['experiencia'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php else: ?>
                        <span class="text-muted">No especificada.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/Templates/footer.php'; ?>