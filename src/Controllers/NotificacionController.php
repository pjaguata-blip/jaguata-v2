<?php

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Services/DatabaseService.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Models/Notificacion.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;
use Jaguata\Helpers\Session;
use Jaguata\Models\Notificacion;
use PDO;
use PDOException;

AppConfig::init();

/**
 * Controlador de notificaciones
 * - Admin:
 *    - indexAdmin: listado para el panel admin
 *    - crearDesdeAdmin: alta de notificación masiva
 * - Paseador / Dueño:
 *    - index: listado filtrado por usuario + rol + globales
 *    - markRead / markAllRead: marcar como leídas
 */
class NotificacionController
{
    private PDO $db;
    private Notificacion $notificacionModel;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
        $this->notificacionModel = new Notificacion();
    }

    /**
     * 🔹 Listar notificaciones para el panel Admin
     * $destino: 'todos', 'admin', 'paseador', 'dueno'
     */
    public function indexAdmin(string $destino = 'todos'): array
    {
        try {
            $sql = "
                SELECT 
                    n.noti_id AS id,
                    COALESCE(u.nombre, CONCAT('Usuario ID ', n.usu_id)) AS usuario,
                    n.rol_destinatario,
                    n.titulo,
                    n.mensaje,
                    n.estado,
                    n.leido,
                    n.created_at AS fecha
                FROM notificaciones n
                LEFT JOIN usuarios u ON u.usu_id = n.usu_id
                WHERE 1 = 1
            ";

            $params = [];

            $destino = strtolower(trim($destino));
            // en la BD tenés enum('admin', 'paseador', 'dueño', 'todos')
            if (in_array($destino, ['admin', 'paseador', 'dueno', 'dueño', 'todos'], true)) {
                // normalizamos 'dueno' -> 'dueño'
                if ($destino === 'dueno') {
                    $destino = 'dueño';
                }
                if ($destino !== 'todos') {
                    $sql .= " AND n.rol_destinatario = :destino";
                    $params[':destino'] = $destino;
                }
            }

            $sql .= " ORDER BY n.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('❌ Error NotificacionController::indexAdmin() => ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔹 Crear notificación desde el panel Admin (masiva por rol o todos)
     *
     * Espera en $data:
     * - titulo
     * - mensaje
     * - destinatario  (ej: 'todos', 'dueño', 'paseador')
     * - tipo          (opcional: general|sistema|promocion...)
     * - prioridad     (opcional: baja|media|alta)
     * - canal         (opcional: app|email|push)
     */
    public function crearDesdeAdmin(array $data): array
    {
        $titulo    = trim($data['titulo'] ?? '');
        $mensaje   = trim($data['mensaje'] ?? '');
        $rawDest   = trim($data['destinatario'] ?? 'todos');
        $tipo      = trim($data['tipo'] ?? 'general');
        $prioridad = strtolower(trim($data['prioridad'] ?? 'media'));
        $canal     = strtolower(trim($data['canal'] ?? 'app'));

        if ($titulo === '' || $mensaje === '') {
            return ['success' => false, 'error' => 'Título y mensaje son obligatorios.'];
        }

        // 🔁 Normalizar destinatario a los valores reales del ENUM:
        // enum('admin', 'paseador', 'dueño', 'todos')
        $mapDestinos = [
            'todos'       => 'todos',
            'todo'        => 'todos',

            'duenos'      => 'dueño',
            'dueños'      => 'dueño',
            'dueno'       => 'dueño',
            'dueño'       => 'dueño',

            'paseadores'  => 'paseador',
            'paseador'    => 'paseador',

            'admin'       => 'admin',
            'administrador' => 'admin',
        ];

        $destKey      = mb_strtolower($rawDest, 'UTF-8');
        $destinatario = $mapDestinos[$destKey] ?? 'todos';

        $validPrioridades = ['baja', 'media', 'alta'];
        if (!in_array($prioridad, $validPrioridades, true)) {
            $prioridad = 'media';
        }

        $validCanales = ['app', 'email', 'push'];
        if (!in_array($canal, $validCanales, true)) {
            $canal = 'app';
        }

        // Admin que envía
        $adminId = Session::getUsuarioId() ?: null;

        // 🔹 Notificación masiva: no está ligada a un usuario concreto → usamos NULL en usu_id
        $usuIdMasivo = null;

        try {
            $sql = "
                INSERT INTO notificaciones (
                    usu_id,
                    rol_destinatario,
                    admin_id,
                    tipo,
                    prioridad,
                    canal,
                    titulo,
                    mensaje,
                    paseo_id,
                    leido,
                    estado
                ) VALUES (
                    :usu_id,
                    :rol_destinatario,
                    :admin_id,
                    :tipo,
                    :prioridad,
                    :canal,
                    :titulo,
                    :mensaje,
                    NULL,
                    0,
                    'pendiente'
                )
            ";

            $stmt = $this->db->prepare($sql);

            // usu_id NULL para masivas (evitamos FK con 0)
            if ($usuIdMasivo === null) {
                $stmt->bindValue(':usu_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':usu_id', $usuIdMasivo, PDO::PARAM_INT);
            }

            // admin_id puede ser null si por algo no hay sesión aún
            if ($adminId !== null) {
                $stmt->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':admin_id', null, PDO::PARAM_NULL);
            }

            $stmt->bindValue(':rol_destinatario', $destinatario);
            $stmt->bindValue(':tipo', $tipo);
            $stmt->bindValue(':prioridad', $prioridad);
            $stmt->bindValue(':canal', $canal);
            $stmt->bindValue(':titulo', $titulo);
            $stmt->bindValue(':mensaje', $mensaje);

            $stmt->execute();

            return ['success' => true];
        } catch (PDOException $e) {
            // Mientras probás, devolvemos el mensaje real para ver el motivo exacto
            error_log('❌ Error NotificacionController::crearDesdeAdmin() => ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'Error al guardar la notificación: ' . $e->getMessage(),
            ];
        }
    }

    /* ==========================================================
       MÉTODOS PARA PASEADOR / DUEÑO (vistas de cada usuario)
       ========================================================== */

    /**
     * 🔹 Listado paginado para el usuario logueado (dueño / paseador)
     * Usa Notificacion::listByUser() que filtra por:
     *  - usu_id
     *  - rol_destinatario = rol
     *  - rol_destinatario = 'todos'
     */
    public function index(array $filters = []): array
    {
        $usuId = Session::getUsuarioId();
        $rol   = Session::getUsuarioRol() ?? '';

        if (!$usuId || $rol === '') {
            return [
                'data'       => [],
                'total'      => 0,
                'page'       => 1,
                'perPage'    => (int)($filters['perPage'] ?? 10),
                'totalPages' => 1,
            ];
        }

        $q       = trim($filters['q'] ?? '');
        $leido   = $filters['leido'] ?? '';
        $page    = max(1, (int)($filters['page'] ?? 1));
        $perPage = max(1, (int)($filters['perPage'] ?? 10));

        $leidoFilter = ($leido === '' ? null : (int)$leido);

        return $this->notificacionModel->listByUser(
            (int)$usuId,
            (string)$rol,
            $leidoFilter,
            $q !== '' ? $q : null,
            $page,
            $perPage,
            null // tipo (si quisieras filtrar a futuro)
        );
    }

    /**
     * 🔹 Marcar una notificación como leída (para el usuario actual)
     * Recibe normalmente $_POST con 'noti_id'
     */
    public function markRead(array $data): bool
    {
        $notiId = (int)($data['noti_id'] ?? 0);
        $usuId  = Session::getUsuarioId() ?? 0;

        if ($notiId <= 0 || $usuId <= 0) {
            return false;
        }

        return $this->notificacionModel->markRead($notiId, (int)$usuId);
    }

    /**
     * 🔹 Marcar todas como leídas para el usuario actual (rol + globales)
     */
    public function markAllRead(): bool
    {
        $usuId = Session::getUsuarioId() ?? 0;
        $rol   = Session::getUsuarioRol() ?? '';

        if ($usuId <= 0 || $rol === '') {
            return false;
        }

        $count = $this->notificacionModel->markAllRead((int)$usuId, (string)$rol);
        return $count > 0;
    }
    public function enviarNotificacionUsuario(int $usuarioId, string $titulo, string $mensaje): bool
    {
        // 1) Acá va tu lógica de guardar/enviar notificación
        // $ok = $this->notificacionModel->crear([...]);

        $ok = true; // simulo que salió bien

        if ($ok) {
            $adminId = Session::getUsuarioId(); // el admin que envía

            // 🔹 AUDITORÍA
            Auditoria::log(
                'ENVIAR NOTIFICACIÓN',
                'Notificaciones',
                'El admin ID ' . $adminId .
                    ' envió una notificación al usuario ID ' . $usuarioId .
                    ' con título: "' . $titulo . '"',
                $usuarioId,  // usuario afectado
                $adminId     // admin que hace la acción
            );
        }

        return $ok;
    }
    public function enviarNotificacionMasiva(array $idsUsuarios, string $titulo, string $mensaje): bool
    {
        // Lógica de enviar (bucle, insert masivo, etc.)
        $cantidad = count($idsUsuarios);
        $ok = true;

        if ($ok) {
            $adminId = Session::getUsuarioId();

            Auditoria::log(
                'ENVIAR NOTIFICACIÓN MASIVA',
                'Notificaciones',
                'El admin ID ' . $adminId .
                    ' envió una notificación masiva a ' . $cantidad .
                    ' usuarios. Título: "' . $titulo . '"',
                null,      // usuarioId: null porque es masivo
                $adminId
            );
        }

        return $ok;
    }
}
