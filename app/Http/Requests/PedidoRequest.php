<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Pedido de mostrador creado por el vendedor: cliente existente + líneas.
 * Valida stock por línea (la validación definitiva ocurre de nuevo,
 * con bloqueo de filas, al confirmar la venta).
 */
class PedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esPersonal() ?? false;
    }

    public function rules(): array
    {
        return [
            // "exists" no conoce los soft deletes: sin whereNull, un producto o cliente eliminado
            // pasaría la validación y el servicio fallaría con un error 500 al no encontrarlo.
            'id_cliente' => ['required', 'integer', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['array'],
            'items.*.id_producto' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->whereNull('deleted_at')],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:10000'],
            // En dólares se cobra IGTF; en bolívares, IVA
            'moneda_pago' => ['required', 'in:USD,VES'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_cliente.required' => 'Selecciona el cliente.',
            'id_cliente.exists' => 'El cliente seleccionado no existe.',
            'items.required' => 'Agrega al menos un producto.',
            'moneda_pago.required' => 'Indica si el cliente paga en dólares o en bolívares.',
            'items.*.id_producto.distinct' => 'Hay un producto repetido; suma las cantidades en una sola línea.',
            'items.*.id_producto.exists' => 'Uno de los productos no existe.',
            'items.*.cantidad.min' => 'La cantidad mínima es 1.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $items = collect($this->input('items', []));
                $productos = Producto::whereIn('id', $items->pluck('id_producto'))->get()->keyBy('id');

                foreach ($items as $i => $item) {
                    $producto = $productos[$item['id_producto'] ?? 0] ?? null;
                    if ($producto && (int) ($item['cantidad'] ?? 0) > $producto->stock) {
                        $validator->errors()->add(
                            "items.{$i}.cantidad",
                            "Solo hay {$producto->stock} unidades de {$producto->codigo_fabrica}."
                        );
                    }
                }
            },
        ];
    }
}
