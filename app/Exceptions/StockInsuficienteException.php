<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza dentro de la transacción de venta cuando un producto no tiene
 * stock suficiente. Al salir de DB::transaction() provoca el rollback.
 */
class StockInsuficienteException extends RuntimeException
{
}
