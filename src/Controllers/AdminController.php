<?php

namespace Jaguata\Controllers;

use Jaguata\Helpers\Session;

class AdminController
{
    public function index()
    {
        if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
            header('Location: /login.php');
            exit;
        }

        $view = $_GET['view'] ?? 'dashboard';
        $path = __DIR__ . "/../Views/admin/{$view}.php";

        if (!file_exists($path)) {
            $path = __DIR__ . '/../Views/admin/dashboard.php';
        }

        include __DIR__ . '/../Views/admin/layout.php';
    }
}
