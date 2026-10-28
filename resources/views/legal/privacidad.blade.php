@extends('layouts.app')
@section('titulo', 'Política de privacidad')
@section('descripcion', 'Qué datos guarda el portal de Inversiones Rialto 7, para qué los usa y cómo puedes consultarlos, corregirlos o pedir que se borren.')

@section('cabecera')
    <h1 class="titular text-5xl sm:text-6xl">Política de privacidad</h1>
    <p class="mt-3 max-w-2xl text-lg text-white/80">Qué datos guardamos, para qué y cómo puedes consultarlos o corregirlos.</p>
@endsection

@section('contenido')
@component('legal._documento', ['secciones' => [
    'responsable' => 'Quién es responsable',
    'datos' => 'Qué datos guardamos',
    'uso' => 'Para qué los usamos',
    'cookies' => 'Cookies',
    'terceros' => 'Con quién se comparten',
    'seguridad' => 'Cómo los protegemos',
    'derechos' => 'Tus derechos',
]])
    <p>Esta política explica cómo <strong>Inversiones Rialto 7 C.A.</strong> trata los datos personales que recibe a través de este portal. Al enviar una cotización o crear una cuenta aceptas lo que aquí se describe.</p>

    <h2 id="responsable">Quién es responsable</h2>
    <p>Inversiones Rialto 7 C.A., RIF J-50668510-8, con sede en Av. Las Industrias, Edificio Campagna, Valle de la Pascua, estado Guárico, Venezuela.</p>

    <h2 id="datos">Qué datos guardamos</h2>
    <ul>
        <li><strong>Al cotizar o comprar:</strong> cédula o RIF, nombre o razón social, teléfono, dirección y, si lo indicas, correo electrónico.</li>
        <li><strong>Si creas una cuenta:</strong> además, tu correo y tu contraseña, que se guarda cifrada; nadie en la empresa puede leerla.</li>
        <li><strong>De tus operaciones:</strong> los productos, cantidades, precios y fechas de tus cotizaciones y pedidos.</li>
    </ul>
    <p>No pedimos datos bancarios ni de tarjetas: el portal no procesa pagos.</p>

    <h2 id="uso">Para qué los usamos</h2>
    <ul>
        <li>Preparar y responder tus cotizaciones, confirmar la existencia y contactarte por teléfono o WhatsApp.</li>
        <li>Registrar tus pedidos y llevar el historial que ves en «Mi cuenta».</li>
        <li>Cumplir con los registros comerciales y fiscales de la empresa.</li>
    </ul>
    <p>No usamos tus datos para publicidad de terceros ni los vendemos.</p>

    <h2 id="cookies">Cookies</h2>
    <p>El portal usa solo cookies necesarias para funcionar. No usa cookies de publicidad ni de medición de terceros.</p>
    <ul>
        <li><strong>Sesión:</strong> mantiene tu sesión y tu cotización mientras navegas.</li>
        <li><strong>Respaldo de la cotización:</strong> guarda, cifrada, la lista de productos que armaste durante 30 días, para que no la pierdas si vuelves otro día.</li>
        <li><strong>Recordar mis datos (opcional):</strong> solo si marcas esa casilla, guarda cifrados tus datos de contacto en ese teléfono durante 90 días. Puedes borrarlos cuando quieras con «Borrar mis datos».</li>
    </ul>

    <h2 id="terceros">Con quién se comparten</h2>
    <p>Tus datos solo los ve el personal de ventas y la administración de la empresa. Cuando eliges escribirnos por WhatsApp, esa conversación se rige por las condiciones de WhatsApp. El enlace «Cómo llegar» abre Google Maps.</p>

    <h2 id="seguridad">Cómo los protegemos</h2>
    <p>El portal funciona con conexión cifrada (HTTPS), contraseñas cifradas, acceso por perfil para el personal y respaldos diarios de la base de datos. Las cotizaciones que envías sin cuenta solo se pueden abrir con su enlace personal.</p>

    <h2 id="derechos">Tus derechos</h2>
    <p>Conforme al artículo 28 de la Constitución de la República Bolivariana de Venezuela, puedes conocer qué datos tenemos sobre ti, pedir que se corrijan y solicitar que se eliminen cuando ya no sean necesarios para fines comerciales o legales. Escríbenos por WhatsApp o visítanos en la sede; si tienes cuenta, también puedes ver tus cotizaciones y pedidos en «Mi cuenta».</p>
    <p><a href="{{ route('contacto') }}" class="enlace">Ir a Contacto</a></p>
@endcomponent
@endsection
