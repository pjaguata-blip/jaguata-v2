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

/**
 * Controlador de Mascotas (lado dueño)
 */
class MascotaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * Listar mascotas del dueño logueado
     */
    public function index(): array
    {
        $rol = Session::getUsuarioRol() ?? '';

        // 👉 Si es ADMIN, listamos TODAS las mascotas
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
                    created_at,
                    updated_at
                FROM mascotas
                ORDER BY created_at DESC
            ";

            $st = $this->db->prepare($sql);
            $st->execute();

            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // 👉 Si es dueño (u otro rol), solo las del dueño actual
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

    /**
     * Versión explícita usada en algunos lugares
     */
    public function indexByDuenoActual(): array
    {
        return $this->index();
    }

    /**
     * Obtener una mascota específica (ver PerfilMascota)
     * Valida que pertenezca al dueño logueado.
     */
    public function show(int $id): array
    {
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

    /**
     * Crear mascota (usado por AgregarMascota.php)
     */
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

        // Si eligió "Otra", usamos lo que escribió
        if ($raza === 'Otra' && $razaOtra !== '') {
            $raza = $razaOtra;
        }

        // Validaciones básicas
        if ($nombre === '' || $pesoKg <= 0) {
            Session::setError('Completá al menos el nombre y el peso de la mascota.');
            return; // volvemos al formulario sin redirigir, los valores quedan en $_POST
        }

        // Edad en meses
        $edadMeses = null;
        if ($edadValor !== null && $edadValor > 0) {
            $edadMeses = ($edadUnidad === 'anios') ? $edadValor * 12 : $edadValor;
        }

        // Tamaño: si no marcó nada, calculamos por peso
        if (!$tamano) {
            if ($pesoKg <= 7)        $tamano = 'pequeno';
            elseif ($pesoKg <= 18)   $tamano = 'mediano';
            elseif ($pesoKg <= 35)   $tamano = 'grande';
            else                     $tamano = 'grande'; // la BD solo tiene pequeño/mediano/grande
        } elseif ($tamano === 'gigante') {
            // Por seguridad, lo mapeamos a 'grande' porque el ENUM de la BD no tiene 'gigante'
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
            // No redirigimos para que el usuario vea el error arriba del formulario
        }
    }
}
