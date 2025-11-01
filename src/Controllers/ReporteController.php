<?php

namespace Jaguata\Controllers;

use Jaguata\Services\DatabaseService;
use PDO;
use PDOException;

class ReporteController
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * 🔹 Obtiene estadísticas generales del sistema
     */
    public function getEstadisticas(): array
    {
        try {
            // Total de usuarios
            $usuariosTotal = (int)$this->db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

            // Total de paseos
            $paseosTotal = (int)$this->db->query("SELECT COUNT(*) FROM paseos")->fetchColumn();

            // Paseos completados
            $paseosCompletos = (int)$this->db->query("SELECT COUNT(*) FROM paseos WHERE estado = 'completo'")->fetchColumn();

            // Paseos pendientes
            $paseosPendientes = (int)$this->db->query("SELECT COUNT(*) FROM paseos WHERE estado IN ('solicitado', 'confirmado', 'en_curso')")->fetchColumn();

            // Ingresos totales (suma de precio_total de paseos completados)
            $ingresosTotales = (float)$this->db->query("SELECT COALESCE(SUM(precio_total), 0) FROM paseos WHERE estado = 'completo'")->fetchColumn();

            // Usuarios por rol
            $roles = [];
            $stmtRoles = $this->db->query("SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol");
            foreach ($stmtRoles->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $roles[$r['rol']] = (int)$r['total'];
            }

            // Paseos por día (últimos 7 días)
            $paseosPorDia = [];
            $stmtDias = $this->db->query("
                SELECT 
                    DATE_FORMAT(inicio, '%a') AS dia,
                    COUNT(*) AS total
                FROM paseos
                WHERE inicio >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE_FORMAT(inicio, '%a')
                ORDER BY DATE(inicio)
            ");
            foreach ($stmtDias->fetchAll(PDO::FETCH_ASSOC) as $d) {
                $paseosPorDia[$d['dia']] = (int)$d['total'];
            }

            // Ingresos por mes (últimos 6 meses)
            $ingresosPorMes = [];
            $stmtMes = $this->db->query("
                SELECT 
                    DATE_FORMAT(inicio, '%b') AS mes,
                    SUM(precio_total) AS total
                FROM paseos
                WHERE estado = 'completo'
                AND inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(inicio, '%b')
                ORDER BY MIN(inicio)
            ");
            foreach ($stmtMes->fetchAll(PDO::FETCH_ASSOC) as $m) {
                $ingresosPorMes[$m['mes']] = (float)$m['total'];
            }

            return [
                'usuarios' => $usuariosTotal,
                'paseos_total' => $paseosTotal,
                'paseos_completos' => $paseosCompletos,
                'paseos_pendientes' => $paseosPendientes,
                'ingresos_totales' => $ingresosTotales,
                'roles' => $roles,
                'paseos_por_dia' => $paseosPorDia,
                'ingresos_por_mes' => $ingresosPorMes
            ];
        } catch (PDOException $e) {
            error_log("❌ Error en ReporteController::getEstadisticas(): " . $e->getMessage());
            return ['error' => 'Error al obtener estadísticas'];
        }
    }
}
