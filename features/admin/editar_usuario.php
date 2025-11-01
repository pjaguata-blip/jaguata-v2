<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/UsuarioController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\UsuarioController;

AppConfig::init();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$controller = new UsuarioController();
$usuario = $controller->getById($id);

if (!$usuario) {
    die('<h3 style="color:red; text-align:center;">Usuario no encontrado</h3>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $rol = trim($_POST['rol']);
    $estado = trim($_POST['estado']);

    $ok = $controller->actualizarUsuario($id, [
        'nombre' => $nombre,
        'email' => $email,
        'rol' => $rol,
        'estado' => $estado
    ]);

    if ($ok) {
        header('Location: /jaguata/public/admin/Usuarios.php?actualizado=1');
        exit;
    } else {
        $error = "No se pudo actualizar el usuario.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <a href="Usuarios.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Volver</a>
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Editar Usuario #<?= $usuario['usu_id'] ?></h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="rol" class="form-select">
                            <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="paseador" <?= $usuario['rol'] === 'paseador' ? 'selected' : '' ?>>Paseador</option>
                            <option value="dueno" <?= $usuario['rol'] === 'dueno' ? 'selected' : '' ?>>Dueño</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="activo" <?= $usuario['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="suspendido" <?= $usuario['estado'] === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar cambios</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>