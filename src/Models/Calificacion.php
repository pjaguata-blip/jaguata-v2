<?php

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;

class Calificacion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function crear(array $data): int
    {
        $sql = "INSERT INTO calificaciones (paseo_id, rater_id, rated_id, calificacion, comentario, tipo)
                VALUES (:paseo_id, :rater_id, :rated_id, :calificacion, :comentario, :tipo)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':paseo_id'     => $data['paseo_id'],
            ':rater_id'     => $data['rater_id'],
            ':rated_id'     => $data['rated_id'],
            ':calificacion' => $data['calificacion'],
            ':comentario'   => $data['comentario'] ?? null,
            ':tipo'         => $data['tipo'] ?? 'paseador',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function existeParaPaseo(int $paseoId, string $tipo, int $raterId): bool
    {
        $sql = "SELECT COUNT(*) FROM calificaciones 
                WHERE paseo_id = :p AND tipo = :t AND rater_id = :r";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $paseoId, ':t' => $tipo, ':r' => $raterId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function promedioPorUsuario(int $usuarioId, string $tipo = 'paseador'): float
    {
        $sql = "SELECT AVG(calificacion) FROM calificaciones 
                WHERE rated_id = :u AND tipo = :t";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':u' => $usuarioId, ':t' => $tipo]);
        return round((float)$stmt->fetchColumn(), 2);
    }

    
    public function resumenPorPaseador(int $paseadorId): array
    {
        $sql = "SELECT 
                    AVG(calificacion) AS promedio,
                    COUNT(*)          AS total
                FROM calificaciones
                WHERE rated_id = :id
                  AND tipo = 'paseador'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $paseadorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return [
            'promedio' => $row && $row['total'] > 0 ? round((float)$row['promedio'], 1) : null,
            'total'    => (int)($row['total'] ?? 0),
        ];
    }

    public function resumenPorDueno(int $duenoId): array
    {
        $sql = "SELECT 
                    AVG(c.calificacion) AS promedio,
                    COUNT(*)            AS total
                FROM calificaciones c
                INNER JOIN mascotas m ON m.mascota_id = c.rated_id
                WHERE c.tipo = 'mascota'
                  AND m.dueno_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $duenoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return [
            'promedio' => $row && $row['total'] > 0 ? round((float)$row['promedio'], 1) : null,
            'total'    => (int)($row['total'] ?? 0),
        ];
    }

    public function promedioGlobalPorTipo(string $tipo): ?float
    {
        if (!in_array($tipo, ['paseador', 'mascota'], true)) {
            return null;
        }

        if ($tipo === 'paseador') {
            // Promedio de calificaciones de paseadores
            $sql = "SELECT AVG(calificacion) AS prom
                    FROM calificaciones
                    WHERE tipo = 'paseador'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // Promedio de calificaciones de dueños (a través de sus mascotas)
            $sql = "SELECT AVG(c.calificacion) AS prom
                    FROM calificaciones c
                    INNER JOIN mascotas m ON m.mascota_id = c.rated_id
                    WHERE c.tipo = 'mascota'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }

        $prom = $stmt->fetchColumn();
        if ($prom === false || $prom === null) {
            return null;
        }

        return round((float)$prom, 1);
    }
    public function opinionesPorUsuario(int $ratedId, string $tipo = 'paseador', int $limite = 20): array
    {
        $tipo = strtolower(trim($tipo));
        if (!in_array($tipo, ['paseador', 'mascota'], true)) {
            $tipo = 'paseador';
        }

        $limite = max(1, min(50, $limite));

        $sql = "
        SELECT
            c.cali_id,
            c.paseo_id,
            c.calificacion,
            c.comentario,
            c.tipo,
            c.created_at,
            u.usu_id   AS rater_id,
            u.nombre   AS rater_nombre,
            u.email    AS rater_email
        FROM calificaciones c
        INNER JOIN usuarios u ON u.usu_id = c.rater_id
        WHERE c.rated_id = :rated_id
          AND c.tipo = :tipo
          AND c.comentario IS NOT NULL
          AND TRIM(c.comentario) <> ''
        ORDER BY c.created_at DESC
        LIMIT {$limite}
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rated_id' => $ratedId,
            ':tipo'     => $tipo,
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    public function resumenPorRated(int $ratedId, string $tipo): array
    {
        $sql = "
        SELECT 
            AVG(calificacion) AS promedio,
            COUNT(*)          AS total
        FROM calificaciones
        WHERE rated_id = :rated_id
          AND tipo     = :tipo
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rated_id' => $ratedId,
            ':tipo'     => $tipo,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'promedio' => isset($row['promedio']) ? (float)$row['promedio'] : null,
            'total'    => isset($row['total']) ? (int)$row['total'] : 0,
        ];
    }

    public function opinionesPorRated(int $ratedId, string $tipo, int $limit = 5, int $offset = 0): array
    {
        // OJO: LIMIT/OFFSET conviene pasarlos como int directo (sin comillas)
        $limit  = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $sql = "
        SELECT
            c.cali_id,
            c.paseo_id,
            c.rater_id,
            c.rated_id,
            c.calificacion,
            c.comentario,
            c.tipo,
            c.created_at,
            u.nombre AS autor_nombre,
            u.email  AS autor_email
        FROM calificaciones c
        LEFT JOIN usuarios u ON u.usu_id = c.rater_id
        WHERE c.rated_id = :rated_id
          AND c.tipo     = :tipo
        ORDER BY c.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rated_id' => $ratedId,
            ':tipo'     => $tipo,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
