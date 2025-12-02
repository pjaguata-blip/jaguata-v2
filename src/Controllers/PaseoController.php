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
                m.nombre      AS mascota_nombre,
                m.foto_url    AS mascota_foto,
                d.nombre      AS dueno_nombre,
                d.telefono    AS dueno_telefono,
                d.ciudad      AS dueno_ciudad,
                d.barrio      AS dueno_barrio
            FROM paseos p
            INNER JOIN mascotas m ON m.mascota_id = p.mascota_id
            INNER JOIN usuarios d ON d.usu_id     = m.dueno_id
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
                m.nombre       AS mascota_nombre,
                m.foto_url     AS mascota_foto,
                d.nombre       AS dueno_nombre,
                d.telefono     AS dueno_telefono,
                d.ciudad       AS dueno_ciudad,
                d.barrio       AS dueno_barrio,
                pa.nombre      AS paseador_nombre,
                pa.telefono    AS paseador_telefono
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
}
