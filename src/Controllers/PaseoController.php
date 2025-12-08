<?php

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Services/DatabaseService.php';
require_once __DIR__ . '/../Models/Paseo.php';
require_once __DIR__ . '/../Helpers/Session.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;
use Jaguata\Models\Paseo;
use Jaguata\Helpers\Session;
use PDO;
use PDOException;

AppConfig::init();

class PaseoController
{
    private PDO $db;
    private Paseo $paseoModel;

    public function __construct()
    {
        $this->db         = DatabaseService::getInstance()->getConnection();
        $this->paseoModel = new Paseo();
    }

    /**
     * 🔹 Listado general de paseos (panel ADMIN)
     * Usado en: features/admin/Paseos.php
     */
    public function index(): array
    {
        try {
            return $this->paseoModel->getAllWithRelations();
        } catch (PDOException $e) {
            error_log('PaseoController::index error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔹 Listar paseos asignados a un paseador
     * Usado en: features/paseador/MisPaseos.php y Dashboard paseador
     */
    public function indexForPaseador(int $paseadorId): array
    {
        if ($paseadorId <= 0) {
            return [];
        }

        $sql = "
        SELECT 
            p.paseo_id,
            p.mascota_id,
            p.paseador_id,
            p.inicio,
            p.duracion,
            p.ubicacion,
            p.estado,
            p.precio_total,
            p.estado_pago,
            p.puntos_ganados,
            p.created_at,
            p.updated_at,

            -- 🔹 Datos de la mascota y dueño
            m.nombre      AS mascota_nombre,
            m.foto_url    AS mascota_foto,
            d.nombre      AS dueno_nombre,
            d.telefono    AS dueno_telefono,
            d.ciudad      AS dueno_ciudad,
            d.barrio      AS dueno_barrio,

            -- 🔹 Datos del pago (si existe)
            pg.id         AS pago_id,
            pg.comprobante AS comprobante_archivo,
            pg.estado     AS pago_estado

        FROM paseos p
        INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
        INNER JOIN usuarios d ON d.usu_id     = m.dueno_id
        LEFT JOIN pagos pg 
            ON pg.paseo_id = p.paseo_id
            -- opcional: solo pagos confirmados / procesados
            AND pg.estado IN ('confirmado_por_dueno','confirmado_por_admin','pagado','procesado')

        WHERE p.paseador_id = :paseador_id
        ORDER BY p.inicio DESC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':paseador_id' => $paseadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }



    /**
     * 🔹 Listar paseos de un dueño
     * Usado en: features/dueno/MisPaseos.php y otros
     */
    public function indexByDueno(int $duenoId): array
    {
        if ($duenoId <= 0) {
            return [];
        }

        $sql = "
            SELECT 
                p.paseo_id,
                p.mascota_id,
                p.paseador_id,
                p.inicio,
                p.duracion,
                p.ubicacion,
                p.estado,
                p.precio_total,
                p.estado_pago,
                p.puntos_ganados,
                p.created_at,
                p.updated_at,
                m.nombre       AS mascota_nombre,
                m.foto_url     AS mascota_foto,
                pa.nombre      AS paseador_nombre,
                pa.telefono    AS paseador_telefono,
                pa.ciudad      AS paseador_ciudad,
                pa.barrio      AS paseador_barrio
            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            INNER JOIN usuarios pa ON pa.usu_id   = p.paseador_id
            WHERE m.dueno_id = :dueno_id
            ORDER BY p.inicio DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔹 Listar mascotas de un dueño (para filtros / selects)
     * Usado en: filtros de gastos del dueño
     */
    public function listarMascotasDeDueno(int $duenoId): array
    {
        if ($duenoId <= 0) {
            return [];
        }

        $sql = "
            SELECT 
                mascota_id,
                nombre
            FROM mascotas
            WHERE dueno_id = :dueno_id
            ORDER BY nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔹 Listar paseadores (para filtros / selects)
     * Usado en: filtros de gastos del dueño
     */
    public function listarPaseadores(): array
    {
        $sql = "
            SELECT 
                usu_id,
                nombre
            FROM usuarios
            WHERE rol = 'paseador'
            ORDER BY nombre ASC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔹 Obtener un paseo simple por ID (sin joins)
     * Usado donde solo se requiere el registro directo
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return $this->paseoModel->find($id);
    }

    /**
     * 🔹 Detalle de paseo para vistas de detalle (paseador)
     * Usado en: features/paseador/DetallePaseo.php
     */
    public function show(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = "
        SELECT 
            p.*,
            m.nombre       AS nombre_mascota,
            m.foto_url     AS mascota_foto,
            d.nombre       AS nombre_dueno,
            d.telefono     AS dueno_telefono,
            d.ciudad       AS dueno_ciudad,
            d.barrio       AS dueno_barrio,
            pa.nombre      AS paseador_nombre,
            pa.telefono    AS paseador_telefono,
            -- alias para que 'direccion' exista en la vista
            p.ubicacion    AS direccion
        FROM paseos p
        INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
        INNER JOIN usuarios d ON d.usu_id     = m.dueno_id
        INNER JOIN usuarios pa ON pa.usu_id   = p.paseador_id
        WHERE p.paseo_id = :id
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }


    /**
     * 🔹 Detalle para pantalla de pago (dueño)
     * Usado en: features/dueno/PagarPaseo.php
     */
    public function getDetalleParaPago(int $paseoId): ?array
    {
        if ($paseoId <= 0) {
            return null;
        }

        $sql = "
            SELECT 
                p.paseo_id,
                p.inicio,
                p.duracion           AS duracion_min,
                p.precio_total,
                p.estado,
                p.estado_pago,
                pa.nombre            AS nombre_paseador,
                pa.banco_nombre      AS paseador_banco,
                pa.alias_cuenta      AS paseador_alias,
                pa.cuenta_numero     AS paseador_cuenta
            FROM paseos p
            INNER JOIN usuarios pa ON pa.usu_id = p.paseador_id
            WHERE p.paseo_id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $paseoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * 🔹 Cancelar un paseo (dueño)
     * Usado en: features/dueno/CancelarPaseo.php
     */
    public function cancelarPaseo(int $id, string $motivo = ''): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'error'   => 'ID de paseo inválido'
            ];
        }

        try {
            $sql = "
                UPDATE paseos
                SET estado = 'cancelado',
                    updated_at = NOW()
                WHERE paseo_id = :id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            $ok = $stmt->rowCount() > 0;

            return [
                'success' => $ok,
                'mensaje' => $ok
                    ? 'El paseo fue cancelado correctamente.'
                    : 'No se pudo cancelar el paseo.'
            ];
        } catch (PDOException $e) {
            error_log('PaseoController::cancelarPaseo error: ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'Error al cancelar el paseo.'
            ];
        }
    }
    /**
     * 🔹 Cancelar un paseo (paseador)
     * Usado en: features/Paseador/cancelarPaseoPaseador.php
     */
    public function cancelarPaseoPaseador(int $paseoId, int $paseadorId): array
    {
        if ($paseoId <= 0 || $paseadorId <= 0) {
            return ['success' => false, 'error' => 'Datos inválidos.'];
        }

        $paseo = $this->getById($paseoId);
        if (!$paseo) {
            return ['success' => false, 'error' => 'Paseo no encontrado.'];
        }

        if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorId) {
            return ['success' => false, 'error' => 'No tienes permiso sobre este paseo.'];
        }

        $estadoActual = strtolower($paseo['estado'] ?? '');

        // Permitimos cancelar desde estos estados
        if (!in_array($estadoActual, ['solicitado', 'pendiente', 'confirmado', 'en_curso'], true)) {
            return ['success' => false, 'error' => 'El paseo no se puede cancelar desde su estado actual.'];
        }

        $ok = $this->paseoModel->actualizarEstado($paseoId, 'cancelado');

        return [
            'success' => $ok,
            'mensaje' => $ok
                ? 'Paseo cancelado correctamente.'
                : 'No se pudo cancelar el paseo.'
        ];
    }



    /**
     * 🔹 Datos para exportar paseos (Excel)
     * Usado en: public/api/paseos/exportarPaseos.php
     */
    public function obtenerDatosExportacion(): array
    {
        try {
            $sql = "
                SELECT 
                    p.paseo_id                    AS id,
                    dueno.nombre                  AS dueno_nombre,
                    paseador.nombre               AS paseador_nombre,
                    m.nombre                      AS mascota_nombre,
                    p.inicio                      AS fecha_inicio,
                    p.duracion                    AS duracion,
                    p.precio_total                AS costo,
                    p.estado                      AS estado,
                    p.estado_pago                 AS estado_pago,
                    COALESCE(p.puntos_ganados, 0) AS puntos_ganados
                FROM paseos p
                LEFT JOIN usuarios dueno 
                       ON dueno.usu_id = p.dueno_id
                LEFT JOIN usuarios paseador 
                       ON paseador.usu_id = p.paseador_id
                LEFT JOIN mascotas m 
                       ON m.mascota_id = p.mascota_id
                ORDER BY p.paseo_id DESC
            ";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('❌ Error en obtenerDatosExportacion(): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔹 Detalle de paseo para vista del ADMIN (VerPaseo.php)
     */
    public function getDetalleAdmin(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return $this->paseoModel->getDetalleAdmin($id);
    }

    /**
     * 🔹 Cambiar estado del paseo desde el ADMIN
     * Acciones soportadas: finalizar, cancelar
     */
    public function cambiarEstadoDesdeAdmin(int $id, string $accion): array
    {
        if ($id <= 0) {
            return [
                'ok'      => false,
                'mensaje' => 'ID de paseo inválido.'
            ];
        }

        $accion = strtolower(trim($accion));

        // En tu enum: solicitado, confirmado, en_curso, completo, cancelado
        $estado = match ($accion) {
            'finalizar' => 'completo',
            'cancelar'  => 'cancelado',
            default     => null
        };

        if ($estado === null) {
            return [
                'ok'      => false,
                'mensaje' => 'Acción no válida.'
            ];
        }

        $ok = $this->paseoModel->actualizarEstado($id, $estado);

        return [
            'ok'      => $ok,
            'mensaje' => $ok
                ? ($estado === 'completo'
                    ? 'Paseo finalizado correctamente.'
                    : 'Paseo cancelado correctamente.')
                : 'No se pudo actualizar el estado del paseo.'
        ];
    }
    /**
     * 🔹 Iniciar paseo (paseador)
     * Cambia estado a 'en_curso' si el paseo es del paseador logueado
     */
    public function apiIniciar(int $paseoId): bool
    {
        if ($paseoId <= 0) {
            return false;
        }

        $paseo = $this->getById($paseoId);
        if (!$paseo) {
            return false;
        }

        // Valida que el paseo sea del paseador logueado
        $paseadorActualId = (int)(Session::getUsuarioId() ?? 0);
        if ($paseadorActualId <= 0 || (int)($paseo['paseador_id'] ?? 0) !== $paseadorActualId) {
            return false;
        }

        $estadoActual = strtolower($paseo['estado'] ?? '');
        // Solo permitir iniciar si está confirmado o pendiente
        if (!in_array($estadoActual, ['confirmado', 'pendiente', 'solicitado'], true)) {
            return false;
        }

        return $this->paseoModel->actualizarEstado($paseoId, 'en_curso');
    }

    /**
     * 🔹 Completar paseo (paseador)
     * Cambia estado a 'completo'. Si querés guardar comentario,
     * asegurate de tener una columna en la tabla (ej: comentario_paseador).
     */
    public function completarPaseo(int $paseoId, string $comentario = ''): array
    {
        if ($paseoId <= 0) {
            return ['success' => false, 'error' => 'ID de paseo inválido.'];
        }

        $paseo = $this->getById($paseoId);
        if (!$paseo) {
            return ['success' => false, 'error' => 'Paseo no encontrado.'];
        }

        // Valida que sea del paseador logueado
        $paseadorActualId = (int)(Session::getUsuarioId() ?? 0);
        if ($paseadorActualId <= 0 || (int)($paseo['paseador_id'] ?? 0) !== $paseadorActualId) {
            return ['success' => false, 'error' => 'No tienes permiso sobre este paseo.'];
        }

        $estadoActual = strtolower($paseo['estado'] ?? '');
        if (!in_array($estadoActual, ['en_curso', 'confirmado'], true)) {
            return ['success' => false, 'error' => 'El paseo no se puede completar desde su estado actual.'];
        }

        try {
            // Solo cambiamos el estado a completo
            $ok = $this->paseoModel->actualizarEstado($paseoId, 'completo');

            // 🔸 Si TENÉS una columna para comentario, podés descomentar esto
            /*
            if ($ok && $comentario !== '') {
                $sql = "
                    UPDATE paseos
                    SET comentario_paseador = :comentario,
                        updated_at = NOW()
                    WHERE paseo_id = :id
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':comentario' => $comentario,
                    ':id'         => $paseoId,
                ]);
            }
            */

            return [
                'success' => $ok,
                'error'   => $ok ? null : 'No se pudo marcar el paseo como completo.'
            ];
        } catch (PDOException $e) {
            error_log('PaseoController::completarPaseo error: ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'Error al completar el paseo.'
            ];
        }
    }

    /**
     * 🔹 Solicitudes pendientes para un paseador (estado solicitado/pendiente)
     * Usado en: features/paseador/Solicitudes.php
     */
    public function getSolicitudesPendientes(int $paseadorId): array
    {
        if ($paseadorId <= 0) {
            return [];
        }

        $sql = "
            SELECT 
                p.paseo_id,
                p.mascota_id,
                p.paseador_id,
                p.inicio,
                p.duracion,
                p.precio_total,
                p.estado,
                m.nombre    AS nombre_mascota,
                d.nombre    AS nombre_dueno
            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            INNER JOIN usuarios d ON d.usu_id     = m.dueno_id
            WHERE p.paseador_id = :paseador_id
              AND p.estado IN ('solicitado', 'pendiente')
            ORDER BY p.inicio ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':paseador_id' => $paseadorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔹 Confirmar solicitud de paseo (paseador)
     */
    public function confirmarPaseoPaseador(int $paseoId, int $paseadorId): array
    {
        if ($paseoId <= 0 || $paseadorId <= 0) {
            return ['success' => false, 'error' => 'Datos inválidos.'];
        }

        $paseo = $this->getById($paseoId);
        if (!$paseo) {
            return ['success' => false, 'error' => 'Paseo no encontrado.'];
        }

        if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorId) {
            return ['success' => false, 'error' => 'No tienes permiso sobre este paseo.'];
        }

        $estadoActual = strtolower($paseo['estado'] ?? '');
        if (!in_array($estadoActual, ['solicitado', 'pendiente'], true)) {
            return ['success' => false, 'error' => 'El paseo ya fue gestionado.'];
        }

        $ok = $this->paseoModel->actualizarEstado($paseoId, 'confirmado');

        return [
            'success' => $ok,
            'mensaje' => $ok
                ? 'Solicitud confirmada correctamente.'
                : 'No se pudo confirmar la solicitud.'
        ];
    }

    /**
     * 🔹 Rechazar / cancelar solicitud de paseo (paseador)
     */
    public function rechazarPaseoPaseador(int $paseoId, int $paseadorId): array
    {
        if ($paseoId <= 0 || $paseadorId <= 0) {
            return ['success' => false, 'error' => 'Datos inválidos.'];
        }

        $paseo = $this->getById($paseoId);
        if (!$paseo) {
            return ['success' => false, 'error' => 'Paseo no encontrado.'];
        }

        if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorId) {
            return ['success' => false, 'error' => 'No tienes permiso sobre este paseo.'];
        }

        $estadoActual = strtolower($paseo['estado'] ?? '');
        if (!in_array($estadoActual, ['solicitado', 'pendiente'], true)) {
            return ['success' => false, 'error' => 'El paseo ya fue gestionado.'];
        }

        $ok = $this->paseoModel->actualizarEstado($paseoId, 'cancelado');

        return [
            'success' => $ok,
            'mensaje' => $ok
                ? 'Solicitud rechazada correctamente.'
                : 'No se pudo rechazar la solicitud.'
        ];
    }
    public function obtenerDatosExportacionPaseador(int $paseadorId): array
    {
        return $this->paseoModel->getExportByPaseador($paseadorId);
    }
    public function store(): void
    {
        $duenoId    = (int)(Session::getUsuarioId() ?? 0);

        $mascotaId  = (int)($_POST['mascota_id'] ?? 0);
        $paseadorId = (int)($_POST['paseador_id'] ?? 0);
        $inicio     = trim((string)($_POST['inicio'] ?? ''));
        $duracion   = (int)($_POST['duracion'] ?? 0);
        $ubicacion  = trim((string)($_POST['ubicacion'] ?? ''));

        if ($duenoId <= 0 || $mascotaId <= 0 || $paseadorId <= 0 || $inicio === '' || $duracion <= 0) {
            $_SESSION['error'] = 'Datos incompletos para crear el paseo.';
            return;
        }

        $db = DatabaseService::getInstance()->getConnection();

        $sql = "INSERT INTO paseos (dueno_id, mascota_id, paseador_id, inicio, duracion, ubicacion, estado, created_at)
            VALUES (:dueno_id, :mascota_id, :paseador_id, :inicio, :duracion, :ubicacion, 'pendiente', NOW())";

        $st = $db->prepare($sql);
        $ok = $st->execute([
            ':dueno_id'    => $duenoId,
            ':mascota_id'  => $mascotaId,
            ':paseador_id' => $paseadorId,
            ':inicio'      => $inicio,
            ':duracion'    => $duracion,
            ':ubicacion'   => $ubicacion,
        ]);

        if ($ok) {
            $_SESSION['success'] = 'Paseo solicitado correctamente.';
        } else {
            $_SESSION['error'] = 'Ocurrió un error al solicitar el paseo.';
        }
    }
}
