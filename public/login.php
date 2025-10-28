<?php
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Helpers/Validaciones.php';
require_once __DIR__ . '/../src/Models/Usuario.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Helpers\Validaciones;
use Jaguata\Models\Usuario;

AppConfig::init();

// Redirigir si ya está logueado
if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRol();
    if ($usuario) {
        $usuario['remember_me'] = $remember_me;
        Session::login($usuario);

        if ($usuario['rol'] === 'admin') {
            header('Location: ' . BASE_URL . '/public/admin.php');
        } else {
            header('Location: ' . BASE_URL . "/features/{$usuario['rol']}/Dashboard.php");
        }
        exit;
    }

    exit;
}

$titulo = 'Iniciar Sesión - Jaguata';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    $validEmail = Validaciones::validarEmail($email);
    $validPass  = Validaciones::validarPassword($password);

    if (!$validEmail['valido']) {
        $error = $validEmail['mensaje'];
    } elseif (!$validPass['valido']) {
        $error = $validPass['mensaje'];
    } else {
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->authenticate($email, $password);

        if ($usuario) {
            $usuario['remember_me'] = $remember_me;
            Session::login($usuario);
            header('Location: ' . BASE_URL . "/features/{$usuario['rol']}/Dashboard.php");
            exit;
        } else {
            $error = 'Credenciales incorrectas. Verifica tu email y contraseña.';
        }
    }
}

$error = $error ?: Session::getError();
$success = $success ?: Session::getSuccess();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
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
            color: #333;
        }

        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background-color: #ffffff;
            padding: 2rem;
        }

        h2 {
            color: #3c6255;
            font-weight: 700;
        }

        .btn-primary {
            background-color: #3c6255;
            border: none;
            transition: all .3s;
        }

        .btn-primary:hover {
            background-color: #2f4e45;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 0.8rem 1rem;
            transition: all .3s;
        }

        .form-control:focus {
            border-color: #20c997;
            box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.25);
        }

        .form-check-input:checked {
            background-color: #3c6255;
            border-color: #3c6255;
        }

        a {
            color: #3c6255;
            text-decoration: none;
        }

        a:hover {
            color: #20c997;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .footer-icons i {
            color: #3c6255;
        }

        .footer-icons small {
            color: #555;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background-color: #f5f7fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 0 auto 1rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="text-center mb-4">
                        <div class="logo-circle">
                            <img src="<?= ASSETS_URL; ?>/uploads/perfiles/logojag.png" alt="Jaguata" width="100">
                        </div>
                        <h2>Bienvenido a Jaguata 🐾</h2>
                        <p class="text-muted">Inicia sesión para continuar</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= Validaciones::generarCSRF(); ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="fas fa-envelope me-1"></i>Email</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>" placeholder="tu@email.com">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="fas fa-lock me-1"></i>Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">Recordar mi sesión</label>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión</button>
                        </div>

                        <div class="text-center">
                            <p class="mb-2"><a href="<?= BASE_URL ?>/recuperar-password.php">¿Olvidaste tu contraseña?</a></p>
                            <p>¿No tienes cuenta? <a href="<?= BASE_URL ?>/registro.php" class="fw-bold">Regístrate aquí</a></p>
                        </div>
                    </form>
                </div>

                <div class="text-center mt-4 footer-icons">
                    <div class="row">
                        <div class="col-4">
                            <i class="fas fa-shield-alt fa-2x mb-2"></i>
                            <small>Seguro</small>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <small>24/7</small>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-star fa-2x mb-2"></i>
                            <small>Verificados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>
</body>

</html>