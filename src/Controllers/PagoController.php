<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Pago;
use Jaguata\Helpers\Session;
use Jaguata\Config\AppConfig;
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

        // Estado inicial
        $estado = ($data['metodo'] === 'efectivo')
            ? 'confirmado_por_dueno' // efectivo: dueño ya confirmó
            : 'pendiente';            // transferencia: esperar confirmación del paseador

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
            return ['success' => true, 'id' => $id, 'estado' => $estado];
        } catch (Exception $e) {
            error_log('Error crearPagoDueno: ' . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    public function confirmarPago(int $pagoId, ?string $observacion = null): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $ok = $this->pago->updateEstado($pagoId, 'confirmado_por_paseador', $observacion);
            return $ok ? ['success' => true] : ['error' => 'No se pudo confirmar'];
        } catch (Exception $e) {
            error_log('Error confirmarPago: ' . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    public function observarPago(int $pagoId, ?string $observacion = null): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $ok = $this->pago->updateEstado($pagoId, 'observacion', $observacion);
            return $ok ? ['success' => true] : ['error' => 'No se pudo actualizar'];
        } catch (Exception $e) {
            error_log('Error observarPago: ' . $e->getMessage());
            return ['error' => 'Error interno'];
        }
    }

    /**
     * Alias para compatibilidad: algunos archivos llaman crear($data)
     * Mantiene el flujo actual usando crearPagoDueno().
     */
    public function crear(array $data): array
    {
        return $this->crearPagoDueno($data);
    }

    /**
     * Reporte para GastosTotales.php
     * Filtros esperados: dueno_id, from, to, mascota_id, paseador_id, metodo, estado
     * Devuelve columnas usadas por la vista.
     */
    public function listarGastosDueno(array $filters): array
    {
        if (!Session::isLoggedIn()) {
            return [];
        }

        $duenoId = (int)($filters['dueno_id'] ?? 0);
        if ($duenoId <= 0) {
            return [];
        }

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
                    p.id,
                    p.fecha_pago,
                    p.monto,
                    UPPER(p.metodo) AS metodo,
                    p.estado AS estado_raw,
                    p.referencia,
                    p.observacion,
                    ps.id        AS paseo_id,
                    ps.fecha     AS fecha_paseo,
                    m.nombre     AS mascota,
                    u.nombre     AS paseador
                FROM pagos p
                JOIN paseos ps  ON ps.id = p.paseo_id
                JOIN mascotas m ON m.id  = ps.mascota_id
                JOIN usuarios u ON u.id  = ps.paseador_id
                WHERE ps.dueno_id = :dueno
            ";

            $params = [':dueno' => $duenoId];

            if (!empty($from)) {
                $sql .= " AND DATE(p.fecha_pago) >= :from";
                $params[':from'] = $from;
            }
            if (!empty($to)) {
                $sql .= " AND DATE(p.fecha_pago) <= :to";
                $params[':to'] = $to;
            }
            if (!empty($mascotaId)) {
                $sql .= " AND ps.mascota_id = :mid";
                $params[':mid'] = (int)$mascotaId;
            }
            if (!empty($paseadorId)) {
                $sql .= " AND ps.paseador_id = :pid";
                $params[':pid'] = (int)$paseadorId;
            }
            if (!empty($metodo)) {
                $sql .= " AND UPPER(p.metodo) = :met";
                $params[':met'] = strtoupper((string)$metodo);
            }

            if (!empty($estado)) {
                $estado = strtoupper((string)$estado);
                if ($estado === 'CONFIRMADO') {
                    $sql .= " AND (p.estado LIKE 'confirmado_%' OR p.estado = 'confirmado')";
                } elseif ($estado === 'PENDIENTE') {
                    $sql .= " AND (p.estado = 'pendiente')";
                } elseif ($estado === 'RECHAZADO') {
                    $sql .= " AND (p.estado = 'rechazado' OR p.estado = 'observacion')";
                }
            }

            $sql .= " ORDER BY p.fecha_pago DESC, p.id DESC";

            $st = $db->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Normalización de estado
            foreach ($rows as &$r) {
                $raw = strtolower((string)($r['estado_raw'] ?? ''));
                if (strpos($raw, 'confirmado') === 0) {
                    $r['estado'] = 'CONFIRMADO';
                } elseif ($raw === 'pendiente') {
                    $r['estado'] = 'PENDIENTE';
                } elseif ($raw === 'rechazado' || $raw === 'observacion') {
                    $r['estado'] = 'RECHAZADO';
                } else {
                    $r['estado'] = strtoupper($raw ?: 'PENDIENTE');
                }
                unset($r['estado_raw']);
            }
            unset($r);

            return $rows;
        } catch (\Throwable $e) {
            error_log('listarGastosDueno: ' . $e->getMessage());
            return [];
        }
    }
}
