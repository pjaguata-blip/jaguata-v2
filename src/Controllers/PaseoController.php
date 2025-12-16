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

    /** 🔹 Listado general de paseos (ADMIN) */
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
                p.mascota_id_2,
                p.cantidad_mascotas,
                p.paseador_id,
                p.inicio,
                p.duracion,
                p.ubicacion,
                p.pickup_lat,
                p.pickup_lng,
                p.estado,
                p.precio_total,
                p.estado_pago,
                p.puntos_ganados,
                p.created_at,
                p.updated_at,

                -- Mascota 1
                m.nombre    AS mascota_nombre,
                m.foto_url  AS mascota_foto,

                -- Mascota 2 (opcional)
                m2.nombre   AS mascota2_nombre,
                m2.foto_url AS mascota2_foto,

                -- Dueño
                d.nombre    AS dueno_nombre,
                d.telefono  AS dueno_telefono,
                d.ciudad    AS dueno_ciudad,
                d.barrio    AS dueno_barrio,

                -- Pago (si existe)
                pg.id          AS pago_id,
                pg.comprobante AS comprobante_archivo,
                pg.estado      AS pago_estado,

                -- Calificación dueño->paseador
                c.calificacion AS calificacion,
                c.comentario   AS calificacion_comentario,
                c.created_at   AS calificacion_fecha

            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            LEFT  JOIN mascotas m2 ON m2.mascota_id = p.mascota_id_2
            INNER JOIN usuarios d ON d.usu_id = m.dueno_id

            LEFT JOIN pagos pg 
                ON pg.paseo_id = p.paseo_id
                AND pg.estado IN ('confirmado_por_dueno','confirmado_por_admin','pagado','procesado')

            LEFT JOIN calificaciones c
                ON c.paseo_id = p.paseo_id
               AND c.tipo     = 'paseador'
               AND c.rated_id = p.paseador_id

            WHERE p.paseador_id = :paseador_id
            ORDER BY p.inicio DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':paseador_id' => $paseadorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔹 Listar paseos de un dueño
     * Usado en: features/dueno/MisPaseos.php
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
            p.mascota_id_2,
            p.cantidad_mascotas,

            p.paseador_id,
            p.inicio,
            p.duracion,
            p.ubicacion,
            p.pickup_lat,
            p.pickup_lng,
            p.estado,
            p.precio_total,
            p.estado_pago,
            p.puntos_ganados,
            p.created_at,
            p.updated_at,

            -- Mascota 1
            m.nombre       AS nombre_mascota,
            m.foto_url     AS mascota_foto,

            -- Mascota 2 (opcional)
            m2.nombre      AS nombre_mascota_2,
            m2.foto_url    AS mascota_foto_2,

            -- Paseador
            pa.nombre      AS nombre_paseador,
            pa.telefono    AS paseador_telefono,
            pa.ciudad      AS paseador_ciudad,
            pa.barrio      AS paseador_barrio

        FROM paseos p
        INNER JOIN mascotas m  ON m.mascota_id  = p.mascota_id
        LEFT  JOIN mascotas m2 ON m2.mascota_id = p.mascota_id_2
        INNER JOIN usuarios pa ON pa.usu_id     = p.paseador_id
        WHERE m.dueno_id = :dueno_id
        ORDER BY p.inicio DESC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    /** 🔹 Listar mascotas de un dueño (para selects) */
    public function listarMascotasDeDueno(int $duenoId): array
    {
        if ($duenoId <= 0) return [];

        $sql = "
            SELECT mascota_id, nombre
            FROM mascotas
            WHERE dueno_id = :dueno_id
            ORDER BY nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dueno_id' => $duenoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** 🔹 Listar paseadores (para selects) */
    public function listarPaseadores(): array
    {
        $sql = "
            SELECT usu_id, nombre
            FROM usuarios
            WHERE rol = 'paseador'
            ORDER BY nombre ASC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** 🔹 Obtener un paseo simple por ID */
    public function getById(int $id): ?array
    {
        if ($id <= 0) return null;
        return $this->paseoModel->find($id);
    }

    /**
     * 🔹 Detalle de paseo (paseador / dueño)
     * Usado en: features/paseador/VerPaseo.php y ver paseo del dueño
     */
    public function show(int $id): ?array
    {
        if ($id <= 0) return null;

        $sql = "
            SELECT 
                p.*,

                -- Mascota 1
                m.nombre       AS nombre_mascota,
                m.foto_url     AS mascota_foto,

                -- Mascota 2 (opcional)
                p.mascota_id_2,
                p.cantidad_mascotas,
                m2.nombre      AS nombre_mascota_2,
                m2.foto_url    AS mascota_foto_2,

                -- Dueño
                d.usu_id       AS dueno_id,
                d.nombre       AS nombre_dueno,
                d.telefono     AS dueno_telefono,
                d.ciudad       AS dueno_ciudad,
                d.barrio       AS dueno_barrio,

                -- Paseador
                pa.nombre      AS paseador_nombre,
                pa.telefono    AS paseador_telefono,

                -- alias para que 'direccion' exista en la vista
                p.ubicacion    AS direccion

            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            LEFT  JOIN mascotas m2 ON m2.mascota_id = p.mascota_id_2
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

    /** 🔹 Detalle para pantalla de pago (dueño) */
    public function getDetalleParaPago(int $paseoId): ?array
    {
        if ($paseoId <= 0) return null;

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

    /** 🔹 Cancelar un paseo (dueño) */
    public function cancelarPaseo(int $id, string $motivo = ''): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'ID de paseo inválido'];
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
                'mensaje' => $ok ? 'El paseo fue cancelado correctamente.' : 'No se pudo cancelar el paseo.'
            ];
        } catch (PDOException $e) {
            error_log('PaseoController::cancelarPaseo error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error al cancelar el paseo.'];
        }
    }

    /** 🔹 Cancelar un paseo (paseador) */
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
        if (!in_array($estadoActual, ['solicitado', 'pendiente', 'confirmado', 'en_curso'], true)) {
            return ['success' => false, 'error' => 'El paseo no se puede cancelar desde su estado actual.'];
        }

        $ok = $this->paseoModel->actualizarEstado($paseoId, 'cancelado');

        return [
            'success' => $ok,
            'mensaje' => $ok ? 'Paseo cancelado correctamente.' : 'No se pudo cancelar el paseo.'
        ];
    }

    /** 🔹 Datos para exportar paseos (Excel) */
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
                LEFT JOIN mascotas m 
                       ON m.mascota_id = p.mascota_id
                LEFT JOIN usuarios dueno 
                       ON dueno.usu_id = m.dueno_id
                LEFT JOIN usuarios paseador 
                       ON paseador.usu_id = p.paseador_id
                ORDER BY p.paseo_id DESC
            ";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('❌ Error en obtenerDatosExportacion(): ' . $e->getMessage());
            return [];
        }
    }

    /** 🔹 Detalle de paseo para vista del ADMIN */
    public function getDetalleAdmin(int $id): ?array
    {
        if ($id <= 0) return null;
        return $this->paseoModel->getDetalleAdmin($id);
    }

    /** 🔹 Cambiar estado del paseo desde el ADMIN */
    public function cambiarEstadoDesdeAdmin(int $id, string $accion): array
    {
        if ($id <= 0) return ['ok' => false, 'mensaje' => 'ID de paseo inválido.'];

        $accion = strtolower(trim($accion));
        $estado = match ($accion) {
            'finalizar' => 'completo',
            'cancelar'  => 'cancelado',
            default     => null
        };

        if ($estado === null) return ['ok' => false, 'mensaje' => 'Acción no válida.'];

        $ok = $this->paseoModel->actualizarEstado($id, $estado);

        return [
            'ok'      => $ok,
            'mensaje' => $ok
                ? ($estado === 'completo' ? 'Paseo finalizado correctamente.' : 'Paseo cancelado correctamente.')
                : 'No se pudo actualizar el estado del paseo.'
        ];
    }

    /** 🔹 Iniciar paseo (paseador) */
    public function apiIniciar(int $paseoId): bool
    {
        if ($paseoId <= 0) return false;

        $paseo = $this->getById($paseoId);
        if (!$paseo) return false;

        $paseadorActualId = (int)(Session::getUsuarioId() ?? 0);
        if ($paseadorActualId <= 0 || (int)($paseo['paseador_id'] ?? 0) !== $paseadorActualId) {
            return false;
        }

        $estadoActual = strtolower($paseo['estado'] ?? '');
        if (!in_array($estadoActual, ['confirmado', 'pendiente', 'solicitado'], true)) return false;

        return $this->paseoModel->actualizarEstado($paseoId, 'en_curso');
    }

    /** 🔹 Completar paseo (paseador) */
    public function completarPaseo(int $paseoId, string $comentario = ''): array
    {
        if ($paseoId <= 0) return ['success' => false, 'error' => 'ID de paseo inválido.'];

        $paseo = $this->getById($paseoId);
        if (!$paseo) return ['success' => false, 'error' => 'Paseo no encontrado.'];

        $paseadorActualId = (int)(Session::getUsuarioId() ?? 0);
        if ($paseadorActualId <= 0 || (int)($paseo['paseador_id'] ?? 0) !== $paseadorActualId) {
            return ['success' => false, 'error' => 'No tienes permiso sobre este paseo.'];
        }

        $estadoActual = strtolower($paseo['estado'] ?? '');
        if (!in_array($estadoActual, ['en_curso', 'confirmado'], true)) {
            return ['success' => false, 'error' => 'El paseo no se puede completar desde su estado actual.'];
        }

        try {
            $ok = $this->paseoModel->actualizarEstado($paseoId, 'completo');
            return ['success' => $ok, 'error' => $ok ? null : 'No se pudo marcar el paseo como completo.'];
        } catch (PDOException $e) {
            error_log('PaseoController::completarPaseo error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error al completar el paseo.'];
        }
    }

    /** 🔹 Solicitudes pendientes para un paseador */
    public function getSolicitudesPendientes(int $paseadorId): array
    {
        if ($paseadorId <= 0) return [];

        $sql = "
            SELECT 
                p.paseo_id,
                p.mascota_id,
                p.mascota_id_2,
                p.cantidad_mascotas,
                p.paseador_id,
                p.inicio,
                p.duracion,
                p.precio_total,
                p.estado,

                m.nombre  AS nombre_mascota,
                m2.nombre AS nombre_mascota_2,
                d.nombre  AS nombre_dueno

            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            LEFT  JOIN mascotas m2 ON m2.mascota_id = p.mascota_id_2
            INNER JOIN usuarios d ON d.usu_id     = m.dueno_id
            WHERE p.paseador_id = :paseador_id
              AND p.estado IN ('solicitado', 'pendiente')
            ORDER BY p.inicio ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':paseador_id' => $paseadorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** 🔹 Confirmar solicitud (paseador) */
    public function confirmarPaseoPaseador(int $paseoId, int $paseadorId): array
    {
        if ($paseoId <= 0 || $paseadorId <= 0) return ['success' => false, 'error' => 'Datos inválidos.'];

        $paseo = $this->getById($paseoId);
        if (!$paseo) return ['success' => false, 'error' => 'Paseo no encontrado.'];

        if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorId) {
            return ['success' => false, 'error' => 'No tienes permiso sobre este paseo.'];
        }

        $estadoActual = strtolower($paseo['estado'] ?? '');
        if (!in_array($estadoActual, ['solicitado', 'pendiente'], true)) {
            return ['success' => false, 'error' => 'El paseo ya fue gestionado.'];
        }

        $ok = $this->paseoModel->actualizarEstado($paseoId, 'confirmado');

        return ['success' => $ok, 'mensaje' => $ok ? 'Solicitud confirmada correctamente.' : 'No se pudo confirmar la solicitud.'];
    }

    /** 🔹 Rechazar/cancelar solicitud (paseador) */
    public function rechazarPaseoPaseador(int $paseoId, int $paseadorId): array
    {
        if ($paseoId <= 0 || $paseadorId <= 0) return ['success' => false, 'error' => 'Datos inválidos.'];

        $paseo = $this->getById($paseoId);
        if (!$paseo) return ['success' => false, 'error' => 'Paseo no encontrado.'];

        if ((int)($paseo['paseador_id'] ?? 0) !== $paseadorId) {
            return ['success' => false, 'error' => 'No tienes permiso sobre este paseo.'];
        }

        $estadoActual = strtolower($paseo['estado'] ?? '');
        if (!in_array($estadoActual, ['solicitado', 'pendiente'], true)) {
            return ['success' => false, 'error' => 'El paseo ya fue gestionado.'];
        }

        $ok = $this->paseoModel->actualizarEstado($paseoId, 'cancelado');

        return ['success' => $ok, 'mensaje' => $ok ? 'Solicitud rechazada correctamente.' : 'No se pudo rechazar la solicitud.'];
    }

    /** 🔹 Datos exportación paseador */
    public function obtenerDatosExportacionPaseador(int $paseadorId): array
    {
        return $this->paseoModel->getExportByPaseador($paseadorId);
    }

    /**
     * 🔹 Guardar nueva solicitud de paseo (dueño)
     * 2 mascotas máx + descuento 30% si son 2
     */
    public function store(): void
    {
        $duenoId = (int)(Session::getUsuarioId() ?? 0);

        $mascota1 = (int)($_POST['mascota_id_1'] ?? 0);
        $mascota2 = (int)($_POST['mascota_id_2'] ?? 0);

        $paseadorId = (int)($_POST['paseador_id'] ?? 0);
        $inicio     = trim((string)($_POST['inicio'] ?? ''));
        $duracion   = (int)($_POST['duracion'] ?? 0);
        $ubicacion  = trim((string)($_POST['ubicacion'] ?? ''));

        $pickupLat = (isset($_POST['pickup_lat']) && $_POST['pickup_lat'] !== '') ? (float)$_POST['pickup_lat'] : null;
        $pickupLng = (isset($_POST['pickup_lng']) && $_POST['pickup_lng'] !== '') ? (float)$_POST['pickup_lng'] : null;

        if ($duenoId <= 0 || $mascota1 <= 0 || $paseadorId <= 0 || $inicio === '' || $duracion <= 0 || $ubicacion === '') {
            $_SESSION['error'] = 'Datos incompletos para crear el paseo.';
            return;
        }

        if ($mascota2 > 0 && $mascota2 === $mascota1) {
            $_SESSION['error'] = 'Las mascotas seleccionadas no pueden ser las mismas.';
            return;
        }

        $cantidadMascotas = 1 + ($mascota2 > 0 ? 1 : 0);

        try {
            // ✅ Validar que mascota 1 pertenezca al dueño
            $chk1 = $this->db->prepare("
            SELECT COUNT(*)
            FROM mascotas
            WHERE mascota_id = :mascota_id
              AND dueno_id   = :dueno_id
        ");
            $chk1->execute([':mascota_id' => $mascota1, ':dueno_id' => $duenoId]);
            if ((int)$chk1->fetchColumn() === 0) {
                $_SESSION['error'] = 'La Mascota 1 seleccionada no te pertenece.';
                return;
            }

            // ✅ Validar mascota 2 (si existe)
            if ($mascota2 > 0) {
                $chk2 = $this->db->prepare("
                SELECT COUNT(*)
                FROM mascotas
                WHERE mascota_id = :mascota_id
                  AND dueno_id   = :dueno_id
            ");
                $chk2->execute([':mascota_id' => $mascota2, ':dueno_id' => $duenoId]);
                if ((int)$chk2->fetchColumn() === 0) {
                    $_SESSION['error'] = 'La Mascota 2 seleccionada no te pertenece.';
                    return;
                }
            }

            // ✅ Precio por hora del paseador
            $stmtPrecio = $this->db->prepare("
            SELECT precio_hora
            FROM paseadores
            WHERE paseador_id = :paseador_id
            LIMIT 1
        ");
            $stmtPrecio->execute([':paseador_id' => $paseadorId]);
            $precioHora = (float)($stmtPrecio->fetchColumn() ?: 0);

            $horas = $duracion / 60;
            if ($precioHora <= 0) {
                $precioTotal = 0;
            } else {
                $precioTotal = ($cantidadMascotas === 2)
                    ? round(($precioHora * 2) * $horas * 0.70, 0) // -30%
                    : round($precioHora * $horas, 0);
            }

            // ✅ Estados iniciales
            $estadoInicial     = 'solicitado';
            $estadoPagoInicial = 'pendiente';
            $puntosInicial     = 0;

            // ✅ INSERT incluyendo estado para que NO quede NULL
            $sql = "
            INSERT INTO paseos (
                mascota_id,
                mascota_id_2,
                cantidad_mascotas,
                paseador_id,
                inicio,
                duracion,
                ubicacion,
                precio_total,
                pickup_lat,
                pickup_lng,
                pickup_direccion,
                estado,
                estado_pago,
                puntos_ganados
            ) VALUES (
                :mascota_id,
                :mascota_id_2,
                :cantidad_mascotas,
                :paseador_id,
                :inicio,
                :duracion,
                :ubicacion,
                :precio_total,
                :pickup_lat,
                :pickup_lng,
                :pickup_direccion,
                :estado,
                :estado_pago,
                :puntos_ganados
            )
        ";

            $st = $this->db->prepare($sql);
            $ok = $st->execute([
                ':mascota_id'        => $mascota1,
                ':mascota_id_2'      => ($mascota2 > 0 ? $mascota2 : null),
                ':cantidad_mascotas' => $cantidadMascotas,
                ':paseador_id'       => $paseadorId,
                ':inicio'            => $inicio,
                ':duracion'          => $duracion,
                ':ubicacion'         => $ubicacion,
                ':precio_total'      => $precioTotal,
                ':pickup_lat'        => $pickupLat,
                ':pickup_lng'        => $pickupLng,
                ':pickup_direccion'  => $ubicacion,
                ':estado'            => $estadoInicial,
                ':estado_pago'       => $estadoPagoInicial,
                ':puntos_ganados'    => $puntosInicial,
            ]);

            if ($ok) {
                $_SESSION['success'] = ($cantidadMascotas === 2)
                    ? 'Paseo solicitado correctamente (2 mascotas con 30% de descuento).'
                    : 'Paseo solicitado correctamente.';
            } else {
                $_SESSION['error'] = 'Ocurrió un error al solicitar el paseo.';
            }
        } catch (PDOException $e) {
            error_log('PaseoController::store error: ' . $e->getMessage());
            $_SESSION['error'] = 'Ocurrió un error al solicitar el paseo.';
        }
    }


    /** 🔹 Obtener ruta del paseo */
    public function getRuta(int $paseoId): array
    {
        if ($paseoId <= 0) return [];
        return $this->paseoModel->getRuta($paseoId);
    }
}
