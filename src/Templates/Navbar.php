<?php
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Services/NotificacionService.php';

use Jaguata\Helpers\Session;
use Jaguata\Services\NotificacionService;

$usuarioLogueado = Session::isLoggedIn();
$rolUsuario = Session::getUsuarioRol();
$nombreUsuario = Session::getUsuarioNombre();
$fotoUsuario = Session::get('usuario_foto');

// 🔹 URL dinámica de inicio
$inicioUrl = BASE_URL . "/index.php";
if ($usuarioLogueado && $rolUsuario) {
    $inicioUrl = BASE_URL . "/features/{$rolUsuario}/Dashboard.php";
}
?>



<style>
    /* --- Estilo coherente con el Dashboard --- */
    .navbar-panel {
        background: #fff;
        border-radius: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .navbar-panel .form-control {
        border-radius: 10px 0 0 10px;
        border: 1px solid #dcdcdc;
        box-shadow: none;
    }

    .navbar-panel .btn-success {
        background: #3c6255;
        border: none;
        border-radius: 0 10px 10px 0;
    }

    .navbar-panel .btn-success:hover {
        background: #2e4d44;
    }

    .navbar-panel .dropdown-menu {
        border-radius: 12px;
        font-size: 0.95rem;
    }

    .navbar-panel .dropdown-item:hover {
        background-color: #f0f3f0;
    }
</style>