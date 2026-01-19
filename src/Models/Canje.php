<?php
declare(strict_types=1);

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;

class Canje
{
    private DatabaseService $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    public function crear(int $usuarioId, int $recompensaId, int $puntosUsados): int
    {
        $this->db->prepare(
            "INSERT INTO canjes (usuario_id, recompensa_id, puntos_usados, estado)
             VALUES (:u, :r, :p, 'pendiente')"
        )->execute([
            ':u' => $usuarioId,
            ':r' => $recompensaId,
            ':p' => $puntosUsados,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function listarPendientesPorUsuario(int $usuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT c.canje_id, c.puntos_usados, c.estado, c.created_at,
                    r.titulo, r.descripcion
             FROM canjes c
             INNER JOIN recompensas r ON r.recompensa_id = c.recompensa_id
             WHERE c.usuario_id = :u AND c.estado = 'pendiente'
             ORDER BY c.created_at DESC",
            [':u' => $usuarioId]
        );
    }
}
