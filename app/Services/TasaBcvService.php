<?php

namespace App\Services;

use App\Models\Configuracion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Tasa oficial del dólar publicada por el Banco Central de Venezuela (www.bcv.org.ve).
 *
 * El BCV publica en su página de inicio un recuadro «USD» con la tasa (por ejemplo «888,11830000»)
 * y la «Fecha Valor». Este servicio lee ese recuadro, valida el número y lo guarda como la tasa
 * del portal, con la fuente y la fecha valor. Si el BCV no responde o cambia su página, lanza
 * una excepción y la tasa anterior sigue vigente (también se puede escribir a mano).
 */
class TasaBcvService
{
    public const URL = 'https://www.bcv.org.ve/';

    /** @return array{tasa: float, fecha_valor: ?Carbon} */
    public function consultar(): array
    {
        try {
            $respuesta = Http::timeout(25)->retry(2, 1500, throw: false)
                ->withOptions(['verify' => $this->certificados()])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Portal Rialto 7)'])
                ->get(self::URL);
        } catch (ConnectionException $e) {
            throw new RuntimeException('No se pudo conectar con el BCV. Revisa la conexión a internet o escribe la tasa a mano.', 0, $e);
        }

        if (! $respuesta->successful()) {
            throw new RuntimeException('El BCV no respondió (código ' . $respuesta->status() . '). Intenta más tarde o escribe la tasa a mano.');
        }

        return $this->leer($respuesta->body());
    }

    /**
     * Extrae la tasa y la fecha valor del HTML del BCV.
     *
     * @return array{tasa: float, fecha_valor: ?Carbon}
     */
    public function leer(string $html): array
    {
        if (! preg_match('/id="dolar".*?<strong[^>]*>\s*([\d.,]+)\s*<\/strong>/s', $html, $m)) {
            throw new RuntimeException('No se encontró la tasa del dólar en la página del BCV.');
        }

        // Formato venezolano: «888,11830000» o «1.234,56» → 888.1183 / 1234.56
        $tasa = round((float) str_replace(['.', ','], ['', '.'], $m[1]), 4);
        if ($tasa <= 0 || $tasa > 99999999) {
            throw new RuntimeException("La tasa leída del BCV no es válida ({$m[1]}).");
        }

        $fecha = preg_match('/Fecha Valor:.*?content="([^"]+)"/s', $html, $f) ? Carbon::parse($f[1]) : null;

        return ['tasa' => $tasa, 'fecha_valor' => $fecha];
    }

    /**
     * Certificados de confianza para la conexión segura (la verificación nunca se desactiva).
     * resources/certificados/cacert.pem es el paquete de certificados raíz de Mozilla (curl.se/ca)
     * más el certificado intermedio de Sectigo que el servidor del BCV no envía; PHP de XAMPP no
     * trae certificados propios. Sin ese archivo se usan los del sistema.
     */
    private function certificados(): string|bool
    {
        $paquete = resource_path('certificados/cacert.pem');

        return is_file($paquete) ? $paquete : true;
    }

    /** Consulta el BCV y guarda la tasa en la configuración del portal. */
    public function actualizar(): array
    {
        $datos = $this->consultar();

        $guardar = fn (string $clave, string $valor, string $descripcion) => Configuracion::updateOrCreate(['clave' => $clave], compact('valor', 'descripcion'));
        $guardar('tasa_cambio', (string) $datos['tasa'], 'Tasa oficial USD → Bs. (BCV)');
        $guardar('tasa_fuente', 'BCV', 'Origen de la tasa: BCV (automática) o manual');
        $guardar('tasa_fecha_valor', $datos['fecha_valor']?->toDateString() ?? now()->toDateString(), 'Fecha valor publicada por el BCV');

        return $datos;
    }
}
