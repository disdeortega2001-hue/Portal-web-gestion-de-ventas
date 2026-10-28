@extends('layouts.app')
@section('titulo', 'Tasa, IVA y pagos')

@section('contenido')
@php
    $valor = fn ($clave, $defecto = '') => old($clave, $configuraciones[$clave]->valor ?? $defecto);
    $actualizado = fn ($clave) => isset($configuraciones[$clave]) ? $configuraciones[$clave]->updated_at->format('d/m/Y h:i a') : 'nunca';
@endphp
<x-encabezado titulo="Tasa, IVA y pagos">
    <span class="block max-w-[70ch]">Los cambios se aplican de inmediato en el catálogo y en las cotizaciones. Las cotizaciones y pedidos ya emitidos conservan la tasa y el IVA con que se crearon.</span>
</x-encabezado>

<form method="POST" action="{{ route('admin.configuracion.update') }}" class="needs-validation max-w-4xl space-y-5" novalidate
      x-data="{ tasa: {{ (float) $valor('tasa_cambio', 0) }}, iva: {{ (float) $valor('porcentaje_iva', 16) }}, f: new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }">
    @csrf @method('PUT')

    <section class="lobulo p-5 lg:p-6" aria-labelledby="t-moneda">
        <h2 id="t-moneda" class="seccion-titulo">Moneda e impuesto</h2>
        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <div>
                <label for="tasa_cambio" class="etiqueta">Tasa oficial USD → Bs. *</label>
                <div class="grupo-campo">
                    <span class="unidad">Bs.</span>
                    <input type="number" id="tasa_cambio" name="tasa_cambio" value="{{ $valor('tasa_cambio') }}" x-model.number="tasa" class="campo cifra @error('tasa_cambio') is-invalid @enderror" required min="0.0001" step="0.0001">
                </div>
                <p @class(['error-campo', '!block' => $errors->has('tasa_cambio')])>@error('tasa_cambio'){{ $message }}@else Mayor que cero, hasta 4 decimales. @enderror</p>
                <p class="ayuda">Por 1 USD. Última actualización: <span class="cifra">{{ $actualizado('tasa_cambio') }}</span>.</p>
            </div>
            <div>
                <label for="porcentaje_iva" class="etiqueta">IVA *</label>
                <div class="grupo-campo">
                    <input type="number" id="porcentaje_iva" name="porcentaje_iva" value="{{ $valor('porcentaje_iva', 16) }}" x-model.number="iva" class="campo cifra @error('porcentaje_iva') is-invalid @enderror" required min="0" max="100" step="0.01">
                    <span class="unidad">%</span>
                </div>
                <p @class(['error-campo', '!block' => $errors->has('porcentaje_iva')])>@error('porcentaje_iva'){{ $message }}@else Entre 0 y 100. @enderror</p>
            </div>
        </div>
        {{-- Ejemplo orientativo para revisar los valores antes de guardar (el cálculo oficial lo hace el servidor) --}}
        <p class="mt-4 rounded-[3px] bg-laguna-clara px-4 py-3 text-sm text-laguna-tinta cifra" id="ejemplo" aria-live="polite"
           x-text="'Ejemplo: un producto de USD 100,00 queda en USD ' + f.format(100 + iva) + ' con IVA, equivalente a Bs. ' + f.format((100 + iva) * (tasa || 0)) + '.'"></p>
    </section>

    <section class="lobulo p-5 lg:p-6" aria-labelledby="t-inv">
        <h2 id="t-inv" class="seccion-titulo">Inventario</h2>
        <label for="umbral_stock_bajo" class="etiqueta mt-4">Alerta de bajo stock *</label>
        <div class="grupo-campo max-w-xs">
            <input type="number" id="umbral_stock_bajo" name="umbral_stock_bajo" value="{{ $valor('umbral_stock_bajo', 5) }}" class="campo cifra @error('umbral_stock_bajo') is-invalid @enderror" required min="0" step="1">
            <span class="unidad">unidades o menos</span>
        </div>
        <p @class(['error-campo', '!block' => $errors->has('umbral_stock_bajo')])>@error('umbral_stock_bajo'){{ $message }}@else Número entero, 0 o más. @enderror</p>
        <p class="ayuda">Se usa en el tablero, en Inventario y en la insignia «Quedan N» del catálogo.</p>
    </section>

    <section class="lobulo p-5 lg:p-6" aria-labelledby="t-desc">
        <h2 id="t-desc" class="seccion-titulo">Descuento por volumen</h2>
        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <div>
                <label for="descuento_porcentaje" class="etiqueta">Descuento *</label>
                <div class="grupo-campo">
                    <input type="number" id="descuento_porcentaje" name="descuento_porcentaje" value="{{ $valor('descuento_porcentaje', 0) }}" class="campo cifra @error('descuento_porcentaje') is-invalid @enderror" required min="0" max="50" step="0.01">
                    <span class="unidad">%</span>
                </div>
                <p @class(['error-campo', '!block' => $errors->has('descuento_porcentaje')])>@error('descuento_porcentaje'){{ $message }}@else Entre 0 y 50. Usa 0 para desactivarlo. @enderror</p>
            </div>
            <div>
                <label for="descuento_monto_minimo" class="etiqueta">Cuando el subtotal alcance *</label>
                <div class="grupo-campo">
                    <span class="unidad">USD</span>
                    <input type="number" id="descuento_monto_minimo" name="descuento_monto_minimo" value="{{ $valor('descuento_monto_minimo', 0) }}" class="campo cifra @error('descuento_monto_minimo') is-invalid @enderror" required min="0" step="0.01">
                </div>
                <p @class(['error-campo', '!block' => $errors->has('descuento_monto_minimo')])>@error('descuento_monto_minimo'){{ $message }}@else 0 o más. @enderror</p>
            </div>
        </div>
    </section>

    <section class="lobulo p-5 lg:p-6" aria-labelledby="t-contacto">
        <h2 id="t-contacto" class="seccion-titulo">Contacto con los clientes</h2>
        <label for="telefono_whatsapp" class="etiqueta mt-4">WhatsApp de la empresa</label>
        <input type="tel" id="telefono_whatsapp" name="telefono_whatsapp" value="{{ $valor('telefono_whatsapp') }}" class="campo max-w-xs cifra @error('telefono_whatsapp') is-invalid @enderror" placeholder="0414-1234567" inputmode="tel" pattern="(\+?58|0)[\s\-]?\d{3}[\s\-]?\d{7}">
        <p class="error-campo">@error('telefono_whatsapp'){{ $message }}@else Escribe el número completo, por ejemplo 0414-1234567. @enderror</p>
        <p class="ayuda max-w-[65ch]">Activa el botón «WhatsApp» de la cabecera, la consulta de productos agotados y el contacto al terminar una cotización. Déjalo vacío para ocultarlos.</p>
    </section>

    <section class="lobulo p-5 lg:p-6" aria-labelledby="t-pagos">
        <h2 id="t-pagos" class="seccion-titulo">Métodos de pago</h2>
        <p class="ayuda max-w-[70ch]">Los métodos activos se ofrecen al comprar. Los datos de las cuentas son opcionales: si están vacíos, el cliente ve que se los envías por WhatsApp.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @foreach(\App\Support\MetodosPago::METODOS as $metodo => $definicion)
                <fieldset class="rounded-[8px] border border-linea p-4">
                    <legend class="px-1">
                        <label class="flex cursor-pointer items-center gap-2 font-extrabold uppercase">
                            <input type="checkbox" name="{{ $metodo }}_activo" value="1" @checked(old("{$metodo}_activo", $configuraciones["{$metodo}_activo"]->valor ?? '1') === '1')>
                            {{ $definicion['nombre'] }}
                        </label>
                    </legend>
                    <p class="apoyo text-sm">{{ $definicion['descripcion'] }}</p>
                    @foreach($definicion['campos'] as $clave => $etiqueta)
                        <label for="{{ $clave }}" class="etiqueta mt-3">{{ $etiqueta }}</label>
                        <input type="text" id="{{ $clave }}" name="{{ $clave }}" value="{{ $valor($clave) }}" class="campo @error($clave) is-invalid @enderror" maxlength="120" placeholder="Por definir">
                    @endforeach
                </fieldset>
            @endforeach
        </div>
    </section>

    <button class="btn btn-sol btn-grande">Guardar configuración</button>
</form>
@endsection
