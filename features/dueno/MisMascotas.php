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

/* Controlador */
$mascotaController = new MascotaController();

/* POST → crear mascota */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mascotaController->store();
}

/* Helpers */
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
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

/* Vista actual */
$vista = $_GET['vista'] ?? 'lista';

/* Datos de UI */
$rolMenu       = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures  = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = h(Session::getUsuarioNombre() ?? 'Dueño/a');

/* Razas */
$razasDisponibles = require __DIR__ . '/../../src/Data/Razas.php';
sort($razasDisponibles);

/* Estados POST */
$tamanoPost      = $_POST['tamano'] ?? '';
$pesoPost        = $_POST['peso_kg'] ?? '';
$edadValorPost   = $_POST['edad_valor'] ?? '';
$edadUnidadPost  = $_POST['edad_unidad'] ?? 'meses';

/* Mascotas del dueño */
$mascotas = $mascotaController->index();

/* Filtros */
$q       = trim($_GET['q'] ?? '');
$raza    = trim($_GET['raza'] ?? '');
$tamanoF = trim($_GET['tamano'] ?? '');
$edadMin = ($_GET['edad_min'] ?? '') !== '' ? (int)$_GET['edad_min'] : null;
$edadMax = ($_GET['edad_max'] ?? '') !== '' ? (int)$_GET['edad_max'] : null;

/* Opciones únicas */
$razasUnicas   = [];
$tamanosUnicos = [];

foreach ($mascotas as $m) {
    if (!empty($m['raza']))   $razasUnicas[$m['raza']] = true;
    if (!empty($m['tamano'])) $tamanosUnicos[$m['tamano']] = true;
}
$razasUnicas   = array_keys($razasUnicas);
sort($razasUnicas);
$tamanosUnicos = array_keys($tamanosUnicos);
sort($tamanosUnicos);

/* Aplicar filtros */
$mascotasFiltradas = array_values(array_filter(
    $mascotas,
    function ($m) use ($q, $raza, $tamanoF, $edadMin, $edadMax) {
        $ok = true;

        if ($q !== '') {
            $txt = strtolower(($m['nombre'] ?? '') . ' ' . ($m['raza'] ?? '') . ' ' . ($m['tamano'] ?? ''));
            $ok = $ok && str_contains($txt, strtolower($q));
        }
        if ($raza !== '')    $ok = $ok && (($m['raza'] ?? '') === $raza);
        if ($tamanoF !== '') $ok = $ok && (($m['tamano'] ?? '') === $tamanoF);

        $edadMeses = (int)($m['edad_meses'] ?? ($m['edad'] ?? 0));
        if ($edadMin !== null) $ok = $ok && $edadMeses >= $edadMin;
        if ($edadMax !== null) $ok = $ok && $edadMeses <= $edadMax;

        return $ok;
    }
));
$resultCount = count($mascotasFiltradas);

/* URLs de navegación */
$urlLista    = $baseFeatures . '/MisMascotas.php?vista=lista';
$urlTarjetas = $baseFeatures . '/MisMascotas.php?vista=tarjetas';
$urlNueva    = $baseFeatures . '/MisMascotas.php?vista=nueva';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Mascotas - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS global -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            background: var(--gris-fondo, #f4f6f9);
        }

        main.main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }

        @media (max-width: 768px) {
            main.main-content {
                margin-left: 0;
                padding: 16px;
            }
        }

        .mascota-card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .badge-raz {
            background: #e7f3ef;
            color: #3c6255;
            font-weight: 500;
        }

        #btnTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #3c6255, #20c997);
            color: #fff;
            display: none;
            cursor: pointer;
            z-index: 1000;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Botón hamburguesa mobile -->
    <button class="btn btn-outline-secondary d-md-none ms-2 mt-3" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <div class="container-fluid py-2">

            <!-- Header -->
            <div class="header-box header-mascotas mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-paw me-2"></i>Mis Mascotas
                    </h1>
                    <p class="mb-0">
                        Gestioná, agregá y seleccioná tus mascotas desde una sola vista, <?= $usuarioNombre ?>.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $urlLista ?>" class="btn btn-outline-light fw-semibold <?= $vista === 'lista' ? 'active' : '' ?>">
                        <i class="fas fa-table me-1"></i> Lista
                    </a>
                    <a href="<?= $urlTarjetas ?>" class="btn btn-outline-light fw-semibold <?= $vista === 'tarjetas' ? 'active' : '' ?>">
                        <i class="fas fa-th-large me-1"></i> Tarjetas
                    </a>
                    <a href="<?= $urlNueva ?>" class="btn btn-light text-success fw-semibold <?= $vista === 'nueva' ? 'active' : '' ?>">
                        <i class="fas fa-plus me-1"></i> Nueva
                    </a>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- =============================
                    VISTA AGREGAR MASCOTA
            ============================== -->
            <?php if ($vista === 'nueva'): ?>

                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-info-circle me-2"></i>Agregar Mascota
                    </div>
                    <div class="section-body">

                        <form method="POST" novalidate>
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" name="nombre" required value="<?= h($_POST['nombre'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Raza</label>
                                    <select class="form-select" id="raza" name="raza">
                                        <option value="">Seleccione una raza</option>
                                        <?php foreach ($razasDisponibles as $r): ?>
                                            <option value="<?= h($r) ?>" <?= (($_POST['raza'] ?? '') === $r ? 'selected' : '') ?>>
                                                <?= h($r) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="Otra" <?= (($_POST['raza'] ?? '') === 'Otra' ? 'selected' : '') ?>>Otra</option>
                                    </select>

                                    <input type="text"
                                        class="form-control mt-2 d-none"
                                        id="raza_otra"
                                        name="raza_otra"
                                        placeholder="Especifique la raza"
                                        value="<?= h($_POST['raza_otra'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Peso (kg) *</label>
                                    <input type="number" step="0.1" min="0" id="peso_kg" name="peso_kg" class="form-control" required value="<?= h($pesoPost) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tamaño</label>
                                    <div class="btn-group w-100 flex-wrap">
                                        <input type="radio" class="btn-check" name="tamano" id="tam_peq" value="pequeno" <?= $tamanoPost === 'pequeno' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success" for="tam_peq">Pequeño</label>

                                        <input type="radio" class="btn-check" name="tamano" id="tam_med" value="mediano" <?= $tamanoPost === 'mediano' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-warning" for="tam_med">Mediano</label>

                                        <input type="radio" class="btn-check" name="tamano" id="tam_gra" value="grande" <?= $tamanoPost === 'grande' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-danger" for="tam_gra">Grande</label>

                                        <input type="radio" class="btn-check" name="tamano" id="tam_gig" value="gigante" <?= $tamanoPost === 'gigante' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-dark" for="tam_gig">Gigante</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Edad</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="edad_valor" value="<?= h($edadValorPost) ?>">
                                        <select name="edad_unidad" class="form-select">
                                            <option value="meses" <?= $edadUnidadPost === 'meses' ? 'selected' : '' ?>>Meses</option>
                                            <option value="anios" <?= $edadUnidadPost === 'anios' ? 'selected' : '' ?>>Años</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" rows="4"><?= h($_POST['observaciones'] ?? '') ?></textarea>
                                </div>

                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="<?= $urlLista ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Volver
                                </a>
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="fas fa-save me-1"></i> Guardar Mascota
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

            <?php elseif ($vista === 'tarjetas'): ?>

                <!-- =============================
                        VISTA TARJETAS
                ============================== -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-th-large me-2"></i>Mis Mascotas (vista tarjetas)
                    </div>
                    <div class="section-body">

                        <?php if ($resultCount === 0): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-dog fa-3x mb-2"></i>
                                <p class="mb-3">No tenés mascotas registradas.</p>
                                <a href="<?= $urlNueva ?>" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i> Agregar
                                </a>
                            </div>
                        <?php else: ?>

                            <div class="row g-4">
                                <?php foreach ($mascotasFiltradas as $m):
                                    $id        = (int)$m['mascota_id'];
                                    $nom       = h($m['nombre']);
                                    $raz       = $m['raza'] ?? null;
                                    $tam       = $m['tamano'] ?? null;
                                    $foto      = $m['foto_url'] ?? null;
                                    $edadMeses = (int)($m['edad_meses'] ?? 0);
                                ?>
                                    <div class="col-sm-6 col-lg-4 col-xl-3">
                                        <div class="card shadow-sm h-100">

                                            <?php if ($foto): ?>
                                                <img src="<?= h($foto) ?>" class="mascota-card-img">
                                            <?php else: ?>
                                                <div class="mascota-card-img bg-light d-flex justify-content-center align-items-center">
                                                    <i class="fas fa-dog fa-2x text-secondary"></i>
                                                </div>
                                            <?php endif; ?>

                                            <div class="card-body">
                                                <h5 class="card-title mb-1"><?= $nom ?></h5>

                                                <div class="mb-2">
                                                    <?php if ($raz): ?>
                                                        <span class="badge badge-raz me-1"><?= h($raz) ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($tam): ?>
                                                        <span class="badge bg-light text-dark"><?= etiquetaTamano($tam) ?></span>
                                                    <?php endif; ?>
                                                </div>

                                                <p class="card-text text-muted small mb-3">
                                                    Edad: <?= edadAmigable($edadMeses) ?>
                                                </p>

                                                <div class="d-grid gap-2">
                                                    <a href="<?= $baseFeatures ?>/PerfilMascota.php?id=<?= $id ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-id-card me-1"></i> Perfil
                                                    </a>
                                                    <a href="<?= $baseFeatures ?>/EditarMascota.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
                                                        <i class="fas fa-pen-to-square me-1"></i> Editar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>

            <?php else: ?>

                <!-- =============================
                        VISTA LISTA (TABLA)
                ============================== -->

                <div class="section-card mb-3">
                    <div class="section-header">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </div>
                    <div class="section-body">

                        <form class="row g-3 align-items-end" method="get">
                            <input type="hidden" name="vista" value="lista">

                            <div class="col-md-4">
                                <label class="form-label">Buscar</label>
                                <input type="text" class="form-control" name="q" value="<?= h($q) ?>" placeholder="Nombre, raza o tamaño">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Raza</label>
                                <select class="form-select" name="raza">
                                    <option value="">Todas</option>
                                    <?php foreach ($razasUnicas as $r): ?>
                                        <option value="<?= h($r) ?>" <?= $raza === $r ? 'selected' : '' ?>><?= h($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Tamaño</label>
                                <select class="form-select" name="tamano">
                                    <option value="">Todos</option>
                                    <?php foreach ($tamanosUnicos as $t): ?>
                                        <option value="<?= h($t) ?>" <?= $tamanoF === $t ? 'selected' : '' ?>><?= etiquetaTamano($t) ?></option>
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
                                <button class="btn btn-success">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                                <a href="<?= $urlLista ?>" class="btn btn-outline-secondary">Limpiar</a>
                                <span class="ms-auto badge bg-success"><?= $resultCount ?> resultado(s)</span>
                            </div>
                        </form>

                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-list me-2"></i>Listado de Mascotas
                    </div>
                    <div class="section-body">

                        <?php if ($resultCount === 0): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-dog fa-2x mb-2"></i>
                                <p>No se encontraron mascotas.</p>
                                <a href="<?= $urlNueva ?>" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i> Agregar Mascota
                                </a>
                            </div>
                        <?php else: ?>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-success">
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
                                            $id   = (int)$m['mascota_id'];
                                            $foto = $m['foto_url'];
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php if ($foto): ?>
                                                        <img src="<?= h($foto) ?>" class="rounded" style="width:48px;height:48px;object-fit:cover;">
                                                    <?php else: ?>
                                                        <div class="rounded bg-light d-flex justify-content-center align-items-center" style="width:48px;height:48px;">
                                                            <i class="fas fa-paw text-secondary"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>

                                                <td><strong><?= h($m['nombre']) ?></strong></td>
                                                <td><?= h($m['raza'] ?? '-') ?></td>
                                                <td><?= etiquetaTamano($m['tamano'] ?? null) ?></td>
                                                <td><?= edadAmigable($m['edad_meses'] ?? null) ?></td>

                                                <td class="text-end">
                                                    <a href="<?= $baseFeatures ?>/PerfilMascota.php?id=<?= $id ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $baseFeatures ?>/EditarMascota.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= $baseFeatures ?>/EliminarMascota.php?id=<?= $id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar mascota?')">
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

            <?php endif; ?>

            <footer class="mt-4 text-center text-muted small">
                © <?= date('Y') ?> Jaguata — Panel del Dueño
            </footer>
        </div>
    </main>

    <!-- Botón volver arriba -->
    <button id="btnTop"><i class="fas fa-arrow-up"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar responsive
        document.getElementById('toggleSidebar')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('sidebar-open');
        });

        // Raza "Otra"
        const selRaza = document.getElementById('raza');
        const razaOtra = document.getElementById('raza_otra');
        if (selRaza && razaOtra) {
            function toggleRazaOtra() {
                razaOtra.classList.toggle('d-none', selRaza.value !== 'Otra');
            }
            selRaza.addEventListener('change', toggleRazaOtra);
            toggleRazaOtra();
        }

        // Autoset tamaño según peso
        const peso = document.getElementById('peso_kg');
        if (peso) {
            const radios = {
                pequeno: document.getElementById('tam_peq'),
                mediano: document.getElementById('tam_med'),
                grande: document.getElementById('tam_gra'),
                gigante: document.getElementById('tam_gig')
            };
            peso.addEventListener('input', () => {
                const p = parseFloat(peso.value || '0');
                if (p <= 7) radios.pequeno.checked = true;
                else if (p <= 18) radios.mediano.checked = true;
                else if (p <= 35) radios.grande.checked = true;
                else radios.gigante.checked = true;
            });
        }

        // Botón volver arriba
        const btnTop = document.getElementById('btnTop');
        window.addEventListener('scroll', () => {
            btnTop.style.display = window.scrollY > 200 ? 'block' : 'none';
        });
        btnTop.addEventListener('click', () => window.scrollTo({
            top: 0,
            behavior: 'smooth'
        }));
    </script>

</body>

</html>