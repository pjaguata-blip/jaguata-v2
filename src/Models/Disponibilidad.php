<?php

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

use PDO;

class Disponibilidad extends BaseModel
{
    protected string $table = 'disponibilidades_paseador';
    protected string $primaryKey = 'id';

    public function getByPaseador(int $paseadorId): array
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE paseador_id = :id
                ORDER BY FIELD(dia_semana,
                    'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $paseadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveDisponibilidad(int $paseadorId, array $data): bool
    {
        // 1) borrar disponibilidad anterior
        $del = $this->db->prepare("DELETE FROM {$this->table} WHERE paseador_id = ?");
        $del->execute([$paseadorId]);

        if (empty($data)) {
            return true;
        }

        // 2) insertar nueva
        $sql = "INSERT INTO {$this->table} 
                (paseador_id, dia_semana, hora_inicio, hora_fin, activo)
                VALUES (:p, :d, :i, :f, :a)";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $d) {
            // seguridad mínima
            if (empty($d['dia']) || empty($d['inicio']) || empty($d['fin'])) {
                continue;
            }

            $stmt->execute([
                ':p' => $paseadorId,
                ':d' => $d['dia'],
                ':i' => $d['inicio'],
                ':f' => $d['fin'],
                ':a' => !empty($d['activo']) ? 1 : 0,
            ]);
        }

        return true;
    }
}
