<?php

namespace App\Support;

/**
 * Ubica el archivo del logo oficial en public/img/, sea cual sea su formato.
 * Devuelve la ruta relativa a public/ (para asset()) o null si todavía no existe.
 */
class Logo
{
    private const EXTENSIONES = ['png', 'webp', 'jpg', 'jpeg', 'svg'];

    public static function ruta(): ?string
    {
        static $ruta = false;   // Una sola búsqueda por petición

        if ($ruta === false) {
            $ruta = null;
            foreach (self::EXTENSIONES as $extension) {
                if (is_file(public_path("img/logo.{$extension}"))) {
                    $ruta = "img/logo.{$extension}";
                    break;
                }
            }
        }

        return $ruta;
    }
}
