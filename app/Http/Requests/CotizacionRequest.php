<?php

namespace App\Http\Requests;

/**
 * Generar cotización desde el carrito.
 * - Cliente registrado: sus datos ya están en la BD, solo observaciones.
 * - Invitado: debe llenar todos los datos del cliente.
 */
class CotizacionRequest extends ClienteRequest
{
    public function rules(): array
    {
        $reglas = ['observaciones' => ['nullable', 'string', 'max:500']];

        if (! $this->user()?->cliente) {
            $reglas += self::reglasCliente();
        }

        return $reglas;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()?->cliente) {
            parent::prepareForValidation();
        }
        $this->merge(['observaciones' => trim(strip_tags((string) $this->input('observaciones'))) ?: null]);
    }
}
