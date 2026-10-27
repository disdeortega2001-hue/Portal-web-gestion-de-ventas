<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/** Página pública «Nuestra historia»: reseña y las cuatro marcas con representación exclusiva. */
class NosotrosTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    public function test_cuenta_la_historia_y_las_marcas_exclusivas(): void
    {
        $this->configurar();

        $this->get(route('nosotros'))
            ->assertOk()
            ->assertSee('Agente autorizado exclusivo en Venezuela')
            ->assertSeeInOrder(['Marchesan Tatu', 'Colombo Aemco', 'Vicon', 'Lumagi'])
            ->assertSee('1963')
            ->assertSee('Inversiones Campagna');
    }

    public function test_muestra_las_cifras_la_linea_de_vida_y_los_productos_de_cada_marca(): void
    {
        $this->configurar();
        $this->crearProducto(['marca' => 'Vicon']);
        $this->crearProducto(['marca' => 'Vicon']);
        $this->crearProducto(['marca' => 'Lumagi']);
        $this->crearProducto(['marca' => 'Vicon', 'activo' => false]);   // oculto: no cuenta

        $this->get(route('nosotros'))
            ->assertOk()
            ->assertSee((now()->year - 1963) . '<span class="text-sol">+</span>', false)
            ->assertSeeInOrder(['Fundación', 'Importación desde Brasil', 'Agente autorizado exclusivo', 'Ahora somos Rialto 7', 'Catálogo en línea'])
            ->assertSeeInOrder(['Confianza', 'Atención cercana', 'Conocimiento de cada pieza'])
            ->assertSee('Ver 2 productos')
            ->assertSee('Ver 1 producto')
            ->assertSee(route('catalogo.index', ['marca' => 'Colombo Aemco']), false);
    }

    public function test_el_catalogo_enlaza_a_la_historia(): void
    {
        $this->configurar();

        $this->get(route('catalogo.index'))->assertOk()->assertSee(route('nosotros'), false);
    }
}
