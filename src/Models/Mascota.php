<?php

declare(strict_types=1);

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

use PDO;

class Mascota extends BaseModel
{
    protected string $table      = 'mascotas';
    protected string $primaryKey = 'mascota_id';

    public function __construct()
    {
        parent::__construct();
    }

    public function getByDueno(int $duenoId): array
    {
        $sql = "
            SELECT 
                mascota_id,
                dueno_id,
                nombre,
                raza,
                peso_kg,
                tamano,
                edad_meses,
                observaciones,
                foto_url,
                created_at,
                updated_at
            FROM mascotas
            WHERE dueno_id = :dueno_id
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAllWithDueno(): array
    {
        $sql = "
            SELECT 
                m.mascota_id,
                m.dueno_id,
                m.nombre,
                m.raza,
                m.peso_kg,
                m.tamano,
                m.edad_meses,
                m.observaciones,
                m.foto_url,
                m.estado,
                m.created_at,
                m.updated_at,
                u.nombre AS dueno_nombre,
                u.email  AS dueno_email
            FROM mascotas m
            INNER JOIN usuarios u ON u.usu_id = m.dueno_id
            ORDER BY m.created_at DESC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public function resumenPorMascota(int $mascotaId): array
    {
        $sql = "SELECT 
                    AVG(calificacion) AS promedio,
                    COUNT(*)          AS total
                FROM calificaciones
                WHERE rated_id = :id
                  AND tipo = 'mascota'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $mascotaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return [
            'promedio' => $row && $row['total'] > 0 ? round((float)$row['promedio'], 1) : null,
            'total'    => (int)($row['total'] ?? 0),
        ];
    }
}
