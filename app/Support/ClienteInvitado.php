<?php

namespace App\Support;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Usuario;

class ClienteInvitado
{
    /**
     * El invitado se identifica solo con su cédula/RIF, SIN contraseña. Por eso un formulario
     * anónimo (cotización o compra sin cuenta) nunca modifica la ficha de un cliente que ya
     * existe ni la asocia a una cuenta: quien escriba una cédula ajena no puede cambiar el
     * teléfono al que llama el vendedor ni apropiarse de sus cotizaciones o pedidos. Si los
     * datos difieren, se anotan en el documento para que el vendedor los verifique antes de llamar.
     *
     * @return array{0: Cliente, 1: ?string} el cliente y una nota de contacto (o null)
     */
    public static function resolver(ClienteRequest $request): array
    {
        $datos = $request->only('nombre', 'telefono', 'correo', 'direccion');
        $cliente = Cliente::withTrashed()->where('identificacion', $request->input('identificacion'))->first();

        if (! $cliente) {
            $cliente = new Cliente(['identificacion' => $request->input('identificacion')] + $datos);

            // Usuario cliente creado por el administrador: su primera cotización crea su ficha
            if ($request->user()?->rol === Usuario::ROL_CLIENTE) {
                $cliente->id_usuario = $request->user()->id;
            }

            $cliente->save();

            return [$cliente, null];
        }

        if ($cliente->trashed()) {
            $cliente->restore();
        }

        $soloDigitos = fn (?string $tel) => preg_replace('/\D/', '', (string) $tel);
        $distintos = mb_strtolower(trim($cliente->nombre)) !== mb_strtolower(trim($datos['nombre']))
            || $soloDigitos($cliente->telefono) !== $soloDigitos($datos['telefono'])
            || mb_strtolower(trim($cliente->direccion)) !== mb_strtolower(trim($datos['direccion']));

        $nota = $distintos
            ? "Contacto indicado por el cliente (verificar antes de llamar): {$datos['nombre']} · {$datos['telefono']} · {$datos['direccion']}"
            : null;

        return [$cliente, $nota];
    }
}
