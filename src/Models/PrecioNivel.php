<?php
namespace Jaguata\Models;

use Jaguata\Services\DatabaseService;

class PrecioNivel extends BaseModel {
    protected string $table = 'precios_nivel';
    protected string $primaryKey = 'precio_id';

    public function getPreciosByZona(string $zona): array {
        return $this->findAll(['zona' => $zona], 'tipo_servicio ASC');
    }

    public function getPrecioByZonaYTipo(string $zona, string $tipoServicio = 'standard'): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE zona = :zona AND tipo_servicio = :tipoServicio";
        return $this->db->fetchOne($sql, [
            'zona' => $zona,
            'tipoServicio' => $tipoServicio
        ]);
    }

    public function getAllZonas(): array {
        $sql = "SELECT DISTINCT zona FROM {$this->table} ORDER BY zona ASC";
        $resultados = $this->db->fetchAll($sql);
        return array_column($resultados, 'zona');
    }

    public function getTiposServicio(): array {
        $sql = "SELECT DISTINCT tipo_servicio FROM {$this->table} ORDER BY tipo_servicio ASC";
        $resultados = $this->db->fetchAll($sql);
        return array_column($resultados, 'tipo_servicio');
    }

    public function calcularPrecio(string $zona, string $tipoServicio, int $duracion, bool $esFinDeSemana = false, bool $esFeriado = false): float {
        $precio = $this->getPrecioByZonaYTipo($zona, $tipoServicio);
        
        if (!$precio) {
            throw new \Exception("No se encontró precio para la zona: {$zona} y tipo: {$tipoServicio}");
        }
        
        $precioTotal = $precio['precio_base'] + ($precio['precio_hora'] * ($duracion / 60));
        
        // Aplicar descuentos
        if ($esFeriado && $precio['descuento_feriados'] > 0) {
            $precioTotal *= (1 - $precio['descuento_feriados'] / 100);
        } elseif ($esFinDeSemana && $precio['descuento_findesemana'] > 0) {
            $precioTotal *= (1 - $precio['descuento_findesemana'] / 100);
        }
        
        return round($precioTotal, 2);
    }

    public function crearPrecio(string $zona, string $tipoServicio, float $precioBase, float $precioHora, float $descuentoFinde = 0, float $descuentoFeriado = 0): int {
        $data = [
            'zona' => $zona,
            'tipo_servicio' => $tipoServicio,
            'precio_base' => $precioBase,
            'precio_hora' => $precioHora,
            'descuento_findesemana' => $descuentoFinde,
            'descuento_feriados' => $descuentoFeriado
        ];
        
        return $this->create($data);
    }

    public function actualizarPrecio(int $precioId, float $precioBase, float $precioHora, ?float $descuentoFinde = null, ?float $descuentoFeriado = null): bool {
        $data = [
            'precio_base' => $precioBase,
            'precio_hora' => $precioHora
        ];
        
        if ($descuentoFinde !== null) {
            $data['descuento_findesemana'] = $descuentoFinde;
        }
        
        if ($descuentoFeriado !== null) {
            $data['descuento_feriados'] = $descuentoFeriado;
        }
        
        return $this->update($precioId, $data);
    }

    public function getPreciosConDescuentos(): array {
        $sql = "SELECT *, 
                       (precio_base + precio_hora) as precio_estandar,
                       (precio_base + precio_hora) * (1 - descuento_findesemana / 100) as precio_finde,
                       (precio_base + precio_hora) * (1 - descuento_feriados / 100) as precio_feriado
                FROM {$this->table} 
                ORDER BY zona ASC, tipo_servicio ASC";
        
        return $this->db->fetchAll($sql);
    }

    public function getPreciosByTipoServicio(string $tipoServicio): array {
        return $this->findAll(['tipo_servicio' => $tipoServicio], 'zona ASC');
    }

    public function eliminarPrecio(int $precioId): bool {
        return $this->delete($precioId);
    }

    public function getPreciosDisponibles(): array {
        $sql = "SELECT p.*, 
                       COUNT(ps.paseo_id) as total_paseos,
                       AVG(ps.precio_total) as precio_promedio
                FROM {$this->table} p
                LEFT JOIN paseos ps ON p.zona = (
                    SELECT zona FROM paseadores WHERE paseador_id = ps.paseador_id
                )
                GROUP BY p.precio_id
                ORDER BY p.zona ASC, p.tipo_servicio ASC";
        
        return $this->db->fetchAll($sql);
    }
}
