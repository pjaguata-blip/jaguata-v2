<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;
use Jaguata\Models\Paseador;

AppConfig::init();

$RUTA_SELF = AppConfig::getBaseUrl() . '/registro.php';

// Redirigir si ya está logueado
if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRol() ?? 'dueno';
    header('Location: ' . AppConfig::getBaseUrl() . "/features/{$rol}/Dashboard.php");
    exit;
}

$error = Session::getError();
$success = Session::getSuccess();

// Carpeta de subida
$uploadDir = __DIR__ . '/../uploads/verificaciones/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// --- Procesar formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rol = $_POST['rol'] ?? 'dueno';
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['confirm_password'] ?? '';
    $errores = [];

    // Validaciones base
    if ($nombre === '' || mb_strlen($nombre) < 3) $errores[] = 'El nombre debe tener al menos 3 caracteres.';
    if (!Validaciones::validarEmail($email)) $errores[] = 'Correo electrónico inválido.';
    if ($pass !== $pass2) $errores[] = 'Las contraseñas no coinciden.';
    if (mb_strlen($pass) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if (empty($telefono)) $errores[] = 'El teléfono es obligatorio.';

    // Aceptación de bases y condiciones
    $aceptaCondiciones = isset($_POST['acepta_condiciones']);
    if (!$aceptaCondiciones) {
        $errores[] = 'Debes aceptar las Bases y Condiciones para continuar.';
    }

    $files = ['cedula_frente', 'cedula_dorso', 'selfie', 'antecedentes'];
    $uploads = [];

    // Archivos requeridos para paseadores
    if ($rol === 'paseador') {
        foreach ($files as $f) {
            if (empty($_FILES[$f]['name'])) {
                $errores[] = "El archivo de " . str_replace('_', ' ', $f) . " es obligatorio.";
            } else {
                $ext = strtolower(pathinfo($_FILES[$f]['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $errores[] = "Formato inválido para $f (solo JPG, PNG o PDF).";
                }
            }
        }
    }

    if (empty($errores)) {
        try {
            $usuarioModel = new Usuario();
            if ($usuarioModel->getByEmail($email)) {
                $errores[] = 'El correo ya está registrado.';
            } else {
                // Subir archivos
                foreach ($files as $f) {
                    if (!empty($_FILES[$f]['name'])) {
                        $filename = uniqid($f . '_') . '.' . strtolower(pathinfo($_FILES[$f]['name'], PATHINFO_EXTENSION));
                        move_uploaded_file($_FILES[$f]['tmp_name'], $uploadDir . $filename);
                        $uploads[$f] = $filename;
                    }
                }

                // Crear usuario pendiente
                $usuarioId = $usuarioModel->createUsuario([
                    'nombre' => $nombre,
                    'email' => $email,
                    'password' => $pass,
                    'rol' => $rol,
                    'telefono' => $telefono,
                    'estado' => 'pendiente',
                    'foto_cedula_frente' => $uploads['cedula_frente'] ?? null,
                    'foto_cedula_dorso' => $uploads['cedula_dorso'] ?? null,
                    'foto_selfie' => $uploads['selfie'] ?? null,
                    'certificado_antecedentes' => $uploads['antecedentes'] ?? null,
                    'acepto_terminos' => 1,
                    'fecha_aceptacion' => date('Y-m-d H:i:s'),
                    'ip_registro' => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);

                if ($rol === 'paseador') {
                    $paseadorModel = new Paseador();
                    $paseadorModel->create([
                        'paseador_id' => $usuarioId,
                        'nombre' => $nombre,
                        'disponible' => 0,
                        'calificacion' => 0,
                        'total_paseos' => 0,
                    ]);
                }

                Session::setSuccess('¡Tu cuenta fue registrada! El administrador revisará tus documentos 🐾');
                header('Location: ' . AppConfig::getBaseUrl() . '/login.php');
                exit;
            }
        } catch (Throwable $e) {
            $errores[] = 'Ocurrió un error al registrar la cuenta.';
        }
    }

    if (!empty($errores)) {
        Session::setError(implode(' | ', $errores));
        header('Location: ' . $RUTA_SELF);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #3c6255 0%, #20c997 100%);
            font-family: "Poppins", sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0 1rem;
        }

        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background-color: #ffffff;
            padding: 2rem;
            width: 100%;
            max-width: 720px;
            margin: auto;
            animation: fadeIn 0.7s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-circle {
            width: 90px;
            height: 90px;
            background-color: #f5f7fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin: 0 auto 1.5rem;
        }

        h2 {
            color: #3c6255;
            font-weight: 700;
        }

        .btn-primary,
        .btn-success {
            background-color: #3c6255;
            border: none;
            transition: all .3s;
        }

        .btn-primary:hover,
        .btn-success:hover {
            background-color: #2f4e45;
        }

        .form-control:focus {
            border-color: #20c997;
            box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.25);
        }

        .hidden {
            display: none;
        }

        .preview-img {
            border-radius: 10px;
            max-height: 110px;
            object-fit: cover;
        }

        a {
            color: #3c6255;
            text-decoration: none;
        }

        a:hover {
            color: #20c997;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="text-center mb-3">
                <div class="logo-circle">
                    <img src="<?= AppConfig::getAssetsUrl(); ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="80">
                </div>
                <h2>Crear cuenta</h2>
                <p class="text-muted">Únete a la comunidad Jaguata</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= Validaciones::generarCSRF(); ?>">

                <div class="text-center mb-3">
                    <label class="form-label fw-semibold">Tipo de cuenta</label><br>
                    <input type="radio" class="btn-check" name="rol" id="dueno" value="dueno" checked>
                    <label for="dueno" class="btn btn-outline-success me-2"><i class="fas fa-paw me-1"></i> Dueño</label>

                    <input type="radio" class="btn-check" name="rol" id="paseador" value="paseador">
                    <label for="paseador" class="btn btn-outline-success"><i class="fas fa-walking me-1"></i> Paseador</label>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre completo</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" required placeholder="0981-123-456">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirmar contraseña</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <div id="paseadorExtra" class="hidden mt-4">
                    <hr>
                    <h6 class="fw-bold text-success mb-3">Documentos obligatorios para paseadores</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cédula (frente)</label>
                            <input type="file" name="cedula_frente" class="form-control" accept="image/*" required>
                            <img id="preview_frente" class="preview-img mt-2 w-100 hidden">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cédula (dorso)</label>
                            <input type="file" name="cedula_dorso" class="form-control" accept="image/*" required>
                            <img id="preview_dorso" class="preview-img mt-2 w-100 hidden">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selfie con cédula</label>
                            <input type="file" name="selfie" class="form-control" accept="image/*" required>
                            <img id="preview_selfie" class="preview-img mt-2 w-100 hidden">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Certificado de antecedentes</label>
                            <input type="file" name="antecedentes" class="form-control" accept=".pdf,image/*" required>
                        </div>
                    </div>
                </div>

                <!-- Checkbox de Bases y Condiciones -->
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="acepta_condiciones" name="acepta_condiciones" required>
                    <label class="form-check-label" for="acepta_condiciones">
                        Acepto las
                        <a href="<?= AppConfig::getBaseUrl(); ?>/bases-y-condiciones.php" target="_blank" class="fw-semibold text-success">
                            Bases y Condiciones
                        </a>
                        de uso de Jaguata.
                    </label>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus me-2"></i> Crear cuenta
                    </button>
                </div>

                <p class="text-center mt-3 mb-0">
                    ¿Ya tienes cuenta? <a href="<?= AppConfig::getBaseUrl(); ?>/login.php" class="fw-bold">Inicia sesión</a>
                </p>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dueno = document.getElementById('dueno');
        const paseador = document.getElementById('paseador');
        const extra = document.getElementById('paseadorExtra');
        dueno.addEventListener('change', () => extra.classList.add('hidden'));
        paseador.addEventListener('change', () => extra.classList.remove('hidden'));

        function previewFile(input, id) {
            const file = input.files[0];
            const preview = document.getElementById(id);
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
            }
        }

        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', () => {
                const id = 'preview_' + input.name.split('_')[1];
                if (document.getElementById(id)) previewFile(input, id);
            });
        });
    </script>
</body>

</html>