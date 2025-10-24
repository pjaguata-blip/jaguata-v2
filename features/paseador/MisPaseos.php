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

// Filtro por estado
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
$ingresosTotales   = array_reduce($by('completo'), fn($a, $p) => $a + (float)($p['precio_total'] ?? 0), 0);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Paseos - Paseador | Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
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
            transition: background 0.2s, transform 0.2s;
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
            padding: 2rem;
            background-color: #f5f7fa;
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

        .page-header h2 {
            margin: 0;
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
        }

        .card-header {
            background: linear-gradient(90deg, #3c6255, #20c997);
            color: #fff;
            font-weight: 600;
        }

        .btn-outline-primary,
        .btn-outline-success,
        .btn-outline-danger,
        .btn-outline-info {
            border-width: 1.5px;
        }

        .badge.bg-success {
            background-color: #3c6255 !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-home me-2"></i>Inicio</a></li>
                        <li><a class="nav-link active" href="MisPaseos.php"><i class="fas fa-walking me-2"></i>Mis Paseos</a></li>
                        <li><a class="nav-link" href="Disponibilidad.php"><i class="fas fa-calendar-check me-2"></i>Disponibilidad</a></li>
                        <li><a class="nav-link" href="Perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                        <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Salir</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contenido -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header">
                    <h2><i class="fas fa-walking me-2"></i> Mis Paseos Asignados</h2>
                    <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                <!-- Resumen -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase">Total Paseos</h6>
                                <h3 class="fw-bold"><?= $totalPaseos ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase">Pendientes</h6>
                                <h3 class="fw-bold text-warning"><?= $paseosPendientes ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase">Completados</h6>
                                <h3 class="fw-bold text-success"><?= $paseosCompletados ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase">Ingresos Totales</h6>
                                <h3 class="fw-bold text-primary">₲<?= number_format($ingresosTotales, 0, ',', '.') ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-filter me-2"></i> Filtros</div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" onchange="filtrarPorEstado()">
                                    <option value="">Todos los estados</option>
                                    <?php foreach ($estadosValidos as $v): ?>
                                        <option value="<?= $v ?>" <?= $estadoFiltro === $v ? 'selected' : '' ?>>
                                            <?= ucfirst(str_replace('_', ' ', $v)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista -->
                <?php if (empty($paseos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-dog fa-5x text-secondary mb-4"></i>
                        <h4 class="text-muted">No tienes paseos asignados por el momento</h4>
                    </div>
                <?php else: ?>
                    <div class="card shadow">
                        <div class="card-header"><i class="fas fa-list me-2"></i> Lista de Paseos</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
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
                                        <?php foreach ($paseos as $p):
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
                                                <td><?= h($p['nombre_dueno'] ?? '') ?></td>
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
                                                    <div class="btn-group" role="group">
                                                        <a href="VerPaseo.php?id=<?= $paseoId ?>" class="btn btn-sm btn-outline-primary" title="Ver"><i class="fas fa-eye"></i></a>
                                                        <?php if ($estado === 'pendiente'): ?>
                                                            <form action="AccionPaseo.php" method="post" class="d-inline">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="confirmar">
                                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('¿Confirmar este paseo?');" title="Confirmar"><i class="fas fa-check"></i></button>
                                                            </form>
                                                            <form action="AccionPaseo.php" method="post" class="d-inline">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="cancelar">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Cancelar este paseo?');" title="Cancelar"><i class="fas fa-times"></i></button>
                                                            </form>
                                                        <?php elseif ($estado === 'confirmado'): ?>
                                                            <form action="AccionPaseo.php" method="post" class="d-inline">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="iniciar">
                                                                <button type="submit" class="btn btn-sm btn-outline-info" onclick="return confirm('¿Iniciar este paseo?');" title="Iniciar"><i class="fas fa-play"></i></button>
                                                            </form>
                                                            <form action="AccionPaseo.php" method="post" class="d-inline">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="cancelar">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Cancelar este paseo?');" title="Cancelar"><i class="fas fa-times"></i></button>
                                                            </form>
                                                        <?php elseif ($estado === 'en_curso'): ?>
                                                            <form action="AccionPaseo.php" method="post" class="d-inline">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="completar">
                                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('¿Marcar como completado?');" title="Completar"><i class="fas fa-flag-checkered"></i></button>
                                                            </form>
                                                            <form action="AccionPaseo.php" method="post" class="d-inline">
                                                                <input type="hidden" name="id" value="<?= $paseoId ?>">
                                                                <input type="hidden" name="accion" value="cancelar">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Cancelar este paseo?');" title="Cancelar"><i class="fas fa-times"></i></button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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