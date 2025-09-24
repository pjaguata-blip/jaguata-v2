<?php

namespace Jaguata\Models;

/**
 * Modelo Historial
 * Gestiona el historial de actividades y puntos de un usuario
 */
class Historial extends BaseModel
{
    protected string $table = 'historiales';
    protected string $primaryKey = 'historial_id';

    public function getHistorialByUsuario(int $usuarioId, ?int $limite = null): array
    {
        $sql = "SELECT h.*, p.inicio, m.nombre AS mascota_nombre, u_paseador.nombre AS paseador_nombre
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

    public function getPuntosTotales(int $usuarioId): int
    {
        $sql = "SELECT SUM(puntos) AS total_puntos FROM {$this->table} WHERE usu_id = :usuarioId";
        $res = $this->db->fetchOne($sql, ['usuarioId' => $usuarioId]);
        return (int)($res['total_puntos'] ?? 0);
    }

    public function registrarActividad(int $usuarioId, string $actividad, int $puntos, ?int $paseoId = null): int
    {
        $data = [
            'usu_id'     => $usuarioId,
            'paseo_id'   => $paseoId,
            'puntos'     => $puntos,
            'actividad'  => $actividad,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->create($data);
    }

    public function getActividadesDisponibles(): array
    {
        return [
            'registro_usuario'    => 'Registro de Usuario',
            'paseo_completado'    => 'Paseo Completado',
            'calificacion_realizada' => 'Calificación Realizada',
            'primer_paseo'        => 'Primer Paseo',
            'usuario_referido'    => 'Usuario Referido',
            'paseo_confirmado'    => 'Paseo Confirmado',
            'pago_realizado'      => 'Pago Realizado',
            'perfil_completado'   => 'Perfil Completado',
        ];
    }

    public function getPuntosByActividad(string $actividad): int
    {
        $puntos = [
            'registro_usuario'     => 20,
            'paseo_completado'     => 10,
            'calificacion_realizada' => 5,
            'primer_paseo'         => 50,
            'usuario_referido'     => 100,
            'paseo_confirmado'     => 5,
            'pago_realizado'       => 15,
            'perfil_completado'    => 10,
        ];

        return $puntos[$actividad] ?? 0;
    }
}
