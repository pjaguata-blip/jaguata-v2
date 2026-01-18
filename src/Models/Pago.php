<?php

declare(strict_types=1);

namespace Jaguata\Models;

require_once __DIR__ . '/../Services/DatabaseService.php';

use Jaguata\Services\DatabaseService;
use PDO;

class Pago
{
    private PDO $db;
    private string $table = 'pagos';

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function getUltimosDatosPorUsuario(int $usuarioId): ?array
    {
        $sql = "
            SELECT metodo, banco, alias, cuenta
            FROM pagos
            WHERE usuario_id = :uid
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':uid' => $usuarioId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

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
                pg.alias,
                pg.referencia,
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

        $sql .= " ORDER BY pg.created_at DESC, pg.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            if (isset($row['estado'])) {
                $row['estado'] = ucfirst(strtolower((string)$row['estado']));
            }
        }

        return $rows;
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO pagos (
                paseo_id, usuario_id, metodo, banco, cuenta,
                comprobante, alias, referencia, monto, estado,
                observacion, created_at
            ) VALUES (
                :paseo_id, :usuario_id, :metodo, :banco, :cuenta,
                :comprobante, :alias, :referencia, :monto, :estado,
                :observacion, NOW()
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':paseo_id'    => (int)$data['paseo_id'],
            ':usuario_id'  => (int)$data['usuario_id'],
            ':metodo'      => (string)$data['metodo'],
            ':banco'       => $data['banco'] ?? null,
            ':cuenta'      => $data['cuenta'] ?? null,
            ':comprobante' => $data['comprobante'] ?? null,
            ':alias'       => $data['alias'] ?? null,
            ':referencia'  => $data['referencia'] ?? null,
            ':monto'       => (float)$data['monto'],
            ':estado'      => $data['estado'] ?? 'pendiente',
            ':observacion' => $data['observacion'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByPaseoId(int $paseoId): ?array
    {
        $sql = "SELECT * FROM pagos WHERE paseo_id = :paseo_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':paseo_id' => $paseoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function upsertByPaseoId(int $paseoId, array $data): int
    {
        $existente = $this->findByPaseoId($paseoId);

        if ($existente) {
            $sql = "
                UPDATE pagos SET
                    usuario_id = :usuario_id,
                    metodo = :metodo,
                    banco = :banco,
                    cuenta = :cuenta,
                    comprobante = :comprobante,
                    alias = :alias,
                    referencia = :referencia,
                    monto = :monto,
                    estado = :estado,
                    observacion = :observacion,
                    updated_at = NOW()
                WHERE paseo_id = :paseo_id
            ";
            $st = $this->db->prepare($sql);
            $st->execute([
                ':usuario_id'  => (int)$data['usuario_id'],
                ':metodo'      => (string)$data['metodo'],
                ':banco'       => $data['banco'] ?? null,
                ':cuenta'      => $data['cuenta'] ?? null,
                ':comprobante' => $data['comprobante'] ?? null,
                ':alias'       => $data['alias'] ?? null,
                ':referencia'  => $data['referencia'] ?? null,
                ':monto'       => (float)$data['monto'],
                ':estado'      => $data['estado'] ?? ($existente['estado'] ?? 'pendiente'),
                ':observacion' => $data['observacion'] ?? null,
                ':paseo_id'    => $paseoId,
            ]);

            return (int)$existente['id'];
        }

        $data['paseo_id'] = $paseoId;
        return $this->create($data);
    }

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
                pg.alias,
                pg.referencia,
                pg.comprobante,
                pg.monto,
                pg.estado,
                pg.observacion,
                pg.created_at AS fecha,
                u.nombre      AS usuario,
                u.email       AS usuario_email,
                u.telefono    AS usuario_telefono,
                p.inicio      AS paseo_inicio,
                p.duracion    AS paseo_duracion,
                p.precio_total AS paseo_precio
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
