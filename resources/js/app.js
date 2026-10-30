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
    aviso.dataset.espera = esError ? 6000 : 4000;
    programarCierre(aviso);
}

// El aviso se cierra solo, pero no mientras el dedo, el cursor o el foco están sobre él
function programarCierre(aviso) {
    clearTimeout(temporizador);
    temporizador = setTimeout(() => aviso.removeAttribute('data-visible'), Number(aviso.dataset.espera || 4000));
}
const avisoFlotante = document.getElementById('aviso');
if (avisoFlotante) {
    ['pointerenter', 'pointerdown', 'focusin'].forEach((ev) => avisoFlotante.addEventListener(ev, () => clearTimeout(temporizador)));
    ['pointerleave', 'focusout'].forEach((ev) => avisoFlotante.addEventListener(ev, () => {
        if (avisoFlotante.hasAttribute('data-visible') && !avisoFlotante.contains(document.activeElement)) programarCierre(avisoFlotante);
    }));
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

// Tablas en el teléfono: cada celda lleva el nombre de su columna para mostrarse como ficha (ver .tabla en app.css)
document.querySelectorAll('table.tabla').forEach((tabla) => {
    const titulos = [...tabla.querySelectorAll('thead th')].map((th) => th.textContent.trim());
    tabla.querySelectorAll('tbody tr').forEach((fila) => {
        [...fila.children].forEach((celda, i) => {
            if (!celda.hasAttribute('colspan') && titulos[i]) celda.dataset.etiqueta = titulos[i];
        });
    });
});

// Confirmación antes de acciones irreversibles: <form data-confirmar="¿…?">. Va primero: si se cancela, nada más corre.
document.addEventListener('submit', (e) => {
    const mensaje = e.target.dataset?.confirmar;
    if (mensaje && !window.confirm(mensaje)) { e.preventDefault(); e.stopImmediatePropagation(); }
});

// Enlaces para copiar (<input data-seleccionar>): al tocarlos queda todo seleccionado
document.addEventListener('focusin', (e) => { if (e.target.matches?.('[data-seleccionar]')) e.target.select(); });

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

/*
 * Movimiento al desplazarse: lo marcado con data-revelar entra subiendo la primera vez que aparece,
 * y los números con data-contar cuentan desde cero. Sin IntersectionObserver o con «reducir movimiento»
 * todo se muestra de una vez (la clase .js del <html> es la que oculta al inicio).
 */
const sinMovimiento = matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window);

function contar(el) {
    const final = parseFloat(el.dataset.contar);
    if (Number.isNaN(final) || sinMovimiento) return;
    const inicio = performance.now(), duracion = 1400;
    const paso = (t) => {
        const avance = Math.min(1, (t - inicio) / duracion);
        el.textContent = Math.round(final * (1 - Math.pow(1 - avance, 3))).toLocaleString('es-VE');
        if (avance < 1) requestAnimationFrame(paso);
    };
    requestAnimationFrame(paso);
}

if (sinMovimiento) {
    document.querySelectorAll('[data-revelar]').forEach((el) => el.classList.add('visible'));
} else {
    const observador = new IntersectionObserver((entradas) => {
        entradas.forEach((entrada) => {
            if (!entrada.isIntersecting) return;
            entrada.target.classList.add('visible');
            entrada.target.querySelectorAll('[data-contar]').forEach(contar);
            if (entrada.target.matches('[data-contar]')) contar(entrada.target);
            observador.unobserve(entrada.target);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('[data-revelar], [data-contar]').forEach((el) => observador.observe(el));
}

/*
 * Hoja inferior que se arrastra (panel de filtros en el celular). Sigue al dedo 1:1 desde donde se agarró,
 * se resiste al subir más allá de su lugar y, al soltar, proyecta el impulso: si el gesto la lleva más allá
 * de la mitad, se cierra continuando a la velocidad del dedo; si no, vuelve a su sitio.
 */
const proyectar = (v, d = 0.998) => (v / 1000) * d / (1 - d);
const elastico = (x, alto, c = 0.55) => (x * alto * c) / (alto + c * Math.abs(x));

document.querySelectorAll('[data-hoja]').forEach((hoja) => {
    let inicioY = null, desde = 0, historial = [];

    // La clase abre/cierra con la propiedad translate de Tailwind 4; el dedo mueve con transform encima.
    // La posición en pantalla es la suma de las dos.
    const posicionActual = () => {
        const estilo = getComputedStyle(hoja);
        const y = (estilo.translate.split(' ')[1] || '0').trim();
        const desdeClase = y.endsWith('%') ? (parseFloat(y) / 100) * hoja.offsetHeight : parseFloat(y) || 0;
        return new DOMMatrixReadOnly(estilo.transform).m42 + desdeClase;
    };

    hoja.addEventListener('pointerdown', (e) => {
        if (!hoja.hasAttribute('data-abierto') || !e.target.closest('[data-arrastre]') || e.target.closest('button, a, input, select') || matchMedia('(min-width: 64rem)').matches) return;
        hoja.setPointerCapture(e.pointerId);
        desde = posicionActual();               // si estaba en movimiento, se agarra donde está
        inicioY = e.clientY;
        historial = [{ y: e.clientY, t: e.timeStamp }];
        hoja.style.transition = 'none';
        hoja.style.transform = `translateY(${desde}px)`;
    });

    hoja.addEventListener('pointermove', (e) => {
        if (inicioY === null) return;
        const dy = desde + e.clientY - inicioY;
        hoja.style.transform = `translateY(${dy < 0 ? elastico(dy, hoja.offsetHeight) : dy}px)`;
        historial.push({ y: e.clientY, t: e.timeStamp });
        if (historial.length > 5) historial.shift();
    });

    const soltar = (e) => {
        if (inicioY === null) return;
        const dy = Math.max(0, desde + e.clientY - inicioY);
        const [a, b] = [historial[0], historial[historial.length - 1]];
        const velocidad = b.t > a.t ? ((b.y - a.y) / (b.t - a.t)) * 1000 : 0;   // px/s, positiva hacia abajo
        const alto = hoja.offsetHeight;
        const cerrar = dy + proyectar(velocidad) > alto / 2;
        inicioY = null;

        // Continúa a la velocidad del dedo: la duración sale de la distancia que falta y la velocidad al soltar
        const falta = cerrar ? alto - dy : dy;
        const duracion = Math.min(0.35, Math.max(0.16, Math.abs(velocidad) > 50 ? (falta / Math.abs(velocidad)) * 1.6 : 0.3));
        // transform vuelve a 0 y, si se cierra, translate va a 100 % con la misma curva: juntas trazan un solo recorrido
        const curva = `${duracion}s cubic-bezier(0.2, 0.8, 0.3, 1)`;
        hoja.style.transition = `transform ${curva}, translate ${curva}`;
        hoja.style.transform = '';
        if (cerrar) window.dispatchEvent(new CustomEvent('cerrar-hoja'));
        setTimeout(() => { hoja.style.transition = ''; }, duracion * 1000 + 50);
    };
    hoja.addEventListener('pointerup', soltar);
    hoja.addEventListener('pointercancel', soltar);
});

// Portada: las fotos se relevan solas cada 6 s con zoom lento; los puntos llevan la barra de avance
Alpine.data('portada', (total, duracion = 6000) => ({
    actual: 0, total, duracion, temporizador: null,
    init() {
        if (sinMovimiento || total < 2) return;
        this.programar();
        document.addEventListener('visibilitychange', () => (document.hidden ? clearTimeout(this.temporizador) : this.programar()));
    },
    programar() { clearTimeout(this.temporizador); this.temporizador = setTimeout(() => this.ir(this.actual + 1), this.duracion); },
    ir(i) { this.actual = (i + this.total) % this.total; if (!sinMovimiento) this.programar(); },
}));

window.Alpine = Alpine;
Alpine.start();
