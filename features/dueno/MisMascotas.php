<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;

AppConfig::init();

// Auth
$auth = new AuthController();
$auth->checkRole('dueno');

// Datos
$controller = new MascotaController();
$mascotas   = $controller->index();

// -------- Helpers --------
/** Muestra edad (guardada en MESES) de forma amigable. */
function edadAmigable($meses): string
{
    if ($meses === null || $meses === '') return '—';
    $m = (int)$meses;
    if ($m < 12) return $m . ' mes' . ($m === 1 ? '' : 'es');
    $anios = intdiv($m, 12);
    $resto = $m % 12;
    return $resto ? "{$anios} a {$resto} m" : "{$anios} año" . ($anios === 1 ? '' : 's');
}

// ---- Filtros (GET) ----
$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$raza    = isset($_GET['raza']) ? trim($_GET['raza']) : '';
$tamano  = isset($_GET['tamano']) ? trim($_GET['tamano']) : '';
// IMPORTANTE: Edad en filtros se interpreta en MESES
$edadMin = (isset($_GET['edad_min']) && $_GET['edad_min'] !== '') ? (int)$_GET['edad_min'] : null;
$edadMax = (isset($_GET['edad_max']) && $_GET['edad_max'] !== '') ? (int)$_GET['edad_max'] : null;

// Opciones únicas para selects (derivadas de los datos)
$razasUnicas   = [];
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
$tamanosUnicos = array_keys($tamanosUnicos);
sort($razasUnicas);
sort($tamanosUnicos);

// Aplicar filtros en memoria
$mascotasFiltradas = array_values(array_filter($mascotas, function ($m) use ($q, $raza, $tamano, $edadMin, $edadMax) {
    $ok = true;

    // Búsqueda libre
    if ($q !== '') {
        $nombre = strtolower((string)($m['nombre'] ?? ''));
        $rza    = strtolower((string)($m['raza'] ?? ''));
        $tm     = strtolower((string)($m['tamano'] ?? ''));
        $txt    = $nombre . ' ' . $rza . ' ' . $tm;
        $ok     = $ok && (strpos($txt, strtolower($q)) !== false);
    }

    // Raza exacta
    if ($raza !== '') {
        $ok = $ok && (isset($m['raza']) && $m['raza'] === $raza);
    }

    // Tamaño exacto
    if ($tamano !== '') {
        $ok = $ok && (isset($m['tamano']) && $m['tamano'] === $tamano);
    }

    // Edad en MESES (entre rangos si están definidos)
    if ($edadMin !== null) {
        $ok = $ok && (isset($m['edad']) && (int)$m['edad'] >= $edadMin);
    }
    if ($edadMax !== null) {
        $ok = $ok && (isset($m['edad']) && (int)$m['edad'] <= $edadMax);
    }

    return $ok;
}));

$resultCount = count($mascotasFiltradas);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Mascotas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>

<body class="has-fixed-navbar">
    <div class="container-fluid">
        <div class="row">

            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column gap-1">
                        <!-- Mascotas -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuMascotas" aria-expanded="true">
                                <i class="fas fa-paw me-2"></i>
                                <span class="flex-grow-1">Mascotas</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse show ps-4 nav flex-column" id="menuMascotas">
                                <li class="nav-item">
                                    <a class="nav-link active" href="MisMascotas.php">
                                        <i class="fas fa-list-ul me-2"></i> Mis Mascotas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="AgregarMascota.php">
                                        <i class="fas fa-plus-circle me-2"></i> Agregar Mascota
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
                                <li class="nav-item"><a class="nav-link" href="MetodosPago.php"><i class="fas fa-wallet me-2"></i> Método de Pago</a></li>
                                <li class="nav-item"><a class="nav-link" href="GastosTotales.php"><i class="fas fa-chart-line me-2"></i> Gastos Totales</a></li>
                            </ul>
                        </li>

                        <!-- Notificaciones -->
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" href="Notificaciones.php">
                                <i class="fas fa-bell me-2"></i> <span>Notificaciones</span>
                            </a>
                        </li>

                        <!-- Mi Perfil -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuPerfil" aria-expanded="false">
                                <i class="fas fa-user me-2"></i>
                                <span class="flex-grow-1">Mi Perfil</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuPerfil">
                                <li class="nav-item"><a class="nav-link" href="MisPuntos.php"><i class="fas fa-star me-2"></i> Mis Puntos</a></li>
                                <li class="nav-item"><a class="nav-link" href="Perfil.php"><i class="fas fa-id-card me-2"></i> Configuración</a></li>
                                <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido -->
            <main class="col-12 col-md-9 col-lg-10 main-content">
                <!-- Encabezado + acciones -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h1 class="page-title mb-0">Mis Mascotas</h1>
                    <div class="d-flex align-items-center gap-2">
                        <a href="AgregarMascota.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva Mascota
                        </a>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success'];
                                                        unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'];
                                                    unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form class="row g-3 align-items-end" method="get" action="">
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="q">Buscar</label>
                                <input type="text" class="form-control" id="q" name="q"
                                    placeholder="Nombre, raza o tamaño"
                                    value="<?= htmlspecialchars($q) ?>">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label" for="raza">Raza</label>
                                <select class="form-select" id="raza" name="raza">
                                    <option value="">Todas</option>
                                    <?php foreach ($razasUnicas as $r): ?>
                                        <option value="<?= htmlspecialchars($r) ?>" <?= $raza === $r ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label" for="tamano">Tamaño</label>
                                <select class="form-select" id="tamano" name="tamano">
                                    <option value="">Todos</option>
                                    <?php foreach ($tamanosUnicos as $t): ?>
                                        <option value="<?= htmlspecialchars($t) ?>" <?= $tamano === $t ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label" for="edad_min">Edad (meses) ≥</label>
                                <input type="number" min="0" class="form-control" id="edad_min" name="edad_min"
                                    value="<?= $edadMin !== null ? (int)$edadMin : '' ?>">
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label" for="edad_max">Edad (meses) ≤</label>
                                <input type="number" min="0" class="form-control" id="edad_max" name="edad_max"
                                    value="<?= $edadMax !== null ? (int)$edadMax : '' ?>">
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                                <a href="MisMascotas.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-eraser me-1"></i> Limpiar
                                </a>
                                <span class="ms-auto badge bg-primary align-self-center">
                                    <?= $resultCount ?> resultado<?= $resultCount === 1 ? '' : 's' ?>
                                </span>
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
                                <p class="mb-0">No se encontraron mascotas con los filtros actuales.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Raza</th>
                                            <th>Tamaño</th>
                                            <th>Edad</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mascotasFiltradas as $m): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($m['nombre'] ?? '') ?></td>
                                                <td>
                                                    <?php if (!empty($m['raza'])): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                                            <?= htmlspecialchars($m['raza']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($m['tamano'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars(edadAmigable($m['edad'] ?? null)) ?></td>
                                                <td class="text-end">
                                                    <a href="EditarMascota.php?id=<?= (int)($m['mascota_id'] ?? 0) ?>"
                                                        class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                    <a href="EliminarMascota.php?id=<?= (int)($m['mascota_id'] ?? 0) ?>"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('¿Seguro que deseas eliminar esta mascota?')">
                                                        <i class="fas fa-trash-alt"></i> Eliminar
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

            </main>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>