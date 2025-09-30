<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Controllers/MetodoPagoController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\MetodoPagoController;
use Jaguata\Helpers\Session;

// Inicializar aplicación
AppConfig::init();

// Verificar autenticación
$auth = new AuthController();
$auth->requireRole(['dueno']);

// Controlador
$controller = new MetodoPagoController();
$usuarioId  = Session::get('usuario_id');

// Variables para feedback
$mensaje = '';
$error   = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo       = $_POST['tipo'] ?? '';
    $alias      = trim($_POST['alias'] ?? '');
    $expiracion = $_POST['expiracion'] ?? null;
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if ($tipo === '' || $alias === '') {
        $error = 'Debes completar todos los campos obligatorios.';
    } else {
        $data = [
            'usu_id'      => $usuarioId,
            'tipo'        => $tipo,
            'alias'       => $alias,
            'expiracion'  => $expiracion,
            'is_default'  => $is_default
        ];

        try {
            $id = $controller->store($data);
            if ($id) {
                if ($is_default) {
                    $controller->setDefault($usuarioId, $id);
                }
                $mensaje = 'Método de pago agregado correctamente.';
            } else {
                $error = 'No se pudo registrar el método de pago.';
            }
        } catch (Exception $e) {
            $error = 'Error interno: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Agregar Método de Pago - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include __DIR__ . '/../../src/Templates/Header.php'; ?>
    <?php include __DIR__ . '/../../src/Templates/Navbar.php'; ?>

    <div class="container mt-4">
        <h2>Agregar Método de Pago</h2>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="mt-3">
            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo de pago</label>
                <select name="tipo" id="tipo" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="efectivo">Efectivo</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="alias" class="form-label">Alias</label>
                <input type="text" name="alias" id="alias" maxlength="50" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="expiracion" class="form-label">Expiración (MM/YYYY) <small class="text-muted">(opcional)</small></label>
                <input type="text" name="expiracion" id="expiracion" maxlength="7" class="form-control" placeholder="MM/YYYY">
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                <label class="form-check-label" for="is_default">
                    Establecer como predeterminado
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="MetodosPago.php" class="btn btn-secondary">Volver</a>
        </form>
    </div>

    <?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>
</body>

</html>