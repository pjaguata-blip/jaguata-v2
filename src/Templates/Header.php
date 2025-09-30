<?php
require_once __DIR__ . '/../Helpers/Session.php';

use Jaguata\Helpers\Session;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo ?? 'Jaguata - Paseo de Mascotas'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/favicon.ico">
</head>

<body class="<?php echo $body_class ?? ''; ?>">

    <?php
    // Flash messages
    $mensajes = Session::getFlashMessages();
    if (!empty($mensajes)): ?>
        <div class="container mt-2">
            <?php foreach ($mensajes as $tipo => $mensaje): ?>
                <div class="alert alert-<?php echo $tipo === 'error' ? 'danger' : $tipo; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    $usuarioLogueado = Session::isLoggedIn();
    $rolUsuario      = Session::getUsuarioRol();
    $nombreUsuario   = Session::getUsuarioNombre();

    // 🔹 URL dinámica de inicio
    $inicioUrl = BASE_URL;
    if ($usuarioLogueado && $rolUsuario) {
        $inicioUrl = BASE_URL . "/features/{$rolUsuario}/Dashboard.php";
    }
    ?>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand fw-bold text-primary" href="<?php echo $inicioUrl; ?>">
                Jaguata
            </a>

            <!-- Toggle para móviles -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menú -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $inicioUrl; ?>">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/sobre_nosotros.php">Sobre Nosotros</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/contacto.php">Contacto</a></li>
                </ul>

                <ul class="navbar-nav">
                    <?php if ($usuarioLogueado): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo htmlspecialchars($nombreUsuario); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/features/<?php echo $rolUsuario; ?>/Perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/features/<?php echo $rolUsuario; ?>/Dashboard.php">Dashboard</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">Iniciar Sesión</a></li>
                        <li class="nav-item"><a class="btn btn-primary ms-2" href="<?php echo BASE_URL; ?>/registro.php">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4">