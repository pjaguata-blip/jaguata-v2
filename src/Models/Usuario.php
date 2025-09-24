<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class Historial extends BaseModel
{
    protected string $table = 'historiales';
    protected string $primaryKey = 'historial_id';

    public function getHistorialByUsuario($usuarioId, $limite = null)
    {
        $sql = "SELECT h.*, p.inicio, m.nombre as mascota_nombre, u_paseador.nombre as paseador_nombre
                FROM historiales h 
                LEFT JOIN paseos p ON h.paseo_id = p.paseo_id 
                LEFT JOIN mascotas m ON p.mascota_id = m.mascota_id 
                LEFT JOIN usuarios u_paseador ON p.paseador_id = u_paseador.usu_id 
                WHERE h.usu_id = :usuarioId
                ORDER BY h.created_at DESC";

        $params = ['usuarioId' => $usuarioId];

        if ($limite) {
            $sql .= " LIMIT :limite";
            $params['limite'] = $limite;
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function getHistorialByActividad($usuarioId, $actividad)
    {
        return $this->findAll([
            'usu_id' => $usuarioId,
            'actividad' => $actividad
        ], 'created_at DESC');
    }

    public function getPuntosTotales($usuarioId)
    {
        $sql = "SELECT SUM(puntos) as total_puntos FROM {$this->table} WHERE usu_id = :usuarioId";
        $resultado = $this->db->fetchOne($sql, ['usuarioId' => $usuarioId]);
        return $resultado['total_puntos'] ?? 0;
    }

    public function getPuntosByActividadUsuario($usuarioId, $actividad)
    {
        $sql = "SELECT SUM(puntos) as total_puntos FROM {$this->table} 
                WHERE usu_id = :usuarioId AND actividad = :actividad";
        $resultado = $this->db->fetchOne($sql, [
            'usuarioId' => $usuarioId,
            'actividad' => $actividad
        ]);
        return $resultado['total_puntos'] ?? 0;
    }

    public function registrarActividad($usuarioId, $actividad, $puntos, $paseoId = null)
    {
        $data = [
            'usu_id' => $usuarioId,
            'paseo_id' => $paseoId,
            'puntos' => $puntos,
            'actividad' => $actividad,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    public function registrarPaseoCompletado($usuarioId, $paseoId, $puntos = 10)
    {
        return $this->registrarActividad($usuarioId, 'paseo_completado', $puntos, $paseoId);
    }

    public function registrarCalificacion($usuarioId, $paseoId, $puntos = 5)
    {
        return $this->registrarActividad($usuarioId, 'calificacion_realizada', $puntos, $paseoId);
    }

    public function registrarRegistro($usuarioId, $puntos = 20)
    {
        return $this->registrarActividad($usuarioId, 'registro_usuario', $puntos);
    }

    public function registrarPrimerPaseo($usuarioId, $paseoId, $puntos = 50)
    {
        return $this->registrarActividad($usuarioId, 'primer_paseo', $puntos, $paseoId);
    }

    public function registrarReferido($usuarioId, $puntos = 100)
    {
        return $this->registrarActividad($usuarioId, 'usuario_referido', $puntos);
    }

    public function getActividadesDisponibles()
    {
        return [
            'registro_usuario' => 'Registro de Usuario',
            'paseo_completado' => 'Paseo Completado',
            'calificacion_realizada' => 'Calificación Realizada',
            'primer_paseo' => 'Primer Paseo',
            'usuario_referido' => 'Usuario Referido',
            'paseo_confirmado' => 'Paseo Confirmado',
            'pago_realizado' => 'Pago Realizado',
            'perfil_completado' => 'Perfil Completado'
        ];
    }

    public function getPuntosByActividad($actividad)
    {
        $puntos = [
            'registro_usuario' => 20,
            'paseo_completado' => 10,
            'calificacion_realizada' => 5,
            'primer_paseo' => 50,
            'usuario_referido' => 100,
            'paseo_confirmado' => 5,
            'pago_realizado' => 15,
            'perfil_completado' => 10
        ];

        return $puntos[$actividad] ?? 0;
    }

    public function getHistorialByFecha($usuarioId, $fechaInicio, $fechaFin)
    {
        $sql = "SELECT h.*, p.inicio, m.nombre as mascota_nombre, u_paseador.nombre as paseador_nombre
                FROM historiales h 
                LEFT JOIN paseos p ON h.paseo_id = p.paseo_id 
                LEFT JOIN mascotas m ON p.mascota_id = m.mascota_id 
                LEFT JOIN usuarios u_paseador ON p.paseador_id = u_paseador.usu_id 
                WHERE h.usu_id = :usuarioId 
                AND DATE(h.created_at) BETWEEN :fechaInicio AND :fechaFin
                ORDER BY h.created_at DESC";

        return $this->db->fetchAll($sql, [
            'usuarioId' => $usuarioId,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin
        ]);
    }

    public function getRankingUsuarios($limite = 10)
    {
        $sql = "SELECT u.usu_id, u.nombre, u.rol, SUM(h.puntos) as total_puntos
                FROM usuarios u 
                INNER JOIN historiales h ON u.usu_id = h.usu_id 
                GROUP BY u.usu_id, u.nombre, u.rol 
                ORDER BY total_puntos DESC 
                LIMIT :limite";

        return $this->db->fetchAll($sql, ['limite' => $limite]);
    }

    public function getEstadisticasUsuario($usuarioId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_actividades,
                    SUM(puntos) as total_puntos,
                    COUNT(DISTINCT DATE(created_at)) as dias_activos,
                    MAX(created_at) as ultima_actividad
                FROM {$this->table} 
                WHERE usu_id = :usuarioId";

        return $this->db->fetchOne($sql, ['usuarioId' => $usuarioId]);
    }
}
