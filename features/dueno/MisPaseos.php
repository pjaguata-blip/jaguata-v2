<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

$paseoCtrl = new PaseoController();
$duenoId = (int)Session::get('usuario_id');
$paseos = $paseoCtrl->indexByDueno($duenoId) ?? [];

// Métricas
$total = count($paseos);
$pendientes = array_filter($paseos, fn($p) => in_array($p['estado'], ['Pendiente', 'confirmado']));
$completos  = array_filter($paseos, fn($p) => strtolower($p['estado']) === 'completo');
$cancelados = array_filter($paseos, fn($p) => strtolower($p['estado']) === 'cancelado');
$gastoTotal = array_sum(array_column($completos, 'precio_total'));

$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Paseos - Jaguata</title>
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

        .card-premium .card-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            font-weight: 600;
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

        .table thead {
            background: #f8f9fa;
            font-weight: 600;
        }

        .badge {
            font-size: 0.85rem;
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
                <li><a class="nav-link active" href="#"><i class="fas fa-walking"></i> Mis Paseos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/SolicitarPaseo.php"><i class="fas fa-plus"></i> Solicitar Paseo</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MisMascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/MiPerfil.php"><i class="fas fa-user"></i> Perfil</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h4>Mis Paseos</h4>
                    <p>Listado de tus paseos realizados, pendientes y cancelados 🐾</p>
                </div>
                <a href="SolicitarPaseo.php" class="btn btn-light text-success fw-semibold">
                    <i class="fas fa-plus me-1"></i> Solicitar nuevo paseo
                </a>
            </div>

            <!-- Estadísticas -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card-premium text-center p-3">
                        <div class="small text-muted">Total</div>
                        <div class="display-6"><?= $total ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-premium text-center p-3">
                        <div class="small text-muted">Pendientes</div>
                        <div class="display-6"><?= count($pendientes) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-premium text-center p-3">
                        <div class="small text-muted">Completados</div>
                        <div class="display-6"><?= count($completos) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-premium text-center p-3">
                        <div class="small text-muted">Gasto Total</div>
                        <div class="h4 mb-0">₲<?= number_format($gastoTotal, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <?php if (empty($paseos)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-dog fa-4x mb-3"></i>
                    <h5>No tenés paseos registrados</h5>
                    <a href="SolicitarPaseo.php" class="btn btn-gradient mt-3"><i class="fas fa-plus me-1"></i> Solicitar tu primer paseo</a>
                </div>
            <?php else: ?>
                <div class="card-premium">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i> Lista de Paseos</h5>
                        <div>
                            <select class="form-select form-select-sm" id="filtroEstado" style="width:auto;" onchange="filtrarEstado()">
                                <option value="">Todos</option>
                                <option value="Pendiente">Pendientes</option>
                                <option value="confirmado">Confirmados</option>
                                <option value="en_curso">En curso</option>
                                <option value="completo">Completos</option>
                                <option value="cancelado">Cancelados</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Mascota</th>
                                        <th>Paseador</th>
                                        <th>Fecha</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>Precio</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paseos as $p): ?>
                                        <tr>
                                            <td><i class="fas fa-paw text-success me-2"></i><?= htmlspecialchars($p['nombre_mascota'] ?? '') ?></td>
                                            <td><i class="fas fa-user text-secondary me-2"></i><?= htmlspecialchars($p['nombre_paseador'] ?? '') ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($p['inicio'] ?? '')) ?></td>
                                            <td><?= (int)($p['duracion'] ?? 0) ?> min</td>
                                            <td>
                                                <?php
                                                $estado = strtolower($p['estado']);
                                                $badge = match ($estado) {
                                                    'completo' => 'success',
                                                    'cancelado' => 'danger',
                                                    'en_curso' => 'info',
                                                    'confirmado' => 'primary',
                                                    default => 'warning'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $badge ?>"><?= ucfirst($estado) ?></span>
                                            </td>
                                            <td>₲<?= number_format((float)($p['precio_total'] ?? 0), 0, ',', '.') ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="DetallePaseo.php?paseo_id=<?= $p['paseo_id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <?php if (in_array($p['estado'], ['Pendiente', 'confirmado'])): ?>
                                                        <a href="CancelarPaseo.php?id=<?= $p['paseo_id'] ?>"
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('¿Cancelar este paseo?')"
                                                            title="Cancelar">
                                                            <i class="fas fa-times"></i>
                                                        </a>

                                                        <a href="pago_paseo_dueno.php?paseo_id=<?= $p['paseo_id'] ?>"
                                                            class="btn btn-sm btn-outline-success"
                                                            title="Pagar">
                                                            <i class="fas fa-wallet"></i>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle.addEventListener('click', () => sidebar.classList.toggle('show'));

        function filtrarEstado() {
            const estado = document.getElementById('filtroEstado').value;
            const url = new URL(window.location.href);
            if (estado) url.searchParams.set('estado', estado);
            else url.searchParams.delete('estado');
            window.location.href = url.toString();
        }
    </script>
</body>

</html>