# AGENTS.md — Contexto del Proyecto gestionTI2026

> Este archivo contiene la información esencial que cualquier agente de IA necesita para trabajar efectivamente en este proyecto. Si modificas la arquitectura, stack o convenciones, actualiza este archivo.

---

## 1. Visión General

**gestionTI2026** es un sistema de gestión interno modular desarrollado para la Subgerencia de Desarrollo Agrícola del Proyecto Especial Chavimochic (PECH), Gobierno Regional La Libertad.

El módulo más desarrollado y funcional es **Producción Agraria**, que gestiona inventario, ventas, proformas, vouchers de pago, reportes y un chatbot de IA.

---

## 2. Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| **Backend** | PHP puro (7.4+, sin framework MVC) |
| **Base de datos** | Microsoft SQL Server (`sqlsrv` extension) |
| **Frontend** | PHP + HTML + CSS + JavaScript vanilla |
| **UI Framework** | [Tabler](https://tabler.io/) (basado en Bootstrap 5) vía CDN |
| **Iconos** | Tabler Icons vía CDN |
| **Notificaciones** | SweetAlert2 v11 |
| **Gráficos** | ApexCharts (solo en reportes) |
| **PDF** | jsPDF + AutoTable |
| **Tablas avanzadas** | DataTables + Select2 (solo en módulo usuarios) |
| **Build tools** | Ninguno. Sin `package.json`, sin `composer.json` |

### CDN principales (cargados en `public/header.php`)
- `tabler/css/tabler.min.css`
- `tabler/icons-webfont/tabler-icons.min.css`
- `sweetalert2/sweetalert2.min.css`
- `sweetalert2/sweetalert2.min.js`
- `tabler/core/dist/js/tabler.min.js`

---

## 3. Arquitectura del Proyecto

### Front Controller
`index.php` en la raíz es el **único punto de entrada**. Todas las URLs pasan por aquí.

```
URL amigable: /produccion_agraria/inventario
  → index.php?route=produccion_agraria/inventario
  → $module = 'produccion_agraria', $action = 'inventario'

URL clásica:  /index.php?module=produccion_agraria&action=guardar_producto
  → $module = 'produccion_agraria', $action = 'guardar_producto'
```

### Router (`index.php`)
1. Parsea `$_GET['route']` o fallback a `$_GET['module']`/`$_GET['action']`
2. Determina `$module` y `$action`
3. Si es auth/login → renderiza directamente sin sesión
4. Si no → `Auth::check()` valida sesión y permisos
5. Detecta si es **AJAX** mediante lista blanca `$acciones_ajax`
6. Si es AJAX → **NO incluye** `header.php` ni `footer.php`, devuelve JSON y hace `exit`
7. Si es vista → incluye `public/header.php` + controlador + `public/footer.php`

### Estructura de cada módulo
```
modules/<nombre_modulo>/
  controllers/<Nombre>Controller.php    # Lógica de peticiones
  models/<Nombre>Model.php              # Lógica SQL y BD
  views/
    index.php                           # Vista principal
    subvistas.php                       # Vistas adicionales si aplica
  assets/
    css/                                # Estilos propios del módulo
    js/                                 # Scripts propios (si hay)
  database/
    *.sql                               # Scripts de migración/schema
```

### Convenciones de nomenclatura
- **Clases:** PascalCase (`InventarioModel`, `PuntoVentaController`)
- **Métodos:** camelCase (`guardarProducto`, `listarVentasHoy`)
- **Variables/archivos:** snake_case o lowercase
- **Sin namespaces ni autoloading PSR-4.** Todo se carga con `require_once`
- **Conexión BD:** Variable global `$conn` (instanciada en `index.php` y pasada a modelos)

---

## 4. Sistema de Autenticación y Permisos

- **Clase `core/Auth.php`:**
  - `Auth::check()`: Valida sesión activa (`$_SESSION['usuario_id']`)
  - Consulta `comun.Permisos` + `comun.Modulos` para validar acceso
  - Redirección a `/dashboard?error=access_denied` si no tiene permiso
- **Roles:** `ADMIN` y otros. Solo `ADMIN` accede a `usuarios` y `sistemas`
- **Menú dinámico:** `public/header.php` consulta `comun.Modulos` + `comun.Permisos` para armar la navbar
- **Variables de sesión:** `$_SESSION['usuario_id']`, `['usuario_nombre']`, `['usuario_rol']`

---

## 5. Patrón AJAX vs Vista

### Vista (render HTML)
```php
$productos = $model->listarProductos();
include __DIR__ . '/../views/inventario/index.php';
```

### AJAX (devolver JSON)
```php
if ($action == 'guardar_producto') {
    header('Content-Type: application/json; charset=utf-8');
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $result = $model->guardarProducto($data);
    echo json_encode($result);
    exit;  // ← CRÍTICO: evitar que se incluya header/footer
}
```

**REGLA CRÍTICA:** Si agregas una nueva acción AJAX, debe registrarse en el array `$acciones_ajax` de `index.php` (raíz).

---

## 6. Manejo de BLOBs / Imágenes

El sistema almacena imágenes como **BLOB en SQL Server** (no en disco ni en cloud).

### Flujo Frontend → Backend → BD
1. **Frontend:** `FileReader.readAsDataURL(file)` → obtiene Base64
2. **Envío:** `fetch` con JSON enviando `imagen_base64` + `imagen_nombre`
3. **Backend:** `base64_decode($data['imagen_base64'])` → binario
4. **Modelo:** Parámetro tipado para `VARBINARY(MAX)`:
   ```php
   $blobParam = [
       $data['imagen_blob'],
       SQLSRV_PARAM_IN,
       SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY),
       SQLSRV_SQLTYPE_VARBINARY('max')
   ];
   ```
5. **BD:** Guarda en columna `VARBINARY(MAX)`

### Flujo de lectura (visualización)
- Endpoint `ver_imagen_producto` recupera el blob
- Detecta MIME type por extensión del nombre original
- Devuelve con `Content-Type: image/jpeg|png|...` y `Content-Disposition: inline`
- Si no hay imagen → devuelve GIF transparente 1x1 para evitar ícono roto

### Tablas con BLOBs
| Tabla | Columnas BLOB |
|-------|--------------|
| `producto` | `imagen_nombre`, `imagen_blob` |
| `voucher_deposito` | `url_imagen`, `archivo_blob` |

---

## 7. Sistema de Ventas y Stock (FIFO)

### Flujo de una venta
1. **Punto de Venta** crea una **proforma** (`transaccion` con `estado = 'PENDIENTE'`)
2. Stock se descuenta inmediatamente de los lotes más antiguos (FIFO)
3. **Bandeja de Entrada** lista proformas pendientes
4. Al **procesar** la proforma: se asigna `metodo_pago`, `serie_comprobante`, `correlativo_comprobante`, `id_voucher`, estado → `PROCESADO`
5. Al **anular**: se revierte stock a lotes y se registra movimiento de reintegración en kardex

### Método de descuento FIFO
1. Obtiene lotes del producto ordenados por `fecha_creacion ASC, id_lote ASC`
2. Descuenta `cantidadPendiente` iterando lotes desde el más antiguo
3. Actualiza `lote.stock_actual`
4. Registra movimiento en `kardex` (tipo_movimiento = 'VENTA')
5. Inserta `transaccion_detalle` vinculado al `id_lote` usado

### Tipos de Precio
| Tipo | Descripción |
|------|-------------|
| **Fijo** | Precio definido manualmente |
| **UIT** | `valor_uit(año) * porcentaje_uit` |
| **Variable** | Último precio de `historial_precio` (más reciente) |

---

## 8. Configuración del Proyecto

### Archivos clave
| Archivo | Propósito |
|---------|-----------|
| `config/config.php` | Define `BASE_URL`, carga `.env`, helper `env()` |
| `config/db.php` | Clase `Conexion` con `sqlsrv_connect()` |
| `config/db.php.example` | Plantilla de conexión |
| `.env` | `OPENCODE_API_KEY`, `OPENCODE_MODEL` |

### Variables importantes
- `BASE_URL`: Usado en redirecciones, links del menú, assets CSS
- `OPENCODE_API_KEY`: Para el chatbot IA integrado
- Conexión SQL Server: `10.0.100.252`, base `BD_GESTION_TI` y `BD_PRODUCCIONDESARROLLO`

---

## 9. Base de Datos — Esquemas Principales

### `comun` (Sistema Core)
| Tabla | Propósito |
|-------|-----------|
| `Usuarios` | Usuarios del sistema (con `password_hash`) |
| `Modulos` | Módulos registrados del menú |
| `Permisos` | Relación usuario-módulo (`pueden_ver`, `pueden_editar`) |

### `BD_PRODUCCIONDESARROLLO.dbo` (Producción Agraria)
| Tabla | Propósito |
|-------|-----------|
| `producto` | Catálogo (con imagen BLOB, tipo_precio, UIT) |
| `clase` | Clasificación de productos |
| `centro_produccion` | Ubicaciones/centros agrarios |
| `uit` | Valor UIT por año |
| `cliente` | Clientes (DNI/RUC, nombre_rs, tipo_cliente) |
| `lote` | Lotes de producto con `stock_actual` |
| `kardex` | Movimientos de inventario |
| `historial_precio` | Precios variables por fecha |
| `transaccion` | Ventas/proformas (encabezado) |
| `transaccion_detalle` | Items de cada venta |
| `voucher_deposito` | Vouchers de pago (con archivo BLOB) |

---

## 10. Módulos del Sistema

| Módulo | Estado | Descripción |
|--------|--------|-------------|
| **auth** | ✅ Activo | Login/logout con sesiones PHP |
| **dashboard** | ✅ Activo | Página de inicio |
| **produccion_agraria** | ✅ **Principal** | Inventario, ventas, proformas, vouchers, reportes, chatbot IA |
| &nbsp;&nbsp;↳ inventario | Submódulo | CRUD productos, lotes, kardex, mermas, precios, imágenes BLOB |
| &nbsp;&nbsp;↳ punto_venta | Submódulo | Punto de venta/proforma con descuento FIFO |
| &nbsp;&nbsp;↳ bandeja | Submódulo | Proformas pendientes, procesamiento, vouchers |
| &nbsp;&nbsp;↳ tablas | Submódulo | Catálogos: clases, centros, UIT, clientes |
| &nbsp;&nbsp;↳ reportes | Submódulo | Dashboard con gráficos, reportes varios |
| &nbsp;&nbsp;↳ consultas | Submódulo | Chatbot IA con Tool Calling |
| **usuarios** | ✅ Activo | CRUD usuarios y permisos. Solo ADMIN |
| **sistemas** | ✅ Activo | Registro de módulos + **fábrica de código** que genera MVC automáticamente. Solo ADMIN |
| **consultas** | ✅ Activo | Controlador del chatbot IA |
| adquisiciones | 📝 Placeholder | Plantilla MVC mínima |
| agricola | 📝 Placeholder | Plantilla MVC mínima |
| certificados | 📝 Placeholder | Plantilla MVC mínima |
| inventario (raíz) | 📝 Placeholder | Plantilla MVC mínima |
| patrimonio | 📝 Placeholder | Plantilla MVC mínima |
| reportestecnicos | 📝 Placeholder | Plantilla MVC mínima |
| salas | 📝 Placeholder | Plantilla MVC mínima |

---

## 11. Chatbot / IA Integrada ("Consultas IA")

El módulo `consultas` implementa un asistente virtual tipo chatbot que responde preguntas del usuario consultando la base de datos en tiempo real mediante un patrón de **Tool Calling simulado** (no nativo de la API OpenAI, sino por prompt engineering con emisión de JSON).

### 11.1. Arquitectura del módulo

```
modules/consultas/
  controllers/ConsultasController.php   # Orquestador: recibe mensajes, llama API, ejecuta tools
  models/ChatToolsModel.php             # 12 tools de consulta SQL READ-ONLY
  views/index.php                        # Interfaz de chat con JS vanilla + localStorage
```

### 11.2. Routing y delegación

El módulo `consultas` **no se accede directamente** como `module=consultas`. La vista y todos los endpoints AJAX se despachan a través del módulo `produccion_agraria`, que delega a `ConsultasController`:

- `index.php` → `module=produccion_agraria` → `Produccion_agrariaController.php` (line 99-114) → `case 'consultas'` / `case 'chat_enviar'` / `case 'tool_*'` → `include 'modules/consultas/controllers/ConsultasController.php'`

URL de acceso: `/produccion_agraria/consultas`

### 11.3. API de IA

| Parámetro | Valor |
|-----------|-------|
| **Endpoint** | `https://opencode.ai/zen/v1/chat/completions` |
| **Modelo** | `deepseek-v4-flash-free` (configurable en `.env` → `OPENCODE_MODEL`) |
| **API Key** | `.env` → `OPENCODE_API_KEY` |
| **Temperature** | `0.3` |
| **Max tokens** | `1024` |
| **Timeout cURL** | 60 segundos |
| **Errores detectados** | `401` con `CreditsError` → "Sin créditos"; cURL errors; JSON parse errors |

### 11.4. Patrón Tool Calling Simulado (Dos Llamadas)

El modelo NO tiene acceso nativo a function calling. En su lugar se usa prompt engineering:

**System Prompt** (`ConsultasController.php:23-48`): Instruye al modelo a emitir **exactamente un JSON raw** con `{"tool": "nombre", "params": {...}}` cuando necesite datos de la BD. Si no requiere datos, responde normalmente. Reglas estrictas:
- No markdown, no backticks, no explicaciones, no saludos cuando emite tool call.
- Solo el JSON puro.

**Flujo de dos llamadas** (`chat_enviar`, linea 217-301):

```
LLAMADA 1: [system prompt] + [historial 8 msgs] + [mensaje usuario]
  ↓
  El modelo decide: ¿necesito consultar BD?
  ├── NO → Responde directamente como texto → se muestra al usuario
  └── SÍ → Emite JSON tool call
       ↓
       Backend extrae JSON (3 intentos de parseo, ver §11.7)
       ↓
       Ejecuta tool en ChatToolsModel → obtiene resultados SQL
       ↓
LLAMADA 2: [misma conversación] + [respuesta JSON del modelo como assistant]
           + ["Resultado de la consulta: ... Por favor resume estos datos..."]
  ↓
  Modelo genera respuesta en lenguaje natural con los datos
  ↓
  Respuesta final se envía al frontend con: {success, respuesta, tool_usada, resultado_raw}
```

### 11.5. Las 12 Tools (ChatToolsModel.php)

Todas las queries son **READ-ONLY**, usan **prepared statements** con `sqlsrv_query($db, $sql, $params)`, y tienen límite `TOP 20` o `TOP 30`.

| # | Tool | Acción | Params | Tablas consultadas |
|---|------|--------|--------|--------------------|
| 1 | `consultar_stock` | Stock actual > 0 de productos con `maneja_stock=1` | `producto`, `clase`, `centro` | `lote`, `producto`, `clase`, `centro_produccion` |
| 2 | `consultar_ventas` | Ventas/donaciones por período | `fecha_desde`, `fecha_hasta`, `estado`, `cliente`, `metodo_pago` | `transaccion`, `cliente`, `centro_produccion` |
| 3 | `consultar_proformas` | Proformas registradas | `estado`, `cliente`, `fecha_desde` | `transaccion`, `cliente`, `centro_produccion` |
| 4 | `consultar_vouchers` | Vouchers de depósito con monto asignado | `fecha_desde`, `fecha_hasta`, `asignado` (si/no) | `voucher_deposito`, `transaccion` |
| 5 | `consultar_productos` | Catálogo con precio vigente calculado | `clase`, `centro`, `tipo_precio`, `nombre` | `producto`, `clase`, `centro_produccion`, `historial_precio`, `uit` |
| 6 | `consultar_clientes` | Directorio con total acumulado | `nombre`, `tipo` (Planilla/Externo) | `cliente`, `transaccion` |
| 7 | `consultar_mermas` | Pérdidas con valor monetario estimado | `fecha_desde`, `fecha_hasta`, `producto` | `kardex`, `lote`, `producto`, `clase`, `centro_produccion`, `historial_precio`, `uit` |
| 8 | `consultar_kardex` | Movimientos de inventario (INGRESO, VENTA, MERMA, REINTEGRO) | `producto`, `tipo_movimiento`, `fecha_desde`, `fecha_hasta` | `kardex`, `lote`, `producto`, `clase`, `centro_produccion` |
| 9 | `consultar_top_productos_vendidos` | Ranking por unidades o monto (solo PROCESADO) | `fecha_desde`, `fecha_hasta`, `centro`, `orden` (cantidad/monto), `limite` (1-20, default 10) | `transaccion_detalle`, `producto`, `transaccion`, `clase`, `centro_produccion` |
| 10 | `consultar_valorizacion_inventario` | Valor monetario del stock (precio × stock) | `centro`, `clase`, `producto` | `lote`, `producto`, `clase`, `centro_produccion`, `historial_precio`, `uit` |
| 11 | `consultar_ventas_por_mes` | Tendencia mensual (monto + conteo) | `meses` (1-24, default 6), `centro`, `metodo_pago` | `transaccion`, `centro_produccion` |
| 12 | `consultar_vouchers_saldo` | Vouchers con saldo restante calculado | `fecha_desde`, `fecha_hasta`, `saldo_estado` (positivo/cero) | `voucher_deposito`, `transaccion` |

### 11.6. Cálculo de precio vigente

Las tools 5, 7 y 10 calculan el precio en tiempo real usando la misma lógica que el sistema:

```sql
CASE
  WHEN tipo_precio = 'UIT' AND porcentaje_uit IS NOT NULL
    THEN valor_uit * porcentaje_uit          -- UIT actual × porcentaje del producto
  WHEN tipo_precio = 'Variable'
    THEN último precio de historial_precio    -- Más reciente por fecha_registro
  ELSE NULL
END AS precio_vigente
```

El `valor_uit` se obtiene de `BD_PRODUCCIONDESARROLLO.dbo.uit` filtrando por `anio = YEAR(GETDATE())`.

### 11.7. Extracción de JSON (3 intentos de parseo)

Función `procesarToolCall()` (`ConsultasController.php:117-212`):

1. **Intento 1:** `json_decode($content)` — el contenido completo es JSON puro.
2. **Intento 2:** Regex `/\{\s*"tool"\s*:[^}]+\}/s` — busca un objeto JSON simple con `"tool"`.
3. **Intento 3:** Regex `/\{[\s\S]*?"tool"[\s\S]*?\}/s` — busca cualquier objeto JSON profundo que contenga `"tool"` (maneja modelos de razonamiento que anidan JSON dentro de texto).

Cada intento valida que el JSON decodificado tenga las claves `tool` y `params`.

### 11.8. Endpoints AJAX

Todos registrados en `$acciones_ajax` de `index.php:79-83` y en `$acciones_ajax_bandeja` de `Produccion_agrariaController.php:9-13`:

| Endpoint | Propósito | Respuesta |
|----------|-----------|-----------|
| `chat_enviar` | **Principal**: recibe `{mensaje, historial}`, ejecuta flujo de 2 llamadas | `{success, respuesta, modelo, tool_usada, resultado_raw}` |
| `tool_stock` a `tool_vouchers_saldo` | **Individuales**: 12 endpoints para llamar cada tool directamente | `{success, data: [...]}` |

### 11.9. Frontend (views/index.php)

- **UI:** Chat estilo burbujas con gradiente PECH azul (`#004d99` → `#0070cc`).
- **Historial:** Persistido en `localStorage` bajo clave `chat_historial_pech`. Máximo 8 mensajes recientes enviados al backend.
- **Indicadores visuales:**
  - `mostrarTyping()`: animación de 3 dots "Pensando..." durante llamada API.
  - `mostrarConsultandoBD()`: indicador "Consultando base de datos..." con 800ms de delay cuando se usó una tool (simula latencia de consulta).
- **Renderizado de tablas:** Si `resultado_raw` contiene `{columns, rows}`, se renderiza una `<table>` HTML (Tabler-styled) con valores monetarios formateados (`S/ X.XX`) y cantidades sin decimales.
- **Renderizado markdown:** Conversión manual (sin librería) de `**negrita**`, `*cursiva*`, `` `codigo` ``, bloques ` ``` `, y tablas markdown (`| col |`) a HTML.
- **Limpieza:** Botón con confirmación SweetAlert2 que borra `localStorage` y DOM (conserva mensaje de bienvenida inicial).
- **Fetch:** Llama a `module=produccion_agraria&action=chat_enviar` (no a `module=consultas` directamente).

### 11.10. Configuración requerida

El chatbot **no funciona** sin estas variables en `.env`:
```
OPENCODE_API_KEY=sk-...
OPENCODE_MODEL=deepseek-v4-flash-free
```

Si la API key está vacía, el endpoint `chat_enviar` devuelve `{success: false, message: "API key no configurada"}`.

### 11.11. Manejo de errores

- **CreditError (401):** Detecta `error.type === 'CreditsError'` y muestra mensaje amigable: "Sin créditos disponibles... recarga tu saldo en opencode.ai/billing".
- **HTTP 429 (rate limit API):** Detectado y mostrado como error amigable.
- **cURL errors:** Se loguean con `error_log('[Chatbot] ...')` y se retorna mensaje genérico.
- **JSON parse errors:** Idem, con `json_last_error_msg()`.
- **Tool call fallido (JSON no parseable):** Si el contenido parece un tool call pero los 3 intentos de parseo fallan, se muestra mensaje "Entendí que necesitas consultar datos, pero tuve dificultades... ¿Podrías reformular?" en lugar de mostrar JSON crudo al usuario.
- **Tool inválida (no en whitelist):** Si el modelo emite una tool no registrada, se loguea y responde con mensaje genérico.
- **PHP Throwable:** El `try/catch` global captura cualquier excepción, limpia buffers y devuelve JSON 500.

### 11.12. Mejoras implementadas (2026-06-04)

#### Seguridad
| Mejora | Archivo | Detalle |
|--------|---------|---------|
| **SSL verification** | `ConsultasController.php` | Habilitado por defecto. Deshabilitar solo con `CHAT_SSL_VERIFY=false` en `.env` |
| **Rate limiting** | `ConsultasController.php` | 20 requests/min por sesión PHP. Bloquea con HTTP 429 y frontend muestra countdown |
| **Tool whitelist** | `ConsultasController.php` | `$TOOLS_VALIDAS` valida que solo las 12 tools registradas se ejecuten |
| **`.env.example`** | raíz | Template sin credenciales reales (`.env` ya en `.gitignore`) |

#### Robustez
| Mejora | Archivo | Detalle |
|--------|---------|---------|
| **Acciones AJAX unificadas** | `core/ChatActions.php` | Archivo centralizado referenciado por `index.php` y `Produccion_agrariaController.php` con `...require`. Solo editar un lugar al agregar tools |
| **Detección tool call fallido** | `ConsultasController.php` | `pareceToolCallFallido()` evita mostrar JSON crudo al usuario |
| **Sin `$GLOBALS`** | `ConsultasController.php` | El system prompt se usa como variable local en vez de `$GLOBALS` |

#### UX / Frontend
| Mejora | Archivo | Detalle |
|--------|---------|---------|
| **Feedback visual específico** | `views/index.php` | Badge con nombre de tool (ej. "Stock de productos") en burbuja del bot |
| **Quick prompts** | `views/index.php` | 6 chips clickeables: Stock, Ventas, Top productos, Proformas, Valor inventario, Mermas |
| **Dark mode** | `views/index.php` | Soporte `prefers-color-scheme: dark` con variables CSS |
| **Rate limit UI** | `views/index.php` | Al recibir 429, muestra mensaje y deshabilita input por `retry_after` segundos |

---

## 12. Reglas Críticas para Desarrollo

1. **Siempre pasar por `index.php`**. No acceder directamente a controladores.
2. **Nuevas acciones AJAX** deben registrarse en `$acciones_ajax` de `index.php` (raíz).
3. **En controladores AJAX**, usar `ob_clean()` o limpiar buffers antes de `header('Content-Type: application/json')`.
4. **No iniciar sesiones** en controladores sin verificar que `core/Auth.php` ya lo hizo.
5. **Usar prepared statements** (`sqlsrv_query` con parámetros) para todas las consultas.
6. **Cambios de schema** documentarlos en archivos `.sql` bajo `modules/<modulo>/database/`.
7. **Módulos placeholder** solo tienen estructura mínima generada por la fábrica de `sistemas`.
8. **No hay migraciones automáticas.** Los scripts SQL deben ejecutarse manualmente.

---

## 13. Submódulo Inventario (produccion_agraria/inventario)

El submódulo de inventario es el corazón del sistema de Producción Agraria. Gestiona el catálogo de productos con imágenes BLOB, lotes con FIFO, movimientos de kardex, mermas e historial de precios.

### 13.1. Routing (doble nivel)

```
Browser: /produccion_agraria/inventario
  → root index.php: $module='produccion_agraria', $action='inventario'
  → Produccion_agrariaController.php (switch $action)
  → case 'inventario' → require_once InventarioController.php
```

`Produccion_agrariaController.php` delega las siguientes acciones a `InventarioController`:
`inventario`, `obtener_producto`, `guardar_producto`, `eliminar_producto`, `obtener_lotes`, `obtener_kardex`, `guardar_lote`, `guardar_merma`, `obtener_precio_actual`, `guardar_precio`, `ver_imagen_producto`

### 13.2. Controller: `InventarioController.php` (170 líneas)

| # | Action | HTTP | AJAX | Propósito |
|---|--------|------|------|-----------|
| 1 | `listar` (default) | GET | No | Renderiza vista principal con productos, clases, centros y UIT |
| 2 | `guardar_producto` | POST | Sí | Crea/actualiza producto. Decodifica `imagen_base64` → BLOB |
| 3 | `eliminar_producto` | POST | Sí | Elimina producto. Captura FK 547 con mensaje amigable |
| 4 | `obtener_producto` | GET | Sí | Retorna un producto en JSON para formulario de edición |
| 5 | `obtener_lotes` | GET | Sí | Retorna `{lotes, stock_total}` para modal de detalle |
| 6 | `obtener_kardex` | GET | Sí | Retorna `{movimientos}` para pestaña kardex del detalle |
| 7 | `guardar_lote` | POST | Sí | Crea nuevo lote + movimiento INGRESO (transaccional) |
| 8 | `guardar_merma` | POST | Sí | Registra merma: valida cantidad ≤ stock, decrementa, inserta kardex (transaccional) |
| 9 | `obtener_precio_actual` | GET | Sí | Último precio de `historial_precio` para el formulario |
| 10 | `guardar_precio` | POST | Sí | Inserta nuevo registro en `historial_precio` |
| 11 | `ver_imagen_producto` | GET | Sí | Sirve imagen BLOB con Content-Type según extensión. Fallback a 1x1 GIF |

### 13.3. Model: `InventarioModel.php` (511 líneas, 20 métodos)

Todas las queries usan el esquema `BD_PRODUCCIONDESARROLLO.dbo.`

#### CRUD Productos (4)
| Método | Tablas | Propósito |
|--------|--------|-----------|
| `listarProductos()` | `producto` LEFT JOIN `clase`, `centro_produccion` | Catálogo completo ordenado por `id_producto` |
| `obtenerProducto($id)` | `producto` LEFT JOIN `clase`, `centro_produccion` | Producto individual por ID |
| `guardarProducto($data)` | `producto` | INSERT/UPDATE. Maneja 3 ramas de imagen: eliminar, nueva (BLOB), sin cambios. Anula `porcentaje_uit` si `tipo_precio != 'UIT'` |
| `eliminarProducto($id)` | `producto` | DELETE. Captura error FK 547 |

#### Dropdowns (2)
| Método | Tabla | Propósito |
|--------|-------|-----------|
| `listarClasesSelect()` | `clase` | `id_clase, nombre_clase` para selects |
| `listarCentrosSelect()` | `centro_produccion` | `id_centro, nombre_centro` para selects |

#### Lotes / FIFO / Kardex (5)
| Método | Tablas | Propósito |
|--------|--------|-----------|
| `listarLotesPorProducto($id)` | `lote` | Lotes activos (`stock_actual > 0`) ordenados `fecha_creacion ASC` (FIFO). Calcula `antiguedad_dias`, `estado_texto` (Agotado/Stock Critico/Activo) |
| `listarMovimientosKardex($id)` | `kardex` LEFT JOIN `lote` | Historial de movimientos ordenado por `fecha DESC` |
| `obtenerStockTotal($id)` | `lote` | `SUM(stock_actual)` para un producto |
| `guardarLote($data)` | `lote`, `kardex` | Transaccional: INSERT lote → obtiene ID → INSERT movimiento INGRESO |
| `guardarMerma($data)` | `lote`, `kardex` | Transaccional: valida cantidad ≤ stock → decrementa `lote.stock_actual` → INSERT movimiento MERMA |

#### UIT (1)
| Método | Tabla | Propósito |
|--------|-------|-----------|
| `obtenerUITActual()` | `uit` | Valor UIT del año actual (`date('Y')`) |

#### Historial de Precios (3)
| Método | Tabla | Propósito |
|--------|-------|-----------|
| `obtenerPrecioActual($id)` | `historial_precio` | TOP 1 más reciente por `fecha_registro` |
| `listarHistorialPrecios($id)` | `historial_precio` | Historial completo ordenado descendente |
| `guardarPrecio($data)` | `historial_precio` | INSERT con `fecha_registro = GETDATE()` |

#### Imágenes BLOB (3)
| Método | Tabla | Propósito |
|--------|-------|-----------|
| `obtenerImagenProducto($id)` | `producto` | Primario: `sqlsrv_get_field()` con `SQLSRV_ENC_BINARY` |
| `obtenerImagenProductoBase64($id)` | `producto` | Fallback: `CAST(imagen_blob AS VARCHAR(MAX))` → hex → `hex2bin()` |
| `eliminarImagenProducto($id)` | `producto` | UPDATE `imagen_nombre = NULL, imagen_blob = NULL` |

### 13.4. Vista: `views/inventario/index.php` (1878 líneas)

Vista monolítica que contiene HTML + CSS inline + JS inline en un solo archivo.

#### Estructura HTML
| Sección | Elemento/ID | Descripción |
|---------|-------------|-------------|
| Breadcrumb | `.breadcrumb` | Prod. Agraria → Inventario |
| Filtros | `#filtro-centro`, `#filtro-clase`, `#buscar-global`, `#btn-stock-critico` | Dropdowns de centro y clase, búsqueda por nombre, toggle stock crítico |
| Tabla productos | `#tabla-productos` | 8 columnas: Imagen (48px avatar), Producto (nombre + código), Nombre Científico, Clase, Centro, Unidad, Maneja Stock (badge), Acciones. Filas con atributos `data-centro`, `data-clase`, `data-nombre` para filtrado client-side |

#### Modales (7)
| Modal | ID | Propósito |
|-------|-----|-----------|
| Formulario Producto | `#modal-producto` | CRUD completo: nombre, nombre_científico, unidad_medida, maneja_stock, clase, centro, tipo_precio, porcentaje_uit, precio_calculado, imagen |
| Preview Imagen | `#modal-preview-imagen` | Visualización a tamaño completo (max-height 400px) |
| Detalle Producto | `#modal-detalle-producto` | Modal grande (xl) con tabs de Lotes Activos y Kardex, botones exportar PDF/Excel |
| Nuevo Lote | `#modal-nuevo-lote` | Formulario: código_lote, stock_inicial, centro |
| Reportar Merma | `#modal-reportar-merma` | Método exacto/porcentual, tipo_merma, motivo |
| Reajuste Stock | `#modal-reajuste-stock` | Ajuste administrativo (WIP) |
| Merma Rápida | `#modal-merma` | Legacy, aparentemente superado por `modal-reportar-merma` |

#### JavaScript inline (~1150 líneas, 26 funciones)
| Grupo | Funciones clave |
|-------|----------------|
| CRUD Producto | `editarProducto()`, `handleSubmitProducto()`, `eliminarProducto()`, `limpiarFormProducto()` |
| Imágenes | `fileToBase64()`, `handleImagenChange()`, `handleEliminarImagenChange()`, `mostrarPreviewImagen()` |
| Precios | `togglePorcentajeUIT()`, `calcularPrecioUIT()`, `cargarPrecioActual()` |
| Lotes/Kardex | `mostrarLotes()`, `mostrarModalNuevoLote()`, `guardarLote()` |
| Mermas | `mostrarModalMerma()`, `guardarMerma()`, `calcularMermaPorcentual()` |
| Filtros | `aplicarFiltros()` (debounce 300ms), `toggleStockCritico()` |
| Exportación | `exportarDetalleProductoPDF()`, `exportarDetalleProductoExcel()` |
| Utilidades | `debugLog()`, `getClaseProducto()` |

Librerías CDN cargadas al final: SweetAlert2, jsPDF, jsPDF AutoTable.

### 13.5. Sub-features destacadas

#### Imágenes como BLOB
- Flujo: FileReader → base64 → JSON → `base64_decode()` → binary → `VARBINARY(MAX)`
- Binding SQL Server: `SQLSRV_SQLTYPE_VARBINARY('max')`
- Lectura con `sqlsrv_get_field()`, fallback hexadecimal
- MIME type detectado por extensión (jpg, jpeg, png, gif, bmp, webp, pdf)
- Fallback a GIF transparente 1x1 si no hay imagen

#### FIFO/PEPS (Lotes)
- Lotes ordenados por `fecha_creacion ASC` (más antiguos primero)
- `antiguedad_dias` calculado con `DATEDIFF(day, fecha_creacion, GETDATE())`
- Estados: **Agotado** (stock ≤ 0), **Stock Crítico** (< 10), **Activo** (resto)
- Colores: rojo (> 20 días), naranja (> 7 días), verde (reciente)

#### Tipos de Precio
| Tipo | Lógica |
|------|--------|
| **Fijo** | Manual (no expuesto en formulario inventario) |
| **UIT** | `precio = valor_uit * porcentaje_uit`. Porcentaje guardado en `producto.porcentaje_uit`. Calculado client-side |
| **Variable** | Último registro de `historial_precio` (más reciente). Nuevos precios se insertan al guardar el producto |

#### Mermas (Pérdidas)
- Dos métodos: **Cantidad Exacta** o **Porcentaje** del stock del lote
- Tipos: Vencimiento, Deterioro, Plaga, Proceso, Otro
- Transaccional: decrementa `lote.stock_actual` + INSERT movimiento MERMA en `kardex`
- Validación: cantidad no puede exceder stock del lote

#### Filtrado Client-Side
- Por `centro_produccion`, `clase`, nombre (texto con debounce 300ms)
- Animación CSS: fade out → fade in (`fadeInUp`) en filas visibles
- Usa atributos `data-*` en las filas de la tabla

#### Exportación
- **Excel/CSV:** UTF-8 BOM, delimitado por punto y coma, secciones Lotes + Kardex
- **PDF:** jsPDF + AutoTable, membrete PECH oficial, tablas con striping, numeración de páginas

### 13.6. Registro AJAX en index.php raíz

Las 10 acciones inventario registradas en `$acciones_ajax` (`index.php:66-72`):
`obtener_producto`, `guardar_producto`, `eliminar_producto`, `obtener_lotes`, `obtener_kardex`, `guardar_lote`, `guardar_merma`, `obtener_precio_actual`, `guardar_precio`, `ver_imagen_producto`

### 13.7. Assets

- **CSS:** Los 4 archivos en `modules/produccion_agraria/assets/css/` — `variables.css`, `components.css`, `common.css`, `responsive.css` — son compartidos por todo el módulo de Producción Agraria, incluido inventario.
- **JS:** No hay archivos `.js` separados para inventario. Todo el JavaScript está inline en `views/inventario/index.php`.

---

## 14. Assets CSS del Módulo Agrario

Ubicados en `modules/produccion_agraria/assets/css/`:
- `variables.css`: Variables CSS custom (colores PECH, sombras, spacing)
- `components.css`: Gradientes, badges, avatares, stat-cards, modales
- `common.css`: Utilidades, animaciones, scroll custom
- `responsive.css`: Breakpoints mobile/tablet, tablas tipo card

### Paleta PECH
- **Verde:** `#009540` (`--pech-verde`)
- **Azul:** `#004d99` (`--pech-azul`)

---

## 15. Deploy y Requisitos

- **Servidor web:** Apache (con `mod_rewrite`) o IIS (con URL Rewrite)
- **PHP:** 7.4+ con extensión `sqlsrv` habilitada
- **Base de datos:** SQL Server accesible en `10.0.100.252`
- **Archivos de rewrite:** `.htaccess` (Apache) y `web.config` (IIS) ya incluidos
- **Sin proceso de build:** Copiar archivos directamente al servidor
- **Post-deploy:** Editar `config/db.php` con credenciales reales

## 16. Submódulo Punto de Venta (produccion_agraria/punto_venta)

El submódulo de punto de venta (POS) gestiona el registro de ventas del catálogo de productos (solo los que manejan stock) generando una proforma en estado `PENDIENTE` y aplicando descuento FIFO a nivel de lote.

### 16.1. Routing (doble nivel)

```
Browser: /produccion_agraria/punto_venta
  → root index.php: $module='produccion_agraria', $action='punto_venta'
  → Produccion_agrariaController.php (switch $action)
  → case 'punto_venta' → require_once PuntoVentaController.php
```

`Produccion_agrariaController.php` delega las siguientes acciones a `PuntoVentaController`:
`punto_venta`, `buscar_producto`, `buscar_clientes`, `guardar_venta`, `crear_cliente_rapido`

### 16.2. Controller: `PuntoVentaController.php` (87 líneas)

| # | Action | HTTP | AJAX | Propósito |
|---|--------|------|------|-----------|
| 1 | `index` (default) | GET | No | Obtiene listas iniciales de clientes, productos y ventas de hoy y renderiza la vista. |
| 2 | `buscar_producto` | GET | Sí | Busca y retorna la información de un producto por su ID en formato JSON. |
| 3 | `buscar_clientes` | GET | Sí | Filtra y retorna la lista de clientes coincidentes con el parámetro `q` en JSON. |
| 4 | `guardar_venta` | POST | Sí | Registra la cabecera y el detalle de la venta. Descuenta stock por FIFO. |
| 5 | `crear_cliente_rapido` | POST | Sí | Inserta un cliente con ID temporal incremental de forma ágil desde el formulario de venta. |

### 16.3. Model: `PuntoVentaModel.php` (330 líneas)

Todas las consultas SQL operan sobre el esquema `BD_PRODUCCIONDESARROLLO.dbo.*`.

#### Consultas e Inserciones Clave:
*   **`listarProductosVenta()` y `buscarProducto($id)`**: Selecciona productos que tienen `maneja_stock = 1`. Une a las tablas `clase`, `centro_produccion`, `uit` (año actual) e `historial_precio` (subconsulta para obtener el último precio registrado). Calcula y adjunta el `precio_venta` en tiempo real según el tipo de precio configurado (UIT o Variable).
*   **`guardarVenta($data)`**: Transaccional (`sqlsrv_begin_transaction`).
    1.  Obtiene el `id_centro` del primer producto vendido.
    2.  Inserta la cabecera en `transaccion` estableciendo `estado = 'PENDIENTE'` (proforma), `tipo_op = 'VENTA'`, y el total. Obtiene el `id_transaccion` generado.
    3.  Itera los ítems vendidos. Para cada producto, busca lotes activos (`stock_actual > 0`) ordenados por `fecha_creacion ASC, id_lote ASC` (criterio FIFO).
    4.  Itera lotes descontando la cantidad requerida: resta stock en `lote.stock_actual`, registra un movimiento `VENTA` en `kardex` calculando el nuevo `saldo_final` en base al saldo anterior del lote, e inserta registros en `transaccion_detalle`.
*   **`crearClienteRapido($nombre)`**: Genera un código de DNI/RUC temporal bajo el patrón `TEMPyyMMddXXXX` (donde `XXXX` es un aleatorio) e inserta el cliente en la base de datos con `tipo_cliente = 0` (Externo). Retorna los datos creados y el ID generado.

### 16.4. Vista: `views/punto_venta/index.php` (868 líneas)

Consiste en una interfaz monolítica enriquecida con Bootstrap 5 / Tabler y JavaScript vanilla.

#### Elementos Clave de la Interfaz:
*   **Búsqueda y Autocompletado**:
    *   **Clientes**: Filtrado reactivo en el cliente sobre la colección `clientesDisponibles`. Si el cliente no existe, muestra el enlace "Registrar rápido". Al pulsarlo, llama por AJAX a `crear_cliente_rapido`, añade el cliente a la lista local y lo selecciona automáticamente.
    *   **Productos**: Búsqueda reactiva local que despliega un dropdown personalizado mostrando nombre, unidad de medida y precio vigente. Al seleccionarse, se añade a la tabla de ítems con cantidad `1`.
*   **Tabla de Ítems Vendidos**: Permite modificar en tiempo real la cantidad y el precio unitario del producto seleccionado, recalculando subtotales y total general automáticamente.
*   **Modo Venta Masiva (Cola)**:
    *   Diseñado para procesar rápidamente filas de clientes consecutivos.
    *   Al activarse mediante el switch, se mantiene fijo el producto inicial (bloqueado con cantidad 1 para el siguiente cliente) y el método de pago, limpiando el resto del formulario tras guardar.
    *   Añade un historial inferior de "Últimas Ventas Procesadas en Cola" en la sesión activa con animaciones de resaltado verde temporal.
    *   Habilita atajo de teclado: presionar `Enter` en el campo numérico de cantidad ejecuta directamente la transacción (`#btn-procesar`).

---

*Última actualización: 2026-06-04*
