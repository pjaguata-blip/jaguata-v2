<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;

AppConfig::init();

// 🔒 Verificación de sesión y rol
$auth = new AuthController();
$auth->checkRole('paseador');

// 🔹 Datos simulados (en producción desde BD)
$paseador = [
    'nombre'         => Session::getUsuarioNombre() ?? 'Paseador',
    'email'          => Session::getUsuarioEmail() ?? 'paseador@correo.com',
    'telefono'       => '0981 123 456',
    'zona'           => 'Asunción',
    'notificaciones' => true
];

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paseador['nombre']         = trim($_POST['nombre']);
    $paseador['email']          = trim($_POST['email']);
    $paseador['telefono']       = trim($_POST['telefono']);
    $paseador['zona']           = trim($_POST['zona']);
    $paseador['notificaciones'] = isset($_POST['notificaciones']);

    if (!empty($_POST['nueva_password'])) {
        if ($_POST['nueva_password'] === $_POST['confirmar_password']) {
            $mensaje = '✅ Datos actualizados correctamente y contraseña cambiada.';
        } else {
            $mensaje = '⚠️ Las contraseñas no coinciden.';
        }
    } else {
        $mensaje = '✅ Datos actualizados correctamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Paseador | Jaguata</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --verde-jaguata: #3c6255;
            --verde-claro: #20c997;
            --gris-fondo: #f4f6f9;
            --gris-texto: #555;
            --blanco: #fff;
        }

        body {
            background-color: var(--gris-fondo);
            font-family: "Poppins", sans-serif;
            color: var(--gris-texto);
            margin: 0;
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* === SIDEBAR unificada (250px, mismo estilo que las demás) === */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #fff;
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .sidebar .nav-link i {
            width: 22px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: var(--blanco);
            transform: translateX(4px);
        }

        /* === CONTENIDO === */
        main.content {
            margin-left: 250px;
            padding: 2rem 2.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            main.content {
                margin-left: 0;
                padding: 1.5rem;
            }

            .menu-toggle {
                display: block;
            }
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background-color: #1e1e2f;
            color: #fff;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        /* === HEADER === */
        .page-header {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-weight: 600;
            font-size: 1.3rem;
            margin: 0;
        }

        /* === FORM === */
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }

        label {
            font-weight: 600;
            color: #333;
        }

        .form-check-input:checked {
            background-color: #3c6255;
            border-color: #3c6255;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            border-radius: 8px;
            transition: .3s;
            padding: 0.7rem 1.6rem;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: .9;
        }

        .alert {
            border-radius: 10px;
            border-left: 5px solid #20c997;
        }

        footer {
            text-align: center;
            color: #777;
            font-size: 0.9rem;
            padding: 1rem 0;
            margin-top: 2rem;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="layout">
        <?php include __DIR__ . '/../../src/Templates/SidebarPaseador.php'; ?>

        <!-- Contenido -->
        <main class="content">
            <div class="page-header">
                <h2><i class="fas fa-user-cog me-2"></i>Configuración de Cuenta</h2>
                <a href="Dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Volver
                </a>
            </div>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-success mb-4">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="card p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Nombre completo</label>
                        <input type="text" name="nombre" class="form-control"
                            value="<?= htmlspecialchars($paseador['nombre']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label>Correo electrónico</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($paseador['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="form-control"
                            value="<?= htmlspecialchars($paseador['telefono']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Zona de trabajo</label>
                        <input type="text" name="zona" class="form-control"
                            value="<?= htmlspecialchars($paseador['zona']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Notificaciones</label><br>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="notificaciones"
                                <?= $paseador['notificaciones'] ? 'checked' : '' ?>>
                            <label class="form-check-label">Recibir notificaciones por correo</label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="text-success">
                    <i class="fas fa-key me-2"></i>Cambio de contraseña
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Nueva contraseña</label>
                        <input type="password" name="nueva_password" class="form-control"
                            placeholder="Dejar vacío para no cambiar">
                    </div>
                    <div class="col-md-6">
                        <label>Confirmar nueva contraseña</label>
                        <input type="password" name="confirmar_password" class="form-control">
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn-gradient">
                        <i class="fas fa-save me-2"></i>Guardar cambios
                    </button>
                </div>
            </form>

            <footer>© <?= date('Y') ?> Jaguata — Panel de Paseador</footer>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar'); // id definido en SidebarPaseador.php

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    </script>
</body>

</html>