<?php

declare(strict_types=1);

namespace Jaguata\Controllers;

require_once __DIR__ . '/../Config/AppConfig.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Helpers\Session;
use Jaguata\Services\DatabaseService;
use PDO;
use PDOException;

AppConfig::init();

class MascotaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function index(): array
    {
        $rol = Session::getUsuarioRol() ?? '';
        if ($rol === 'admin') {
            $sql = "
                SELECT 
                    mascota_id,
                    dueno_id,
                    nombre,
                    raza,
                    peso_kg,
                    tamano,
                    edad_meses,
                    observaciones,
                    foto_url,
                    estado,
                    created_at,
                    updated_at
                FROM mascotas
                ORDER BY created_at DESC
            ";

            $st = $this->db->prepare($sql);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $duenoId = (int)(Session::getUsuarioId() ?? 0);
        if ($duenoId <= 0) {
            return [];
        }

        $sql = "
            SELECT 
                mascota_id,
                dueno_id,
                nombre,
                raza,
                peso_kg,
                tamano,
                edad_meses,
                observaciones,
                foto_url,
                created_at,
                updated_at
            FROM mascotas
            WHERE dueno_id = :dueno_id
            ORDER BY created_at DESC
        ";

        $st = $this->db->prepare($sql);
        $st->bindValue(':dueno_id', $duenoId, PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public function indexByDuenoActual(): array
    {
        return $this->index();
    }

    public function show(int $id): array
    {
        $rol = Session::getUsuarioRol() ?? '';
        $id  = (int)$id;

        if ($id <= 0) {
            return ['error' => 'ID inválido'];
        }

        if ($rol === 'admin') {
            $sql = "
                SELECT 
                    mascota_id,
                    dueno_id,
                    nombre,
                    raza,
                    peso_kg,
                    tamano,
                    edad_meses,
                    observaciones,
                    foto_url,
                    estado,
                    created_at,
                    updated_at
                FROM mascotas
                WHERE mascota_id = :id
                LIMIT 1
            ";
            $st = $this->db->prepare($sql);
            $st->bindValue(':id', $id, PDO::PARAM_INT);
            $st->execute();

            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: ['error' => 'Mascota no encontrada'];
        }

        $duenoId = (int)(Session::getUsuarioId() ?? 0);
        if ($duenoId <= 0) {
            return ['error' => 'Sesión no válida'];
        }

        $sql = "
            SELECT 
                mascota_id,
                dueno_id,
                nombre,
                raza,
                peso_kg,
                tamano,
                edad_meses,
                observaciones,
                foto_url,
                estado,
                created_at,
                updated_at
            FROM mascotas
            WHERE mascota_id = :id
              AND dueno_id  = :dueno_id
            LIMIT 1
        ";

        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->bindValue(':dueno_id', $duenoId, PDO::PARAM_INT);
        $st->execute();

        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['error' => 'Mascota no encontrada'];
        }

        return $row;
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $duenoId = (int)(Session::getUsuarioId() ?? 0);
        if ($duenoId <= 0) {
            Session::setError('Sesión no válida, iniciá sesión nuevamente.');
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }

        // ==== Datos del POST ====
        $nombre     = trim($_POST['nombre'] ?? '');
        $raza       = trim($_POST['raza'] ?? '');
        $razaOtra   = trim($_POST['raza_otra'] ?? '');
        $pesoKg     = (float)($_POST['peso_kg'] ?? 0);
        $tamano     = $_POST['tamano'] ?? null;
        $edadValor  = isset($_POST['edad_valor']) ? (int)$_POST['edad_valor'] : null;
        $edadUnidad = $_POST['edad_unidad'] ?? 'meses';
        $obs        = trim($_POST['observaciones'] ?? '');

        if ($raza === 'Otra' && $razaOtra !== '') {
            $raza = $razaOtra;
        }

        if ($nombre === '' || $pesoKg <= 0) {
            Session::setError('Completá al menos el nombre y el peso de la mascota.');
            return;
        }

        $edadMeses = null;
        if ($edadValor !== null && $edadValor > 0) {
            $edadMeses = ($edadUnidad === 'anios') ? $edadValor * 12 : $edadValor;
        }

        if (!$tamano) {
            if ($pesoKg <= 7)        $tamano = 'pequeno';
            elseif ($pesoKg <= 18)   $tamano = 'mediano';
            else                     $tamano = 'grande';
        } elseif ($tamano === 'gigante') {
            $tamano = 'grande';
        }

        try {
            $sql = "
                INSERT INTO mascotas (
                    dueno_id,
                    nombre,
                    raza,
                    peso_kg,
                    tamano,
                    edad_meses,
                    observaciones,
                    created_at,
                    updated_at
                ) VALUES (
                    :dueno_id,
                    :nombre,
                    :raza,
                    :peso_kg,
                    :tamano,
                    :edad_meses,
                    :observaciones,
                    NOW(),
                    NOW()
                )
            ";

            $st = $this->db->prepare($sql);
            $st->bindValue(':dueno_id', $duenoId, PDO::PARAM_INT);
            $st->bindValue(':nombre', $nombre);
            $st->bindValue(':raza', $raza !== '' ? $raza : null);
            $st->bindValue(':peso_kg', $pesoKg);
            $st->bindValue(':tamano', $tamano);
            $st->bindValue(':edad_meses', $edadMeses, $edadMeses === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $st->bindValue(':observaciones', $obs !== '' ? $obs : null);

            $st->execute();

            Session::setSuccess('Mascota registrada correctamente 🐶');
            header('Location: ' . BASE_URL . '/features/dueno/MisMascotas.php');
            exit;
        } catch (PDOException $e) {
            Session::setError('Error al guardar la mascota: ' . $e->getMessage());
        }
    }

    public function update(int $id): array
    {
        $duenoId = (int)(Session::getUsuarioId() ?? 0);
        if ($duenoId <= 0) {
            return ['success' => false, 'error' => 'Sesión no válida.'];
        }

        // 1) Verificar que la mascota exista y pertenezca al dueño
        $st = $this->db->prepare("
            SELECT mascota_id, dueno_id, foto_url
            FROM mascotas
            WHERE mascota_id = :id
              AND dueno_id   = :dueno_id
            LIMIT 1
        ");
        $st->execute([
            ':id'       => $id,
            ':dueno_id' => $duenoId
        ]);
        $actual = $st->fetch(PDO::FETCH_ASSOC);

        if (!$actual) {
            return ['success' => false, 'error' => 'Mascota no encontrada o no te pertenece.'];
        }

        // 2) Leer POST (soporta tus nombres actuales)
        $nombre   = trim((string)($_POST['nombre'] ?? ''));
        $raza     = trim((string)($_POST['raza'] ?? ''));
        $razaOtra = trim((string)($_POST['raza_otra'] ?? ''));
        if ($raza === 'Otra' && $razaOtra !== '') {
            $raza = $razaOtra;
        }

        $pesoKg   = isset($_POST['peso_kg']) && $_POST['peso_kg'] !== '' ? (float)$_POST['peso_kg'] : null;
        $tamano   = isset($_POST['tamano']) ? trim((string)$_POST['tamano']) : null;

        // Tu EditarMascota manda edad_meses (no edad_valor/unidad)
        $edadMeses = null;
        if (isset($_POST['edad_meses']) && $_POST['edad_meses'] !== '') {
            $edadMeses = (int)$_POST['edad_meses'];
            if ($edadMeses < 0) $edadMeses = 0;
        } elseif (isset($_POST['edad_valor']) && $_POST['edad_valor'] !== '') {
            $edadValor  = (int)$_POST['edad_valor'];
            $edadUnidad = (string)($_POST['edad_unidad'] ?? 'meses');
            if ($edadValor > 0) {
                $edadMeses = ($edadUnidad === 'anios') ? $edadValor * 12 : $edadValor;
            }
        }

        $obs = trim((string)($_POST['observaciones'] ?? ''));

        if ($nombre === '') {
            return ['success' => false, 'error' => 'El nombre es obligatorio.'];
        }

        // 3) Foto (opcional)
        $fotoUrl = $actual['foto_url'] ?? null;

        if (!empty($_FILES['foto']) && isset($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $tmp  = $_FILES['foto']['tmp_name'];
            $name = $_FILES['foto']['name'] ?? 'foto.jpg';

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $permitidas, true)) {
                return ['success' => false, 'error' => 'Formato de imagen no permitido (jpg, png, webp).'];
            }

            // Carpeta destino (ajustá si tu proyecto usa otra)
            $dir = dirname(__DIR__, 2) . '/uploads/mascotas';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $fileName = 'mascota_' . $id . '_' . time() . '.' . $ext;
            $destAbs  = $dir . '/' . $fileName;

            if (!move_uploaded_file($tmp, $destAbs)) {
                return ['success' => false, 'error' => 'No se pudo subir la foto.'];
            }

            // URL/Path guardado en BD (relativo)
            $fotoUrl = BASE_URL . '/uploads/mascotas/' . $fileName;
        }

        // 4) Update
        try {
            $sql = "
                UPDATE mascotas
                SET nombre        = :nombre,
                    raza          = :raza,
                    peso_kg       = :peso_kg,
                    tamano        = :tamano,
                    edad_meses    = :edad_meses,
                    observaciones = :observaciones,
                    foto_url      = :foto_url,
                    updated_at    = NOW()
                WHERE mascota_id  = :id
                  AND dueno_id    = :dueno_id
            ";

            $stUp = $this->db->prepare($sql);
            $stUp->execute([
                ':nombre'        => $nombre,
                ':raza'          => ($raza !== '' ? $raza : null),
                ':peso_kg'       => $pesoKg,
                ':tamano'        => ($tamano !== '' ? $tamano : null),
                ':edad_meses'    => $edadMeses,
                ':observaciones' => ($obs !== '' ? $obs : null),
                ':foto_url'      => $fotoUrl,
                ':id'            => $id,
                ':dueno_id'      => $duenoId,
            ]);

            return ['success' => true];
        } catch (PDOException $e) {
            error_log('MascotaController::update error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error al actualizar la mascota.'];
        }
    }

    public function destroy(int $id): void
    {
        $duenoId = (int)(Session::getUsuarioId() ?? 0);
        if ($duenoId <= 0) {
            $_SESSION['error'] = 'Sesión no válida.';
            header('Location: MisMascotas.php');
            exit;
        }

        // Verificar propiedad
        $st = $this->db->prepare("
            SELECT mascota_id
            FROM mascotas
            WHERE mascota_id = :id
              AND dueno_id   = :dueno_id
            LIMIT 1
        ");
        $st->execute([':id' => $id, ':dueno_id' => $duenoId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $_SESSION['error'] = 'Mascota no encontrada o no te pertenece.';
            header('Location: MisMascotas.php');
            exit;
        }

        // Verificar paseos asociados (evita error por FK)
        $st2 = $this->db->prepare("
            SELECT COUNT(*) 
            FROM paseos
            WHERE mascota_id = :id
        ");
        $st2->execute([':id' => $id]);
        $cantPaseos = (int)$st2->fetchColumn();

        if ($cantPaseos > 0) {
            $_SESSION['error'] = 'No podés eliminar esta mascota porque tiene paseos asociados.';
            header('Location: MisMascotas.php');
            exit;
        }

        // Eliminar
        try {
            $del = $this->db->prepare("
                DELETE FROM mascotas
                WHERE mascota_id = :id
                  AND dueno_id   = :dueno_id
            ");
            $del->execute([':id' => $id, ':dueno_id' => $duenoId]);

            $_SESSION['success'] = 'Mascota eliminada correctamente.';
            header('Location: MisMascotas.php');
            exit;
        } catch (PDOException $e) {
            error_log('MascotaController::destroy error: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo eliminar la mascota.';
            header('Location: MisMascotas.php');
            exit;
        }
    }
    public function setEstado(int $mascotaId, string $estado): array
    {
        $rol = Session::getUsuarioRol() ?? '';
        if ($rol !== 'admin') {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        $estado = strtolower(trim($estado));
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            return ['success' => false, 'error' => 'Estado inválido'];
        }

        try {
            $st = $this->db->prepare("
            UPDATE mascotas
            SET estado = :estado,
                updated_at = NOW()
            WHERE mascota_id = :id
        ");
            $st->execute([
                ':estado' => $estado,
                ':id' => $mascotaId
            ]);

            return ['success' => true];
        } catch (PDOException $e) {
            error_log('MascotaController::setEstado error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'No se pudo actualizar el estado'];
        }
    }
    public function obtenerDatosExportacion(): array
{
    try {
        $rol = Session::getUsuarioRol() ?? '';

        // ✅ Admin ve todas, Dueño solo las suyas
        $where = "";
        $params = [];

        if ($rol !== 'admin') {
            $duenoId = (int)(Session::getUsuarioId() ?? 0);
            if ($duenoId <= 0) return [];

            $where = "WHERE m.dueno_id = :dueno_id";
            $params[':dueno_id'] = $duenoId;
        }

        $sql = "
            SELECT
                m.mascota_id,
                m.dueno_id,
                u.nombre AS dueno_nombre,
                u.email  AS dueno_email,

                m.nombre,
                m.raza,
                m.peso_kg,
                m.tamano,
                m.edad_meses,
                m.observaciones,
                m.foto_url,
                m.estado,
                m.created_at,
                m.updated_at,

                /* ✅ métricas de paseos */
                COALESCE(px.total_paseos, 0)      AS total_paseos,
                COALESCE(px.total_gastado, 0)     AS total_gastado,
                COALESCE(px.ultimo_paseo, '')     AS ultimo_paseo,
                COALESCE(px.puntos_ganados, 0)    AS puntos_ganados

            FROM mascotas m
            LEFT JOIN usuarios u ON u.usu_id = m.dueno_id

            LEFT JOIN (
                SELECT
                    mascota_id,
                    COUNT(*) AS total_paseos,
                    COALESCE(SUM(precio_total), 0) AS total_gastado,
                    MAX(inicio) AS ultimo_paseo,
                    COALESCE(SUM(COALESCE(puntos_ganados, 0)), 0) AS puntos_ganados
                FROM paseos
                GROUP BY mascota_id
            ) px ON px.mascota_id = m.mascota_id

            $where
            ORDER BY m.mascota_id DESC
        ";

        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, PDO::PARAM_INT);
        }
        $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    } catch (PDOException $e) {
        error_log('❌ MascotaController::obtenerDatosExportacion error: ' . $e->getMessage());
        return [];
    }
}

}
