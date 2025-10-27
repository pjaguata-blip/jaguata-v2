<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Helpers\Session;

AppConfig::init();

$authController = new AuthController();
$authController->checkRole('dueno');

$paseoController   = new PaseoController();
$mascotaController = new MascotaController();
$mascotas = $mascotaController->index();

if (empty($mascotas)) {
    $_SESSION['error'] = 'Debes tener al menos una mascota registrada para solicitar paseos';
    header('Location: AgregarMascota.php');
    exit;
}

$mascotaPreseleccionada  = (int)($_GET['mascota_id'] ?? 0);
$paseadorPreseleccionado = (int)($_GET['paseador_id'] ?? 0);
$fechaFiltro             = trim($_GET['fecha'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paseoController->store();
}

$paseadorModel = new \Jaguata\Models\Paseador();
$paseadores = $fechaFiltro ? $paseadorModel->getDisponibles($fechaFiltro) : $paseadorModel->getDisponibles();

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
$usuarioNombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Dueño');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Paseo - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Fuente Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
        }

        /* Sidebar */
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
            transition: all 0.2s ease-in-out;
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

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        /* Header */
        .page-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 600;
            font-size: 1.6rem;
            letter-spacing: 0.2px;
            margin: 0;
        }

        .page-header i {
            font-size: 1.4rem;
            vertical-align: middle;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .card-header {
            background-color: #3c6255;
            color: #fff;
            font-weight: 600;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: 0.9;
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            padding: 1rem 0;
            margin-top: 2rem;
        }

        /* Botón volver arriba */
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            cursor: pointer;
            display: none;
            z-index: 1000;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        #btnTop:hover {
            transform: scale(1.1);
            opacity: 0.9;
        }

        @media (max-width:768px) {
            #btnTop {
                right: 20px;
                bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="text-center mb-4">
                    <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="50">
                    <hr class="text-light">
                </div>
                <ul class="nav flex-column gap-1 px-2">
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Mi perfil</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis mascotas</a></li>
                    <li><a class="nav-link active" href="<?= $baseFeatures; ?>/SolicitarPaseo.php"><i class="fas fa-walking"></i> Reservar paseo</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-hourglass-half"></i> Paseos pendientes</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosCompletados.php"><i class="fas fa-check-circle"></i> Paseos completados</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                    <li><a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php"><i class="fas fa-wallet"></i> Mis gastos</a></li>
                    <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                </ul>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="page-header">
                    <h2><i class="fas fa-calendar-check me-2"></i> Solicitar Paseo</h2>
                    <a href="Dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>

                </a>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'];
                                                                unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'];
                                                                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-dog me-2"></i> Información del Paseo</span>
                        <a href="BuscarPaseadores.php" class="btn btn-light btn-sm"><i class="fas fa-search me-1"></i> Buscar paseadores</a>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="mascota_id" class="form-label">Mascota *</label>
                                    <select class="form-select" name="mascota_id" required>
                                        <option value="">Seleccionar mascota</option>
                                        <?php foreach ($mascotas as $m): ?>
                                            <option value="<?= (int)$m['mascota_id'] ?>" <?= ((int)$m['mascota_id'] === $mascotaPreseleccionada) ? 'selected' : '' ?>>
                                                <?= h($m['nombre']) ?> (<?= ucfirst(h($m['tamano'])) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="paseador_id" class="form-label">Paseador *</label>
                                    <select class="form-select" id="paseador_id" name="paseador_id" required>
                                        <option value="">Seleccionar paseador</option>
                                        <?php foreach ($paseadores as $p):
                                            $precio = is_numeric($p['precio_hora']) ? (float)$p['precio_hora'] : 0; ?>
                                            <option value="<?= (int)$p['paseador_id'] ?>"
                                                data-precio="<?= $precio ?>"
                                                <?= ((int)$p['paseador_id'] === $paseadorPreseleccionado) ? 'selected' : '' ?>>
                                                <?= h($p['nombre']) ?> - ₲<?= number_format($precio, 0, ',', '.') ?>/hora (⭐ <?= (float)$p['calificacion'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Fecha y hora *</label>
                                    <input type="datetime-local" class="form-control" name="inicio" id="inicio" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Duración *</label>
                                    <select class="form-select" id="duracion" name="duracion" required>
                                        <option value="">Seleccionar duración</option>
                                        <?php foreach ([15 => '15 min', 30 => '30 min', 45 => '45 min', 60 => '1 hora', 90 => '1.5 horas', 120 => '2 horas'] as $min => $label): ?>
                                            <option value="<?= $min ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <hr>
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <small class="text-muted">Tarifa por hora</small>
                                    <div class="fs-5" id="tarifaHora">—</div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted">TOTAL ESTIMADO</small>
                                    <div class="fs-4 fw-bold" id="totalEstimado">—</div>
                                </div>
                            </div>

                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Recordá:</strong> el paseador confirmará tu solicitud antes del paseo. Podés cancelarlo hasta 1 hora antes del inicio.
                            </div>

                            <div class="d-flex justify-content-between mt-3">
                                <a href="Dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Cancelar</a>
                                <button type="submit" class="btn btn-gradient"><i class="fas fa-paper-plane me-1"></i> Solicitar Paseo</button>
                            </div>
                        </form>
                    </div>
                </div>

                <footer>© <?= date('Y') ?> Jaguata — Todos los derechos reservados.</footer>
            </main>
        </div>
    </div>

    <!-- Botón volver arriba -->
    <button id="btnTop" title="Volver arriba"><i class="fas fa-arrow-up"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll top button
        const btnTop = document.getElementById("btnTop");
        window.addEventListener("scroll", () => {
            btnTop.style.display = window.scrollY > 200 ? "block" : "none";
        });
        btnTop.addEventListener("click", () => window.scrollTo({
            top: 0,
            behavior: "smooth"
        }));

        // Form logic
        document.addEventListener('DOMContentLoaded', () => {
            const now = new Date();
            now.setHours(now.getHours() + 2);
            const formatted = now.toISOString().slice(0, 16);
            const inicio = document.getElementById('inicio');
            inicio.min = formatted;
            inicio.value = formatted;

            const selPaseador = document.getElementById('paseador_id');
            const selDuracion = document.getElementById('duracion');
            const tarifa = document.getElementById('tarifaHora');
            const total = document.getElementById('totalEstimado');
            const formatPYG = n => new Intl.NumberFormat('es-PY').format(Math.round(n));

            function update() {
                const opt = selPaseador.options[selPaseador.selectedIndex];
                const precio = opt?.dataset?.precio ? parseFloat(opt.dataset.precio) : 0;
                const dur = parseInt(selDuracion.value || 0);
                const totalCalc = (precio * dur) / 60;
                tarifa.textContent = precio ? `₲ ${formatPYG(precio)} / hora` : '—';
                total.textContent = totalCalc ? `₲ ${formatPYG(totalCalc)}` : '—';
            }

            selPaseador.addEventListener('change', update);
            selDuracion.addEventListener('change', update);
        });
    </script>
</body>

</html>