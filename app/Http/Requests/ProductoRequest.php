<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de productos (administrador).
 * El código de fábrica es obligatorio y único: así cada pedido lleva un código estándar.
 */
class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puedeAdministrar() ?? false;
    }

    public function rules(): array
    {
        $idProducto = $this->route('producto')?->id;

        return [
            'codigo_fabrica' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9][A-Z0-9\-\.\/]*$/',
                Rule::unique('productos', 'codigo_fabrica')->ignore($idProducto)],
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'compatibilidad' => ['nullable', 'string', 'max:2000'],
            'marca' => ['required', 'string', 'max:80'],
            'precio' => ['required', 'numeric', 'min:0.01', 'max:9999999999', 'decimal:0,2'],
            'costo' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'id_categoria' => ['required', 'integer', 'exists:categorias,id'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'stock' => [$idProducto ? 'prohibited' : 'required', 'integer', 'min:0'],  // Al editar, el stock solo cambia por movimientos
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_fabrica.required' => 'El código de fábrica es obligatorio.',
            'codigo_fabrica.unique' => 'Ya existe un producto con ese código de fábrica.',
            'codigo_fabrica.regex' => 'Usa solo letras mayúsculas, números, guiones, puntos o barras.',
            'nombre.required' => 'Escribe el nombre del producto.',
            'marca.required' => 'Indica la marca o fabricante.',
            'precio.required' => 'Indica el precio en USD.',
            'precio.min' => 'El precio debe ser mayor que cero.',
            'precio.decimal' => 'El precio admite máximo 2 decimales.',
            'id_categoria.required' => 'Selecciona una categoría.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.max' => 'La imagen no debe pesar más de 2 MB.',
            'stock.prohibited' => 'El stock se modifica desde Inventario, no desde la ficha.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo_fabrica' => mb_strtoupper(trim((string) $this->input('codigo_fabrica'))),
            'activo' => $this->boolean('activo'),
        ]);
    }
}
