<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;

AppConfig::init();

// Si el usuario ya está logueado, enviarlo a su dashboard
if (Session::isLoggedIn()) {
    $rol = Session::getUsuarioRol();
    header('Location: ' . BASE_URL . "/features/{$rol}/Dashboard.php");
    exit;
}

// Si no está logueado, mostrar la página "Sobre Nosotros" como inicio
header('Location: ' . BASE_URL . '/sobre_nosotros.php');
exit;
