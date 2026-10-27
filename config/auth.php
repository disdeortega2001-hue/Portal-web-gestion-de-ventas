<?php

/*
|--------------------------------------------------------------------------
| Autenticación del portal Rialto 7
|--------------------------------------------------------------------------
| Igual a la configuración estándar de Laravel 10, salvo el modelo: los usuarios
| están en la tabla "usuarios" (App\Models\Usuario), con columnas "correo" y
| "contrasena". La tabla de tokens de recuperación conserva el nombre estándar
| porque lo exige el gestor de contraseñas de Laravel.
*/

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\Usuario::class,
        ],
    ],

    // Enlaces de recuperación: vencen a los 60 minutos y se puede pedir uno por minuto
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
