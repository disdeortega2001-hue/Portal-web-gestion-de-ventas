<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:100', Rule::unique('categorias', 'nombre')->ignore($this->route('categoria')?->id)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre de la categoría.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim(strip_tags((string) $this->input('nombre'))),
            'descripcion' => trim(strip_tags((string) $this->input('descripcion'))) ?: null,
        ]);
    }
}
