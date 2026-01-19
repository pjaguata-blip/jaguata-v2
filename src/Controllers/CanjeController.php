<?php
declare(strict_types=1);

namespace Jaguata\Controllers;

use Jaguata\Services\DatabaseService;
use Jaguata\Models\Recompensa;
use Throwable;

class CanjeController
{
    private DatabaseService $db;
    private Recompensa $recompensaModel;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
        $this->recompensaModel = new Recompensa();
    }

    public function canjear(int $usuarioId, int $recompensaId): array
    {
        if ($usuarioId <= 0 || $recompensaId <= 0) {
            return ['success' => false, 'error' => 'Datos inválidos.'];
        }

        $rec = $this->recompensaModel->getById($recompensaId);
        if (!$rec) {
            return ['success' => false, 'error' => 'La recompensa no existe.'];
        }

        // ✅ activa/activo check (soporta ambos)
        $flag = null;
        if (array_key_exists('activo', $rec))  $flag = (int)($rec['activo'] ?? 0);
        if (array_key_exists('activa', $rec))  $flag = (int)($rec['activa'] ?? 0);
        if ($flag !== null && $flag !== 1) {
            return ['success' => false, 'error' => 'La recompensa no está activa.'];
        }

        $costo = (int)($rec['costo_puntos'] ?? 0);
        if ($costo <= 0) {
            return ['success' => false, 'error' => 'Costo inválido.'];
        }

        $row = $this->db->fetchOne("
            SELECT COALESCE(puntos,0) AS puntos
            FROM usuarios
            WHERE usu_id = :id
            LIMIT 1
        ", [':id' => $usuarioId]);

        $saldo = (int)($row['puntos'] ?? 0);
        if ($saldo < $costo) {
            return ['success' => false, 'error' => 'No tenés puntos suficientes.'];
        }

        $titulo = (string)($rec['titulo'] ?? 'Recompensa');

        // ✅ Si ya canjeó hoy la misma recompensa, evitamos duplicados (opcional)
        // (Podés borrar esto si querés permitir canjes ilimitados)
        $ya = $this->db->fetchOne("
            SELECT canje_id
            FROM canjes
            WHERE usuario_id = :uid
              AND recompensa_id = :rid
              AND DATE(created_at) = CURRENT_DATE()
              AND estado IN ('activo','usado')
            LIMIT 1
        ", [':uid' => $usuarioId, ':rid' => $recompensaId]);

        if ($ya) {
            return ['success' => false, 'error' => 'Ya canjeaste esta recompensa hoy.'];
        }

        $this->db->beginTransaction();
        try {
            // 1) Descontar saldo
            $this->db->prepare("
                UPDATE usuarios
                SET puntos = COALESCE(puntos,0) - :costo
                WHERE usu_id = :uid
            ")->execute([
                ':costo' => $costo,
                ':uid'   => $usuarioId
            ]);

            // 2) Guardar canje
            $this->db->prepare("
                INSERT INTO canjes (usuario_id, recompensa_id, puntos_usados, estado, created_at)
                VALUES (:uid, :rid, :pts, 'activo', NOW())
            ")->execute([
                ':uid' => $usuarioId,
                ':rid' => $recompensaId,
                ':pts' => $costo
            ]);

            // 3) Historial de puntos (NEGATIVO)
            $this->db->prepare("
                INSERT INTO puntos (usuario_id, descripcion, puntos, fecha)
                VALUES (:uid, :desc, :pts, NOW())
            ")->execute([
                ':uid'  => $usuarioId,
                ':desc' => "Canje: {$titulo} (#{$recompensaId})",
                ':pts'  => (-1 * $costo)
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'mensaje' => "Canje realizado: {$titulo}. Se descontaron {$costo} puntos."
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Error al canjear: ' . $e->getMessage()];
        }
    }
}
