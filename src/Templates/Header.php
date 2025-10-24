<?php
require_once __DIR__ . '/../Helpers/Session.php';

use Jaguata\Helpers\Session;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Jaguata - Paseo de Mascotas'; ?></title>

    <!-- Bootstrap + FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="<?= ASSETS_URL; ?>/css/style.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="icon" type="image/x-icon" href="<?= ASSETS_URL; ?>/images/favicon.ico">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #f6f9f7;
        }

        .navbar-brand img {
            height: 38px;
            border-radius: 6px;
        }

        .swal2-popup {
            border-radius: 16px !important;
        }
    </style>
</head>

<body class="<?= $body_class ?? ''; ?>">

    <?php
    // Mensajes flash (éxito, error, info, warning)
    $mensajes = Session::getFlashMessages();
    ?>

    <!-- Navbar (opcional si tu header lo incluye globalmente) -->


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // --- Mostrar mensajes flash con SweetAlert2 ---
        <?php if (!empty($mensajes)): ?>
            <?php foreach ($mensajes as $tipo => $mensaje): ?>
                Swal.fire({
                    icon: '<?= $tipo === 'error' ? 'error' : ($tipo === 'success' ? 'success' : ($tipo === 'warning' ? 'warning' : 'info')) ?>',
                    title: '<?=
                            $tipo === "success" ? "¡Listo!" : ($tipo === "error" ? "Ups..." : ($tipo === "warning" ? "Atención" : "Información"))
                            ?>',
                    text: '<?= addslashes($mensaje) ?>',
                    showConfirmButton: <?= $tipo === 'success' ? 'false' : 'true' ?>,
                    timer: <?= $tipo === 'success' ? '2200' : 'null' ?>,
                    background: '#f6f9f7',
                    confirmButtonColor: '#3c6255'
                });
            <?php endforeach; ?>
        <?php endif; ?>
    </script>