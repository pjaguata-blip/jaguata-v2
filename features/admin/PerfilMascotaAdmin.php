<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Models/Mascota.php';
require_once dirname(__DIR__, 2) . '/src/Models/Usuario.php';
require_once dirname(__DIR__, 2) . '/src/Models/Calificacion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Models\Mascota;
use Jaguata\Models\Usuario;
use Jaguata\Models\Calificacion;

AppConfig::init();

// 🔒 Solo admin
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

// Helper escape
function h(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// ID de mascota
$mascotaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($mascotaId <= 0) {
    http_response_code(400);
    exit('<h3 style="color:red; text-align:center;">ID de mascota no válido</h3>');
}

// Modelos
$mascotaModel      = new Mascota();
$usuarioModel      = new Usuario();


// Mascota (BaseModel → find)
$mascota = $mascotaModel->find($mascotaId);

if (!$mascota) {
    http_response_code(404);
    exit('<h3 style="color:red; text-align:center;">Mascota no encontrada</h3>');
}

// Dueño (📌 CORREGIDO: usamos find() en vez de getById())
$duenoId = (int)($mascota['dueno_id'] ?? 0);
$dueno   = $duenoId > 0 ? $usuarioModel->find($duenoId) : null;

// Reputación de esta mascota
// Reputación de esta mascota (usamos el modelo Mascota)
$reputacion  = $mascotaModel->resumenPorMascota($mascotaId);
$promMascota = $reputacion['promedio'] ?? null;
$totalOpin   = $reputacion['total'] ?? 0;


// Datos derivados
$tamano = strtolower($mascota['tamano'] ?? '');
$badgeTamano = match ($tamano) {
    'pequeno' => 'bg-success',
    'mediano' => 'bg-info',
    'grande'  => 'bg-warning',
    default   => 'bg-secondary'
};
$tamanoLabel = match ($tamano) {
    'pequeno' => 'Pequeño',
    'mediano' => 'Mediano',
    'grande'  => 'Grande',
    default   => 'N/D'
};

$edadMeses = (int)($mascota['edad_meses'] ?? 0);
if ($edadMeses >= 12) {
    $anios = intdiv($edadMeses, 12);
    $resto = $edadMeses % 12;
    if ($resto > 0) {
        $edadTexto = $anios . ' año' . ($anios > 1 ? 's' : '') .
            " y $resto mes" . ($resto > 1 ? 'es' : '');
    } else {
        $edadTexto = $anios . ' año' . ($anios > 1 ? 's' : '');
    }
} elseif ($edadMeses > 0) {
    $edadTexto = $edadMeses . ' mes' . ($edadMeses > 1 ? 'es' : '');
} else {
    $edadTexto = 'N/D';
}

$fotoUrl = $mascota['foto_url'] ?? '';

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Mascota - Admin | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <!-- Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <main>
        <div class="py-4">

            <!-- HEADER -->
            <div class="header-box header-dashboard mb-4">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-paw me-2"></i>
                        Perfil de Mascota
                    </h1>
                    <p class="mb-0">
                        Visualizá los datos detallados de la mascota y su dueño 🐶
                    </p>
                </div>
                <div class="text-end">
                    <a href="<?= BASE_URL; ?>/features/admin/Mascotas.php"
                        class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver al listado
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <!-- Columna izquierda: info principal -->
                <div class="col-lg-5">
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-dog me-2"></i>
                            Mascota #<?= (int)$mascota['mascota_id']; ?> — <?= h($mascota['nombre'] ?? ''); ?>
                        </div>
                        <div class="section-body">
                            <div class="d-flex flex-column align-items-center mb-3">
                                <?php if (!empty($fotoUrl)): ?>
                                    <img src="<?= h($fotoUrl); ?>"
                                        alt="Foto de <?= h($mascota['nombre'] ?? ''); ?>"
                                        class="rounded-circle mb-2"
                                        style="width:90px;height:90px;object-fit:cover;">
                                <?php else: ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-2"
                                        style="width:90px;height:90px;background:#e9f7f1;">
                                        <i class="fas fa-dog fa-2x text-success"></i>
                                    </div>
                                <?php endif; ?>
                                <h3 class="mb-0"><?= h($mascota['nombre'] ?? ''); ?></h3>
                                <small class="text-muted">
                                    Registrada el <?= h(substr((string)($mascota['created_at'] ?? ''), 0, 10)); ?>
                                </small>
                            </div>

                            <hr>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <span class="text-muted small d-block">Raza</span>
                                    <strong><?= h($mascota['raza'] ?? 'N/D'); ?></strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted small d-block">Tamaño</span>
                                    <span class="badge <?= $badgeTamano; ?>">
                                        <?= $tamanoLabel; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <span class="text-muted small d-block">Peso</span>
                                    <strong>
                                        <?= number_format((float)($mascota['peso_kg'] ?? 0), 1, ',', '.'); ?> kg
                                    </strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted small d-block">Edad estimada</span>
                                    <strong><?= h($edadTexto); ?></strong>
                                </div>
                            </div>

                            <div class="mt-3">
                                <span class="text-muted small d-block mb-1">Observaciones</span>
                                <?php if (!empty($mascota['observaciones'])): ?>
                                    <p class="mb-0"><?= nl2br(h($mascota['observaciones'])); ?></p>
                                <?php else: ?>
                                    <p class="mb-0 text-muted">Sin observaciones registradas.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: dueño + reputación -->
                <div class="col-lg-7">
                    <!-- Dueño -->
                    <div class="section-card mb-3">
                        <div class="section-header">
                            <i class="fas fa-user me-2"></i>
                            Dueño de la mascota
                        </div>
                        <div class="section-body">
                            <?php if ($dueno): ?>
                                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                    <div>
                                        <h5 class="mb-1"><?= h($dueno['nombre'] ?? ''); ?></h5>
                                        <div class="text-muted small">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?= h($dueno['email'] ?? ''); ?>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="fas fa-id-card me-1"></i>
                                            ID Usuario: #<?= (int)$dueno['usu_id']; ?>
                                        </div>
                                    </div>
                                    <div class="mt-2 mt-sm-0">
                                        <span class="badge bg-info text-dark">
                                            <?= ucfirst(strtolower($dueno['rol'] ?? 'dueno')); ?>
                                        </span>
                                    </div>
                                </div>

                                <button class="btn btn-outline-success btn-sm"
                                    onclick="window.location.href='<?= BASE_URL; ?>/features/admin/editar_usuario.php?id=<?= (int)$dueno['usu_id']; ?>'">
                                    <i class="fas fa-user-pen me-1"></i>
                                    Ver / editar perfil de dueño
                                </button>
                            <?php else: ?>
                                <div class="alert alert-light mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    No se encontró información del dueño.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Reputación de la mascota -->
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-star-half-alt me-2"></i>
                            Reputación de la mascota
                        </div>
                        <div class="section-body">
                            <?php if ($promMascota !== null && $totalOpin > 0): ?>
                                <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between">
                                    <div class="mb-2 mb-sm-0">
                                        <div class="display-6 mb-0">
                                            <?= number_format((float)$promMascota, 1, ',', '.'); ?>
                                            <span class="fs-5">/ 5</span>
                                        </div>
                                        <div class="rating-stars fs-4">
                                            <?php
                                            $rounded = (int)round($promMascota);
                                            for ($i = 1; $i <= 5; $i++):
                                                $cls = $i <= $rounded ? 'fas text-warning' : 'far text-muted';
                                            ?>
                                                <i class="<?= $cls; ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= (int)$totalOpin; ?> opinión<?= $totalOpin === 1 ? '' : 'es'; ?> de paseadores
                                        </div>
                                    </div>
                                    <div class="text-muted small">
                                        Las calificaciones de la mascota ayudan a los paseadores
                                        a anticipar su comportamiento y necesidades.
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light mb-0 text-center">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Esta mascota todavía no tiene calificaciones registradas.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- col derecha -->
            </div><!-- row -->

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel de Administración
            </footer>
        </div><!-- py-4 -->
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar móvil
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });
    </script>
</body>

</html>