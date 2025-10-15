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
    // Flash messages (éxito, error, info)
    $mensajes = Session::getFlashMessages();
    if (!empty($mensajes)): ?>
        <div class="container mt-2">
            <?php foreach ($mensajes as $tipo => $mensaje): ?>
                <div class="alert alert-<?php echo $tipo === 'error' ? 'danger' : $tipo; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>