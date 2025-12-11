<?php

declare(strict_types=1);

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

use PDO;

class Paseo extends BaseModel
{
    protected string $table      = 'paseos';
    protected string $primaryKey = 'paseo_id';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 🔹 Paseos para panel admin (lista general)
     * Trae nombre de mascota, paseador y dueño.
     * Usado en: features/admin/Paseos.php
     */
    public function getAllWithRelations(): array
    {
        $sql = "
            SELECT 
                p.paseo_id,
                p.mascota_id,
                p.paseador_id,
                p.inicio,
                p.duracion,
                p.ubicacion,
                p.pickup_lat,
                p.pickup_lng,
                p.precio_total,
                p.estado,
                p.estado_pago,
                p.puntos_ganados,
                p.created_at,
                p.updated_at,
                m.nombre       AS nombre_mascota,
                pa.nombre      AS nombre_paseador,
                du.nombre      AS nombre_dueno
            FROM paseos p
            LEFT JOIN mascotas m  ON m.mascota_id = p.mascota_id
            LEFT JOIN usuarios pa ON pa.usu_id    = p.paseador_id   -- Paseador
            LEFT JOIN usuarios du ON du.usu_id    = m.dueno_id      -- Dueño
            ORDER BY p.inicio DESC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔹 Paseos por dueño (para vistas del dueño)
     */
    public function getByDueno(int $duenoId): array
    {
        $sql = "
        SELECT 
            p.*,
            m.nombre       AS nombre_mascota,
            u.nombre       AS nombre_paseador
        FROM paseos p
        INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
        INNER JOIN usuarios u ON u.usu_id     = p.paseador_id
        WHERE m.dueno_id = :dueno_id
        ORDER BY p.inicio DESC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }



    /**
     * 🔹 Detalle de paseo para panel admin (VerPaseo.php)
     * Incluye nombres y coordenadas del paseador + pickup.
     */
    public function getDetalleAdmin(int $id): ?array
    {
        $sql = "
            SELECT 
                p.*,
                m.nombre       AS nombre_mascota,
                pa.nombre      AS nombre_paseador,
                du.nombre      AS nombre_dueno,
                pa.latitud     AS paseador_latitud,
                pa.longitud    AS paseador_longitud,
                p.pickup_lat,
                p.pickup_lng
            FROM paseos p
            LEFT JOIN mascotas m  ON m.mascota_id = p.mascota_id
            LEFT JOIN usuarios pa ON pa.usu_id    = p.paseador_id
            LEFT JOIN usuarios du ON du.usu_id    = m.dueno_id
            WHERE p.paseo_id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * 🔹 Actualiza el estado del paseo (solicitado, confirmado, en_curso, completo, cancelado)
     */
    public function actualizarEstado(int $id, string $estado): bool
    {
        $sql = "
            UPDATE paseos
            SET estado = :estado,
                updated_at = NOW()
            WHERE paseo_id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estado' => $estado,
            ':id'     => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * 🔹 Exportar paseos por paseador (Excel)
     */
    public function getExportByPaseador(int $paseadorId): array
    {
        $sql = "
            SELECT 
                p.paseo_id,
                m.nombre      AS nombre_mascota,
                u.nombre      AS nombre_dueno,
                p.inicio,
                p.duracion    AS duracion_min,
                p.estado,
                p.estado_pago,
                p.precio_total
            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            INNER JOIN usuarios u ON u.usu_id = m.dueno_id
            WHERE p.paseador_id = :id
            ORDER BY p.inicio DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $paseadorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔹 Obtener ruta (lista de puntos lat/lng) para un paseo
     * Usado para dibujar la línea con huellitas en el mapa
     */
    public function getRuta(int $paseoId): array
    {
        $sql = "
            SELECT latitud, longitud
            FROM paseo_rutas
            WHERE paseo_id = :id
            ORDER BY creado_en ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $paseoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
