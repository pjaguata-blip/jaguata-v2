<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/src/Controllers/ConfiguracionController.php';
require_once dirname(__DIR__, 2) . '/src/Models/Usuario.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\AuthController;
use Jaguata\Controllers\ConfiguracionController;
use Jaguata\Models\Usuario;

AppConfig::init();

/* 🔒 Verificación de sesión y rol */
$auth = new AuthController();
$auth->checkRole('paseador');

$usuarioId      = Session::getUsuarioId();
$usuarioNombre  = Session::getUsuarioNombre() ?? 'Paseador';

$usuarioModel   = new Usuario();
$configCtrl     = new ConfiguracionController();

if (!$usuarioId) {
    // Por seguridad, si no hay ID de usuario volvemos al login
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

/* 🔹 Cargar datos actuales desde BD */
$usuarioRow = $usuarioModel->find((int)$usuarioId);

if (!$usuarioRow) {
    die('<h3 style="color:red; text-align:center;">Usuario no encontrado</h3>');
}

/* Preferencia de notificaciones desde tabla configuracion */
$notifKey = 'notif_email_usuario_' . $usuarioId;
$notifVal = $configCtrl->get($notifKey); // '1' o '0' o null
$notifActiva = $notifVal === null ? true : ($notifVal === '1');

/* Estado para el formulario */
$paseador = [
    'nombre'         => $usuarioRow['nombre']   ?? $usuarioNombre,
    'email'          => $usuarioRow['email']    ?? '',
    'telefono'       => $usuarioRow['telefono'] ?? '',
    'zona'           => $usuarioRow['zona']     ?? '',
    'notificaciones' => $notifActiva,
];

$mensajeExito = '';
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tomar datos del formulario
    $paseador['nombre']         = trim($_POST['nombre'] ?? '');
    $paseador['email']          = trim($_POST['email'] ?? '');
    $paseador['telefono']       = trim($_POST['telefono'] ?? '');
    $paseador['zona']           = trim($_POST['zona'] ?? '');
    $paseador['notificaciones'] = isset($_POST['notificaciones']);

    $nuevaPassword     = $_POST['nueva_password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';

    $errores = [];

    if ($paseador['nombre'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if ($paseador['email'] === '' || !filter_var($paseador['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }

    if ($nuevaPassword !== '') {
        if ($nuevaPassword !== $confirmarPassword) {
            $errores[] = 'Las contraseñas no coinciden.';
        } elseif (strlen($nuevaPassword) < 6) {
            $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        }
    }

    if (empty($errores)) {
        // 🔹 Actualizar datos básicos del usuario en la tabla usuarios
        try {
            $usuarioModel->update((int)$usuarioId, [
                'nombre'   => $paseador['nombre'],
                'email'    => $paseador['email'],
                'telefono' => $paseador['telefono'],
                'zona'     => $paseador['zona'],
            ]);

            // 🔹 Guardar preferencia de notificaciones en tabla configuracion
            $configCtrl->set($notifKey, $paseador['notificaciones'] ? '1' : '0');

            // 🔹 Cambio de contraseña (si corresponde)
            if ($nuevaPassword !== '' && $nuevaPassword === $confirmarPassword) {
                $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT);
                $usuarioModel->actualizarPassword((int)$usuarioId, $hash);
                $mensajeExito = '✅ Datos actualizados correctamente y contraseña cambiada.';
            } else {
                $mensajeExito = '✅ Datos actualizados correctamente.';
            }

            // Actualizar nombre de sesión por si lo cambió
            $usuarioRow['nombre'] = $paseador['nombre'];
            $usuarioRow['email']  = $paseador['email'];

            Session::login($usuarioRow);
        } catch (\Throwable $e) {
            $mensajeError = '❌ Ocurrió un error al guardar los datos: ' . $e->getMessage();
        }
    } else {
        $mensajeError = '⚠️ Corrige los siguientes errores:<br>- ' . implode('<br>- ', $errores);
    }
}

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar Paseador (mismo estilo que las demás pantallas) -->
    <?php include dirname(__DIR__, 2) . '/src/Templates/SidebarPaseador.php'; ?>

    <main>
        <!-- HEADER CONFIGURACIÓN -->
        <div class="header-box header-config mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-1">
                    <i class="fas fa-user-cog me-2"></i>Configuración de cuenta
                </h1>
                <p class="mb-0">Actualizá tus datos de contacto, zona de trabajo y contraseña 🐾</p>
            </div>
            <a href="<?= BASE_URL; ?>/features/paseador/Dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-1"></i> Volver al panel
            </a>
        </div>

        <!-- MENSAJES -->
        <?php if ($mensajeExito !== ''): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?= $mensajeExito; ?>
            </div>
        <?php endif; ?>

        <?php if ($mensajeError !== ''): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= $mensajeError; ?>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-id-badge me-2"></i>Datos de tu cuenta
            </div>

            <div class="section-body">
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre completo</label>
                        <input type="text" name="nombre" class="form-control"
                            value="<?= h($paseador['nombre']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Correo electrónico</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= h($paseador['email']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Teléfono</label>
                        <input type="text" name="telefono" class="form-control"
                            value="<?= h($paseador['telefono']); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Zona de trabajo</label>
                        <input type="text" name="zona" class="form-control"
                            value="<?= h($paseador['zona']); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Notificaciones</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="notificaciones"
                                id="chkNotificaciones"
                                <?= $paseador['notificaciones'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="chkNotificaciones">
                                Recibir notificaciones por correo
                            </label>
                        </div>
                    </div>

                    <hr class="mt-4 mb-3">

                    <div class="col-12">
                        <h5 class="text-success mb-0">
                            <i class="fas fa-key me-2"></i>Cambio de contraseña
                        </h5>
                        <small class="text-muted">Dejá en blanco si no querés cambiar tu contraseña.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nueva contraseña</label>
                        <input type="password" name="nueva_password" class="form-control"
                            placeholder="Dejar vacío para no cambiar">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirmar nueva contraseña</label>
                        <input type="password" name="confirmar_password" class="form-control">
                    </div>

                    <div class="col-12 text-end mt-3">
                        <button type="submit" class="btn-enviar">
                            <i class="fas fa-save me-2"></i>Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <footer class="mt-4 text-center text-muted">
            <small>© <?= date('Y'); ?> Jaguata — Panel del Paseador</small>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>