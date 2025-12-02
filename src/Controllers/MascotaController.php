<?php

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Models/Mascota.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Models\Mascota;

AppConfig::init();

class MascotaController
{
    private Mascota $mascotaModel;

    public function __construct()
    {
        $this->mascotaModel = new Mascota();
    }

    /**
     * Listado general de mascotas (admin)
     */
    public function index(): array
    {
        return $this->mascotaModel->all();
    }

    /**
     * Mascotas del dueño logueado
     */
    public function indexByDuenoActual(): array
    {
        $duenoId = Session::getUsuarioId();
        if (!$duenoId) {
            return [];
        }
        return $this->mascotaModel->getByDueno($duenoId);
    }
}
