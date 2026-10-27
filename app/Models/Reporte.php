<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Auditoría de reportes generados (permite medir el indicador de reportes gerenciales).
 */
class Reporte extends Model
{
    protected $table = 'reportes';

    protected $fillable = ['id_usuario', 'tipo', 'formato', 'filtros', 'registros', 'fecha'];

    protected $casts = [
        'filtros' => 'array',
        'fecha' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario')->withTrashed();
    }
}
