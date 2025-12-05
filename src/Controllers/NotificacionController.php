<?php

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Services/DatabaseService.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Models/Notificacion.php';
require_once __DIR__ . '/../Helpers/Auditoria.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;
use Jaguata\Helpers\Session;
use Jaguata\Models\Notificacion;
use PDO;
use PDOException;
use Jaguata\Helpers\Auditoria;

AppConfig::init();

/**
 * Controlador de notificaciones
 * - Admin:
 *    - indexAdmin: listado para el panel admin
 *    - crearDesdeAdmin: alta de notificación masiva
 * - Paseador / Dueño:
 *    - index / listForCurrentUser: listado filtrado por usuario + rol + globales
 *    - markRead / marcarLeidaForCurrentUser / markAllRead / marcarTodasForCurrentUser
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

        // 🔹 Notificación masiva: usu_id NULL
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

            // usu_id NULL para masivas
            if ($usuIdMasivo === null) {
                $stmt->bindValue(':usu_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':usu_id', $usuIdMasivo, PDO::PARAM_INT);
            }

            // admin_id puede ser null
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
     * Usa Notificacion::listByUser()
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
            null // tipo (futuro)
        );
    }

    /**
     * 🔹 Convenience para la vista (paseador/dueno)
     * Coincide con lo que usás en Notificaciones.php (paseador)
     */
    public function listForCurrentUser(
        int $page = 1,
        int $perPage = 10,
        ?int $leido = null,
        ?string $q = null
    ): array {
        $filters = [
            'page'    => $page,
            'perPage' => $perPage,
        ];

        if ($leido !== null) {
            $filters['leido'] = $leido;
        }

        if ($q !== null && $q !== '') {
            $filters['q'] = $q;
        }

        return $this->index($filters);
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
     * 🔹 Wrapper para la vista: marcar una notificación desde Paseador/Dueño
     */
    public function marcarLeidaForCurrentUser(int $notiId): bool
    {
        if ($notiId <= 0) {
            return false;
        }
        return $this->markRead(['noti_id' => $notiId]);
    }

    /**
     * 🔹 Marcar todas como leídas para el usuario actual (rol + globales)
     * Devuelve TRUE/FALSE
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

    /**
     * 🔹 Wrapper para la vista: devuelve CUÁNTAS se marcaron como leídas
     */
    public function marcarTodasForCurrentUser(): int
    {
        $usuId = Session::getUsuarioId() ?? 0;
        $rol   = Session::getUsuarioRol() ?? '';

        if ($usuId <= 0 || $rol === '') {
            return 0;
        }

        return $this->notificacionModel->markAllRead((int)$usuId, (string)$rol);
    }

    public function enviarNotificacionUsuario(int $usuarioId, string $titulo, string $mensaje): bool
    {
        // Acá iría la lógica real de creación
        $ok = true; // simulado

        if ($ok) {
            $adminId = Session::getUsuarioId(); // el admin que envía

            // 🔹 AUDITORÍA
            Auditoria::log(
                'ENVIAR NOTIFICACIÓN',
                'Notificaciones',
                'El admin ID ' . $adminId .
                    ' envió una notificación al usuario ID ' . $usuarioId . ' con título: "' . $titulo . '"',
                $usuarioId,  // usuario afectado
                $adminId     // admin que hace la acción
            );
        }

        return $ok;
    }

    public function enviarNotificacionMasiva(array $idsUsuarios, string $titulo, string $mensaje): bool
    {
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
                null,
                $adminId
            );
        }

        return $ok;
    }

    /**
     * 🔹 Notificaciones recientes para el usuario logueado (paseador, dueño, etc.)
     * Usado en: Dashboard Paseador / Dashboard Dueño
     */
    public function getRecientes(int $usuId, int $limit = 5): array
    {
        if ($usuId <= 0) {
            return [];
        }

        // Rol real desde sesión para filtrar
        $rol = Session::getUsuarioRol() ?? 'paseador';

        try {
            return $this->notificacionModel->getRecientes($usuId, $rol, $limit);
        } catch (\PDOException $e) {
            error_log('❌ Error NotificacionController::getRecientes => ' . $e->getMessage());
            return [];
        }
    }
}
