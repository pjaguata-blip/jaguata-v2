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
            $this->db->prepare(
                "INSERT INTO puntos (usuario_id, descripcion, puntos, fecha)
                 VALUES (:uid, :desc, :pts, NOW())"
            )->execute([
                ':uid'  => $usuarioId,
                ':desc' => $descripcion,
                ':pts'  => $puntos
            ]);

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

    /**
     * ✅ Otorgar puntos por paseo (UNA sola vez)
     * Usa: paseos.puntos_ganados como candado anti-duplicado
     */
   public function addPorPaseo(int $paseoId): int
{
    // 1) Traer paseo + dueño (JOIN real) + mascotas (1 y 2)
    $p = $this->db->fetchOne(
        "SELECT 
            p.paseo_id,
            p.mascota_id,
            p.mascota_id_2,
            p.cantidad_mascotas,
            p.precio_total,
            p.puntos_ganados,
            p.estado,
            m.dueno_id AS dueno_id
         FROM paseos p
         INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
         WHERE p.paseo_id = :id
         LIMIT 1",
        [':id' => $paseoId]
    );

    if (!$p) return 0;

    // ✅ Candado: si ya se otorgó, no repetir
    if ((int)($p['puntos_ganados'] ?? 0) > 0) return 0;

    $estado = strtolower(trim((string)($p['estado'] ?? '')));
    // Otorgamos solo si está completo (por seguridad)
    if ($estado !== 'completo') return 0;

    $duenoId = (int)($p['dueno_id'] ?? 0);
    if ($duenoId <= 0) return 0;

    $precio = (float)($p['precio_total'] ?? 0);

    // 2) Regla de puntos (ajustable)
    // Ejemplo: 1 punto cada 1000Gs, mínimo 1
    $puntosGanados = (int)floor($precio / 1000);
    if ($puntosGanados < 1) $puntosGanados = 1;

    // ✅ Si querés que 2 mascotas den un bonus (opcional)
    // Ej: +1 punto extra si hay 2 mascotas
    $cantMascotas = (int)($p['cantidad_mascotas'] ?? 1);
    if ($cantMascotas >= 2) {
        $puntosGanados += 1; // podés cambiar esta regla si querés
    }

    $desc = "Puntos por paseo #{$paseoId}";

    // 3) Doble candado por si acaso (historial)
    $ya = $this->db->fetchOne(
    "SELECT id FROM puntos WHERE paseo_id = :pid LIMIT 1",
    [':pid' => $paseoId]
);
if ($ya) return 0;


    // 4) Transacción
    $this->db->beginTransaction();

    try {
        // Insert historial
       $this->db->prepare(
    "INSERT INTO puntos (usuario_id, descripcion, puntos, fecha, paseo_id)
     VALUES (:uid, :desc, :pts, NOW(), :pid)"
)->execute([
    ':uid'  => $duenoId,
    ':desc' => $desc,
    ':pts'  => $puntosGanados,
    ':pid'  => $paseoId
]);


        // Sumar saldo (usuarios.usu_id)
        $this->db->prepare(
            "UPDATE usuarios
             SET puntos = COALESCE(puntos,0) + :pts
             WHERE usu_id = :uid"
        )->execute([
            ':pts' => $puntosGanados,
            ':uid' => $duenoId
        ]);

        // Guardar puntos en el paseo (candado)
        $this->db->prepare(
            "UPDATE paseos
             SET puntos_ganados = :pts
             WHERE paseo_id = :id"
        )->execute([
            ':pts' => $puntosGanados,
            ':id'  => $paseoId
        ]);

        $this->db->commit();
        return $puntosGanados;

    } catch (\Throwable $e) {
        $this->db->rollBack();
        throw $e;
    }
}

}
