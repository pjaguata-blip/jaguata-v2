<?php
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Helpers/Session.php';

use Jaguata\Helpers\Session;
use Jaguata\Config\AppConfig;

// Inicializar configuración
AppConfig::init();

// Cerrar sesión
Session::logout();

// Redirigir al login con mensaje
header('Location: ' . BASE_URL . '/login.php?mensaje=sesion_cerrada');
exit;
?>
