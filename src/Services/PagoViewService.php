<?php

namespace Jaguata\Services;

use PDO;

class PagoViewService
{
    public function __construct(private PDO $db = null)
    {
        $this->db = $this->db ?? $GLOBALS['db'];
    }

    /**
     * Retorna pago + paseo + dueno + paseador
     */
    public function getPagoFull(int $pagoId): ?array
    {
        $sql = "
        SELECT 
            pg.id               AS pago_id,
            pg.paseo_id,
            pg.usuario_id       AS paseador_id_en_pagos,  -- receptor
            pg.metodo,
            pg.alias            AS alias_transferencia,
            pg.referencia       AS referencia,            -- nro tx o archivo
            pg.monto,
            pg.estado           AS estado_pago,
            pg.created_at       AS pagado_en,
            pg.updated_at       AS actualizado_en,

            p.dueno_id,
            p.paseador_id,
            p.inicio,
            p.duracion_min,
            p.precio_total,
            p.estado            AS estado_paseo,

            u1.usu_id           AS dueno_id,
            u1.nombre           AS dueno_nombre,
            u1.email            AS dueno_email,

            u2.usu_id           AS paseador_id,
            u2.nombre           AS paseador_nombre,
            u2.email            AS paseador_email,
            u2.banco_nombre,
            u2.alias_cuenta,
            u2.cuenta_numero
        FROM pagos pg
        JOIN paseos p   ON p.paseo_id = pg.paseo_id
        JOIN usuarios u1 ON u1.usu_id = p.dueno_id
        JOIN usuarios u2 ON u2.usu_id = p.paseador_id
        WHERE pg.id = :pid
        LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([':pid' => $pagoId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row;
    }
}
