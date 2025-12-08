<?php

namespace Jaguata\Services;

use PDO;

class PagoViewService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        // Usamos el $GLOBALS['db'] si no se inyecta nada
        $this->db = $db ?? ($GLOBALS['db'] ?? null);

        if (!$this->db instanceof PDO) {
            throw new \RuntimeException('No hay conexión PDO disponible para PagoViewService');
        }
    }

    /**
     * Retorna pago + paseo + datos de dueño y paseador
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
            pg.referencia       AS referencia,
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
            u1.telefono         AS dueno_telefono,

            u2.usu_id           AS paseador_id,
            u2.nombre           AS paseador_nombre,
            u2.email            AS paseador_email,
            u2.telefono         AS paseador_telefono,

            cb.banco_nombre,
            cb.alias            AS alias_cuenta,
            cb.numero_cuenta    AS cuenta_numero

        FROM pagos pg
        INNER JOIN paseos p      ON p.paseo_id = pg.paseo_id
        INNER JOIN usuarios u1   ON u1.usu_id  = p.dueno_id
        INNER JOIN usuarios u2   ON u2.usu_id  = p.paseador_id
        LEFT JOIN cuentas_bancarias cb ON cb.usuario_id = pg.usuario_id
        WHERE pg.id = :pago_id
        LIMIT 1
        ";

        $st = $this->db->prepare($sql);
        $st->bindValue(':pago_id', $pagoId, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
