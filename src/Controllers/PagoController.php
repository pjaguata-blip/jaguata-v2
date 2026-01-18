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

        // ✅ NORMALIZAR método (acepta EFECTIVO / efectivo / Transferencia, etc.)
        $metodo = strtoupper(trim((string)$data['metodo'])); // EFECTIVO | TRANSFERENCIA

        // ✅ Para el dueño: si ya cargó el pago, lo marcamos como confirmado por dueño
        // (así en Paseador ya se ve “Pagado” si vos querés eso)
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

            // ✅ CLAVE: reflejar en la tabla paseos lo que usa tu pantalla del paseador
            // Tu MisPaseos.php muestra pago leyendo: $p['estado_pago']
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
            UPPER(pg.metodo) AS metodo,

            /* ✅ Normalizamos el estado para la UI */
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

        /* ✅ Estado UI: CONFIRMADO / PENDIENTE / RECHAZADO
       Si NO se envía estado => por defecto mostramos SOLO confirmados */
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
    /**
     * ✅ Lista pagos del dueño (usuario_id en pagos = dueño)
     * Trae también info del paseo + mascotas (incluye 2 mascotas)
     */
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
}
