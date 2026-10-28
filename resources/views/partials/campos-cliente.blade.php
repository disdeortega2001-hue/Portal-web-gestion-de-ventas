{{--
    Campos obligatorios del cliente con validación HTML5 (mismos patrones que ClienteRequest).
    Cédula/RIF en dos partes: la letra se elige y el número abre el teclado numérico del celular.
    ClienteRequest las une en "V-12345678".
    $recordado (opcional): datos guardados en este teléfono con "Recordar mis datos".
    $angosto (opcional): en una columna lateral, un campo por fila para que la cédula no se corte.
    $sinCorreo (opcional): compra sin cuenta, donde el correo no hace falta.
--}}
@php($r = $recordado ?? [])
@php($col = ($angosto ?? false) ? 'sm:col-span-2' : '')
<div class="grid gap-4 sm:grid-cols-2">
    <div class="{{ $col }}">
        <label for="numero_identificacion" class="etiqueta">Cédula o RIF *</label>
        <div @class(['flex gap-2', 'grupo-invalido' => $errors->has('identificacion')])>
            <label for="tipo_identificacion" class="sr-only">Tipo de documento</label>
            <select id="tipo_identificacion" name="tipo_identificacion" class="campo !w-24 shrink-0" required>
                @foreach(['V' => 'V', 'E' => 'E', 'J' => 'J', 'G' => 'G', 'P' => 'P'] as $valor => $texto)
                    <option value="{{ $valor }}" @selected(old('tipo_identificacion', $r['tipo_identificacion'] ?? 'V') === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
            <input type="text" id="numero_identificacion" name="numero_identificacion" value="{{ old('numero_identificacion', $r['numero_identificacion'] ?? '') }}"
                   class="campo cifra @error('identificacion') is-invalid @enderror"
                   required inputmode="numeric" pattern="[0-9]{6,10}" maxlength="10" placeholder="12345678" autocomplete="off"
                   aria-describedby="ayuda-identificacion">
        </div>
        <p @class(['error-campo', '!block' => $errors->has('identificacion')])>@error('identificacion'){{ $message }}@else Solo números, de 6 a 10 dígitos. @enderror</p>
        <p class="ayuda" id="ayuda-identificacion">V: cédula · E: extranjero · J: empresa (RIF con su último dígito, sin guion).</p>
    </div>
    <div class="{{ $col }}">
        <label for="nombre" class="etiqueta">Nombre o razón social *</label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $r['nombre'] ?? '') }}"
               class="campo @error('nombre') is-invalid @enderror" required minlength="3" maxlength="150" autocomplete="name">
        <p class="error-campo">@error('nombre'){{ $message }}@else Escribe tu nombre (mínimo 3 letras). @enderror</p>
    </div>
    <div class="{{ $col }}">
        <label for="telefono" class="etiqueta">Teléfono *</label>
        <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', $r['telefono'] ?? '') }}"
               class="campo cifra @error('telefono') is-invalid @enderror" required pattern="0[0-9]{3}-?[0-9]{7}" placeholder="0414-1234567" inputmode="tel" autocomplete="tel">
        <p class="error-campo">@error('telefono'){{ $message }}@else Formato: 0414-1234567. @enderror</p>
    </div>
    @unless($sinCorreo ?? false)
    <div class="{{ $col }}">
        <label for="correo" class="etiqueta">Correo {{ ($correoObligatorio ?? false) ? '*' : '(opcional)' }}</label>
        <input type="email" id="correo" name="correo" value="{{ old('correo', $r['correo'] ?? '') }}"
               class="campo @error('correo') is-invalid @enderror" maxlength="150" autocomplete="email" @if($correoObligatorio ?? false) required @endif>
        <p class="error-campo">@error('correo'){{ $message }}@else Escribe un correo válido. @enderror</p>
    </div>
    @endunless
    <div class="sm:col-span-2">
        <label for="direccion" class="etiqueta">Dirección *</label>
        <input type="text" id="direccion" name="direccion" value="{{ old('direccion', $r['direccion'] ?? '') }}"
               class="campo @error('direccion') is-invalid @enderror" required minlength="5" maxlength="255" placeholder="Finca, sector, municipio" autocomplete="street-address">
        <p class="error-campo">@error('direccion'){{ $message }}@else Escribe la dirección de entrega. @enderror</p>
    </div>
</div>
