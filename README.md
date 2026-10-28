# Portal Rialto 7 — Laravel 12

Portal transaccional de ventas de **Inversiones Rialto 7 C.A.**: catálogo público, carrito de cotización, pedidos con transacción ACID e inventario con trazabilidad.

## 0. Arranque rápido en este equipo (XAMPP)

El proyecto ya está completo y probado aquí: dependencias en `vendor/`, `.env` configurado, base `rialto7` migrada y sembrada.

1. Abre el **XAMPP Control Panel** y pulsa **Start** en **MySQL**.
2. En esta carpeta ejecuta:
   ```bash
   C:\xampp\php\php.exe artisan serve
   ```
   Los estilos y scripts ya están compilados en `public/build`. Si cambias una vista o `resources/css/app.css`, vuelve a compilar con `npm run build`, o deja `npm run dev` abierto mientras trabajas.
3. Abre http://127.0.0.1:8000. Usuarios de prueba (contraseña `Rialto7*2024`): `admin@rialto7.test`, `vendedor@rialto7.test`, `cliente@rialto7.test`.

El manual de uso para cada perfil, con la guía para cambiar textos y diseño, está en `..\Manual del portal Rialto 7.docx`.

Otros comandos útiles:
- Reiniciar los datos de demostración: `C:\xampp\php\php.exe artisan migrate:fresh --seed`. Carga los 17 productos reales con sus fotos (desde `database/seeders/catalogo`) y genera 30 días de ventas de prueba para que el tablero muestre tendencia.
- Cargar o actualizar el catálogo desde Excel: `php artisan catalogo:importar archivo.xlsx --probar` (ver sección 13).
- Respaldar y restaurar: `php artisan respaldo:crear` y `php artisan respaldo:restaurar nombre.zip` (ver sección 13).
- Ejecutar las 98 pruebas: `C:\xampp\php\php.exe artisan test`. Usan la base `rialto7_test`.
- Regenerar las capturas de las Figuras 17–22: ver `..\herramientas\capturas-tesis.mjs`.
- Composer está en `..\herramientas\composer.phar`.

> **¿Por qué Laravel 12 y no 10?** Laravel 10 dejó de recibir parches de seguridad en febrero de 2025, y Composer bloquea todas sus versiones por avisos de seguridad sin corregir. La tesis pide "Laravel 10 o superior" (Cuadro 30). Laravel 12 corre en PHP 8.2 y conserva la estructura de carpetas de Laravel 10 que usa este código (`app/Http/Kernel.php`), así que no hubo que reescribir nada.

---

## 1. Instalación en otro equipo o servidor

Requisitos: PHP 8.2 o superior (extensiones `pdo_mysql`, `mbstring`, `gd`, `dom`, `zip`, `intl`), Composer 2 y MySQL 8.0 o MariaDB 10.6+. En XAMPP, `gd`, `zip` e `intl` vienen comentadas en `php.ini`: quita el `;` de `extension=gd`, `extension=zip` y `extension=intl`.

La carpeta ya es un proyecto Laravel completo. `composer.json` incluye Laravel 12, dompdf y maatwebsite/excel; `config/app.php` ya usa `es` y `America/Caracas`.

1. Copia la carpeta **sin** `vendor/` ni `.env` y ejecuta `composer install`.
2. Para traducir los mensajes genéricos de Laravel (opcional): `composer require laravel-lang/common --dev` y luego `php artisan lang:add es`.
3. Copia `.env.example` a `.env` y configúralo:
   ```
   APP_NAME="Rialto 7"
   DB_CONNECTION=mysql
   DB_DATABASE=rialto7
   DB_USERNAME=rialto7
   DB_PASSWORD=********
   SESSION_LIFETIME=120

   # Correo (recuperación de contraseña). En desarrollo, "log" escribe el correo en storage/logs/laravel.log
   MAIL_MAILER=log
   MAIL_FROM_ADDRESS="no-responder@tudominio.com"
   MAIL_FROM_NAME="Inversiones Rialto 7"
   ```
   En producción usa el SMTP de la cuenta de correo del hosting (cPanel → *Cuentas de correo electrónico → Configurar cliente de correo*):
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=mail.tudominio.com
   MAIL_PORT=465
   MAIL_ENCRYPTION=ssl
   MAIL_USERNAME=no-responder@tudominio.com
   MAIL_PASSWORD=********
   ```
4. Crea la base de datos con `utf8mb4` y ejecuta:
   ```bash
   php artisan key:generate
   php artisan migrate:fresh --seed
   php artisan storage:link
   php artisan serve
   ```

Usuarios de prueba (contraseña `Rialto7*2024`): `admin@rialto7.test`, `vendedor@rialto7.test`, `cliente@rialto7.test`.

> **Breeze o login propio:** Breeze asume la tabla `users` con columnas `email`/`password`. Como el esquema exige `usuarios(correo, contrasena)`, aquí se escribió un `LoginController` equivalente. Usa las mismas piezas de Laravel (`Auth::attempt`, regeneración de sesión, `RateLimiter`), así que la seguridad es la misma y no hace falta instalar Breeze.

---

## 2. Estructura y justificación de cada archivo

```
app/
  Exceptions/StockInsuficienteException.php
  Http/
    Controllers/
      Auth/LoginController.php, RegistroController.php
      CatalogoController.php, CarritoController.php, CotizacionController.php, CuentaController.php
      Gestion/CotizacionController.php, PedidoController.php, InventarioController.php
    Middleware/VerificarRol.php
    Requests/ ClienteRequest, RegistroRequest, CotizacionRequest, PedidoRequest,
              ProductoRequest, MovimientoInventarioRequest, LoginRequest
  Models/ Usuario, Categoria, Producto, Cliente, Cotizacion, DetalleCotizacion,
          Pedido, DetallePedido, MovimientoInventario, Reporte, Configuracion
  Providers/AppServiceProvider.php
  Services/ CalculadoraComercial, CarritoService, CotizacionService, PedidoService, InventarioService
database/migrations/ (8 archivos)   database/seeders/DatabaseSeeder.php
public/css/rialto7.css               public/img/producto-generico.svg
resources/views/ layouts, partials, catalogo, carrito, cotizaciones, auth, cuenta, gestion
routes/web.php
```

### Migraciones
| Archivo | Por qué |
|---|---|
| `…000001_create_usuarios_table` | Reemplaza `users`. El `rol` es un enum indexado (3 perfiles) y `estado` permite desactivar cuentas sin borrarlas. |
| `…000003_create_productos_table` | `codigo_fabrica` es **único e indexado** (problema 1). Hay índices en `marca`, `nombre` y `(id_categoria, marca)` para los filtros. `costo` queda separado del `precio` público. |
| `…000004_create_clientes_table` | `identificacion` única, para no duplicar clientes. `id_usuario` es opcional porque hay clientes invitados. |
| `…000005/000006` cotizaciones y pedidos | Guardan `precio_unitario`, `porcentaje_iva` y `tasa_cambio` **del momento**: un cambio de precio posterior no altera documentos emitidos. |
| `…000007_create_movimientos_inventario_table` | Guarda `stock_anterior` y `stock_nuevo` por movimiento para la auditoría. No tiene soft delete, porque la trazabilidad no se borra. |
| `…000008` reportes y configuraciones | `reportes` audita cada reporte generado (indicador 4). `configuraciones` hace editables la tasa, el IVA y el umbral. |

Todas las tablas críticas llevan `timestamps()` y `softDeletes()`. Las claves foráneas usan `restrictOnDelete` para no perder historial.

### Modelos
- Cada modelo declara `$table`, porque los nombres en español no siguen la pluralización inglesa de Laravel.
- `Producto` oculta `costo` en `$hidden` y nunca llega a una vista ni a una respuesta JSON. Sus *scopes* `buscar()` y `filtrar()` concentran la lógica del catálogo.
- Las relaciones de los detalles usan `withTrashed()`, así una cotización vieja sigue mostrando un producto ya retirado.
- `Configuracion` guarda en caché los parámetros y limpia la caché al guardarlos, para no consultar la BD en cada tarjeta.

### Servicios (controladores delgados)
- **`CalculadoraComercial`**: único lugar donde se calculan subtotal, descuento, IVA, total y Bs. Trabaja en **centavos enteros**, así no hay errores de punto flotante (problema 2).
- **`CarritoService`**: guarda el carrito en sesión, para invitados y registrados. Guarda solo `id ⇒ cantidad`; precio y stock se leen siempre de la BD.
- **`PedidoService::confirmarVenta()`**: la **transacción ACID**. Ver la sección 3.
- **`InventarioService`**: entradas, salidas y ajustes con bloqueo de fila y registro del movimiento.

### Form Requests
`ClienteRequest` define los patrones venezolanos (cédula/RIF y teléfono) y lo reutilizan el registro, la cotización de invitados y el pedido de mostrador. Así ningún pedido queda sin datos (problema 1). Cada request sanea la entrada en `prepareForValidation()` (`strip_tags`, mayúsculas) y trae los mensajes en español. Los mismos patrones están en los atributos HTML5 `pattern` y `required`, para validar también en el navegador.

### Seguridad
- Contraseñas con cast `hashed` (bcrypt), CSRF con `@csrf` en todos los formularios y regeneración de sesión al iniciar sesión.
- *Rate limiting*: 5 intentos por correo+IP en el login, más `throttle` en las rutas de login, registro y cotización.
- `VerificarRol` en cada grupo de rutas protegidas. También expulsa usuarios desactivados.
- Las cotizaciones de invitados se ven solo con **enlace firmado** (`URL::temporarySignedRoute`, 30 días). Cambiar el número en la URL devuelve 403, así que nadie ve datos de otros clientes.

---

## 3. La transacción ACID de la venta (`PedidoService::confirmarVenta`)

```
BEGIN
  SELECT … FROM pedidos WHERE id = ? FOR UPDATE           ← nadie más confirma este pedido
  SELECT … FROM productos WHERE id IN (…) ORDER BY id FOR UPDATE   ← bloquea el stock en orden fijo (evita interbloqueos)
  ¿stock suficiente para TODAS las líneas?  — no → excepción → ROLLBACK
  UPDATE productos SET stock = stock - n            (por producto)
  INSERT movimientos_inventario (salida, stock antes/después, usuario)
  UPDATE pedidos SET estado = 'confirmado'
COMMIT
```

`DB::transaction(..., 3)` reintenta hasta 3 veces si MySQL detecta un interbloqueo. Si dos vendedores confirman a la vez pedidos con el último repuesto, el segundo espera el bloqueo, ve stock 0 y recibe "Stock insuficiente" **sin que nada cambie**. Así nunca se vende más de lo disponible.

---

## 4. Despliegue en hosting compartido (Apache + SSL + dominio)

1. **Subir:** comprime el proyecto **sin** `node_modules` y súbelo por el administrador de archivos (cPanel) a `/home/usuario/rialto7`, **fuera** de `public_html`. Si el hosting tiene SSH, ejecuta `composer install --no-dev --optimize-autoloader`. Si no, súbelo con la carpeta `vendor/` ya incluida.
2. **Carpeta pública:** apunta el dominio a `rialto7/public`. En cPanel: *Dominios → Raíz del documento*. Si no se puede cambiar, copia el contenido de `public/` a `public_html/` y en `public_html/index.php` corrige las rutas a `__DIR__.'/../rialto7/vendor/autoload.php'` y `__DIR__.'/../rialto7/bootstrap/app.php'`.
3. **Base de datos:** crea la BD y el usuario MySQL en cPanel, impórtalos y configura `.env` con `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tudominio.com` y `SESSION_SECURE_COOKIE=true` (las cookies de sesión y de "Recordar mis datos" solo viajan por HTTPS).
4. **SSL:** en cPanel, *SSL/TLS Status → Run AutoSSL* (Let's Encrypt gratis). Luego fuerza HTTPS en `public/.htaccess`, antes de las reglas de Laravel:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   Header always set Strict-Transport-Security "max-age=31536000"
   ```
   `AppServiceProvider` ya fuerza `https://` en los enlaces cuando `APP_ENV=production`.
5. **Comandos finales** (por SSH o con una tarea Cron de una sola vez):
   ```bash
   php artisan migrate --force
   php artisan db:seed --force          # solo la primera vez; cambia las contraseñas de prueba
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
6. **Permisos:** `storage/` y `bootstrap/cache/` deben tener permiso de escritura (755 o 775).
7. **DNS:** en el registrador del dominio `.com.ve` o `.com`, apunta los registros A al IP del hosting.

---

## 5. Tablero y reportes (segunda entrega)

```
app/Services/TableroService.php, ReporteService.php
app/Http/Controllers/Gestion/TableroController.php, ReporteController.php
app/Http/Requests/ReporteRequest.php
app/Exports/VentasExport.php, InventarioExport.php, Hojas/ExistenciasHoja.php, Hojas/MovimientosHoja.php
resources/views/gestion/tablero.blade.php
resources/views/gestion/reportes/index.blade.php, pdf/ventas, pdf/inventario, pdf/_estilos, pdf/_encabezado
```

**Tablero** (`/gestion/tablero`, vendedor y administrador). Es la página de inicio del personal al iniciar sesión.
- **Indicadores:** ventas del día y del mes, cotizaciones pendientes (y cuántas llevan más de 24 h), productos con bajo stock y agotados, y tasa de conversión de cotizaciones del mes.
- **Gráficos (Chart.js 4.4.1):** ventas diarias de los últimos 30 días y los 5 productos más vendidos del mes. Una tabla muestra las alertas de inventario según `umbral_stock_bajo`.
- **Tiempo real:** la página se dibuja con datos del servidor y luego consulta `/gestion/tablero/datos` (JSON) cada 60 s. Así se actualiza sin recargar y, si se pierde la señal, conserva los últimos valores.
- **Rendimiento:** `TableroService` no usa caché. Cada métrica es una sola consulta agregada (`SUM`, `COUNT`, `GROUP BY`) sobre columnas indexadas (`estado`, `fecha_confirmacion`, `stock`).

**Reportes** (`/gestion/reportes`, solo administrador, como pide el perfil).
- **Ventas:** una fila por producto vendido en pedidos **confirmados**, con filtros de fechas, producto y vendedor. El resumen trae pedidos, unidades, subtotal, IVA, total en USD y Bs., y las ventas por vendedor.
- **Inventario:** existencias actuales (estado, stock y valor a **precio de venta**; el costo nunca se expone) más los movimientos del período, con filtros de producto, categoría y usuario responsable. El Excel sale en un libro con dos hojas.
- **Una sola consulta para todo:** `ReporteService` devuelve *consultas*, no listas. La pantalla las pagina de 50 en 50 y el PDF y el Excel las leen completas, así lo que se ve en pantalla es exactamente lo que se exporta.
- **Excel:** fechas y montos salen como números reales de Excel, no como texto, para filtrar, sumar y armar tablas dinámicas. Tiene encabezado fijo y autofiltro.
- **Generación en segundos:** el rango máximo es de un año (`ReporteRequest`), las consultas usan *joins* en vez de cargar modelos uno por uno y la exportación tiene `throttle:20,1`.
- **Auditoría (indicador 4):** cada reporte generado, en pantalla, PDF o Excel, queda en la tabla `reportes` con usuario, filtros, formato y número de registros. En pantalla se registra solo la primera página. Para medir el indicador de la tesis basta con `SELECT tipo, formato, COUNT(*) FROM reportes GROUP BY tipo, formato`.

---

## 6. Administración (tercera entrega)

```
app/Http/Controllers/Admin/UsuarioController.php, CategoriaController.php, ProductoController.php, ConfiguracionController.php
app/Http/Requests/UsuarioRequest.php, CategoriaRequest.php, ConfiguracionRequest.php   (+ ProductoRequest de la 1.ª entrega)
resources/views/admin/usuarios/{index,form}, categorias/{index,form}, productos/{index,form}, configuracion
```

Todo vive bajo `/admin`, con el middleware `rol:administrador`, y se abre desde el menú **Administración**. Cada entidad usa un solo formulario Blade para crear y editar (`$modelo->exists` decide el título, la ruta y el método `PUT`), lo que evita duplicar campos y validaciones.

- **Usuarios:** crear, editar, desactivar y eliminar (borrado lógico).
  - Al editar, la contraseña vacía no se cambia.
  - El administrador no puede quitarse su propio perfil, desactivarse ni eliminarse (`UsuarioRequest::after()`), así el sistema nunca queda sin administrador.
  - Un usuario desactivado es expulsado en su siguiente petición por `VerificarRol`.
- **Categorías:** no se elimina una categoría que tenga productos, ni siquiera eliminados, porque podrían restaurarse.
- **Productos:**
  - El código de fábrica es obligatorio y único, y siempre se guarda en mayúsculas.
  - El costo es interno, solo se ve en este formulario y nunca en el catálogo.
  - **Imagen:** JPG, PNG o WEBP de hasta 2 MB, con vista previa y aviso inmediato en el navegador. Se guarda en `storage/app/public/productos` con nombre aleatorio. La anterior se borra solo después de guardar con éxito; si falla la BD al crear, se borra la imagen recién subida.
  - **Stock:** el inicial se registra como movimiento de entrada "Inventario inicial". Después la ficha **no permite editar el stock** (`prohibited`); solo cambia desde Inventario o por ventas, así toda variación queda trazada.
  - **Eliminar:** es un borrado lógico. El producto sale del catálogo, sigue en cotizaciones, pedidos y reportes, y se puede **restaurar** desde la pestaña "Eliminados".
  - **Visibilidad:** el interruptor "Visible en el catálogo" oculta un producto sin eliminarlo.
- **Configuración:** tasa USD/Bs. (hasta 4 decimales), IVA, umbral de bajo stock y descuento por volumen (porcentaje y monto mínimo).
  - Acepta coma decimal ("36,50").
  - Se guarda en una transacción y el modelo `Configuracion` limpia su caché, así el cambio se ve de inmediato en el catálogo, el carrito y el tablero.
  - Las cotizaciones y pedidos ya emitidos **no cambian**, porque guardan su propia tasa e IVA.
  - Muestra la fecha de la última actualización de la tasa y un ejemplo de conversión antes de guardar.
- **Caché del catálogo:** las listas de categorías y marcas del filtro se limpian solas al crear, editar, eliminar o restaurar (eventos `booted()` en `Categoria` y `Producto`).

---

## 7. Pruebas automatizadas (PHPUnit)

```
tests/Concerns/CreaDatos.php                 atajos: configurar(), crearUsuario(), crearCliente(), crearProducto(), venta()
tests/Feature/CalculadoraComercialTest.php   6 pruebas
tests/Feature/VentaTransaccionTest.php       7 pruebas
tests/Feature/ControlAccesoTest.php          19 pruebas (10 de la matriz rol × módulo + 9)
tests/Feature/ReportesTest.php               12 pruebas
tests/Feature/RecuperarContrasenaTest.php    7 pruebas
tests/Feature/UsabilidadMovilTest.php        22 pruebas (mejoras de la sección 9)
tests/Feature/SeguridadTest.php              13 pruebas (revisión de seguridad, sección 10)
tests/Feature/CargaCatalogoTest.php          4 pruebas (carga del catálogo desde Excel, sección 13)
tests/Feature/NosotrosTest.php               2 pruebas («Nuestra historia», sección 13)
tests/Feature/RespaldoTest.php               5 pruebas (respaldo y recuperación, sección 13)
```
Total al 27/10/2026: 98 pruebas, 357 verificaciones (incluye pruebas menores de otros archivos de `tests/Feature`).

### Cómo ejecutarlas

**Opción A, rápida:** SQLite en memoria, sin crear nada. En `phpunit.xml` descomenta estas dos líneas:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

**Opción B, recomendada para la tesis:** MySQL 8, el mismo motor de producción, donde los bloqueos `FOR UPDATE` son reales. Crea la BD vacía `rialto7_test` y en `phpunit.xml` pon:
```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="rialto7_test"/>
```

Luego ejecuta `php artisan test`. Para correr un solo archivo: `php artisan test --filter=VentaTransaccionTest`.

Cada prueba arranca con la BD vacía (`RefreshDatabase`) y crea sus datos con **los mismos servicios del sistema** (`PedidoService`, `InventarioService`, `CotizacionService`), no con inserciones directas. Así una prueba que pasa demuestra que la regla de negocio real se cumple.

### Qué demuestra cada grupo (útil para el capítulo de pruebas de la tesis)

| Grupo | Casos | Problema del diagnóstico / característica ISO 25010 |
|---|---|---|
| **Cálculo comercial** | subtotal, IVA, total y Bs. con valores calculados a mano · `0,1 × 3 + 0,2 = 0,50` exacto (en float daría 0,5000000000000001) · descuento justo en el mínimo y debajo de él · el IVA va sobre la base ya descontada · cambios de configuración · carrito vacío | Problema 2 (16,67 % de cálculos erróneos) · *Adecuación funcional – exactitud* |
| **Transacción de venta** | descuenta stock y deja movimientos con stock antes/después · **stock insuficiente en una línea revierte todo el pedido**, incluido el producto que sí tenía stock · stock vendido por otra vía entre cotizar y confirmar · confirmar dos veces · anular repone stock · una salida manual nunca deja stock negativo · el vendedor ve el error en pantalla y nada cambia | Regla "nunca vender más del stock" · *Fiabilidad – tolerancia a fallos* (atomicidad ACID) |
| **Control de acceso** | matriz de 10 módulos × 3 perfiles (200 o 403 esperado) · visitante sin sesión → login · el catálogo nunca muestra el costo · producto agotado no entra al carrito · usuario desactivado es expulsado y no puede entrar · login bloqueado tras 5 intentos · un cliente no ve cotizaciones ajenas · un invitado necesita el enlace firmado, que vence · el administrador no puede desactivarse a sí mismo | *Seguridad – confidencialidad, autenticidad* |
| **Reportes** | escenario con 5 pedidos donde solo 2 deben contar (pendiente, anulado y fuera de rango excluidos) · total 580,00 = 104,40 + 475,60 · filtros por vendedor y por producto · ventas por vendedor ordenadas · resumen de inventario valorizado a precio de venta · exportación a Excel con las mismas filas y registro en `reportes` · Excel de inventario con 2 hojas · PDF válido · rango mayor a un año rechazado · el vendedor no puede exportar | Problema 4 (40 % de reportes generados) · *Adecuación funcional – corrección* |
| **Recuperar contraseña** | envía el enlace correcto en español · misma respuesta exista o no el correo · cuenta desactivada no recibe enlace · con el enlace cambia la contraseña y puede ingresar · el enlace sirve una sola vez · token falso o vencido rechazado · contraseña nueva débil rechazada | *Seguridad – autenticidad, confidencialidad* |

> **Nota sobre concurrencia:** PHPUnit corre en un solo proceso, así que no puede lanzar dos ventas *exactamente* a la vez. Las pruebas demuestran la atomicidad (todo o nada) y la revalidación del stock al confirmar. El bloqueo `SELECT … FOR UPDATE` entre dos vendedores simultáneos se puede demostrar a mano en MySQL abriendo dos sesiones de consola: la segunda queda en espera hasta que la primera hace `COMMIT`.

---

## 8. Recuperación de contraseña

```
database/migrations/2024_10_01_000009_create_password_reset_tokens_table.php
app/Http/Controllers/Auth/RecuperarContrasenaController.php
app/Notifications/RestablecerContrasena.php
resources/views/auth/recuperar.blade.php, restablecer.blade.php
```

El enlace **¿Olvidaste tu contraseña?** está en la pantalla de ingreso. El flujo usa el gestor de contraseñas de Laravel (`Password::broker()`), que ya resuelve lo delicado: tokens guardados con hash, vencimiento a los 60 minutos (`auth.passwords.users.expire`), un solo uso y un envío por minuto como máximo por cuenta. Para adaptarlo a la tabla `usuarios`:

- `Usuario::getEmailForPasswordReset()` devuelve `correo` en lugar de `email`.
- `Usuario::sendPasswordResetNotification()` envía `RestablecerContrasena`, un correo en español cuyo enlace lleva `?correo=`.
- Al restablecer, se guarda en `contrasena` (con bcrypt por el cast `hashed`) y se renueva `remember_token`, lo que cierra las sesiones "recordadas" en otros equipos.
- La tabla de tokens conserva el nombre `password_reset_tokens` y la columna `email`, porque así lo exige el repositorio de tokens de Laravel; ahí se guarda el `correo` del usuario.

Además:
- **No revela cuentas:** la respuesta es la misma exista o no el correo, para que nadie pueda probar qué correos están registrados.
- **Cuentas desactivadas:** no reciben enlace.
- **Límites de uso:** `throttle:5,1` al pedir el enlace y `throttle:10,1` al restablecer.

---

## 9. Usabilidad en el celular (crítica de diseño con impeccable)

Una revisión heurística de Nielsen sobre el catálogo, la cotización y el tablero en un celular de 375 px dio **24/40**. Encontró 4 problemas graves (P1) y 1 moderado (P2), corregidos así:

| Problema | Corrección | Archivos |
|---|---|---|
| **P1:** en el celular, la primera pantalla del catálogo eran filtros | Solo el buscador queda visible. Las categorías son chips de un toque y los demás filtros pasan a un panel inferior (`offcanvas-lg`) que en escritorio es una barra lateral. Los filtros activos se quitan con un toque. La tarjeta es horizontal en el celular (foto de 96 px), así que caben unos 4 productos por pantalla en lugar de 1,5. | `catalogo/index`, `partials/producto-card`, `rialto7.css` |
| **P1:** agregar recargaba la página y devolvía al usuario arriba | `fetch` con un aviso breve y el contador actualizado. Sin JavaScript, vuelve a `#producto-ID`. Todos los botones de envío se bloquean mientras esperan ("Enviando…"), para evitar el doble envío con señal lenta. | `layouts/app`, `CarritoController` |
| **P1:** no había forma de hablar con una persona | Nueva clave `telefono_whatsapp` en Configuración (vacía = sin botones). WhatsApp en la barra superior fija, "Preguntar cuándo llega" en los productos agotados y una confirmación de cotización con "Qué sigue", "Guardar en mi WhatsApp" y "Escribir al vendedor". | `Support/Contacto`, `cotizaciones/show`, `admin/configuracion` |
| **P1:** el tablero quedaba vacío si Chart.js no cargaba | El servidor imprime las cifras; los gráficos se dibujan solo `if (window.Chart)`. En el celular los montos van a ancho completo y lo urgente (alertas) va primero. La consulta se pausa con la pestaña oculta. | `gestion/tablero` |
| **P2:** fotos pesadas y todos los productos con el mismo círculo gris | Al subir, se guardan versiones WEBP de 800 y 240 px (con GD, corrigiendo la orientación EXIF) servidas con `srcset`. Sin foto, se muestra una silueta según la categoría. | `ImagenProductoService`, `Producto::urlImagen()`, `public/img/categorias/` |

Otros ajustes:
- Un solo término de cara al cliente, "cotización" (antes se mezclaba con "carrito").
- El cliente lee "Quedan 3" y el personal lee "Bajo stock: 3".
- El precio dice "+ IVA".
- La cédula va en dos partes, con teclado numérico.
- La cantidad se cambia con − / + o escribiéndola, y se guarda sola.
- Se pide confirmación antes de vaciar la cotización.
- El contador se oculta cuando está en 0.
- Hay enlace "Saltar al contenido" y la selección, el cursor y las casillas usan los colores de la marca.

**Comprobación:** revisé `public/vista-previa-catalogo.html` (el mismo marcado y el mismo CSS, con datos del seeder) a 375 px y en escritorio. No hay desbordes horizontales. **Borra ese archivo antes de desplegar.** Para que una cotización sin enviar dure más de 2 horas, sube `SESSION_LIFETIME` en `.env` (por ejemplo, `1440` = 1 día).

### Segunda ronda (crítica: 24/40 → 28/40)

La segunda crítica encontró 1 problema P1 y 3 P2, corregidos así:

| Problema | Corrección |
|---|---|
| **P1:** el WhatsApp no aparece tras instalar, porque el seeder deja el número vacío | Mientras falte, el tablero muestra un aviso con lo que se pierde. El administrador recibe el botón "Configurar WhatsApp" y el vendedor ve "Pídele al administrador". El seeder sigue vacío a propósito: el número real lo escribe la empresa. |
| **P2:** el botón "Filtros" quedaba junto al chip de la categoría "Filtros" | El panel ahora es **"Ajustar"** ("Ajustar búsqueda", "Ver resultados", "Quitar ajustes"). "Filtros" queda solo como nombre de categoría. |
| **P2:** el invitado reescribía sus datos y la cotización vencía en 2 horas | Casilla opcional (desmarcada) "Recordar mis datos en este teléfono", guardados en una cookie cifrada y HttpOnly de 90 días, con "¿No eres…? Borrar mis datos" (`Support/ClienteRecordado`). La lista de productos se respalda en otra cookie cifrada de 30 días y se recupera cuando empieza una sesión nueva; la cookie se valida y nunca se confía en ella (`CarritoService`). |
| **P2:** desde el catálogo se agrega de 1 en 1 | El aviso dice "Agregado: CÓDIGO. Llevas N." y su enlace "Cambiar cantidad" lleva a `#linea-ID`, donde están los botones − / +. |

Observaciones menores también corregidas:
- Sin espacio vacío antes de los resultados en el celular.
- Gráfico de ventas de 14 días en el celular y 30 en escritorio; cambia al girar el teléfono.

---

## 10. Revisión de seguridad y errores

Revisé todo el código a mano, archivo por archivo, porque no había PHP para ejecutar `php -l`. Esto es lo que encontré y cómo quedó:

| # | Hallazgo | Riesgo | Corrección |
|---|---|---|---|
| 1 | **Alto.** Un invitado se identifica solo con su cédula. Cualquiera que escribiera una cédula ajena **reemplazaba el teléfono y la dirección** de ese cliente, y el vendedor llamaba a otra persona. | Suplantación; datos de contacto alterados | Un formulario anónimo ya no modifica la ficha de un cliente existente. Si los datos difieren, se anotan en la cotización ("verificar antes de llamar"). |
| 2 | **Alto.** Registrar una cuenta con una cédula que ya había cotizado como invitado daba acceso a **todo su historial** de cotizaciones y pedidos. | Fuga de datos de otros clientes | Si la ficha tiene historial, la cuenta queda en verificación hasta que un vendedor confirme por teléfono (ver "Verificación de cuentas" abajo). El registro tampoco sobrescribe los datos de la ficha. |
| 3 | **Medio.** Los Excel exportados convertían en **fórmula** cualquier texto que empezara con `=`; los nombres y direcciones los escriben invitados. | Inyección de fórmulas (enlaces maliciosos al abrir el reporte) | El trait `TextoSeguroEnExcel` guarda todo texto como texto en las 3 hojas; números y fechas siguen siendo números. |
| 4 | **Medio.** `exists:productos,id` no considera los borrados lógicos: vender o mover inventario de un producto eliminado pasaba la validación y terminaba en **error 500**. | Error del sistema | `Rule::exists(...)->whereNull('deleted_at')` en `PedidoRequest` (también para el cliente) y `MovimientoInventarioRequest`. |
| 5 | **Bajo.** La consulta de stock en vivo respondía también para productos ocultos del catálogo. | Fuga menor de información | `abort_unless($producto->activo, 404)`, igual que la ficha. |
| 6 | **Bajo.** Los `confirm()` escapaban el texto dos veces (`{{ e(...) }}`): un nombre con apóstrofo se mostraba como `&#039;`. | Mensajes rotos (no llegaba a ejecutar código) | Atributo `data-confirmar` con un solo manejador en el layout. |
| 7 | **Bajo.** Faltaban cabeceras HTTP de seguridad. | Clickjacking, *sniffing*, filtración de enlaces firmados por `Referer` | Middleware `CabecerasSeguridad` (`nosniff`, `SAMEORIGIN`, `Referrer-Policy`, HSTS bajo HTTPS) y `SESSION_SECURE_COOKIE=true` en producción. |

**Lo que revisé y está bien:**
- **SQL:** todo el SQL crudo es estático o usa parámetros enlazados, sin inyección.
- **Vistas:** escapan siempre (`{!! !!}` solo con `nl2br(e(...))`).
- **Formularios y peticiones:** CSRF en todos los formularios y en `fetch`.
- **Datos:** `$fillable` sin campos peligrosos expuestos (rol y stock se asignan explícitamente).
- **Acceso:** autorización por rol en cada grupo de rutas.
- **Archivos:** las fotos se vuelven a codificar con GD, lo que descarta contenido incrustado, y no se admite SVG.
- **Precios:** siempre del servidor.
- **Cuentas:** contraseñas con bcrypt, sesión regenerada al ingresar y límite de intentos en ingreso, registro y recuperación.
- **Relaciones Eloquent:** todas declaran su clave foránea en español (`id_categoria`, `id_cliente`…) y coinciden con las migraciones; los detalles usan `withTrashed()` para no romper documentos históricos.

**Verificación de cuentas (cierra el riesgo de suplantación por cédula):**
- **Cédula nueva:** la cuenta y la ficha se crean juntas.
- **Ficha de invitado sin historial:** se asocia de inmediato, porque no hay nada que proteger.
- **Ficha con cotizaciones o pedidos:** la cuenta queda **en verificación** (columnas `id_usuario_solicitante` y `solicitud_vinculacion_en`, migración `000010`).
  - El cliente ve "Estamos verificando tu cuenta" y puede seguir cotizando.
  - El tablero avisa al personal.
  - En **Gestión → Verificar cuentas**, el vendedor llama al teléfono **de la ficha**, no al de la cuenta nueva, y aprueba o rechaza.
- **Mientras haya una solicitud pendiente,** nadie más puede registrar esa cédula. Si el vendedor la rechaza, la cédula queda libre de nuevo; así, quien registre una cédula ajena ya no bloquea a su dueño de forma permanente.

**Correcciones de la segunda pasada:**

| # | Hallazgo | Corrección |
|---|---|---|
| 8 | **Medio.** El middleware `guest` de Laravel envía a `/home`, ruta que no existe aquí: un usuario conectado que abriera "Ingresar", "Registro" o "Recuperar contraseña" recibía **error 404**. | `RedirectIfAuthenticated` propio: el personal va al tablero y el cliente al catálogo. |
| 9 | **Bajo.** En `RegistroRequest`, `mensajesCliente() + [...]` daba prioridad al mensaje genérico ("Ya existe un cliente…") sobre el del registro, porque con `+` gana el arreglo de la izquierda. | Se invirtió el orden. |
| 10 | **Configuración manual propensa a errores** (editar `Kernel.php` y `auth.php` a mano). | Se entregan los archivos listos (paso 1). |

**Requisito de versión:** `PedidoRequest` y `UsuarioRequest` usan el método `after()` de Form Request, disponible desde Laravel 10.8; `composer create-project laravel/laravel:^10.0` instala una versión posterior.

---

## 11. Estado

**Ejecutado y verificado el 26/10/2026** con PHP 8.2.12 (XAMPP), MariaDB 10.4 y Laravel 12:

- **Pruebas:** `OK (87 tests, 305 assertions)` sobre MySQL real, sin avisos.
- **Migraciones y seeder:** se ejecutan sin errores.
- **Recorrido manual en el navegador, de punta a punta:**
  - Catálogo → ficha → agregar a cotización → enviar como invitado: genera la COT con enlace firmado y su PDF.
  - Ingreso del administrador → tablero → convertir la cotización en pedido → confirmar la venta: el stock baja (35→34, 50→49) y los movimientos quedan registrados.
  - Reportes de ventas e inventario: se ven en pantalla y se exportan a PDF y a Excel.

Correcciones hechas al ejecutarlo:

| # | Hallazgo | Corrección |
|---|---|---|
| 11 | Laravel 10 ya no se puede instalar: Composer lo bloquea por avisos de seguridad. | Migrado a Laravel 12, conservando la estructura de Laravel 10. |
| 12 | La prueba de fórmulas en Excel fallaba. El Excel era correcto, pero maatwebsite deja su binder como global y, al releer el archivo, los números volvían a texto. | La prueba restablece `DefaultValueBinder` antes de leer. |
| 13 | Metadato `@dataProvider` en comentario (deprecado en PHPUnit 11 y eliminado en 12). | Atributo `#[DataProvider]`. |
| 14 | Cada PDF pesaba unos 880 KB porque llevaba la fuente completa incrustada, algo costoso con datos móviles rurales. | `enable_font_subsetting` en `config/dompdf.php`: ahora pesan 25–30 KB. |
| 15 | A 1366 px, la resolución mínima del Cuadro 29, la barra del personal se partía en dos y tres líneas. | La barra del personal se colapsa en `xl`, sin íconos entre 1200 y 1399 px, con "Salir" corto y sin "Mi cotización" (el personal vende desde Pedidos). |
| 16 | En la cotización, el número de cédula se cortaba en la columna lateral. | Parámetro `angosto` en `partials/campos-cliente`: un campo por fila. |
| 17 | El gráfico de 30 días salía vacío porque todas las ventas del seeder caían en el mismo día. | El seeder agrega 16 ventas de mostrador repartidas en 30 días, creadas con `PedidoService`. |
| 18 | En el reporte, la fecha y el número de pedido se partían en dos líneas. | `text-nowrap`. |

Antes de desplegar, cambia las contraseñas de prueba.

---

## 12. Rediseño visual: Tailwind CSS + Alpine.js (26/10/2026)

La interfaz pasó de Bootstrap 5 (por CDN) a **Tailwind CSS 4 + Alpine.js**, compilados con **Vite**. Las rutas, los controladores, los servicios y las 87 pruebas no cambiaron. Toda la lógica sigue en el servidor y cada formulario funciona aunque no cargue JavaScript.

**Mundo visual: el mapa-guía del llano.**
- **Tierra:** zonas de color plano en el verde petróleo del logo (`#0B4A3B`) sobre un fondo crema (el «agua»), separadas por costas orgánicas dibujadas en SVG en lugar de líneas rectas.
- **Cápsulas:** los controles son cápsulas de color. Sol (`#FFD23F`) es la acción principal, coral marca agotado y peligro, laguna da información y lila indica pendiente.
- **Firma:** la cápsula «Mi cotización» va clavada en la costa de la cabecera. En las cápsulas de categoría, el chip de conteo cruza al frente cuando la categoría está activa.
- **Tipografía:** una sola familia, Hanken Grotesk, servida desde el propio portal (sin Google Fonts), con cifras tabulares en precios y cantidades.
- **Personal:** usa un riel verde lateral (su borde derecho es una costa) que cabe sin partirse a 1366 px. En el celular se abre como menú.

**Estructura del frontend**
```
resources/css/app.css          tokens (@theme) y componentes: .btn, .capsula, .campo, .lobulo, .estado, .tabla, .aviso
resources/js/app.js            Alpine + agregar sin recargar, bloqueo de doble envío, confirmaciones, validación en vivo
resources/js/graficos.js       Chart.js empaquetado (el tablero ya no depende de un CDN)
resources/views/components/    <x-icono>, <x-logo>, <x-encabezado>, <x-pestanas>
resources/views/partials/paginacion.blade.php   paginación propia (AppServiceProvider)
```

**Peso:** 11,8 KB de CSS y 21 KB de JS comprimidos. Los gráficos (50 KB) solo se cargan en el tablero.

**Logo:** el portal muestra `public/img/logo.png` si existe; mientras tanto, solo el nombre. Copia ahí el archivo del logo oficial y aparecerá en la cabecera, el riel del personal, el pie y el ícono de la pestaña.

**Instalar en otro equipo:** además de `composer install`, ejecuta `npm install` y `npm run build` (requiere Node 18 o superior). Las pruebas no necesitan la compilación: `TestCase` usa `withoutVite()`.

**Otros cambios:**
- Las cápsulas de categoría muestran cuántos productos activos hay; la caché se limpia cuando un producto cambia de categoría o de visibilidad.
- Las siluetas de producto sin foto ya no traen fondo propio y usan el verde de la marca.
- Se eliminaron `public/css/rialto7.css`, la vista previa estática y la vista `welcome` del esqueleto, que nadie usaba.

---

## 13. Catálogo real, «Nuestra historia» y respaldos (27/10/2026)

**Catálogo real.** El catálogo tiene solo los 17 productos que entregó la empresa (Marchesan Tatu, Colombo Aemco, Vicon y Lumagi), con sus fotos.
- `php artisan catalogo:plantilla carpeta` crea la plantilla `productos.xlsx` y la carpeta `fotos/`.
- `php artisan catalogo:importar productos.xlsx --probar` valida todo sin guardar e indica la fila de cada error; con un solo error no se carga nada.
- Sin `--probar` agrega o actualiza por código de fábrica; la diferencia de existencia queda como movimiento de ajuste. `--reemplazar` borra antes el catálogo con sus pedidos, cotizaciones y movimientos (los usuarios y clientes se conservan).
- Las fotos se asocian por la columna «Foto» o, si falta, por el código del producto.
- Archivos: `app/Support/PlantillaCatalogo.php`, `app/Services/ImportadorCatalogo.php`, `app/Console/Commands/Catalogo*.php`, `tests/Feature/CargaCatalogoTest.php`.

**«Nuestra historia»** (`/nosotros`, `NosotrosController` + `resources/views/nosotros.blade.php`). Es un resumen corto de la reseña de la tesis en cinco bloques:
- **Cifras:** años desde 1963, marcas exclusivas y productos activos, calculados con el catálogo.
- **Línea de vida:** tarjetas con *scroll-snap* que se recorren con flechas, teclado, los años de la pista o deslizando (Alpine). Sin JavaScript se ven todas en una fila desplazable.
- **Pilares:** confianza, atención cercana y conocimiento de cada pieza.
- **Marcas:** logos (`public/img/marcas/`), categorías y cantidad de productos de cada una, con enlace al catálogo filtrado por `?marca=`.
- **Contacto:** dirección, WhatsApp y catálogo.

Los textos están en las listas `$hitos`, `$pilares` y `$cifras` al inicio de la vista. Los momentos sin año confirmado («Oficio», «Alianzas») se cambian por el año cuando la empresa lo indique.

**Respaldo y recuperación** (requisito no funcional del Cuadro 28).
- `RespaldoService` genera un `.zip` en `storage/app/respaldos` con `base-de-datos.sql` (todas las tablas, generado con PHP, sin depender de `mysqldump`) y las fotos de los productos. Conserva los 14 más recientes.
- **Automático:** `app/Console/Kernel.php` programa `respaldo:crear` cada día a las 2:00 a. m. En el hosting basta una tarea Cron por minuto: `php artisan schedule:run`.
- **Panel:** Administración → Respaldos permite crear uno al momento, descargarlo y restaurarlo.
- **Restaurar** reemplaza los datos dentro de una transacción (si algo falla, nada cambia) y antes guarda un respaldo del estado actual. Por consola: `php artisan respaldo:restaurar nombre.zip`. El `.sql` también se puede importar a mano con phpMyAdmin.
- Pruebas: `tests/Feature/RespaldoTest.php` (5 casos: contenido del respaldo, restauración de datos y fotos, archivo inválido, acceso solo del administrador y rotación).

**Producción.** `.env.produccion.example` trae la configuración lista para el hosting (`APP_DEBUG=false`, HTTPS, SMTP, sesión de un día).

---

## 14. Rediseño «La franja de fábrica» y portal completo (27/10/2026)

**Diseño.** La interfaz pasó del «mapa-guía del llano» (costas onduladas y cápsulas) a **la librea de fábrica**, elegida por la usuaria entre varias propuestas: carrocería verde Rialto, una franja verde-blanco-amarillo como firma, botones y paneles rectos con el talón de franjas, números grandes en círculos blancos («ruedos») y la tipografía Barlow / Barlow Condensed autoalojada (`@fontsource`). El sistema completo está en `DESIGN.md` y `.impeccable/design.json`; los colores y componentes en `resources/css/app.css`.

**Lo que se agregó para que sea un portal web completo:**

| Parte | Qué hace | Archivos |
|---|---|---|
| Página de inicio (`/`) | Buscador por código, pizarra del día (tasa, existencias, IVA), equipo estrella, marcas exclusivas, categorías, más pedidos, pasos para cotizar. El catálogo pasó a `/catalogo` | `InicioController`, `inicio.blade.php` |
| Contacto (`/contacto`) | WhatsApp, llamada, cómo llegar y un formulario que abre la consulta en WhatsApp (no guarda datos) | `contacto.blade.php`, `SitioController::contactoWhatsapp` |
| Privacidad y términos | Redactados según lo que el portal hace de verdad (datos, cookies, precios, cotizaciones) | `legal/privacidad`, `legal/terminos` |
| Páginas de error | 403, 404, 419, 429, 500 y 503 propias, sin depender de la base de datos | `resources/views/errors/` |
| Buscadores y redes | Descripción, URL canónica, Open Graph, datos estructurados (Store y Product), `noindex` en páginas privadas, `sitemap.xml` y `robots.txt` dinámicos | `partials/meta.blade.php`, `SitioController` |
| Instalable en el teléfono | `site.webmanifest`, íconos de 192 y 512 px, imagen para compartir | `public/` |

**Funciones nuevas (propuesta aprobada tras el recorrido por los módulos):**
- **Clientes** (personal): directorio con búsqueda por nombre, cédula o teléfono; ficha con historial, totales, WhatsApp, llamada, nueva venta y corrección de datos. `Gestion\ClienteController`.
- **Venta de mostrador** con buscadores de cliente y producto y totales en vivo (mismo cálculo que `CalculadoraComercial`, en centavos; el servidor vuelve a calcular). Registrar un cliente nuevo vuelve a la venta con él elegido.
- **Carga desde Excel en el panel**: plantilla descargable, subida del Excel y las fotos, revisión previa (con un error no se carga nada) y confirmación; «Reemplazar todo» crea antes un respaldo. `Admin\ImportacionController`.
- **Búsqueda y WhatsApp** en cotizaciones y pedidos; **contadores** de lo pendiente en el menú del personal.
- **Catálogo**: orden (disponibles, precio, nombre) y sugerencias mientras se escribe (`/catalogo/sugerencias`).
- **Ficha**: copiar código y compartir por WhatsApp. **Mi cuenta**: «Repetir» cotización con los precios de hoy.
- **Tablero**: accesos rápidos. **Reportes**: períodos de un toque (hoy, semana, mes, mes pasado, año).

**Pruebas:** `tests/Feature/PortalCompletoTest.php` (12 casos). Total al 27/10/2026: **111 pruebas, 459 verificaciones**, todas aprobadas.

## 15. Estilo «Azul de campo» (27/10/2026)

A pedido de la usuaria, el portal toma el estilo y las animaciones de cicmelinst.com, con los colores de Marchesan (es.marchesan.com.br). El verde del logo Rialto queda como acento de marca. Las reglas del diseño están en `DESIGN.md`.

- **Colores:**
  - azul principal `#0D5192`, azul profundo `#052B70` y azul noche `#031C46`;
  - amarillo `#FFD300` para la acción principal;
  - verde Rialto `#0B4A3B` solo en el logo y el sello «desde 1963».
- **Letra:** Raleway variable. Los titulares van en mayúsculas, con una palabra resaltada y un subrayado amarillo.
- **Portada:**
  - cinco fotos a pantalla completa que se relevan cada 6 s con un zoom lento: la fachada del Edificio Campagna y cuatro fotos de campo;
  - arcos azules, cifras que cuentan e indicador «Desliza»;
  - la tasa del día aparece junto a «Los más pedidos».
- **Movimiento:**
  - las secciones entran subiendo al desplazarse (`data-revelar`) y las cifras cuentan (`data-contar`);
  - con «reducir movimiento» activado, todo se muestra de una vez y la portada no rota.
- **Fotos:**
  - están en `public/img/portada/`: la fachada y el equipo, enviados por la empresa, y las fotos de campo de Unsplash;
  - los créditos de las fotos de campo están en `public/img/portada/CREDITOS.txt`.
- **Nuestra historia:** la fachada de la sede junto al resumen, y la foto del equipo junto a los pilares.
- **Gestión:** el mismo azul y amarillo, sin fotos ni animaciones de entrada. Los gráficos y los PDF también pasaron al azul.

**Pruebas:** 111 pruebas, 460 verificaciones, todas aprobadas.
