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

---

*Generado automáticamente el 2026-05-29 por GitHub Copilot a partir del análisis exhaustivo del código fuente de `d:\SISTEMAS\gestionTI2026`.*
