<?php
require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/PagoController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\PagoController;

AppConfig::init();

if (!Session::isLoggedIn()) {
    header('Location: /jaguata/public/login.php');
    exit;
}
$rol = Session::getUsuarioRol() ?? 'admin';
$baseFeatures = BASE_URL . "/features/{$rol}";

// Simular datos
$pagos = [
    ['id' => 5001, 'usuario' => 'Lucas Díaz', 'monto' => 40000, 'fecha' => '2025-10-26', 'estado' => 'Pagado'],
    ['id' => 5002, 'usuario' => 'María López', 'monto' => 25000, 'fecha' => '2025-10-27', 'estado' => 'Pendiente'],
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos y Facturación - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* === Layout general (basado en tu Dashboard) === */
        body {
            background-color: #f5f7fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* === Sidebar === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
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

        /* === Contenido === */
        main.content {
            flex-grow: 1;
            margin-left: 240px;
            padding: 2rem 2.5rem;
            background: #f5f7fa;
        }

        /* === Encabezado === */
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

        /* === Filtros === */
        .filtros {
            background: #fff;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .filtros input,
        .filtros select {
            border-radius: 8px;
        }

        /* === Tabla de pagos === */
        .table {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .table thead {
            background: #3c6255;
            color: #fff;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .table-hover tbody tr:hover {
            background: #eef8f2;
        }

        /* === Botones === */
        .btn-ver {
            background-color: #20c997;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
        }

        .btn-ver:hover {
            background-color: #3c6255;
            color: #fff;
        }

        /* === Footer === */
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
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="text-center mb-4">
                <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Logo" width="50">
                <hr class="text-light">
            </div>
            <ul class="nav flex-column gap-1 px-2">
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Dashboard.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a class="nav-link active" href="#"><i class="fas fa-wallet"></i> Pagos</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Reportes.php"><i class="fas fa-chart-pie"></i> Reportes</a></li>
                <li><a class="nav-link" href="<?= $baseFeatures; ?>/Configuracion.php"><i class="fas fa-cogs"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
            </ul>
        </aside>

        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h1 class="fw-bold"><i class="fas fa-wallet me-2"></i>Pagos y Facturación</h1>
                    <p>Visualizá y administrá las transacciones del sistema 💳</p>
                </div>
                <i class="fas fa-coins fa-3x opacity-75"></i>
            </div>

            <!-- Filtros -->
            <div class="filtros mb-4">
                <form class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Buscar por usuario</label>
                        <input type="text" class="form-control" placeholder="Ej: Lucas Díaz">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select class="form-select">
                            <option value="">Todos</option>
                            <option>Pagado</option>
                            <option>Pendiente</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Rango de fechas</label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="col-md-3 text-end align-self-end">
                        <button class="btn btn-success"><i class="fas fa-filter"></i> Filtrar</button>
                    </div>
                </form>
            </div>

            <!-- Tabla -->
            <div class="card">
                <div class="card-body">
                    <table class="table table-hover align-middle text-center">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $p): ?>
                                <tr>
                                    <td><strong>#<?= $p['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($p['usuario']) ?></td>
                                    <td>₲<?= number_format($p['monto'], 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                                    <td>
                                        <?php if (strtolower($p['estado']) === 'pagado'): ?>
                                            <span class="badge bg-success">Pagado</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><button class="btn-ver"><i class="fas fa-file-invoice"></i> Ver</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer>
                <small>© <?= date('Y') ?> Jaguata — Sistema de Pagos</small>
            </footer>
        </main>
    </div>
</body>

</html>