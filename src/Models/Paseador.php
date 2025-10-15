<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

class Paseador extends BaseModel
{
    protected string $table = 'paseadores';
    protected string $primaryKey = 'paseador_id';

    /**
     * === NUEVO ===
     * Listar todos los paseadores (lo usa PaseadorController::index)
     */
    public function all(): array
    {
        // Orden por recientes (created_at si existe) y luego calificación/paseos
        $sql = "SELECT *
                FROM {$this->table}
                ORDER BY created_at DESC, calificacion DESC, total_paseos DESC, {$this->primaryKey} DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Obtener todos los paseadores disponibles (tu método existente).
     * Nota: conservamos el JOIN a usuarios como ya lo tenías.
     */
    public function getDisponibles(): array
    {
        $sql = "SELECT p.*, u.nombre, u.email
                FROM {$this->table} p
                JOIN usuarios u ON u.usu_id = p.paseador_id
                WHERE p.disponibilidad = 1
                ORDER BY p.calificacion DESC, p.total_paseos DESC, p.{$this->primaryKey} DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Crear un paseador (tu método existente).
     */
    public function createPaseador(array $data): int
    {
        return $this->create($data);
    }

    /**
     * Actualizar datos del paseador (tu método existente).
     */
    public function updatePaseador(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Obtener un paseador por ID (tu método existente).
     */
    public function getById(int $id): ?array
    {
        return $this->find($id) ?: null;
    }

    /**
     * Sumar paseo + calificación (tu método existente).
     * Nota: este promedio es simplificado (no pondera por cantidad de reseñas).
     */
    public function registrarPaseo(int $id, float $nuevaCalificacion): bool
    {
        $sql = "UPDATE {$this->table}
                SET calificacion = :calificacion
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, [
            'id' => $id,
            'calificacion' => $nuevaCalificacion
        ]);
    }

    /**
     * === NUEVO ===
     * Cambiar disponibilidad visible (lo usa PaseadorController::apiSetDisponible)
     */
    public function setDisponible(int $id, bool $estado): bool
    {
        $sql = "UPDATE {$this->table}
                SET disponible = :estado
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, [
            'estado' => (int)$estado,
            'id'     => $id,
        ]);
    }

    /**
     * === NUEVO ===
     * Actualizar calificación (lo usa PaseadorController::apiUpdateCalificacion)
     */
    public function updateCalificacion(int $id, float $valor): bool
    {
        $sql = "UPDATE {$this->table}
                SET calificacion = :cal
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, [
            'cal' => $valor,
            'id'  => $id,
        ]);
    }

    /**
     * === NUEVO ===
     * Incrementar contador de paseos (lo usa PaseadorController::apiIncrementarPaseos)
     */
    public function incrementarPaseos(int $id): bool
    {
        $sql = "UPDATE {$this->table}
                SET total_paseos = total_paseos + 1
                WHERE {$this->primaryKey} = :id";
        return $this->db->executeQuery($sql, [
            'id' => $id,
        ]);
    }
}
