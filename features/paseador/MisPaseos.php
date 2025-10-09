<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

// Init + auth
AppConfig::init();
$auth = new AuthController();
$auth->checkRole('paseador');

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Controlador
$paseoController = new PaseoController();

// Paseador en sesión
$paseadorId = (int)(Session::get('usuario_id') ?? 0);

// Paseos del paseador
$paseos = $paseoController->indexForPaseador($paseadorId);

// Filtro por estado (valores válidos)
$estadosValidos = ['pendiente', 'confirmado', 'en_curso', 'completo', 'cancelado'];
$estadoFiltro = strtolower(trim((string)($_GET['estado'] ?? '')));
if ($estadoFiltro !== '' && in_array($estadoFiltro, $estadosValidos, true)) {
    $paseos = array_values(array_filter($paseos, fn($p) => strtolower($p['estado']) === $estadoFiltro));
}

// Estadísticas
$by = fn($s) => array_filter($paseos, fn($p) => strtolower($p['estado']) === $s);
$totalPaseos       = count($paseos);
$paseosPendientes  = count($by('pendiente')) + count($by('confirmado'));
$paseosCompletados = count($by('completo'));
$paseosCancelados  = count($by('cancelado'));

// Ingresos (solo completos)
$ingresosTotales = 0;
foreach ($by('completo') as $p) $ingresosTotales += (float)($p['precio_total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Paseos - Paseador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
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
                        <li class="nav-item"><a class="nav-link" href="Dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active" href="MisPaseos.php"><i class="fas fa-walking me-2"></i>Mis Paseos</a></li>
                        <li class="nav-item"><a class="nav-link" href="Solicitudes.php"><i class="fas fa-envelope-open-text me-2"></i>Solicitudes</a></li>
                        <li class="nav-item"><a class="nav-link" href="Perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mis Paseos Asignados</h1>
                </div>

                <!-- Flash -->
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

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Paseos</div>
                                <div class="h5 mb-0 fw-bold"><?= $totalPaseos ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pendientes</div>
                                <div class="h5 mb-0 fw-bold"><?= $paseosPendientes ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Completados</div>
                                <div class="h5 mb-0 fw-bold"><?= $paseosCompletados ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">Ingresos Totales</div>
                                <div class="h5 mb-0 fw-bold">₲<?= number_format($ingresosTotales, 0, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">Filtros</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" onchange="filtrarPorEstado()">
                                    <option value="">Todos los estados</option>
                                    <?php
                                    foreach ($estadosValidos as $v) {
                                        $sel = ($estadoFiltro === $v) ? 'selected' : '';
                                        echo "<option value=\"{$v}\" {$sel}>" . ucfirst(str_replace('_', ' ', $v)) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista -->
                <?php if (empty($paseos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-walking fa-5x text-gray-300 mb-4"></i>
                        <h3 class="text-muted">No tienes paseos para mostrar</h3>
                    </div>
                <?php else: ?>
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-primary">Lista de Paseos</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th>Mascota</th>
                                            <th>Dueño</th>
                                            <th>Fecha</th>
                                            <th>Duración</th>
                                            <th>Estado</th>
                                            <th>Pago</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paseos as $p): ?>
                                            <?php
                                            $estado  = strtolower($p['estado']);
                                            $paseoId = (int)$p['paseo_id'];
                                            $badge = match ($estado) {
                                                'completo'   => 'success',
                                                'cancelado'  => 'danger',
                                                'en_curso'   => 'info',
                                                'confirmado' => 'primary',
                                                default      => 'warning'
                                            };
                                            ?>
                                            <tr>
                                                <td><?= h($p['nombre_mascota'] ?? '') ?></td>
                                                <td><?= h($p['nombre_dueno']   ?? '') ?></td>
                                                <td>
                                                    <strong><?= isset($p['inicio']) ? date('d/m/Y', strtotime($p['inicio'])) : '—' ?></strong><br>
                                                    <small><?= isset($p['inicio']) ? date('H:i', strtotime($p['inicio'])) : '—' ?></small>
                                                </td>
                                                <td><?= h($p['duracion'] ?? $p['duracion_min'] ?? '') ?> min</td>
                                                <td><span class="badge bg-<?= $badge ?>"><?= ucfirst(str_replace('_', ' ', $estado)) ?></span></td>
                                                <td>
                                                    <?php if (($p['estado_pago'] ?? '') === 'procesado'): ?>
                                                        <span class="text-success">Pagado</span>
                                                    <?php elseif (($p['estado_pago'] ?? '') === 'pendiente'): ?>
                                                        <span class="text-warning">Pendiente</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group" aria-label="Acciones">
                                                        <!-- Ver -->
                                                        <a href="VerPaseo.php?id=<?= $paseoId ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                                            <i class="fas fa-eye"></i>
                                                        </a>

                                                        <?php if ($estado === 'pendiente'): ?>
                                                            <!-- Confirmar -->
                                                            <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="confirmar">
                                                                <button type="submit" class="btn btn-sm btn-outline-success"
                                                                    onclick="return confirm('¿Confirmar este paseo?');" title="Confirmar">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                            <!-- Cancelar -->
                                                            <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="cancelar">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                    onclick="return confirm('¿Cancelar este paseo?');" title="Cancelar">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>

                                                        <?php elseif ($estado === 'confirmado'): ?>
                                                            <!-- Iniciar -->
                                                            <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="iniciar">
                                                                <button type="submit" class="btn btn-sm btn-outline-info"
                                                                    onclick="return confirm('¿Iniciar ahora este paseo?');" title="Iniciar">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            </form>
                                                            <!-- Cancelar -->
                                                            <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="cancelar">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                    onclick="return confirm('¿Cancelar este paseo?');" title="Cancelar">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>

                                                        <?php elseif ($estado === 'en_curso'): ?>
                                                            <!-- Completar -->
                                                            <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="completar">
                                                                <button type="submit" class="btn btn-sm btn-outline-success"
                                                                    onclick="return confirm('¿Marcar como completado?');" title="Completar">
                                                                    <i class="fas fa-flag-checkered"></i>
                                                                </button>
                                                            </form>
                                                            <!-- Cancelar -->
                                                            <form action="AccionPaseo.php" method="post" class="d-inline ms-1">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="cancelar">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                    onclick="return confirm('¿Cancelar este paseo en curso?');" title="Cancelar">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filtrarPorEstado() {
            const estado = document.getElementById('estado').value;
            const url = new URL(window.location);
            if (estado) url.searchParams.set('estado', estado);
            else url.searchParams.delete('estado');
            window.location.href = url.toString();
        }
    </script>
</body>

</html>