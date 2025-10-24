<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

// ====== Helpers ======
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function edadHumana(?int $meses): string
{
    if (!$meses || $meses <= 0) return '—';
    if ($meses < 12) return "$meses meses";
    $a = intdiv($meses, 12);
    $m = $meses % 12;
    return $m ? "{$a} años, {$m} meses" : "{$a} años";
}
function tamanoEtiqueta(?string $t): string
{
    return match ($t) {
        'pequeno' => 'Pequeño',
        'mediano' => 'Mediano',
        'grande' => 'Grande',
        default => '—'
    };
}
function badgeEstado(string $estado): string
{
    return match (strtolower($estado)) {
        'completo' => '<span class="badge bg-success">Completo</span>',
        'cancelado' => '<span class="badge bg-danger">Cancelado</span>',
        'confirmado' => '<span class="badge bg-primary">Confirmado</span>',
        'pendiente' => '<span class="badge bg-warning text-dark">Pendiente</span>',
        default => '<span class="badge bg-secondary">' . h($estado) . '</span>'
    };
}

// ====== Controladores ======
$rol = Session::getUsuarioRol() ?: 'dueno';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: MisMascotas.php");
    exit;
}

$mascotaCtrl = new MascotaController();
$paseoCtrl   = new PaseoController();

$mascota = $mascotaCtrl->show($id);
if (isset($mascota['error'])) {
    $_SESSION['error'] = $mascota['error'];
    header("Location: MisMascotas.php");
    exit;
}

$nombre = h($mascota['nombre'] ?? 'Mascota');
$raza = $mascota['raza'] ?? null;
$peso = $mascota['peso_kg'] ?? null;
$tamano = $mascota['tamano'] ?? null;
$edad = $mascota['edad_meses'] ?? null;
$observaciones = h($mascota['observaciones'] ?? '');
$foto = $mascota['foto_url'] ?? '';
$creado = $mascota['created_at'] ?? null;
$actualizado = $mascota['updated_at'] ?? null;

// Paseos
$paseos = $paseoCtrl->index();
$paseosMascota = array_filter($paseos, fn($p) => (int)($p['mascota_id'] ?? 0) === $id);
usort($paseosMascota, fn($a, $b) => strtotime($b['inicio'] ?? '') <=> strtotime($a['inicio'] ?? ''));
$recientes = array_slice($paseosMascota, 0, 5);
$completados = array_filter($paseosMascota, fn($p) => strtolower($p['estado'] ?? '') === 'completo');
$pendientes = array_filter($paseosMascota, fn($p) => in_array(strtolower($p['estado'] ?? ''), ['pendiente', 'confirmado']));
$totalPaseos = count($paseosMascota);
$totalCompleto = count($completados);
$totalPendientes = count($pendientes);
$gastoTotal = array_sum(array_map(fn($p) => (float)($p['precio_total'] ?? 0), $completados));

$baseFeatures = BASE_URL . "/features/{$rol}";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Perfil de Mascota - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
        }

        .layout {
            display: flex;
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
            box-shadow: 4px 0 12px rgba(0, 0, 0, .15);
            z-index: 1000;
            transition: transform .3s ease-in-out;
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
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
            background: #1e1e2f;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        @media(max-width:768px) {
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
            padding: 2.5rem;
            width: calc(100% - 240px);
        }

        @media(max-width:768px) {
            main.content {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
        }

        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
        }

        .form-label {
            font-weight: 600;
            color: #3c6255;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: .9;
        }

        .img-avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 14px;
            border: 3px solid #20c99733;
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
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis mascotas</a></li>
                <li><a class="nav-link active" href="#"><i class="fas fa-id-card"></i> Perfil mascota</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h4>Perfil de <?= $nombre ?></h4>
                    <p>Información general y paseos recientes 🐾</p>
                </div>
                <i class="fas fa-paw fa-3x opacity-75"></i>
            </div>

            <div class="row g-4">
                <!-- Info principal -->
                <div class="col-lg-4">
                    <div class="card-premium p-3 h-100">
                        <div class="text-center mb-3">
                            <img src="<?= $foto ? h($foto) : 'https://via.placeholder.com/150x150.png?text=Mascota'; ?>" class="img-avatar mb-2">
                            <h5><?= $nombre ?></h5>
                            <small class="text-muted">ID #<?= $id ?></small>
                        </div>
                        <div><strong>Raza:</strong> <?= $raza ? h($raza) : '—' ?></div>
                        <div><strong>Peso:</strong> <?= $peso ? number_format((float)$peso, 1, ',', '.') . ' kg' : '—' ?></div>
                        <div><strong>Tamaño:</strong> <?= tamanoEtiqueta($tamano) ?></div>
                        <div><strong>Edad:</strong> <?= edadHumana((int)$edad) ?></div>
                        <div><strong>Creado:</strong> <?= $creado ? date('d/m/Y H:i', strtotime($creado)) : '—' ?></div>
                        <div><strong>Actualizado:</strong> <?= $actualizado ? date('d/m/Y H:i', strtotime($actualizado)) : '—' ?></div>
                        <?php if ($observaciones): ?>
                            <hr>
                            <div><strong>Observaciones:</strong><br><?= nl2br($observaciones) ?></div>
                        <?php endif; ?>
                        <div class="text-end mt-3">
                            <a href="EditarMascota.php?id=<?= $id ?>" class="btn btn-gradient btn-sm"><i class="fas fa-pen me-1"></i>Editar</a>
                        </div>
                    </div>
                </div>

                <!-- Info paseos -->
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card-premium text-center p-3">
                                <div class="small text-muted">Paseos</div>
                                <div class="display-6"><?= $totalPaseos ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card-premium text-center p-3">
                                <div class="small text-muted">Completados</div>
                                <div class="display-6"><?= $totalCompleto ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card-premium text-center p-3">
                                <div class="small text-muted">Pendientes</div>
                                <div class="display-6"><?= $totalPendientes ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card-premium text-center p-3">
                                <div class="small text-muted">Gasto Total</div>
                                <div class="h4 mb-0">₲<?= number_format($gastoTotal, 0, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-premium mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-walking me-2"></i>Paseos recientes</h5>
                            <div class="btn-group btn-group-sm">
                                <a href="PaseosPendientes.php?mascota_id=<?= $id ?>" class="btn btn-outline-light">Pendientes</a>
                                <a href="PaseosCompletados.php?mascota_id=<?= $id ?>" class="btn btn-outline-light">Completados</a>
                                <a href="PaseosCancelados.php?mascota_id=<?= $id ?>" class="btn btn-outline-light">Cancelados</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recientes)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-calendar-xmark fa-2x mb-2"></i>
                                    <p>No hay paseos registrados para esta mascota.</p>
                                    <a href="SolicitarPaseo.php" class="btn btn-gradient btn-sm"><i class="fas fa-plus me-1"></i>Solicitar paseo</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Paseador</th>
                                                <th>Estado</th>
                                                <th>Precio</th>
                                                <th>Duración</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recientes as $p): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y H:i', strtotime($p['inicio'] ?? '')) ?></td>
                                                    <td><?= h($p['nombre_paseador'] ?? '—') ?></td>
                                                    <td><?= badgeEstado($p['estado'] ?? '') ?></td>
                                                    <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                                    <td><?= (int)($p['duracion_min'] ?? 0) ?> min</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    </script>
</body>

</html>