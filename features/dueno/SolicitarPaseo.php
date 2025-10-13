<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;

// Inicializar aplicación
AppConfig::init();

// Verificar autenticación
$authController = new AuthController();
$authController->checkRole('dueno');

// Obtener controladores
$paseoController   = new PaseoController();
$mascotaController = new MascotaController();

// Obtener mascotas del dueño
$mascotas = $mascotaController->index();

// Verificar que tenga mascotas
if (empty($mascotas)) {
    $_SESSION['error'] = 'Debes tener al menos una mascota registrada para solicitar paseos';
    header('Location: AgregarMascota.php');
    exit;
}

// Inputs / preselecciones
$mascotaPreseleccionada  = (int)($_GET['mascota_id'] ?? 0);
$paseadorPreseleccionado = (int)($_GET['paseador_id'] ?? 0);
$fechaFiltro             = trim($_GET['fecha'] ?? ''); // opcional: filtrar paseadores disponibles por fecha

// Procesar formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paseoController->store();
}

// Obtener paseadores disponibles (opcionalmente por fecha)
$paseadorModel = new \Jaguata\Models\Paseador();
if ($fechaFiltro) {
    // Si tu modelo soporta filtrar por fecha:
    $paseadores = $paseadorModel->getDisponibles($fechaFiltro); // adapta el método si usa otro nombre
} else {
    $paseadores = $paseadorModel->getDisponibles();
}

// Mapear para acceso rápido por id
$paseadoresById = [];
foreach ($paseadores as $p) {
    $paseadoresById[(int)$p['paseador_id']] = $p;
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Paseo - Jaguata</title>
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
                    <ul class="nav flex-column gap-1">
                        <!-- Mi Perfil -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuPerfil" aria-expanded="false">
                                <i class="fas fa-user me-2"></i>
                                <span class="flex-grow-1">Mi Perfil</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuPerfil">
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php">
                                        <i class="fas fa-id-card me-2"></i> Ver Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">
                                        <i class="fas fa-user-edit me-2 text-warning"></i> Editar Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php">
                                        <i class="fas fa-coins me-2 text-success"></i> Gastos Totales
                                    </a>
                                </li>
                            </ul>
                        </li>




                        <!-- Mascotas -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuMascotas" aria-expanded="false">
                                <i class="fas fa-paw me-2"></i>
                                <span class="flex-grow-1">Mascotas</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuMascotas">
                                <li class="nav-item">
                                    <a class="nav-link" href="MisMascotas.php">
                                        <i class="fas fa-list-ul me-2"></i> Mis Mascotas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="AgregarMascota.php">
                                        <i class="fas fa-plus-circle me-2"></i> Agregar Mascota
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $firstMascotaId ? '' : 'disabled' ?>"
                                        href="<?= $firstMascotaId ? 'PerfilMascota.php?id=' . (int)$firstMascotaId : '#' ?>">
                                        <i class="fas fa-id-badge me-2"></i> Perfil de mi Mascota
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
                                <li class="nav-item">
                                    <!-- Enviar a Pendientes (allí hay botón Pagar con paseo_id) -->
                                    <a class="nav-link" href="PaseosPendientes.php">
                                        <i class="fas fa-wallet me-2"></i> Pagar paseo
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Notificaciones -->
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" href="Notificaciones.php">
                                <i class="fas fa-bell me-2"></i>
                                <span>Notificaciones</span>
                            </a>
                        </li>

                        <!-- Configuración (solo Editar Perfil y Cerrar Sesión) -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center w-100 text-start"
                                data-bs-toggle="collapse" data-bs-target="#menuConfig" aria-expanded="false">
                                <i class="fas fa-gear me-2"></i>
                                <span class="flex-grow-1">Configuración</span>
                                <i class="fas fa-chevron-right ms-2 chevron"></i>
                            </button>
                            <ul class="collapse ps-4 nav flex-column" id="menuConfig">
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $baseFeatures; ?>/EditarPerfil.php">
                                        <i class="fas fa-user-cog me-2"></i> Editar Perfil
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </li>

                    </ul>
                </div>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Solicitar Paseo</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="MisPaseos.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <li><?php echo h($error); ?></li>
                            <?php endforeach;
                            unset($_SESSION['errors']); ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tip: Atajo para prefiltrar por fecha antes de elegir paseador -->
                <div class="alert alert-info d-flex align-items-start gap-2">
                    <i class="fas fa-lightbulb mt-1"></i>
                    <div>
                        ¿Querés ver disponibilidad por día antes de elegir? Usá <a class="alert-link" href="BuscarPaseadores.php">Buscar Paseadores</a> y filtrá por <strong>fecha disponible</strong>. Podés volver acá con el paseador y fecha ya seleccionados.
                    </div>
                </div>

                <!-- Formulario -->
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="card shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><i class="fas fa-walking text-primary me-2"></i>Información del Paseo</h5>
                                <a class="btn btn-sm btn-outline-primary" href="BuscarPaseadores.php"><i class="fas fa-search me-1"></i> Buscar paseadores</a>
                            </div>
                            <div class="card-body">
                                <form method="POST" data-validate>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="mascota_id" class="form-label">Mascota <span class="text-danger">*</span></label>
                                            <select class="form-select" id="mascota_id" name="mascota_id" required>
                                                <option value="">Seleccionar mascota</option>
                                                <?php foreach ($mascotas as $mascota): ?>
                                                    <option value="<?php echo (int)$mascota['mascota_id']; ?>" <?php echo ((int)$mascota['mascota_id'] === $mascotaPreseleccionada ? 'selected' : ''); ?>>
                                                        <?php echo h($mascota['nombre']); ?> (<?php echo ucfirst(h($mascota['tamano'])); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Seleccioná la mascota que querés pasear</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="paseador_id" class="form-label">Paseador <span class="text-danger">*</span></label>
                                            <select class="form-select" id="paseador_id" name="paseador_id" required>
                                                <option value="">Seleccionar paseador</option>
                                                <?php foreach ($paseadores as $paseador): ?>
                                                    <option value="<?php echo (int)$paseador['paseador_id']; ?>"
                                                        data-precio="<?php echo (float)$paseador['precio_hora']; ?>"
                                                        <?php echo ((int)$paseador['paseador_id'] === $paseadorPreseleccionado ? 'selected' : ''); ?>>
                                                        <?php echo h($paseador['nombre']); ?> - ₲<?php echo number_format($paseador['precio_hora'], 0, ',', '.'); ?>/hora (⭐ <?php echo (float)$paseador['calificacion']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Seleccioná un paseador disponible</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="inicio" class="form-label">Fecha y hora <span class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control" id="inicio" name="inicio" value="<?php echo h($_POST['inicio'] ?? ''); ?>" required>
                                            <div class="form-text">Mínimo 2 horas desde ahora</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="duracion" class="form-label">Duración <span class="text-danger">*</span></label>
                                            <select class="form-select" id="duracion" name="duracion" required>
                                                <option value="">Seleccionar duración</option>
                                                <?php
                                                $durOptions = [15 => '15 minutos', 30 => '30 minutos', 45 => '45 minutos', 60 => '1 hora', 90 => '1.5 horas', 120 => '2 horas'];
                                                $durSel = (int)($_POST['duracion'] ?? 0);
                                                foreach ($durOptions as $min => $label):
                                                ?>
                                                    <option value="<?php echo $min; ?>" <?php echo ($durSel === $min ? 'selected' : ''); ?>><?php echo $label; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Duración del paseo</div>
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-6">
                                            <div class="small text-color #ffff">Tarifa por hora</div>
                                            <div class="fs-5" id="tarifaHora">—</div>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <div class="small text-color #ffff">TOTAL:</div>
                                            <div class="fs-4 fw-semibold" id="totalEstimado">—</div>
                                            <div class="small text-color #ffff">El total final puede variar si el paseador ajusta la tarifa o la duración cambia.</div>
                                        </div>
                                    </div>

                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Importante:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>El paseador confirmará la solicitud antes del paseo.</li>
                                            <li>El pago se procesará una vez que el paseo sea <strong>confirmado</strong>.</li>
                                            <li>Podés cancelar el paseo hasta 1 hora antes del inicio.</li>
                                        </ul>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="MisPaseos.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Cancelar</a>
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Solicitar Paseo</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // +2 horas desde "ahora" para el mínimo
            const now = new Date();
            now.setHours(now.getHours() + 2);
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hour = String(now.getHours()).padStart(2, '0');
            const minute = String(now.getMinutes()).padStart(2, '0');
            const minDateTime = `${year}-${month}-${day}T${hour}:${minute}`;

            const inputInicio = document.getElementById('inicio');
            inputInicio.min = minDateTime;
            if (!inputInicio.value) inputInicio.value = minDateTime;

            // Cálculo de tarifa y total estimado
            const selectPaseador = document.getElementById('paseador_id');
            const selectDuracion = document.getElementById('duracion');
            const tarifaHora = document.getElementById('tarifaHora');
            const totalEstimado = document.getElementById('totalEstimado');

            function formatPYG(n) {
                try {
                    return new Intl.NumberFormat('es-PY').format(Math.round(n));
                } catch (e) {
                    return n;
                }
            }

            function updateTotales() {
                const opt = selectPaseador.options[selectPaseador.selectedIndex];
                const precioHora = opt && opt.dataset && opt.dataset.precio ? parseFloat(opt.dataset.precio) : 0;
                const minutos = parseInt(selectDuracion.value || '0', 10);
                const horas = minutos / 60.0;

                tarifaHora.textContent = precioHora > 0 ? `₲ ${formatPYG(precioHora)} / hora` : '—';
                const total = (precioHora > 0 && horas > 0) ? (precioHora * horas) : 0;
                totalEstimado.textContent = (total > 0) ? `₲ ${formatPYG(total)}` : '—';
            }

            selectPaseador.addEventListener('change', updateTotales);
            selectDuracion.addEventListener('change', updateTotales);
            updateTotales(); // inicial
        });
    </script>
</body>

</html>