<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos obligatorios de un cliente. Lo reutilizan la cotización de invitados,
 * el registro y el pedido de mostrador, para que ningún pedido quede incompleto.
 *
 * Patrones venezolanos:
 *  - Cédula/RIF: V-12345678, E-81234567, J-30123456-7, G-20000001-1
 *  - Teléfono: 0414-1234567, 0235-3412345
 */
class ClienteRequest extends FormRequest
{
    public const PATRON_IDENTIFICACION = '/^[VEJPGvejpg]-?\d{6,9}(-?\d)?$/';
    public const PATRON_TELEFONO = '/^0\d{3}-?\d{7}$/';

    public function authorize(): bool
    {
        return true;
    }

    /** Reglas reutilizables por otros Form Requests. */
    public static function reglasCliente(?int $idClienteIgnorar = null): array
    {
        return [
            'identificacion' => ['required', 'string', 'max:20', 'regex:' . self::PATRON_IDENTIFICACION],
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'telefono' => ['required', 'string', 'regex:' . self::PATRON_TELEFONO],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public static function mensajesCliente(): array
    {
        return [
            'identificacion.required' => 'Escribe tu cédula o RIF.',
            'identificacion.regex' => 'La cédula o RIF debe tener el formato V-12345678 o J-30123456-7.',
            'identificacion.unique' => 'Ya existe un cliente con esa cédula o RIF.',
            'nombre.required' => 'Escribe el nombre o razón social.',
            'nombre.min' => 'El nombre debe tener al menos 3 letras.',
            'telefono.required' => 'Escribe un número de teléfono.',
            'telefono.regex' => 'El teléfono debe tener el formato 0414-1234567.',
            'correo.email' => 'El correo no tiene un formato válido.',
            'direccion.required' => 'Escribe la dirección (finca, sector, municipio).',
        ];
    }

    public function rules(): array
    {
        $idCliente = $this->route('cliente')?->id;
        $reglas = self::reglasCliente();
        $reglas['identificacion'][] = Rule::unique('clientes', 'identificacion')->ignore($idCliente);

        return $reglas;
    }

    public function messages(): array
    {
        return self::mensajesCliente();
    }

    /**
     * Sanitización: deja la identificación igual que el modelo Cliente la guarda
     * (V-12345678), para que las búsquedas y la regla unique coincidan.
     */
    protected function prepareForValidation(): void
    {
        $identificacion = strtoupper(str_replace([' ', '.'], '', (string) $this->input('identificacion')));

        // Formulario en dos partes (letra + número): se unen en "V-12345678"
        if ($this->filled('tipo_identificacion') && $this->filled('numero_identificacion')) {
            $identificacion = strtoupper((string) $this->input('tipo_identificacion'))
                . preg_replace('/\D/', '', (string) $this->input('numero_identificacion'));
        }

        $this->merge([
            'identificacion' => preg_replace('/^([VEJPG])-?/', '$1-', $identificacion),
            'nombre' => trim(strip_tags((string) $this->input('nombre'))),
            'direccion' => trim(strip_tags((string) $this->input('direccion'))),
            'telefono' => str_replace(' ', '', (string) $this->input('telefono')),
        ]);
    }
}
