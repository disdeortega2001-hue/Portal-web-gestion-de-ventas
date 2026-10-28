<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Registro de clientes: datos del cliente + credenciales de acceso.
 */
class RegistroRequest extends ClienteRequest
{
    public function rules(): array
    {
        $reglas = self::reglasCliente();
        // La cédula puede existir si solo cotizó como invitado; no si ya tiene cuenta
        // o si otra persona ya pidió asociarla y está en verificación.
        $reglas['identificacion'][] = Rule::unique('clientes', 'identificacion')
            ->where(fn (Builder $q) => $q->whereNotNull('id_usuario')->orWhereNotNull('id_usuario_solicitante'));
        // Opcional: solo sirve para recuperar la contraseña por correo
        $reglas['correo'] = ['nullable', 'email', 'max:150', 'unique:usuarios,correo'];
        $reglas['contrasena'] = ['required', 'confirmed', Password::min(8)->letters()->numbers()];

        return $reglas;
    }

    public function messages(): array
    {
        // Los mensajes propios van primero: con "+" gana el arreglo de la izquierda
        return [
            'identificacion.unique' => 'Esa cédula o RIF ya tiene una cuenta o una solicitud en verificación. Si es tuya, inicia sesión o llámanos.',
            'correo.unique' => 'Ese correo ya está registrado.',
            'contrasena.required' => 'Crea una contraseña.',
            'contrasena.confirmed' => 'Las contraseñas no coinciden.',
        ] + self::mensajesCliente();
    }
}
