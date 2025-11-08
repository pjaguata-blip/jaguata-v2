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

/* 🔒 Auth */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Datos base */
$controller = new MascotaController();
$mascotas   = $controller->index();

$rolMenu      = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$defaultBack  = $baseFeatures . '/Dashboard.php';
$referer      = $_SERVER['HTTP_REFERER'] ?? '';
$backUrl      = (is_string($referer) && str_starts_with($referer, BASE_URL)) ? $referer : $defaultBack;

/* Helpers */
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

/* Filtros */
$q       = trim($_GET['q'] ?? '');
$raza    = trim($_GET['raza'] ?? '');
$tamano  = trim($_GET['tamano'] ?? '');
$edadMin = ($_GET['edad_min'] ?? '') !== '' ? (int)$_GET['edad_min'] : null;
$edadMax = ($_GET['edad_max'] ?? '') !== '' ? (int)$_GET['edad_max'] : null;

/* Opciones únicas */
$razasUnicas = [];
$tamanosUnicos = [];
foreach ($mascotas as $m) {
    if (!empty($m['raza'])) {
        $razasUnicas[$m['raza']]   = true;
    }
    if (!empty($m['tamano'])) {
        $tamanosUnicos[$m['tamano']] = true;
    }
}
$razasUnicas   = array_keys($razasUnicas);
sort($razasUnicas);
$tamanosUnicos = array_keys($tamanosUnicos);
sort($tamanosUnicos);

/* Aplicar filtros */
$mascotasFiltradas = array_values(array_filter($mascotas, function ($m) use ($q, $raza, $tamano, $edadMin, $edadMax) {
    $ok = true;
    if ($q !== '') {
        $txt = strtolower(($m['nombre'] ?? '') . ' ' . ($m['raza'] ?? '') . ' ' . ($m['tamano'] ?? ''));
        $ok = $ok && str_contains($txt, strtolower($q));
    }
    if ($raza !== '') {
        $ok = $ok && (($m['raza'] ?? '') === $raza);
    }
    if ($tamano !== '') {
        $ok = $ok && (($m['tamano'] ?? '') === $tamano);
    }
    $edadMeses = (int)($m['edad_meses'] ?? ($m['edad'] ?? 0));
    if ($edadMin !== null) {
        $ok = $ok && $edadMeses >= $edadMin;
    }
    if ($edadMax !== null) {
        $ok = $ok && $edadMeses <= $edadMax;
    }
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
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        body {
            background-color: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: var(--gris-texto);
            margin: 0
        }

        /* Sidebar unificada */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, .2);
            z-index: 1000
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: .2s;
            font-size: .95rem
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px)
        }

        /* Main */
        main {
            margin-left: 250px;
            padding: 2rem
        }

        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: var(--blanco);
            padding: 1.6rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08)
        }

        .card-header {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            background: var(--verde-jaguata);
            color: #fff;
            font-weight: 600
        }

        .table thead {
            background: var(--verde-jaguata);
            color: #fff
        }

        .table tbody tr:hover {
            background: #e6f4ea
        }

        .btn-outline-primary {
            border-color: var(--verde-jaguata);
            color: var(--verde-jaguata)
        }

        .btn-outline-primary:hover {
            background: var(--verde-jaguata);
            color: #fff
        }

        .btn-outline-secondary {
            border-color: var(--verde-claro);
            color: var(--verde-claro)
        }

        .btn-outline-secondary:hover {
            background: var(--verde-claro);
            color: #fff
        }

        .btn-light.text-success {
            color: var(--verde-claro) !important
        }

        .badge.bg-success {
            background-color: var(--verde-claro) !important
        }

        footer {
            text-align: center;
            padding: 1rem;
            color: #777;
            font-size: .9rem;
            margin-top: 2rem
        }

        @media (max-width:768px) {
            main {
                margin-left: 0;
                padding: 1.25rem
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Contenido -->
    <main>
        <!-- Header -->
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold"><i class="fas fa-paw me-2"></i>Mis Mascotas</h1>
                <p>Gestioná tus mascotas: filtros, perfil y acciones rápidas.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= h($backUrl) ?>" class="btn btn-outline-light fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <a href="AgregarMascota.php" class="btn btn-light fw-semibold text-success">
                    <i class="fas fa-plus me-1"></i> Nueva
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-filter me-2"></i>Filtros</div>
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
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                        <a href="MisMascotas.php" class="btn btn-outline-secondary">
                            <i class="fas fa-eraser me-1"></i> Limpiar
                        </a>
                        <span class="ms-auto badge bg-success"><?= $resultCount ?> resultado<?= $resultCount === 1 ? '' : 's' ?></span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
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
                                    $id  = (int)($m['mascota_id'] ?? 0);
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
                                            <a href="PerfilMascota.php?id=<?= $id ?>" class="btn btn-sm btn-outline-success" title="Ver perfil">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="EditarMascota.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="EliminarMascota.php?id=<?= $id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar mascota?')" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Dueño</small></footer>
    </main>

</body>

</html>