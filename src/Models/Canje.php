<?php
declare(strict_types=1);

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;

class Canje
{
    private DatabaseService $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    public function listarPendientesPorUsuario(int $usuarioId): array
    {
        return $this->db->fetchAll("
            SELECT
                c.canje_id,
                c.usuario_id,
                c.recompensa_id,
                c.puntos_usados,
                COALESCE(c.estado,'') AS estado,
                c.created_at,
                c.tipo_descuento,
                c.valor_descuento,
                c.titulo_snapshot,
                c.paseo_id,
                c.used_at
            FROM canjes c
            WHERE c.usuario_id = :u
              AND COALESCE(c.estado,'') = 'pendiente'
            ORDER BY c.created_at DESC
        ", [':u' => $usuarioId]);
    }

    public function marcarUsado(int $canjeId, int $usuarioId, int $paseoId): bool
    {
        $st = $this->db->prepare("
            UPDATE canjes
            SET estado = 'usado',
                paseo_id = :p,
                used_at = NOW()
            WHERE canje_id = :c
              AND usuario_id = :u
              AND COALESCE(estado,'') = 'pendiente'
        ");

        $st->execute([
            ':p' => $paseoId,
            ':c' => $canjeId,
            ':u' => $usuarioId
        ]);

        return $st->rowCount() > 0;
    }

    public function getByIdParaUsuario(int $canjeId, int $usuarioId): ?array
    {
        $row = $this->db->fetchOne("
            SELECT
                c.canje_id,
                c.usuario_id,
                c.estado,
                c.tipo_descuento,
                c.valor_descuento,
                c.titulo_snapshot
            FROM canjes c
            WHERE c.canje_id = :c
              AND c.usuario_id = :u
            LIMIT 1
        ", [':c' => $canjeId, ':u' => $usuarioId]);

        return $row ?: null;
    }
}
