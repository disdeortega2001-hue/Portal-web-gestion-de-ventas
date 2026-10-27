<?php

namespace App\Support;

use App\Models\Configuracion;

/**
 * Datos de contacto de la empresa para la interfaz (WhatsApp y llamada).
 * El número se configura en Administración → Configuración; si está vacío,
 * las vistas no muestran ningún botón de contacto.
 */
class Contacto
{
    /** Número en formato internacional solo dígitos (58414…), o null si no está configurado. */
    public static function numero(): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) Configuracion::valor('telefono_whatsapp', ''));

        if (str_starts_with($digitos, '0')) {
            $digitos = '58' . substr($digitos, 1);   // 0414… → 58414…
        }

        return strlen($digitos) === 12 ? $digitos : null;
    }

    /** Enlace para escribir a la empresa por WhatsApp, con un mensaje ya redactado. */
    public static function whatsapp(string $mensaje = ''): ?string
    {
        $numero = self::numero();
        if (! $numero) {
            return null;
        }

        return 'https://wa.me/' . $numero . ($mensaje !== '' ? '?text=' . rawurlencode($mensaje) : '');
    }

    /** Enlace para llamar desde el celular. */
    public static function llamada(): ?string
    {
        $numero = self::numero();

        return $numero ? 'tel:+' . $numero : null;
    }

    /** Número como lo escribe la gente en Venezuela: 0414-1234567. */
    public static function telefonoVisible(): ?string
    {
        $numero = self::numero();

        return $numero ? '0' . substr($numero, 2, 3) . '-' . substr($numero, 5) : null;
    }

    /** Compartir un texto por WhatsApp eligiendo el chat (por ejemplo, enviárselo a uno mismo). */
    public static function compartir(string $mensaje): string
    {
        return 'https://wa.me/?text=' . rawurlencode($mensaje);
    }
}
