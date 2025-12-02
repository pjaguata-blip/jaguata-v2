<?php

declare(strict_types=1);

namespace Jaguata\Models;

require_once __DIR__ . '/BaseModel.php';

use PDO;

class Mascota extends BaseModel
{
    protected string $table      = 'mascotas';
    protected string $primaryKey = 'mascota_id';

    public function __construct()
    {
        parent::__construct();
    }

    public function getByDueno(int $duenoId): array
    {
        $sql = "
            SELECT 
                mascota_id,
                dueno_id,
                nombre,
                raza,
                peso_kg,
                tamano,
                edad_meses,
                observaciones,
                foto_url,
                created_at,
                updated_at
            FROM mascotas
            WHERE dueno_id = :dueno_id
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
