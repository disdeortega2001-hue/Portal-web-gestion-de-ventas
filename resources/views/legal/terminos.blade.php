@extends('layouts.app')
@section('titulo', 'Términos de venta')
@section('descripcion', 'Cómo funcionan los precios, las cotizaciones y los pedidos en el portal de Inversiones Rialto 7.')

@php
    $iva = rtrim(rtrim(number_format(\App\Models\Configuracion::porcentajeIva(), 2, ',', ''), '0'), ',');
    $igtf = rtrim(rtrim(number_format(\App\Models\Configuracion::porcentajeIgtf(), 2, ',', ''), '0'), ',');
@endphp

@section('cabecera')
    <h1 class="titular text-5xl sm:text-6xl">Términos de venta</h1>
    <p class="mt-3 max-w-2xl text-lg text-white/80">Cómo funcionan los precios, las cotizaciones y los pedidos.</p>
@endsection

@section('contenido')
@component('legal._documento', ['secciones' => [
    'precios' => 'Precios',
    'cotizaciones' => 'Cotizaciones',
    'pedidos' => 'Pedidos y existencia',
    'pago' => 'Pago y entrega',
    'validez' => 'Validez de los documentos',
]])
    <p>Estos términos aplican a las cotizaciones y pedidos hechos a <strong>Inversiones Rialto 7 C.A.</strong> (RIF J-50668510-8) a través de este portal.</p>

    <h2 id="precios">Precios</h2>
    <ul>
        <li>Los precios se publican en dólares estadounidenses (USD) sin impuestos.</li>
        <li>Si pagas en dólares (Zelle o efectivo) se cobra el IGTF, hoy de {{ $igtf }} %. Si pagas en bolívares (Pago Móvil, transferencia o punto de venta) se cobra el IVA, hoy de {{ $iva }} %.</li>
        <li>El monto en bolívares se calcula a la tasa oficial del Banco Central de Venezuela (BCV) del día.</li>
        <li>Todos los cálculos los hace el sistema; lo que ves en pantalla es lo que figura en el documento.</li>
    </ul>

    <h2 id="cotizaciones">Cotizaciones</h2>
    <ul>
        <li>Una cotización es una solicitud: no es una factura ni reserva la mercancía.</li>
        <li>Cada cotización conserva los precios, el impuesto (IVA o IGTF) y la tasa con que se emitió, aunque luego cambien.</li>
        <li>Un vendedor revisa la existencia y te contacta por teléfono o WhatsApp para confirmarla.</li>
    </ul>

    <h2 id="pedidos">Pedidos y existencia</h2>
    <ul>
        <li>La existencia publicada es la real del momento. Se descuenta cuando el vendedor confirma la venta.</li>
        <li>Si al confirmar ya no hay unidades suficientes de un producto, el sistema no procesa el pedido y el vendedor te avisa.</li>
    </ul>

    <h2 id="pago">Pago y entrega</h2>
    <p>El portal no cobra en línea: al comprar eliges Pago Móvil, transferencia, Zelle, efectivo o punto de venta. Los pagos a distancia los reportas con su referencia y el personal los verifica antes de confirmar la venta. La entrega o el retiro en la sede y las condiciones de garantía de cada producto te los indica el vendedor al confirmar tu pedido.</p>

    <h2 id="validez">Validez de los documentos</h2>
    <p>Las cotizaciones y pedidos generados por el portal son mensajes de datos con la misma eficacia probatoria que los documentos escritos, según la Ley sobre Mensajes de Datos y Firmas Electrónicas (2001).</p>
    <p><a href="{{ route('contacto') }}" class="enlace">¿Dudas? Escríbenos</a></p>
@endcomponent
@endsection
