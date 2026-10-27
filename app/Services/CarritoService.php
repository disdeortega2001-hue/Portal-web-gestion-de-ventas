<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

/**
 * Carrito de cotización ("Mi cotización") guardado en la sesión, para que funcione igual
 * con clientes registrados e invitados. Solo guarda [id_producto => cantidad];
 * precios y stock se leen siempre de la BD al momento de mostrar o cotizar.
 *
 * Respaldo: la sesión vence en 2 horas y un productor puede armar la cotización, salir
 * al potrero y volver al día siguiente. Por eso la lista también se guarda en una cookie
 * cifrada de 30 días y se recupera cuando empieza una sesión nueva.
 */
class CarritoService
{
    private const CLAVE_SESION = 'carrito';
    public const COOKIE = 'r7_cotizacion';
    private const DIAS_RESPALDO = 30;
    private const MAX_LINEAS = 50;

    public function __construct(private readonly CalculadoraComercial $calculadora)
    {
    }

    /** @return array<int, int> */
    private function items(): array
    {
        // Sesión nueva (la anterior venció): se recupera la lista desde la cookie de respaldo
        if (! session()->has(self::CLAVE_SESION)) {
            session([self::CLAVE_SESION => $this->leerRespaldo()]);
        }

        return session(self::CLAVE_SESION, []);
    }

    private function guardar(array $items): void
    {
        session([self::CLAVE_SESION => $items]);

        $items
            ? Cookie::queue(self::COOKIE, json_encode($items), self::DIAS_RESPALDO * 24 * 60)
            : Cookie::queue(Cookie::forget(self::COOKIE));
    }

    /** Lee y valida la cookie: solo pares id => cantidad enteros y positivos (nunca se confía en ella). */
    private function leerRespaldo(): array
    {
        $datos = json_decode((string) request()->cookie(self::COOKIE), true);

        if (! is_array($datos)) {
            return [];
        }

        $items = [];
        foreach (array_slice($datos, 0, self::MAX_LINEAS, true) as $id => $cantidad) {
            if (ctype_digit((string) $id) && is_int($cantidad) && $cantidad > 0 && $cantidad <= 10000) {
                $items[(int) $id] = $cantidad;
            }
        }

        return $items;   // Los productos inexistentes o inactivos se descartan luego en lineas()
    }

    public function agregar(Producto $producto, int $cantidad): void
    {
        $items = $this->items();
        $nuevaCantidad = ($items[$producto->id] ?? 0) + $cantidad;
        $this->validarStock($producto, $nuevaCantidad);

        $items[$producto->id] = $nuevaCantidad;
        $this->guardar($items);
    }

    public function actualizar(Producto $producto, int $cantidad): void
    {
        $this->validarStock($producto, $cantidad);

        $items = $this->items();
        $items[$producto->id] = $cantidad;
        $this->guardar($items);
    }

    public function quitar(int $idProducto): void
    {
        $items = $this->items();
        unset($items[$idProducto]);
        $this->guardar($items);
    }

    /** Deja la lista vacía (no la borra de la sesión, para no recuperar el respaldo en esta misma petición). */
    public function vaciar(): void
    {
        $this->guardar([]);
    }

    public function cantidadItems(): int
    {
        return array_sum($this->items());
    }

    /** Unidades de un producto que ya están en la cotización. */
    public function cantidadDe(int $idProducto): int
    {
        return $this->items()[$idProducto] ?? 0;
    }

    public function estaVacio(): bool
    {
        return empty($this->items());
    }

    /**
     * Líneas del carrito con su producto (una sola consulta, sin N+1).
     * Quita del carrito los productos que ya no existen o fueron desactivados.
     *
     * @return Collection<int, array{producto: Producto, precio: string, cantidad: int}>
     */
    public function lineas(): Collection
    {
        $items = $this->items();
        $productos = Producto::activos()->with('categoria')->whereIn('id', array_keys($items))->get()->keyBy('id');

        $vigentes = array_intersect_key($items, $productos->all());
        if (count($vigentes) !== count($items)) {
            $this->guardar($vigentes);
        }

        return collect($vigentes)->map(fn (int $cantidad, int $id) => [
            'producto' => $productos[$id],
            'precio' => (string) $productos[$id]->precio,
            'cantidad' => $cantidad,
        ]);
    }

    /** Totales del carrito calculados por el motor del servidor. */
    public function resumen(): array
    {
        return $this->calculadora->calcular($this->lineas());
    }

    /** Regla de negocio: no se puede cotizar más de lo que hay en existencia. */
    private function validarStock(Producto $producto, int $cantidad): void
    {
        if ($producto->estaAgotado()) {
            throw ValidationException::withMessages([
                'cantidad' => "El producto {$producto->codigo_fabrica} está agotado.",
            ]);
        }

        if ($cantidad > $producto->stock) {
            throw ValidationException::withMessages([
                'cantidad' => "Solo hay {$producto->stock} unidades disponibles de {$producto->codigo_fabrica}.",
            ]);
        }
    }
}
