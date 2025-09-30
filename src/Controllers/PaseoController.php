<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Paseo;
use Jaguata\Helpers\Session;
use Exception;

class PaseoController
{
    private Paseo $paseoModel;

    public function __construct()
    {
        $this->paseoModel = new Paseo();
    }

    public function index()
    {
        return $this->paseoModel->allWithRelations();
    }

    public function store()
    {
        if (!Session::isLoggedIn()) {
            $_SESSION['error'] = "Debes iniciar sesión.";
            header("Location: " . BASE_URL . "/public/login.php");
            exit;
        }

        try {
            $data = [
                'mascota_id'   => $_POST['mascota_id'] ?? null,
                'paseador_id'  => $_POST['paseador_id'] ?? null,
                'inicio'       => $_POST['inicio'] ?? date('Y-m-d H:i:s'),
                'duracion'     => $_POST['duracion'] ?? 60,
                'precio_total' => $_POST['precio_total'] ?? 0,
            ];

            $this->paseoModel->create($data);
            $_SESSION['success'] = "Paseo solicitado con éxito.";
            header("Location: " . BASE_URL . "/features/dueno/MisPaseos.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al solicitar paseo: " . $e->getMessage();
            header("Location: " . BASE_URL . "/features/dueno/SolicitarPaseo.php");
            exit;
        }
    }
}
