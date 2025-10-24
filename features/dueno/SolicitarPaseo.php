<?php
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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Paseo - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL; ?>/css/jaguata-theme.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* === Estilos premium (idénticos al Dashboard) === */
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            overflow-x: hidden;
        }

        .layout {
            display: flex;
            flex-wrap: nowrap;
            width: 100%;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            transition: background 0.2s, transform 0.2s;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
        }

        .sidebar .nav-link:hover {
            background-color: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background-color: #1e1e2f;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }

        main.content {
            flex-grow: 1;
            margin-left: 240px;
            background-color: #f5f7fa;
            padding: 2rem 2.5rem;
            transition: margin-left 0.3s ease;
            width: calc(100% - 240px);
        }

        @media (max-width: 768px) {
            main.content {
                margin-left: 0;
                padding: 1.5rem;
                width: 100%;
            }
        }

        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .welcome-box h4 {
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 4px;
        }

        .welcome-box p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07);
        }

        .card-header {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            padding: 1rem 0;
            margin-top: 2rem;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="text-center mb-4">
                <img src="../../assets/img/logo.png" alt="Jaguata" width="120" class="mb-3">
                <hr class="text-light">
            </div>

            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Mi perfil</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis mascotas</a></li>
                <li><a class="nav-link active" href="<?= $baseFeatures; ?>/SolicitarPaseo.php"><i class="fas fa-walking"></i> Reservar paseo</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosPendientes.php"><i class="fas fa-hourglass-half"></i> Paseos pendientes</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosCompletados.php"><i class="fas fa-check-circle"></i> Paseos completados</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/PaseosCancelados.php"><i class="fas fa-times-circle"></i> Paseos cancelados</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/GastosTotales.php"><i class="fas fa-wallet"></i> Mis gastos</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h4>🐕 Solicitar un nuevo paseo</h4>
                    <p>Seleccioná tu mascota, paseador y horario preferido</p>
                </div>
                <i class="fas fa-calendar-check fa-3x opacity-75"></i>
            </div>

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
                <div class="card-header bg-success text-white fw-semibold d-flex justify-content-between align-items-center">
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
                                    <?php foreach ($paseadores as $p): ?>
                                        <option value="<?= (int)$p['paseador_id'] ?>"
                                            data-precio="<?= (float)$p['precio_hora'] ?>"
                                            <?= ((int)$p['paseador_id'] === $paseadorPreseleccionado) ? 'selected' : '' ?>>
                                            <?= h($p['nombre']) ?> - ₲<?= number_format($p['precio_hora'], 0, ',', '.') ?>/hora (⭐ <?= (float)$p['calificacion'] ?>)
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
                                    <?php foreach ([15 => '15 minutos', 30 => '30 minutos', 45 => '45 minutos', 60 => '1 hora', 90 => '1.5 horas', 120 => '2 horas'] as $min => $label): ?>
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
                            <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i> Solicitar Paseo</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));

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