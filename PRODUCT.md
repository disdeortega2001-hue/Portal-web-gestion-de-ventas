# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Clientes (principal):** productores agrícolas, ganaderos y pequeños comerciantes de fincas y hatos alrededor de Valle de la Pascua (Municipio Leonardo Infante, Guárico). Consultan desde el celular, a menudo con señal débil o datos móviles caros. Su trabajo: saber si hay un repuesto, cuánto cuesta en USD y Bs., y pedir una cotización sin viajar a la sede ni llamar varias veces. (Confirmado por la usuaria.)
- **Personal (2–3 personas):** vendedor/atención al cliente y administrador, en una PC de escritorio en la sede (resolución mínima 1366×768). Trabajo: atender cotizaciones, confirmar ventas, mover inventario, sacar reportes para la gerencia. (Confirmado.)

## Product Purpose

Portal web transaccional de ventas de **Inversiones Rialto 7 C.A.** (antes Inversiones Campagna, fundada en 1963, empresa familiar). Publica el catálogo de maquinaria agrícola y repuestos con existencia y precio en tiempo real, recibe cotizaciones en línea, convierte cotizaciones en pedidos con descuento automático de stock y genera reportes PDF/Excel. Éxito = los indicadores del diagnóstico de la tesis: menos consultas telefónicas, cálculos 100 % exactos, respuesta en minutos y no en 45, reportes sin consolidación manual.

## Positioning

63 años importando directamente desde Brasil repuestos difíciles de conseguir en los canales venezolanos, ahora consultables desde la finca a cualquier hora, con el código de fábrica exacto y la compatibilidad por modelo de tractor.

## Operating Context

- Precios en USD más IVA (16 %) y en Bs. a la tasa oficial del día; la tasa la edita el administrador.
- La búsqueda se hace por código de fábrica, nombre, marca o modelo de maquinaria compatible.
- El canal humano es WhatsApp; el número lo configura la empresa.
- Proyecto de Trabajo de Grado (IUPSM, Ingeniería de Sistemas, autora Genesis Seballo, 2026). Las capturas de pantalla son las Figuras 17–22 del Capítulo IV.

## Capabilities and Constraints

- Stack fijado por la tesis: Laravel (12), PHP 8.2, MySQL, patrón MVC, HTML5/CSS3/JavaScript. La interfaz pasa de Bootstrap 5 a **Tailwind CSS + Alpine.js** (decisión de la usuaria, 26/10/2026).
- Tres perfiles: cliente, vendedor y administrador.
- Funciona también sin JavaScript (formularios clásicos) y debe pesar poco por la señal rural.
- Las 87 pruebas PHPUnit fijan el comportamiento; el rediseño no cambia la lógica.

## Brand Commitments

- Nombre: "Inversiones Rialto 7 C.A." (marca corta: "Rialto 7"). RIF J-50668510-8. Sede: Av. Las Industrias, Edificio Campagna, Valle de la Pascua.
- **Logo oficial (entregado por la usuaria el 26/10/2026):** emblema blanco sobre verde petróleo oscuro (≈ #0B4A3B). Un engranaje contiene el puente de Rialto sobre un recuadro con el monograma «R7» en serif de alto contraste (tipo Didone), «INVERSIONES RIALTO 7 C.A.» en versales serif negritas siguiendo el arco y el RIF «J-50668510-8» debajo. Archivo: `public/img/logo.png` (pendiente de que la usuaria lo copie; no redibujarlo ni inventar variantes).
- Colores de marca vinculantes: ese verde petróleo y el blanco. La serif de alto contraste es la voz tipográfica de la marca.
- Tono: cercano, claro, en español venezolano y sin tecnicismos para el cliente ("tu finca", "cotización").

## Evidence on Hand

- Catálogo de demostración en `database/seeders/DatabaseSeeder.php` (15 productos reales de marcas: John Deere, Massey Ferguson, New Holland, Tatu Marchesan, Baldan, Jacto, Gates, LuK, Pedrollo, Jumil).
- La usuaria dice tener fotos de productos y de la sede, pero no están en el proyecto. **No inventar** fotos, testimonios, cifras de clientes ni premios.

## Product Principles

1. Disponibilidad y precio primero: es lo que el productor viene a buscar.
2. Que funcione con un pulgar y poca señal antes que lucir bien en escritorio.
3. El código de fábrica es la verdad: siempre visible y copiable.
4. El personal trabaja rápido: densidad legible, sin adornos en el panel.
5. Cada número que muestra el sistema lo calcula el servidor.

## Accessibility & Inclusion

Usuarios con alfabetización digital variable y pantallas al sol: contraste alto (WCAG AA como mínimo), objetivos táctiles de 44 px, texto base de 16 px o más y nada que dependa solo del color.
