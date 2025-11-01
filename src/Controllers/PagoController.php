<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Pago;
use Jaguata\Helpers\Session;
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

        $estado = ($data['metodo'] === 'efectivo')
            ? 'confirmado_por_dueno'
            : 'pendiente';

        $payload = [
            'paseo_id'    => (int)$data['paseo_id'],
            'usuario_id'  => (int)$data['usuario_id'],
            'metodo'      => (string)$data['metodo'],
            'banco'       => $data['banco'] ?? null,
            'cuenta'      => $data['cuenta'] ?? null,
            'comprobante' => $data['comprobante'] ?? null,
            'alias'       => $data['alias'] ?? null,
            'referencia'  => $data['referencia'] ?? null,
            'monto'       => (float)$data['monto'],
            'estado'      => $estado,
            'observacion' => $data['observacion'] ?? null,
        ];

        try {
            $id = $this->pago->create($payload);

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

    public function confirmarPago(int $pagoId, ?string $observacion = null): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $ok = $this->pago->updateEstado($pagoId, 'confirmado_por_paseador', $observacion);

            if ($ok) {
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

    public function crear(array $data): array
    {
        return $this->crearPagoDueno($data);
    }

    /**
     * 🔹 Reporte para GastosTotales.php
     */
    public function listarGastosDueno(array $filters): array
    {
        $db = DatabaseService::getInstance();

        $sql = "
            SELECT 
                pg.id,
                pg.paseo_id,
                pg.metodo,
                UPPER(pg.estado) AS estado,
                pg.monto,
                pg.referencia,
                pg.observacion,
                DATE(pg.created_at) AS fecha_pago,
                ps.inicio AS fecha_paseo,
                m.nombre AS mascota,
                u_p.nombre AS paseador
            FROM pagos pg
            INNER JOIN paseos ps ON ps.paseo_id = pg.paseo_id
            INNER JOIN mascotas m ON m.mascota_id = ps.mascota_id
            INNER JOIN usuarios u_p ON u_p.usu_id = ps.paseador_id
            WHERE m.dueno_id = :dueno_id
        ";

        $params = ['dueno_id' => $filters['dueno_id']];

        if (!empty($filters['from'])) {
            $sql .= " AND DATE(pg.created_at) >= :from";
            $params['from'] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= " AND DATE(pg.created_at) <= :to";
            $params['to'] = $filters['to'];
        }

        if (!empty($filters['mascota_id'])) {
            $sql .= " AND m.mascota_id = :mascota_id";
            $params['mascota_id'] = $filters['mascota_id'];
        }

        if (!empty($filters['paseador_id'])) {
            $sql .= " AND ps.paseador_id = :paseador_id";
            $params['paseador_id'] = $filters['paseador_id'];
        }

        if (!empty($filters['metodo'])) {
            $sql .= " AND LOWER(pg.metodo) = LOWER(:metodo)";
            $params['metodo'] = $filters['metodo'];
        }

        if (!empty($filters['estado'])) {
            $sql .= " AND LOWER(pg.estado) = LOWER(:estado)";
            $params['estado'] = $filters['estado'];
        }

        $sql .= " ORDER BY pg.created_at DESC";

        $db = DatabaseService::connection(); // ✅ también devuelve un PDO directamente
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
