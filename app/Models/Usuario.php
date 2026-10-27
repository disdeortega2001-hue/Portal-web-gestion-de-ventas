<?php

namespace App\Models;

use App\Notifications\RestablecerContrasena;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario autenticable del sistema. El rol define qué módulos puede usar.
 */
class Usuario extends Authenticatable
{
    use Notifiable, SoftDeletes;

    public const ROL_CLIENTE = 'cliente';
    public const ROL_VENDEDOR = 'vendedor';
    public const ROL_ADMINISTRADOR = 'administrador';

    protected $table = 'usuarios';

    protected $fillable = ['nombre', 'correo', 'contrasena', 'rol', 'estado'];

    protected $hidden = ['contrasena', 'remember_token'];

    protected $casts = [
        'contrasena' => 'hashed',   // Se aplica bcrypt automáticamente al asignar
    ];

    /** Laravel busca la contraseña en "password"; aquí la columna es "contrasena". */
    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    /** Correo usado por las notificaciones (restablecer contraseña, etc.). */
    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }

    /** El gestor de contraseñas de Laravel busca "email"; aquí la columna es "correo". */
    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    /** Envía el enlace de recuperación con un mensaje en español. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerContrasena($token));
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    public function esPersonal(): bool
    {
        return $this->tieneRol(self::ROL_VENDEDOR, self::ROL_ADMINISTRADOR);
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'id_usuario');
    }

    public function pedidosGestionados(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_usuario');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'id_usuario');
    }
}
