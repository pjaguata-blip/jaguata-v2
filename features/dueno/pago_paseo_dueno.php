<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/PaseoController.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\PaseoController;
use Jaguata\Helpers\Session;

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('dueno');

$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

$paseoId = (int)($_GET['paseo_id'] ?? $_POST['paseo_id'] ?? 0);
$paseoCtrl = new PaseoController();
$detalle = $paseoCtrl->getDetalleParaPago($paseoId);
if (!$detalle) {
    http_response_code(404);
    exit('Paseo no encontrado');
}

$paseadorNombre = htmlspecialchars($detalle['nombre_paseador']);
$paseadorBanco  = htmlspecialchars($detalle['paseador_banco'] ?? '');
$paseadorAlias  = htmlspecialchars($detalle['paseador_alias'] ?? '');
$paseadorCuenta = htmlspecialchars($detalle['paseador_cuenta'] ?? '');
$fecha          = date('d/m/Y H:i', strtotime($detalle['inicio']));
$duracion       = (int)($detalle['duracion_min'] ?? 0) . ' min';
$monto          = number_format((float)$detalle['precio_total'], 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar paseo - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar idéntico al dashboard */
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

        /* Botón móvil */
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

        /* Contenido principal */
        main.content {
            flex-grow: 1;
            margin-left: 240px;
            padding: 2.5rem;
            width: calc(100% - 240px);
            transition: all .3s ease;
        }

        @media (max-width: 768px) {
            main.content {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
            }
        }

        /* Header premium */
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
            font-size: 1.3rem;
            margin-bottom: .4rem;
        }

        /* Card premium */
        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            background: #fff;
        }

        .card-premium .card-header {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            padding: 1rem 1.3rem;
        }

        .summary-box {
            background: #212529;
            color: #fff;
            border-radius: 10px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
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
                <li><a class="nav-link active" href="#"><i class="fas fa-wallet"></i> Pago de paseo</a></li>
                <li><a class="nav-link text-danger" href="<?= BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h4>Pago del paseo</h4>
                    <p>Confirmá y registrá tu pago de manera rápida y segura 🐾</p>
                </div>
                <i class="fas fa-money-bill-wave fa-3x opacity-75"></i>
            </div>

            <div class="card-premium p-4">
                <div class="card-header"><i class="fas fa-receipt me-2"></i>Resumen del paseo</div>
                <div class="card-body">
                    <div class="summary-box">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Paseador:</strong> <?= $paseadorNombre ?></p>
                                <p><strong>Fecha:</strong> <?= $fecha ?></p>
                                <p><strong>Duración:</strong> <?= $duracion ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Monto:</strong> ₲ <?= $monto ?></p>
                                <p><strong>Banco:</strong> <?= $paseadorBanco ?: '-' ?></p>
                                <p><strong>Alias/Cuenta:</strong> <?= $paseadorAlias ?: $paseadorCuenta ?></p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Método de pago</label>
                            <div class="d-flex gap-4 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo" id="m1" value="efectivo" checked>
                                    <label class="form-check-label" for="m1">Efectivo</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo" id="m2" value="transferencia">
                                    <label class="form-check-label" for="m2">Transferencia</label>
                                </div>
                            </div>
                        </div>

                        <div id="transferenciaFields" class="border rounded p-3 bg-white mb-4 d-none">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Banco</label>
                                    <input type="text" class="form-control" name="banco" value="<?= $paseadorBanco ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cuenta o alias</label>
                                    <input type="text" class="form-control" name="cuenta" value="<?= $paseadorAlias ?: $paseadorCuenta ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Comprobante (JPG/PNG/PDF, máx 5MB)</label>
                                    <input type="file" class="form-control" name="comprobante" accept=".jpg,.jpeg,.png,.pdf">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Observación (opcional)</label>
                                    <textarea class="form-control" name="observacion" rows="2" placeholder="Ej: Transferí desde mi cuenta de ahorro..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-gradient px-4 py-2">
                                <i class="fas fa-check me-2"></i>Confirmar pago
                            </button>
                            <a href="Dashboard.php" class="btn btn-outline-secondary px-4 py-2">Cancelar</a>
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
        const m1 = document.getElementById('m1'),
            m2 = document.getElementById('m2'),
            box = document.getElementById('transferenciaFields');
        [m1, m2].forEach(r => r.addEventListener('change', () => box.classList.toggle('d-none', !m2.checked)));
    </script>
</body>

</html>