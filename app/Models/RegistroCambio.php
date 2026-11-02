<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Quién cambió un precio, un parámetro del portal o un crédito. Solo se agrega: nunca se edita ni se borra. */
class RegistroCambio extends Model
{
    public const UPDATED_AT = null;
    public const CREATED_AT = 'creado_en';

    protected $table = 'registros_cambios';

    protected $fillable = ['id_usuario', 'entidad', 'referencia', 'campo', 'antes', 'despues'];

    protected $casts = ['creado_en' => 'datetime'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
