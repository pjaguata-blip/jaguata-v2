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

/* 🔒 Auth (rol dueño) */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Datos usuario (igual que resto de features) */
$usuarioId = (int)(Session::getUsuarioId() ?? 0);
$rol       = Session::getUsuarioRol() ?: 'dueno';

$usuarioModel = new Usuario();
/* Usamos find() basado en BaseModel (primaryKey usu_id) */
$usuario = $usuarioModel->find($usuarioId);

if (!$usuario) {
    http_response_code(404);
    exit('❌ Usuario no encontrado');
}

/* Puntos del dueño (campo puntos en la tabla usuarios) */
$puntos       = (int)($usuario['puntos'] ?? 0);
$baseFeatures = BASE_URL . "/features/{$rol}";

/* Helpers */
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

/* Derivados UI */
$foto = $usuario['foto_perfil'] ?? ($usuario['perfil_foto'] ?? '');
if ($foto && !esUrlAbsoluta($foto)) {
    $foto = rtrim(BASE_URL, '/') . $foto;
}
if (!$foto) {
    $foto = ASSETS_URL . '/images/user-placeholder.png';
}

$edad         = calcularEdad($usuario['fecha_nacimiento'] ?? null);
$departamento = $usuario['departamento'] ?? null;
$ciudad       = $usuario['ciudad'] ?? null;
$barrio       = $usuario['barrio'] ?? null;
$calle        = $usuario['calle'] ?? null;
$direccionRef = $usuario['direccion'] ?? null;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mi Perfil - Jaguata</title>

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

    <!-- 🎨 Estilos globales Jaguata (mismo archivo que usa Admin y Dashboard dueño) -->
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet" />
</head>

<body>

    <!-- Sidebar dueño unificado -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Botón hamburguesa para móvil (usa .sidebar-open del CSS) -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Contenido -->
    <main>
        <div class="py-4">
            <!-- HEADER unificado Perfil + Puntos (usa header-box + header-dashboard del CSS) -->
            <div class="header-box header-dashboard mb-4">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-user me-2"></i>Mi Perfil — Dueño
                    </h1>
                    <p class="mb-0">
                        Datos de tu cuenta y tus puntos acumulados en Jaguata 🐾
                    </p>
                </div>
                <div class="text-end">
                    <div class="small text-white-50">Puntos acumulados</div>
                    <div class="fs-2 fw-bold">
                        <i class="fas fa-star text-warning me-1"></i>
                        <?= number_format($puntos, 0, ',', '.'); ?>
                    </div>
                    <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>

                </div>
            </div>

            <div class="row g-3">
                <!-- Col izquierda: perfil básico -->
                <div class="col-lg-4">
                    <div class="section-card text-center">
                        <div class="mb-3">
                            <img
                                src="<?= h($foto) ?>"
                                alt="Foto de perfil"
                                class="perfil-avatar mb-2">
                            <h4 class="mb-1"><?= h($usuario['nombre'] ?? null, 'Sin nombre'); ?></h4>
                            <span class="badge-rol">Dueño</span>
                        </div>

                        <div class="perfil-datos text-start small">
                            <div class="mb-2">
                                <i class="fa-solid fa-envelope me-2"></i>
                                <strong>Email:</strong> <?= h($usuario['email']); ?>
                            </div>
                            <div class="mb-2">
                                <i class="fa-solid fa-phone me-2"></i>
                                <strong>Teléfono:</strong> <?= h($usuario['telefono']); ?>
                            </div>
                            <div class="mb-2">
                                <i class="fa-solid fa-cake-candles me-2"></i>
                                <strong>Cumpleaños:</strong>
                                <?php if (!empty($usuario['fecha_nacimiento'])): ?>
                                    <?= fechaLatina($usuario['fecha_nacimiento']); ?>
                                    <?php if ($edad !== null): ?>
                                        <span class="text-muted">(<?= $edad; ?> años)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No especificado</span>
                                <?php endif; ?>
                            </div>
                            <div class="mb-2">
                                <i class="fa-solid fa-star me-2"></i>
                                <strong>Puntos:</strong> <?= number_format($puntos, 0, ',', '.'); ?>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="<?= $baseFeatures; ?>/EditarPerfil.php" class="btn-accion btn-activar">
                                <i class="fas fa-edit me-1"></i> Editar perfil
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Col derecha: puntos + dirección + notas -->
                <div class="col-lg-8">
                    <div class="row g-3">
                        <!-- Caja grande de puntos -->
                        <div class="col-12">
                            <div class="section-card">
                                <div class="section-header">
                                    <i class="fas fa-medal me-2"></i> Tus recompensas
                                </div>
                                <div class="section-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <p class="mb-1">
                                                Cada paseo completado suma puntos en tu cuenta.
                                            </p>
                                            <p class="mb-0 text-muted">
                                                Más adelante podrás canjearlos por beneficios dentro de Jaguata.
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-center mt-3 mt-md-0">
                                            <div class="mini-stat-value">
                                                <?= number_format($puntos, 0, ',', '.'); ?>
                                            </div>
                                            <div class="text-muted small">puntos acumulados</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dirección -->
                        <div class="col-12">
                            <div class="section-card">
                                <div class="section-header">
                                    <i class="fa-solid fa-location-dot me-2"></i> Dirección
                                </div>
                                <div class="section-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <small class="text-muted">Departamento</small>
                                            <div class="fw-semibold"><?= h($departamento); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Ciudad</small>
                                            <div class="fw-semibold"><?= h($ciudad); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Barrio</small>
                                            <div class="fw-semibold"><?= h($barrio); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Calle</small>
                                            <div class="fw-semibold"><?= h($calle); ?></div>
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted">Referencia / Complemento</small>
                                            <div class="fw-semibold"><?= h($direccionRef); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preferencias -->
                        <div class="col-md-6">
                            <div class="section-card h-100">
                                <div class="section-header">
                                    <i class="fa-solid fa-dog me-2"></i> Preferencias
                                </div>
                                <div class="section-body">
                                    <?php if (!empty($usuario['preferencias'])): ?>
                                        <div class="text-muted" style="white-space: pre-wrap;">
                                            <?= htmlspecialchars($usuario['preferencias'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No especificadas.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Notas / observaciones -->
                        <div class="col-md-6">
                            <div class="section-card h-100">
                                <div class="section-header">
                                    <i class="fa-solid fa-notes-medical me-2"></i> Notas o comentarios
                                </div>
                                <div class="section-body">
                                    <?php if (!empty($usuario['observaciones'])): ?>
                                        <div class="text-muted" style="white-space: pre-wrap;">
                                            <?= htmlspecialchars($usuario['observaciones'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Sin observaciones.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Dueño
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en mobile (usa la clase .sidebar-open de tu CSS)
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>