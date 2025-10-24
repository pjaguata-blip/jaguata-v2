<?php
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

$controller = new MascotaController();
$mascotas   = $controller->index();

$rolMenu     = Session::getUsuarioRol() ?: 'dueno';
$defaultBack = BASE_URL . "/features/{$rolMenu}/Dashboard.php";
$referer     = $_SERVER['HTTP_REFERER'] ?? '';
$backUrl     = (is_string($referer) && str_starts_with($referer, BASE_URL)) ? $referer : $defaultBack;

function edadAmigable($meses): string
{
    if ($meses === null || $meses === '') return '—';
    $m = (int)$meses;
    if ($m < 12) return $m . ' mes' . ($m === 1 ? '' : 'es');
    $a = intdiv($m, 12);
    $r = $m % 12;
    return $r ? "{$a} a {$r} m" : "{$a} año" . ($a === 1 ? '' : 's');
}
function etiquetaTamano(?string $t): string
{
    return match ($t) {
        'pequeno' => 'Pequeño',
        'mediano' => 'Mediano',
        'grande'  => 'Grande',
        'gigante' => 'Gigante',
        default   => '—',
    };
}
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$q = trim($_GET['q'] ?? '');
$raza = trim($_GET['raza'] ?? '');
$tamano = trim($_GET['tamano'] ?? '');
$edadMin = ($_GET['edad_min'] ?? '') !== '' ? (int)$_GET['edad_min'] : null;
$edadMax = ($_GET['edad_max'] ?? '') !== '' ? (int)$_GET['edad_max'] : null;

$razasUnicas = [];
$tamanosUnicos = [];
foreach ($mascotas as $m) {
    if (!empty($m['raza'])) $razasUnicas[$m['raza']] = true;
    if (!empty($m['tamano'])) $tamanosUnicos[$m['tamano']] = true;
}
$razasUnicas = array_keys($razasUnicas);
$tamanosUnicos = array_keys($tamanosUnicos);
sort($razasUnicas);
sort($tamanosUnicos);

$mascotasFiltradas = array_values(array_filter($mascotas, function ($m) use ($q, $raza, $tamano, $edadMin, $edadMax) {
    $ok = true;
    if ($q !== '') {
        $txt = strtolower(($m['nombre'] ?? '') . ' ' . ($m['raza'] ?? '') . ' ' . ($m['tamano'] ?? ''));
        $ok = $ok && str_contains($txt, strtolower($q));
    }
    if ($raza !== '') $ok = $ok && (($m['raza'] ?? '') === $raza);
    if ($tamano !== '') $ok = $ok && (($m['tamano'] ?? '') === $tamano);
    $edadMeses = (int)($m['edad_meses'] ?? ($m['edad'] ?? 0));
    if ($edadMin !== null) $ok = $ok && $edadMeses >= $edadMin;
    if ($edadMax !== null) $ok = $ok && $edadMeses <= $edadMax;
    return $ok;
}));
$resultCount = count($mascotasFiltradas);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Mascotas - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
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
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background-color: #3c6255;
            color: #fff;
        }

        main {
            background-color: #f5f7fa;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
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
            background-color: #fff;
            color: #3c6255;
        }

        .page-header .btn-light:hover {
            background-color: #3c6255;
            color: #fff;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(90deg, #3c6255, #20c997);
            color: #fff;
            font-weight: 600;
        }

        table thead th {
            background-color: #3c6255;
            color: #fff;
            border: none;
        }

        table tbody tr:hover {
            background-color: #e6f4ea;
        }

        .btn-outline-primary {
            border-color: #3c6255;
            color: #3c6255;
        }

        .btn-outline-primary:hover {
            background-color: #3c6255;
            color: #fff;
        }

        .btn-outline-secondary {
            border-color: #20c997;
            color: #20c997;
        }

        .btn-outline-secondary:hover {
            background-color: #20c997;
            color: #fff;
        }

        .badge.bg-success {
            background-color: #20c997 !important;
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
                        <li><a class="nav-link" href="<?= BASE_URL ?>/features/dueno/Dashboard.php"><i class="fas fa-home me-2"></i> Inicio</a></li>
                        <li><a class="nav-link active" href="#"><i class="fas fa-paw me-2"></i> Mis Mascotas</a></li>
                        <li><a class="nav-link" href="AgregarMascota.php"><i class="fas fa-plus me-2"></i> Nueva Mascota</a></li>
                        <li><a class="nav-link" href="PaseosPendientes.php"><i class="fas fa-walking me-2"></i> Paseos</a></li>
                        <li><a class="nav-link" href="GastosTotales.php"><i class="fas fa-wallet me-2"></i> Gastos</a></li>
                        <li><a class="nav-link" href="Notificaciones.php"><i class="fas fa-bell me-2"></i> Notificaciones</a></li>
                        <li><a class="nav-link text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header">
                    <h1><i class="fas fa-paw me-2"></i> Mis Mascotas</h1>
                    <div class="d-flex gap-2">
                        <a href="<?= h($backUrl) ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <a href="AgregarMascota.php" class="btn btn-light btn-sm text-success">
                            <i class="fas fa-plus me-1"></i> Nueva
                        </a>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <form class="row g-3 align-items-end" method="get">
                            <div class="col-md-4">
                                <label class="form-label">Buscar</label>
                                <input type="text" class="form-control" name="q" value="<?= h($q) ?>" placeholder="Nombre, raza o tamaño">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Raza</label>
                                <select name="raza" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($razasUnicas as $r): ?>
                                        <option value="<?= h($r) ?>" <?= $raza === $r ? 'selected' : '' ?>><?= h($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tamaño</label>
                                <select name="tamano" class="form-select">
                                    <option value="">Todos</option>
                                    <?php foreach ($tamanosUnicos as $t): ?>
                                        <option value="<?= h($t) ?>" <?= $tamano === $t ? 'selected' : '' ?>><?= h(etiquetaTamano($t)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Edad ≥</label>
                                <input type="number" class="form-control" name="edad_min" value="<?= $edadMin ?? '' ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Edad ≤</label>
                                <input type="number" class="form-control" name="edad_max" value="<?= $edadMax ?? '' ?>">
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-filter me-1"></i> Filtrar</button>
                                <a href="MisMascotas.php" class="btn btn-outline-secondary"><i class="fas fa-eraser me-1"></i> Limpiar</a>
                                <span class="ms-auto badge bg-success"><?= $resultCount ?> resultado<?= $resultCount === 1 ? '' : 's' ?></span>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if ($resultCount === 0): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-dog fa-2x mb-2"></i>
                                <p>No se encontraron mascotas.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Foto</th>
                                            <th>Nombre</th>
                                            <th>Raza</th>
                                            <th>Tamaño</th>
                                            <th>Edad</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mascotasFiltradas as $m):
                                            $id = (int)($m['mascota_id'] ?? 0);
                                            $foto = $m['foto_url'] ?? '';
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php if ($foto): ?>
                                                        <img src="<?= h($foto) ?>" alt="Foto" class="rounded" style="width:48px;height:48px;object-fit:cover;">
                                                    <?php else: ?>
                                                        <div class="d-inline-flex align-items-center justify-content-center rounded bg-light" style="width:48px;height:48px;">
                                                            <i class="fas fa-paw text-secondary"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?= h($m['nombre'] ?? '-') ?></strong></td>
                                                <td><?= h($m['raza'] ?? '-') ?></td>
                                                <td><?= h(etiquetaTamano($m['tamano'] ?? null)) ?></td>
                                                <td><?= h(edadAmigable($m['edad_meses'] ?? ($m['edad'] ?? null))) ?></td>
                                                <td class="text-end">
                                                    <a href="PerfilMascota.php?id=<?= $id ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-eye"></i></a>
                                                    <a href="EditarMascota.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                                    <a href="EliminarMascota.php?id=<?= $id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar mascota?')"><i class="fas fa-trash"></i></a>
                                                </td>
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
</body>

</html>