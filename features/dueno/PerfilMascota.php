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

// ===== Init + auth =====
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

// ===== Helpers =====
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function edadHumana(?int $meses): string
{
    if (!$meses || $meses <= 0) return '—';
    if ($meses < 12) return $meses . ' meses';
    $a = intdiv($meses, 12);
    $m = $meses % 12;
    return $m ? "{$a} años, {$m} meses" : "{$a} años";
}

function tamanoEtiqueta(?string $t): string
{
    return match ($t) {
        'pequeno' => 'Pequeño',
        'mediano' => 'Mediano',
        'grande'  => 'Grande',
        default   => '—',
    };
}

function badgeEstado(string $estado): string
{
    $estado = strtolower($estado);
    return match ($estado) {
        'completo'   => '<span class="badge bg-success">Completo</span>',
        'cancelado'  => '<span class="badge bg-danger">Cancelado</span>',
        'confirmado' => '<span class="badge bg-primary">Confirmado</span>',
        'pendiente'  => '<span class="badge bg-warning text-dark">Pendiente</span>',
        default      => '<span class="badge bg-secondary">' . h($estado) . '</span>',
    };
}

// ===== Navegación segura (Volver) =====
$rol = Session::getUsuarioRol() ?: 'dueno';
$defaultBack = BASE_URL . "/features/{$rol}/MisMascotas.php";
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$backUrl = (is_string($referer) && str_starts_with($referer, BASE_URL)) ? $referer : $defaultBack;

// ===== Param =====
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "ID inválido";
    header("Location: MisMascotas.php");
    exit;
}

// ===== Controladores =====
$mascotaController = new MascotaController();
$paseoController   = new PaseoController();

// ===== Datos mascota =====
$mascota = $mascotaController->show($id);
if (isset($mascota['error'])) {
    $_SESSION['error'] = $mascota['error'];
    header("Location: MisMascotas.php");
    exit;
}

// Atributos mascota
$nombre        = h($mascota['nombre'] ?? 'Mascota');
$raza          = $mascota['raza'] ?? null;
$pesoKg        = $mascota['peso_kg'] ?? null;
$tamano        = $mascota['tamano'] ?? null; // pequeno|mediano|grande
$edadMeses     = isset($mascota['edad_meses']) ? (int)$mascota['edad_meses'] : (isset($mascota['edad']) ? (int)$mascota['edad'] : null);
$observaciones = h($mascota['observaciones'] ?? '');
$fotoUrl       = $mascota['foto_url'] ?? '';
$creado        = $mascota['created_at'] ?? null;
$actualizado   = $mascota['updated_at'] ?? null;

// ===== Selector de mascotas (del dueño actual) =====
$listaSelect = [];
try {
    $todas = $mascotaController->index(); // ya devuelve solo las del dueño
    foreach ($todas as $mx) {
        $mid = (int)($mx['mascota_id'] ?? $mx['id'] ?? $mx['id_mascota'] ?? 0);
        if ($mid > 0) {
            $listaSelect[] = [
                'id'     => $mid,
                'nombre' => $mx['nombre'] ?? ('Mascota #' . $mid),
            ];
        }
    }
} catch (\Throwable $e) {
    // si algo falla, no rompemos la página
    $listaSelect = [];
}

// ===== Paseos de esta mascota =====
$paseos = $paseoController->index(); // listado general (tu controlador debería filtrar por dueño)
$paseosMascota = array_values(array_filter($paseos, function (array $p) use ($id) {
    $mid = $p['mascota_id'] ?? ($p['id_mascota'] ?? null);
    return (int)$mid === $id;
}));

// Ordenar recientes (desc)
$recientes = $paseosMascota;
usort($recientes, function ($a, $b) {
    $ta = strtotime($a['inicio'] ?? $a['created_at'] ?? '1970-01-01');
    $tb = strtotime($b['inicio'] ?? $b['created_at'] ?? '1970-01-01');
    return $tb <=> $ta;
});
$recientes = array_slice($recientes, 0, 5);

// Particiones por estado
$pendientes  = array_values(array_filter($paseosMascota, fn($p) => in_array(strtolower($p['estado'] ?? ''), ['pendiente', 'confirmado'], true)));
$completados = array_values(array_filter($paseosMascota, fn($p) => strtolower($p['estado'] ?? '') === 'completo'));
$cancelados  = array_values(array_filter($paseosMascota, fn($p) => strtolower($p['estado'] ?? '') === 'cancelado'));

// Métricas
$totalPaseos   = count($paseosMascota);
$totalCompleto = count($completados);
$totalPendConf = count($pendientes);
$totalCancel   = count($cancelados);
$gastoTotal    = 0;
foreach ($completados as $px) $gastoTotal += (float)($px['precio_total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Perfil de Mascota - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">

    <style>
        .card {
            border-radius: 12px;
        }

        .img-avatar {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, .08);
            background: #f8f9fa;
        }

        .kv {
            font-size: .9rem;
        }

        .kv .k {
            color: #6c757d;
            width: 140px;
        }

        .kv .v {
            font-weight: 600;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-12 col-lg-12 px-md-4">

                <!-- Título + volver + selector -->
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= h($backUrl) ?>" class="btn btn-outline-secondary btn-sm"
                            onclick="event.preventDefault(); if (history.length>1){history.back();} else {window.location.href='<?= h($backUrl) ?>';}">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <h1 class="h2 mb-0"><i class="fas fa-paw me-2"></i> Perfil de <?= $nombre ?></h1>
                    </div>

                    <?php if (count($listaSelect) > 1): ?>
                        <div class="d-flex align-items-center gap-2">
                            <label for="selectMascota" class="small text-muted mb-0">Cambiar mascota:</label>
                            <select id="selectMascota" class="form-select form-select-sm"
                                onchange="if(this.value){ window.location.href='PerfilMascota.php?id=' + this.value; }">
                                <?php foreach ($listaSelect as $opt): ?>
                                    <option value="<?= (int)$opt['id'] ?>" <?= ((int)$opt['id'] === $id ? 'selected' : '') ?>>
                                        <?= h($opt['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="PerfilMascotaSeleccionar.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list me-1"></i> Ver todas
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="PerfilMascotaSeleccionar.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list me-1"></i> Ver otras mascotas
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mensajes -->
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?= $_SESSION['success'];
                                                                    unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-triangle-exclamation me-1"></i> <?= $_SESSION['error'];
                                                                            unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-3">
                    <!-- Col izquierda: info principal -->
                    <div class="col-lg-4">
                        <div class="card shadow h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <?php if (!empty($fotoUrl)): ?>
                                        <img src="<?= h($fotoUrl) ?>" alt="Foto de <?= $nombre ?>" class="img-avatar">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/140x140.png?text=Mascota" alt="Sin foto" class="img-avatar">
                                    <?php endif; ?>
                                    <div>
                                        <h4 class="mb-0"><?= $nombre ?></h4>
                                        <small class="text-muted">ID: <?= (int)$id ?></small>
                                    </div>
                                </div>

                                <div class="kv d-flex mb-2">
                                    <div class="k">Raza</div>
                                    <div class="v flex-fill"><?= $raza ? h($raza) : '—'; ?></div>
                                </div>
                                <div class="kv d-flex mb-2">
                                    <div class="k">Peso</div>
                                    <div class="v flex-fill"><?= $pesoKg !== null ? number_format((float)$pesoKg, 1, ',', '.') . ' kg' : '—'; ?></div>
                                </div>
                                <div class="kv d-flex mb-2">
                                    <div class="k">Tamaño</div>
                                    <div class="v flex-fill"><?= tamanoEtiqueta($tamano); ?></div>
                                </div>
                                <div class="kv d-flex mb-2">
                                    <div class="k">Edad</div>
                                    <div class="v flex-fill"><?= edadHumana($edadMeses); ?></div>
                                </div>
                                <div class="kv d-flex mb-2">
                                    <div class="k">Creado</div>
                                    <div class="v flex-fill"><?= $creado ? date('d/m/Y H:i', strtotime($creado)) : '—' ?></div>
                                </div>
                                <div class="kv d-flex">
                                    <div class="k">Actualizado</div>
                                    <div class="v flex-fill"><?= $actualizado ? date('d/m/Y H:i', strtotime($actualizado)) : '—' ?></div>
                                </div>

                                <?php if (!empty($observaciones)): ?>
                                    <hr>
                                    <div>
                                        <div class="text-muted mb-1">Observaciones</div>
                                        <div><?= nl2br($observaciones) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Col derecha: métricas + paseos -->
                    <div class="col-lg-8">
                        <!-- KPIs -->
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="card shadow h-100">
                                    <div class="card-body">
                                        <div class="text-muted text-uppercase small">Paseos</div>
                                        <div class="display-6"><?= $totalPaseos ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow h-100">
                                    <div class="card-body">
                                        <div class="text-muted text-uppercase small">Completados</div>
                                        <div class="display-6"><?= $totalCompleto ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow h-100">
                                    <div class="card-body">
                                        <div class="text-muted text-uppercase small">Pend/Conf</div>
                                        <div class="display-6"><?= $totalPendConf ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow h-100">
                                    <div class="card-body">
                                        <div class="text-muted text-uppercase small">Gasto Total</div>
                                        <div class="h4 mb-0">₲<?= number_format($gastoTotal, 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paseos recientes -->
                        <div class="card shadow mt-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-walking me-2 text-primary"></i> Paseos recientes</h5>
                                <div class="btn-group btn-group-sm">
                                    <a href="PaseosPendientes.php?mascota_id=<?= (int)$id ?>" class="btn btn-outline-secondary">Pendientes</a>
                                    <a href="PaseosCompletados.php?mascota_id=<?= (int)$id ?>" class="btn btn-outline-secondary">Completados</a>
                                    <a href="PaseosCancelados.php?mascota_id=<?= (int)$id ?>" class="btn btn-outline-secondary">Cancelados</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recientes)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-xmark fa-2x text-muted mb-2"></i>
                                        <div class="text-muted">No hay paseos registrados para esta mascota.</div>
                                        <a href="SolicitarPaseo.php" class="btn btn-primary btn-sm mt-2">
                                            <i class="fas fa-plus me-1"></i> Solicitar paseo
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="min-width:160px;">Fecha</th>
                                                    <th>Paseador</th>
                                                    <th>Estado</th>
                                                    <th>Precio</th>
                                                    <th>Duración</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recientes as $p): ?>
                                                    <?php
                                                    $ini    = $p['inicio'] ?? null;
                                                    $dur    = $p['duracion_min'] ?? null;
                                                    $nomPa  = $p['nombre_paseador'] ?? null;
                                                    $est    = $p['estado'] ?? '';
                                                    $precio = $p['precio_total'] ?? 0;
                                                    ?>
                                                    <tr>
                                                        <td><?= $ini ? date('d/m/Y H:i', strtotime($ini)) : '—' ?></td>
                                                        <td><?= $nomPa ? h($nomPa) : '<span class="text-muted">—</span>' ?></td>
                                                        <td><?= badgeEstado((string)$est) ?></td>
                                                        <td>₲<?= number_format((float)$precio, 0, ',', '.') ?></td>
                                                        <td><?= $dur !== null ? (int)$dur . ' min' : '—' ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="<?= h($backUrl) ?>" class="btn btn-outline-secondary"
                                onclick="event.preventDefault(); if (history.length>1){history.back();} else {window.location.href='<?= h($backUrl) ?>';}">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                            <a href="EditarMascota.php?id=<?= (int)$id ?>" class="btn btn-primary">
                                <i class="fas fa-pen-to-square me-1"></i> Editar Mascota
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-4 small text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Este perfil usa los datos actuales de la mascota y filtra los paseos por
                    <code>mascota_id=<?= (int)$id ?></code>.
                </div>

            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>