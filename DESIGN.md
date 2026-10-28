---
name: Rialto 7
description: Portal de ventas de repuestos y maquinaria agrícola de Inversiones Rialto 7, en el azul y amarillo de las marcas que representa.
colors:
  azul-principal: "#0d5192"
  azul-profundo: "#052b70"
  azul-noche: "#031c46"
  azul-claro: "#dce8f5"
  amarillo: "#ffd300"
  amarillo-claro: "#fff4b3"
  verde-rialto: "#0b4a3b"
  verde-rialto-oscuro: "#062a21"
  verde-rialto-vivo: "#2a9d6f"
  verde-rialto-claro: "#dcebe4"
  fondo: "#f4f6f9"
  fondo-hondo: "#e7ecf2"
  blanco: "#ffffff"
  tinta: "#0f1b2d"
  tinta-suave: "#4a5668"
  linea: "#d3dbe5"
  verde-disponible: "#2e9b5f"
  verde-disponible-claro: "#dcf1e3"
  rojo-senal: "#d63a2c"
  rojo-senal-claro: "#fbe3e0"
  rojo-senal-tinta: "#a2271b"
  azul-info: "#2f6fb0"
  azul-info-claro: "#deeaf6"
  lila-pendiente-claro: "#efe8fa"
typography:
  display:
    fontFamily: "Raleway Variable, system-ui, sans-serif"
    fontSize: "4.5rem"
    fontWeight: 900
    lineHeight: 1.02
    letterSpacing: "-0.01em"
    textTransform: uppercase
  headline:
    fontFamily: "Raleway Variable, system-ui, sans-serif"
    fontSize: "3rem"
    fontWeight: 900
    lineHeight: 1.02
    textTransform: uppercase
  title:
    fontFamily: "Raleway Variable, system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 900
    lineHeight: 1.15
    textTransform: uppercase
  body:
    fontFamily: "Raleway Variable, system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.55
  label:
    fontFamily: "Raleway Variable, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 800
    letterSpacing: "0.08em"
    textTransform: uppercase
  numeral:
    fontFamily: "Raleway Variable, system-ui, sans-serif"
    fontSize: "2.25rem"
    fontWeight: 900
    fontFeature: "lnum, tnum"
rounded:
  control: "6px"
  panel: "8px"
  photo: "10px"
  pill: "999px"
---

# Design System: Rialto 7

## Overview

**Azul de campo.** El portal viste los colores de las marcas que Rialto 7 representa en Venezuela: el azul profundo y el amarillo de Marchesan Tatu. El verde del logo Rialto es el tercer color: pie de página, degradados sobre las fotos, subrayados y secciones de apoyo. La portada abre con fotos de campo y de la sede a sangre, que se relevan solas con un zoom lento. Encima van arcos azules translúcidos y titulares en mayúsculas con una palabra resaltada en amarillo. Al bajar, cada bloque entra subiendo con suavidad y las cifras cuentan hasta su valor, en la línea de cicmelinst.com.

Hay dos mundos que comparten tokens. El público (portada, catálogo, historia, contacto) es expresivo: fotos, arcos y movimiento. El de gestión (tablero, pedidos, inventario, reportes) es sobrio: el mismo azul y amarillo, sin fotos ni animaciones de entrada, pensado para escanear.

## Colors

### Primary
- **Azul principal** `#0d5192`: enlaces, títulos sobre claro, botón secundario, círculo de existencias, gráficos.
- **Azul profundo** `#052b70`: cabeceras de página, secciones oscuras, titulares grandes.
- **Azul noche** `#031c46`: pie de página y degradados sobre fotos.

### Secondary
- **Amarillo** `#ffd300`: la acción principal (Agregar, Mi cotización), la palabra resaltada en titulares sobre azul, el subrayado de 5 px bajo los títulos y el foco del teclado.
- **Verde Rialto** `#0b4a3b` (profundo `#062a21`, vivo `#2a9d6f`):
  - fondo del logo y sello «desde 1963»;
  - degradados azul→verde sobre las fotos de las cabeceras;
  - remate verde del subrayado y de la línea amarilla;
  - pie de página (azul noche a verde profundo) y sección «Tres pasos»;
  - el segundo arco decorativo.

### Tertiary (señales)
- Verde disponible `#2e9b5f`, rojo señal `#d63a2c`, azul info `#2f6fb0` y lila pendiente: estados de existencia y de pedido. Siempre con su versión clara de fondo.

### Neutral
- Fondo `#f4f6f9`, fondo hondo `#e7ecf2`, blanco para paneles, línea `#d3dbe5`.
- Tinta `#0f1b2d` para el texto; tinta suave `#4a5668` para el texto de apoyo.

### Named Rules
- **Un amarillo por vista.** El amarillo marca la acción principal; si todo es amarillo, nada lo es.
- **El verde acompaña, no manda.** Va en fondos, degradados y detalles; los botones siguen siendo amarillo (acción) y azul (secundario).

## Typography

Raleway variable en todo el portal, con números de forma «lining» (Raleway trae números antiguos por defecto).

### Hierarchy
- **Titular** (`.titular`): peso 900, mayúsculas, interlineado 1.02. La palabra clave va en `<em>`: amarillo sobre azul, azul sobre claro.
- **Subrayado** (`.subrayado`): barra amarilla de 5 px bajo el titular de cada sección.
- **Cuerpo**: 17 px, interlineado 1.55, líneas de 60 a 75 caracteres.
- **Etiquetas**: 13 px, peso 800, mayúsculas, espaciado 0.08em.
- **Cifras**: peso 900 con números tabulares para precios, existencias y códigos.

### Named Rules
- No se usan rótulos pequeños encima de los titulares: el subrayado amarillo hace ese trabajo.

## Layout

- Ancho máximo de 1320 px con márgenes de 16, 24 y 32 px.
- Las secciones públicas alternan blanco, fondo claro y azul profundo, con 64 a 96 px de aire vertical.
- La portada ocupa la pantalla completa en escritorio. En el celular, la foto lleva su propio recorte vertical.
- En gestión, el riel lateral fijo y las tablas densas mandan.

## Elevation & Depth

- Paneles blancos con borde de línea. Al pasar el cursor, `.tarjeta-viva` sube 4 px y gana una sombra azul suave.
- La cabecera gana sombra al desplazarse.
- Las insignias sobre las fotos (años de trayectoria, nombre de la sede) flotan con una sombra azul difusa.
- No hay sombras duras desplazadas.

## Shapes

- Controles con 6 px de radio y paneles con 8 px.
- Fotos con 10 px de radio; las insignias y los estados son píldoras.
- El círculo con borde azul (`.ruedo`) lleva la existencia sobre la foto del producto.
- Los arcos (`<x-arcos>`) son dos curvas concéntricas translúcidas a la derecha de las secciones azules.

## Components

- **Botones:** `.btn-sol` (amarillo, acción principal), `.btn-tierra` (azul), `.btn-linea` (borde azul) y `.btn-linea-clara` (borde blanco sobre azul). Al pasar el cursor suben 2 px.
- **Tarjeta de producto:**
  - foto sobre fondo claro con el círculo de existencias;
  - código de fábrica en azul, grande;
  - precio en USD con su equivalente en bolívares;
  - botón amarillo «Agregar».
- **Portada (`portada`, Alpine):**
  - cinco fotos que se relevan cada 6 s con zoom lento;
  - puntos con barra de avance;
  - se pausa cuando la pestaña está oculta.
- **Revelado (`data-revelar`):** el bloque entra subiendo 32 px, escalonado con `--orden`.
- **Contadores (`data-contar`):** cuentan hasta su valor al aparecer.
- **Tasa del día:** la tasa va junto a «Los más pedidos», con el IVA y la fecha.

## Do's and Don'ts

- **Sí:** fotos reales de la sede y del equipo; fotos de campo con crédito en `public/img/portada/CREDITOS.txt`.
- **Sí:** toda cabecera de página pública lleva una foto de fondo (campo o sede) con el degradado azul y verde encima.
- **Sí:** en lugar del fondo blanco o gris liso, el contenido de las páginas públicas y las secciones claras de la portada van sobre una foto de campo o de la sede con un velo claro (`.fondo-foto`, al estilo de Tatu). En computadora la foto queda fija y el contenido se desliza encima. Las tarjetas y formularios siguen en blanco para leerse bien.
- **Sí:** respetar «reducir movimiento». Sin animaciones, todo se muestra de una vez y la portada no rota.
- **Sí:** el texto mínimo de 4.5:1 de contraste; el amarillo solo lleva texto azul profundo o tinta.
- **No:** texto con degradado, rótulos encima de los titulares, iconos hechos con caracteres ni sombras duras.
- **No:** botones o enlaces en verde; el verde vive en fondos y detalles.
