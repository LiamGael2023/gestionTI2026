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
`punto_venta`, `buscar_producto`, `buscar_clientes`, `guardar_venta`, `crear_cliente_rapido`

### 16.2. Controller: `PuntoVentaController.php` (86 líneas)

| # | Action | HTTP | AJAX | Propósito |
|---|--------|------|------|-----------|
| 1 | `index` (default) | GET | No | Obtiene listas iniciales de clientes, productos y ventas de hoy y renderiza la vista. |
| 2 | `buscar_producto` | GET | Sí | Busca y retorna la información de un producto por su ID en formato JSON. |
| 3 | `buscar_clientes` | GET | Sí | Filtra y retorna la lista de clientes coincidentes con el parámetro `q` en JSON. |
| 4 | `guardar_venta` | POST | Sí | Registra la cabecera y el detalle de la venta. Descuenta stock por FIFO. |
| 5 | `crear_cliente_rapido` | POST | Sí | Inserta un cliente con ID temporal incremental de forma ágil desde el formulario de venta. |

### 16.3. Model: `PuntoVentaModel.php` (338 líneas)

Todas las consultas SQL operan sobre el esquema `BD_PRODUCCIONDESARROLLO.dbo.*`.

#### Métodos:
| Método | Propósito |
|--------|-----------|
| `listarClientes()` | Todos los clientes ordenados por nombre |
| `buscarClientes($query)` | Búsqueda por nombre con `LIKE`, incluye tipo (Planilla/Externo) |
| `listarProductosVenta()` | Productos con `maneja_stock=1` + precio calculado + stock total |
| `buscarProducto($id)` | Producto individual con precio calculado |
| `calcularPrecio($producto)` | Helper privado: UIT × porcentaje o precio variable |
| `guardarVenta($data)` | **Transaccional FIFO**: inserta cabecera `PENDIENTE` → descuenta lotes → kardex → detalle |
| `listarVentasHoy()` | Ventas del día (todas, no solo PROCESADO) |
| `crearClienteRapido($nombre)` | Genera `dni_ruc = TEMPyyMMddXXXX`, inserta con `tipo_cliente=0` |

#### Flujo de `guardarVenta()`:
1. Obtiene `id_centro` del primer producto vendido
2. Inserta cabecera en `transaccion` con `estado='PENDIENTE'`, `tipo_op='VENTA'`, `OUTPUT INSERTED.id_transaccion`
3. Itera ítems vendidos. Para cada producto, busca lotes activos (`stock_actual > 0`) ordenados por `fecha_creacion ASC, id_lote ASC` (FIFO)
4. Itera lotes descontando: resta stock, registra kardex (`tipo_movimiento='VENTA'`), inserta `transaccion_detalle`
5. Si stock insuficiente, lanza Exception

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

*Última actualización: 2026-06-19*
