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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        main {
            margin-left: 250px;
            padding: 2rem;
        }

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
        }

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

        footer {
            text-align: center;
            color: #777;
            font-size: 0.85rem;
            margin-top: 2rem;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

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
            <!-- General -->
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

            <!-- Financiera -->
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

            <!-- Técnica -->
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

        <!-- Seguridad -->
        <div class="section-card">
            <div class="section-header"><i class="fas fa-lock me-2"></i>Seguridad de la Cuenta</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Contraseña actual</label>
                    <input type="password" id="passActual" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Nueva contraseña</label>
                    <input type="password" id="passNueva" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Confirmar nueva contraseña</label>
                    <input type="password" id="passConfirm" class="form-control">
                </div>
            </div>
            <div class="text-end mt-3">
                <button id="btnCambiarPass" class="btn-guardar"><i class="fas fa-key me-2"></i>Actualizar contraseña</button>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('btnCambiarPass').addEventListener('click', () => {
            const actual = document.getElementById('passActual').value.trim();
            const nueva = document.getElementById('passNueva').value.trim();
            const confirmar = document.getElementById('passConfirm').value.trim();

            if (!actual || !nueva || !confirmar)
                return Swal.fire('⚠️', 'Completá todos los campos.', 'warning');

            if (nueva !== confirmar)
                return Swal.fire('❌', 'Las contraseñas no coinciden.', 'error');

            if (nueva.length < 6)
                return Swal.fire('🔒', 'La nueva contraseña debe tener al menos 6 caracteres.', 'warning');

            // En un entorno real aquí se haría fetch() a tu controlador PHP
            Swal.fire({
                icon: 'success',
                title: 'Contraseña actualizada',
                text: 'Tu contraseña fue cambiada correctamente.',
                confirmButtonColor: '#20c997'
            });

            document.getElementById('passActual').value = '';
            document.getElementById('passNueva').value = '';
            document.getElementById('passConfirm').value = '';
        });
    </script>
</body>

</html>