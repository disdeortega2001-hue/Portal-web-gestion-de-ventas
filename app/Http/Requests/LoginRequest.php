<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Cédula o RIF (clientes) o correo (personal)
            'usuario' => ['required', 'string', 'max:150'],
            'contrasena' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'Escribe tu cédula o RIF (o tu correo si eres del personal).',
            'contrasena.required' => 'Escribe tu contraseña.',
        ];
    }
}
