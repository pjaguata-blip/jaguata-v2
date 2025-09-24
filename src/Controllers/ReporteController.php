<?php
namespace Jaguata\Controllers;

use Jaguata\Helpers\Session;
use Jaguata\Models\Reporte;
use Exception;

class ReporteController
{
    private Reporte $reporteModel;

    public function __construct()
    {
        $this->reporteModel = new Reporte();
    }

    /**
     * Obtener estadísticas generales
     * Ejemplo: total de paseos, pagos procesados, usuarios registrados
     */
    public function getEstadisticas(): array
    {
        try {
            return $this->reporteModel->getEstadisticas();
        } catch (Exception $e) {
            error_log("Error en getEstadisticas: " . $e->getMessage());
            return ['error' => 'No se pudieron obtener las estadísticas'];
        }
    }

    /**
     * Reporte de ganancias del paseador actual
     */
    public function getGanancias(): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $paseadorId = Session::getUsuarioId();
            return $this->reporteModel->getGananciasPorPaseador($paseadorId);
        } catch (Exception $e) {
            error_log("Error en getGanancias: " . $e->getMessage());
            return ['error' => 'No se pudo obtener el reporte de ganancias'];
        }
    }

    /**
     * Reporte de actividades por rol (dueño/paseador/admin)
     */
    public function getActividadesPorRol(): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $rol = Session::getUsuarioRol();
            return $this->reporteModel->getActividadesPorRol($rol);
        } catch (Exception $e) {
            error_log("Error en getActividadesPorRol: " . $e->getMessage());
            return ['error' => 'No se pudo generar el reporte de actividades'];
        }
    }
}
