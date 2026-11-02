<?php

namespace App\Models\Concerns;

use App\Models\RegistroCambio;

/**
 * Deja constancia de quién cambió los campos sensibles de un modelo, con el valor anterior y el nuevo.
 * Solo registra lo que hace una persona: la tasa que trae sola la tarea automática no llena el registro.
 * El modelo define camposRegistrados() ([campo => nombre legible]) y referenciaRegistro().
 */
trait RegistraCambios
{
    protected static function bootRegistraCambios(): void
    {
        static::updated(function ($modelo) {
            if (! auth()->check()) {
                return;
            }
            foreach ($modelo->camposRegistrados() as $campo => $nombre) {
                if ($modelo->wasChanged($campo)) {
                    RegistroCambio::create([
                        'id_usuario' => auth()->id(),
                        'entidad' => $modelo->entidadRegistro(),
                        'referencia' => $modelo->referenciaRegistro(),
                        'campo' => $nombre,
                        'antes' => self::textoRegistro($modelo->getOriginal($campo)),
                        'despues' => self::textoRegistro($modelo->getAttribute($campo)),
                    ]);
                }
            }
        });
    }

    private static function textoRegistro(mixed $valor): ?string
    {
        return is_bool($valor) ? ($valor ? 'Sí' : 'No') : ($valor === null ? null : (string) $valor);
    }
}
