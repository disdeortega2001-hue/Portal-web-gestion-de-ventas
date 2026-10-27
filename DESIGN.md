---
name: Rialto 7
description: Portal de ventas de Inversiones Rialto 7 C.A. como mapa-guía del llano; tierra verde, agua crema y cápsulas de color plano.
colors:
  tierra: "#0b4a3b"
  tierra-oscura: "#073328"
  tierra-clara: "#dfeae3"
  sabana: "#8ed462"
  sabana-clara: "#e4f3d6"
  agua: "#f5f1e4"
  agua-honda: "#ebe5d2"
  tinta: "#2c2e2a"
  tinta-suave: "#585c53"
  linea: "#d8d0b8"
  sol: "#ffd23f"
  sol-hover: "#ffc81a"
  sol-clara: "#fff1bf"
  coral: "#ff6b5a"
  coral-clara: "#ffe1dc"
  coral-tinta: "#a8321f"
  laguna: "#4fa6e3"
  laguna-clara: "#dcecf9"
  laguna-tinta: "#175a87"
  lila: "#ebc1ff"
  lila-clara: "#f6e6ff"
  white: "#ffffff"
typography:
  display:
    fontFamily: "Hanken Grotesk Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "4rem"
    fontWeight: 800
    lineHeight: 0.98
    letterSpacing: "-0.035em"
  headline:
    fontFamily: "Hanken Grotesk Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "2.75rem"
    fontWeight: 800
    lineHeight: 0.98
    letterSpacing: "-0.035em"
  title:
    fontFamily: "Hanken Grotesk Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 800
    letterSpacing: "-0.02em"
  body:
    fontFamily: "Hanken Grotesk Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
    fontFeature: "\"ss01\""
  label:
    fontFamily: "Hanken Grotesk Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 700
  code:
    fontFamily: "Hanken Grotesk Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 700
    letterSpacing: "0.06em"
    fontFeature: "\"tnum\", \"lnum\""
  table-head:
    fontFamily: "Hanken Grotesk Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    letterSpacing: "0.08em"
rounded:
  capsula: "9999px"
  lobulo: "1.375rem"
  hoja: "1.75rem"
  campo: "1rem"
  menu-item: "0.75rem"
  logo: "0.75rem"
spacing:
  gutter-movil: "16px"
  gutter-sm: "24px"
  gutter-lg: "32px"
  gap-sm: "8px"
  gap-md: "16px"
  gap-lg: "24px"
  gap-xl: "32px"
  contenido-cliente: "1320px"
  contenido-personal: "1400px"
  riel: "256px"
components:
  button-sol:
    backgroundColor: "{colors.sol}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.capsula}"
    padding: "0 20px"
    height: "44px"
  button-sol-hover:
    backgroundColor: "{colors.sol-hover}"
  button-tierra:
    backgroundColor: "{colors.tierra}"
    textColor: "{colors.white}"
    rounded: "{rounded.capsula}"
    padding: "0 20px"
    height: "44px"
  button-tierra-hover:
    backgroundColor: "{colors.tierra-oscura}"
  button-linea:
    backgroundColor: "transparent"
    textColor: "{colors.tinta}"
    rounded: "{rounded.capsula}"
    padding: "0 20px"
    height: "44px"
  button-linea-hover:
    backgroundColor: "{colors.tinta}"
    textColor: "{colors.agua}"
  button-grande:
    rounded: "{rounded.capsula}"
    padding: "0 24px"
    height: "52px"
  button-mini:
    rounded: "{rounded.capsula}"
    padding: "0 14px"
    height: "36px"
  capsula:
    backgroundColor: "{colors.white}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.capsula}"
    padding: "0 6px 0 16px"
    height: "40px"
  capsula-activa:
    backgroundColor: "{colors.tierra}"
    textColor: "{colors.white}"
    rounded: "{rounded.capsula}"
    padding: "0 16px 0 6px"
  campo:
    backgroundColor: "{colors.white}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.campo}"
    padding: "0 16px"
    height: "48px"
  lobulo:
    backgroundColor: "{colors.white}"
    rounded: "{rounded.lobulo}"
  lobulo-tierra:
    backgroundColor: "{colors.tierra}"
    textColor: "{colors.white}"
    rounded: "{rounded.lobulo}"
  estado-disponible:
    backgroundColor: "{colors.sabana-clara}"
    textColor: "{colors.tierra}"
    rounded: "{rounded.capsula}"
    padding: "4px 12px"
  estado-bajo:
    backgroundColor: "{colors.sol-clara}"
    textColor: "#6b4b00"
    rounded: "{rounded.capsula}"
    padding: "4px 12px"
  estado-agotado:
    backgroundColor: "{colors.coral-clara}"
    textColor: "{colors.coral-tinta}"
    rounded: "{rounded.capsula}"
    padding: "4px 12px"
  estado-pendiente:
    backgroundColor: "{colors.lila-clara}"
    textColor: "#5b2c78"
    rounded: "{rounded.capsula}"
    padding: "4px 12px"
  estado-info:
    backgroundColor: "{colors.laguna-clara}"
    textColor: "{colors.laguna-tinta}"
    rounded: "{rounded.capsula}"
    padding: "4px 12px"
  riel-item-activo:
    backgroundColor: "{colors.sol}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.capsula}"
    padding: "0 16px"
    height: "44px"
---

# Design System: Rialto 7

<!-- Documentado desde el código construido (resources/css/app.css, layouts/app.blade.php, componentes y vistas) el 26/10/2026, tras la revisión final con disposición «ship». Fuente de tokens: el bloque @theme de app.css (Tailwind CSS 4). -->

## Overview

**Creative North Star: "El mapa-guía del llano"**

El portal se lee como el mapa de un parque o de una finca: zonas de color plano (la **tierra** verde petróleo de la marca, el **agua** crema del fondo, manchas de **sabana**) separadas por **costas orgánicas** en lugar de líneas rectas. Sobre ese mapa, los controles y los estados son **cápsulas** de color plano: la cápsula **sol** es siempre la acción principal; coral, laguna y lila nombran agotado/peligro, información y pendiente. No hay degradados ni sombras difusas: la profundidad sale del contraste de zonas y de anillos crema que recortan una pieza sobre otra.

Hay dos densidades dentro del mismo mundo. El cliente (productor en el celular, al sol, con poca señal) recibe una cabecera de tierra que termina en costa, con la cápsula «Mi cotización» clavada en el cabo de la derecha, y tarjetas con la imagen recortada en lóbulo. El personal (PC de 1366 px) trabaja en un riel verde fijo a la izquierda cuyo borde derecho también es costa, con tablas densas, reglas de 1 px y cifras tabulares. Una sola familia tipográfica, Hanken Grotesk, hace toda la jerarquía con peso y tamaño.

El movimiento es breve y físico: las cápsulas ceden al pulsarlas (escala 0.96) y vuelven con un rebote deliberado; las hojas y el riel entran con una salida suave. Todo se apaga con `prefers-reduced-motion`.

**Key Characteristics:**
- Color plano por zonas; costas SVG en lugar de bordes rectos entre tierra y agua.
- Cápsulas (radio completo) para botones, filtros, estados, paginación e ítems de menú.
- Lóbulos (22 px) para superficies; imagen de producto recortada con la forma `#forma-lobulo`.
- Una sola familia, Hanken Grotesk Variable; titulares extrabold compactos (interlineado 0.98, tracking −0.035em).
- Cifras tabulares en todo precio, cantidad y código.
- Objetivos táctiles de 44 px como mínimo; texto base de 16 px.

## Colors

Paleta de mapa: dos zonas grandes (tierra y agua), una zona secundaria (sabana) y cuatro cápsulas de señal, cada una con su versión clara de fondo y, cuando hace falta, una tinta oscura para texto legible.

### Primary
- **Tierra verde petróleo** (#0b4a3b): el verde de la marca. Cabecera del cliente, riel del personal, paneles de indicadores (`lobulo-tierra`), botón `btn-tierra`, cápsula de filtro activa, página actual de la paginación, barras del gráfico de ventas, `theme-color` del navegador, casilla de verificación y cursor de texto.
- **Tierra oscura** (#073328): hover de `btn-tierra` y pie de página del cliente.
- **Tierra clara** (#dfeae3): tinte disponible para fondos suaves de la zona tierra.

### Secondary
- **Sol** (#ffd23f): la acción principal y nada más: `btn-sol` («Agregar», «Buscar», «Mi cotización», «WhatsApp»), ítem activo del riel, chip de conteo de la cápsula activa, selección de texto, barras del gráfico de más vendidos y la pastilla «con más de 24 h». Hover #ffc81a.
- **Sabana** (#8ed462): zonas secundarias (tarjeta «Conversión del mes», lóbulo decorativo al 30 % detrás de las categorías), etiqueta de sección «Administración» del riel y borde de campo válido.

### Tertiary (cápsulas de señal)
- **Coral** (#ff6b5a): agotado y peligro; borde y halo de campo inválido; cifra de agotados sobre tierra (texto grande). Su tinta de texto es **coral tinta** (#a8321f) y su fondo **coral clara** (#ffe1dc).
- **Laguna** (#4fa6e3): información y **anillo de foco** universal (3 px). Texto en **laguna tinta** (#175a87) sobre **laguna clara** (#dcecf9).
- **Lila** (#ebc1ff): pendiente. Fondo **lila clara** (#f6e6ff), texto #5b2c78.

### Neutral
- **Agua crema** (#f5f1e4): fondo de página; anillo de 4 px que recorta la cápsula «Mi cotización» y el aviso flotante; fondo de la hoja inferior de filtros; hover de filas de tabla.
- **Agua honda** (#ebe5d2): pista de la barra de desplazamiento, fondo de unidades (Bs., %, USD) pegadas a campos, estado vacío del catálogo.
- **Tinta** (#2c2e2a): texto principal, borde de `btn-linea`, regla de 2 px bajo encabezados de tabla, eje y tooltip de los gráficos, aviso flotante, velo de hojas al 50 %.
- **Tinta suave** (#585c53): texto de apoyo (`apoyo`), encabezados de tabla, ticks de gráficos.
- **Línea** (#d8d0b8): bordes de lóbulos, campos y cápsulas; reglas de tabla a 1 px; separador punteado de la tarjeta.
- **Blanco** (#ffffff): superficie de tarjetas, campos y cápsulas en reposo; texto sobre tierra.

### Roles de estado (escala fija)
| Estado | Clases | Fondo | Texto |
|---|---|---|---|
| Disponible / confirmado / convertida / activo | `estado-disponible`, `-confirmado`, `-convertida`, `-activo` | sabana clara | tierra |
| Bajo stock | `estado-bajo` | sol clara | #6b4b00 |
| Agotado / anulado / rechazada / inactivo | `estado-agotado`, `-anulado`, `-rechazada`, `-inactivo` | coral clara | coral tinta |
| Pendiente | `estado-pendiente` | lila clara | #5b2c78 |
| Información | `estado-info` | laguna clara | laguna tinta |

Avisos (`aviso-*`) usan los mismos fondos claros con tintas más oscuras: éxito tierra oscura, error #6e1f12, info #0f3f61, atención #4d3600.

### Named Rules
**The Sol Is the Action Rule.** El amarillo sol marca la acción principal o el lugar actual (ítem activo del riel, conteo activo). Nunca decora; si dos cosas en la misma vista piden sol, una de ellas no es la acción principal.

**The Flat Zones Rule.** Color plano siempre: ni degradados ni sombras difusas. Las zonas se separan por costa o por contraste de color, no por sombra.

**The Never Color Alone Rule.** Cada estado lleva texto («Agotado», «Quedan 3», «Bajo stock: 2») y un punto `::before` del color de la tinta; el color refuerza, nunca es el único portador.

**The Light Fill, Dark Ink Rule.** En las cápsulas de estado el fondo es la versión clara y el texto la tinta oscura de la misma familia; coral, laguna y lila puros no llevan texto pequeño encima.

## Typography

**Display Font:** Hanken Grotesk Variable (con ui-sans-serif, system-ui, sans-serif)
**Body Font:** Hanken Grotesk Variable (misma familia)
**Label/Mono Font:** la misma familia con `tabular-nums lining-nums` (utilidad `cifra`); no hay monoespaciada.

**Character:** una grotesca cálida y redonda que en extrabold compacto se vuelve rotunda como un letrero de campo, y en regular se lee cómoda al sol. `font-feature-settings: "ss01"` en todo el cuerpo.

### Hierarchy
- **Display** (800, `2.25rem` → `3rem` sm → `4rem` lg, interlineado 0.98, tracking −0.035em, clase `titular`): titular de la cabecera del catálogo, en blanco sobre tierra, `text-wrap: balance`.
- **Headline** (800, `2.25rem` → `2.75rem` lg, 0.98, `titular`): título de página del personal (`x-encabezado`); también cifras grandes de indicadores (`2rem`) y la conversión (`3.75rem`).
- **Title** (800, `1.25rem`, tracking −0.02em, `seccion-titulo`): títulos de sección dentro de lóbulos. Título de tarjeta: 700, `1.0625rem`, interlineado ajustado.
- **Body** (400, `1rem`, 1.5): todo texto corrido; descripciones en `max-w-xl`/`max-w-2xl`. Precio principal 800 `1.25rem` con «+ IVA» 600 `0.875rem` en tinta suave; Bs. 600 `0.875rem` en tinta.
- **Label** (700, `0.875rem`, `etiqueta`): etiquetas de campo. Botones 700 `0.9375rem` (mini `0.875rem`, grande `1rem`).
- **Código de fábrica** (700, `0.8125rem`, tracking 0.06em, versalitas por `uppercase`, tierra, tabular, sin cortes, clase `codigo`): siempre visible en tarjeta, ficha y tablas.
- **Encabezado de tabla** (700, `0.75rem`, tracking 0.08em, mayúsculas, tinta suave).

### Named Rules
**The One Family Rule.** Una sola familia; la jerarquía la hacen el peso (400 / 600 / 700 / 800) y el tamaño. La serif de alto contraste de la marca vive solo dentro del archivo del logo oficial; no se imita con fuentes web.

**The Tabular Figures Rule.** Todo precio, cantidad, conteo y código usa `cifra` para alinearse en columna.

## Layout

**Modelo espacial.** Una página de agua crema con una zona de tierra arriba (cliente) o a la izquierda (personal). Contenido centrado: cliente `max-w-[1320px]` con `pt-14 pb-16` (lg `pt-16`) para dejar sitio a la cápsula clavada en la costa; personal `max-w-[1400px]` con `py-6` (lg `py-8`). Márgenes laterales 16 px → 24 px (sm) → 32 px (lg). El envoltorio del contenido lleva `overflow-x-clip` para que los lóbulos decorativos que se salen por el borde no generen barra horizontal.

**Ritmo.** Separaciones de 8 / 12 / 16 / 24 / 32 px (`gap-2`, `gap-3`, `gap-4`, `gap-6`, `gap-8`). Lóbulos con relleno de 20 px, 24 px en xl. `scroll-padding-top: 6rem`.

**Puntos de corte** (los de Tailwind 4): sm 640, md 768, lg 1024, xl 1280.

### 390 px (celular del productor)
- Cabecera: logo (si existe) + «Rialto 7»; WhatsApp como cápsula con solo ícono de 44 px; menú de cuenta en un desplegable. La navegación principal se oculta hasta md.
- Costa de 36 px de alto; «Mi cotización» (`btn-grande`, 52 px) a 16 px del borde derecho.
- Categorías en una fila de cápsulas con desplazamiento horizontal que sangra hasta el borde (`-mx-4 px-4`).
- Tarjeta horizontal: imagen en lóbulo de 104 px a la izquierda, datos a la derecha; una columna.
- Filtros en **hoja inferior** (`max-h-85dvh`, radio superior 28 px, fondo agua) abierta por «Ajustar»; velo tinta al 50 %; Escape la cierra.
- Personal: barra superior tierra de 64 px con botón de menú de 44 px; el riel entra como cajón desde la izquierda. En el tablero lo accionable (alertas) va antes que los gráficos y el gráfico de ventas muestra 14 días.

### 1366 px (PC del personal)
- Riel fijo de 256 px (`lg:pl-64` en `body`), contenido hasta 1400 px. Tablero en rejilla de 12 columnas: gráfico de ventas 8 + conversión 4; alertas 6 + más vendidos 6. Panel de indicadores en 4 columnas desde xl.
- Las cabeceras de las páginas públicas, vistas por el personal, se convierten en un `lobulo-tierra` dentro del contenido.

### 1440 px (escritorio del cliente)
- Barra lateral de filtros de 256 px fija al desplazar (`lg:sticky top-6`) + rejilla de tarjetas: 2 columnas desde sm, 3 desde xl, separación 16 px.
- La cápsula «Mi cotización» se alinea con el borde derecho del contenido: `right: max(2rem, calc((100vw - 1320px)/2 + 2rem))`. La fila de categorías reserva `pr-56` para no chocar con ella.
- Costa de 56 px de alto; buscador de 64 px de alto.

### Named Rules
**The Thumb First Rule.** Se diseña primero para un pulgar en 390 px; el escritorio amplía, no reordena la verdad (código, existencia y precio siempre en la tarjeta).

## Elevation & Depth

Sistema plano. No hay `box-shadow` de elevación en ninguna superficie: la profundidad sale de las zonas de color, de las costas y de **anillos crema** (`ring-4 ring-agua`) que recortan una pieza sobre la zona que pisa: la cápsula «Mi cotización» sobre la costa y el aviso flotante sobre el contenido. Los únicos `box-shadow` del sistema son halos de estado en campos: foco `0 0 0 4px rgb(11 74 59 / 0.15)` e inválido `0 0 0 4px rgb(255 107 90 / 0.18)`. Las capas superpuestas (hoja de filtros, cajón del riel) se separan con un velo tinta al 50 %.

### Named Rules
**The Cream Ring Rule.** Lo que flota sobre otra zona se recorta con un anillo de 4 px del color del agua, nunca con sombra.

## Shapes

- **Cápsula** (radio completo): botones, cápsulas de filtro, estados, paginación (40 px), ítems del riel, chip de conteo, buscador, aviso flotante, barras de los gráficos (`borderRadius: 999`).
- **Lóbulo** (22 px, token `--radius-lobulo`): tarjetas, secciones del tablero, panel de indicadores, estado vacío.
- **Campo** (16 px): campos de formulario, avisos, menú desplegable; ítems del menú 12 px; hoja inferior 28 px arriba.
- **Costa**: trazo SVG orgánico y asimétrico que cierra la tierra. Cabecera: viewBox 1440×56, lóbulos desiguales y un cabo a la derecha bajo «Mi cotización». Pie: viewBox 1440×40 sobre tierra oscura. Riel del personal: costa vertical de 24 px de ancho (viewBox 24×800) que sobresale 20 px por su borde derecho, solo desde lg.
- **Lóbulo de recorte** (`clipPath #forma-lobulo`, unidades relativas a la caja): recorta foto o silueta de producto; sin foto, el lóbulo se rellena de sabana clara y la silueta genérica va al 80 % de opacidad.
- **Lóbulos decorativos**: manchas SVG de sabana al 30 % o laguna al 25 % que se salen del borde, siempre `aria-hidden` y `pointer-events-none`.
- **Reglas**: 1 px en línea; 2 px en tinta bajo encabezados de tabla; borde de 1.5 px en cápsulas, campos y botones de línea; separador punteado en la tarjeta.
- **Íconos**: trazo de 2 px en rejilla 24×24, extremos redondeados (`x-icono`), decorativos por defecto.

### Named Rules
**The Coast Not Line Rule.** Donde la tierra se encuentra con el agua hay una costa SVG, no un borde recto. Dentro del agua, en cambio, las divisiones son reglas de 1 px.

## Components

### Buttons (`.btn`)
Cápsulas que ceden y rebotan al pulsarlas.
- **Shape:** radio completo; alto mínimo 44 px (`min-h-11`), relleno 20 px, 700 a `0.9375rem`, ícono con separación de 8 px.
- **Sol** (`btn-sol`): sol con texto tinta; hover #ffc81a. Acción principal.
- **Tierra** (`btn-tierra`): tierra con texto blanco; hover tierra oscura. Acción de confirmación dentro de paneles («Ver resultados»).
- **Línea** (`btn-linea`): transparente, borde 1.5 px tinta; hover se rellena de tinta con texto agua. **Línea clara** (`btn-linea-clara`): versión sobre tierra, borde blanco al 80 %, hover blanco con texto tierra.
- **Tamaños:** `btn-grande` 52 px / 24 px de relleno / `1rem`; `btn-mini` 36 px / 14 px / `0.875rem` (en la cabecera se fuerza a 44 px).
- **Pulsado:** `transform: scale(0.96)` con `0.4s var(--ease-rebote)`. **Deshabilitado:** opacidad 55 %, cursor no permitido.
- **Chip de flecha** (`.chip`): círculo de 32 px que monta el borde derecho de la cápsula (−12 px), tinta con flecha blanca (sol sobre tierra en `btn-tierra`); en hover avanza 3 px con rebote.
- **En espera:** spinner `.girando` de 1 em, trazo 2.5 px en `currentColor`.

### Cápsulas de filtro (`.capsula`)
- **Reposo:** blanco, borde 1.5 px línea, 40 px de alto, 600 a `0.875rem`; chip de **conteo** de 28 px en agua a la derecha. Hover: borde tierra.
- **Activa** (`aria-current`): tierra con texto blanco y la fila se invierte (`flex-row-reverse`): el chip de conteo **cruza al frente** y se vuelve sol con texto tinta. Es la firma de los filtros.
- **Quitar** (`capsula-quitar`): cápsula con «×» para quitar una búsqueda o ajuste activo con un toque.
- **Pestañas de estado** (`x-pestanas`): la misma cápsula sin conteo, en fila desplazable.

### Estados (`.estado`)
Cápsula de 4 × 12 px, 700 a `0.8125rem`, con punto de 8 px en `currentColor` antes del texto. Roles en la tabla de Colors. Stock en tres niveles fijos: el cliente lee «Disponibles: N» / «Quedan N» / «Agotado»; el personal lee «Disponible: N» / «Bajo stock: N» / «Agotado».

### Cards / Containers (`.lobulo`)
- **Corner Style:** 22 px.
- **Background:** blanco con borde 1 px línea; variante `lobulo-tierra` sin borde y texto blanco; variante sabana (conversión) con texto tinta.
- **Shadow Strategy:** ninguna (ver Elevation & Depth).
- **Internal Padding:** 12 px (tarjeta en celular), 16 px (tarjeta sm+), 20–24 px (secciones).
- **Tarjeta de producto:** imagen en lóbulo (cuadrada en celular, 4:3 desde sm) que crece a 1.03 en hover con rebote; código → nombre → marca · categoría → estado; separador punteado; precio USD + IVA y Bs. a la izquierda, acción a la derecha («Agregar» sol, o «Preguntar cuándo llega» en línea si está agotado).

### Inputs / Fields (`.campo`)
- **Style:** blanco, borde 1.5 px línea, radio 16 px, alto mínimo 48 px, texto `1rem`, marcador en tinta suave al 80 %. Select con chevrón SVG propio a 16 px del borde.
- **Focus:** borde tierra + halo de 4 px tierra al 15 %.
- **Error:** borde coral + halo coral al 18 %; mensaje `error-campo` 600 en coral tinta. **Válido:** borde sabana.
- **Unidad pegada** (`grupo-campo` > `unidad`): bloque agua honda con 700 en tinta suave, comparte el radio del lado externo.
- **Buscador del catálogo:** cápsula blanca sin borde de 56 px (64 px lg) sobre tierra, ícono a 20 px; foco con anillo sol al 70 %.

### Tablas (`.tabla`)
Densas y sobrias: `0.9375rem`, celdas 12 × 12 px, encabezado en mayúsculas sobre regla de 2 px tinta, filas con regla de 1 px línea y hover agua. Columnas numéricas `num` alineadas a la derecha y tabulares.

### Navigation
- **Cliente:** enlaces cápsula en blanco al 90 % sobre tierra, hover blanco al 10 %, actual blanco al 15 %. Menú de cuenta: panel blanco de 224 px, radio 16 px, borde línea.
- **Riel del personal** (`nav#riel`): tierra, 256 px, fijo a la izquierda; su **borde derecho es una costa** SVG que invade 20 px el agua. Ítems cápsula de 44 px, 600, ícono + texto; activo en **sol con texto tinta**, resto blanco al 90 % con hover blanco al 10 %. Sección «Administración» separada por un rótulo 700 `0.75rem` en sabana. Al pie, nombre del usuario y «Salir» en `btn-linea-clara`. Debajo de lg es un cajón con velo, transición `300ms var(--ease-salida)`, cierre con Escape.
- **Paginación:** cápsulas de 40 px; enlaces blancos con borde línea (hover borde tierra); actual en tierra con texto blanco.

### «Mi cotización» clavada en la costa (componente firma)
`btn-sol btn-grande` posicionado en `top-full` de la cabecera y subido un tercio de su alto (`-translate-y-1/3`), justo sobre el cabo derecho de la costa, recortado con `ring-4 ring-agua`. Lleva ícono, texto y un contador de 28 px tinta con cifra blanca que se oculta cuando la cotización está vacía. Su `aria-label` dice el número de productos. Es el momento memorable del portal; no se duplica en otra parte de la cabecera.

### Aviso flotante (`.toast`)
Cápsula tinta (coral tinta para error) de hasta 30 rem, centrada abajo respetando `safe-area-inset-bottom`, con anillo agua de 4 px; entra desde abajo con `0.4s var(--ease-rebote)`; `role="status"`, `aria-live="polite"`; enlaces en sol.

### Avisos (`.aviso`)
Bloques de radio 16 px, 12 × 16 px, `0.9375rem`, ícono + texto, fondo claro de la familia de señal. Éxito `role="status"`, error `role="alert"`.

### Gráficos (Chart.js, tablero)
Fuente Hanken Grotesk 13 px, ticks tinta suave, rejilla #e6dfca, ejes base tinta, sin leyenda ni animación. Barras en cápsula (`borderRadius: 999`): ventas en tierra con hover sol; más vendidos en sol con hover tierra. Tooltip tinta, radio 12 px. Sin JavaScript o sin Chart.js, las cifras impresas por el servidor siguen visibles y aparece un aviso.

### Logo
`x-logo` muestra el **logo oficial** `public/img/logo.png` (emblema blanco sobre verde petróleo) solo si el archivo existe, con radio 12 px y `object-contain`; mientras falte, la cabecera muestra únicamente el nombre «Rialto 7». **Activo de marca pendiente:** la usuaria aún no ha copiado el archivo; no se redibuja, no se aproxima y no se inventan variantes. El favicon sigue la misma condición.

### Motion
- `--ease-rebote` `cubic-bezier(0.34, 1.45, 0.64, 1)`: sobrepaso deliberado, **regla del mundo limitada a la respuesta física**: escala al pulsar botones, chip de flecha, cápsulas, crecimiento de imagen de tarjeta y entrada del aviso flotante. Siempre 0.4–0.5 s y sobre `transform`.
- `--ease-salida` `cubic-bezier(0.16, 1, 0.3, 1)`: hojas y cajones (hoja de filtros 400 ms, riel 300 ms).
- Color: 0.15–0.2 s lineal por defecto.

### Accessibility
- **Objetivos de 44 px:** `.btn` `min-h-11`, ítems del riel y del menú 44 px, botones de ícono `size-11`; las variantes mini en cabecera se fuerzan a 44 px. Excepción documentada: cápsulas de filtro (40 px) y paginación (40 px) con separación de 6–8 px.
- **Contraste:** texto pequeño solo en combinaciones AA: tinta o tinta suave sobre agua/blanco, blanco sobre tierra, tinta sobre sol, tintas oscuras sobre los fondos claros de estado. El coral puro sobre tierra solo en cifras grandes (2 rem extrabold).
- **Foco:** `:focus-visible` con contorno laguna de 3 px y separación de 2 px en todo elemento; enlace «Saltar al contenido» en cápsula sol.
- **Movimiento reducido:** `prefers-reduced-motion: reduce` lleva toda transición y animación a 0.01 ms.
- **Hoja inerte:** la hoja de filtros lleva `:inert="!panel && !lg"`: cerrada en el celular no recibe foco ni lectura; en escritorio es una barra lateral normal. Hojas y cajón se cierran con Escape y con toque en el velo; los disparadores llevan `aria-expanded`/`aria-controls`.
- Íconos y formas decorativas `aria-hidden`; estados siempre con texto; `lang="es-VE"`; todo funciona sin JavaScript (formularios clásicos).

## Do's and Don'ts

### Do:
- **Do** usar sol (#ffd23f) con texto tinta para la única acción principal de cada vista, y para marcar el lugar actual en el riel.
- **Do** cerrar toda zona de tierra con una costa SVG orgánica y asimétrica (`preserveAspectRatio="none"`, alto 36–56 px en la cabecera).
- **Do** recortar con un anillo agua de 4 px lo que flota sobre otra zona («Mi cotización», aviso flotante).
- **Do** usar cápsulas de radio completo para controles y estados, y lóbulos de 22 px para superficies.
- **Do** escribir precios, cantidades y códigos con `cifra`, y mostrar siempre el código de fábrica con la clase `codigo`.
- **Do** dar a cada estado su texto y su punto, con fondo claro y tinta oscura de la misma familia.
- **Do** mantener objetivos de 44 px y texto base de 16 px.
- **Do** reservar `--ease-rebote` para la respuesta física al pulsar o aparecer; usar `--ease-salida` para hojas y cajones.

### Don't:
- **Don't** usar degradados ni sombras difusas para separar superficies; los únicos halos son los de foco y error de campo.
- **Don't** separar tierra y agua con una línea recta.
- **Don't** usar sol como decoración ni poner dos acciones sol que compitan en la misma vista.
- **Don't** añadir una segunda familia tipográfica ni imitar la serif del logo con fuentes web.
- **Don't** redibujar, aproximar o inventar el emblema de Rialto 7; si `public/img/logo.png` no existe, solo el nombre.
- **Don't** aplicar el rebote a transiciones de color, a la entrada de hojas o a desplazamientos de página.
- **Don't** comunicar un estado solo con color.
- **Don't** poner texto pequeño sobre coral, laguna o lila puros.
