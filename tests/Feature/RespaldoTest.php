<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Services\RespaldoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;
use ZipArchive;

/** Respaldo y recuperación (Cuadro 28: disponibilidad con mecanismos de respaldo). */
class RespaldoTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
        Storage::fake('public');

        // Carpeta propia de la prueba: nunca se tocan los respaldos reales de storage/app/respaldos
        $this->carpeta = storage_path('framework/testing/respaldos-' . uniqid());
        config(['filesystems.respaldos' => $this->carpeta]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);
        parent::tearDown();
    }

    public function test_el_respaldo_guarda_la_base_de_datos_y_las_fotos(): void
    {
        $this->crearProducto(['codigo_fabrica' => 'CC5002/3', 'nombre' => "Cardán 330 x 60 «O'Brien»"]);
        Storage::disk('public')->put('productos/foto-800.webp', 'imagen');

        $nombre = app(RespaldoService::class)->crear();

        $zip = new ZipArchive();
        $this->assertTrue($zip->open("{$this->carpeta}/{$nombre}"));
        $sql = $zip->getFromName('base-de-datos.sql');
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `productos`', $sql);
        $this->assertStringContainsString('CC5002/3', $sql);
        $this->assertSame('imagen', $zip->getFromName('fotos/foto-800.webp'));
        $zip->close();
    }

    public function test_restaurar_devuelve_los_datos_y_las_fotos_al_momento_del_respaldo(): void
    {
        $producto = $this->crearProducto(['nombre' => "Disco dentado 24\" x 6 5/16\"\nde rastra", 'stock' => 10]);
        Storage::disk('public')->put('productos/foto-800.webp', 'imagen');
        $servicio = app(RespaldoService::class);
        $nombre = $servicio->crear();

        // Después del respaldo: se vende, se borra un producto y se pierde la foto
        $producto->update(['stock' => 3]);
        $otro = $this->crearProducto(['nombre' => 'Creado después']);
        Storage::disk('public')->delete('productos/foto-800.webp');

        $r = $servicio->restaurar("{$this->carpeta}/{$nombre}");

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame("Disco dentado 24\" x 6 5/16\"\nde rastra", $producto->fresh()->nombre);
        $this->assertNull(Producto::withTrashed()->find($otro->id));
        Storage::disk('public')->assertExists('productos/foto-800.webp');
        $this->assertSame(1, $r['fotos']);
    }

    public function test_un_archivo_que_no_es_respaldo_no_cambia_nada(): void
    {
        $producto = $this->crearProducto(['stock' => 7]);
        $falso = storage_path('framework/testing/falso.zip');
        File::ensureDirectoryExists(dirname($falso));
        $zip = new ZipArchive();
        $zip->open($falso, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('otra-cosa.txt', 'hola');
        $zip->close();

        try {
            app(RespaldoService::class)->restaurar($falso);
            $this->fail('Debió rechazar el archivo.');
        } catch (\RuntimeException) {
            $this->assertSame(7, $producto->fresh()->stock);
        } finally {
            File::delete($falso);
        }
    }

    public function test_solo_el_administrador_ve_crea_y_descarga_respaldos(): void
    {
        $this->actingAs($this->crearUsuario('vendedor'))->get(route('admin.respaldos.index'))->assertForbidden();

        $admin = $this->crearUsuario('administrador');
        $this->actingAs($admin)->post(route('admin.respaldos.store'))->assertRedirect(route('admin.respaldos.index'));

        $nombre = app(RespaldoService::class)->listar()[0]['nombre'];
        $this->get(route('admin.respaldos.index'))->assertOk()->assertSee($nombre);
        $this->get(route('admin.respaldos.descargar', $nombre))->assertOk()->assertDownload($nombre);
        $this->get(route('admin.respaldos.descargar', '..%2F.env'))->assertNotFound();
    }

    public function test_se_conservan_solo_los_respaldos_mas_recientes(): void
    {
        File::ensureDirectoryExists($this->carpeta);
        for ($i = 1; $i <= RespaldoService::CONSERVAR + 3; $i++) {
            File::put(sprintf('%s/rialto7-2020-01-%02d-000000.zip', $this->carpeta, $i), 'x');
        }

        app(RespaldoService::class)->crear();

        $lista = app(RespaldoService::class)->listar();
        $this->assertCount(RespaldoService::CONSERVAR, $lista);
        $this->assertStringStartsWith('rialto7-' . now()->format('Y'), $lista[0]['nombre']);
    }
}
