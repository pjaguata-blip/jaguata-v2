<?php

namespace Jaguata\Helpers;

class AuthHelper
{
    public static function checkRole($requiredRole)
    {
        session_start();
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $requiredRole) {
            header("Location: /public/login.php");
            exit();
        }
    }
}
