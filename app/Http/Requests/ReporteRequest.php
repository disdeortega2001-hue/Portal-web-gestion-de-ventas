<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros de los reportes. El rango máximo de un año mantiene la generación
 * en segundos, incluso en hosting compartido.
 */
class ReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puedeAdministrar() ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:ventas,inventario'],
            'desde' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde', 'before_or_equal:' . $this->fechaMaxima()],
            'id_producto' => ['nullable', 'integer', 'exists:productos,id'],
            'id_vendedor' => ['nullable', 'integer', Rule::exists('usuarios', 'id')->whereIn('rol', \App\Models\Usuario::PERFILES_PANEL)],
            'id_categoria' => ['nullable', 'integer', 'exists:categorias,id'],
            'formato' => ['nullable', 'in:pantalla,pdf,excel'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Elige el tipo de reporte.',
            'desde.required' => 'Indica la fecha inicial.',
            'desde.before_or_equal' => 'La fecha inicial no puede ser futura.',
            'hasta.required' => 'Indica la fecha final.',
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            'hasta.before_or_equal' => 'El rango no puede superar un año.',
            'id_vendedor.exists' => 'El vendedor seleccionado no existe.',
        ];
    }

    /** Un año después de "desde" (o hoy si "desde" no es válida todavía). */
    private function fechaMaxima(): string
    {
        $desde = strtotime((string) $this->input('desde'));

        return $desde ? date('Y-m-d', strtotime('+1 year', $desde)) : date('Y-m-d');
    }

    /** Filtros limpios para el servicio de reportes. */
    public function filtros(): array
    {
        return $this->safe()->only(['tipo', 'desde', 'hasta', 'id_producto', 'id_vendedor', 'id_categoria']);
    }
}
