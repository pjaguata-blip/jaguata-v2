<?php

declare(strict_types=1);

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;

class Punto
{
    private DatabaseService $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    public function getByUsuario(int $usuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT id, descripcion, puntos, fecha
             FROM puntos
             WHERE usuario_id = :id
             ORDER BY fecha DESC",
            [':id' => $usuarioId]
        );
    }

    public function getTotal(int $usuarioId): int
    {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(puntos,0) AS puntos
             FROM usuarios
             WHERE usu_id = :id",
            [':id' => $usuarioId]
        );

        return (int)($row['puntos'] ?? 0);
    }

    public function getTotalMesActual(int $usuarioId): int
    {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(SUM(puntos),0) AS total
             FROM puntos
             WHERE usuario_id = :id
               AND YEAR(fecha) = YEAR(CURRENT_DATE())
               AND MONTH(fecha) = MONTH(CURRENT_DATE())",
            [':id' => $usuarioId]
        );

        return (int)($row['total'] ?? 0);
    }

    public function add(int $usuarioId, string $descripcion, int $puntos): int
    {
        $this->db->beginTransaction();

        try {
            // Insertar movimiento
            $this->db->prepare(
                "INSERT INTO puntos (usuario_id, descripcion, puntos, fecha)
                 VALUES (:uid, :desc, :pts, NOW())"
            )->execute([
                ':uid'  => $usuarioId,
                ':desc' => $descripcion,
                ':pts'  => $puntos
            ]);

            // Actualizar saldo en usuarios
            $this->db->prepare(
                "UPDATE usuarios
                 SET puntos = COALESCE(puntos,0) + :pts
                 WHERE usu_id = :uid"
            )->execute([
                ':pts' => $puntos,
                ':uid' => $usuarioId
            ]);

            $this->db->commit();
            return (int)$this->db->lastInsertId();

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
