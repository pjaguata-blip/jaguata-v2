<?php
declare(strict_types=1);

namespace Jaguata\Controllers;

use Jaguata\Helpers\Session;
use Jaguata\Models\Calificacion;
use Jaguata\Config\AppConfig;
use PDO;

class CalificacionController
{
    private Calificacion $model;

    public function __construct()
    {
        $this->model = new Calificacion();
    }

    public function calificarPaseador(array $data): array
    {
        if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'dueno') {
            return ['error' => 'No autorizado'];
        }

        $paseoId = (int)($data['paseo_id'] ?? 0);
        $ratedId = (int)($data['rated_id'] ?? 0);
        $calif   = (int)($data['calificacion'] ?? 0);

        if ($paseoId <= 0) return ['error' => 'Falta paseo_id'];
        if ($ratedId <= 0) return ['error' => 'Falta rated_id'];
        if ($calif < 1 || $calif > 5) return ['error' => 'Calificación inválida (1 a 5)'];

        $raterId = (int)(Session::getUsuarioId() ?? 0);
        if ($raterId <= 0) return ['error' => 'Sesión inválida'];

        // Evitar duplicado
        if ($this->model->existeParaPaseo($paseoId, 'paseador', $raterId)) {
            return ['error' => 'Ya calificaste este paseo'];
        }

        $id = $this->model->crear([
            'paseo_id'     => $paseoId,
            'rater_id'     => $raterId,
            'rated_id'     => $ratedId,
            'calificacion' => $calif,
            'comentario'   => trim((string)($data['comentario'] ?? '')),
            'tipo'         => 'paseador',
        ]);

        return ['success' => true, 'id' => $id];
    }

    public function calificarMascota(array $data): array
    {
        if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'paseador') {
            return ['error' => 'No autorizado'];
        }

        $paseoId   = (int)($data['paseo_id'] ?? 0);
        $mascotaId = (int)($data['rated_id'] ?? 0); // ✅ rated_id = mascota_id
        $calif     = (int)($data['calificacion'] ?? 0);

        if ($paseoId <= 0)   return ['error' => 'Falta paseo_id'];
        if ($mascotaId <= 0) return ['error' => 'Falta rated_id (mascota_id)'];
        if ($calif < 1 || $calif > 5) return ['error' => 'Calificación inválida (1 a 5)'];

        $raterId = (int)(Session::getUsuarioId() ?? 0);
        if ($raterId <= 0) return ['error' => 'Sesión inválida'];

        // ✅ Validar que el paseo pertenece al paseador y está completo
        $pdo = AppConfig::db();
        $st = $pdo->prepare("
            SELECT paseo_id, paseador_id, estado, mascota_id, mascota_id_2
            FROM paseos
            WHERE paseo_id = :p
            LIMIT 1
        ");
        $st->execute([':p' => $paseoId]);
        $p = $st->fetch(PDO::FETCH_ASSOC);

        if (!$p) return ['error' => 'Paseo no encontrado'];
        if ((int)$p['paseador_id'] !== $raterId) return ['error' => 'No autorizado para calificar este paseo'];

        $estado = strtolower(trim((string)($p['estado'] ?? '')));
        if (!in_array($estado, ['completo', 'finalizado'], true)) {
            return ['error' => 'Solo se puede calificar cuando el paseo está completado'];
        }

        $m1 = (int)($p['mascota_id'] ?? 0);
        $m2 = (int)($p['mascota_id_2'] ?? 0);

        if (!in_array($mascotaId, array_filter([$m1, $m2]), true)) {
            return ['error' => 'La mascota no corresponde a este paseo'];
        }

        // ✅ Evitar duplicado (1 calificación por paseo para tipo mascota)
        if ($this->model->existeParaPaseo($paseoId, 'mascota', $raterId)) {
            return ['error' => 'Ya calificaste este paseo'];
        }

        $id = $this->model->crear([
            'paseo_id'     => $paseoId,
            'rater_id'     => $raterId,
            'rated_id'     => $mascotaId,
            'calificacion' => $calif,
            'comentario'   => trim((string)($data['comentario'] ?? '')),
            'tipo'         => 'mascota',
        ]);

        return ['success' => true, 'id' => $id];
    }
}
