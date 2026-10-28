<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    // id_usuario_solicitante no es asignable en masa: solo lo fija el registro y lo resuelve el personal
    protected $fillable = ['id_usuario', 'identificacion', 'nombre', 'telefono', 'correo', 'direccion'];

    protected $casts = [
        'solicitud_vinculacion_en' => 'datetime',
    ];

    /** ¿Tiene cotizaciones o pedidos? Una ficha con historial no se asocia a una cuenta sin verificar. */
    public function tieneHistorial(): bool
    {
        return $this->cotizaciones()->exists() || $this->pedidos()->exists();
    }

    /** Búsqueda del personal por nombre, cédula/RIF (con o sin letra y guion) o teléfono. */
    public function scopeBuscar(Builder $consulta, ?string $texto): Builder
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return $consulta;
        }
        $digitos = preg_replace('/\D/', '', $texto);

        return $consulta->where(function ($q) use ($texto, $digitos) {
            $q->where('nombre', 'like', '%' . $texto . '%')
              ->orWhere('identificacion', 'like', '%' . strtoupper($texto) . '%');
            if (strlen($digitos) >= 4) {
                $q->orWhere('identificacion', 'like', '%' . $digitos . '%')
                  ->orWhereRaw("REPLACE(telefono, '-', '') LIKE ?", ['%' . $digitos . '%']);
            }
        });
    }

    /** Normaliza la cédula/RIF: mayúsculas y con guion tras la letra (V-12345678). */
    public function setIdentificacionAttribute(string $valor): void
    {
        $limpio = strtoupper(preg_replace('/[\s.]/', '', $valor));
        $this->attributes['identificacion'] = preg_replace('/^([VEJPG])-?/', '$1-', $limpio);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    /** Cuenta que pidió asociarse a esta ficha y espera la verificación del vendedor. */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_solicitante');
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'id_cliente');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_cliente');
    }
}
