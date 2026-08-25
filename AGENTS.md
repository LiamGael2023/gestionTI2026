# AGENTS.md — Contexto del Proyecto gestionTI2026

> Este archivo contiene la información esencial que cualquier agente de IA necesita para trabajar efectivamente en este proyecto. Si modificas la arquitectura, stack o convenciones, actualiza este archivo.

---

## 1. Visión General

**gestionTI2026** es un sistema de gestión interno modular desarrollado para la Subgerencia de Desarrollo Agrícola del Proyecto Especial Chavimochic (PECH), Gobierno Regional La Libertad.

El módulo más desarrollado y funcional es **Producción Agraria**, que gestiona inventario, ventas, proformas, vouchers de pago, reportes, un dashboard CMS con widgets configurables y un chatbot de IA.

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
| **Gráficos** | ApexCharts (reportes y dashboard CMS) |
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
`index.php` en la raíz es el **único punto de entrada**. Todas las URLs pasan por aquí. Usa `ob_start()` al inicio para control de output buffering en respuestas AJAX.

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
5. Resuelve aliases de ruta: `login→auth/login`, `logout→auth/logout`, `autenticar→auth/autenticar`
6. Detecta si es **AJAX** mediante lista blanca `$acciones_ajax`
7. Si es AJAX → **NO incluye** `header.php` ni `footer.php`, devuelve JSON y hace `exit`
8. Si es vista → incluye `public/header.php` + controlador + `public/footer.php`
9. Fallback para módulos sin controlador: `soporte`, `certificados`, `adquisiciones` usan handlers inline

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
- **Conexión BD:** `$conn` se establece en `public/header.php` vía `Conexion::conectar()` y se pasa por constructor a los modelos (`new Modelo($conn)`). Los controladores que pueden ejecutarse fuera del flujo normal (AJAX) hacen su propia conexión con fallback: `if (!isset($conn) || !$conn) { $conn = Conexion::conectar(); }`

---

## 4. Sistema de Autenticación y Permisos

- **Clase `core/Auth.php`:**
  - `Auth::check()`: Valida sesión activa (`$_SESSION['usuario_id']`)
  - Consulta `comun.Permisos` + `comun.Modulos` para validar acceso
  - Redirección a `/dashboard?error=access_denied` si no tiene permiso
- **Roles:** `ADMIN` y otros. Solo `ADMIN` accede a `usuarios` y `sistemas`
- **Menú dinámico:** `public/header.php` consulta `comun.Modulos` + `comun.Permisos` para armar la navbar
- **Variables de sesión:** `$_SESSION['usuario_id']`, `['usuario_nombre']`, `['usuario_rol']`
- **Login:** `AuthController.php` autentica contra `comun.Usuarios` usando `password_verify()`, establece sesión y retorna JSON con redirect

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
- Método primario: `sqlsrv_get_field()` con `SQLSRV_ENC_BINARY`
- Método fallback: `CAST(imagen_blob AS VARCHAR(MAX))` → hex → `hex2bin()`
- Limpia buffers con `while (ob_get_level()) { ob_end_clean(); }` antes de servir imagen binaria
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
1. **Punto de Venta** crea una **proforma** (`transaccion` con `estado = 'PENDIENTE'`, `tipo_op = 'VENTA'`)
2. Stock se descuenta inmediatamente de los lotes más antiguos (FIFO)
3. **Bandeja de Entrada** lista proformas pendientes
4. Al **procesar** la proforma: se asigna `metodo_pago`, `serie_comprobante`, `correlativo_comprobante`, `id_voucher`, estado → `PROCESADO`
5. Al **anular**: se revierte stock a lotes y se registra movimiento de reintegración en kardex, estado → `RECHAZADO`

### Método de descuento FIFO
1. Obtiene lotes del producto ordenados por `fecha_creacion ASC, id_lote ASC`
2. Descuenta `cantidadPendiente` iterando lotes desde el más antiguo
3. Actualiza `lote.stock_actual`
4. Registra movimiento en `kardex` (tipo_movimiento = 'VENTA')
5. Inserta `transaccion_detalle` vinculado al `id_lote` usado

### Métodos de Pago
| Método | Descripción |
|--------|-------------|
| **VENTA** | Venta directa con boucher de depósito |
| **DONACION** | Donación con resolución de gerencia |

La constraint `CK_transaccion_metodo_pago` en SQL Server solo permite `'VENTA'` y `'DONACION'`.

### Tipos de Precio
| Tipo | Descripción |
|------|-------------|
| **UIT** | `valor_uit(año) * porcentaje_uit` |
| **Variable** | Último precio de `historial_precio` (más reciente) |

> **Nota (2026-07-27):** El sistema maneja únicamente dos tipos de precio: **UIT** (basado en porcentaje de la Unidad Impositiva Tributaria) y **Variable** (último precio registrado en el historial). La CHECK constraint `CK_producto_tipo_precio` en SQL Server solo permite estos dos valores.

---

## 8. Configuración del Proyecto

### Archivos clave
| Archivo | Propósito |
|---------|-----------|
| `config/config.php` | Define `BASE_URL`, carga `.env`, helper `env()` |
| `config/db.php` | Clase `Conexion` con `sqlsrv_connect()` |
| `config/db.php.example` | Plantilla de conexión |
| `.env` | `OPENCODE_API_KEY`, `OPENCODE_MODEL`, `CHAT_SERVICE`, `CHAT_SSL_VERIFY` |
| `.env.example` | Template sin credenciales reales |

### Variables importantes
- `BASE_URL`: Usado en redirecciones, links del menú, assets CSS
- `OPENCODE_API_KEY`: Para el chatbot IA integrado
- `OPENCODE_MODEL`: Modelo a usar (default: `deepseek-v4-flash`)
- `CHAT_SERVICE`: `opencode` (OpenCode Go) o `deepseek` (API directa DeepSeek)
- `CHAT_SSL_VERIFY`: `false` para deshabilitar verificación SSL en entornos con problemas de certificados
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
| `dashboard_config` | Configuración de widgets del dashboard CMS por usuario |

---

## 10. Módulos del Sistema

| Módulo | Estado | Descripción |
|--------|--------|-------------|
| **auth** | ✅ Activo | Login/logout con sesiones PHP |
| **dashboard** | ✅ Activo | Página de inicio (vista placeholder mínima) |
| **produccion_agraria** | ✅ **Principal** | Inventario, ventas, proformas, vouchers, reportes, dashboard CMS, chatbot IA |
| &nbsp;&nbsp;↳ inventario | Submódulo | CRUD productos, lotes, kardex, mermas, precios, imágenes BLOB |
| &nbsp;&nbsp;↳ punto_venta | Submódulo | Punto de venta/proforma con descuento FIFO |
| &nbsp;&nbsp;↳ bandeja | Submódulo | Proformas pendientes, procesamiento, vouchers |
| &nbsp;&nbsp;↳ tablas | Submódulo | Catálogos: clases, centros, UIT, clientes |
| &nbsp;&nbsp;↳ reportes | Submódulo | 7 tipos de reportes, KPIs, exportación PDF/Excel |
| &nbsp;&nbsp;↳ dashboard | Submódulo | Dashboard CMS No-Code con 17 widgets configurables y drag-and-drop |
| &nbsp;&nbsp;↳ consultas | Submódulo | Chatbot IA con Tool Calling (17 tools) |
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
  models/ChatToolsModel.php             # 17 tools de consulta SQL READ-ONLY
  views/index.php                        # Interfaz de chat con JS vanilla + localStorage
```

### 11.2. Routing y delegación

El módulo `consultas` **no se accede directamente** como `module=consultas`. La vista y todos los endpoints AJAX se despachan a través del módulo `produccion_agraria`, que delega a `ConsultasController`:

- `index.php` → `module=produccion_agraria` → `Produccion_agrariaController.php` → `case 'consultas'` / `case 'chat_enviar'` / `case 'tool_*'` → `include 'modules/consultas/controllers/ConsultasController.php'`

URL de acceso: `/produccion_agraria/consultas`

### 11.3. API de IA

Soporta **dos backends** configurables vía `CHAT_SERVICE` en `.env`:

| Servicio | Endpoint | Modelo |
|----------|----------|--------|
| `opencode` (default) | `https://opencode.ai/zen/go/v1/chat/completions` | `deepseek-v4-flash` |
| `deepseek` | `https://api.deepseek.com/chat/completions` | `deepseek-chat` |

| Parámetro | Valor |
|-----------|-------|
| **Temperature** | `0.3` |
| **Max tokens** | `1024` |
| **Timeout cURL** | 60 segundos |
| **SSL verify** | Configurable vía `CHAT_SSL_VERIFY` en `.env` |

### 11.4. Patrón Tool Calling Simulado (Dos Llamadas)

El modelo NO tiene acceso nativo a function calling. En su lugar se usa prompt engineering:

**System Prompt** (`ConsultasController.php`): Instruye al modelo a emitir **exactamente un JSON raw** con `{"tool": "nombre", "params": {...}}` o array `[{...},{...}]` para múltiples tools cuando necesite datos de la BD. Si no requiere datos, responde normalmente. Reglas estrictas:
- No markdown, no backticks, no explicaciones, no saludos cuando emite tool call.
- Solo el JSON puro.
- Incluye fechas contextuales auto-calculadas (hoy, ayer, este mes, esta semana).
- Puede responder preguntas de ayuda ("cómo hacer...") sin usar tools.

**Flujo de dos llamadas** (`chat_enviar`):

```
LLAMADA 1: [system prompt] + [historial 8 msgs] + [mensaje usuario]
  ↓
  El modelo decide: ¿necesito consultar BD?
  ├── NO → Responde directamente como texto → se muestra al usuario
  └── SÍ → Emite JSON tool call
       ↓
       Backend extrae JSON (3 intentos de parseo + balanced brace extraction, ver §11.7)
       ↓
       Ejecuta tool(s) en ChatToolsModel → obtiene resultados SQL
       ↓
LLAMADA 2: [misma conversación] + [respuesta JSON del modelo como assistant]
           + ["Resultado de la consulta: ... Por favor resume estos datos..."]
  ↓
  Modelo genera respuesta en lenguaje natural con los datos
  ↓
  Respuesta final se envía al frontend con: {success, respuesta, tool_usada, resultado_raw}
```

**Detección de razonamiento fallido:** `pareceToolCallFallido()` detecta cuando el modelo emite texto de análisis ("necesito...", "veamos...", "voy a...") > 200 chars sin JSON válido. En este caso, se envía un prompt de corrección para forzar al modelo a emitir el JSON.

### 11.5. Las 17 Tools (ChatToolsModel.php)

Todas las queries son **READ-ONLY**, usan **prepared statements** con `sqlsrv_query($db, $sql, $params)`, y tienen límite `TOP 20` o `TOP 30`.

**Tools 1-12: Consultas Clásicas**

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

**Tools 13-17: Tools Avanzadas**

| # | Tool | Acción | Params | Descripción |
|---|------|--------|--------|-------------|
| 13 | `consultar_grafico` | **Dispatcher de 7 tipos de gráficos** | `tipo` (ventas_mes, top_productos, stock_centro, ventas_metodo_pago, valorizacion_clase, mermas_mes, ventas_vs_donaciones) | Retorna `{columns, rows, grafico: {tipo, titulo, categorias, series, formato, height}}`. Tipos de chart: bar, horizontalBar, donut, pie, area |
| 14 | `consultar_resumen` | **Resumen ejecutivo diario** | (sin params) | Ejecuta 6 queries independientes: ventas hoy, proformas pendientes, stock crítico, vouchers sin asignar, mermas hoy, valor inventario. Retorna tabla con 6 KPIs |
| 15 | `consultar_comparativa` | **Comparativa entre dos períodos** | `tipo` (ventas/mermas/ingresos), `fecha1_desde/hasta`, `fecha2_desde/hasta` | Calcula variación porcentual entre períodos. Retorna tabla + gráfico de barras |
| 16 | `consultar_buscar` | **Búsqueda global** | `termino` | Busca en 4 tablas (TOP 5 cada una): productos, clientes, vouchers, lotes. Retorna resultados unificados con `tipo`, `resultado`, `extra`, `contexto` |
| 17 | `consultar_recomendaciones` | **Alertas inteligentes** | (sin params) | 4 queries: reponer stock (<10), clientes inactivos (>30 días), alta merma (60 días), proformas antiguas (>7 días). Retorna `{tipo, recomendacion, motivo}` |

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

### 11.7. Extracción de JSON (3 intentos de parseo + balanced brace extraction)

Función `procesarToolCall()` (`ConsultasController.php`):

1. **Intento 1:** `json_decode($content)` — el contenido completo es JSON puro.
2. **Intento 2:** Regex `/\{\s*"tool"\s*:[^}]+\}/s` — busca un objeto JSON simple con `"tool"`.
3. **Intento 3:** `extraerJsonBalanced()` — busca `{"tool"` o `[{"tool"` y extrae mediante balanceo de llaves/corchetes con awareness de strings (maneja escaped quotes). Soporta multi-tool arrays.

Cada intento valida que el JSON decodificado tenga las claves `tool` y `params`, y que el tool name esté en la whitelist `$TOOLS_VALIDAS`.

### 11.8. Endpoints AJAX

Todos registrados en `core/ChatActions.php` (19 entradas) y referenciados vía `...require` desde `index.php` y `Produccion_agrariaController.php`:

| Endpoint | Propósito | Respuesta |
|----------|-----------|-----------|
| `chat_enviar` | **Principal**: recibe `{mensaje, historial}`, ejecuta flujo de 2 llamadas | `{success, respuesta, modelo, tool_usada, resultado_raw}` |
| `tool_stock` a `tool_recomendaciones` | **Individuales**: 18 endpoints para llamar cada tool directamente | `{success, data: [...]}` |

### 11.9. Frontend (views/index.php)

- **UI:** Chat estilo burbujas con gradiente PECH azul (`#004d99` → `#0070cc`).
- **Historial:** Persistido en `localStorage` bajo clave `chat_historial_pech`. Máximo 8 mensajes recientes enviados al backend.
- **Indicadores visuales:**
  - `mostrarTyping()`: animación de 3 dots "Pensando..." durante llamada API.
  - `mostrarConsultandoBD()`: indicador "Consultando base de datos..." con 800ms de delay cuando se usó una tool (simula latencia de consulta).
- **Renderizado de tablas:** Si `resultado_raw` contiene `{columns, rows}`, se renderiza una `<table>` HTML (Tabler-styled) con valores monetarios formateados (`S/ X.XX`) y cantidades sin decimales.
- **Renderizado markdown:** Conversión manual (sin librería) de `**negrita**`, `*cursiva*`, `` `codigo` ``, bloques ` ``` `, y tablas markdown (`| col |`) a HTML.
- **Limpieza:** Botón con confirmación SweetAlert2 que borra `localStorage` y DOM (conserva mensaje de bienvenida inicial).
- **Voice input:** `activarVoz()` usa Web Speech API para dictado por voz.
- **Fetch:** Llama a `module=produccion_agraria&action=chat_enviar` (no a `module=consultas` directamente).

### 11.10. Configuración requerida

El chatbot **no funciona** sin estas variables en `.env`:
```
CHAT_SERVICE=opencode
OPENCODE_API_KEY=sk-...
OPENCODE_MODEL=deepseek-v4-flash
```

Si la API key está vacía, el endpoint `chat_enviar` devuelve `{success: false, message: "API key no configurada"}`.

### 11.11. Manejo de errores

- **CreditError (401):** Detecta `error.type === 'CreditsError'` y muestra mensaje amigable: "Sin créditos disponibles... recarga tu saldo en opencode.ai/billing".
- **HTTP 429 (rate limit API):** Detectado y mostrado como error amigable.
- **HTTP 429 (rate limit local):** 20 requests/min por sesión PHP. Frontend muestra countdown y deshabilita input.
- **cURL errors:** Se loguean con `error_log('[Chatbot] ...')` y se retorna mensaje genérico.
- **JSON parse errors:** Idem, con `json_last_error_msg()`.
- **Tool call fallido (JSON no parseable):** Si el contenido parece un tool call (`pareceToolCallFallido()`) pero los intentos de parseo fallan, se muestra mensaje "Entendí que necesitas consultar datos, pero tuve dificultades... ¿Podrías reformular?" en lugar de mostrar JSON crudo al usuario.
- **Tool inválida (no en whitelist):** Si el modelo emite una tool no registrada, se loguea y responde con mensaje genérico.
- **PHP Throwable:** El `try/catch` global captura cualquier excepción, limpia buffers y devuelve JSON 500.

### 11.12. Mejoras implementadas (2026-06-04)

#### Seguridad
| Mejora | Archivo | Detalle |
|--------|---------|---------|
| **SSL verification** | `ConsultasController.php` | Habilitado por defecto. Deshabilitar solo con `CHAT_SSL_VERIFY=false` en `.env` |
| **Rate limiting** | `ConsultasController.php` | 20 requests/min por sesión PHP. Bloquea con HTTP 429 y frontend muestra countdown |
| **Tool whitelist** | `ConsultasController.php` | `$TOOLS_VALIDAS` con 17 tools. Valida que solo tools registradas se ejecuten |
| **`.env.example`** | raíz | Template sin credenciales reales (`.env` ya en `.gitignore`) |
| **Dual backend support** | `ConsultasController.php` | `CHAT_SERVICE` permite elegir entre OpenCode Go y DeepSeek API directa |

#### Robustez
| Mejora | Archivo | Detalle |
|--------|---------|---------|
| **Acciones AJAX unificadas** | `core/ChatActions.php` | 19 entradas centralizadas referenciadas por `index.php` y `Produccion_agrariaController.php` con `...require`. Solo editar un lugar al agregar tools |
| **Detección tool call fallido** | `ConsultasController.php` | `pareceToolCallFallido()` evita mostrar JSON crudo al usuario |
| **Corrección de razonamiento** | `ConsultasController.php` | Si el modelo emite análisis en vez de JSON, se reenvía prompt de corrección |
| **Balanced brace extraction** | `ConsultasController.php` | `extraerJsonBalanced()` maneja JSON anidado y multi-tool arrays |
| **Sin `$GLOBALS`** | `ConsultasController.php` | El system prompt se usa como variable local en vez de `$GLOBALS` |

#### UX / Frontend
| Mejora | Archivo | Detalle |
|--------|---------|---------|
| **Feedback visual específico** | `views/index.php` | Badge con nombre de tool (ej. "Stock de productos") en burbuja del bot |
| **Quick prompts** | `views/index.php` | 6 chips clickeables: Stock, Ventas, Top productos, Proformas, Valor inventario, Mermas |
| **Dark mode** | `views/index.php` | Soporte `prefers-color-scheme: dark` con variables CSS |
| **Rate limit UI** | `views/index.php` | Al recibir 429, muestra mensaje y deshabilita input por `retry_after` segundos |
| **Voice input** | `views/index.php` | Botón de micrófono con Web Speech API |

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
9. **Al agregar una tool de chatbot**, solo editar `core/ChatActions.php` (el array se propaga automáticamente via `...require`).
10. **Para servir archivos binarios (BLOB)**, limpiar todos los buffers de salida con `while (ob_get_level()) { ob_end_clean(); }` antes de enviar headers y datos binarios.
11. **Siempre instanciar modelos con `new Modelo($conn)`**, pasando la conexión por constructor.

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

### 13.2. Controller: `InventarioController.php` (175 líneas)

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
| 11 | `ver_imagen_producto` | GET | Sí | Sirve imagen BLOB con Content-Type según extensión. Método primario + fallback hexadecimal. Fallback a 1x1 GIF |

### 13.3. Model: `InventarioModel.php` (511 líneas, 19 métodos)

Todas las queries usan el esquema `BD_PRODUCCIONDESARROLLO.dbo.`

#### CRUD Productos (4)
| Método | Tablas | Propósito |
|--------|--------|-----------|
| `listarProductos()` | `producto` LEFT JOIN `clase`, `centro_produccion` | Catálogo completo ordenado por `id_producto` |
| `obtenerProducto($id)` | `producto` LEFT JOIN `clase`, `centro_produccion` | Producto individual por ID |
| `guardarProducto($data)` | `producto` | INSERT/UPDATE. Maneja 3 ramas de imagen: eliminar, nueva (BLOB), sin cambios. Anula `porcentaje_uit` si `tipo_precio != 'UIT'`. Usa `OUTPUT INSERTED.id_producto` para obtener ID |
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
| `listarMovimientosKardex($id)` | `kardex` LEFT JOIN `lote` | Historial de movimientos ordenado por `fecha DESC`. Convierte DateTime a string ISO |
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

Las 10 acciones inventario registradas en `$acciones_ajax`:
`obtener_producto`, `guardar_producto`, `eliminar_producto`, `obtener_lotes`, `obtener_kardex`, `guardar_lote`, `guardar_merma`, `obtener_precio_actual`, `guardar_precio`, `ver_imagen_producto`

### 13.7. Assets

- **CSS:** Los 5 archivos en `modules/produccion_agraria/assets/css/` — `variables.css`, `components.css`, `common.css`, `responsive.css`, `dashboard.css` — son compartidos por todo el módulo de Producción Agraria, incluido inventario.
- **JS:** No hay archivos `.js` separados para inventario. Todo el JavaScript está inline en `views/inventario/index.php`.

---

## 14. Assets CSS del Módulo Agrario

Ubicados en `modules/produccion_agraria/assets/css/`:
- `variables.css`: Variables CSS custom (`--pa-verde`, `--pa-verde-dark`, `--pa-verde-light`, sombras, espaciado)
- `components.css`: Gradientes, badges outline, avatares, stat-cards, modales, botones
- `common.css`: Utilidades, animaciones, scroll custom
- `responsive.css`: Breakpoints mobile/tablet, tablas tipo card
- `dashboard.css`: Estilos del Dashboard CMS (widget grid, KPI cards, palette modal, drag-and-drop)

### Paleta PECH
- **Verde:** `#009540` (`--pech-verde`, `--pa-verde`)
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
`punto_venta`, `buscar_cliente_api`, `guardar_venta`, `crear_cliente_rapido`

> **Nota:** Los endpoints `buscar_producto` y `buscar_clientes` fueron **eliminados** (código muerto): la vista busca clientes y productos en los arrays precargados (`productosDisponibles` / `clientesDisponibles`).

### 16.2. Controller: `PuntoVentaController.php` (≈190 líneas)

Todas las operaciones de escritura exigen **token CSRF** (`$_SESSION['csrf_token']`, validado con `hash_equals`). Si falta o es inválido → HTTP 403.

| # | Action | HTTP | AJAX | Propósito |
|---|--------|------|------|-----------|
| 1 | `index` (default) | GET | No | Obtiene listas iniciales de clientes y productos y renderiza la vista. |
| 2 | `guardar_venta` | POST | Sí | Registra la venta. Valida CSRF y **recalcula precios en servidor** (no confía en el precio/total del cliente). |
| 3 | `crear_cliente_rapido` | POST | Sí | Inserta un cliente temporal (tipo Externo). Valida CSRF. |
| 4 | `buscar_cliente_api` | GET | Sí | Consulta DNI/RUC (RENIEC/SUNAT/PECH) y registra el cliente si no existe. Valida CSRF. |

El `catch` final captura `\Throwable`, loguea el detalle y devuelve un JSON genérico (sin filtrar mensajes internos de SQL/API).

### 16.3. Model: `PuntoVentaModel.php` (≈545 líneas)

Todas las consultas SQL operan sobre el esquema `BD_PRODUCCIONDESARROLLO.dbo.*`.

#### Métodos:
| Método | Propósito |
|--------|-----------|
| `listarClientes()` | Todos los clientes activos ordenados por nombre. Retorna `tipo_cliente` como `'Planilla'`/`'Externo'` (0=Planilla, 1=Externo) |
| `listarProductosVenta()` | Productos con `maneja_stock=1` + precio calculado + stock total. Último precio con tie-break `ROW_NUMBER() ... id_historial DESC` |
| `buscarProducto($id)` | Producto individual con precio calculado. Mismo tie-break |
| `calcularPrecio($producto)` | Helper privado: UIT × porcentaje o precio variable |
| `guardarVenta($data)` | **Transaccional FIFO**: recalcula precios en servidor → inserta cabecera → descuenta lotes con `WITH (UPDLOCK)` → kardex → una fila de detalle por lote |
| `crearClienteRapido($nombre)` | Genera `dni_ruc = TEMPyyMMddXXXX`, inserta con `tipo_cliente=1` (Externo), `OUTPUT INSERTED.id_cliente` |

> **Seguridad de precios:** `guardarVenta()` ignora `precio`, `subtotal` y `total` enviados por el cliente; relanza el precio oficial del producto (`calcularPrecio`), valida cantidad entera > 0 y recomputa el total. La edición visual del precio en el carrito solo aplica en el cliente (útil para mostrar), el monto real lo define el servidor.

#### Flujo de `guardarVenta()`:
1. Valida `id_cliente` e ítems; normaliza cantidades (entero > 0)
2. Por cada ítem: recarga el producto con `buscarProducto()` → precio oficial → subtotal; acumula total
3. Soporta `fecha` opcional (`Y-m-d`, ventas retroactivas); si no viene, `GETDATE()`
4. Inserta cabecera en `transaccion` con `estado='PENDIENTE'` (o `PROCESADO` si PLANILLA), `tipo_op='VENTA'`, `OUTPUT INSERTED.id_transaccion`
5. Por cada ítem: busca lotes activos con `WITH (UPDLOCK, ROWLOCK)` (evita sobreventa concurrente) ordenados por `fecha_creacion ASC, id_lote ASC` (FIFO), filtrando por el **centro propio del producto**
6. Itera lotes descontando: resta stock, registra kardex (`tipo_movimiento='VENTA'`), acumula `asignaciones` por lote
7. Inserta **una fila de `transaccion_detalle` por cada lote usado** (trazabilidad FIFO completa)
8. Si stock insuficiente, lanza Exception → rollback

### 16.4. Vista: `views/punto_venta/index.php` (~1015 líneas)

Interfaz monolítica Bootstrap 5 / Tabler con JavaScript vanilla. **Layout POS de 2 columnas:**

```
[Catálogo de Productos (col-lg-8)]          [Cliente y Pago (col-lg-4)]
  [🔍 Buscar producto] [◻ Solo stock]         Cliente ▾ | Fecha | Método
  [ grid #productos-grid de tarjetas:         [◻ Desc. planilla] [Modo Cola]
    imagen BLOB, nombre, badge stock,        [Carrito (col-lg-4)]
    precio — click para agregar ]             [ tabla items, header sticky,
    scroll interno                            scroll interno ]
                                              [ Barra fija #pos-cart-footer:
                                                TOTAL | Limpiar | Procesar ]
[Historial "Últimas Ventas Procesadas" (full width)]
```

#### Elementos Clave de la Interfaz:
*   **Catálogo de Productos**: Grid de tarjetas (`renderProductos()`) filtrable en tiempo real por nombre, clase, centro o código (búsqueda sin tildes con `normalizarTexto()`), con toggle **Solo stock** y ordenamiento (con stock primero, agotados al final y sin `pointer-events`). Cada tarjeta muestra la imagen real del producto (BLOB vía `ver_imagen_producto`) o un ícono. Clic → `agregarProductoDirecto(id, event)`.
*   **Búsqueda y Autocompletado de Clientes**: Filtrado reactivo sobre `clientesDisponibles`. Si no existe, ofrece "Registrar rápido" → AJAX `crear_cliente_rapido`, o consulta RENIEC/SUNAT/PECH vía `buscar_cliente_api` (ambos con CSRF).
*   **Tabla de Ítems Vendidos**: Permite modificar en tiempo real la cantidad y el precio unitario (solo visual; el servidor recalcula el precio oficial en `guardarVenta`), recalculando subtotales y total.
*   **Barra fija del carrito** (`#pos-cart-footer`): Total + botones Limpiar/Procesar siempre visibles aunque la tabla haga scroll.
*   **Modo Venta Masiva (Cola)**:
    *   Diseñado para procesar rápidamente filas de clientes consecutivos.
    *   Al activarse mediante el switch, se mantiene fijo el producto inicial (bloqueado con cantidad 1 para el siguiente cliente) y el método de pago, limpiando el resto del formulario tras guardar.
    *   Añade un historial inferior de "Últimas Ventas Procesadas en Cola" en la sesión activa con animaciones de resaltado verde temporal.
    *   Habilita atajo de teclado: presionar `Enter` en el campo numérico de cantidad ejecuta directamente la transacción (`#btn-procesar`).

---

## 17. Submódulo Dashboard CMS (produccion_agraria/dashboard)

Dashboard personalizable **No-Code** donde cada usuario construye su propio layout con widgets de tipo KPI, gráfico y tabla. Los widgets se pueden arrastrar (drag-and-drop), configurar, añadir y eliminar. La configuración se persiste en `dashboard_config`.

### 17.1. Archivos

| Archivo | Líneas | Propósito |
|---------|--------|-----------|
| `controllers/DashboardController.php` | 139 | 5 endpoints: vista principal + dash_load, dash_save, dash_reset, dash_widget |
| `models/DashboardModel.php` | 220 | Config BD + catálogo de widgets + datos delegando a ChatToolsModel |
| `views/dashboard/index.php` | 560 | Interfaz completa con grid CSS, drag-and-drop, paleta modal, renderizado de gráficos |
| `assets/css/dashboard.css` | 162 | Estilos: grid layout, widget cards, KPI colors, palette modal, drag states |
| `database/dashboard_config.sql` | 23 | Schema de la tabla `dashboard_config` |

### 17.2. Tabla `dashboard_config`

```sql
CREATE TABLE BD_PRODUCCIONDESARROLLO.dbo.dashboard_config (
    id_config INT IDENTITY(1,1) NOT NULL,
    usuario_id INT NOT NULL,
    posicion INT NOT NULL DEFAULT 0,
    widget_tipo VARCHAR(50) NOT NULL,
    widget_titulo VARCHAR(100) NULL,
    widget_tamano VARCHAR(20) DEFAULT 'medium',
    widget_config NVARCHAR(MAX) NULL,
    activo BIT DEFAULT 1,
    fecha_creacion DATETIME DEFAULT GETDATE(),
    fecha_modificacion DATETIME DEFAULT GETDATE(),
    CONSTRAINT PK_dashboard_config PRIMARY KEY (id_config)
);
```

### 17.3. Endpoints AJAX

| Endpoint | Método | Propósito |
|----------|--------|-----------|
| `dash_load` | GET | Carga config + datos de todos los widgets del usuario. Si no tiene config, devuelve layout por defecto |
| `dash_save` | POST | Guarda el layout actual del usuario (DELETE + INSERT transaccional) |
| `dash_reset` | POST | Elimina la config del usuario (vuelve al layout por defecto) |
| `dash_widget` | GET | Obtiene datos de un widget individual (`tipo` + `config` params) |

### 17.4. Catálogo de Widgets (17 tipos)

El `DashboardModel` reutiliza `ChatToolsModel` para todas las consultas de datos.

#### KPIs (7 widgets)
| Widget | Tipo | Tamaño | Datos de |
|--------|------|--------|----------|
| Ventas Hoy | `kpi_ventas_hoy` | small | `consultarResumen` (índice 0) |
| Proformas Pendientes | `kpi_proformas_pendientes` | small | `consultarResumen` (índice 1) |
| Stock Crítico | `kpi_stock_critico` | small | `consultarResumen` (índice 2) |
| Vouchers Sin Asignar | `kpi_vouchers_sin_asignar` | small | `consultarResumen` (índice 3) |
| Mermas Hoy | `kpi_mermas_hoy` | small | `consultarResumen` (índice 4) |
| Valor Inventario | `kpi_valor_inventario` | small | `consultarResumen` (índice 5) |
| Resumen Ejecutivo | `resumen_ejecutivo` | medium | `consultarResumen` (6 KPIs en grid) |

#### Gráficos (7 widgets)
| Widget | Tipo | Chart | Tamaño |
|--------|------|-------|--------|
| Ventas Mensuales | `grafico_ventas_mes` | bar | medium |
| Top Productos | `grafico_top_productos` | horizontalBar | medium |
| Stock por Centro | `grafico_stock_centro` | donut | small |
| Método de Pago | `grafico_metodo_pago` | pie | small |
| Valor por Clase | `grafico_valorizacion_clase` | bar | medium |
| Mermas Mensuales | `grafico_mermas_mes` | bar | medium |
| Ventas vs Donaciones | `grafico_vs_donaciones` | area | large |

#### Tablas (4 widgets)
| Widget | Tipo | Tamaño |
|--------|------|--------|
| Stock Actual | `tabla_stock` | medium |
| Últimas Ventas | `tabla_ventas_recientes` | medium |
| Proformas | `tabla_proformas` | medium |
| Recomendaciones | `tabla_recomendaciones` | medium |

### 17.5. Layout por Defecto

Para nuevos usuarios sin configuración, se cargan 9 widgets automáticamente:
4 KPIs (`kpi_ventas_hoy`, `kpi_stock_critico`, `kpi_valor_inventario`, `kpi_mermas_hoy`) + 3 gráficos (`grafico_ventas_mes`, `grafico_stock_centro`, `grafico_metodo_pago`) + 2 tablas (`tabla_proformas`, `tabla_recomendaciones`).

### 17.6. Funcionalidades del Frontend

- **Grid CSS responsivo:** `grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))`. Tamaños: small (span 1), medium (span 2), large (span 1/-1)
- **Drag-and-drop:** HTML5 Drag and Drop API. Reordena widgets y actualiza `widgetsActivos[]`
- **Paleta de widgets:** Modal con catálogo agrupado por categorías. Click para agregar widget con datos precargados vía AJAX
- **Configuración por widget:** Modal para cambiar título y tamaño
- **Refresco individual:** Botón de refresh por widget que recarga sus datos vía `dash_widget`
- **Filtro global:** Inputs de fecha desde/hasta pasados como query params a `dash_load`
- **ApexCharts:** Renderizado dinámico con `buildChartOptions()` que mapea `grafico.tipo` a config de ApexCharts (bar, horizontalBar, donut, pie, area)
- **KPI colors:** Cada KPI tiene color distintivo (vede=valor inventario, rojo=stock crítico, naranja=proformas, etc.)

---

## 18. Submódulo Bandeja y Vouchers (produccion_agraria/bandeja)

Gestiona el ciclo de vida completo de proformas pendientes: visualización, procesamiento, anulación, y administración de vouchers de pago con archivos BLOB.

### 18.1. Archivos

| Archivo | Líneas | Propósito |
|---------|--------|-----------|
| `controllers/BandejaController.php` | 284 | 13 endpoints: proformas (4) + vouchers (8) + vista |
| `models/BandejaModel.php` | 276 | CRUD proformas: listar, obtener, procesar, anular (FIFO reversal), métodos de pago, correlativos |
| `models/VoucherModel.php` | 309 | CRUD vouchers: listar, guardar (BLOB), asignar, desasignar, eliminar, actualizar |
| `views/bandeja/index.php` | 1373 | Interfaz completa con tabla de proformas, 6 modales, ~20 funciones JS |

### 18.2. Endpoints AJAX

| Endpoint | Propósito |
|----------|-----------|
| `obtener_proforma` | Carga detalle completo (header + items + voucher info) |
| `procesar_proforma` | Cambia estado `PENDIENTE` → `PROCESADO`, asigna método_pago, serie, correlativo, id_voucher |
| `anular_proforma` | Revierte stock a lotes (FIFO), registra REINTEGRO en kardex, estado → `RECHAZADO` |
| `siguiente_correlativo` | `MAX(correlativo_comprobante) + 1` para una serie dada |
| `listar_vouchers` | Lista vouchers con `total_proformas` y `monto_asignado` agregados |
| `guardar_voucher` | Crea voucher con archivo BLOB (mismo patrón que imágenes) |
| `descargar_voucher` | Sirve archivo BLOB con Content-Type detectado por extensión |
| `listar_proformas_disponibles` | Proformas PENDIENTE disponibles para asignar a voucher |
| `asignar_voucher_proformas` | Transaccional: asigna voucher a proformas seleccionadas, las marca PROCESADO |
| `desasignar_voucher` | Revierte todas las proformas de un voucher a PENDIENTE |
| `eliminar_voucher` | Desasigna proformas + DELETE del voucher |
| `actualizar_voucher` | Edita metadata del voucher (num_operacion, monto, fecha) |

### 18.3. Flujo de Procesamiento

1. Usuario selecciona proforma PENDIENTE → clic en "Procesar"
2. Modal de procesamiento: elige método de pago (VENTA/DONACION), sube archivo (boucher/resolución), asigna serie y correlativo de comprobante
3. **Flujo VENTA:** Sube boucher → crea voucher → asigna voucher → procesa proforma
4. **Flujo DONACION:** Sube resolución → crea voucher (tipo DONACION) → asigna → procesa
5. El sistema calcula automáticamente el siguiente correlativo disponible

### 18.4. Anulación con Reversión FIFO

`BandejaModel::anularProforma()` revierte el descuento de stock:
1. Obtiene todas las líneas de detalle
2. Para cada línea: `lote.stock_actual = stock_actual + cantidad`
3. Inserta movimiento `REINTEGRO` en kardex
4. Cambia `transaccion.estado = 'RECHAZADO'` con motivo en `doc_justificante`

### 18.5. Vista

- **Tabla de proformas** con 8 columnas: ID, Fecha, Cliente, Centro, Método, Comprobante, Total, Acciones
- **Filtros:** Estado, fecha desde/hasta, búsqueda por cliente
- **6 modales:** Detalle, Procesar, Anular, Vouchers (lista), Nuevo Voucher (upload), Asignar Voucher
- El modal de procesamiento cambia dinámicamente según `metodo_pago` seleccionado (VENTA vs DONACION)
- **BLOB handling:** `fileToBase64()` → JSON → `base64_decode()` → `SQLSRV_SQLTYPE_VARBINARY('max')`
- **Descarga de voucher:** `descargar_voucher` sirve el binario con `Content-Disposition: inline` para PDF/imágenes

---

## 19. Submódulo Tablas (produccion_agraria/tablas)

CRUD de 4 entidades de catálogo para el sistema de Producción Agraria.

### 19.1. Archivos

| Archivo | Líneas | Propósito |
|---------|--------|-----------|
| `controllers/TablasController.php` | 161 | 13 endpoints: vista + 12 AJAX (3 CRUD × 4 entidades) |
| `models/TablasModel.php` | 237 | 16 métodos: 4 CRUD por entidad |
| `views/tablas/index.php` | 998 | Interfaz con tabs, 4 modales, ~600 líneas JS |

### 19.2. Entidades Gestionadas

| Entidad | Tabla | Campos | PK |
|---------|-------|--------|-----|
| **Clase** | `clase` | `id_clase`, `nombre_clase` | id_clase (IDENTITY) |
| **Centro** | `centro_produccion` | `id_centro`, `nombre_centro`, `ubicacion`, `encargado` | id_centro (IDENTITY) |
| **UIT** | `uit` | `anio`, `valor` | anio (natural key) |
| **Cliente** | `cliente` | `id_cliente`, `dni_ruc`, `nombre_rs`, `tipo_cliente` | id_cliente (IDENTITY) |

### 19.3. Endpoints AJAX por Entidad (3 × 4 = 12)

Cada entidad tiene: `obtener_<entidad>`, `guardar_<entidad>`, `eliminar_<entidad>`

| Entidad | Endpoints |
|---------|-----------|
| Clase | `obtener_clase`, `guardar_clase`, `eliminar_clase` |
| Centro | `obtener_centro`, `guardar_centro`, `eliminar_centro` |
| UIT | `obtener_uit`, `guardar_uit`, `eliminar_uit` |
| Cliente | `obtener_cliente`, `guardar_cliente`, `eliminar_cliente` |

### 19.4. Vista

- **4 tabs** (nav-tabs card-header-tabs): Clases, Centros, UIT, Clientes
- Cada tab tiene su propia tabla con botones Editar/Eliminar y botón "Nuevo"
- **4 modales** de formulario, uno por entidad
- **JavaScript:** Usa `FormData` para POST (no JSON). SweetAlert2 para confirmaciones. Bootstrap Toast para notificaciones.
- **AJAX response parsing:** Limpia la respuesta con regex para extraer JSON de respuestas potencialmente corruptas

---

## 20. Submódulo Reportes (produccion_agraria/reportes)

Sistema de reportes con 7 tipos de informes, KPIs, filtros dinámicos y exportación PDF/Excel con membrete institucional PECH.

### 20.1. Archivos

| Archivo | Líneas | Propósito |
|---------|--------|-----------|
| `controllers/ReportesController.php` | 172 | 9 endpoints: vista + 8 AJAX de datos |
| `models/ReportesModel.php` | 760 | 18 métodos con queries complejas, price calculation, consolidados |
| `views/reportes/index.php` | 1667 | Interfaz de menú de tarjetas + tabs + filtros + tablas + exportación |

### 20.2. Reportes Disponibles

| # | Reporte | Endpoint AJAX | KPIs |
|---|---------|---------------|------|
| 1 | Ventas y Facturación | `ventas_data` | Monto total, N° transacciones, Ticket promedio |
| 2 | Valorización de Inventario | `inventario_data` | Valor total almacén, Lotes activos |
| 3 | Mermas y Pérdidas | `mermas_data` | Valor estimado pérdidas, N° registros |
| 4 | Conciliación de Vouchers | `vouchers_report_data` | Total depósitos, Saldo libre |
| 5 | Clientes y Recaudación | `clientes_report_data` | (sin KPIs, tabla con totales acumulados) |
| 6 | Consolidado por Centro | `consolidado_report_data` | (sin KPIs, tabla resumen multi-query) |
| 7 | Catálogo de Precios | `precios_report_data` | (sin KPIs, tabla de precios vigentes) |

### 20.3. Dashboard Data Endpoints

| Endpoint | Datos |
|----------|-------|
| `dashboard_data` | `ventas_por_mes`, `metodos_pago` (distribución), `inventario_clase` (valorización) |

### 20.4. ReportesModel (760 líneas)

- **18 métodos** organizados por tipo de reporte
- **Price calculation** replicada en todos los métodos que requieren valorización (usando UIT actual + último historial_precio)
- **Consolidado por Centro** es la query más compleja: 4 subconsultas (ventas, donaciones, inventario, mermas) con LEFT JOIN sobre `centro_produccion`
- Todas las queries usan `BD_PRODUCCIONDESARROLLO.dbo.*` con prepared statements
- Fechas en español con `FORMAT(fecha, 'MMM yyyy', 'es-PE')`

### 20.5. Vista: Interfaz de Reportes

- **Menú de 7 tarjetas** con iconos y colores distintivos que al hacer clic abren el reporte correspondiente
- **Panel de filtros dinámico:** Muestra/oculta campos según el reporte activo (`reportConfigs` mapea tipo → filtros visibles)
- **Tabs** con tablas renderizadas vía JavaScript a partir de datos AJAX
- **KPIs** en cards con formato de moneda (`S/ X.XX`)
- **Exportación:**
  - **Excel:** CSV UTF-8 BOM delimitado por punto y coma
  - **PDF:** jsPDF + AutoTable con membrete PECH completo (logo, dirección, teléfonos, numeración de páginas)
  - El reporte de Precios usa formato **retrato A4 a dos columnas** agrupado por clase
  - Los demás reportes usan formato **apaisado** con tabla autoTable

---

---

## 21. Soft Delete en Tablas de Producción

**Implementado: 2026-06-25**

Siguiendo el mismo patrón de `comun.Usuarios`, se implementó soft delete en las tablas principales de producción. En lugar de DELETE físico, se usa `UPDATE SET activo = 0`.

### 21.1. Tablas con Soft Delete

| Tabla | Columna | Tipo | Default |
|-------|---------|------|---------|
| `producto` | `activo` | BIT | 1 |
| `clase` | `activo` | BIT | 1 |
| `centro_produccion` | `activo` | BIT | 1 |
| `cliente` | `activo` | BIT | 1 |
| `uit` | `activo` | BIT | 1 |
| `voucher_deposito` | `activo` | BIT | 1 |
| `dashboard_config` | `activo` | BIT | 1 (ya existía) |

### 21.2. Migraciones SQL

Scripts en `modules/produccion_agraria/database/`:
- `alter_producto_activo.sql`
- `alter_clase_activo.sql`
- `alter_centro_produccion_activo.sql`
- `alter_cliente_activo.sql`
- `alter_uit_activo.sql`
- `alter_voucher_deposito_activo.sql`

Todos usan `IF NOT EXISTS` para ejecución idempotente.

### 21.3. Cambios en Modelos

| Modelo | Método | Cambio |
|--------|--------|--------|
| `InventarioModel` | `eliminarProducto()` | DELETE → `UPDATE SET activo = 0` (sin handler FK 547) |
| `TablasModel` | `eliminarClase()` | DELETE → `UPDATE SET activo = 0` |
| `TablasModel` | `eliminarCentro()` | DELETE → `UPDATE SET activo = 0` |
| `TablasModel` | `eliminarCliente()` | DELETE → `UPDATE SET activo = 0` |
| `TablasModel` | `eliminarUit()` | DELETE → `UPDATE SET activo = 0` |
| `VoucherModel` | `eliminarVoucher()` | DELETE → `UPDATE SET activo = 0` |
| `DashboardModel` | `saveConfig()` | DELETE → `UPDATE SET activo = 0` |
| `DashboardModel` | `resetConfig()` | DELETE → `UPDATE SET activo = 0` |

### 21.4. Filtros `WHERE activo = 1` Agregados

Se agregaron filtros en **~80 queries** distribuidas en 8 archivos:
- `InventarioModel` — listados de productos, clases, centros, UIT, stock masivo
- `PuntoVentaModel` — clientes, productos, búsquedas
- `BandejaModel` — LEFT JOINs con cliente, centro, producto
- `VoucherModel` — listado de vouchers
- `ReportesModel` — los 7 reportes + catálogos auxiliares
- `ChatToolsModel` — las 17 tools del chatbot
- `TablasModel` — CRUD de clase, centro, cliente, UIT
- `DashboardModel` — ya filtraba `activo = 1` en `getConfig()`

### 21.5. Tablas SIN Soft Delete

| Tabla | Motivo |
|-------|--------|
| `lote` | No se borra; usa `stock_actual > 0` como indicador |
| `kardex` | Registro histórico de auditoría |
| `transaccion` | Tiene campo `estado` (PENDIENTE/PROCESADO/RECHAZADO) |
| `transaccion_detalle` | Vinculada al ciclo de vida de `transaccion` |
| `historial_precio` | Registro histórico |
| `clase_centro` | Tabla de vinculación M:N; sus DELETEs son sync, no borrado |
| `producto_centro` | Tabla de vinculación M:N; sus DELETEs son sync, no borrado |

### 21.6. Regla para Nuevos Desarrollos

- Toda nueva tabla de catálogo debe incluir `activo BIT NOT NULL DEFAULT 1`
- Usar `UPDATE SET activo = 0` en lugar de `DELETE` para "eliminar" registros
- Agregar `WHERE activo = 1` en todos los SELECTs que listen registros
- Las queries por ID directo (`WHERE id = ?`) pueden omitir el filtro para permitir acceso de auditoría

*Última actualización: 2026-06-25*
=======
# AGENTS.md — Contexto Técnico Completo: Sistema GestionTI 2026 · Módulo Laboratorio

> **Uso**: Este archivo sirve como contexto de dominio para IAs que trabajen sobre este repositorio.  
> Describe arquitectura, flujo de datos, tablas de base de datos, columnas clave y dependencias entre módulos.  
> **Última actualización**: 2026-05-29 — generado por análisis exhaustivo del código fuente.

---

## 1. Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Lenguaje backend | PHP 8.x (sin ORM, sin framework) |
| Base de datos | SQL Server 2019 — schema principal: `laboratorio`, schema auxiliar: `comun` |
| Driver BD | `sqlsrv` (Microsoft ODBC Driver 17) |
| Frontend | jQuery 3.6.0 · Bootstrap 5.1.3 · Tabler 1.0.0-beta17 · SweetAlert2 · DataTables 1.13.4 (server-side) |
| Servidor BD | `10.0.100.252` · base: `BD_GESTION_TI` |
| URL base | `/gestionTI/` |
| Auth | Sesión PHP — `$_SESSION['usuario_id']`, `$_SESSION['usuario_rol']` |
| Excel export | PhpSpreadsheet (autoload: `libs/vendor/autoload.php`) |

---

## 2. Estructura del Proyecto

```
d:\SISTEMAS\gestionTI2026\
├── index.php                          # Router principal (híbrido GET ?module=&action=&subaction=)
├── web.config                         # Configuración IIS
├── config/
│   ├── config.php                     # Constantes globales
│   └── db.php                         # Clase Conexion::conectar() → sqlsrv_connect()
├── core/
│   └── Auth.php                       # Auth::check() → verifica $_SESSION['usuario_id'] y activo=1
├── public/
│   ├── header.php                     # Layout HTML: navbar, sidebar, scripts globales
│   └── footer.php                     # Cierre layout
└── modules/
    ├── auth/                          # Login / logout / autenticar
    ├── dashboard/                     # Vista inicio
    ├── usuarios/                      # ABM usuarios (solo ADMIN)
    ├── sistemas/                      # Config sistema (solo ADMIN)
    ├── laboratorio/                   # ← NÚCLEO DEL SISTEMA
    │   ├── Validaciones.php           # Clase con reglas de negocio compartidas
    │   ├── controllers/
    │   │   └── LaboratorioController.php   # Dispatcher: switch($action) → sub-controladores
    │   ├── models/
    │   │   └── LaboratorioModel.php        # Usuarios, roles, permisos por submódulo
    │   ├── views/
    │   │   └── index.php                   # Hub de tabs del módulo laboratorio
    │   ├── muestra/
    │   │   ├── controllers/
    │   │   │   ├── AnalisisAPI.php          # AJAX: ingreso/lectura de resultados de análisis
    │   │   │   ├── MuestraAPI.php           # AJAX: recepción, firma, estado de muestras
    │   │   │   ├── MuestraController.php    # Dispatcher sub-vistas de muestra
    │   │   │   ├── ExportarCalidadAgua.php  # Export XLSX para proyectos CC y Drenes
    │   │   │   ├── ExportarProyectoMonitoreo.php  # Export XLSX para proyectos Monitoreo
    │   │   │   ├── ExportarBitacorasPorDefecto.php
    │   │   │   └── ExportarResultadosPasados.php
    │   │   ├── models/
    │   │   │   ├── MuestraModel.php         # CRUD Muestra_Lab, recepción, estados
    │   │   │   ├── ProyectoModel.php        # CRUD proyectos + creación masiva de muestras
    │   │   │   ├── SolicitudAnalisisModel.php
    │   │   │   └── ResultadoAnalisisModel.php
    │   │   └── views/
    │   │       ├── index.php                # SPA: tabs Monitoreo/Calidad/Drenes + DataTables
    │   │       ├── creacion_masiva_api.php  # AJAX backend para CRUD de proyectos
    │   │       ├── data_periodos.php        # DataTable server-side: lista de proyectos
    │   │       ├── analisis_proyecto.php    # Tabla Excel de ingreso de resultados
    │   │       ├── recepcion_formulario.php # Formulario de recepción individual
    │   │       ├── por_defecto.php          # Vista principal de muestras por estado
    │   │       ├── ver_progreso.php         # Vista de muestras en progreso
    │   │       ├── ver_firmar.php           # Vista para firma de resultados
    │   │       └── firma_agricultor.php
    │   ├── equipo/
    │   │   ├── controllers/ (EquipoController.php, EquipoAPI.php)
    │   │   └── models/ (EquipoModel.php, EquipoEstadoModel.php, RequisitoEquipoModel.php)
    │   ├── reactivo/
    │   │   ├── controllers/
    │   │   └── models/ (ReactivoModel.php, IngresoModel.php, AjusteInventarioModel.php,
    │   │                  BitacoraModel.php, UnidadMedidaModel.php)
    │   ├── servicio/
    │   │   └── models/ (ServicioModel.php, RecetaServicioModel.php, ServicioResiduoModel.php)
    │   ├── parametro/
    │   │   └── models/ (ParametroModel.php, NormativaModel.php, LimiteModel.php)
    │   ├── venta/
    │   │   └── models/ (VentaModel.php, ProductoServicioModel.php)
    │   ├── residuo/
    │   │   └── models/ (ResiduoModel.php, RegistroResiduosModel.php, NormativaSST.php)
    │   └── proveedor/
    ├── adquisiciones/
    ├── agricola/
    ├── certificados/
    ├── inventario/
    ├── patrimonio/
    ├── reportestecnicos/
    └── salas/
```

### Patrón de Routing

```
GET ?module=laboratorio&action=muestra&subaction=analisis_proyecto&id_proyecto=23
 └─► index.php (línea 60+)
      ├── Auth::check() → verifica sesión
      ├── include public/header.php
      └── include modules/laboratorio/controllers/LaboratorioController.php
           └── switch($action)
                └── case 'muestra' → include MuestraController.php
                     └── switch($subaction)
                          └── case 'analisis_proyecto' → include views/analisis_proyecto.php
```

**AJAX requests** apuntan siempre a archivos `*_api.php` / `*API.php` que:
1. Setean `header('Content-Type: application/json; charset=utf-8')`
2. Parsean `$_GET`, `$_POST`, o `file_get_contents('php://input')` (JSON body)
3. Responden `{ success: bool, ...datos }` o lanzan HTTP 400/500 con `{ success: false, message: '...' }`

### Conexión a Base de Datos

```php
// config/db.php
class Conexion {
    static public function conectar() {
        $serverName = "10.0.100.252";
        $connectionOptions = [
            "Database" => "BD_GESTION_TI",
            "Uid" => "sa", "PWD" => "...",
            "CharacterSet" => "UTF-8",
            "TrustServerCertificate" => true,
            "Encrypt" => true
        ];
        return sqlsrv_connect($serverName, $connectionOptions);
    }
}
```

Cada API/controller llama `$conn = Conexion::conectar()` al inicio. Si falla, retorna JSON 500.

### Sistema de Roles y Permisos (LaboratorioModel)

```
comun.Usuarios.rol → 'ADMIN' | 'LABORAL' | 'USUARIO'

laboratorio.Usuario_Rol → laboratorio.Rol (Id_Rol, Nombre)
laboratorio.Rol_Permiso → laboratorio.Submodulo (Id_Submodulo, Nombre, Url)
laboratorio.Permiso_Submodulo → permisos detallados (leer, escribir, firmar, etc.)

LaboratorioModel::obtenerResponsabilidades($id_usuario):
  - Si esAdministrador() → devuelve todos los Submodulos activos
  - Si no → devuelve solo los Submodulos del Rol asignado al usuario
```

---

## 3. Modelo de Datos — Tablas Clave (schema `laboratorio`)

### 3.1 Tablas Maestras (Catálogos)

| Tabla | PK | Descripción |
|---|---|---|
| `Servicio_Tecnico` | `Id_Servicio` | Tipos de análisis (Análisis de pH, Nitrato, etc.) |
| `Parametro_Analisis` | `Id_Parametro` | Parámetros medibles asociados a un servicio |
| `Normativa_Legal` | `Id_Normativa` | Normativas/estándares (ECA, OMS, etc.) |
| `Limite_Legal` | `Id_Limite` | Límites por parámetro y normativa |
| `Reactivo_Lab` | `Id_Reactivo` | Reactivos con stock, reserva y vencimiento |
| `Unidad_Medida` | `Id_Unidad_Medida` | Unidades para reactivos |
| `Receta_Servicio` | (`Id_Reactivo`, `Id_Servicio`) | Cantidad de reactivo necesaria por servicio |
| `Producto_Venta` | `Id_Producto` | Paquetes de análisis vendibles |
| `Producto_Servicio` | (`Id_Producto`, `Id_Servicio`) | Qué servicios incluye cada producto |
| `Equipo_Lab` | `Id_Equipo` | Equipos del laboratorio |
| `Equipo_Estado` | `Id_Estado` | Estados de equipo (Operativo, Mantenimiento, etc.) |
| `Residuo_Catalogo` | `Id_Residuo_Cat` | Catálogo de tipos de residuos |
| `Cliente` | `Id_Cliente` | Clientes/agricultores del laboratorio |
| `Submodulo` | `Id_Submodulo` | Submódulos del sistema (para permisos) |
| `Rol` | `Id_Rol` | Roles de laboratorio (distintos del rol comun) |
| `Usuario_Rol` | (`Id_Usuario`, `Id_Rol`) | Asignación de rol de laboratorio a un usuario |

### 3.2 Tablas de Proceso (Transaccionales)

| Tabla | PK | Descripción |
|---|---|---|
| `Proyecto_Monitoreo` | `Id_Proyecto` | Proyecto de creación masiva (monitoreo / calidad / drenes) |
| `Proyecto_Detalle_Analisis` | `Id_Detalle_Proyecto` | Productos planificados por proyecto (cantidad) |
| `Muestra_Lab` | `Id_Muestra` | Registro central de cada muestra física |
| `Muestra_Producto` | `Id_Muestra_Producto` | Producto de venta asociado a la muestra |
| `Solicitud_Analisis` | `Id_Solicitud_Analisis` | Pedido de análisis de un servicio para una muestra |
| `Resultado_Analisis` | `Id_Resultado` | Valor hallado para un parámetro en una solicitud |
| `Detalle_Agua` | `Id_Detalle_Agua` | Metadata de muestra de tipo Agua |
| `Detalle_Suelo` | `Id_Detalle_Suelo` | Metadata de muestra de tipo Suelo |
| `Muestra_Bitacora` | `Id_Bitacora` | Historial de cambios/derivaciones de muestras |
| `Ingreso_Reactivo` | `Id_Ingreso` | Entradas al stock de reactivos |
| `Ajuste_Inventario` | `Id_Ajuste` | Correcciones manuales de stock (Tipo_Ajuste: Entrada/Salida) |
| `Movimiento_Kardex` | `Id_Movimiento` | Kardex de cada movimiento de reactivo (Tipo_Movimiento: 'E'/'S') |
| `Consumo_Reaccion` | — | Relaciona Movimiento_Kardex con Muestra_Producto |
| `Registro_Residuos_Log` | `Id_Registro_Res` | Registro mensual de generación de residuos |

### 3.3 Columnas Críticas de `Muestra_Lab`

```sql
Id_Muestra          INT PK IDENTITY
Id_Cliente          INT FK → laboratorio.Cliente
Id_Receptor         INT FK → comun.Usuarios   -- quién recepcionó físicamente
Id_Especialista     INT FK → comun.Usuarios   -- analista asignado
Id_Proyecto         INT FK → Proyecto_Monitoreo  (NULL si muestra individual)
Valle               NVARCHAR(100)
Eje_X               NVARCHAR(50)   -- coordenada GPS X
Eje_Y               NVARCHAR(50)   -- coordenada GPS Y
Fecha_Recepcion     DATETIME
Fecha_Toma          DATETIME
Estado              NVARCHAR(20)   -- ver ciclo de vida abajo
Tipo_Servicio       NVARCHAR(50)   -- 'interno'|'externo'|nombre del producto
Observacion_Muestra NVARCHAR(MAX)
Ruta_Imagen         NVARCHAR(MAX)  -- path a imagen adjunta (base64 o ruta)
Es_Control_Calidad  BIT DEFAULT 0  -- 1 = proyecto de Calidad de Agua
Es_Drene            BIT DEFAULT 0  -- 1 = proyecto de Drenes
Fecha_Analisis      DATETIME       -- fecha en que inician los análisis del proyecto
Id_Jefe_Lab         INT FK → comun.Usuarios
Fecha_Validacion    DATETIME
Activo              BIT DEFAULT 1
Usuario_Creacion    INT FK → comun.Usuarios
Fecha_Creacion      DATETIME
Fecha_Modificacion  DATETIME
```

**Ciclo de vida de `Estado`:**

```
[Individual]  Por Recepcionar → Recepcionado → En Análisis → Completado → Finalizado
                              ↘ Rechazado
[Masiva]      → En Análisis (directamente al crear desde proyecto)
```

### 3.4 Columnas Críticas de `Proyecto_Monitoreo`

```sql
Id_Proyecto         INT PK IDENTITY
Nombre_Proyecto     NVARCHAR(200) NOT NULL
Valle               NVARCHAR(100)
Temporada           NVARCHAR(20)   -- e.g. '2026-II'
Fecha_Inicio        DATE
Tipo_Muestra        NVARCHAR(20)   -- 'Agua'|'Suelo'
Uso_Agua            NVARCHAR(100)  -- 'Riego'|'Consumo Humano'
Fuente_Agua         NVARCHAR(100)  -- TIPO de fuente: 'Superficial'|'Subterráneo'
Nivel_Agua          NVARCHAR(100)  -- Nombre de fuente ingresado en el form (ej: 'Rio', 'Canal')
Es_Control_Calidad  BIT DEFAULT 0
Es_Drene            BIT DEFAULT 0
Id_Responsable      INT FK → comun.Usuarios
Estado              NVARCHAR(20)   -- 'Planificado'|'En Progreso'|'Terminado'
Activo              BIT DEFAULT 1
Usuario_Creacion    INT FK → comun.Usuarios
Fecha_Creacion      DATETIME
Fecha_Modificacion  DATETIME
```

### 3.5 Columnas Críticas de `Detalle_Agua`

```sql
Id_Detalle_Agua     INT PK IDENTITY
Id_Muestra          INT FK → Muestra_Lab
Uso_Agua            NVARCHAR(100)   -- copiado de Proyecto_Monitoreo.Uso_Agua
Fuente_Agua         NVARCHAR(100)   -- TIPO de fuente (= $proyecto['Nivel_Agua'])
Cantidad_Muestra    NVARCHAR(20)    -- siempre '1 Litro' en creación masiva
Nivel_Agua          NVARCHAR(200)   -- NOMBRE individual de la fuente (RIO TABLACHACA / DREN DV-4.3...)
Activo              BIT DEFAULT 1
Usuario_Creacion    INT
Fecha_Creacion      DATETIME
```

> **Inversión semántica Fuente/Nivel**: Al almacenar en `Detalle_Agua`:
> - `Fuente_Agua` ← `$proyecto['Nivel_Agua']` (el "tipo": Superficial, Subterráneo, Canal…)
> - `Nivel_Agua`  ← `$fuente_muestra` (el nombre individual: "RIO TABLACHACA", "DREN DV-4.3…")

### 3.6 Columnas Críticas de `Reactivo_Lab`

```sql
Id_Reactivo         INT PK IDENTITY
Nombre              NVARCHAR(200)
Tipo                NVARCHAR(100)
Fecha_Vencimiento   DATE
Cantidad_Inicial    FLOAT   -- cantidad referencial al crear el reactivo
Cantidad_Stock      FLOAT   -- stock real disponible (aumenta con ingresos, baja con consumos)
Cantidad_Reservada  FLOAT   -- reservado por proyectos planificados (trigger TR_Reserva)
Id_Proveedor        INT NULL FK  -- añadido dinámicamente por ReactivoModel::migrarColumnas()
Id_Unidad_Medida    INT NULL FK  -- añadido dinámicamente por ReactivoModel::migrarColumnas()
Activo              BIT DEFAULT 1
```

### 3.7 Columnas Críticas de `Proyecto_Detalle_Analisis`

```sql
Id_Detalle_Proyecto INT PK IDENTITY
Id_Proyecto         INT FK → Proyecto_Monitoreo
Id_Producto_Venta   INT FK → Producto_Venta
Cantidad_Planificada INT
Activo              BIT DEFAULT 1
```

> **Trigger `TR_Reserva_Reactivos_Proyecto`**: Se activa en INSERT/UPDATE/DELETE de esta tabla.
> - INSERT o UPDATE: calcula `Receta_Servicio.Cantidad_Necesaria × Cantidad_Planificada` para cada reactivo y actualiza `Reactivo_Lab.Cantidad_Reservada`.
> - DELETE real: libera la reserva. Por eso `eliminarDetalle()` usa `DELETE` real, no soft-delete.

### 3.8 Columnas de `Movimiento_Kardex` y `Consumo_Reaccion`

```sql
-- Movimiento_Kardex
Id_Movimiento       INT PK IDENTITY
Id_Reactivo         INT FK → Reactivo_Lab
Tipo_Movimiento     CHAR(1)   -- 'E' Entrada | 'S' Salida
Cantidad            FLOAT
Activo              BIT DEFAULT 1

-- Consumo_Reaccion
Id_Movimiento       INT FK → Movimiento_Kardex
Id_Muestra_Producto INT FK → Muestra_Producto
Activo              BIT DEFAULT 1
```

Cuando se rechaza una muestra (`confirmarRecepcion(..., pasa=false)`), el sistema:
1. Busca los `Movimiento_Kardex` de tipo 'S' ligados a la `Muestra_Producto`
2. Restaura `Reactivo_Lab.Cantidad_Stock += Cantidad` por cada movimiento
3. Marca `Movimiento_Kardex.Activo = 0` y `Consumo_Reaccion.Activo = 0`

---

## 4. Módulos del Sistema de Laboratorio

---

### 4.1 Módulo: Configuración de Servicios (`/laboratorio/servicio`)

**Propósito**: Define los análisis técnicos disponibles (pH, Nitrato, etc.) y su relación con reactivos necesarios.

#### Tablas involucradas

| Operación | Tabla | Columnas |
|---|---|---|
| SELECT | `Servicio_Tecnico` | `Id_Servicio`, `Nombre`, `Descripcion`, `Tipo_Muestra`, `Requiere_Reactivos` |
| INSERT/UPDATE | `Servicio_Tecnico` | ídem + `Usuario_Creacion`, `Activo`, `Fecha_Creacion` |
| Soft-delete | `Servicio_Tecnico` | `Activo = 0` |
| SELECT | `Receta_Servicio` | `Id_Reactivo`, `Id_Servicio`, `Cantidad_Necesaria` |
| UPSERT | `Receta_Servicio` | Si existe → UPDATE (reactiva si estaba inactivo); si no → INSERT |
| Soft-delete | `Receta_Servicio` | `Activo = 0` |

#### Notas de implementación

- `ServicioModel::guardar()` detecta dinámicamente si existe la columna `Requiere_Reactivos` antes de insertarla (autoevolutivo).
- `RecetaServicioModel::guardar()` hace un CHECK previo `WHERE Id_Reactivo=? AND Id_Servicio=?` (incluyendo inactivos) para decidir UPDATE o INSERT, garantizando idempotencia.

---

### 4.2 Módulo: Parámetros de Análisis (`/laboratorio/parametro`)

**Propósito**: Define qué se mide dentro de cada servicio y los límites legales aplicables.

#### Tablas involucradas

| Operación | Tabla | Columnas |
|---|---|---|
| SELECT | `Parametro_Analisis` | `Id_Parametro`, `Id_Servicio`, `Nombre`, `Unidad_Medida`, `Categoria`, `Metodo_Utilizado` |
| INSERT/UPDATE | `Parametro_Analisis` | ídem + `Usuario_Creacion`, `Activo` |
| Soft-delete | `Parametro_Analisis` | `Activo = 0` (bloqueo si tiene `Limite_Legal` activos) |
| SELECT/INSERT | `Normativa_Legal` | `Id_Normativa`, `Nombre` |
| INSERT/UPDATE | `Limite_Legal` | `Id_Limite`, `Id_Parametro`, `Id_Normativa`, `Valor_Min`, `Valor_Max`, `Descripcion` |

#### Notas de implementación

- `LimiteModel::guardar()` detecta dinámicamente si existe la columna `Descripcion` en `Limite_Legal` (autoevolutivo).
- `Limite_Legal.Descripcion` es el campo usado para clasificar límites por categoría (Riego, Consumo Humano, Animal) al exportar a Excel. Se normaliza quitando tildes y comparando con `strpos`.
- `obtenerCategoriasLimite` en `creacion_masiva_api.php` devuelve las categorías únicas ordenadas por prioridad: Riego (1), Animal (2), Humano (3), resto (9).

---

### 4.3 Módulo: Productos de Venta (`/laboratorio/venta`)

**Propósito**: Define los paquetes comerciales y qué servicios técnicos incluyen.

#### Tablas involucradas

| Operación | Tabla | Columnas |
|---|---|---|
| SELECT/INSERT/UPDATE | `Producto_Venta` | `Id_Producto`, `Nombre_Comercial`, `Descripcion`, `Precio_Venta`, `Tipo`, `Tipo_Vista` |
| Soft-delete | `Producto_Venta` | `Activo = 0` |
| INSERT | `Producto_Servicio` | `Id_Producto`, `Id_Servicio`, `Activo` |
| Soft-delete | `Producto_Servicio` | `Activo = 0` |

#### `Tipo_Vista` — segmentación de visibilidad

- `GENERAL`: visible en todos los contextos.
- `INTERNO`: solo para operadores internos.
- `VentaModel::obtenerTodos($scope)` recibe `GENERAL`, `INTERNO` o `INTERNO_GENERAL` (default) y filtra con `Tipo_Vista IN (?, ?)` o `AND Tipo_Vista = ?` según corresponda.
- `VentaModel` detecta dinámicamente si la columna `Tipo_Vista` existe antes de filtrar.

---

### 4.4 Módulo: Reactivos (`/laboratorio/reactivo`)

**Propósito**: Inventario de reactivos químicos con control de stock, reservas y trazabilidad.

#### Tablas involucradas

| Operación | Tabla | Acción |
|---|---|---|
| SELECT/INSERT/UPDATE | `Reactivo_Lab` | CRUD con migración automática de columnas |
| INSERT | `Ingreso_Reactivo` | Registra entrada de stock (trigger o lógica PHP actualiza `Cantidad_Stock`) |
| INSERT/UPDATE | `Ajuste_Inventario` | Correcciones manuales (`Tipo_Ajuste`: Entrada / Salida) |
| INSERT | `Movimiento_Kardex` | Kardex por consumo al crear muestras (`Tipo_Movimiento`: 'E'/'S') |
| INSERT | `Consumo_Reaccion` | Relaciona movimiento kardex con `Muestra_Producto` |

#### Mecánica de reservas (CRÍTICO)

```
guardarDetalle(id_proyecto, id_producto, cantidad_nueva)
  → validarReservaReactivosDetalle():
      Para cada reactivo de la receta del producto:
        demanda_delta = (cantidad_nueva - cantidad_actual) × Receta_Servicio.Cantidad_Necesaria
        si demanda_delta > Reactivo_Lab.Cantidad_Stock disponible → lanza Exception
  → UPSERT Proyecto_Detalle_Analisis
  → Trigger TR_Reserva_Reactivos_Proyecto actualiza Reactivo_Lab.Cantidad_Reservada

eliminarDetalle(id_detalle)
  → DELETE REAL (no soft-delete) para activar el trigger
  → Trigger libera: Reactivo_Lab.Cantidad_Reservada -= cantidad_reservada_anterior
```

#### Mecánica de consumo real (al crear muestras)

```
registrarConsumoReactivosInterno($idMuestraProducto, $usuarioId)
  → CHECK: si ya existe Consumo_Reaccion para esta Muestra_Producto → skip (idempotente)
  → Para cada servicio de la muestra y cada reactivo de su receta:
      INSERT Movimiento_Kardex (Tipo_Movimiento='S', Cantidad=...)
      INSERT Consumo_Reaccion (Id_Movimiento, Id_Muestra_Producto)
      UPDATE Reactivo_Lab SET Cantidad_Stock = Cantidad_Stock - Cantidad
```

#### Reversión al rechazar muestra

```
confirmarRecepcion(..., pasa=false)
  → SELECT Movimiento_Kardex JOIN Consumo_Reaccion WHERE Id_Muestra = ? AND Tipo='S'
  → UPDATE Reactivo_Lab SET Cantidad_Stock = Cantidad_Stock + Cantidad
  → UPDATE Movimiento_Kardex SET Activo = 0
  → UPDATE Consumo_Reaccion SET Activo = 0
```

---

### 4.5 Módulo: Equipos (`/laboratorio/equipo`)

**Propósito**: Registro y control de equipos con estados y calibraciones.

#### Tablas involucradas

| Operación | Tabla | Columnas |
|---|---|---|
| SELECT/INSERT/UPDATE | `Equipo_Lab` | `Id_Equipo`, `Id_Estado`, `Nombre`, `Proveedor`, `Id_Proveedor`, `Fecha_Adquisicion`, `Fecha_Ultima_Calibracion`, `Fecha_Proxima_Calibracion` |
| SELECT | `Equipo_Estado` | `Id_Estado`, `Nombre` |
| INSERT/UPDATE | `Requisito_Equipo` | `Id_Equipo`, `Descripcion`, `Estado` |

`EquipoModel::migrarColumnas()` añade `Id_Proveedor` y `Fecha_Adquisicion` con `ALTER TABLE IF NOT EXISTS` al instanciar. Usa `OUTPUT INSERTED.Id_Equipo` en vez de `SCOPE_IDENTITY()`.

---

### 4.6 Módulo: Residuos (`/laboratorio/residuo`)

**Propósito**: Catálogo de residuos y registro mensual de generación.

#### Tablas involucradas

| Operación | Tabla | Columnas |
|---|---|---|
| SELECT/INSERT/UPDATE | `Residuo_Catalogo` | `Id_Residuo_Cat`, `Codigo_Item`, `Nombre_Item`, `Tipo_Principal`, `Subcategoria`, `Unidad_Referencia` |
| SELECT/INSERT/UPDATE | `Registro_Residuos_Log` | `Id_Registro_Res`, `Mes`, `Anio`, `Ubicacion`, `Codigo_SST`, `Id_Normativa_Aplicable` |

---

### 4.7 Módulo: Muestras — Flujo Individual (`/laboratorio/muestra`)

**Propósito**: Ciclo de vida completo de muestras individuales.

#### Subvistas disponibles (desde `MuestraController.php`)

| `subaction` | Vista | Descripción |
|---|---|---|
| `por_defecto` | `por_defecto.php` | Lista de muestras por estado |
| `recepcion_formulario` | `recepcion_formulario.php` | Formulario recepción individual |
| `ver_progreso` | `ver_progreso.php` | Muestras en análisis |
| `ver_firmar` | `ver_firmar.php` | Muestras para firma (requiere permiso `firmar`) |
| `firma_agricultor` | `firma_agricultor.php` | Firma digital (requiere permiso `firmar`) |
| `analisis_agricultor` | `analisis_agricultor.php` | Ingreso de resultados por muestra individual |
| `analisis_proyecto` | `analisis_proyecto.php` | Tabla Excel de resultados de proyecto |
| `creacion_masiva` | `creacion_masiva.php` | SPA de gestión de proyectos masivos |
| `bitacora_detalle` | `bitacora_detalle.php` | Historial de cambios de una muestra |
| `resultados_pasados` | `resultados_pasados.php` | Resultados históricos |

#### Flujo de recepción individual (detallado)

```
1. Usuario carga recepcion_formulario.php
2. Completa: cliente, especialista, tipo muestra, coordenadas GPS, observaciones
3. POST → MuestraAPI.php { action:'guardar', ...campos }
   → MuestraModel::guardar($datos)
     → INSERT Muestra_Lab (Estado='Por Recepcionar')
     ← Id_Muestra
4. Si tipo='Agua': INSERT Detalle_Agua
   Si tipo='Suelo': INSERT Detalle_Suelo
5. Si hay producto seleccionado:
   → INSERT Muestra_Producto
   → INSERT Solicitud_Analisis por cada servicio del producto
   → INSERT Resultado_Analisis (Valor_Hallado=NULL) por cada parámetro del servicio
   → registrarConsumoReactivosInterno() → Movimiento_Kardex + Consumo_Reaccion
6. POST → MuestraAPI.php { action:'confirmar_recepcion', id_muestra, pasa:bool, tipo_servicio, observacion, checklist }
   → Si pasa=false: restaurar stock reactivos (reversión Kardex) + Estado='Rechazado'
   → Si pasa=true: UPDATE Muestra_Lab Estado='Recepcionado'
7. POST → MuestraAPI.php { action:'iniciar_analisis_agricultor', id_muestra }
   → MuestraModel::iniciarAnalisisDesdeMuestra()
   → UPDATE Muestra_Lab Estado='En Análisis'
   → Crea Solicitud_Analisis + Resultado_Analisis si no existen aún
```

#### Flujo de análisis y firma

```
Analista ingresa resultados:
  → POST AnalisisAPI.php { action:'guardar_resultado', id_resultado, valor_hallado }
    → UPDATE Resultado_Analisis SET Valor_Hallado=?, Fecha_Modificacion=GETDATE()

Jefe de laboratorio firma:
  → POST MuestraAPI.php { action:'firmar_muestra', id_muestra, firmar_todos:bool }
    → UPDATE Muestra_Lab SET Estado='Finalizado', Id_Jefe_Lab=?, Fecha_Validacion=GETDATE()
```

---

### 4.8 Módulo: Creación Masiva de Muestras

**Propósito**: Crear lotes de muestras en un "Proyecto de Monitoreo". Tres tipos:

| Tipo | Flags | Fuentes por defecto |
|---|---|---|
| Monitoreo | `Es_Control_Calidad=0, Es_Drene=0` | Sin fuentes predefinidas |
| Calidad de Agua | `Es_Control_Calidad=1` | 10 fuentes (RIO TABLACHACA, RIO SANTA…) |
| Drenes | `Es_Drene=1` | 11 fuentes de dren (DREN DV-4.3…) |

#### Archivos clave

| Archivo | Rol |
|---|---|
| `muestra/views/index.php` | SPA con tabs, modales, DataTables server-side |
| `muestra/views/creacion_masiva_api.php` | Backend AJAX CRUD proyectos (no requiere Auth en session_start propio) |
| `muestra/views/data_periodos.php` | Feed DataTables — filtra por tipo en PHP post-query |
| `muestra/models/ProyectoModel.php` | Toda la lógica de negocio |

#### Flujo completo — Crear proyecto y ejecutar

```
FASE 1: Crear Proyecto (Estado='Planificado')
─────────────────────────────────────────────
POST creacion_masiva_api.php { action:'guardarProyecto', ...campos, servicios:[] }
  → sqlsrv_begin_transaction()
  → ProyectoModel::guardar($datos)
      → INSERT Proyecto_Monitoreo (Estado siempre 'Planificado')
      ← Id_Proyecto
  → Por cada servicio en $_POST['servicios']:
      cantidad = max(10, cantidad) si es CC o Drene
      ProyectoModel::guardarDetalle(Id_Proyecto, Id_Producto, Cantidad)
        → validarReservaReactivosDetalle() [falla si stock insuficiente]
        → UPSERT Proyecto_Detalle_Analisis
        → Trigger actualiza Reactivo_Lab.Cantidad_Reservada
  → sqlsrv_commit()
← { success:true, id_proyecto:N }

FASE 2: Iniciar Ejecución (Estado='En Progreso')
─────────────────────────────────────────────────
POST creacion_masiva_api.php { action:'generarMuestras', id_proyecto, fuentes_calidad[]?, fuentes_drene[]? }
  → ProyectoModel::guardar([Id_Proyecto, Estado:'En Progreso', Fuentes_Calidad, Fuentes_Drene])
      → SELECT Estado anterior
      → UPDATE Proyecto_Monitoreo SET Estado='En Progreso'
      → Como estado_anterior != 'En Progreso' → llamar crearMuestrasDesdePeriodo()
  → Contar muestras creadas con contarMuestrasPorProyecto()
← { success:true, muestras_creadas:N }

FASE 3: crearMuestrasDesdePeriodo() — motor central
─────────────────────────────────────────────────────
1. validarServiciosConParametros() — lanza Exception si algún servicio no tiene parámetros activos
2. obtenerIdClienteProyecto() — SELECT TOP 1 Id_Cliente FROM laboratorio.Cliente WHERE Activo=1
3. normalizarFuentesControlCalidad() — rellena array hasta total_muestras, valores vacíos → base rotativa
4. Para cada detalle (Cantidad_Planificada muestras por producto):
   Para i=0..Cantidad_Planificada:
     a. Calcular $fuente_muestra:
        - Si es CC: fuentes_calidad_normalizadas[$indice] (o base[$indice % 10])
        - Si es Drene: fuentes_drene_normalizadas[$indice]
        - Si no: $proyecto['Fuente_Agua']
     b. INSERT Muestra_Lab (Estado='En Análisis', Tipo_Servicio=Nombre_Producto, Observacion='Muestra de Proyecto: '+Nombre_Proyecto)
        ← Id_Muestra
     c. INSERT Muestra_Producto(Id_Muestra, Id_Producto_Venta, Id_Cliente)
        ← Id_Muestra_Producto
     d. Para cada servicio del producto (Producto_Servicio WHERE Id_Producto=? AND Activo=1):
        INSERT Solicitud_Analisis(Id_Muestra, Id_Servicio, Estado='En Análisis')
        ← Id_Solicitud
        Para cada parámetro del servicio (Parametro_Analisis WHERE Id_Servicio=? AND Activo=1):
          SELECT TOP 1 Id_Normativa FROM Limite_Legal WHERE Id_Parametro=?
          INSERT Resultado_Analisis(Id_Solicitud, Id_Parametro, Id_Normativa, Valor_Hallado=NULL)
     e. registrarConsumoReactivosInterno(Id_Muestra_Producto)
        → INSERT Movimiento_Kardex (Tipo='S') + INSERT Consumo_Reaccion
        → UPDATE Reactivo_Lab.Cantidad_Stock -= cantidad
     f. Si Tipo_Muestra='Agua' y Uso_Agua!=null:
        INSERT Detalle_Agua(Fuente_Agua=$proyecto['Nivel_Agua'], Nivel_Agua=$fuente_muestra)
5. asegurarResultadosProyecto() — garantiza cobertura idempotente:
   a. UPDATE Resultado_Analisis SET Activo=1 (reactiva filas previamente desactivadas)
   b. INSERT INTO Resultado_Analisis … SELECT (todas las combinaciones solicitud/parámetro faltantes)
```

#### `data_periodos.php` — filtrado por tipo

```
POST { draw, start, length, es_control_calidad, es_drene }
  → SELECT * FROM Proyecto_Monitoreo (+ join Usuarios) WHERE Activo=1
  → Filtro PHP en memoria:
    es_drene=1           → solo Es_Drene=1
    es_control_calidad=1 → solo Es_Control_Calidad=1
    es_control_calidad=0 → Es_Control_Calidad=0 AND Es_Drene=0  (Monitoreo puro)
  → Paginación PHP (array_slice)
  → Genera HTML de badges y botones según estado:
    Planificado → botón "Iniciar" (iniciarEjecucion(id))
    En Progreso → botón "Análisis" + botón "Exportar Excel"
    Finalizado  → botón "Ver Resultados" + botón "Exportar Excel"
    Siempre     → botón "Editar" + botón "Eliminar"
```

#### Editar Proyecto (solo si análisis no ha iniciado)

```
POST/JSON creacion_masiva_api.php { action:'editarProyecto', id_proyecto, ...campos, servicios[] }
  → proyectoModel->proyectoTieneAnalisisIniciado():
      Cuenta muestras activas Y resultados con valor/estado finalizado
  → sqlsrv_begin_transaction()
  → guardar($datos_completos) [UPDATE completo de campos]
  → Para cada servicio enviado:
      Si análisis iniciado: solo valida que no cambie cantidad ni agregue nuevos productos
      Si no iniciado: guardarDetalle() (UPSERT + trigger reserva)
  → Para servicios en mapa_actual pero NO en enviados (solo si no iniciado):
      eliminarDetalle(Id_Detalle_Proyecto) [DELETE real → trigger libera reserva]
  → sqlsrv_commit()
```

#### Fuentes de Control de Calidad por defecto

```php
['RIO TABLACHACA', 'RIO SANTA', 'ENTRADA DESARENADOR', 'SALIDA DESARENADOR',
 'CANAL EVACUADOR', 'RIO VIRU', 'RIO MOCHE', 'RIO CHICAMA',
 'CANAL MADRE', 'CENTRAL HIDROELECTRICA VIRU SAN JOSE']
// Para drenes: fuentes específicas de dren
```

Si el usuario no envía fuentes (o envía menos que el total de muestras), los valores vacíos se rellenan con `base[$indice % count($base)]` (rotación cíclica).

---

### 4.9 Módulo: Análisis de Proyecto (`analisis_proyecto.php`)

**Propósito**: Vista de ingreso de resultados para todas las muestras de un proyecto en formato tabla tipo Excel.

#### Consultas principales

```sql
-- Datos del proyecto
SELECT * FROM laboratorio.Proyecto_Monitoreo WHERE Id_Proyecto = ?

-- Muestras con fuente de agua (columna Dren/Fuente)
SELECT m.Id_Muestra,
       ROW_NUMBER() OVER (ORDER BY m.Id_Muestra) AS NumeroOrden,
       m.Tipo_Servicio, da.Nivel_Agua
FROM laboratorio.Muestra_Lab m
LEFT JOIN laboratorio.Detalle_Agua da ON da.Id_Muestra = m.Id_Muestra AND da.Activo = 1
WHERE m.Id_Proyecto = ? AND m.Activo = 1

-- Parámetros del proyecto (DISTINCT)
SELECT DISTINCT pa.Id_Parametro, pa.Nombre, pa.Unidad_Medida
FROM laboratorio.Parametro_Analisis pa
INNER JOIN laboratorio.Servicio_Tecnico st ON pa.Id_Servicio = st.Id_Servicio
INNER JOIN laboratorio.Solicitud_Analisis sa ON sa.Id_Servicio = st.Id_Servicio
INNER JOIN laboratorio.Muestra_Lab ml ON ml.Id_Muestra = sa.Id_Muestra
WHERE ml.Id_Proyecto = ? AND pa.Activo = 1

-- Mapa de resultados (Id_Muestra_Id_Parametro → {Id_Resultado, Valor_Hallado})
SELECT ra.Id_Resultado, ra.Id_Solicitud_Analisis, ra.Id_Parametro, ra.Valor_Hallado
FROM laboratorio.Resultado_Analisis ra
INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
WHERE sa.Id_Muestra IN (SELECT Id_Muestra FROM laboratorio.Muestra_Lab WHERE Id_Proyecto = ?)
AND ra.Activo = 1 AND sa.Activo = 1
```

#### Columna dinámica Dren/Fuente

- Si `Es_Control_Calidad=1` o `Es_Drene=1` → columna extra entre "No" y parámetros.
- Encabezado: `"Dren"` si `Es_Drene`, `"Fuente"` si `Es_Control_Calidad`.
- Valor mostrado: `Detalle_Agua.Nivel_Agua` (nombre individual).

#### Guardar resultado vía AJAX

```
POST AnalisisAPI.php { action:'guardar_resultado', id_resultado:N, valor_hallado:'...' }
  → UPDATE Resultado_Analisis SET Valor_Hallado=?, Fecha_Modificacion=GETDATE() WHERE Id_Resultado=?
```

#### Otros endpoints de AnalisisAPI.php

| Acción | Descripción |
|---|---|
| `obtener_solicitudes_proyecto` | Devuelve resumen de servicios del proyecto con `SolicitudAnalisisModel::obtenerResumenProyecto()` |
| `obtener_parametros_servicio` | Lista parámetros activos de un servicio ordenados por Categoria, Nombre |
| `obtener_contexto_consumo_extra` | Para consumos adicionales de reactivos: muestras + servicios + reactivos disponibles |
| `guardar_avance` | Guarda array de `{id_resultado, valor_hallado}` en bulk |
| `finalizar_proyecto` | Cambia Estado del proyecto a 'Terminado' |

---

### 4.10 Módulo: Exportación Excel

#### ExportarCalidadAgua.php (para CC y Drenes)

```
GET ?id_proyecto=X
  → SELECT Proyecto_Monitoreo
  → SELECT Muestra_Lab + Detalle_Agua (Nivel_Agua como fuente)
  → SELECT Parametro_Analisis + Categoria → agrupa en FISICO/QUIMICO/MICROBIOLOGICO/OTROS
  → SELECT Resultado_Analisis + Limite_Legal
  → Genera XLSX con PhpSpreadsheet:
      - Encabezados de categoría agrupados
      - Una fila por muestra, columnas = parámetros
      - Colorea rojo si Valor_Hallado > Valor_Max (o < Valor_Min)
      - Celdas E24, G24, E25, G25 en azul (coordenadas fijas del formato normativo)
      - Función _caq_fmtLim(): "min - max" | solo max | solo min
      - Función _caq_fmtNum(): 6 decimales con cero trailing eliminado
```

#### ExportarProyectoMonitoreo.php

```
GET ?id_proyecto=X&categorias[]=nombre_categoria
  → Filtra Limite_Legal WHERE Descripcion IN (categorias[]) → normalizando con _sinTildes()
  → Genera informe agrupado por categoría de límite
```

---

## 5. Dependencias Cruzadas entre Módulos (Grafo de Flujo)

```
Servicio_Tecnico ──────────────────────────────► Parametro_Analisis
     │                                                    │
     ▼                                                    ▼
Receta_Servicio                               Limite_Legal + Normativa_Legal
     │                                                    │
     ▼                                                    ▼
Reactivo_Lab ◄── reserva ── Proyecto_Detalle_Analisis    Resultado_Analisis
     │                             │                      ▲
     │      Movimiento_Kardex◄─────┤                      │
     │      Consumo_Reaccion       │                      │
     │                             │                      │
Producto_Venta ──► Producto_Servicio                      │
     │                     │                              │
     ▼                     ▼                              │
     └──────────► Proyecto_Monitoreo                      │
                       │                                  │
                       ▼                                  │
                  Muestra_Lab ───► Muestra_Producto        │
                       │               │                  │
                       │       Consumo_Reaccion            │
                       │               │                  │
                       │    Movimiento_Kardex              │
                       │                                  │
                       ├──► Detalle_Agua (Nivel_Agua=fuente individual)
                       ├──► Detalle_Suelo
                       └──► Solicitud_Analisis ───────────┘
```

---

## 6. Autenticación y Permisos

```php
// core/Auth.php
Auth::check()
  → Verifica $_SESSION['usuario_id'] existe y $_SESSION['activo'] == 1
  → Si no: redirect a ?module=auth&action=login

// Roles en $_SESSION['usuario_rol']:
'ADMIN'    → acceso total (módulos sistemas, usuarios)
'LABORAL'  → acceso a todos los módulos de laboratorio
'USUARIO'  → acceso restringido según configuración
```

### Sistema de Roles de Laboratorio (tablas adicionales)

```
laboratorio.Usuario_Rol:
  Id_Usuario INT FK → comun.Usuarios
  Id_Rol     INT FK → laboratorio.Rol
  Fecha_Asignacion DATETIME

laboratorio.Rol:
  Id_Rol       INT PK IDENTITY
  Nombre       NVARCHAR(100)
  Descripcion  NVARCHAR(MAX)
  Activo       BIT

laboratorio.Submodulo:
  Id_Submodulo INT PK IDENTITY
  Nombre       NVARCHAR(100)
  Icono        NVARCHAR(50)
  Descripcion  NVARCHAR(MAX)
  Url          NVARCHAR(200)    -- e.g. 'muestra', 'reactivo', 'equipo'
  Activo       BIT

laboratorio.Permiso_Submodulo:
  Id_Rol        INT FK → laboratorio.Rol
  Id_Submodulo  INT FK → laboratorio.Submodulo
  leer          BIT
  escribir      BIT
  firmar        BIT
  ... (otros permisos)
```

`LaboratorioModel::obtenerPermisosSubmodulo($id_usuario, $url)`:
- Busca el `Submodulo.Id_Submodulo` por `Url = $url`
- Une con `Permiso_Submodulo` para el rol del usuario
- Retorna objeto `{firmar:bool, leer:bool, escribir:bool}`
- La vista `ver_firmar.php` y `firma_agricultor.php` requieren `firmar=true`

---

## 7. Convenciones de Código

| Convención | Descripción |
|---|---|
| Soft-delete | `UPDATE ... SET Activo = 0` — nunca DELETE real (excepto `eliminarDetalle` para trigger) |
| Auditoría | Todas las tablas tienen `Usuario_Creacion INT`, `Fecha_Creacion DATETIME`, `Fecha_Modificacion DATETIME` |
| AJAX JSON | Siempre responde `{ success: bool, error?: string, ...data }` con HTTP 400 en excepciones |
| Parámetros SQL | Siempre parametrizados (`sqlsrv_query($db, $sql, $params)`) — nunca interpolación |
| Migraciones | Columnas nuevas se añaden vía `ALTER TABLE IF NOT EXISTS` al instanciar el modelo (autoevolutivo) |
| Transacciones | Operaciones multi-tabla usan `sqlsrv_begin_transaction()` / `sqlsrv_commit()` / `sqlsrv_rollback()` |
| IDs en PHP | Siempre `intval()` antes de usar en queries |
| Fechas | Columnas `DATETIME` retornan objetos `DateTime` de sqlsrv; formateadas con `->format('d-m-Y')` |
| SCOPE_IDENTITY | Recuperado con `sqlsrv_next_result($stmt); $row = sqlsrv_fetch_array(...)` después de INSERT |
| Log de debug | `file_put_contents($log_file, '...', FILE_APPEND)` — archivos `.log` en dirs de modelo durante desarrollo |

---

## 8. Endpoints AJAX del Módulo Laboratorio

### `creacion_masiva_api.php`

| Acción | Método | Parámetros entrada | Respuesta |
|---|---|---|---|
| `obtenerServicios` | GET | `scope` (INTERNO_GENERAL/GENERAL) | `[{id, nombre, tipo}]` |
| `obtenerDetalles` | GET | `id` | `{proyecto:{}, detalles:[], analisis_iniciado, puede_editar_cantidades}` |
| `obtenerCategoriasLimite` | GET | `id_proyecto` | `{categorias:[]}` — ordenadas por prioridad |
| `guardarProyecto` | POST | `nombre_proyecto`, `valle`, `temporada`, `fecha_inicio`, `tipo_muestra`, `uso_agua`, `fuente_agua`, `nivel_agua`, `es_control_calidad`, `es_drene`, `servicios[]` | `{success, id_proyecto}` |
| `editarProyecto` | POST (JSON body) | ídem + `id_proyecto`, `servicios[]` (con eliminaciones automáticas) | `{success, mensaje}` |
| `generarMuestras` | POST | `id_proyecto`, `fuentes_calidad[]?`, `fuentes_drene[]?` | `{success, muestras_creadas}` |
| `eliminarProyecto` | POST | `id` | `{success}` |
| `agregarMuestrasAdicionales` | POST | `id_proyecto`, `extras:[{id, cantidad_extra}]` | `{success, muestras_creadas}` |

### `AnalisisAPI.php`

| Acción | Método | Parámetros entrada | Respuesta |
|---|---|---|---|
| `obtener_solicitudes_proyecto` | GET | `id_proyecto` | `{success, servicios:[]}` |
| `obtener_parametros_servicio` | GET | `id_servicio` | `{success, parametros:[]}` |
| `obtener_contexto_consumo_extra` | GET | `ids_muestras` (CSV) | `{success, muestras:[], servicios:[], reactivos:[]}` |
| `guardar_resultado` | POST | `id_resultado`, `valor_hallado` | `{success}` |
| `guardar_avance` | POST | `resultados:[{id, valor}]` | `{success, guardados:N}` |
| `finalizar_proyecto` | POST | `id_proyecto` | `{success}` |

### `MuestraAPI.php`

| Acción | Método | Parámetros entrada | Respuesta |
|---|---|---|---|
| `guardar` | POST | todos los campos de muestra | `{success, id_muestra}` |
| `confirmar_recepcion` | POST (JSON) | `id_muestra`, `pasa:bool`, `tipo_servicio`, `observacion`, `checklist` | `{success}` |
| `iniciar_analisis_agricultor` | POST (JSON) | `id_muestra`, `usuario_id`, `iniciar_todos:bool` | `{success, muestras_actualizadas, solicitudes_creadas}` |
| `cambiarEstado` | POST | `id_muestra`, `estado` | `{success}` |
| `eliminar` | POST | `id_muestra` | `{success}` |
| `firmar_muestra` | POST (JSON) | `id_muestra`, `firmar_todos:bool` | `{success}` |

---

## 9. Reglas de Negocio Críticas

1. **Mínimo 10 muestras por servicio** en proyectos de Calidad de Agua o Drenes.
2. **No se pueden modificar cantidades** una vez que el análisis está "En Progreso" (muestras ya creadas).
3. **No se pueden agregar nuevos productos** a un proyecto "En Progreso".
4. **Eliminar `Proyecto_Detalle_Analisis`** dispara el trigger que libera `Cantidad_Reservada` en `Reactivo_Lab` → DELETE real, nunca soft-delete.
5. **`Nivel_Agua` en `Detalle_Agua`** almacena el nombre individual de la fuente (fuente rotativa de calidad o nombre del dren), no el tipo genérico.
6. **`Fuente_Agua` en `Detalle_Agua`** almacena el tipo genérico (`$proyecto['Nivel_Agua']`: Río, Canal, Superficial, Pozo).
7. **`Fuentes_Calidad` / `Fuentes_Drene`** son arrays con N nombres; si son menos que N se rellenan vía `$base[$indice % count($base)]`.
8. **`clavesNoEstado`** en `ProyectoModel::guardar()` excluye `['Id_Proyecto', 'Estado', 'Fuentes_Calidad', 'Fuentes_Drene']` para detectar update solo-estado y no corromper campos del proyecto.
9. **`asegurarResultadosProyecto()`** se llama al final de `crearMuestrasDesdePeriodo` y `agregarMuestrasAdicionales` para garantizar cobertura completa (idempotente: reactiva inactivos e inserta faltantes).
10. **`validarServiciosConParametros()`** se llama antes de crear muestras para prevenir `Solicitud_Analisis` sin `Resultado_Analisis` (lanzaría Exception si algún servicio del proyecto no tiene parámetros activos).
11. **`registrarConsumoReactivosInterno()`** es idempotente: verifica existencia en `Consumo_Reaccion` antes de insertar para evitar doble descuento de stock.
12. **`Reactivo_Lab.Cantidad_Stock`** comienza en 0 al crear el reactivo; el stock real se agrega vía `Ingreso_Reactivo`.

---

## 10. Migraciones Ejecutadas

```sql
-- Ya ejecutadas (sesión 2026-05-29):
ALTER TABLE laboratorio.Muestra_Lab ADD Es_Drene BIT DEFAULT 0;
ALTER TABLE laboratorio.Proyecto_Monitoreo ADD Es_Drene BIT DEFAULT 0;
ALTER TABLE laboratorio.Detalle_Agua ALTER COLUMN Nivel_Agua NVARCHAR(200);

-- Autoevolutivas (ejecutadas al instanciar modelo):
-- ReactivoModel::migrarColumnas():
ALTER TABLE laboratorio.Reactivo_Lab ADD Id_Unidad_Medida INT NULL;
ALTER TABLE laboratorio.Reactivo_Lab ADD Id_Proveedor INT NULL;
-- EquipoModel::migrarColumnas():
ALTER TABLE laboratorio.Equipo_Lab ADD Id_Proveedor INT NULL;
ALTER TABLE laboratorio.Equipo_Lab ADD Fecha_Adquisicion DATE NULL;
-- LimiteModel (detecta dinámicamente):
ALTER TABLE laboratorio.Limite_Legal ADD Descripcion NVARCHAR(200) NULL;
```
