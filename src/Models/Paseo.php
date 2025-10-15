<?php

namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;
use PDO;
use Exception;

class Paseo
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    // =========================
    // CRUD BÁSICO
    // =========================
    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM paseos ORDER BY inicio DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM paseos WHERE paseo_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create($data)
    {
        // ✅ Verificar paseador válido
        $checkP = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE usu_id = :id AND rol = 'paseador'");
        $checkP->execute(['id' => $data['paseador_id']]);
        if ($checkP->fetchColumn() == 0) {
            throw new Exception("El paseador seleccionado no existe o no es válido.");
        }

        // ✅ Verificar mascota válida
        $checkM = $this->db->prepare("SELECT COUNT(*) FROM mascotas WHERE mascota_id = :id");
        $checkM->execute(['id' => $data['mascota_id']]);
        if ($checkM->fetchColumn() == 0) {
            throw new Exception("La mascota seleccionada no existe.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO paseos (mascota_id, paseador_id, inicio, duracion, precio_total, estado)
            VALUES (:mascota_id, :paseador_id, :inicio, :duracion, :precio_total, 'Pendiente')
        ");
        $stmt->execute([
            ':mascota_id'   => $data['mascota_id'],
            ':paseador_id'  => $data['paseador_id'],
            ':inicio'       => $data['inicio'],
            ':duracion'     => $data['duracion'],
            ':precio_total' => $data['precio_total']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE paseos 
            SET inicio = :inicio, duracion = :duracion, precio_total = :precio_total, updated_at = NOW()
            WHERE paseo_id = :id
        ");
        return $stmt->execute([
            ':inicio'       => $data['inicio'],
            ':duracion'     => $data['duracion'],
            ':precio_total' => $data['precio_total'],
            ':id'           => $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM paseos WHERE paseo_id = ?");
        return $stmt->execute([$id]);
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->db->prepare("
            UPDATE paseos SET estado = :estado, updated_at = NOW() WHERE paseo_id = :id
        ");
        return $stmt->execute([
            ':estado' => $estado,
            ':id'     => $id
        ]);
    }

    // =========================
    // RELACIONES Y CONSULTAS
    // =========================
    public function allWithRelations(): array
    {
        $sql = "SELECT p.*, 
                       u.nombre AS nombre_paseador,
                       m.nombre AS nombre_mascota
                FROM paseos p
                LEFT JOIN usuarios u ON u.usu_id = p.paseador_id
                LEFT JOIN mascotas m ON m.mascota_id = p.mascota_id
                ORDER BY p.inicio DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByDueno(int $duenoId): array
    {
        $sql = "SELECT p.*, 
                       u.nombre AS nombre_paseador,
                       m.nombre AS nombre_mascota
                FROM paseos p
                LEFT JOIN usuarios u ON u.usu_id = p.paseador_id
                LEFT JOIN mascotas m ON m.mascota_id = p.mascota_id
                WHERE m.dueno_id = :dueno_id
                ORDER BY p.inicio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByPaseador(int $paseadorId): array
    {
        $sql = "SELECT p.*, 
                       d.nombre AS nombre_dueno,
                       m.nombre AS nombre_mascota
                FROM paseos p
                LEFT JOIN mascotas m ON m.mascota_id = p.mascota_id
                LEFT JOIN usuarios d ON d.usu_id = m.dueno_id
                WHERE p.paseador_id = :paseador_id
                ORDER BY p.inicio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['paseador_id' => $paseadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 🔹 Devuelve un paseo con nombre del paseador y datos principales
     * usado en pagar_paseo.php
     */
    public function findWithRelations(int $id): ?array
    {
        $sql = "SELECT p.*, 
                       u.usu_id AS paseador_id,
                       u.nombre AS paseador_nombre,
                       m.nombre AS mascota_nombre
                FROM paseos p
                LEFT JOIN usuarios u ON u.usu_id = p.paseador_id
                LEFT JOIN mascotas m ON m.mascota_id = p.mascota_id
                WHERE p.paseo_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return [
            'paseo_id'         => $row['paseo_id'],
            'paseador_id'      => $row['paseador_id'],
            'paseador_nombre'  => $row['paseador_nombre'],
            'mascota_nombre'   => $row['mascota_nombre'],
            'fecha'            => $row['inicio'] ?? '',
            'monto'            => $row['precio_total'] ?? 0,
            'estado'           => $row['estado'] ?? 'pendiente'
        ];
    }

    public function findSolicitudesPendientes(int $paseadorId): array
    {
        $sql = "SELECT p.paseo_id, p.inicio, p.duracion, p.precio_total, p.estado,
                       m.nombre AS nombre_mascota,
                       u.nombre AS nombre_dueno
                FROM paseos p
                INNER JOIN mascotas m ON p.mascota_id = m.mascota_id
                INNER JOIN usuarios u ON m.dueno_id = u.usu_id
                WHERE p.paseador_id = :paseador_id
                  AND p.estado = 'Pendiente'
                ORDER BY p.inicio ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['paseador_id' => $paseadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================
    // REPORTES Y ESTADÍSTICAS
    // =========================
    public function getGananciasPorPaseador(int $paseadorId): float
    {
        $sql = "SELECT SUM(precio_total) as total
                FROM paseos
                WHERE paseador_id = :paseador_id
                  AND estado = 'completo'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['paseador_id' => $paseadorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['total'] ? (float)$row['total'] : 0.0;
    }

    public function contarPorEstado(int $paseadorId, string $estado): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM paseos
                WHERE paseador_id = :paseador_id
                  AND estado = :estado";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'paseador_id' => $paseadorId,
            'estado'      => $estado
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function historialCompletados(int $paseadorId): array
    {
        $sql = "SELECT p.*, 
                       m.nombre AS nombre_mascota,
                       u.nombre AS nombre_dueno
                FROM paseos p
                INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
                INNER JOIN usuarios u ON m.dueno_id = u.usu_id
                WHERE p.paseador_id = :paseador_id
                  AND p.estado = 'completo'
                ORDER BY p.inicio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['paseador_id' => $paseadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByPaseador(int $paseadorId): array
    {
        return $this->findByPaseador($paseadorId);
    }
    public function getById(int $id): ?array
    {
        $sql = "SELECT 
                p.paseo_id,
                p.inicio,
                p.duracion,
                p.precio_total,
                p.estado,
                p.estado_pago,
                p.puntos_ganados,
                p.created_at,
                p.updated_at,
                u.nombre AS paseador_nombre,
                m.nombre AS mascota_nombre
            FROM paseos p
            LEFT JOIN usuarios u ON u.usu_id = p.paseador_id
            LEFT JOIN mascotas m ON m.mascota_id = p.mascota_id
            WHERE p.paseo_id = :id
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        // 🔹 Normalizamos los nombres esperados por la vista VerPaseo.php
        return [
            'paseo_id'        => $row['paseo_id'],
            'mascota_nombre'  => $row['mascota_nombre'] ?? '—',
            'paseador_nombre' => $row['paseador_nombre'] ?? '—',
            'fecha_inicio'    => $row['inicio'] ?? null,
            'duracion'        => $row['duracion'],
            'monto'           => $row['precio_total'],
            'estado'          => $row['estado'],
            'estado_pago'     => $row['estado_pago'],
            'puntos_ganados'  => $row['puntos_ganados'],
            'creado'          => $row['created_at'],
            'actualizado'     => $row['updated_at']
        ];
    }
}
