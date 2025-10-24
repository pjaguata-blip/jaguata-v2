<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

$rol = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rol}";

$paseoController = new PaseoController();
$mascotaController = new MascotaController();

$allPaseos = $paseoController->index();
$mascotas = $mascotaController->index();

// Helpers
function fmtDate($v, string $format = 'd/m/Y H:i'): string
{
    if (!$v) return '-';
    $ts = is_numeric($v) ? (int)$v : strtotime((string)$v);
    return $ts ? date($format, $ts) : '-';
}
function fmtInt($v, string $suf = ' min'): string
{
    return ($v !== null && $v !== '') ? ((int)$v) . $suf : '-';
}
function fmtGs($v): string
{
    return '₲' . number_format((float)($v ?: 0), 0, ',', '.');
}
function estadoToBadge(string $estadoRaw): array
{
    $lc = mb_strtolower($estadoRaw);
    return match ($lc) {
        'cancelado' => ['danger', '❌ Cancelado'],
        'completo', 'completado' => ['success', '✅ Completo'],
        'pendiente', 'solicitado' => ['warning', '🕓 Solicitado'],
        'confirmado' => ['info', '📅 Confirmado'],
        default => ['secondary', ucfirst($estadoRaw ?: '-')],
    };
}

$q = trim((string)($_GET['q'] ?? ''));
$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;

$normalizeTs = function (?string $d, bool $end = false): ?int {
    if (!$d) return null;
    $dt = DateTime::createFromFormat('Y-m-d', $d) ?: (strtotime($d) ? new DateTime($d) : null);
    if (!$dt) return null;
    $dt->setTime($end ? 23 : 0, $end ? 59 : 0, $end ? 59 : 0);
    return $dt->getTimestamp();
};
$tsDesde = $normalizeTs($desde, false);
$tsHasta = $normalizeTs($hasta, true);

// Filtrado por dueño
$idsMascotasDueno = [];
foreach ($mascotas as $m) {
    $mid = $m['mascota_id'] ?? $m['id'] ?? null;
    if ($mid !== null) $idsMascotasDueno[(int)$mid] = true;
}

$soloDueno = array_values(array_filter($allPaseos, fn($p) => isset($idsMascotasDueno[(int)($p['mascota_id'] ?? 0)])));
$paseos = array_values(array_filter($soloDueno, function ($p) use ($q, $tsDesde, $tsHasta) {
    $estado = mb_strtolower((string)($p['estado'] ?? ''));
    if ($estado !== 'cancelado') return false;
    $iniTs = isset($p['inicio']) ? (is_numeric($p['inicio']) ? (int)$p['inicio'] : strtotime((string)$p['inicio'])) : null;
    if ($tsDesde && $iniTs && $iniTs < $tsDesde) return false;
    if ($tsHasta && $iniTs && $iniTs > $tsHasta) return false;
    if ($q !== '') {
        foreach (['nombre_mascota', 'nombre_paseador', 'comentario', 'estado'] as $k)
            if (!empty($p[$k]) && stripos((string)$p[$k], $q) !== false) return true;
        return false;
    }
    return true;
}));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Paseos Cancelados - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Poppins", sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            min-height: 100vh;
            padding-top: 1rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar .nav-link {
            color: #ddd;
            border-radius: 8px;
            padding: 10px 16px;
            margin: 4px 8px;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all .2s ease;
        }

        .sidebar .nav-link:hover {
            background: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #b23b3b;
            color: #fff;
        }

        main {
            background: #f5f7fa;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(90deg, #ff6b6b, #b23b3b);
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-weight: 600;
            margin: 0;
        }

        .page-header .btn-light {
            background: #fff;
            color: #b23b3b;
        }

        .page-header .btn-light:hover {
            background: #b23b3b;
            color: #fff;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .table thead th {
            background: #b23b3b;
            color: #fff;
            border: none;
        }

        .table-hover tbody tr:hover {
            background: #fdeaea;
        }

        .btn-outline-danger {
            border-color: #b23b3b;
            color: #b23b3b;
        }

        .btn-outline-danger:hover {
            background: #b23b3b;
            color: #fff;
        }

        .btn-outline-secondary {
            border-color: #20c997;
            color: #20c997;
        }

        .btn-outline-secondary:hover {
            background: #20c997;
            color: #fff;
        }

        .badge {
            font-size: .85rem;
            padding: 6px 10px;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home me-2"></i>Inicio</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw me-2"></i>Mis Mascotas</a></li>
                        <li><a class="nav-link active" href="#"><i class="fas fa-ban me-2"></i>Paseos Cancelados</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php"><i class="fas fa-wallet me-2"></i>Gastos</a></li>
                        <li><a class="nav-link" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell me-2"></i>Notificaciones</a></li>
                        <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Salir</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header">
                    <h1><i class="fas fa-ban me-2"></i>Paseos Cancelados</h1>
                    <a href="ExportarDashboard.php?estado=cancelado" class="btn btn-light btn-sm">
                        <i class="fas fa-file-export me-1"></i> Exportar
                    </a>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3" method="get">
                            <div class="col-sm-3">
                                <label class="form-label fw-semibold">Desde</label>
                                <input type="date" name="desde" value="<?= htmlspecialchars((string)$desde) ?>" class="form-control">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label fw-semibold">Hasta</label>
                                <input type="date" name="hasta" value="<?= htmlspecialchars((string)$hasta) ?>" class="form-control">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label fw-semibold">Buscar</label>
                                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Mascota, paseador..." class="form-control">
                            </div>
                            <div class="col-sm-2 d-flex align-items-end">
                                <button class="btn btn-outline-danger w-100"><i class="fas fa-search me-1"></i> Filtrar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($paseos)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-heart-broken fa-3x mb-3 text-danger"></i>
                                <p>No hay paseos cancelados con los filtros aplicados.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle text-center">
                                    <thead>
                                        <tr>
                                            <th>Mascota</th>
                                            <th>Paseador</th>
                                            <th>Inicio</th>
                                            <th>Fin</th>
                                            <th>Duración</th>
                                            <th>Precio</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paseos as $p):
                                            $inicio = fmtDate($p['inicio'] ?? null);
                                            $fin = fmtDate($p['fin'] ?? null);
                                            $duracion = fmtInt($p['duracion_min'] ?? $p['duracion'] ?? null);
                                            $precio = fmtGs($p['precio_total'] ?? null);
                                            [$cls, $txt] = estadoToBadge((string)($p['estado'] ?? ''));
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['nombre_mascota'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($p['nombre_paseador'] ?? '-') ?></td>
                                                <td><?= $inicio ?></td>
                                                <td><?= $fin ?></td>
                                                <td><?= $duracion ?></td>
                                                <td><?= $precio ?></td>
                                                <td><span class="badge bg-<?= $cls ?>"><?= $txt ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>