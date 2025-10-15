<?php

namespace Jaguata\Models;

use PDO;

class Pago
{
    private PDO $db;

    public function __construct()
    {
        // Conexión creada por AppConfig::init()
        $this->db = $GLOBALS['db'];
    }

    /**
     * Crea un pago (tabla: pagos)
     * Columnas: id, paseo_id, usuario_id, metodo, banco, cuenta, comprobante, alias, referencia,
     *           monto, estado, observacion, created_at, updated_at
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO pagos (
                    paseo_id, usuario_id, metodo, banco, cuenta, comprobante,
                    alias, referencia, monto, estado, observacion
                ) VALUES (
                    :paseo_id, :usuario_id, :metodo, :banco, :cuenta, :comprobante,
                    :alias, :referencia, :monto, :estado, :observacion
                )";
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
            ':estado'      => (string)$data['estado'],
            ':observacion' => $data['observacion'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Cambia el estado y opcionalmente guarda observación.
     */
    public function updateEstado(int $pagoId, string $estado, ?string $observacion = null): bool
    {
        if ($observacion !== null && $observacion !== '') {
            $sql = "UPDATE pagos SET estado = :estado, observacion = :observacion WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':estado'      => $estado,
                ':observacion' => $observacion,
                ':id'          => $pagoId
            ]);
        }

        $sql = "UPDATE pagos SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado' => $estado,
            ':id'     => $pagoId
        ]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pagos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByPaseoId(int $paseoId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pagos WHERE paseo_id = :pid ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([':pid' => $paseoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
