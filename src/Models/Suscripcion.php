<?php
declare(strict_types=1);

namespace Jaguata\Models;

use PDO;
use Jaguata\Services\DatabaseService;

class Suscripcion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getUltimaPorPaseador(int $paseadorId): ?array
    {
        $sql = "SELECT *
                FROM suscripciones
                WHERE paseador_id = :id
                ORDER BY created_at DESC, id DESC
                LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([':id' => $paseadorId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function tieneActiva(int $paseadorId): bool
    {
        $sql = "SELECT 1
                FROM suscripciones
                WHERE paseador_id = :id
                  AND estado = 'activa'
                  AND (fin IS NULL OR fin >= NOW())
                ORDER BY fin DESC
                LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([':id' => $paseadorId]);
        return (bool)$st->fetchColumn();
    }

    public function crearSolicitud(array $data): int
    {
        $paseadorId = (int)($data['paseador_id'] ?? 0);
        if ($paseadorId <= 0) {
            throw new \InvalidArgumentException('paseador_id inválido');
        }

        $plan  = trim((string)($data['plan'] ?? 'pro'));
        if ($plan === '') $plan = 'pro';

        $monto = (int)($data['monto'] ?? 50000);
        if ($monto <= 0) $monto = 50000;

        $metodo = $data['metodo_pago'] ?? null;
        $metodo = $metodo !== null ? trim((string)$metodo) : null;
        if ($metodo === '') $metodo = null;

        $ref = $data['referencia'] ?? null;
        $ref = $ref !== null ? trim((string)$ref) : null;
        if ($ref === '') $ref = null;
        if ($ref !== null && mb_strlen($ref) > 80) $ref = mb_substr($ref, 0, 80);

        $nota = $data['nota'] ?? null;
        $nota = $nota !== null ? trim((string)$nota) : null;
        if ($nota === '') $nota = null;
        if ($nota !== null && mb_strlen($nota) > 255) $nota = mb_substr($nota, 0, 255);

        $comp = $data['comprobante_path'] ?? null;
        $comp = $comp !== null ? trim((string)$comp) : null;
        if ($comp === '') $comp = null;
        if ($comp !== null && mb_strlen($comp) > 255) $comp = mb_substr($comp, 0, 255);

        $sql = "INSERT INTO suscripciones
                (paseador_id, plan, monto, estado, comprobante_path, metodo_pago, referencia, nota, created_at, updated_at)
                VALUES
                (:paseador_id, :plan, :monto, 'pendiente', :comprobante_path, :metodo_pago, :referencia, :nota, NOW(), NOW())";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':paseador_id'      => $paseadorId,
            ':plan'             => $plan,
            ':monto'            => $monto,
            ':comprobante_path' => $comp,
            ':metodo_pago'      => $metodo,
            ':referencia'       => $ref,
            ':nota'             => $nota,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getPendientes(int $limit = 50): array
{
    $sql = "SELECT s.*, u.nombre AS paseador_nombre, u.email AS paseador_email
            FROM suscripciones s
            JOIN usuarios u ON u.usu_id = s.paseador_id
            WHERE s.estado = 'pendiente'
            ORDER BY s.created_at ASC
            LIMIT :lim";
    $st = $this->db->prepare($sql);
    $st->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}



    public function aprobar(int $suscripcionId): bool
    {
        $st = $this->db->prepare("SELECT paseador_id FROM suscripciones WHERE id = :id LIMIT 1");
        $st->execute([':id' => $suscripcionId]);
        $paseadorId = (int)($st->fetchColumn() ?: 0);

        if ($paseadorId <= 0) return false;

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("
                UPDATE suscripciones
                SET estado = 'vencida', updated_at = NOW()
                WHERE paseador_id = :pid
                  AND estado = 'activa'
            ");
            $st->execute([':pid' => $paseadorId]);

            $st = $this->db->prepare("
                UPDATE suscripciones
                SET estado = 'activa',
                    inicio = NOW(),
                    fin = DATE_ADD(NOW(), INTERVAL 30 DAY),
                    updated_at = NOW()
                WHERE id = :id
            ");
            $ok = $st->execute([':id' => $suscripcionId]);

            $this->db->commit();
            return $ok;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function rechazar(int $suscripcionId, ?string $motivo = null): bool
    {
        $motivo = $motivo !== null ? trim($motivo) : null;
        if ($motivo === '') $motivo = null;
        if ($motivo !== null && mb_strlen($motivo) > 255) $motivo = mb_substr($motivo, 0, 255);

        $sql = "UPDATE suscripciones
                SET estado='rechazada',
                    nota = CASE
                        WHEN :motivo IS NOT NULL AND :motivo <> '' THEN :motivo
                        ELSE nota
                    END,
                    updated_at=NOW()
                WHERE id=:id";
        $st = $this->db->prepare($sql);
        return $st->execute([':id' => $suscripcionId, ':motivo' => $motivo]);
    }

    public function marcarVencidas(): int
    {
        $sql = "UPDATE suscripciones
                SET estado='vencida', updated_at=NOW()
                WHERE estado='activa'
                  AND fin IS NOT NULL
                  AND fin < NOW()";
        return (int)($this->db->exec($sql) ?: 0);
    }


    public function getEstadoActualPorPaseador(int $paseadorId): ?array
{
    $sql = "
        SELECT estado, inicio, fin, monto, plan
        FROM suscripciones
        WHERE paseador_id = :pid
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ";
    $st = $this->db->prepare($sql);
    $st->execute([':pid' => $paseadorId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Para admin: traer estados de suscripción de MUCHOS usuarios de una sola vez (más rápido)
 * Retorna array: [paseador_id => ['estado'=>..., 'inicio'=>..., 'fin'=>..., ...], ...]
 */
public function getEstadosActualesPorPaseadores(array $paseadorIds): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $paseadorIds), fn($v) => $v > 0)));
    if (!$ids) return [];

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Tomamos la última suscripción por paseador (MAX(id) o MAX(created_at))
    $sql = "
        SELECT s.*
        FROM suscripciones s
        INNER JOIN (
            SELECT paseador_id, MAX(id) AS max_id
            FROM suscripciones
            WHERE paseador_id IN ($placeholders)
            GROUP BY paseador_id
        ) x ON x.paseador_id = s.paseador_id AND x.max_id = s.id
    ";

    $st = $this->db->prepare($sql);
    $st->execute($ids);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $map = [];
    foreach ($rows as $r) {
        $pid = (int)($r['paseador_id'] ?? 0);
        if ($pid > 0) {
            $map[$pid] = [
                'estado' => strtolower((string)($r['estado'] ?? '')),
                'inicio' => $r['inicio'] ?? null,
                'fin'    => $r['fin'] ?? null,
                'monto'  => (int)($r['monto'] ?? 0),
                'plan'   => $r['plan'] ?? null,
            ];
        }
    }
    return $map;
}

}
