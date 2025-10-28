<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/ConfiguracionController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\ConfiguracionController;

AppConfig::init();

if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

$configController = new ConfiguracionController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configController->saveMany([
        'nombre_sistema' => $_POST['nombre_sistema'] ?? '',
        'correo_soporte' => $_POST['correo_soporte'] ?? '',
        'modo_mantenimiento' => $_POST['modo_mantenimiento'] ?? '0',
        'comision_porcentaje' => $_POST['comision_porcentaje'] ?? '0',
        'tarifa_base' => $_POST['tarifa_base'] ?? '0',
    ]);
    $mensaje = "✅ Configuración actualizada correctamente.";
}

$config = array_merge([
    'nombre_sistema' => 'Jaguata',
    'correo_soporte' => 'soporte@jaguata.com',
    'modo_mantenimiento' => '0',
    'comision_porcentaje' => '10',
    'tarifa_base' => '20000'
], $configController->getAll());
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f5f7fa;
            --gris-borde: #dce2dc;
            --blanco: #fff;
        }

        body {
            background-color: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
        }

        /* === Sidebar === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #fff;
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
        }

        .sidebar .nav-link {
            color: #ddd;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: #fff;
        }

        /* === Main === */
        main {
            margin-left: 250px;
            padding: 2rem;
        }

        /* === Header === */
        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.6rem 2.2rem;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            animation: fadeIn 0.6s ease;
        }

        /* === Tarjetas de configuración === */
        .section-card {
            background: var(--blanco);
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            padding: 1.8rem 2rem;
            margin-bottom: 1.5rem;
            border-left: 6px solid var(--verde-claro);
            transition: all 0.25s ease;
        }

        .section-card:hover {
            transform: translateY(-3px);
        }

        .section-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--verde-jaguata);
            border-bottom: 1px solid var(--gris-borde);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
        }

        /* === Inputs === */
        label {
            font-weight: 600;
            color: #444;
        }

        input,
        select {
            border-radius: 10px;
            padding: 10px 12px;
            border: 1px solid #ccc;
            transition: 0.2s;
        }

        input:focus,
        select:focus {
            border-color: var(--verde-claro);
            box-shadow: 0 0 0 3px rgba(32, 201, 151, 0.2);
        }

        /* === Switch === */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: var(--verde-claro);
        }

        input:checked+.slider:before {
            transform: translateX(30px);
        }

        /* === Botón === */
        .btn-guardar {
            background: var(--verde-jaguata);
            color: #fff;
            border: none;
            padding: 0.7rem 1.8rem;
            border-radius: 10px;
            font-weight: 500;
            margin-top: 1rem;
            transition: all 0.2s;
        }

        .btn-guardar:hover {
            background: var(--verde-claro);
            transform: scale(1.03);
        }

        /* === Mensaje de éxito === */
        .alert-success {
            border-left: 6px solid var(--verde-claro);
            animation: fadeIn 0.7s ease;
        }

        /* === Animaciones === */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* === Footer === */
        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            margin-top: 2rem;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="text-center mb-4">
            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Logo" width="50">
            <hr class="text-light">
        </div>
        <ul class="nav flex-column gap-1 px-2">
            <li><a class="nav-link" href="Dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a class="nav-link" href="Usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
            <li><a class="nav-link" href="Paseos.php"><i class="fas fa-dog"></i> Paseos</a></li>
            <li><a class="nav-link" href="Pagos.php"><i class="fas fa-wallet"></i> Pagos</a></li>
            <li><a class="nav-link" href="Reportes.php"><i class="fas fa-chart-pie"></i> Reportes</a></li>
            <li><a class="nav-link active" href="#"><i class="fas fa-cogs"></i> Configuración</a></li>
            <li><a class="nav-link" href="Auditoria.php"><i class="fas fa-shield-halved"></i> Auditoría</a></li>
            <li><a class="nav-link text-danger" href="/jaguata/public/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
        </ul>
    </aside>

    <!-- Contenido -->
    <main>
        <div class="welcome-box">
            <div>
                <h1>Configuración del Sistema</h1>
                <p>Personalizá los parámetros globales y operativos ⚙️</p>
            </div>
            <i class="fas fa-sliders-h fa-3x opacity-75"></i>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success text-center fw-semibold"><?= $mensaje ?></div>
        <?php endif; ?>

        <form method="post">
            <!-- Sección general -->
            <div class="section-card">
                <div class="section-header"><i class="fas fa-gears me-2"></i>Configuración General</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Nombre del sistema</label>
                        <input type="text" name="nombre_sistema" value="<?= htmlspecialchars($config['nombre_sistema']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Correo de soporte</label>
                        <input type="email" name="correo_soporte" value="<?= htmlspecialchars($config['correo_soporte']) ?>">
                    </div>
                </div>
            </div>

            <!-- Sección financiera -->
            <div class="section-card">
                <div class="section-header"><i class="fas fa-wallet me-2"></i>Configuración Financiera</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Comisión del sistema (%)</label>
                        <input type="number" min="0" max="100" name="comision_porcentaje" value="<?= htmlspecialchars($config['comision_porcentaje']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Tarifa base por paseo (₲)</label>
                        <input type="number" name="tarifa_base" value="<?= htmlspecialchars($config['tarifa_base']) ?>">
                    </div>
                </div>
            </div>

            <!-- Sección técnica -->
            <div class="section-card">
                <div class="section-header"><i class="fas fa-shield-halved me-2"></i>Configuración del Sistema</div>
                <div class="row align-items-center g-3">
                    <div class="col-md-6">
                        <label>Modo mantenimiento</label><br>
                        <label class="switch">
                            <input type="checkbox" name="modo_mantenimiento" value="1" <?= $config['modo_mantenimiento'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                        <small class="text-muted d-block mt-1">Cuando está activo, solo administradores pueden ingresar.</small>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="submit" class="btn-guardar">
                            <i class="fas fa-save me-2"></i>Guardar cambios
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <footer>
            <small>© <?= date('Y') ?> Jaguata — Panel de Administración</small>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>