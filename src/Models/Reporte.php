<?php

namespace Jaguata\Models;

use PDO;
use PDOException;

class Reporte
{
    private PDO $db;

    public function __construct()
    {
        // Usamos la conexión global que hayas definido en AppConfig
        $this->db = $GLOBALS['db'] ?? null;

        if (!$this->db instanceof PDO) {
            throw new PDOException("No hay conexión a la base de datos en Reporte.php");
        }
    }
    public function getEstadisticas(): array
    {
        try {
            $sql = "
                SELECT 
                    (SELECT COUNT(*) FROM usuarios) AS total_usuarios,
                    (SELECT COUNT(*) FROM mascotas) AS total_mascotas,
                    (SELECT COUNT(*) FROM paseos) AS total_paseos,
                    (SELECT COUNT(*) FROM pagos WHERE estado = 'procesado') AS pagos_exitosos,
                    (SELECT COUNT(*) FROM pagos WHERE estado = 'fallido') AS pagos_fallidos
            ";
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getEstadisticas: " . $e->getMessage());
            return ['error' => 'Error al obtener estadísticas'];
        }
    }

    public function getGananciasPorPaseador(int $paseadorId): array
    {
        try {
            $sql = "
                SELECT 
                    COUNT(*) AS total_paseos,
                    SUM(p.monto) AS total_ganado
                FROM pagos p
                INNER JOIN paseos ps ON ps.id = p.paseo_id
                WHERE ps.paseador_id = :paseador_id
                  AND p.estado = 'procesado'
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':paseador_id' => $paseadorId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getGananciasPorPaseador: " . $e->getMessage());
            return ['error' => 'Error al obtener reporte de ganancias'];
        }
    }

    public function getActividadesPorRol(string $rol): array
    {
        try {
            switch ($rol) {
                case 'dueno':
                    $sql = "
                        SELECT 
                            COUNT(*) AS paseos_Pendientes,
                            SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) AS paseos_completados
                        FROM paseos
                        WHERE dueno_id IS NOT NULL
                    ";
                    break;

                case 'paseador':
                    $sql = "
                        SELECT 
                            COUNT(*) AS paseos_asignados,
                            SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) AS paseos_completados
                        FROM paseos
                        WHERE paseador_id IS NOT NULL
                    ";
                    break;

                case 'admin':
                    $sql = "
                        SELECT 
                            (SELECT COUNT(*) FROM usuarios) AS total_usuarios,
                            (SELECT COUNT(*) FROM paseos) AS total_paseos,
                            (SELECT COUNT(*) FROM pagos) AS total_pagos
                    ";
                    break;

                default:
                    return ['error' => 'Rol no reconocido'];
            }

            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error en getActividadesPorRol: " . $e->getMessage());
            return ['error' => 'Error al obtener reporte por rol'];
        }
    }
}
