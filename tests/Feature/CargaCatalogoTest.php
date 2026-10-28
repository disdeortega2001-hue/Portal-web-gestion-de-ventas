<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Support\PlantillaCatalogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Carga del catálogo real desde la plantilla de Excel (catalogo:importar).
 * Regla clave: con un solo error en el archivo no se guarda nada.
 */
class CargaCatalogoTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
        $this->crearUsuario('directivo');
        Storage::fake('public');

        $this->carpeta = storage_path('framework/testing/carga-' . uniqid());
        File::makeDirectory($this->carpeta . '/fotos', 0775, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);
        parent::tearDown();
    }

    /** Escribe las filas en la plantilla real (así la prueba también cubre sus títulos). */
    private function excel(array $filas): string
    {
        $archivo = $this->carpeta . '/productos.xlsx';
        PlantillaCatalogo::generar($archivo);

        $libro = IOFactory::load($archivo);
        $libro->getSheetByName('Productos')->fromArray($filas, null, 'A2', true);   // Estricto: el 0 se escribe, no queda vacío
        IOFactory::createWriter($libro, 'Xlsx')->save($archivo);

        return $archivo;
    }

    private function foto(string $nombre): void
    {
        $imagen = imagecreatetruecolor(1200, 900);
        imagejpeg($imagen, $this->carpeta . '/fotos/' . $nombre);
        imagedestroy($imagen);
    }

    public function test_reemplaza_el_catalogo_de_demostracion_con_existencias_y_fotos(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $demo = $this->crearProducto(['codigo_fabrica' => 'DEMO-1']);
        $this->venta($vendedor, $this->crearCliente(), [[$demo, 2]]);
        $this->foto('TT-24L.jpg');

        $archivo = $this->excel([
            ['TT-24L', 'Disco liso de rastra 24"', 'Discos', 'Marchesan Tatu', '68,00', 26, 'Rastra Tatu GAPCR', 'Disco liso', '', '41.5'],
            ['VC-100', 'Cuchilla de rotativa', 'Cuchillas', 'Vicon', '12.5', 0, '', '', '', ''],
        ]);

        $this->artisan('catalogo:importar', ['archivo' => $archivo, '--reemplazar' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(['TT-24L', 'VC-100'], Producto::withTrashed()->orderBy('codigo_fabrica')->pluck('codigo_fabrica')->all());
        $this->assertSame(0, DB::table('pedidos')->count());
        $this->assertSame(['Cuchillas', 'Discos'], Categoria::orderBy('nombre')->pluck('nombre')->all());

        $disco = Producto::where('codigo_fabrica', 'TT-24L')->first();
        $this->assertSame('68.00', $disco->precio);
        $this->assertSame('41.50', $disco->getAttributes()['costo']);
        $this->assertSame(26, $disco->stock);
        $this->assertDatabaseHas('movimientos_inventario', [
            'id_producto' => $disco->id, 'tipo' => MovimientoInventario::ENTRADA, 'cantidad' => 26, 'stock_nuevo' => 26,
        ]);
        // La foto se encontró por el código y se guardó en sus dos tamaños
        Storage::disk('public')->assertExists([$disco->imagen, str_replace('-800.', '-240.', $disco->imagen)]);

        $this->get(route('catalogo.index'))->assertOk()->assertSee('Disco liso de rastra')->assertDontSee('DEMO-1');
    }

    public function test_con_un_error_no_guarda_nada_y_dice_la_fila(): void
    {
        $actual = $this->crearProducto(['codigo_fabrica' => 'ACTUAL-1']);

        $archivo = $this->excel([
            ['A-1', 'Producto bueno', 'Filtros', 'Lumagi', '10', 5, '', '', '', ''],
            ['B-2', 'Precio malo', 'Filtros', 'Lumagi', 'diez', 5, '', '', '', ''],
            ['A-1', 'Código repetido', 'Filtros', 'Lumagi', '10', 5, '', '', '', ''],
            ['C-3', 'Foto que no existe', 'Filtros', 'Lumagi', '10', 5, '', '', 'no-esta.jpg', ''],
        ]);

        $this->artisan('catalogo:importar', ['archivo' => $archivo, '--reemplazar' => true, '--force' => true])
            ->expectsOutputToContain('Fila 3')
            ->expectsOutputToContain('Fila 4')
            ->expectsOutputToContain('Fila 5')
            ->assertExitCode(1);

        $this->assertSame([$actual->id], Producto::pluck('id')->all());
    }

    public function test_sin_reemplazar_actualiza_por_codigo_y_ajusta_la_existencia(): void
    {
        $producto = $this->crearProducto(['codigo_fabrica' => 'AE-500', 'precio' => '10.00', 'stock' => 10]);
        $otro = $this->crearProducto(['codigo_fabrica' => 'OTRO-1']);

        $archivo = $this->excel([['ae-500', 'Rodamiento Aemco', 'Rodamientos', 'Colombo Aemco', '15.75', 4, '', '', '', '']]);

        $this->artisan('catalogo:importar', ['archivo' => $archivo])->assertExitCode(0);

        $producto->refresh();
        $this->assertSame('15.75', $producto->precio);
        $this->assertSame(4, $producto->stock);
        $this->assertDatabaseHas('movimientos_inventario', [
            'id_producto' => $producto->id, 'tipo' => MovimientoInventario::AJUSTE, 'stock_anterior' => 10, 'stock_nuevo' => 4,
        ]);
        $this->assertNotNull(Producto::find($otro->id));   // Sin --reemplazar no se borra nada
    }

    public function test_modo_prueba_no_guarda(): void
    {
        $archivo = $this->excel([['X-1', 'Producto', 'Filtros', 'Vicon', '10', 1, '', '', '', '']]);

        $this->artisan('catalogo:importar', ['archivo' => $archivo, '--probar' => true])->assertExitCode(0);

        $this->assertSame(0, Producto::count());
    }
}
