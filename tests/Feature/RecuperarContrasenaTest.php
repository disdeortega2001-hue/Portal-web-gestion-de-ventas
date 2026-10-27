<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Notifications\RestablecerContrasena;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Recuperación de contraseña adaptada a usuarios(correo, contrasena).
 */
class RecuperarContrasenaTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->configurar();
        $this->usuario = $this->crearUsuario('cliente', ['correo' => 'productor@prueba.test']);
    }

    /** Pide el enlace y devuelve el token que viajó en el correo. */
    private function pedirEnlace(string $correo = 'productor@prueba.test'): ?string
    {
        $this->post(route('password.email'), ['correo' => $correo]);

        $token = null;
        Notification::assertSentTo($this->usuario, RestablecerContrasena::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        return $token;
    }

    public function test_envia_el_enlace_al_correo_registrado(): void
    {
        $this->post(route('password.email'), ['correo' => 'productor@prueba.test'])
            ->assertSessionHas('exito');

        Notification::assertSentTo($this->usuario, RestablecerContrasena::class, function (RestablecerContrasena $n) {
            $correo = $n->toMail($this->usuario);

            return str_contains($correo->actionUrl, '/restablecer-contrasena/' . $n->token)
                && str_contains($correo->actionUrl, 'correo=productor%40prueba.test')
                && $correo->subject === 'Recupera tu contraseña · Inversiones Rialto 7';
        });
    }

    public function test_responde_igual_si_el_correo_no_existe_para_no_revelar_cuentas(): void
    {
        $this->post(route('password.email'), ['correo' => 'productor@prueba.test'])->assertSessionHas('exito');
        $mensaje = session('exito');

        $this->post(route('password.email'), ['correo' => 'nadie@prueba.test'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito', $mensaje);

        Notification::assertSentTimes(RestablecerContrasena::class, 1);
    }

    public function test_una_cuenta_desactivada_no_recibe_enlace(): void
    {
        $this->usuario->update(['estado' => 'inactivo']);

        $this->post(route('password.email'), ['correo' => 'productor@prueba.test'])->assertSessionHas('exito');

        Notification::assertNothingSent();
    }

    public function test_con_el_enlace_cambia_la_contrasena_y_puede_ingresar(): void
    {
        $token = $this->pedirEnlace();

        $this->post(route('password.store'), [
            'token' => $token,
            'correo' => 'productor@prueba.test',
            'contrasena' => 'NuevaClave2024',
            'contrasena_confirmation' => 'NuevaClave2024',
        ])->assertRedirect(route('login'))->assertSessionHas('exito');

        $this->assertTrue(Hash::check('NuevaClave2024', $this->usuario->fresh()->contrasena));

        $this->post(route('login'), ['correo' => 'productor@prueba.test', 'contrasena' => 'NuevaClave2024']);
        $this->assertAuthenticatedAs($this->usuario->fresh());
    }

    public function test_el_enlace_solo_sirve_una_vez(): void
    {
        $token = $this->pedirEnlace();
        $datos = ['token' => $token, 'correo' => 'productor@prueba.test', 'contrasena' => 'NuevaClave2024', 'contrasena_confirmation' => 'NuevaClave2024'];

        $this->post(route('password.store'), $datos)->assertSessionHasNoErrors();
        $this->post(route('password.store'), ['contrasena' => 'OtraClave2024', 'contrasena_confirmation' => 'OtraClave2024'] + $datos)
            ->assertSessionHasErrors('correo');

        $this->assertTrue(Hash::check('NuevaClave2024', $this->usuario->fresh()->contrasena));
    }

    public function test_un_token_falso_o_vencido_es_rechazado(): void
    {
        $token = $this->pedirEnlace();
        $datos = ['correo' => 'productor@prueba.test', 'contrasena' => 'NuevaClave2024', 'contrasena_confirmation' => 'NuevaClave2024'];

        $this->post(route('password.store'), ['token' => 'token-inventado'] + $datos)->assertSessionHasErrors('correo');

        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();
        $this->post(route('password.store'), ['token' => $token] + $datos)->assertSessionHasErrors('correo');

        $this->assertTrue(Hash::check('Clave1234', $this->usuario->fresh()->contrasena));   // Sigue la original
    }

    public function test_la_nueva_contrasena_debe_ser_segura_y_coincidir(): void
    {
        $token = $this->pedirEnlace();

        $this->post(route('password.store'), [
            'token' => $token, 'correo' => 'productor@prueba.test',
            'contrasena' => 'corta', 'contrasena_confirmation' => 'distinta',
        ])->assertSessionHasErrors('contrasena');
    }
}
