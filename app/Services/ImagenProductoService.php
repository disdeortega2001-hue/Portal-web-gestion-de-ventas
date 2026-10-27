<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Prepara las fotos de productos para conexiones rurales lentas.
 *
 * Una foto de celular pesa 2–4 MB; el catálogo en el celular la muestra a 96 px.
 * Al subirla se guardan dos versiones livianas (con la extensión GD de PHP):
 *   - productos/<uuid>-800.webp  → ficha del producto (~60–120 KB)
 *   - productos/<uuid>-240.webp  → tarjetas del catálogo (~10–20 KB)
 * El original no se guarda. Si el servidor no tiene WebP, se usa JPEG.
 */
class ImagenProductoService
{
    private const CARPETA = 'productos';
    private const ANCHOS = [800, 240];

    /** @return string ruta de la versión grande (la que se guarda en productos.imagen) */
    public function guardar(UploadedFile $archivo): string
    {
        $origen = @imagecreatefromstring((string) file_get_contents($archivo->getRealPath()));

        if (! $origen instanceof GdImage) {
            throw ValidationException::withMessages([
                'imagen' => 'No pudimos leer la imagen. Prueba con otra foto en JPG, PNG o WEBP.',
            ]);
        }

        $origen = $this->corregirOrientacion($origen, $archivo);
        $base = self::CARPETA . '/' . Str::uuid();
        $extension = function_exists('imagewebp') ? 'webp' : 'jpg';

        try {
            foreach (self::ANCHOS as $ancho) {
                $version = $this->redimensionar($origen, $ancho);
                Storage::disk('public')->put("{$base}-{$ancho}.{$extension}", $this->codificar($version, $extension));
                imagedestroy($version);
            }
        } catch (\Throwable $e) {
            $this->eliminar("{$base}-800.{$extension}");   // No dejar una versión a medias
            throw $e;
        } finally {
            imagedestroy($origen);
        }

        return "{$base}-800.{$extension}";
    }

    /** Borra las dos versiones de una foto (o el archivo único de fotos anteriores a este cambio). */
    public function eliminar(?string $ruta): void
    {
        if (! $ruta) {
            return;
        }

        Storage::disk('public')->delete(array_unique([$ruta, str_replace('-800.', '-240.', $ruta)]));
    }

    /** Copia la imagen a un lienzo blanco del ancho pedido (sin agrandar fotos pequeñas). */
    private function redimensionar(GdImage $origen, int $anchoMaximo): GdImage
    {
        $ancho = imagesx($origen);
        $alto = imagesy($origen);
        $nuevoAncho = min($anchoMaximo, $ancho);
        $nuevoAlto = (int) max(1, round($alto * $nuevoAncho / $ancho));

        $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));   // Fondo blanco para PNG con transparencia
        imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

        return $lienzo;
    }

    private function codificar(GdImage $imagen, string $extension): string
    {
        ob_start();
        $extension === 'webp' ? imagewebp($imagen, null, 80) : imagejpeg($imagen, null, 82);

        return (string) ob_get_clean();
    }

    /** Las fotos de celular vienen giradas según el dato EXIF de orientación. */
    private function corregirOrientacion(GdImage $imagen, UploadedFile $archivo): GdImage
    {
        if (! function_exists('exif_read_data') || $archivo->getMimeType() !== 'image/jpeg') {
            return $imagen;
        }

        $orientacion = (int) (@exif_read_data($archivo->getRealPath())['Orientation'] ?? 1);
        $grados = [3 => 180, 6 => -90, 8 => 90][$orientacion] ?? 0;

        if ($grados === 0) {
            return $imagen;
        }

        $girada = imagerotate($imagen, $grados, 0);
        imagedestroy($imagen);

        return $girada;
    }
}
