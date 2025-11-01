<?php

namespace Jaguata\Controllers;

use Jaguata\Models\Mascota;
use Jaguata\Helpers\Session;
use Exception;
use PDO;

class MascotaController
{
    private Mascota $mascotaModel;

    public function __construct()
    {
        $this->mascotaModel = new Mascota();
    }

    /* 🔹 Helper: normaliza edad a meses (moverla FUERA de destroy) */
    private function normalizarEdadAMeses(?int $valor, string $unidad): ?int
    {
        if ($valor === null) return null;
        return ($unidad === 'anios') ? $valor * 12 : $valor; // 'meses' por defecto
    }

    public function index(): array
    {
        $db = \Jaguata\Services\DatabaseService::connection();
        $duenoId = \Jaguata\Helpers\Session::getUsuarioId();

        $sql = "SELECT mascota_id, nombre, raza, edad_meses, tamano, peso_kg, foto_url 
            FROM mascotas 
            WHERE dueno_id = :dueno_id 
            ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([':dueno_id' => $duenoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function show(int $id): ?array
    {
        if (!Session::isLoggedIn()) {
            return ['error' => 'No autorizado'];
        }

        $mascota = $this->mascotaModel->find($id);
        if (!$mascota || $mascota['dueno_id'] !== Session::getUsuarioId()) {
            return ['error' => 'Mascota no encontrada o no autorizada'];
        }
        return $mascota;
    }

    /* 🔹 GUARDAR (usar nuevos campos del form) */
    public function store(): void
    {
        if (!Session::isLoggedIn()) {
            $_SESSION['error'] = "Debe iniciar sesión para agregar mascotas";
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }

        $nombre        = trim($_POST['nombre'] ?? '');
        $raza          = trim($_POST['raza'] ?? '');
        $raza_otra     = trim($_POST['raza_otra'] ?? '');
        $peso_kg       = isset($_POST['peso_kg']) ? (float)$_POST['peso_kg'] : null; // no se guarda (sin migrar)
        $tamano        = $_POST['tamano'] ?? ''; // pequeno|mediano|grande|gigante
        $edad_valor    = isset($_POST['edad_valor']) ? (int)$_POST['edad_valor'] : null;
        $edad_unidad   = $_POST['edad_unidad'] ?? 'meses';
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($raza === 'Otra' && $raza_otra !== '') {
            $raza = $raza_otra;
        }

        // Edad normalizada a MESES (se guardará en columna 'edad')
        $edad_meses = $this->normalizarEdadAMeses($edad_valor, $edad_unidad);

        // Consistencia del tamaño según el peso en servidor (aunque no lo guardemos)
        if ($peso_kg !== null) {
            if ($peso_kg <= 10) {
                $tamano = 'pequeno';
            } elseif ($peso_kg <= 25) {
                $tamano = 'mediano';
            } elseif ($peso_kg <= 45) {
                $tamano = 'grande';
            } else {
                $tamano = 'gigante';
            }
        }

        $errors = [];
        if ($nombre === '') $errors[] = "El nombre es obligatorio";
        if (!in_array($tamano, ['pequeno', 'mediano', 'grande', 'gigante'], true)) {
            $errors[] = "El tamaño seleccionado no es válido";
        }
        if ($edad_meses !== null && $edad_meses < 0) {
            $errors[] = "La edad no puede ser negativa";
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header("Location: " . BASE_URL . "/features/dueno/AgregarMascota.php");
            exit;
        }

        try {
            // 👇 Guarda edad en meses en la columna 'edad'
            $this->mascotaModel->create([
                'dueno_id'      => Session::getUsuarioId(),
                'nombre'        => $nombre,
                'raza'          => $raza,
                'peso_kg'       => $peso_kg,          // <- nuevo
                'tamano'        => $tamano,
                'edad_meses'    => $edad_meses,       // <- renombrado
                'observaciones' => $observaciones,
            ]);

            $_SESSION['success'] = "Mascota agregada correctamente";
            header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
            exit;
        } catch (Exception $e) {
            error_log("Error al guardar mascota: " . $e->getMessage());
            $_SESSION['error'] = "Error interno al guardar la mascota";
            header("Location: " . BASE_URL . "/features/dueno/AgregarMascota.php");
            exit;
        }
    }

    /* 🔹 ACTUALIZAR con la misma lógica de edad en meses */
    public function update(int $id): void
    {
        if (!Session::isLoggedIn()) {
            $_SESSION['error'] = "Debe iniciar sesión para editar mascotas";
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }

        $nombre        = trim($_POST['nombre'] ?? '');
        $raza          = trim($_POST['raza'] ?? '');
        $raza_otra     = trim($_POST['raza_otra'] ?? '');
        $peso_kg       = isset($_POST['peso_kg']) ? (float)$_POST['peso_kg'] : null; // no se guarda (sin migrar)
        $tamano        = $_POST['tamano'] ?? '';
        $edad_valor    = isset($_POST['edad_valor']) ? (int)$_POST['edad_valor'] : null;
        $edad_unidad   = $_POST['edad_unidad'] ?? 'meses';
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($raza === 'Otra' && $raza_otra !== '') {
            $raza = $raza_otra;
        }

        $edad_meses = $this->normalizarEdadAMeses($edad_valor, $edad_unidad);

        if ($peso_kg !== null) {
            if ($peso_kg <= 10) {
                $tamano = 'pequeno';
            } elseif ($peso_kg <= 25) {
                $tamano = 'mediano';
            } elseif ($peso_kg <= 45) {
                $tamano = 'grande';
            } else {
                $tamano = 'gigante';
            }
        }

        $errors = [];
        if ($nombre === '') $errors[] = "El nombre es obligatorio";
        if (!in_array($tamano, ['pequeno', 'mediano', 'grande', 'gigante'], true)) {
            $errors[] = "El tamaño seleccionado no es válido";
        }
        if ($edad_meses !== null && $edad_meses < 0) {
            $errors[] = "La edad no puede ser negativa";
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header("Location: " . BASE_URL . "/features/dueno/EditarMascota.php?id=" . $id);
            exit;
        }

        try {
            $ok = $this->mascotaModel->update($id, [
                'nombre'        => $nombre,
                'raza'          => $raza,
                'peso_kg'       => $peso_kg,          // <- nuevo
                'tamano'        => $tamano,
                'edad_meses'    => $edad_meses,       // <- renombrado
                'observaciones' => $observaciones,
            ]);

            if ($ok) {
                $_SESSION['success'] = "Mascota actualizada correctamente";
                header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
            } else {
                $_SESSION['error'] = "No se pudo actualizar la mascota";
                header("Location: " . BASE_URL . "/features/dueno/EditarMascota.php?id=" . $id);
            }
            exit;
        } catch (Exception $e) {
            error_log("Error al actualizar mascota: " . $e->getMessage());
            $_SESSION['error'] = "Error interno al actualizar la mascota";
            header("Location: " . BASE_URL . "/features/dueno/EditarMascota.php?id=" . $id);
            exit;
        }
    }

    public function destroy(int $id): void
    {
        if (!Session::isLoggedIn()) {
            $_SESSION['error'] = "Debe iniciar sesión para eliminar mascotas";
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }

        try {
            $mascota = $this->mascotaModel->find($id);

            if (!$mascota || $mascota['dueno_id'] !== Session::getUsuarioId()) {
                $_SESSION['error'] = "Mascota no encontrada o no autorizada";
                header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
                exit;
            }

            $ok = $this->mascotaModel->delete($id);

            if ($ok) {
                $_SESSION['success'] = "Mascota eliminada correctamente";
            } else {
                $_SESSION['error'] = "No se pudo eliminar la mascota";
            }

            header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
            exit;
        } catch (Exception $e) {
            error_log("Error al eliminar mascota: " . $e->getMessage());
            $_SESSION['error'] = "Error interno al eliminar la mascota";
            header("Location: " . BASE_URL . "/features/dueno/MisMascotas.php");
            exit;
        }
    }
}
