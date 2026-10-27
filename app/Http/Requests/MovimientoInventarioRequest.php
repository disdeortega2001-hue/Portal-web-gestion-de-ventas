<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimientoInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esPersonal() ?? false;
    }

    public function rules(): array
    {
        return [
            // Un producto eliminado no recibe movimientos (exists no considera soft deletes por sí solo)
            'id_producto' => ['required', 'integer', Rule::exists('productos', 'id')->whereNull('deleted_at')],
            'tipo' => ['required', 'in:entrada,salida,ajuste'],
            // En ajuste se indica el stock contado (puede ser 0); en entrada/salida, unidades (> 0)
            'cantidad' => ['required', 'integer', $this->input('tipo') === 'ajuste' ? 'min:0' : 'min:1', 'max:100000'],
            'motivo' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_producto.required' => 'Selecciona el producto.',
            'tipo.required' => 'Indica si es entrada, salida o ajuste.',
            'cantidad.required' => 'Indica la cantidad.',
            'cantidad.min' => 'La cantidad no es válida para este tipo de movimiento.',
            'motivo.required' => 'Explica el motivo (factura del proveedor, conteo físico, merma...).',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['motivo' => trim(strip_tags((string) $this->input('motivo')))]);
    }
}
