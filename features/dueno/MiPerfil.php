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
<?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
<?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column gap-1">
                    <!-- Mi Perfil -->
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center w-100 text-start"
                            data-bs-toggle="collapse" data-bs-target="#menuPerfil" aria-expanded="false">
                            <i class="fas fa-user me-2"></i>
                            <span class="flex-grow-1">Mi Perfil</span>
                            <i class="fas fa-chevron-right ms-2 chevron"></i>
                        </button>
                        <ul class="collapse ps-4 nav flex-column" id="menuPerfil">
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php">
                                    <i class="fas fa-id-card me-2"></i> Ver Perfil
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">
                                    <i class="fas fa-user-edit me-2 text-warning"></i> Editar Perfil
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php">
                                    <i class="fas fa-coins me-2 text-success"></i> Gastos Totales
                                </a>
                            </li>
                        </ul>
                    </li>




                    <!-- Mascotas -->
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center w-100 text-start"
                            data-bs-toggle="collapse" data-bs-target="#menuMascotas" aria-expanded="false">
                            <i class="fas fa-paw me-2"></i>
                            <span class="flex-grow-1">Mascotas</span>
                            <i class="fas fa-chevron-right ms-2 chevron"></i>
                        </button>
                        <ul class="collapse ps-4 nav flex-column" id="menuMascotas">
                            <li class="nav-item">
                                <a class="nav-link" href="MisMascotas.php">
                                    <i class="fas fa-list-ul me-2"></i> Mis Mascotas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="AgregarMascota.php">
                                    <i class="fas fa-plus-circle me-2"></i> Agregar Mascota
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $firstMascotaId ? '' : 'disabled' ?>"
                                    href="<?= $firstMascotaId ? 'PerfilMascota.php?id=' . (int)$firstMascotaId : '#' ?>">
                                    <i class="fas fa-id-badge me-2"></i> Perfil de mi Mascota
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Paseos -->
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center w-100 text-start"
                            data-bs-toggle="collapse" data-bs-target="#menuPaseos" aria-expanded="false">
                            <i class="fas fa-walking me-2"></i>
                            <span class="flex-grow-1">Paseos</span>
                            <i class="fas fa-chevron-right ms-2 chevron"></i>
                        </button>
                        <ul class="collapse ps-4 nav flex-column" id="menuPaseos">
                            <li class="nav-item">
                                <a class="nav-link" href="BuscarPaseadores.php">
                                    <i class="fas fa-search me-2"></i> Buscar Paseadores
                                </a>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link d-flex align-items-center w-100 text-start"
                                    data-bs-toggle="collapse" data-bs-target="#menuMisPaseos" aria-expanded="false">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    <span class="flex-grow-1">Mis Paseos</span>
                                    <i class="fas fa-chevron-right ms-2 chevron"></i>
                                </button>
                                <ul class="collapse ps-4 nav flex-column" id="menuMisPaseos">
                                    <li class="nav-item"><a class="nav-link" href="PaseosCompletados.php"><i class="fas fa-check-circle me-2"></i> Completados</a></li>
                                    <li class="nav-item"><a class="nav-link" href="PaseosPendientes.php"><i class="fas fa-hourglass-half me-2"></i> Pendientes</a></li>
                                    <li class="nav-item"><a class="nav-link" href="PaseosCancelados.php"><i class="fas fa-times-circle me-2"></i> Cancelados</a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="SolicitarPaseo.php">
                                    <i class="fas fa-plus-circle me-2"></i> Solicitar Nuevo Paseo
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Pagos -->
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center w-100 text-start"
                            data-bs-toggle="collapse" data-bs-target="#menuPagos" aria-expanded="false">
                            <i class="fas fa-credit-card me-2"></i>
                            <span class="flex-grow-1">Pagos</span>
                            <i class="fas fa-chevron-right ms-2 chevron"></i>
                        </button>
                        <ul class="collapse ps-4 nav flex-column" id="menuPagos">
                            <li class="nav-item">
                                <!-- Enviar a Pendientes (allí hay botón Pagar con paseo_id) -->
                                <a class="nav-link" href="PaseosPendientes.php">
                                    <i class="fas fa-wallet me-2"></i> Pagar paseo
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Notificaciones -->
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="Notificaciones.php">
                            <i class="fas fa-bell me-2"></i>
                            <span>Notificaciones</span>
                        </a>
                    </li>

                    <!-- Configuración (solo Editar Perfil y Cerrar Sesión) -->
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center w-100 text-start"
                            data-bs-toggle="collapse" data-bs-target="#menuConfig" aria-expanded="false">
                            <i class="fas fa-gear me-2"></i>
                            <span class="flex-grow-1">Configuración</span>
                            <i class="fas fa-chevron-right ms-2 chevron"></i>
                        </button>
                        <ul class="collapse ps-4 nav flex-column" id="menuConfig">
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">
                                    <i class="fas fa-user-cog me-2"></i> Editar Perfil
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>

                </ul>
            </div>
        </div>

        <!-- Contenido principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex align-items-center justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2 class="mb-0"><i class="fas fa-user me-2"></i> Mi Perfil - Dueño</h2>
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
                <!-- Columna izquierda -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <img src="<?= htmlspecialchars($foto) ?>" alt="Foto de perfil"
                                class="rounded-circle mb-3" style="width:160px;height:160px;object-fit:cover;">
                            <h4 class="mb-1"><?= h($usuario['nombre'] ?? null, 'Sin nombre') ?></h4>
                            <span class="badge bg-primary-subtle text-primary-emphasis">Dueño</span>

                            <div class="mt-3 text-start small">
                                <div class="mb-2"><i class="fa-solid fa-envelope me-2"></i><strong>Email:</strong> <?= h($usuario['email'] ?? null, 'No registrado') ?></div>
                                <div class="mb-2"><i class="fa-solid fa-phone me-2"></i><strong>Teléfono:</strong> <?= h($usuario['telefono'] ?? null, 'No registrado') ?></div>
                                <div class="mb-2"><i class="fa-solid fa-cake-candles me-2"></i><strong>Cumpleaños:</strong>
                                    <?php if (!empty($usuario['fecha_nacimiento'])): ?>
                                        <?= fechaLatina($usuario['fecha_nacimiento']) ?>
                                        <?= $edad !== null ? " <span class=\"text-muted\">({$edad} años)</span>" : "" ?>
                                    <?php else: ?><span class="text-muted">No especificado</span><?php endif; ?>
                                </div>
                                <div class="mb-2"><i class="fa-solid fa-star me-2"></i><strong>Puntos:</strong> <?= (int)($usuario['puntos'] ?? 0) ?></div>
                                <div class="text-color #ffff mt-3">
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
                        <div class="card-header bg-primary text-white">
                            <strong><i class="fa-solid fa-location-dot me-2"></i> Dirección</strong>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="text-color #ffff small">Departamento</div>
                                    <div class="fw-semibold"><?= h($departamento) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-color #ffff small">Ciudad</div>
                                    <div class="fw-semibold"><?= h($ciudad) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-color #ffff small">Barrio</div>
                                    <div class="fw-semibold"><?= h($barrio) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-color #ffff small">Calle</div>
                                    <div class="fw-semibold"><?= h($calle) ?></div>
                                </div>
                                <div class="col-12">
                                    <div class="text-color #ffff small">Referencia / Complemento</div>
                                    <div class="fw-semibold"><?= h($direccionRef, '—') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preferencias o info adicional -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-primary text-white">
                            <strong><i class="fa-solid fa-dog me-2"></i> Preferencias del Dueño</strong>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($usuario['preferencias'])): ?>
                                <div class="text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($usuario['preferencias'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php else: ?>
                                <span class="text-muted">No especificadas.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notas -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <strong><i class="fa-solid fa-notes-medical me-2"></i> Notas o Comentarios</strong>
                        </div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>