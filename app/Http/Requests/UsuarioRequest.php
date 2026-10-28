<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Alta y edición del personal por el directivo. Solo se crean los tres perfiles del panel:
 * los clientes se registran solos al comprar. Al editar, la contraseña es opcional (vacía = no se cambia).
 */
class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esDirectivo() ?? false;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'correo' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'correo')->ignore($usuario?->id)],
            'rol' => ['required', Rule::in(Usuario::PERFILES_PANEL)],
            'estado' => ['required', 'in:activo,inactivo'],
            'contrasena' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre.',
            'correo.required' => 'Escribe el correo.',
            'correo.unique' => 'Ya existe un usuario con ese correo.',
            'rol.required' => 'Selecciona el perfil.',
            'rol.in' => 'Elige vendedor, administrativo o directivo.',
            'contrasena.required' => 'Crea una contraseña.',
            'contrasena.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    /** El directivo no puede quitarse a sí mismo el perfil ni desactivarse (evita quedar sin acceso). */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $usuario = $this->route('usuario');
                if ($usuario && $usuario->is($this->user())) {
                    if ($this->input('rol') !== Usuario::ROL_DIRECTIVO) {
                        $validator->errors()->add('rol', 'No puedes quitarte el perfil de directivo.');
                    }
                    if ($this->input('estado') !== 'activo') {
                        $validator->errors()->add('estado', 'No puedes desactivar tu propia cuenta.');
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim(strip_tags((string) $this->input('nombre'))),
            'correo' => mb_strtolower(trim((string) $this->input('correo'))),
        ]);
    }
}
