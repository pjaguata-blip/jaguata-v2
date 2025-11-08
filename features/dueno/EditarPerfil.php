<?php
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';
require_once __DIR__ . '/../../src/Models/Usuario.php';
require_once __DIR__ . '/../../src/Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;

AppConfig::init();

$auth = new AuthController();
$auth->checkRole('dueno');

$usuarioModel = new Usuario();
$usuarioId = Session::get('usuario_id');
$usuario = $usuarioModel->getById($usuarioId);
if (!$usuario) exit('Usuario no encontrado.');

// Catálogos (abreviado)
$DEPARTAMENTOS = ['Central', 'Alto Paraná', 'Itapúa', 'Cordillera', 'Guairá', 'Caaguazú'];
$CIUDADES = [
    'Central' => ['Asunción', 'San Lorenzo', 'Luque', 'Otra'],
    'Alto Paraná' => ['Ciudad del Este', 'Minga Guazú', 'Otra']
];

// Datos actuales
$nombre = $usuario['nombre'] ?? '';
$email = $usuario['email'] ?? '';
$telefono = $usuario['telefono'] ?? '';
$direccion = $usuario['direccion'] ?? '';
$depActual = $usuario['departamento'] ?? '';
$ciudadAct = $usuario['ciudad'] ?? '';
$fotoActual = $usuario['foto_perfil'] ?? '';
$msg = $error = '';

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $depActual = trim($_POST['departamento'] ?? '');
    $ciudadAct = trim($_POST['ciudad'] ?? '');
    if ($nombre === '' || $email === '') {
        $error = 'Nombre y correo obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo inválido.';
    } else {
        $fotoNueva = null;
        if (!empty($_FILES['foto']['name'])) {
            $file = $_FILES['foto'];
            $permit = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $mime = mime_content_type($file['tmp_name'] ?? '');
            if (isset($permit[$mime])) {
                $ext = $permit[$mime];
                $dir = __DIR__ . '/../../assets/uploads/perfiles';
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                $fname = 'u' . $usuarioId . '-' . date('YmdHis') . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $dir . '/' . $fname);
                $fotoNueva = '/assets/uploads/perfiles/' . $fname;
            }
        }
        $data = [
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'departamento' => $depActual,
            'ciudad' => $ciudadAct
        ];
        if ($fotoNueva) $data['foto_perfil'] = $fotoNueva;
        if ($usuarioModel->updateUsuario((int)$usuarioId, $data)) {
            $msg = 'Perfil actualizado correctamente.';
            $usuario = $usuarioModel->getById($usuarioId);
            $fotoActual = $usuario['foto_perfil'] ?? '';
        } else {
            $error = 'No se pudo guardar los cambios.';
        }
    }
}

$rolMenu = Session::getUsuarioRol() ?: 'dueno';
$baseFeatures = BASE_URL . "/features/{$rolMenu}";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Editar Perfil - Jaguata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Fuente para unificar tipografía con el resto -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: "Poppins", sans-serif;
            margin: 0;
        }

        .layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e1e2f 0%, #292a3a 100%);
            color: #f8f9fa;
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 1.5rem;
            box-shadow: 4px 0 12px rgba(0, 0, 0, .15);
            z-index: 1000;
            transition: transform .3s;
        }

        .sidebar .nav-link {
            color: #ddd;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            margin: 4px 8px;
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
        }

        .sidebar .nav-link:hover {
            background: #343454;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: #3c6255;
            color: #fff;
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            background: #1e1e2f;
            color: #fff;
            border: 0;
            padding: 8px 10px;
            border-radius: 6px;
            z-index: 1100;
        }

        @media(max-width:768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }

        main.content {
            flex-grow: 1;
            margin-left: 240px;
            padding: 2.5rem;
            width: calc(100% - 240px);
        }

        @media(max-width:768px) {
            main.content {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
            }
        }

        .welcome-box {
            background: linear-gradient(90deg, #20c997, #3c6255);
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
            margin-bottom: 2rem;
        }

        .welcome-box h1 {
            font-weight: 600;
            margin: 0;
            font-size: 1.6rem;
        }

        .card-premium {
            border: none;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .05);
            background: #fff;
        }

        .form-label {
            font-weight: 600;
            color: #3c6255;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #3c6255, #20c997);
            border: none;
            color: #fff;
            font-weight: 500;
        }

        .btn-gradient:hover {
            opacity: .9;
        }

        /* Botón volver arriba (opcional, como el resto) */
        #btnTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #3c6255, #20c997);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
            display: none;
            z-index: 1000;
        }

        #btnTop:hover {
            opacity: .9;
        }
    </style>
</head>

<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="layout">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../src/Templates/SidebarDueno.php'; ?>


        <!-- Contenido -->
        <main class="content">
            <div class="welcome-box mb-4">
                <div>
                    <h1>Editar Perfil</h1>
                    <p class="mb-0">Actualizá tus datos personales y de contacto 🐾</p>
                </div>
                <div class="d-flex align-items-center gap-2">

                    <!-- 👉 Botón VOLVER unificado como en las otras pantallas -->
                    <a href="<?= $baseFeatures; ?>/MiPerfil.php" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card-premium text-center p-3">
                            <?php $src = $fotoActual ? (BASE_URL . $fotoActual) : (ASSETS_URL . '/images/user-placeholder.png'); ?>
                            <img id="previewFoto" src="<?= htmlspecialchars($src) ?>" class="rounded-circle mb-3" style="width:150px;height:150px;object-fit:cover;">
                            <div class="text-start">
                                <label class="form-label">Foto de perfil</label>
                                <input type="file" class="form-control mb-3" name="foto" accept="image/*" id="foto">
                                <label class="form-label">Nombre completo</label>
                                <input type="text" class="form-control mb-3" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control mb-3" name="email" value="<?= htmlspecialchars($email) ?>" required>
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars($telefono) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card-premium p-4">
                            <h5 class="mb-3"><i class="fas fa-map-marker-alt me-2 text-success"></i>Dirección</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Departamento</label>
                                    <select class="form-select" name="departamento">
                                        <option value="">Seleccione…</option>
                                        <?php foreach ($DEPARTAMENTOS as $d): ?>
                                            <option value="<?= $d ?>" <?= $d === $depActual ? 'selected' : '' ?>><?= $d ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Ciudad</label>
                                    <select class="form-select" name="ciudad">
                                        <option value="">Seleccione…</option>
                                        <?php foreach (($CIUDADES[$depActual] ?? []) as $c): ?>
                                            <option value="<?= $c ?>" <?= $c === $ciudadAct ? 'selected' : '' ?>><?= $c ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Referencia / Dirección</label>
                                    <input type="text" class="form-control" name="direccion" value="<?= htmlspecialchars($direccion) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-gradient px-4 py-2">
                        <i class="fas fa-save me-1"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </main>
    </div>

    <!-- Botón volver arriba (igual que en otras) -->
    <button id="btnTop" title="Volver arriba"><i class="fas fa-arrow-up"></i></button>

    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        toggle?.addEventListener('click', () => sidebar.classList.toggle('show'));

        // Preview foto
        document.getElementById('foto')?.addEventListener('change', e => {
            const f = e.target.files?.[0];
            if (!f) return;
            document.getElementById('previewFoto').src = URL.createObjectURL(f);
        });

        // Mostrar/ocultar botón to-top
        const btnTop = document.getElementById("btnTop");
        window.addEventListener("scroll", () => {
            btnTop.style.display = window.scrollY > 200 ? "block" : "none";
        });
        btnTop.addEventListener("click", () => window.scrollTo({
            top: 0,
            behavior: "smooth"
        }));
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>