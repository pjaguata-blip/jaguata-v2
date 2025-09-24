<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MascotaController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MascotaController;

// Inicializar
AppConfig::init();
$authController = new AuthController();
$authController->checkRole('dueno');

$mascotaController = new MascotaController();

// Mascota ID
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "ID inválido";
    header("Location: MisMascotas.php");
    exit;
}

// Obtener mascota
$mascota = $mascotaController->show($id); // este sí existe en tu MascotaController
if (isset($mascota['error'])) {
    $_SESSION['error'] = $mascota['error'];
    header("Location: MisMascotas.php");
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $mascotaController->Update($id); // usa apiUpdate, no update

    if (isset($result['success']) && $result['success']) {
        $_SESSION['success'] = "Mascota actualizada correctamente";
        header("Location: MisMascotas.php");
        exit;
    } else {
        $_SESSION['error'] = $result['error'] ?? "No se pudo actualizar la mascota";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Mascota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Editar Mascota</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" name="nombre" 
                   value="<?= htmlspecialchars($mascota['nombre'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Raza</label>
            <input type="text" class="form-control" name="raza" 
                   value="<?= htmlspecialchars($mascota['raza'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Tamaño</label>
            <select name="tamano" class="form-select">
                <option value="">Seleccionar</option>
                <option value="pequeno" <?= ($mascota['tamano'] ?? '') === 'pequeno' ? 'selected' : '' ?>>Pequeño</option>
                <option value="mediano" <?= ($mascota['tamano'] ?? '') === 'mediano' ? 'selected' : '' ?>>Mediano</option>
                <option value="grande"  <?= ($mascota['tamano'] ?? '') === 'grande'  ? 'selected' : '' ?>>Grande</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Edad</label>
            <input type="number" class="form-control" name="edad" 
                   value="<?= htmlspecialchars($mascota['edad'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control"><?= htmlspecialchars($mascota['observaciones'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
        <a href="MisMascotas.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>
