<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/Session.php';
require_once dirname(__DIR__, 2) . '/src/Services/DatabaseService.php';

require_once dirname(__DIR__, 2) . '/src/Controllers/ConfiguracionController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Controllers\ConfiguracionController;

AppConfig::init();

/* 🔒 Solo admin */
if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
    header('Location: ' . BASE_URL . '/public/login.php?error=unauthorized');
    exit;
}

/* Helpers */
function h(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Rutas base (para botón volver, etc.) */
$rolMenu      = Session::getUsuarioRol() ?: 'admin';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";

$configController = new ConfiguracionController();

$mensajeConfig = '';
$mensajeRoles  = '';

/* 🔹 Traer TODO lo que haya en la tabla configuracion (clave/valor) */
$configDB = $configController->getAll();

/**
 * Defaults del sistema
 * (Ya NO usamos comisión ni tarifa por paseo)
 */
$configBase = [
    'nombre_sistema'            => 'Jaguata',
    'correo_soporte'            => 'soporte@jaguata.com',
    'modo_mantenimiento'        => '0',

    // ✅ Suscripción mensual (nuevas claves)
    'suscripcion_mensual_monto' => '50000', // ejemplo
    'suscripcion_estado'        => '1',     // 1=activo, 0=inactivo
    'suscripcion_detalle'       => 'Acceso completo a la plataforma mediante suscripción mensual.'
];

/**
 * Defaults de roles
 */
$rolesDefault = [
    ['id' => 1, 'nombre' => 'admin',    'descripcion' => 'Acceso total al sistema',                     'estado' => 'activo'],
    ['id' => 2, 'nombre' => 'paseador', 'descripcion' => 'Puede gestionar paseos y ganancias',          'estado' => 'activo'],
    ['id' => 3, 'nombre' => 'dueno',    'descripcion' => 'Puede solicitar paseos y ver historial',      'estado' => 'activo'],
    ['id' => 4, 'nombre' => 'soporte',  'descripcion' => 'Puede atender reclamos y reportes',           'estado' => 'inactivo'],
];

/**
 * Defaults de permisos (acciones por módulo) – opcional
 */
$permisosDefault = [
    'Usuarios'       => ['Ver', 'Editar', 'Eliminar'],
    'Paseos'         => ['Ver', 'Editar', 'Cancelar'],
    'Pagos'          => ['Ver', 'Procesar', 'Reembolsar'],
    'Reportes'       => ['Ver'],
    'Configuración'  => ['Ver', 'Editar'],
    'Auditoría'      => ['Ver'],
];

/**
 * Módulos base del sistema (para configurar qué ve cada rol)
 */
$modulosDefault = [
    'Usuarios',
    'Paseos',
    'Pagos',
    'Reportes',
    'Configuración',
    'Auditoría',
];

/**
 * Defaults de permisos por módulo y rol (quién puede ver qué módulo)
 */
$permisosModuloRolDefault = [
    'Usuarios' => [
        'admin'    => true,
        'paseador' => false,
        'dueno'    => false,
    ],
    'Paseos' => [
        'admin'    => true,
        'paseador' => true,
        'dueno'    => true,
    ],
    'Pagos' => [
        'admin'    => true,
        'paseador' => true,
        'dueno'    => true,
    ],
    'Reportes' => [
        'admin'    => true,
        'paseador' => true,
        'dueno'    => true,
    ],
    'Configuración' => [
        'admin'    => true,
        'paseador' => false,
        'dueno'    => false,
    ],
    'Auditoría' => [
        'admin'    => true,
        'paseador' => false,
        'dueno'    => false,
    ],
];

/* 🔹 Cargar CONFIGURACIÓN del sistema (clave/valor) */
$config = array_merge($configBase, $configDB);

/* 🔹 Cargar ROLES guardados como JSON (si existen) */
if (!empty($configDB['roles_config'])) {
    $dec = json_decode($configDB['roles_config'], true);
    $rolesConfig = is_array($dec) ? $dec : $rolesDefault;
} else {
    $rolesConfig = $rolesDefault;
}

/* 🔹 Cargar PERMISOS guardados como JSON (acciones por módulo) – opcional */
if (!empty($configDB['permisos_config'])) {
    $dec = json_decode($configDB['permisos_config'], true);
    $permisosConfig = is_array($dec) ? $dec : $permisosDefault;
} else {
    $permisosConfig = $permisosDefault;
}

/* 🔹 Cargar PERMISOS POR MÓDULO Y ROL (admin/paseador/dueno) */
if (!empty($configDB['permisos_modulo_rol'])) {
    $dec = json_decode($configDB['permisos_modulo_rol'], true);
    $permisosModuloRolConfig = is_array($dec) ? $dec : $permisosModuloRolDefault;
} else {
    $permisosModuloRolConfig = $permisosModuloRolDefault;
}

/* 🔹 Procesar POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipoForm = $_POST['tipo_form'] ?? 'sistema';

    if ($tipoForm === 'sistema') {

        // ✅ Guardar configuración general + suscripción + modo mantenimiento
        $suscripcionActiva = isset($_POST['suscripcion_estado']) ? '1' : '0';

        $configController->saveMany([
            'nombre_sistema'            => $_POST['nombre_sistema'] ?? '',
            'correo_soporte'            => $_POST['correo_soporte'] ?? '',
            'modo_mantenimiento'        => isset($_POST['modo_mantenimiento']) ? '1' : '0',

            // ✅ Suscripción mensual
            'suscripcion_mensual_monto' => $_POST['suscripcion_mensual_monto'] ?? '0',
            'suscripcion_estado'        => $suscripcionActiva,
            'suscripcion_detalle'       => $_POST['suscripcion_detalle'] ?? '',
        ]);

        $mensajeConfig = "✅ Configuración actualizada correctamente.";

        $configDB = $configController->getAll();
        $config   = array_merge($configBase, $configDB);

    } elseif ($tipoForm === 'roles') {

        // 1) Roles
        $rolesActualizados = [];
        foreach ($rolesDefault as $r) {
            $id = $r['id'];
            $r['estado'] = isset($_POST['rol_estado'][$id]) ? 'activo' : 'inactivo';
            $rolesActualizados[] = $r;
        }

        // 2) Permisos (acciones por módulo) – opcional
        $permisosActualizados = [];
        if (isset($_POST['perm']) && is_array($_POST['perm'])) {
            foreach ($_POST['perm'] as $modulo => $accionesMarcadas) {
                if (is_array($accionesMarcadas)) {
                    $permisosActualizados[$modulo] = array_keys($accionesMarcadas);
                }
            }
        }
        foreach ($permisosDefault as $mod => $acts) {
            if (!isset($permisosActualizados[$mod])) {
                $permisosActualizados[$mod] = [];
            }
        }

        // 3) Permisos por módulo y rol
        $permisosModuloRolActualizados = [];
        $rolesVisibles = ['admin', 'paseador', 'dueno'];

        foreach ($modulosDefault as $modulo) {
            foreach ($rolesVisibles as $rolNombre) {
                $permisosModuloRolActualizados[$modulo][$rolNombre] =
                    isset($_POST['perm_rol'][$modulo][$rolNombre]);
            }
        }

        // Guardar JSON
        $configController->saveMany([
            'roles_config'        => json_encode($rolesActualizados, JSON_UNESCAPED_UNICODE),
            'permisos_config'     => json_encode($permisosActualizados, JSON_UNESCAPED_UNICODE),
            'permisos_modulo_rol' => json_encode($permisosModuloRolActualizados, JSON_UNESCAPED_UNICODE),
        ]);

        $mensajeRoles            = "✅ Roles y permisos actualizados correctamente.";
        $rolesConfig             = $rolesActualizados;
        $permisosConfig          = $permisosActualizados;
        $permisosModuloRolConfig = $permisosModuloRolActualizados;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración - Jaguata</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL; ?>/public/assets/css/jaguata-theme.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<?php include __DIR__ . '/../../src/Templates/SidebarAdmin.php'; ?>

<main>
    <div class="header-box header-configuracion">
        <div>
            <h1>Configuración del Sistema</h1>
            <p>Personalizá parámetros globales, suscripción, roles y permisos ⚙️</p>
        </div>
        <a href="<?= h($baseFeatures); ?>/Dashboard.php" class="btn btn-outline-light">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (!empty($mensajeConfig)): ?>
        <div class="alert alert-success text-center fw-semibold"><?= h($mensajeConfig) ?></div>
    <?php endif; ?>

    <?php if (!empty($mensajeRoles)): ?>
        <div class="alert alert-success text-center fw-semibold"><?= h($mensajeRoles) ?></div>
    <?php endif; ?>

    <!-- ================= FORM CONFIGURACIÓN GENERAL + SUSCRIPCIÓN + SISTEMA ================= -->
    <form method="post">
        <input type="hidden" name="tipo_form" value="sistema">

        <!-- General -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-gears me-2"></i>Configuración General
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Nombre del sistema</label>
                    <input type="text" name="nombre_sistema" class="form-control"
                           value="<?= h($config['nombre_sistema']) ?>">
                </div>
                <div class="col-md-6">
                    <label>Correo de soporte</label>
                    <input type="email" name="correo_soporte" class="form-control"
                           value="<?= h($config['correo_soporte']) ?>">
                </div>
            </div>
        </div>

        <!-- ✅ Suscripción mensual (reemplaza comisión/tarifa) -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-crown me-2"></i>Suscripción Mensual
            </div>

            <p class="text-muted mb-3">
                El sistema ya no usa comisión por transacción. La monetización se maneja mediante suscripción mensual.
            </p>

            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label>Monto mensual (₲)</label>
                    <input type="number" min="0" name="suscripcion_mensual_monto" class="form-control"
                           value="<?= h($config['suscripcion_mensual_monto']) ?>">
                </div>

                <div class="col-md-4">
                    <label>Suscripción activa</label><br>
                    <label class="switch">
                        <input type="checkbox" name="suscripcion_estado" value="1"
                            <?= !empty($config['suscripcion_estado']) && $config['suscripcion_estado'] !== '0' ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                    <small class="text-muted d-block mt-1">
                        Si está desactivada, el sistema puede bloquear funciones premium (según tu lógica).
                    </small>
                </div>

                <div class="col-md-4">
                    <label>Detalle / nota</label>
                    <input type="text" name="suscripcion_detalle" class="form-control"
                           value="<?= h($config['suscripcion_detalle']) ?>"
                           placeholder="Ej: Incluye acceso a reportes y gestión completa.">
                </div>
            </div>
        </div>

        <!-- Técnica -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-shield-halved me-2"></i>Configuración del Sistema
            </div>
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <label>Modo mantenimiento</label><br>
                    <label class="switch">
                        <input type="checkbox" name="modo_mantenimiento" value="1"
                            <?= !empty($config['modo_mantenimiento']) ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                    <small class="text-muted d-block mt-1">
                        Cuando está activo, solo administradores pueden ingresar.
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <button type="submit" class="btn-guardar">
                        <i class="fas fa-save me-2"></i>Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- ================= SEGURIDAD DE LA CUENTA (CAMBIO DE CONTRASEÑA) ================= -->
    <div class="section-card">
        <div class="section-header">
            <i class="fas fa-lock me-2"></i>Seguridad de la Cuenta
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label>Contraseña actual</label>
                <input type="password" id="passActual" class="form-control">
            </div>
            <div class="col-md-4">
                <label>Nueva contraseña</label>
                <input type="password" id="passNueva" class="form-control">
            </div>
            <div class="col-md-4">
                <label>Confirmar nueva contraseña</label>
                <input type="password" id="passConfirm" class="form-control">
            </div>
        </div>
        <div class="text-end mt-3">
            <button id="btnCambiarPass" class="btn-guardar" type="button">
                <i class="fas fa-key me-2"></i>Actualizar contraseña
            </button>
        </div>
    </div>

    <!-- ================= ROLES Y PERMISOS ================= -->
    <form method="post">
        <input type="hidden" name="tipo_form" value="roles">

        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-user-lock me-2"></i>Roles del Sistema
            </div>

            <p class="text-muted mb-3">
                Activá o desactivá los roles disponibles en Jaguata. Los nombres están fijos (admin, paseador, dueño, soporte).
            </p>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tablaRoles">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th class="text-center">Activo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rolesConfig as $r): ?>
                        <tr>
                            <td><strong>#<?= h((string)$r['id']) ?></strong></td>
                            <td><?= h($r['nombre']) ?></td>
                            <td><?= h($r['descripcion']) ?></td>
                            <td class="text-center">
                                <input type="checkbox"
                                       name="rol_estado[<?= (int)$r['id'] ?>]"
                                    <?= ($r['estado'] === 'activo') ? 'checked' : '' ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-key me-2"></i>Permisos por módulo (por rol)
            </div>

            <p class="text-muted mb-3">
                Configurá qué módulos puede ver cada rol dentro del sistema (admin, paseador y dueño).
            </p>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                    <tr>
                        <th style="width: 25%;">Módulo</th>
                        <th class="text-center">Admin</th>
                        <th class="text-center">Paseador</th>
                        <th class="text-center">Dueño</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($modulosDefault as $modulo): ?>
                        <?php
                        $confModulo = $permisosModuloRolConfig[$modulo] ?? [];
                        $adminChecked    = !empty($confModulo['admin']);
                        $paseadorChecked = !empty($confModulo['paseador']);
                        $duenoChecked    = !empty($confModulo['dueno']);
                        ?>
                        <tr>
                            <td><strong><?= h($modulo) ?></strong></td>
                            <td class="text-center">
                                <input type="checkbox"
                                       name="perm_rol[<?= h($modulo) ?>][admin]"
                                    <?= $adminChecked ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="checkbox"
                                       name="perm_rol[<?= h($modulo) ?>][paseador]"
                                    <?= $paseadorChecked ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="checkbox"
                                       name="perm_rol[<?= h($modulo) ?>][dueno]"
                                    <?= $duenoChecked ? 'checked' : '' ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn-guardar">
                    <i class="fas fa-save me-2"></i>Guardar roles y permisos
                </button>
            </div>
        </div>
    </form>

    <footer><small>© <?= date('Y') ?> Jaguata — Panel de Administración</small></footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const BASE_URL = '<?= BASE_URL; ?>';

    // 🔐 Cambio de contraseña
    const btnCambiarPass = document.getElementById('btnCambiarPass');
    if (btnCambiarPass) {
        btnCambiarPass.addEventListener('click', async () => {
            const passActual = document.getElementById('passActual').value.trim();
            const passNueva = document.getElementById('passNueva').value.trim();
            const passConfirm = document.getElementById('passConfirm').value.trim();

            if (!passActual || !passNueva || !passConfirm) {
                Swal.fire('Campos incompletos', 'Debes completar todos los campos.', 'warning');
                return;
            }

            if (passNueva !== passConfirm) {
                Swal.fire('Contraseñas no coinciden', 'La nueva contraseña y su confirmación deben ser iguales.', 'error');
                return;
            }

            try {
                const resp = await fetch(`${BASE_URL}/public/api/cambiarPassword.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        pass_actual: passActual,
                        pass_nueva: passNueva,
                        pass_confirm: passConfirm
                    })
                });

                const data = await resp.json();

                if (data.success) {
                    Swal.fire('Listo', 'Tu contraseña fue actualizada correctamente.', 'success');
                    document.getElementById('passActual').value = '';
                    document.getElementById('passNueva').value = '';
                    document.getElementById('passConfirm').value = '';
                } else {
                    Swal.fire('Error', data.error || 'No se pudo actualizar la contraseña.', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Ocurrió un error inesperado al actualizar la contraseña.', 'error');
            }
        });
    }
</script>

</body>
</html>
