<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * El cliente se registra con lo básico (sin correo obligatorio) y entra con su cédula o RIF.
 * El personal sigue entrando con su correo.
 */
class AccesoClienteTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
    }

    private function registrar(array $extra = []): void
    {
        $this->post(route('registro'), $extra + [
            'tipo_identificacion' => 'V', 'numero_identificacion' => '20111222',
            'nombre' => 'Pedro Pérez', 'telefono' => '0414-1112233', 'direccion' => 'Finca La Ceiba, Zaraza',
            'contrasena' => 'Clave1234', 'contrasena_confirmation' => 'Clave1234',
        ]);
    }

    public function test_el_registro_no_exige_correo(): void
    {
        $this->registrar();

        $usuario = Usuario::where('rol', 'cliente')->firstOrFail();
        $this->assertNull($usuario->correo);
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_cliente_entra_con_su_cedula_escrita_de_cualquier_forma(): void
    {
        $this->registrar();
        $usuario = Usuario::where('rol', 'cliente')->firstOrFail();

        foreach (['V-20111222', 'v20111222', '20.111.222', ' V 20111222 '] as $cedula) {
            auth()->logout();
            $this->post(route('login'), ['usuario' => $cedula, 'contrasena' => 'Clave1234'])->assertRedirect();
            $this->assertAuthenticatedAs($usuario);
        }
    }

    public function test_cedula_o_clave_equivocada_no_entra_y_no_revela_cual_fallo(): void
    {
        $this->registrar();
        auth()->logout();

        $this->post(route('login'), ['usuario' => 'V-20111222', 'contrasena' => 'Equivocada9'])->assertSessionHasErrors(['usuario' => 'Los datos de acceso o la contraseña no son correctos.']);
        $this->post(route('login'), ['usuario' => 'V-99999999', 'contrasena' => 'Clave1234'])->assertSessionHasErrors(['usuario' => 'Los datos de acceso o la contraseña no son correctos.']);
        $this->assertGuest();
    }

    public function test_el_personal_sigue_entrando_con_su_correo(): void
    {
        $vendedor = $this->crearUsuario('vendedor');

        $this->post(route('login'), ['usuario' => $vendedor->correo, 'contrasena' => 'Clave1234'])->assertRedirect(route('gestion.tablero'));
    }

    public function test_el_personal_crea_una_contrasena_temporal_para_un_cliente_sin_correo(): void
    {
        $this->registrar();
        $cliente = Usuario::where('rol', 'cliente')->firstOrFail()->cliente;
        auth()->logout();

        $this->actingAs($this->crearUsuario('vendedor'))
            ->post(route('gestion.clientes.contrasena', $cliente))
            ->assertSessionHas('exito');

        preg_match('/temporal de .+?: (\S+) /', session('exito'), $m);
        auth()->logout();
        $this->post(route('login'), ['usuario' => $cliente->identificacion, 'contrasena' => $m[1]])->assertRedirect();
        $this->assertAuthenticated();

        $this->actingAs($this->crearUsuario('cliente'))->post(route('gestion.clientes.contrasena', $cliente))->assertForbidden();
    }
}
