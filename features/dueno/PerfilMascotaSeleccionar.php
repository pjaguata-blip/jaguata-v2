<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function edadAmigable(?int $meses): string
{
    if ($meses === null || $meses < 0) return '—';
    if ($meses < 12) return $meses . ' mes' . ($meses === 1 ? '' : 'es');
    $a = intdiv($meses, 12);
    $m = $meses % 12;
    return $m ? "{$a} a {$m} m" : "{$a} años";
}

$mascotaCtrl = new MascotaController();
$mascotas    = $mascotaCtrl->index(); // ← Solo del dueño

// Si solo hay una mascota, mandamos directo a su perfil
if (count($mascotas) === 1) {
    $mid = (int)($mascotas[0]['mascota_id'] ?? $mascotas[0]['id'] ?? $mascotas[0]['id_mascota'] ?? 0);
    if ($mid > 0) {
        header("Location: PerfilMascota.php?id={$mid}");
        exit;
    }
}

$rol = Session::getUsuarioRol() ?: 'dueno';
$defaultBack = BASE_URL . "/features/{$rol}/Dashboard.php";
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$backUrl = (is_string($referer) && str_starts_with($referer, BASE_URL)) ? $referer : $defaultBack;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Perfiles de mis Mascotas - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <style>
        .pet-card {
            border-radius: 14px;
            overflow: hidden
        }

        .pet-thumb {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            background: #f8f9fa
        }

        .badge-raz {
            background: #eef2ff;
            color: #3b5bdb
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-12 col-lg-12 px-md-4">
                <div class="d-flex align-items-center justify-content-between pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= h($backUrl) ?>" class="btn btn-outline-secondary btn-sm"
                            onclick="event.preventDefault(); if(history.length>1){history.back();} else {window.location.href='<?= h($backUrl) ?>';}">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <h1 class="h2 mb-0"><i class="fas fa-id-badge me-2"></i> Perfiles de mis Mascotas</h1>
                    </div>
                    <a href="AgregarMascota.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Nueva Mascota
                    </a>
                </div>

                <?php if (empty($mascotas)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-dog fa-2x mb-2"></i>
                        <p class="mb-3">Aún no registraste mascotas.</p>
                        <a href="AgregarMascota.php" class="btn btn-primary btn-sm">Agregar Mascota</a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($mascotas as $m):
                            $id  = (int)($m['mascota_id'] ?? $m['id'] ?? $m['id_mascota'] ?? 0);
                            $nom = h($m['nombre'] ?? 'Mascota');
                            $raz = $m['raza'] ?? null;
                            $tam = $m['tamano'] ?? '—';
                            $edm = isset($m['edad']) ? (int)$m['edad'] : (isset($m['edad_meses']) ? (int)$m['edad_meses'] : null);
                            $foto = $m['foto_url'] ?? '';
                        ?>
                            <div class="col-sm-6 col-lg-4 col-xxl-3">
                                <div class="card pet-card shadow h-100">
                                    <?php if ($foto): ?>
                                        <img class="pet-thumb" src="<?= h($foto) ?>" alt="Foto de <?= $nom ?>">
                                    <?php else: ?>
                                        <img class="pet-thumb" src="https://via.placeholder.com/640x360.png?text=Mascota" alt="Sin foto">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title mb-1"><?= $nom ?></h5>
                                        <div class="mb-2">
                                            <?php if ($raz): ?><span class="badge badge-raz"><?= h($raz) ?></span><?php endif; ?>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= h(ucfirst((string)$tam)) ?></span>
                                        </div>
                                        <div class="text-muted small mb-3">
                                            Edad: <?= edadAmigable($edm) ?>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <a class="btn btn-outline-primary" href="PerfilMascota.php?id=<?= $id ?>">
                                                <i class="fas fa-id-card me-1"></i> Ver perfil
                                            </a>
                                            <a class="btn btn-light" href="EditarMascota.php?id=<?= $id ?>">
                                                <i class="fas fa-pen-to-square me-1"></i> Editar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>