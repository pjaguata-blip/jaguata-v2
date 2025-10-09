<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Paseo;
use Jaguata\Models\Usuario;
use Jaguata\Helpers\Session;
use Exception;

class PaseoController
{
    private Paseo $paseoModel;
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->paseoModel = new Paseo();
        $this->usuarioModel = new Usuario();
    }

    // Obtener paseo por ID (nuevo método)
    public function getPaseoById(int $id)
    {
        return $this->paseoModel->findWithRelations($id);
    }

    // Listar todos los paseos
    public function index()
    {
        return $this->paseoModel->allWithRelations();
    }

    public function show($id)
    {
        return $this->paseoModel->find($id);
    }

    public function store()
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $data = [
            'mascota_id'   => (int)($_POST['mascota_id'] ?? 0),
            'paseador_id'  => (int)($_POST['paseador_id'] ?? 0),
            'inicio'       => $_POST['inicio'] ?? '',
            'duracion'     => (int)($_POST['duracion'] ?? 0),
            'precio_total' => (float)($_POST['precio_total'] ?? 0)
        ];

        return ['id' => $this->paseoModel->create($data)];
    }

    public function update($id)
    {
        $data = [
            'inicio'       => $_POST['inicio'] ?? '',
            'duracion'     => (int)($_POST['duracion'] ?? 0),
            'precio_total' => (float)($_POST['precio_total'] ?? 0),
        ];
        return $this->paseoModel->update($id, $data);
    }

    public function destroy($id)
    {
        return $this->paseoModel->delete($id);
    }

    public function confirmar($id)
    {
        return $this->paseoModel->cambiarEstado($id, 'confirmado');
    }

    public function apiCompletar($id)
    {
        $resultado = $this->paseoModel->cambiarEstado($id, 'completado');

        if ($resultado) {
            $paseo = $this->paseoModel->find($id);
            if ($paseo && isset($paseo['dueno_id'])) {
                $this->usuarioModel->sumarPuntos((int)$paseo['dueno_id'], 10);
            }
        }

        return $resultado;
    }

    public function indexByDueno(int $duenoId)
    {
        return $this->paseoModel->findByDueno($duenoId);
    }

    public function indexForPaseador(int $paseadorId): array
    {
        return $this->paseoModel->findByPaseador($paseadorId);
    }

    public function getSolicitudesPendientes(int $paseadorId): array
    {
        return $this->paseoModel->findSolicitudesPendientes($paseadorId);
    }

    public function getGananciasPorPaseador(int $paseadorId): float
    {
        $paseos = $this->paseoModel->getByPaseador($paseadorId);
        $total = 0;
        foreach ($paseos as $p) {
            if ($p['estado'] === 'completo') {
                $total += $p['precio_total'];
            }
        }
        return $total;
    }
}
