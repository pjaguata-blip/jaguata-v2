<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Pago;
use Jaguata\Helpers\Session;
use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;
use PDO;
use Exception;

class PagoController
{
    private Pago $pago;

    public function __construct()
    {
        $this->pago = new Pago();
    }

    /**
     * Crea registro de pago hecho por el dueño
     * Requiere: paseo_id, usuario_id(paseador), metodo, monto
     * Opcionales: banco, cuenta, comprobante, alias, referencia, observacion
     */
    public function crearPagoDueno(array $data): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $requeridos = ['paseo_id', 'usuario_id', 'metodo', 'monto'];
        foreach ($requeridos as $k) {
            if (!isset($data[$k]) || $data[$k] === '') {
                return ['error' => "Falta $k"];
            }
        }

        // Estado inicial según método
        $estado = ($data['metodo'] === 'efectivo')
            ? 'confirmado_por_dueno' // efectivo: dueño ya confirmó
            : 'pendiente';            // transferencia: espera paseador

        $payload = [
            'paseo_id'    => (int)$data['paseo_id'],
            'usuario_id'  => (int)$data['usuario_id'],
            'metodo'      => (string)$data['metodo'],
            'banco'       => $data['banco']       ?? null,
            'cuenta'      => $data['cuenta']      ?? null,
            'comprobante' => $data['comprobante'] ?? null,
            'alias'       => $data['alias']       ?? null,
            'referencia'  => $data['referencia']  ?? null,
            'monto'       => (float)$data['monto'],
            'estado'      => $estado,
            'observacion' => $data['observacion'] ?? null,
        ];

        try {
            $id = $this->pago->create($payload);

            // 🔹 Actualizar estado del paseo si el método es efectivo
            if ($data['metodo'] === 'efectivo') {
                $db = DatabaseService::getInstance()->getConnection();
                $st = $db->prepare("UPDATE paseos SET estado_pago = 'procesado' WHERE paseo_id = :id");
                $st->execute([':id' => (int)$data['paseo_id']]);
            }

            return ['success' => true, 'id' => $id, 'estado' => $estado];
        } catch (Exception $e) {
            error_log('Error crearPagoDueno: ' . $e->getMessage());
            return ['error' => 'Error interno al crear el pago'];
        }
    }

    /**
     * Confirmar pago (paseador)
     */
    public function confirmarPago(int $pagoId, ?string $observacion = null): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $ok = $this->pago->updateEstado($pagoId, 'confirmado_por_paseador', $observacion);

            if ($ok) {
                // 🔹 Marcar el paseo como pagado
                $db = DatabaseService::getInstance()->getConnection();
                $db->prepare("UPDATE paseos p 
                              JOIN pagos g ON g.paseo_id = p.paseo_id 
                              SET p.estado_pago = 'procesado' 
                              WHERE g.id = :pid")->execute([':pid' => $pagoId]);
            }

            return $ok ? ['success' => true] : ['error' => 'No se pudo confirmar el pago'];
        } catch (Exception $e) {
            error_log('Error confirmarPago: ' . $e->getMessage());
            return ['error' => 'Error interno al confirmar el pago'];
        }
    }

    /**
     * Marcar pago observado o rechazado por el paseador
     */
    public function observarPago(int $pagoId, ?string $observacion = null): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $ok = $this->pago->updateEstado($pagoId, 'observacion', $observacion);
            return $ok ? ['success' => true] : ['error' => 'No se pudo marcar observación'];
        } catch (Exception $e) {
            error_log('Error observarPago: ' . $e->getMessage());
            return ['error' => 'Error interno al observar el pago'];
        }
    }

    /**
     * Alias para compatibilidad: algunos archivos llaman crear($data)
     */
    public function crear(array $data): array
    {
        return $this->crearPagoDueno($data);
    }

    /**
     * Reporte para GastosTotales.php
     */
    public function listarGastosDueno(array $filters): array
    {
        if (!Session::isLoggedIn()) {
            return [];
        }

        $duenoId = (int)($filters['dueno_id'] ?? 0);
        if ($duenoId <= 0) return [];

        $from       = $filters['from']        ?? null;
        $to         = $filters['to']          ?? null;
        $mascotaId  = $filters['mascota_id']  ?? null;
        $paseadorId = $filters['paseador_id'] ?? null;
        $metodo     = $filters['metodo']      ?? null;
        $estado     = $filters['estado']      ?? null;

        try {
            $db = AppConfig::db();
            $sql = "
                SELECT
                    pg.id,
                    pg.fecha_pago,
                    pg.monto,
                    UPPER(pg.metodo) AS metodo,
                    pg.estado AS estado_raw,
                    pg.referencia,
                    pg.observacion,
                    ps.paseo_id,
                    ps.inicio AS fecha_paseo,
                    m.nombre  AS mascota,
                    u.nombre  AS paseador
                FROM pagos pg
                INNER JOIN paseos ps  ON ps.paseo_id = pg.paseo_id
                INNER JOIN mascotas m ON m.mascota_id = ps.mascota_id
                INNER JOIN usuarios u ON u.usu_id = ps.paseador_id
                WHERE m.dueno_id = :dueno
            ";

            $params = [':dueno' => $duenoId];

            if ($from) {
                $sql .= " AND DATE(pg.fecha_pago) >= :from";
                $params[':from'] = $from;
            }
            if ($to) {
                $sql .= " AND DATE(pg.fecha_pago) <= :to";
                $params[':to'] = $to;
            }
            if ($mascotaId) {
                $sql .= " AND ps.mascota_id = :mid";
                $params[':mid'] = (int)$mascotaId;
            }
            if ($paseadorId) {
                $sql .= " AND ps.paseador_id = :pid";
                $params[':pid'] = (int)$paseadorId;
            }
            if ($metodo) {
                $sql .= " AND UPPER(pg.metodo) = :met";
                $params[':met'] = strtoupper($metodo);
            }

            if ($estado) {
                $estado = strtoupper($estado);
                if ($estado === 'CONFIRMADO') {
                    $sql .= " AND (pg.estado LIKE 'confirmado_%' OR pg.estado = 'confirmado')";
                } elseif ($estado === 'PENDIENTE') {
                    $sql .= " AND (pg.estado = 'pendiente')";
                } elseif ($estado === 'RECHAZADO') {
                    $sql .= " AND (pg.estado IN ('rechazado', 'observacion'))";
                }
            }

            $sql .= " ORDER BY pg.fecha_pago DESC, pg.id DESC";

            $st = $db->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as &$r) {
                $raw = strtolower((string)($r['estado_raw'] ?? ''));
                if (str_starts_with($raw, 'confirmado')) {
                    $r['estado'] = 'CONFIRMADO';
                } elseif ($raw === 'pendiente') {
                    $r['estado'] = 'PENDIENTE';
                } elseif (in_array($raw, ['rechazado', 'observacion'])) {
                    $r['estado'] = 'RECHAZADO';
                } else {
                    $r['estado'] = strtoupper($raw ?: 'PENDIENTE');
                }
                unset($r['estado_raw']);
            }
            unset($r);

            return $rows;
        } catch (Exception $e) {
            error_log('listarGastosDueno: ' . $e->getMessage());
            return [];
        }
    }
}
