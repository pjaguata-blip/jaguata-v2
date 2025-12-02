<?php

declare(strict_types=1);

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;
use PDOException;

class Pago
{
    private PDO $db;
    private string $table = 'pagos';

    public function __construct()
    {
        // Conexión única a la BD
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * Listado de pagos con nombre de usuario (para ADMIN).
     * $estado puede ser:
     *  - null  => todos
     *  - 'pendiente', 'pagado', etc.
     */
    public function getAdminList(?string $estado = null): array
    {
        $sql = "
            SELECT 
                pg.id,
                pg.paseo_id,
                pg.usuario_id,
                u.nombre AS usuario,
                pg.metodo,
                pg.banco,
                pg.cuenta,
                pg.monto,
                pg.estado,
                pg.created_at AS fecha
            FROM pagos pg
            INNER JOIN usuarios u ON u.usu_id = pg.usuario_id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($estado)) {
            $sql .= " AND LOWER(pg.estado) = LOWER(:estado)";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY pg.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatear estado: 'Pendiente', 'Pagado', etc.
        foreach ($rows as &$row) {
            if (isset($row['estado'])) {
                $row['estado'] = ucfirst(strtolower((string) $row['estado']));
            }
        }

        return $rows;
    }

    /**
     * Crear un pago.
     * Devuelve el ID insertado.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO pagos (
                paseo_id,
                usuario_id,
                metodo,
                banco,
                cuenta,
                comprobante,
                alias,
                referencia,
                monto,
                estado,
                observacion,
                created_at
            ) VALUES (
                :paseo_id,
                :usuario_id,
                :metodo,
                :banco,
                :cuenta,
                :comprobante,
                :alias,
                :referencia,
                :monto,
                :estado,
                :observacion,
                NOW()
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':paseo_id'    => $data['paseo_id'],
            ':usuario_id'  => $data['usuario_id'],
            ':metodo'      => $data['metodo'],
            ':banco'       => $data['banco'],
            ':cuenta'      => $data['cuenta'],
            ':comprobante' => $data['comprobante'],
            ':alias'       => $data['alias'],
            ':referencia'  => $data['referencia'],
            ':monto'       => $data['monto'],
            ':estado'      => $data['estado'],
            ':observacion' => $data['observacion'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza el estado del pago (y observación opcional).
     */
    public function updateEstado(int $pagoId, string $estado, ?string $observacion = null): bool
    {
        $sql = "
            UPDATE pagos
            SET estado = :estado,
                observacion = :observacion,
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado'      => $estado,
            ':observacion' => $observacion,
            ':id'          => $pagoId,
        ]);
    }

    /**
     * Busca un pago por ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM pagos WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Busca un pago por ID de paseo.
     */
    public function findByPaseoId(int $paseoId): ?array
    {
        $sql = "SELECT * FROM pagos WHERE paseo_id = :paseo_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':paseo_id' => $paseoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * 🔹 Detalle de pago para ADMIN (con usuario y paseo)
     */
    public function getAdminDetalle(int $id): ?array
    {
        $sql = "
            SELECT 
                pg.id,
                pg.paseo_id,
                pg.usuario_id,
                pg.metodo,
                pg.banco,
                pg.cuenta,
                pg.monto,
                pg.estado,
                pg.observacion,
                pg.created_at       AS fecha,
                u.nombre            AS usuario,
                u.email             AS usuario_email,
                u.telefono          AS usuario_telefono,
                p.inicio            AS paseo_inicio,
                p.duracion          AS paseo_duracion,
                p.precio_total      AS paseo_precio
            FROM pagos pg
            LEFT JOIN usuarios u ON u.usu_id   = pg.usuario_id
            LEFT JOIN paseos   p ON p.paseo_id = pg.paseo_id
            WHERE pg.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
