<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Controllers/AdminController.php';
require_once __DIR__ . '/../src/Helpers/Session.php';

use Jaguata\Controllers\AdminController;
use Jaguata\Helpers\Session;

// ❌ NO llames a Session::start(); — es privada y no hace falta

if (!Session::isLoggedIn()) {
    header('Location: /jaguata/public/login.php');
    exit;
}

$rol = Session::getUsuarioRol();
if ($rol !== 'admin') {
    header('Location: /jaguata/public/login.php?error=unauthorized');
    exit;
}

$controller = new AdminController();
$controller->index();
