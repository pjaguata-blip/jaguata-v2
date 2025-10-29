<?php

namespace Jaguata\Controllers;

use Jaguata\Helpers\Session;

class AdminController
{
    /**
     * Carga la vista principal del panel de administración.
     * Determina la subvista según el parámetro "view" (por ejemplo: ?view=usuarios)
     */
    public function index(): void
    {
        // --- 🔒 Verificación de sesión y rol ---
        if (!Session::isLoggedIn() || strtolower(Session::getUsuarioRol() ?? '') !== 'admin') {
            header('Location: /jaguata/public/login.php?error=unauthorized');
            exit;
        }

        // --- 📁 Definir rutas base ---
        $viewsPath = dirname(__DIR__) . "/Views/admin/";
        $defaultView = $viewsPath . "dashboard.php";

        // --- 🧩 Sanitizar parámetro de vista ---
        $view = $_GET['view'] ?? 'dashboard';
        $view = preg_replace('/[^a-zA-Z0-9_-]/', '', $view);

        $targetView = "{$viewsPath}{$view}.php";
        if (!file_exists($targetView)) {
            $targetView = $defaultView;
        }

        // --- 👤 Datos del usuario admin ---
        $usuario = [
            'nombre' => Session::getUsuarioNombre() ?? 'Administrador',
            'email'  => Session::getUsuarioEmail() ?? 'admin@jaguata.com',
            'rol'    => Session::getUsuarioRol() ?? 'admin',
        ];
    }
}
