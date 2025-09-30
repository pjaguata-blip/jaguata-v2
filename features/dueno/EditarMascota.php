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
$mascota = $mascotaController->show($id);
if (!$mascota || isset($mascota['error'])) {
    $_SESSION['error'] = $mascota['error'] ?? "Mascota no encontrada";
    header("Location: MisMascotas.php");
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $mascotaController->apiUpdate($id); // 👈 aquí usamos apiUpdate

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
    <title>Editar Mascota - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-paw me-2"></i>Editar Mascota</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
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
                    <option value="pequeno" <?= ($mascota['tamano'] ?? '') === 'pequeno' ? 'selected' : '' ?>>Pequeño (0-10 kg)</option>
                    <option value="mediano" <?= ($mascota['tamano'] ?? '') === 'mediano' ? 'selected' : '' ?>>Mediano (11-25 kg)</option>
                    <option value="grande" <?= ($mascota['tamano'] ?? '') === 'grande'  ? 'selected' : '' ?>>Grande (26-45 kg)</option>
                    <option value="extra_grande" <?= ($mascota['tamano'] ?? '') === 'extra_grande'  ? 'selected' : '' ?>>Extra Grande (+46 kg)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Edad</label>
                <input type="number" class="form-control" name="edad" min="0" max="30"
                    value="<?= htmlspecialchars($mascota['edad'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($mascota['observaciones'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar cambios
            </button>
            <a href="MisMascotas.php" class="btn btn-secondary">
                <i class="fas fa-times me-1"></i> Cancelar
            </a>
        </form>
    </div>
</body>

</html>