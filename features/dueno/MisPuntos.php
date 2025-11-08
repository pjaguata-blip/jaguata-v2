<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

AppConfig::init();

/* 🔒 Auth (rol dueño) */
$auth = new AuthController();
$auth->checkRole('dueno');

/* Datos usuario */
$usuarioId = (int) (Session::get('usuario_id') ?? 0);
$rol       = Session::getUsuarioRol() ?: 'dueno';

$usuarioModel = new Usuario();
$usuario      = $usuarioModel->getById($usuarioId);

if (!$usuario) {
    http_response_code(404);
    exit('❌ Usuario no encontrado');
}

$puntos       = (int) ($usuario['puntos'] ?? 0);
$baseFeatures = BASE_URL . "/features/{$rol}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mis Puntos - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
            margin: 0
        }

        /* Sidebar fija (250px) */
        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: var(--blanco);
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 3px 0 10px rgba(0, 0, 0, .2);
            z-index: 1000
        }

        .sidebar .nav-link {
            color: #ccc;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 12px 18px;
            border-radius: 8px;
            margin: 6px 10px;
            transition: .2s;
            font-size: .95rem
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--verde-claro);
            color: #fff;
            transform: translateX(4px)
        }

        /* Main */
        main {
            margin-left: 250px;
            padding: 2rem
        }

        @media (max-width:768px) {
            main {
                margin-left: 0;
                padding: 1.25rem
            }
        }

        /* Header */
        .welcome-box {
            background: linear-gradient(90deg, var(--verde-claro), var(--verde-jaguata));
            color: #fff;
            padding: 1.6rem 2rem;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1)
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08)
        }

        .badge-soft {
            background: #eef8f2;
            color: #2f7a67
        }

        .btn-outline-secondary {
            border-color: var(--verde-claro);
            color: var(--verde-claro)
        }

        .btn-outline-secondary:hover {
            background: var(--verde-claro);
            color: #fff
        }

        footer {
            text-align: center;
            color: #777;
            font-size: .9rem;
            margin-top: 2rem
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>

    <!-- Contenido -->
    <main>
        <div class="welcome-box">
            <div>
                <h1 class="fw-bold"><i class="fas fa-star me-2"></i> Mis Puntos</h1>
                <p>Sumá puntos completando paseos y canjealos por beneficios.</p>
            </div>
            <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-light fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card p-4 text-center">
                    <div class="card-body">
                        <h2 class="fw-bold mb-3" style="color:#20c997">
                            <i class="fas fa-medal text-warning me-2"></i> ¡Tus Recompensas!
                        </h2>
                        <p class="text-muted mb-4">Cada paseo completado suma puntos 🐶</p>

                        <div class="bg-light rounded-4 py-4 mb-4 border">
                            <h2 class="display-3 fw-bold mb-0" style="color:#3c6255">
                                <?= number_format($puntos, 0, ',', '.') ?>
                            </h2>
                            <small class="text-secondary">puntos acumulados</small>
                        </div>

                        <a href="<?= $baseFeatures; ?>/Dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Volver al Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <footer><small>© <?= date('Y') ?> Jaguata — Panel del Dueño</small></footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>