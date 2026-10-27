{{-- Aviso para una cuenta cuya cédula ya tenía historial y espera la llamada del vendedor --}}
@if($enVerificacion ?? false)
    <div class="aviso aviso-info mb-6" role="status">
        <x-icono nombre="telefono" class="mt-0.5" />
        <div>
            <strong>Estamos verificando tu cuenta.</strong>
            Tu cédula ya tenía cotizaciones con nosotros. Un vendedor te llamará al teléfono que nos diste en esa ocasión para confirmar que eres tú; después verás aquí todo tu historial. Mientras tanto puedes cotizar normalmente.
        </div>
    </div>
@endif
