import Alpine from 'alpinejs';

/*
 * Comportamiento común del portal. Todo funciona igual sin JavaScript (formularios clásicos);
 * esto solo evita recargas, dobles envíos y confirma lo irreversible.
 */
const TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;
const URL_COTIZACION = document.body.dataset.urlCotizacion;
let temporizador;

function escapar(texto) {
    const d = document.createElement('div');
    d.textContent = texto ?? '';
    return d.innerHTML;
}

function mostrarAviso(html, esError) {
    const aviso = document.getElementById('aviso');
    if (!aviso) return;
    aviso.querySelector('[data-texto]').innerHTML = html;
    aviso.dataset.tipo = esError ? 'error' : 'exito';
    aviso.setAttribute('data-visible', '');
    clearTimeout(temporizador);
    temporizador = setTimeout(() => aviso.removeAttribute('data-visible'), esError ? 6000 : 4000);
}

// Bloquea un botón mientras espera al servidor: evita el doble envío con señal lenta
function bloquear(boton) {
    if (boton.dataset.textoOriginal === undefined) boton.dataset.textoOriginal = boton.innerHTML;
    boton.disabled = true;
    boton.setAttribute('aria-busy', 'true');
    boton.innerHTML = '<span class="girando" aria-hidden="true"></span> ' + escapar(boton.dataset.cargando || 'Enviando…');
}
function desbloquear(boton) {
    if (boton.dataset.textoOriginal !== undefined) {
        boton.innerHTML = boton.dataset.textoOriginal;
        delete boton.dataset.textoOriginal;
    }
    boton.disabled = false;
    boton.removeAttribute('aria-busy');
}

function actualizarContador(items) {
    document.querySelectorAll('[data-carrito-contador]').forEach((b) => { b.textContent = items; b.hidden = items === 0; });
    document.querySelectorAll('[data-carrito-enlace]').forEach((a) => {
        a.setAttribute('aria-label', 'Mi cotización, ' + items + (items === 1 ? ' producto' : ' productos'));
    });
}

// Confirmación antes de acciones irreversibles: <form data-confirmar="¿…?">. Va primero: si se cancela, nada más corre.
document.addEventListener('submit', (e) => {
    const mensaje = e.target.dataset?.confirmar;
    if (mensaje && !window.confirm(mensaje)) { e.preventDefault(); e.stopImmediatePropagation(); }
});

// Validación en vivo (HTML5). El servidor vuelve a validar todo.
document.querySelectorAll('form.needs-validation').forEach((form) => {
    form.querySelectorAll('input, select, textarea').forEach((campo) => {
        campo.addEventListener('input', () => {
            campo.classList.toggle('is-invalid', !campo.checkValidity());
            campo.classList.toggle('is-valid', campo.checkValidity() && campo.value !== '');
        });
    });
    form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); form.querySelector(':invalid')?.focus(); }
        form.classList.add('was-validated');
    });
});

// Agregar a la cotización sin recargar. Sin JavaScript, el formulario vuelve a la misma tarjeta (#producto-ID).
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (e.defaultPrevented || !form.matches('[data-agregar]')) return;
    e.preventDefault();
    const boton = form.querySelector('button');
    bloquear(boton);
    try {
        const r = await fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        });
        if (r.status === 419) { location.reload(); return; }   // La sesión venció: se recarga con un token nuevo
        const datos = await r.json().catch(() => ({}));
        if (r.ok) {
            actualizarContador(datos.items);
            const texto = datos.codigo
                ? 'Agregado: <span class="whitespace-nowrap font-bold">' + escapar(datos.codigo) + '</span>. Llevas ' + datos.llevas + '.'
                : escapar(datos.mensaje);
            mostrarAviso('<span>' + texto + '</span> <a class="font-bold underline" href="' + escapar(datos.enlace || URL_COTIZACION) + '">Cambiar cantidad</a>');
        } else {
            const error = datos.errors ? Object.values(datos.errors)[0][0] : datos.message;
            mostrarAviso(escapar(error || 'No se pudo agregar. Intenta de nuevo.'), true);
        }
    } catch {
        mostrarAviso('Sin conexión. Revisa la señal e intenta de nuevo.', true);
    } finally {
        desbloquear(boton);
    }
});

// Cualquier otro formulario: bloquea el botón que lo envió (un instante después, cuando el navegador ya tomó su valor)
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (e.defaultPrevented || form.hasAttribute('data-sin-bloqueo')) return;
    const boton = e.submitter || form.querySelector('button[type="submit"], button:not([type])');
    if (!boton) return;
    setTimeout(() => bloquear(boton), 0);
    setTimeout(() => desbloquear(boton), 8000);   // Las descargas (PDF, Excel) no cambian de página
});

// Al volver con "Atrás", ningún botón queda bloqueado
window.addEventListener('pageshow', (e) => {
    if (e.persisted) document.querySelectorAll('[aria-busy="true"]').forEach(desbloquear);
});

window.Alpine = Alpine;
Alpine.start();
