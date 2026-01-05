<?php

declare(strict_types=1);

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

use PDO;

class DatosPago extends BaseModel
{
    protected string $table = 'datos_pago';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    public function getByUsuarioId(int $usuarioId): ?array
    {
        $sql = "SELECT * FROM datos_pago WHERE usuario_id = :uid LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([':uid' => $usuarioId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Inserta si no existe, actualiza si ya existe (por usuario_id).
     */
    public function upsert(int $usuarioId, ?string $banco, ?string $alias, ?string $cuenta): bool
    {
        // MySQL: INSERT... ON DUPLICATE KEY requiere UNIQUE(usuario_id)
        // Si tu tabla no tiene UNIQUE, hacemos "select + update/insert".

        $exists = $this->getByUsuarioId($usuarioId);

        if ($exists) {
            $sql = "
                UPDATE datos_pago
                SET banco = :banco,
                    alias = :alias,
                    cuenta = :cuenta,
                    updated_at = CURRENT_TIMESTAMP
                WHERE usuario_id = :uid
            ";
            $st = $this->db->prepare($sql);
            return $st->execute([
                ':banco'  => $banco,
                ':alias'  => $alias,
                ':cuenta' => $cuenta,
                ':uid'    => $usuarioId,
            ]);
        }

        $sql = "
            INSERT INTO datos_pago (usuario_id, banco, alias, cuenta)
            VALUES (:uid, :banco, :alias, :cuenta)
        ";
        $st = $this->db->prepare($sql);
        return $st->execute([
            ':uid'    => $usuarioId,
            ':banco'  => $banco,
            ':alias'  => $alias,
            ':cuenta' => $cuenta,
        ]);
    }
}
