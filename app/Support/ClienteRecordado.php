<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

/**
 * "Recordar mis datos en este teléfono" para clientes invitados.
 *
 * Guarda los datos del formulario en una cookie CIFRADA (EncryptCookies de Laravel)
 * y solo legible por el servidor (HttpOnly), durante 90 días. Es opcional: el cliente
 * la activa con una casilla y la borra con "Borrar mis datos", porque en el campo
 * es común compartir el teléfono.
 */
class ClienteRecordado
{
    public const COOKIE = 'r7_cliente';
    private const DIAS = 90;
    public const CAMPOS = ['tipo_identificacion', 'numero_identificacion', 'nombre', 'telefono', 'correo', 'direccion'];

    /** @return array<string, string> datos guardados, o [] si no hay */
    public static function leer(): array
    {
        $datos = json_decode((string) request()->cookie(self::COOKIE), true);

        if (! is_array($datos)) {
            return [];
        }

        return array_map('strval', array_intersect_key($datos, array_flip(self::CAMPOS)));
    }

    public static function guardar(array $datos): void
    {
        $limpios = array_map(fn ($v) => mb_substr(trim(strip_tags((string) $v)), 0, 255), array_intersect_key($datos, array_flip(self::CAMPOS)));

        Cookie::queue(self::COOKIE, json_encode($limpios), self::DIAS * 24 * 60);
    }

    public static function olvidar(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }
}
