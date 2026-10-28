---
name: Rialto 7
description: Portal de ventas de repuestos y maquinaria agrícola, vestido con la librea de fábrica de Inversiones Rialto 7.
colors:
  verde-rialto: "#0b4a3b"
  verde-rialto-oscuro: "#06291f"
  campo-verde-claro: "#d6e6dc"
  amarillo-franja: "#f5b800"
  amarillo-franja-pulsado: "#ffc71f"
  amarillo-claro: "#fff0b8"
  gris-taller: "#f2f3f0"
  gris-taller-hondo: "#e4e7e2"
  blanco-competencia: "#ffffff"
  tinta: "#10241d"
  tinta-suave: "#4b5852"
  regla: "#ccd3cd"
  borde-campo: "#b5beb8"
  verde-disponible: "#2f9e63"
  verde-disponible-claro: "#dcf1e3"
  rojo-senal: "#d63a2c"
  rojo-senal-claro: "#fbe3e0"
  rojo-senal-tinta: "#a2271b"
  azul-info: "#2f6fb0"
  azul-info-claro: "#deeaf6"
  lila-pendiente-claro: "#efe8fa"
typography:
  display:
    fontFamily: "Barlow Condensed, Barlow, sans-serif"
    fontSize: "5.5rem"
    fontWeight: 800
    lineHeight: 0.92
    letterSpacing: "-0.005em"
  headline:
    fontFamily: "Barlow Condensed, Barlow, sans-serif"
    fontSize: "3rem"
    fontWeight: 800
    lineHeight: 0.92
  title:
    fontFamily: "Barlow Condensed, Barlow, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 800
    lineHeight: 1.1
  body:
    fontFamily: "Barlow, system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Barlow Condensed, Barlow, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 700
    letterSpacing: "0.04em"
  numeral:
    fontFamily: "Barlow Condensed, Barlow, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 800
    fontFeature: "tnum, lnum"
rounded:
  control: "2px"
  panel: "3px"
  ruedo: "9999px"
spacing:
  gutter-mobile: "16px"
  gutter-tablet: "24px"
  gutter-desktop: "32px"
  section: "80px"
components:
  button-primary:
    backgroundColor: "{colors.amarillo-franja}"
    textColor: "{colors.tinta}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "0 20px 0 30px"
    height: "48px"
  button-primary-hover:
    backgroundColor: "{colors.amarillo-franja-pulsado}"
  button-carroceria:
    backgroundColor: "{colors.verde-rialto}"
    textColor: "{colors.blanco-competencia}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    height: "48px"
  button-linea:
    backgroundColor: "{colors.blanco-competencia}"
    textColor: "{colors.tinta}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    height: "48px"
  input:
    backgroundColor: "{colors.blanco-competencia}"
    textColor: "{colors.tinta}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    height: "48px"
  panel:
    backgroundColor: "{colors.blanco-competencia}"
    rounded: "{rounded.panel}"
  panel-carroceria:
    backgroundColor: "{colors.verde-rialto}"
    textColor: "{colors.blanco-competencia}"
    rounded: "{rounded.panel}"
  ruedo:
    backgroundColor: "{colors.blanco-competencia}"
    textColor: "{colors.tinta}"
    typography: "{typography.numeral}"
    rounded: "{rounded.ruedo}"
    size: "56px"
---

# Design System: Rialto 7

## Overview

**Creative North Star: "La librea de fábrica"**

El portal viste los colores de Inversiones Rialto 7 como un implemento agrícola viste los de su fabricante: una carrocería verde, una franja verde-blanco-amarillo que recorre las piezas, números grandes en círculos blancos y paneles rectos de chapa. Es un taller ordenado, no una tienda gris: el productor entiende de un vistazo si hay la pieza (el número del círculo), cuánto cuesta y cómo cotizarla.

La portada (modo Persuadir) lleva la franja vertical con el círculo del 7, la pizarra del día y la fila de marcas como la fila de patrocinadores. El catálogo, la cotización y el panel del personal (modo Operar) usan la misma gramática con más densidad y menos gesto: reglas a 1 px, cifras tabulares y la franja solo como tapa de los paneles.

Se rechazó expresamente: las formas onduladas («costas») y las cápsulas redondeadas del diseño anterior.

**Key Characteristics:**
- Color plano a página completa: carrocería verde, fondo gris de taller, blanco de competencia.
- La franja verde-blanco-amarillo como firma: cierra la barra superior, tapa los paneles y marca el talón de los botones.
- Rótulos en Barlow Condensed extranegrita en mayúsculas; texto corrido en Barlow.
- Esquinas casi rectas (2–3 px); el único círculo es el ruedo numerado.
- Sin sombras: la profundidad la dan los paneles, las reglas y el color.

## Colors

Una carrocería verde con una franja amarilla, sobre el gris claro de un taller; los colores de señal solo informan estados.

### Primary
- **Verde Rialto** (#0b4a3b): el verde del logo. Carrocería: cabeceras de página, riel del personal, pie, paneles verdes y botones secundarios llenos.
- **Verde Rialto oscuro** (#06291f): pie de página y estado pulsado de la carrocería.
- **Campo verde claro** (#d6e6dc): el campo de la librea (franja vertical de la portada, celda de la tasa, totales destacados).

### Secondary
- **Amarillo franja** (#f5b800): la acción principal (botón «Mi cotización», «Agregar», «Crear pedido»), el paso actual, la pestaña activa del menú y la banda central de la franja.

### Tertiary (señales)
- **Verde disponible** (#2f9e63 / #dcf1e3): existencia y estados confirmados.
- **Rojo señal** (#d63a2c / #fbe3e0 / #a2271b): agotado, error y acciones destructivas.
- **Azul información** (#2f6fb0 / #deeaf6): avisos informativos y compatibilidad.
- **Lila pendiente** (#efe8fa con texto #4c2a7a): cotizaciones y pedidos pendientes.

### Neutral
- **Gris de taller** (#f2f3f0): fondo de todas las páginas.
- **Gris de taller hondo** (#e4e7e2): campos de unidad, cajas de imagen vacías.
- **Blanco de competencia** (#ffffff): paneles, barra superior, círculos numerados.
- **Tinta** (#10241d) y **tinta suave** (#4b5852): texto principal y de apoyo.
- **Regla** (#ccd3cd) y **borde de campo** (#b5beb8): líneas de 1 px y bordes de controles.

### Named Rules
**La regla de la franja.** La franja siempre va en el mismo orden (verde, blanco, amarillo, blanco, verde) y con cortes netos; nunca como degradado ni como acento suelto de color.
**La regla de la señal.** El rojo y el verde de estado solo marcan existencia, error o confirmación; nunca decoran.

## Typography

**Display Font:** Barlow Condensed (autoalojada con @fontsource)
**Body Font:** Barlow (autoalojada con @fontsource)

**Character:** Una sola familia de raíz DIN, la de las placas de modelo y la señalética de carretera: la condensada rotula y numera, la normal se lee al sol.

### Hierarchy
- **Display** (800, 3.25–5.5rem, 0.92): titular de la portada y de las cabeceras de página, en mayúsculas.
- **Headline** (800, 2.25–3rem, 0.92): títulos de sección.
- **Title** (800, 1.5rem): títulos de panel, nombres de marca, pasos.
- **Body** (400–600, 1.0625rem, 1.5): texto corrido a 65–75 caracteres por línea.
- **Label** (700, 0.94–1.06rem, +0.04em, mayúsculas): botones, pestañas, navegación, encabezados de tabla.
- **Numeral** (800, cifras tabulares): códigos de fábrica, precios, existencias y conteos.

### Named Rules
**La regla del código.** El código de fábrica se escribe siempre en la condensada extranegrita, en verde Rialto y con cifras tabulares: es la verdad del producto.

## Layout

Contenedor de 1320 px centrado con medianiles de 16, 24 y 32 px (celular, tableta, escritorio). La portada ocupa el ancho completo y deja a la izquierda una franja vertical de librea de 96–112 px desde 1024 px. El catálogo usa una barra lateral de filtros de 16 rem en escritorio que en el celular se vuelve una hoja inferior. El panel del personal tiene un riel verde fijo de 16 rem con la franja vertical en su borde derecho; en el celular el riel se abre como menú. Ritmo de secciones: 56 px en el celular, 80 px en escritorio, con más espacio arriba de un título que debajo. Resolución mínima del personal: 1366 × 768.

## Elevation & Depth

Plano por completo: no hay sombras de profundidad. La jerarquía se construye con paneles blancos sobre gris, paneles verdes de carrocería, reglas de 1 px y la franja de tapa. El único anillo es el foco: contorno amarillo de 3 px, y en los campos un talón amarillo interior de 6 px a la izquierda.

## Shapes

Esquinas de 2 px en controles y de 3 px en paneles: chapa cortada, no plástico redondeado. El círculo se reserva al ruedo numerado (existencia, pasos, conteos del menú y el 7 de la marca). Las imágenes de producto van rectas, contenidas sobre el gris, con mezcla multiplicar para que el fondo blanco de la foto desaparezca.

## Components

- **Botón principal (amarillo franja):** panel recto con el talón de tres franjas a la izquierda (verde, blanco, verde), rótulo en mayúsculas y, cuando lleva flecha, un cuadro tinta pegado al borde derecho.
- **Botón de carrocería (verde):** igual, con el talón amarillo.
- **Botón de línea:** blanco con borde tinta de 1,5 px; al pasar el cursor se invierte a tinta.
- **Campo:** blanco, borde #b5beb8; al enfocarlo aparece el talón amarillo interior y un halo verde suave.
- **Placa de categoría:** rectangular con el conteo en un ruedo; activa, verde con el ruedo amarillo.
- **Tarjeta de producto:** panel blanco con la franja de tapa, foto contenida sobre gris, ruedo con la existencia montado en la esquina de la foto, código grande, precio en USD y Bs.
- **Pizarra:** celdas rectas con reglas de 1 px y números grandes; la tasa del día en el campo verde claro.
- **Riel del personal:** verde, con el ítem activo en amarillo y los pendientes en ruedos.

## Do's and Don'ts

- **Do** usar la franja solo como banda completa (barra superior, tapa de panel, talón de botón, franja vertical).
- **Do** mostrar la existencia como número grande en un ruedo y el estado con su placa de color.
- **Do** escribir precios y códigos con cifras tabulares.
- **Don't** volver a las costas onduladas, las manchas de fondo ni las cápsulas redondeadas.
- **Don't** usar sombras, degradados decorativos ni vidrio esmerilado.
- **Don't** poner etiquetas pequeñas en mayúsculas encima de los títulos: el título habla solo.
