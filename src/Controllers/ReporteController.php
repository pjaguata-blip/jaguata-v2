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
     * ✅ ingresos_totales = suma de suscripciones (activa/vencida)
     */
    public function getEstadisticas(): array
    {
        try {
            // Total de usuarios
            $usuariosTotal = (int)$this->db->query("
                SELECT COUNT(*) FROM usuarios
            ")->fetchColumn();

            // Total de paseos
            $paseosTotal = (int)$this->db->query("
                SELECT COUNT(*) FROM paseos
            ")->fetchColumn();

            // Paseos completados
            $paseosCompletos = (int)$this->db->query("
                SELECT COUNT(*) 
                FROM paseos 
                WHERE estado IN ('completo', 'finalizado')
            ")->fetchColumn();

            // Paseos pendientes
            $paseosPendientes = (int)$this->db->query("
                SELECT COUNT(*) 
                FROM paseos 
                WHERE estado IN ('pendiente', 'solicitado', 'confirmado', 'en_curso')
            ")->fetchColumn();

            // ✅ Ingresos por paseos (si querés conservar este dato)
            $ingresosPaseos = (float)$this->db->query("
                SELECT COALESCE(SUM(precio_total), 0) 
                FROM paseos 
                WHERE estado IN ('completo', 'finalizado')
            ")->fetchColumn();

            // ✅ Ingresos por suscripciones (esto será el “ingresos_totales”)
            $ingresosSuscripciones = 0.0;
            $cantSuscripcionesPagas = 0;

            try {
                $rowSubs = $this->db->query("
                    SELECT 
                        COALESCE(SUM(monto), 0) AS total,
                        COUNT(*) AS cant
                    FROM suscripciones
                    WHERE estado IN ('activa','vencida')
                ")->fetch(PDO::FETCH_ASSOC);

                $ingresosSuscripciones = (float)($rowSubs['total'] ?? 0);
                $cantSuscripcionesPagas = (int)($rowSubs['cant'] ?? 0);
            } catch (PDOException $e) {
                // Si la tabla aún no existe, no rompemos
                $ingresosSuscripciones = 0.0;
                $cantSuscripcionesPagas = 0;
            }

            // Usuarios por rol
            $roles = [];
            $stmtRoles = $this->db->query("
                SELECT rol, COUNT(*) AS total 
                FROM usuarios 
                GROUP BY rol
            ");
            foreach ($stmtRoles->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $roles[$r['rol']] = (int)$r['total'];
            }

            // 🔤 Traducciones EN -> ES
            $diasMap = [
                'Mon' => 'Lun',
                'Tue' => 'Mar',
                'Wed' => 'Mié',
                'Thu' => 'Jue',
                'Fri' => 'Vie',
                'Sat' => 'Sáb',
                'Sun' => 'Dom',
            ];

            $mesesMap = [
                'Jan' => 'Ene',
                'Feb' => 'Feb',
                'Mar' => 'Mar',
                'Apr' => 'Abr',
                'May' => 'May',
                'Jun' => 'Jun',
                'Jul' => 'Jul',
                'Aug' => 'Ago',
                'Sep' => 'Sep',
                'Oct' => 'Oct',
                'Nov' => 'Nov',
                'Dec' => 'Dic',
            ];

            // Paseos por día (últimos 7 días)
            $paseosPorDia = [];
            $stmtDias = $this->db->query("
                SELECT 
                    DATE(inicio) AS fecha,
                    DATE_FORMAT(inicio, '%a') AS dia_en,
                    COUNT(*) AS total
                FROM paseos
                WHERE inicio >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(inicio), DATE_FORMAT(inicio, '%a')
                ORDER BY fecha
            ");
            foreach ($stmtDias->fetchAll(PDO::FETCH_ASSOC) as $d) {
                $diaEn = $d['dia_en'];
                $diaEs = $diasMap[$diaEn] ?? $diaEn;
                $paseosPorDia[$diaEs] = (int)$d['total'];
            }

            // Ingresos por mes (últimos 6 meses) - sigue basado en paseos
            $ingresosPorMes = [];
            $stmtMes = $this->db->query("
                SELECT 
                    DATE_FORMAT(inicio, '%b') AS mes_en,
                    MIN(inicio) AS fecha_ref,
                    SUM(precio_total) AS total
                FROM paseos
                WHERE estado IN ('completo', 'finalizado')
                AND inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(inicio, '%b')
                ORDER BY fecha_ref
            ");
            foreach ($stmtMes->fetchAll(PDO::FETCH_ASSOC) as $m) {
                $mesEn = $m['mes_en'];
                $mesEs = $mesesMap[$mesEn] ?? $mesEn;
                $ingresosPorMes[$mesEs] = (float)$m['total'];
            }

            // Ingresos por paseador (por paseos completados)
            $ingresosPorPaseador = [];
            $stmtPaseadores = $this->db->query("
                SELECT 
                    u.nombre AS paseador,
                    COALESCE(SUM(p.precio_total), 0) AS total
                FROM paseos p
                JOIN usuarios u ON u.usu_id = p.paseador_id
                WHERE p.estado IN ('completo', 'finalizado')
                GROUP BY u.nombre
                ORDER BY total DESC
            ");
            foreach ($stmtPaseadores->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $ingresosPorPaseador[$row['paseador']] = (float)$row['total'];
            }

            return [
                'usuarios'                => $usuariosTotal,
                'paseos_total'            => $paseosTotal,
                'paseos_completos'        => $paseosCompletos,
                'paseos_pendientes'       => $paseosPendientes,

                // ✅ ESTE ES EL QUE USÁS EN EL DASHBOARD
                'ingresos_totales'        => $ingresosSuscripciones,

                // ✅ extras por si querés mostrar ambos
                'ingresos_suscripciones'  => $ingresosSuscripciones,
                'suscripciones_pagadas'   => $cantSuscripcionesPagas,
                'ingresos_paseos'         => $ingresosPaseos,

                'roles'                   => $roles,
                'paseos_por_dia'          => $paseosPorDia,
                'ingresos_por_mes'        => $ingresosPorMes,
                'ingresos_por_paseador'   => $ingresosPorPaseador,
            ];
        } catch (PDOException $e) {
            error_log("❌ Error en ReporteController::getEstadisticas(): " . $e->getMessage());
            return [
                'usuarios'               => 0,
                'paseos_total'           => 0,
                'paseos_completos'       => 0,
                'paseos_pendientes'      => 0,
                'ingresos_totales'       => 0,
                'roles'                  => [],
                'paseos_por_dia'         => [],
                'ingresos_por_mes'       => [],
                'ingresos_por_paseador'  => [],
                'error'                  => 'Error al obtener estadísticas',
            ];
        }
    }
}
