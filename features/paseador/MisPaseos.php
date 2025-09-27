/features/paseador/MisPaseos.php
<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;

// Inicializar aplicación
AppConfig::init();

// Verificar autenticación (rol paseador)
$authController = new AuthController();
$authController->checkRole('paseador');

// Obtener controlador
$paseoController = new PaseoController();

// Obtener paseos asignados al paseador actual
$paseos = $paseoController->indexForPaseador($_SESSION['usuario_id']);

// Filtrar por estado si se especifica
$estadoFiltro = $_GET['estado'] ?? '';
if ($estadoFiltro) {
    $paseos = array_filter($paseos, function ($paseo) use ($estadoFiltro) {
        return $paseo['estado'] === $estadoFiltro;
    });
}

// Agrupar paseos por estado
$paseosPorEstado = [
    'Pendiente' => array_filter($paseos, fn($p) => $p['estado'] === 'Pendiente'),
    'confirmado' => array_filter($paseos, fn($p) => $p['estado'] === 'confirmado'),
    'en_curso'   => array_filter($paseos, fn($p) => $p['estado'] === 'en_curso'),
    'completo'   => array_filter($paseos, fn($p) => $p['estado'] === 'completo'),
    'cancelado'  => array_filter($paseos, fn($p) => $p['estado'] === 'cancelado')
];

// Estadísticas
$totalPaseos = count($paseos);
$paseosPendientes = count($paseosPorEstado['Pendiente']) + count($paseosPorEstado['confirmado']);
$paseosCompletados = count($paseosPorEstado['completo']);
$paseosCancelados = count($paseosPorEstado['cancelado']);

// Calcular ingresos totales
$ingresosTotales = 0;
foreach ($paseosPorEstado['completo'] as $paseo) {
    $ingresosTotales += $paseo['precio_total'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Paseos - Paseador</title>
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
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="Dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="MisPaseos.php">
                                <i class="fas fa-walking me-2"></i>
                                Mis Paseos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="Perfil.php">
                                <i class="fas fa-user me-2"></i>
                                Mi Perfil
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mis Paseos Asignados</h1>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Paseos</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $totalPaseos; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendientes</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $paseosPendientes; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completados</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $paseosCompletados; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Ingresos Totales</div>
                                <div class="h5 mb-0 font-weight-bold">₲<?php echo number_format($ingresosTotales, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" onchange="filtrarPorEstado()">
                                    <option value="">Todos los estados</option>
                                    <option value="Pendiente" <?php echo $estadoFiltro === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="confirmado" <?php echo $estadoFiltro === 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                                    <option value="en_curso" <?php echo $estadoFiltro === 'en_curso' ? 'selected' : ''; ?>>En Curso</option>
                                    <option value="completo" <?php echo $estadoFiltro === 'completo' ? 'selected' : ''; ?>>Completo</option>
                                    <option value="cancelado" <?php echo $estadoFiltro === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de paseos -->
                <?php if (empty($paseos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-walking fa-5x text-gray-300 mb-4"></i>
                        <h3 class="text-muted">No tienes paseos asignados</h3>
                        <p class="text-muted mb-4">Espera a que un dueño te asigne uno</p>
                    </div>
                <?php else: ?>
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Lista de Paseos</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
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
                                        <?php foreach ($paseos as $paseo): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($paseo['nombre_mascota']); ?></td>
                                                <td><?php echo htmlspecialchars($paseo['nombre_dueno']); ?></td>
                                                <td>
                                                    <strong><?php echo date('d/m/Y', strtotime($paseo['inicio'])); ?></strong><br>
                                                    <small><?php echo date('H:i', strtotime($paseo['inicio'])); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($paseo['duracion']); ?> min</td>
                                                <td>
                                                    <span class="badge bg-<?php
                                                                            echo $paseo['estado'] === 'completo' ? 'success' : (
                                                                                $paseo['estado'] === 'cancelado' ? 'danger' : (
                                                                                    $paseo['estado'] === 'en_curso' ? 'info' : 'warning'));
                                                                            ?>">
                                                        <?php echo ucfirst($paseo['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($paseo['estado_pago'] === 'pendiente'): ?>
                                                        <span class="text-warning">Pendiente</span>
                                                    <?php elseif ($paseo['estado_pago'] === 'procesado'): ?>
                                                        <span class="text-success">Pagado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="VerPaseo.php?id=<?php echo $paseo['paseo_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($paseo['estado'] === 'Pendiente'): ?>
                                                            <a href="AceptarPaseo.php?id=<?php echo $paseo['paseo_id']; ?>" class="btn btn-sm btn-outline-success">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php elseif ($paseo['estado'] === 'confirmado'): ?>
                                                            <a href="IniciarPaseo.php?id=<?php echo $paseo['paseo_id']; ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-play"></i>
                                                            </a>
                                                        <?php elseif ($paseo['estado'] === 'en_curso'): ?>
                                                            <a href="CompletarPaseo.php?id=<?php echo $paseo['paseo_id']; ?>" class="btn btn-sm btn-outline-success">
                                                                <i class="fas fa-flag-checkered"></i>
                                                            </a>
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
            if (estado) {
                url.searchParams.set('estado', estado);
            } else {
                url.searchParams.delete('estado');
            }
            window.location.href = url.toString();
        }
    </script>
</body>

</html>