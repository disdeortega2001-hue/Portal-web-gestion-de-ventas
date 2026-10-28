<?php

namespace App\Http\Requests;

use App\Support\MetodosPago;
use Illuminate\Validation\Rule;

/**
 * Confirmar una compra en el portal.
 * - Cliente con cuenta: sus datos ya están guardados; solo elige pago y entrega.
 * - Sin cuenta: escribe lo básico (cédula o RIF, nombre, teléfono y dirección). No hace falta correo ni contraseña.
 */
class CompraRequest extends ClienteRequest
{
    public function rules(): array
    {
        $reglas = [
            'metodo_pago' => ['required', Rule::in(array_keys(MetodosPago::disponibles()))],
            'entrega' => ['required', 'in:retiro,envio'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];

        if (! $this->user()?->cliente) {
            $reglas += self::reglasCliente();
            unset($reglas['correo']);
        }

        return $reglas;
    }

    public function messages(): array
    {
        return self::mensajesCliente() + [
            'metodo_pago.required' => 'Elige cómo vas a pagar.',
            'metodo_pago.in' => 'Ese método de pago no está disponible.',
            'entrega.required' => 'Elige cómo recibes tu pedido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()?->cliente) {
            parent::prepareForValidation();
        }
        $this->merge(['observaciones' => trim(strip_tags((string) $this->input('observaciones'))) ?: null]);
    }
}
