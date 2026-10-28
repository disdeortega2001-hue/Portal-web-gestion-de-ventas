<?php

namespace App\Http\Requests;

use App\Support\MetodosPago;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Parámetros de negocio. Las mismas claves que se guardan en la tabla "configuraciones",
 * más las de los métodos de pago (activo y datos para pagar de cada uno).
 */
class ConfiguracionRequest extends FormRequest
{
    public const CLAVES = ['tasa_cambio', 'porcentaje_iva', 'umbral_stock_bajo', 'descuento_porcentaje', 'descuento_monto_minimo', 'telefono_whatsapp'];

    /** Claves numéricas: aceptan coma decimal. */
    private const NUMERICAS = ['tasa_cambio', 'porcentaje_iva', 'umbral_stock_bajo', 'descuento_porcentaje', 'descuento_monto_minimo'];

    /** Todas las claves que guarda el formulario. */
    public static function claves(): array
    {
        return array_merge(self::CLAVES, MetodosPago::clavesConfiguracion());
    }

    public function authorize(): bool
    {
        return $this->user()?->puedeAdministrar() ?? false;
    }

    public function rules(): array
    {
        return [
            'tasa_cambio' => ['required', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,4'],
            'porcentaje_iva' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'umbral_stock_bajo' => ['required', 'integer', 'min:0', 'max:100000'],
            'descuento_porcentaje' => ['required', 'numeric', 'min:0', 'max:50', 'decimal:0,2'],
            'descuento_monto_minimo' => ['required', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'],
            // Vacío = sin botones de contacto en el portal
            'telefono_whatsapp' => ['nullable', 'regex:/^0\d{10}$/'],
        ] + $this->reglasPagos();
    }

    /** Cada método: casilla de activo y sus datos (opcionales: sin datos, se envían por WhatsApp). */
    private function reglasPagos(): array
    {
        $reglas = [];
        foreach (MetodosPago::METODOS as $metodo => $definicion) {
            $reglas["{$metodo}_activo"] = ['nullable', 'in:1'];
            foreach (array_keys($definicion['campos']) as $clave) {
                $reglas[$clave] = ['nullable', 'string', 'max:120'];
            }
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'tasa_cambio.required' => 'Indica la tasa de cambio.',
            'tasa_cambio.gt' => 'La tasa debe ser mayor que cero.',
            'tasa_cambio.decimal' => 'La tasa admite máximo 4 decimales.',
            'porcentaje_iva.max' => 'El IVA no puede superar 100 %.',
            'umbral_stock_bajo.integer' => 'El umbral debe ser un número entero de unidades.',
            'descuento_porcentaje.max' => 'El descuento no puede superar 50 %.',
            'telefono_whatsapp.regex' => 'Escribe el número completo, por ejemplo 0414-1234567.',
        ];
    }

    /**
     * - Números: acepta coma decimal venezolana ("36,50" → "36.50").
     * - Teléfono: deja solo dígitos y pasa +58 414… a 0414…
     */
    protected function prepareForValidation(): void
    {
        $limpios = [];
        foreach (self::NUMERICAS as $clave) {
            $valor = trim((string) $this->input($clave));
            $limpios[$clave] = str_contains($valor, ',') ? str_replace(['.', ','], ['', '.'], $valor) : $valor;
        }

        $telefono = preg_replace('/\D/', '', (string) $this->input('telefono_whatsapp'));
        if (strlen($telefono) === 12 && str_starts_with($telefono, '58')) {
            $telefono = '0' . substr($telefono, 2);
        }
        $limpios['telefono_whatsapp'] = $telefono === '' ? null : $telefono;

        $this->merge($limpios);
    }
}
