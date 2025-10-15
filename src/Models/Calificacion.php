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
}
