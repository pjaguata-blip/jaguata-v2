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

    /* =========================
       NUEVOS MÉTODOS RESUMEN
       ========================= */

    /**
     * Resumen de reputación para un PASEADOR
     * - promedio: float|null
     * - total: int (cantidad de opiniones)
     */
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

    /**
     * Resumen de reputación para un DUEÑO
     * Se calcula como el promedio de las calificaciones de SUS MASCOTAS
     */
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
    /**
     * Promedio global por tipo de calificación
     *  - 'paseador' → calificaciones que los dueños hacen a paseadores
     *  - 'mascota'  → calificaciones que los paseadores hacen a mascotas (dueños)
     */
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
}
