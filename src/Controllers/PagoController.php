<?php

declare(strict_types=1);

namespace Jaguata\Controllers;

use Jaguata\Models\Pago;
use Jaguata\Helpers\Session;
use Jaguata\Services\DatabaseService;
use PDO;
use PDOException;
use Exception;

class PagoController
{
    private PDO $db;
    private Pago $pagoModel;

    public function __construct()
    {
        $this->db        = DatabaseService::getInstance()->getConnection();
        $this->pagoModel = new Pago();
    }

    /**
     * Listado general para ADMIN (todos los pagos).
     */
    public function index(?string $estado = null): array
    {
        try {
            return $this->pagoModel->getAdminList($estado);
        } catch (PDOException $e) {
            error_log('PagoController::index error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Listado filtrado por estado (admin).
     * $estado: 'pendiente', 'pagado', etc.
     */
    public function getByEstado(string $estado): array
    {
        if (!Session::isLoggedIn() || Session::getUsuarioRol() !== 'admin') {
            return [];
        }

        $estado = strtolower(trim($estado));
        if ($estado === '') {
            return $this->index();
        }

        try {
            return $this->pagoModel->getAdminList($estado);
        } catch (PDOException $e) {
            error_log('PagoController::getByEstado error => ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear pago desde el lado del DUEÑO (gastos del dueño).
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

        $estado = ($data['metodo'] === 'efectivo')
            ? 'confirmado_por_dueno'
            : 'pendiente';

        $payload = [
            'paseo_id'    => (int) $data['paseo_id'],
            'usuario_id'  => (int) $data['usuario_id'],
            'metodo'      => (string) $data['metodo'],
            'banco'       => $data['banco'] ?? null,
            'cuenta'      => $data['cuenta'] ?? null,
            'comprobante' => $data['comprobante'] ?? null,
            'alias'       => $data['alias'] ?? null,
            'referencia'  => $data['referencia'] ?? null,
            'monto'       => (float) $data['monto'],
            'estado'      => $estado,
            'observacion' => $data['observacion'] ?? null,
        ];

        try {
            $id = $this->pagoModel->create($payload);

            // Si es efectivo, marcamos el paseo como procesado directamente
            if ($data['metodo'] === 'efectivo') {
                $st = $this->db->prepare(
                    "UPDATE paseos 
                     SET estado_pago = 'procesado' 
                     WHERE paseo_id = :id"
                );
                $st->execute([':id' => (int) $data['paseo_id']]);
            }

            return ['success' => true, 'id' => $id, 'estado' => $estado];
        } catch (Exception $e) {
            error_log('Error crearPagoDueno: ' . $e->getMessage());
            return ['error' => 'Error interno al crear el pago'];
        }
    }

    /**
     * Alias por si desde el front llamás a /api/pagos/crear
     */
    public function crear(array $data): array
    {
        return $this->crearPagoDueno($data);
    }

    /**
     * El paseador confirma que recibió el pago.
     */
    public function confirmarPago(int $pagoId, ?string $observacion = null): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $ok = $this->pagoModel->updateEstado($pagoId, 'confirmado_por_paseador', $observacion);

            if ($ok) {
                // Marcamos el paseo como procesado
                $sql = "
                    UPDATE paseos p 
                    JOIN pagos g ON g.paseo_id = p.paseo_id 
                    SET p.estado_pago = 'procesado' 
                    WHERE g.id = :pid
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':pid' => $pagoId]);
            }

            return $ok ? ['success' => true] : ['error' => 'No se pudo confirmar el pago'];
        } catch (Exception $e) {
            error_log('Error confirmarPago: ' . $e->getMessage());
            return ['error' => 'Error interno al confirmar el pago'];
        }
    }

    /**
     * Marca un pago con observación (rechazado / necesita revisión).
     */
    public function observarPago(int $pagoId, ?string $observacion = null): array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        try {
            $ok = $this->pagoModel->updateEstado($pagoId, 'observacion', $observacion);
            return $ok ? ['success' => true] : ['error' => 'No se pudo marcar observación'];
        } catch (Exception $e) {
            error_log('Error observarPago: ' . $e->getMessage());
            return ['error' => 'Error interno al observar el pago'];
        }
    }

    /**
     * Reporte de gastos para el dueño (GastosTotales.php).
     */
    public function listarGastosDueno(array $filters): array
    {
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

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function filtrar(array $filtros): array
    {
        $sql = "
        SELECT 
            pg.id,
            pg.paseo_id,
            u.nombre AS usuario,
            pg.metodo,
            pg.banco,
            pg.cuenta,
            pg.monto,
            pg.estado,
            pg.created_at AS fecha
        FROM pagos pg
        INNER JOIN usuarios u ON u.usu_id = pg.usuario_id
        WHERE 1=1
    ";

        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND LOWER(pg.estado) = LOWER(:estado)";
            $params['estado'] = $filtros['estado'];
        }

        if (!empty($filtros['metodo'])) {
            $sql .= " AND LOWER(pg.metodo) = LOWER(:metodo)";
            $params['metodo'] = $filtros['metodo'];
        }

        if (!empty($filtros['banco'])) {
            $sql .= " AND LOWER(pg.banco) LIKE LOWER(:banco)";
            $params['banco'] = "%" . $filtros['banco'] . "%";
        }

        if (!empty($filtros['usuario'])) {
            $sql .= " AND LOWER(u.nombre) LIKE LOWER(:usuario)";
            $params['usuario'] = "%" . $filtros['usuario'] . "%";
        }

        if (!empty($filtros['desde'])) {
            $sql .= " AND DATE(pg.created_at) >= :desde";
            $params['desde'] = $filtros['desde'];
        }

        if (!empty($filtros['hasta'])) {
            $sql .= " AND DATE(pg.created_at) <= :hasta";
            $params['hasta'] = $filtros['hasta'];
        }

        $sql .= " ORDER BY pg.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function detalleAdmin(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        try {
            return $this->pagoModel->getAdminDetalle($id);
        } catch (PDOException $e) {
            error_log('PagoController::detalleAdmin error: ' . $e->getMessage());
            return null;
        }
    }
}
