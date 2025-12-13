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

AppConfig::init();

// 🔒 Solo admin
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

function h(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$mascotaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($mascotaId <= 0) {
    http_response_code(400);
    exit('<h3 style="color:red; text-align:center;">ID de mascota no válido</h3>');
}

$mascotaModel = new Mascota();
$usuarioModel = new Usuario();

$mascota = $mascotaModel->find($mascotaId);
if (!$mascota) {
    http_response_code(404);
    exit('<h3 style="color:red; text-align:center;">Mascota no encontrada</h3>');
}

$duenoId = (int)($mascota['dueno_id'] ?? 0);
$dueno   = $duenoId > 0 ? $usuarioModel->find($duenoId) : null;

$reputacion  = $mascotaModel->resumenPorMascota($mascotaId);
$promMascota = $reputacion['promedio'] ?? null;
$totalOpin   = (int)($reputacion['total'] ?? 0);

$tamano = strtolower((string)($mascota['tamano'] ?? ''));
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
    $edadTexto = $anios . ' año' . ($anios > 1 ? 's' : '');
    if ($resto > 0) $edadTexto .= " y $resto mes" . ($resto > 1 ? 'es' : '');
} elseif ($edadMeses > 0) {
    $edadTexto = $edadMeses . ' mes' . ($edadMeses > 1 ? 'es' : '');
} else {
    $edadTexto = 'N/D';
}

$fotoUrl = (string)($mascota['foto_url'] ?? '');

$estadoMascota = strtolower((string)($mascota['estado'] ?? 'activo'));
if (!in_array($estadoMascota, ['activo', 'inactivo'], true)) {
    $estadoMascota = 'activo';
}
$badgeEstado = match ($estadoMascota) {
    'activo'   => 'estado-activo',
    'inactivo' => 'estado-inactivo',
    default    => 'estado-activo'
};
$estadoLabel = ucfirst($estadoMascota);

// Acción sugerida
$accionEstado = ($estadoMascota === 'activo') ? 'inactivar' : 'activar';
$btnEstadoClass = ($estadoMascota === 'activo') ? 'btn-warning text-dark' : 'btn-success';
$btnEstadoIcon  = ($estadoMascota === 'activo') ? 'fa-ban' : 'fa-check-circle';
$btnEstadoText  = ($estadoMascota === 'activo') ? 'Inactivar' : 'Activar';

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

    <!-- ✅ Extra: orden visual sin romper tu theme -->
    <style>
        .kv-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
        }

        .kv {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .06);
            border-radius: 16px;
            padding: .75rem .9rem;
            text-align: left;
        }

        .kv .k {
            font-size: .78rem;
            opacity: .75;
        }

        .kv .v {
            font-weight: 700;
            margin-top: .1rem;
        }

        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .pet-hero {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .75rem;
            border: 1px dashed rgba(60, 98, 85, .25);
            border-radius: 18px;
            background: rgba(32, 201, 151, .06);
        }

        .pet-hero img,
        .pet-hero .avatar {
            width: 86px;
            height: 86px;
            border-radius: 999px;
            object-fit: cover;
        }

        .pet-hero .avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e9f7f1;
        }

        .pet-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .pet-actions .btn {
            border-radius: 14px;
        }

        .section-body hr {
            opacity: .15;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar" type="button">
        <i class="fas fa-bars"></i>
    </button>

    <main>
        <div class="py-4">

            <!-- HEADER -->
            <div class="header-box header-dashboard mb-4">
                <div>
                    <h1 class="fw-bold mb-1"><i class="fas fa-paw me-2"></i>Perfil de Mascota</h1>
                    <p class="mb-0">Visualizá los datos detallados de la mascota y su dueño 🐶</p>
                </div>
                <div class="text-end">
                    <a href="<?= BASE_URL; ?>/features/admin/Mascotas.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver al listado
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <!-- IZQUIERDA -->
                <div class="col-lg-5">
                    <div class="section-card">
                        <div class="section-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <i class="fas fa-dog me-2"></i>
                                Mascota #<?= (int)$mascota['mascota_id']; ?>
                            </div>

                            <!-- chips arriba -->
                            <div class="chip-row">
                                <span class="badge <?= h($badgeTamano); ?>"><?= h($tamanoLabel); ?></span>
                                <span class="badge-estado <?= h($badgeEstado); ?>"><?= h($estadoLabel); ?></span>
                            </div>
                        </div>

                        <div class="section-body">
                            <!-- HERO -->
                            <div class="pet-hero">
                                <?php if ($fotoUrl !== ''): ?>
                                    <img src="<?= h($fotoUrl); ?>" alt="Foto de <?= h($mascota['nombre'] ?? ''); ?>">
                                <?php else: ?>
                                    <div class="avatar">
                                        <i class="fas fa-dog fa-2x text-success"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="flex-grow-1">
                                    <h3 class="mb-1"><?= h($mascota['nombre'] ?? ''); ?></h3>
                                    <div class="text-muted small">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Registrada el <?= h(substr((string)($mascota['created_at'] ?? ''), 0, 10)); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-user me-1"></i>
                                        Dueño ID: #<?= (int)$duenoId; ?>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            <!-- DATOS ORDENADOS -->
                            <div class="kv-grid">
                                <div class="kv">
                                    <div class="k">Raza</div>
                                    <div class="v"><?= h($mascota['raza'] ?? 'N/D'); ?></div>
                                </div>
                                <div class="kv">
                                    <div class="k">Edad estimada</div>
                                    <div class="v"><?= h($edadTexto); ?></div>
                                </div>
                                <div class="kv">
                                    <div class="k">Peso</div>
                                    <div class="v"><?= number_format((float)($mascota['peso_kg'] ?? 0), 1, ',', '.'); ?> kg</div>
                                </div>
                                <div class="kv">
                                    <div class="k">Estado</div>
                                    <div class="v">
                                        <span class="badge-estado <?= h($badgeEstado); ?>"><?= h($estadoLabel); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- ACCIONES -->
                            <div class="pet-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-sm <?= h($btnEstadoClass); ?>"
                                    id="btnEstadoMascota"
                                    data-id="<?= (int)$mascotaId ?>"
                                    data-accion="<?= h($accionEstado); ?>">
                                    <i class="fas <?= h($btnEstadoIcon); ?> me-1"></i> <?= h($btnEstadoText); ?>
                                </button>

                                <a class="btn btn-sm btn-outline-secondary"
                                    href="<?= BASE_URL; ?>/features/admin/Mascotas.php">
                                    <i class="fas fa-list me-1"></i> Listado
                                </a>
                            </div>

                            <!-- OBSERVACIONES -->
                            <div class="mt-3">
                                <div class="text-muted small mb-1">Observaciones</div>
                                <div class="kv" style="padding: .85rem 1rem;">
                                    <?php if (!empty($mascota['observaciones'])): ?>
                                        <div class="mb-0"><?= nl2br(h((string)$mascota['observaciones'])); ?></div>
                                    <?php else: ?>
                                        <div class="text-muted mb-0">Sin observaciones registradas.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- DERECHA -->
                <div class="col-lg-7">
                    <!-- Dueño -->
                    <div class="section-card mb-3">
                        <div class="section-header">
                            <i class="fas fa-user me-2"></i>Dueño de la mascota
                        </div>
                        <div class="section-body">
                            <?php if ($dueno): ?>
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h5 class="mb-1"><?= h($dueno['nombre'] ?? ''); ?></h5>

                                        <div class="text-muted small">
                                            <i class="fas fa-envelope me-1"></i><?= h($dueno['email'] ?? ''); ?>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="fas fa-id-card me-1"></i>ID Usuario: #<?= (int)($dueno['usu_id'] ?? 0); ?>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <span class="badge bg-info text-dark">
                                            <?= h(ucfirst(strtolower((string)($dueno['rol'] ?? 'dueno')))); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button class="btn btn-outline-success btn-sm"
                                        type="button"
                                        onclick="window.location.href='<?= BASE_URL; ?>/features/admin/editar_usuario.php?id=<?= (int)($dueno['usu_id'] ?? 0); ?>'">
                                        <i class="fas fa-user-pen me-1"></i> Ver / editar perfil de dueño
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light mb-0">
                                    <i class="fas fa-info-circle me-1"></i>No se encontró información del dueño.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Reputación -->
                    <div class="section-card">
                        <div class="section-header">
                            <i class="fas fa-star-half-alt me-2"></i>Reputación de la mascota
                        </div>
                        <div class="section-body">
                            <?php if ($promMascota !== null && $totalOpin > 0): ?>
                                <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
                                    <div class="text-center text-sm-start">
                                        <div class="display-6 mb-0">
                                            <?= number_format((float)$promMascota, 1, ',', '.'); ?>
                                            <span class="fs-5">/ 5</span>
                                        </div>

                                        <div class="rating-stars fs-4">
                                            <?php
                                            $rounded = (int)round((float)$promMascota);
                                            for ($i = 1; $i <= 5; $i++):
                                                $cls = $i <= $rounded ? 'fas text-warning' : 'far text-muted';
                                            ?>
                                                <i class="<?= h($cls); ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>

                                        <div class="small text-muted">
                                            <?= (int)$totalOpin; ?> opinión<?= $totalOpin === 1 ? '' : 'es'; ?> de paseadores
                                        </div>
                                    </div>

                                    <div class="text-muted small">
                                        Las calificaciones ayudan a anticipar comportamiento y necesidades durante el paseo.
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
                </div>

            </div>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y'); ?> Jaguata — Panel de Administración
            </footer>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle sidebar móvil
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.querySelector('.sidebar')?.classList.toggle('sidebar-open');
            document.querySelector('.sidebar-backdrop')?.classList.toggle('show');
        });

        // Activar/Inactivar mascota
        document.getElementById('btnEstadoMascota')?.addEventListener('click', async function() {
            const id = this.dataset.id;
            const accion = this.dataset.accion;

            const msg = accion === 'activar' ?
                '¿Activar esta mascota?' :
                '¿Inactivar esta mascota?';

            if (!confirm(msg)) return;

            this.disabled = true;

            try {
                const fd = new FormData();
                fd.append('id', id);
                fd.append('accion', accion);

                const res = await fetch('<?= BASE_URL; ?>/public/api/mascotas/accionesMascota.php', {
                    method: 'POST',
                    body: fd
                });

                const data = await res.json();
                alert(data.mensaje || 'Operación realizada');
                if (data.ok) location.reload();
            } catch (e) {
                console.error(e);
                alert('Error inesperado al actualizar el estado.');
            } finally {
                this.disabled = false;
            }
        });
    </script>

</body>

</html>