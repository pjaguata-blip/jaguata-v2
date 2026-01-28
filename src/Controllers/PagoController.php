<?php

declare(strict_types=1);
namespace Jaguata\Controllers;
require_once __DIR__ . '/../Services/DatabaseService.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Models/Pago.php';



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

    public function index(?string $estado = null): array
    {
        try {
            return $this->pagoModel->getAdminList($estado);
        } catch (PDOException $e) {
            error_log('PagoController::index error: ' . $e->getMessage());
            return [];
        }
    }

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

        $metodo = strtoupper(trim((string)$data['metodo'])); // EFECTIVO | TRANSFERENCIA
        $estado = ($metodo === 'EFECTIVO' || $metodo === 'TRANSFERENCIA')
            ? 'confirmado_por_dueno'
            : 'pendiente';

        $payload = [
            'paseo_id'    => (int)$data['paseo_id'],
            'usuario_id'  => (int)$data['usuario_id'],
            'metodo'      => $metodo, // guardamos normalizado
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
            $id = $this->pagoModel->create($payload);
            $st = $this->db->prepare("
            UPDATE paseos
            SET estado_pago = 'procesado'
            WHERE paseo_id = :id
        ");
            $st->execute([':id' => (int)$data['paseo_id']]);

            return ['success' => true, 'id' => $id, 'estado' => $estado];
        } catch (Exception $e) {
            error_log('Error crearPagoDueno: ' . $e->getMessage());
            return ['error' => 'Error interno al crear el pago'];
        }
    }

    public function crear(array $data): array
    {
        return $this->crearPagoDueno($data);
    }

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

    public function listarGastosDueno(array $filters): array
    {
        $sql = "
        SELECT 
            pg.id,
            pg.paseo_id,
            UPPER(pg.metodo) AS metodo,
            CASE
                WHEN LOWER(pg.estado) LIKE 'confirmado%' THEN 'CONFIRMADO'
                WHEN LOWER(pg.estado) = 'pendiente'      THEN 'PENDIENTE'
                WHEN LOWER(pg.estado) IN ('rechazado','observacion') THEN 'RECHAZADO'
                ELSE UPPER(pg.estado)
            END AS estado,

            pg.monto,
            pg.referencia,
            pg.observacion,
            DATE(pg.created_at) AS fecha_pago,
            ps.inicio AS fecha_paseo,
            m.nombre AS mascota,
            u_p.nombre AS paseador
        FROM pagos pg
        INNER JOIN paseos ps   ON ps.paseo_id   = pg.paseo_id
        INNER JOIN mascotas m  ON m.mascota_id  = ps.mascota_id
        INNER JOIN usuarios u_p ON u_p.usu_id   = ps.paseador_id
        WHERE m.dueno_id = :dueno_id
    ";

        $params = [':dueno_id' => (int)$filters['dueno_id']];

        if (!empty($filters['from'])) {
            $sql .= " AND DATE(pg.created_at) >= :from";
            $params[':from'] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= " AND DATE(pg.created_at) <= :to";
            $params[':to'] = $filters['to'];
        }

        if (!empty($filters['mascota_id'])) {
            $sql .= " AND m.mascota_id = :mascota_id";
            $params[':mascota_id'] = (int)$filters['mascota_id'];
        }

        if (!empty($filters['paseador_id'])) {
            $sql .= " AND ps.paseador_id = :paseador_id";
            $params[':paseador_id'] = (int)$filters['paseador_id'];
        }

        if (!empty($filters['metodo'])) {
            // UI manda EFECTIVO / TRANSFERENCIA, en BD puede estar en minúscula
            $sql .= " AND UPPER(pg.metodo) = :metodo";
            $params[':metodo'] = strtoupper((string)$filters['metodo']);
        }

        $estadoUI = strtoupper(trim((string)($filters['estado'] ?? '')));
        if ($estadoUI === 'CONFIRMADO' || $estadoUI === '') {
            $sql .= " AND LOWER(pg.estado) LIKE 'confirmado%'";
        } elseif ($estadoUI === 'PENDIENTE') {
            $sql .= " AND LOWER(pg.estado) = 'pendiente'";
        } elseif ($estadoUI === 'RECHAZADO') {
            $sql .= " AND LOWER(pg.estado) IN ('rechazado','observacion')";
        }

        $sql .= " ORDER BY pg.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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

    public function listarPagosDueno(int $duenoId): array
    {
        if ($duenoId <= 0) return [];

        try {
            $sql = "
                SELECT
                    pg.id,
                    pg.paseo_id,
                    pg.usuario_id,
                    pg.metodo,
                    pg.banco,
                    pg.cuenta,
                    pg.alias,
                    pg.referencia,
                    pg.monto,
                    pg.estado,
                    pg.observacion,
                    pg.comprobante,
                    pg.created_at,
                    pg.updated_at,

                    p.inicio,
                    p.duracion,
                    p.cantidad_mascotas,
                    p.mascota_id,
                    p.mascota_id_2,

                    m1.nombre AS mascota_nombre_1,
                    m2.nombre AS mascota_nombre_2

                FROM pagos pg
                INNER JOIN paseos p ON p.paseo_id = pg.paseo_id
                LEFT JOIN mascotas m1 ON m1.mascota_id = p.mascota_id
                LEFT JOIN mascotas m2 ON m2.mascota_id = p.mascota_id_2

                WHERE pg.usuario_id = :dueno_id
                ORDER BY pg.created_at DESC
            ";

            $st = $this->db->prepare($sql);
            $st->execute([':dueno_id' => $duenoId]);

            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('PagoController::listarPagosDueno error: ' . $e->getMessage());
            return [];
        }
    }
    public function obtenerDatosExportacion(): array
{
    try {
        $sql = "
            SELECT
                pg.id              AS pago_id,
                pg.paseo_id        AS paseo_id,
                pg.monto           AS monto,
                pg.estado          AS estado_pago,
                pg.comprobante     AS comprobante,
                pg.created_at      AS pago_creado,
                pg.updated_at      AS pago_actualizado,

                dueno.nombre       AS dueno_nombre,
                dueno.email        AS dueno_email,

                paseador.nombre    AS paseador_nombre,
                paseador.email     AS paseador_email,

                m.nombre           AS mascota_nombre,
                COALESCE(m2.nombre,'') AS mascota2_nombre,
                COALESCE(p.cantidad_mascotas, 1) AS cantidad_mascotas,

                p.inicio           AS paseo_inicio,
                p.duracion         AS paseo_duracion,
                p.estado           AS paseo_estado
            FROM pagos pg
            INNER JOIN paseos p          ON p.paseo_id = pg.paseo_id
            INNER JOIN mascotas m        ON m.mascota_id = p.mascota_id
            LEFT  JOIN mascotas m2       ON m2.mascota_id = p.mascota_id_2
            INNER JOIN usuarios dueno    ON dueno.usu_id = m.dueno_id
            INNER JOIN usuarios paseador ON paseador.usu_id = p.paseador_id
            ORDER BY pg.id DESC
        ";

        $stmt = $this->db->query($sql);
        return $stmt ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

    } catch (\PDOException $e) {
        error_log('❌ PagoController::obtenerDatosExportacion() error: ' . $e->getMessage());
        return [];
    }
}

}
