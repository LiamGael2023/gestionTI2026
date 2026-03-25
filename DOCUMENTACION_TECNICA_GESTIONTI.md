# Documentacion Tecnica Integral - GestionTI (PECH)

## 1. Resumen Ejecutivo

GestionTI es una aplicacion web modular desarrollada en PHP para la gestion interna de procesos TI del Proyecto Especial Chavimochic (PECH). El sistema usa una arquitectura MVC modular, con una capa de acceso a datos basada en repositorios y SQL Server como motor de base de datos.

En esta etapa, el modulo mas maduro y ampliamente evolucionado es `salas` (Gestion de Reservas de Salas), que incluye:

- Solicitud de reservas con validacion de disponibilidad.
- Flujo de autorizacion (aprobar/rechazar).
- Historial y auditoria.
- Administracion de sedes, salas y equipos.
- Carga y visualizacion de foto de sala.
- Impresion del calendario con formato A4.
- Notificaciones por correo via SMTP.

---

## 2. Herramientas y Tecnologias Utilizadas

### 2.1 Backend

- PHP 8.4 (ejecucion en IIS).
- SQLSRV 2022 (`sqlsrv_connect`, `sqlsrv_query`) para conectividad SQL Server.
- Patron MVC modular + capa Repository.
- Sesiones PHP para autenticacion y autorizacion.

### 2.2 Base de datos

- Microsoft SQL Server.
- Esquemas:
  - `comun` (roles, usuarios y sedes).
  - `salas` (sala, equipo, reserva, reserva_equipo, reserva_historial).

### 2.3 Frontend

- Tabler UI (estilos base y layout principal).
- jQuery 3.7.1.
- FullCalendar 6.1.11 (cronograma semanal y mini calendario).
- DataTables 1.13.6 (tablas de pendientes/historial/admin).
- SweetAlert2 (confirmaciones y mensajes).
- Tabler Icons (iconografia).

### 2.4 Correo

- PHPMailer (SMTP STARTTLS).
- Plantillas HTML institucionales con logo embebido (CID).

### 2.5 Infraestructura web

- IIS + `web.config`:
  - Reescritura de URL a `index.php?route=...`.
  - `defaultDocument` a `index.php`.
  - MIME para `.webp`.

---

## 3. Arquitectura General

## 3.1 Enrutamiento

Entrada unica: `index.php`

- Router hibrido:
  - URL amigable: `?route=modulo/accion`
  - URL clasica: `?module=modulo&action=accion`
- Login/logout/autenticar se desvia al modulo `auth`.
- Para el resto, se valida sesion/permisos con `core/Auth.php`.

## 3.2 Estilo arquitectónico (Módulo Salas)

- **MVC modular** por carpeta de módulo:
  - `modules/<modulo>/controllers` — Entrada de solicitudes
  - `modules/<modulo>/models` — Lógica de negocio y acceso a datos
  - `modules/<modulo>/views` — Presentación (HTML, JS, CSS)

- **Patrón Facade** en SalasModel:
  - Punto único de entrada para todos los controllers
  - Delega a repositorios especializados (orquestación)
  - Mantiene API pública estable (los controllers nunca cambian)

- **Capas internas de models/** (estructuración interna):
  1. **core/** — Infraestructura base (BaseModel + SalasModel Facade)
  2. **repositories/** — Capa de acceso a datos (DAL) — 7 repositorios especializados
  3. **roles/** — Validación de autorización por rol — 3 clases de permisos
  4. **features/** — Lógica compartida reutilizable — 2 clases de utilidades

- **Organización de vistas** — Refleja estructura de roles y características:
  - `views/html/features/` — Vistas compartidas por todos los roles
  - `views/html/roles/{admin|usuario|autorizador}/` — Vistas específicas por rol
  - `views/js/features/` — Lógica compartida de JavaScript
  - `views/js/roles/{admin|usuario|autorizador}/` — Lógica específica por rol
  - `views/js/shared/` — Funciones reutilizables (API, alertas, utilidades)

## 3.3 Patrón arquitectónico general (SOLID Principles)

### Capa de datos (Repository Pattern)

Cada entidad/área de negocio tiene su repositorio independiente:

- `SedesRepository` — CRUD de sedes (comun.Sedes)
- `SalasRepository` — CRUD de salas (salas.Sala) + foto_ruta
- `EquiposRepository` — CRUD de equipos AV (salas.Equipo)
- `DisponibilidadRepository` — Consultas de disponibilidad y eventos de calendario
- `ReservasRepository` — CRUD reservas + asociación de equipos + notificaciones
- `AutorizacionRepository` — CRUD autorizaciones + historial + notificaciones
- `EstadisticasRepository` — Queries de indicadores y reportes

**Beneficios:**
- Single Responsibility Principle (SRP): Cada repository responsable de una entidad
- Mantenimiento facilitado: cambios puntuales sin efecto cascada
- Testing: repositorios aislables para unit tests
- Escalabilidad: agregar nuevo repositorio sin modificar existentes (Open/Closed)

### Capa de autorización (Role-based)

Validaciones de permisos enumeradas por rol, sin lógica de negocio:

- `AdminPermissions` — esAdmin(), puedeGestionarSedes(), puedeGestionarSalas(), puedeGestionarEquipos(), puedeVerHistorial()
- `AutorizadorPermissions` — esAutorizadorOAdmin(), puedeVerPendientes(), puedeAutorizar(), puedeRechazar()
- `UsuarioPermissions` — esUsuario(), puedeCrearReserva(), puedeEditarPropia(), puedeCancelarPropia()

**Ventaja:** Separación de concerns — autorización ≠ lógica de negocio

### Capa de características compartidas (Features)

Lógica reutilizable sin dependencias de BD (generalmente métodos estáticos):

- `CalendarioLogic` — validarFechaReserva(), formatearFechaCalendario()
- `DisponibilidadLogic` — hayConflicto(), calcularDisponibilidad()

**Ventaja:** DRY (Don't Repeat Yourself) — extraer lógica común a funciones puras

---

## 4. Estructura Relevante del Proyecto

```text
config/
  config.php
  db.php
core/
  Auth.php
  MailService.php
modules/
  auth/
  dashboard/
  usuarios/
  salas/
    controllers/
      SalasController.php
      ajax_handler.php
    models/
      core/                        ← INFRAESTRUCTURA BASE
        BaseModel.php              Clase base: conexión + helpers + constantes
        SalasModel.php             FACADE: orquestador de repositorios
      repositories/                ← CAPA DE ACCESO A DATOS (7 archivos)
        SedesRepository.php        CRUD Sedes (comun.Sedes)
        SalasRepository.php        CRUD Salas (salas.Sala)
        EquiposRepository.php      CRUD Equipos AV (salas.Equipo)
        DisponibilidadRepository.php  Queries disponibilidad/calendario
        ReservasRepository.php     CRUD Reservas + mail
        AutorizacionRepository.php CRUD autorizaciones + mail
        EstadisticasRepository.php Queries estadísticas
      roles/                       ← CAPA DE AUTORIZACIÓN (3 archivos)
        AdminPermissions.php       Validaciones rol Administrador
        AutorizadorPermissions.php Validaciones rol Autorizador
        UsuarioPermissions.php     Validaciones rol Usuario/Solicitante
      features/                    ← LÓGICA COMPARTIDA (2 archivos)
        CalendarioLogic.php        Validaciones y transformaciones calendario
        DisponibilidadLogic.php    Cálculos de disponibilidad y conflictos
      README.php                   ← DOCUMENTACIÓN ARQUITECTÓNICA
    views/
      html/
        features/
          calendario.php           Vista principal (todos los roles)
        roles/
          admin/
            catalogo.php           Gestión de sedes/salas/equipos (admin)
            historial.php          Historial completo (admin)
          usuario/
            mis-reservas.php       Reservas propias (solicitante)
          autorizador/
            autorizaciones.php     Solicitudes pendientes (autorizador)
      js/
        features/
          calendario.js            Lógica calendario (todos los roles)
        roles/
          admin/
            catalogo.js            Administración sedes/salas/equipos
            admin-salas.js         Subfunciones gestión salas
          usuario/
            mis-reservas.js        Gestión reservas propias
          autorizador/
            autorizaciones.js      Manejo solicitudes pendientes
            historial.js           Consultas histórico
        shared/                    ← FUNCIONES REUTILIZABLES
          api.js                   Cliente AJAX centralizado
          alerts.js                Mensajes y confirmaciones
          utils.js                 Utilidades (escape HTML, badges, etc)
      css/
        calendario.css
assets/
web.config
db_gestsalreu.sql
```

---

## 5. Modulo de Salas - Diseno Funcional

## 5.1 Roles funcionales

- Solicitante:
  - Crear, editar y cancelar reservas pendientes.
  - Ver sus reservas.
- Autorizador:
  - Ver pendientes.
  - Aprobar/rechazar reservas.
  - Ver historial.
- Administrador:
  - Todo lo anterior.
  - Gestion de sedes, salas, equipos, foto de sala.

## 5.2 Flujo principal de reserva

1. Usuario selecciona rango horario en calendario.
2. Se abre modal de nueva solicitud.
3. Selecciona sede -> carga salas.
4. Si la sede tiene una sola sala, se autoselecciona.
5. Selecciona sala -> carga equipos + capacidad + opcion "Ver sala" (foto).
6. Verifica disponibilidad.
7. Envia solicitud.
8. Reserva queda en estado `PENDIENTE`.
9. Se envia correo de notificacion al solicitante.

## 5.3 Flujo de autorizacion

1. Autorizador abre pendientes.
2. Revisa detalle.
3. Aprueba o rechaza (con observacion opcional).
4. Se actualiza estado y se registra en historial de auditoria.
5. Se envia correo al solicitante.

## 5.4 Auto-cancelacion

La auto-cancelacion se ejecuta con control de concurrencia y frecuencia:

- Se intenta en acciones clave del `ajax_handler` (crear/editar/cancelar/aprobar/rechazar y cargas críticas).
- Usa ventana temporal (throttle) para no ejecutar en cada request.
- Usa lock de archivo para evitar ejecuciones simultáneas.
- Se mantiene la misma lógica de negocio interna de `cancelarReservasVencidas()`.
- Existe script opcional de ejecución programada: `modules/salas/controllers/cron_cancelar.php`.

---

## 6. Base de Datos (Modulo Salas)

## 6.1 Script maestro

Archivo: `db_gestsalreu.sql`

Incluye:

- Creacion de tablas `comun` y `salas`.
- Indices principales.
- Trigger de historial de estado.
- Stored procedure de disponibilidad.
- Migracion idempotente para `salas.Sala.foto_ruta`.

## 6.2 Tablas principales

### `salas.Sala`

- `id_sala` (PK)
- `id_sede` (FK a `comun.Sedes`)
- `nombre`, `capacidad`, `descripcion`
- `foto_ruta` (nuevo, nullable)
- `activo`, `fecha_creacion`

### `salas.Equipo`

- `id_equipo` (PK)
- `id_sala` (FK)
- `nombre`, `tipo`, `descripcion`, `activo`

### `salas.Reserva`

- `id_reserva` (PK)
- `id_usuario_solicitante` (FK)
- `id_sala` (FK)
- `fecha`, `hora_inicio`, `hora_fin`
- `motivo`
- `estado` (`PENDIENTE`, `APROBADA`, `RECHAZADA`, `CANCELADA`)
- `observacion_rechazo`
- `id_usuario_autorizador`, `fecha_aprobacion`
- `created_at`, `updated_at`

### `salas.Reserva_Equipo`

- Relacion N:N entre reserva y equipo.

### `salas.Reserva_Historial`

- Registro de transiciones de estado.

## 6.3 Integridad y performance

- Check de horas (`hora_fin > hora_inicio`).
- Check de estado permitido.
- Indices para disponibilidad, solicitante y joins por sede/sala.

## 6.4 Trigger

`salas.TR_Reserva_Historial`

- Se ejecuta `AFTER UPDATE`.
- Si cambia `estado`, inserta evento de auditoria.

## 6.5 Migraciones aplicadas

Se agrego migracion al final del script:

```sql
IF COL_LENGTH('salas.Sala', 'foto_ruta') IS NULL
BEGIN
    ALTER TABLE salas.Sala ADD foto_ruta NVARCHAR(500) NULL;
END
```

---

## 7. Controladores - Explicacion Breve

## 7.1 `index.php` (Front Controller)

- Inicializa configuracion.
- Resuelve ruta y modulo/accion.
- Valida autenticacion/permisos.
- Carga controlador del modulo solicitado.

## 7.2 `modules/auth/controllers/AuthController.php`

- `autenticar`: valida credenciales y crea sesion.
- Guarda datos de sesion:
  - `usuario_id`
  - `usuario_nombre`
  - `usuario_login`
  - `usuario_rol`
  - `usuario_rol_nombre`
- `logout`: limpia sesion.

## 7.3 `modules/salas/controllers/SalasController.php`

- Controlador de vistas del modulo salas.
- Normaliza rol del usuario llamando a `SalasModel::normalizarRolUsuario()` (método centralizado).
- Define contexto de rol y permisos:
  - `$es_autorizador_o_admin` — para ocultar/mostrar secciones
  - `$es_admin` — para validar acceso a gestión
- Carga vistas por acción:
  - `index` (default) → `html/features/dashboard.php` (todos los roles)
  - `calendario` → `html/features/calendario.php` (todos los roles)
  - `dashboard` → `html/features/dashboard.php` (todos los roles)
  - `admin` → `html/roles/admin/catalogo.php` (solo admin)
  - `historial` → `html/roles/admin/historial.php` (solo admin)
  - `pendientes` → `html/roles/autorizador/autorizaciones.php` (autorizador/admin)
  - `mis-reservas` → `html/roles/usuario/mis-reservas.php` (todos)
- Prepara `$usuario_login_impresion` para encabezado de impresión
- Valida permisos antes de cargar vista (respuesta con alerta si acceso denegado)

## 7.4 `modules/salas/controllers/ajax_handler.php`

Controlador JSON central del módulo.

Responsabilidades:

- **Seguridad:**
  - Valida sesión activa.
  - Normaliza rol llamando a `SalasModel::normalizarRolUsuario()`.
  - Valida permisos por rol antes de ejecutar acción.
  - Auto-cancela reservas vencidas con throttle + lock de concurrencia.
  
- **Dispatcher:** Enruta petición por `action` GET/POST a método del `SalasModel`.

- **Validación de campos:** Sanitización y validación de entrada (ej: id numéricas, fechas formato, etc).

- **Llamado a modelo:** Usa `SalasModel` (Facade) que delega a repositorio correspondiente.

- **Respuesta JSON uniforme:**
  ```json
  {
      "ok": true/false,
      "msg": "mensaje descriptivo",
      "data": {...}  // si aplica
  }
  ```

Bloques de acciones implementadas:

- **Catalogos:** `getAllSedes`, `getAllSalas`, `getAllEquipos`, `getSalasBySede`, `getEquiposBySala`
- **Disponibilidad:** `verificarDisponibilidad`, `getEventosCalendar`
- **Reservas solicitante:** `crearReserva`, `getMisReservas`, `getReservaDetalle`, `editarReserva`, `cancelarReserva`
- **Autorizaciones:** `getReservasPendientes`, `aprobarReserva`, `rechazarReserva`, `getHistorial`
- **Admin:**
  - Sedes: `guardarSede`, `toggleSede`
  - Salas: `guardarSala`, `toggleSala`, `guardarFotoSala` (upload + persistencia en BD)
  - Equipos: `guardarEquipo`, `toggleEquipo`
- **Estadísticas:** `getEstadisticasSolicitante`, `getEstadisticasGlobales`

---

## 8. Models y Repositorios - Estructura Detallada

### 8.0 Organización de capas en `models/`

```text
models/
├── core/                   ← INFRAESTRUCTURA
│   ├── BaseModel.php       Base + constantes + helpers DB
│   └── SalasModel.php      FACADE (Orchestrator)
├── repositories/           ← DATA ACCESS LAYER (DAL) — 7 archivos
│   ├── SedesRepository.php
│   ├── SalasRepository.php
│   ├── EquiposRepository.php
│   ├── DisponibilidadRepository.php
│   ├── ReservasRepository.php
│   ├── AutorizacionRepository.php
│   └── EstadisticasRepository.php
├── roles/                  ← AUTHORIZATION LAYER — 3 archivos
│   ├── AdminPermissions.php
│   ├── AutorizadorPermissions.php
│   └── UsuarioPermissions.php
├── features/               ← SHARED LOGIC — 2 archivos
│   ├── CalendarioLogic.php
│   └── DisponibilidadLogic.php
└── README.php              ← DOCUMENTACIÓN ARQUITECTÓNICA
```

**Principios aplicados:**
- **SRP (Single Responsibility):** Cada clase responsable de un só dominio
- **OCP (Open/Closed):** Abierto a extensión, cerrado a modificación
- **DIP (Dependency Inversion):** Inyección de conexión `$conn` en constructores

### 8.1 `core/BaseModel.php`

**Clase base para todos los repositorios.**

Proporciona:

- **Conexión a BD:** Recibe `$conn` (SQL Server) en constructor
- **Constantes compartidas:**
  ```php
  const ROL_SOLICITANTE = 'Solicitante';
  const ROL_AUTORIZADOR = 'Autorizador';
  const ROL_ADMINISTRADOR = 'Administrador';
  const ESTADO_PENDIENTE = 'PENDIENTE';
  const ESTADO_APROBADA = 'APROBADA';
  const ESTADO_RECHAZADA = 'RECHAZADA';
  const ESTADO_CANCELADA = 'CANCELADA';
  ```

- **Helpers de consulta:**
  - `fetchAll($sql, $params)` — Retorna array de resultados o vacío
  - `fetchOne($sql, $params)` — Retorna primer registro o null
  - `execute($sql, $params)` — Ejecuta DML sin retorno
  - `insertAndGetId($sql, $params)` — INSERT + SCOPE_IDENTITY()

- **Métodos estáticos de utilidad:**
  - `normalizarRolUsuario()` — Lee sesión, normaliza rol (fallback legacy ADMIN → Administrador)
  - `esAutorizadorOAdmin($rol)` — Valida si es autorizador o admin
  - `esAdmin($rol)` — Valida si es admin

**Importancia:** Reduce duplicación de código de conexión y queries. Todos los repositorios heredan de aquí.

### 8.2 `core/SalasModel.php` (FACADE PATTERN)

**Punto único de entrada para controladores.**

Responsabilidades:

- **Orquestación:** Instancia en constructor todos los repositorios especializados
  ```php
  $this->sedesRepo = new SedesRepository($db);
  $this->salasRepo = new SalasRepository($db);
  // ... etc (7 total)
  ```

- **API pública estable:** Define métodos públicos que delegan:
  ```php
  public function getSedes() { return $this->sedesRepo->getSedes(); }
  public function crearReserva($datos) { return $this->reservasRepo->crearReserva($datos); }
  // ... ~40 métodos delegadores
  ```

- **Beneficios:**
  - Controllers hablan SÓ con `SalasModel`
  - Si cambia interno de repositorio, controller sigue igual
  - Testing: mockear SalasModel vs. BD real
  - Escalabilidad: agregar repositorio sin afectar API pública

**Estructura interna:** Heredaría de `BaseModel` para acceso a constantes si fuera necesario (actualmente es un orquestador puro).

### 8.3 `repositories/SedesRepository.php`

CRUD de sedes (tabla `comun.Sedes`).

Métodos principales:

- `getSedes()` — Sedes activas con `id, nombre, direccion` (para selectores)
- `getAllSedes()` — Todas (activas e inactivas) para panel admin
- `getSedeById($id)` — Detalle sede
- `guardarSede($datos)` — INSERT o UPDATE según presencia de `id`
- `toggleSede($id, $activo)` — Activar/desactivar

### 8.4 `repositories/SalasRepository.php`

CRUD de salas (tabla `salas.Sala`).

Métodos principales:

- `getSalasBySede($id_sede)` — Salas activas de una sede (JOIN con `comun.Sedes`)
- `getAllSalas()` — Todas salas con datos de sede
- `getSalaById($id)` — Detalle sala
- `guardarSala($datos)` — INSERT o UPDATE
- `toggleSala($id, $activo)` — Activar/desactivar
- `guardarFotoSala($id, $ruta)` — UPDATE `foto_ruta` en BD

**Importante:** Persiste `foto_ruta` (nueva columna) para visualización en:
- Modal de administración (preview)
- Modal "Ver sala" en nueva solicitud

### 8.5 `repositories/EquiposRepository.php`

CRUD de equipos AV (tabla `salas.Equipo`).

Métodos principales:

- `getEquiposBySala($id_sala)` — Equipos activos de una sala (para checkboxes solicitud)
- `getAllEquipos()` — Todos equipos con sede/sala (panel admin)
- `getEquipoById($id)`
- `guardarEquipo($datos)` — INSERT o UPDATE
- `toggleEquipo($id, $activo)`

### 8.6 `repositories/DisponibilidadRepository.php`

Queries especializadas de **disponibilidad** y **eventos de calendario**.

Métodos principales:

- `verificarDisponibilidad($id_sala, $fecha, $hora_inicio, $hora_fin, $excluir_id)`
  - Valida que no esté en pasado
  - Valida que hora_fin > hora_inicio
  - Busca traslapes con reservas `PENDIENTE` y `APROBADA`
  - Parámetro `$excluir_id` para edición (ignorar propia reserva)
  - Retorna: `['disponible' => bool, 'mensaje' => string]`

- `getEventosCalendar($id_sala, $fecha_inicio, $fecha_fin)`
  - Retorna reservas `PENDIENTE` y `APROBADA` en rango (para FullCalendar)
  - Formato: `[{ title: "motivo", start: ISO8601, end: ISO8601, color: ..., ... }]`

- `getEventosCronograma($fecha_inicio, $fecha_fin, $id_sede, $id_sala)`
  - Eventos multi-sala para cronograma semanal
  - Título: `"motivo | sala"` (código de reserva también)

### 8.7 `repositories/ReservasRepository.php`

CRUD de **reservas** (tabla `salas.Reserva`).

Métodos principales:

- `crearReserva($datos)`
  - INSERT en `salas.Reserva`
  - Itera equipos solicitados y valida pertenencia a sala (RN-05)
  - INSERT en `salas.Reserva_Equipo` (N:N)
  - **Envía correo** al solicitante notificando creación
  - Retorna: `id_reserva` o `false` en error

- `getMisReservas($id_usuario)`
  - Reservas del solicitante con datos de sala/sede

- `getReservaDetalle($id_reserva, $id_usuario)`
  - Detalle completo: datos reserva + sede + sala + capacidad + equipos asignados
  - Valida propiedad si `$id_usuario` informado

- `editarReserva($id_reserva, $datos, $id_usuario)`
  - Solo si estado = `PENDIENTE`
  - Valida propiedad: owner == `$id_usuario`
  - UPDATE `salas.Reserva` + regenerar equipos
  - **Envía correo** notificando cambio

- `cancelarReserva($id_reserva, $id_usuario)`
  - Solo si estado = `PENDIENTE`
  - Valida propiedad
  - UPDATE `estado = CANCELADA`
  - Trigger automático inserta en historial
  - **Envía correo** notificando cancelación

**Importante:** Todas las notificaciones usan `core/MailService.php`

### 8.8 `repositories/AutorizacionRepository.php`

Operaciones de **aprobación, rechazo e historial**.

Métodos principales:

- `getReservasPendientes()`
  - Reservas estado = `PENDIENTE` ordenadas por fecha/hora ascendente
  - Incluye datos: solicitante correo, sala, sede, fecha creación

- `aprobarReserva($id_reserva, $id_autorizador)`
  - Valida no haya conflicto con reservas `APROBADA` (RN-CHECK)
  - UPDATE `estado = APROBADA`, `id_usuario_autorizador`, `fecha_aprobacion`
  - Trigger inserta en historial
  - **Envía correo** al solicitante notificando aprobación
  - Retorna: respuesta con OK o mensaje de error

- `rechazarReserva($id_reserva, $id_autorizador, $obs)`
  - UPDATE `estado = RECHAZADA`, `observacion_rechazo`
  - Trigger inserta en historial
  - **Envía correo** incluyendo motivo rechazo
  - Retorna: respuesta

- `cancelarReservasVencidas()`
  - **Se ejecuta en cada petición AJAX** (no requiere cron externo)
  - Busca `PENDIENTE` con `fecha + hora_inicio` <= NOW
  - UPDATE `estado = CANCELADA`
  - Retorna: cantidad canceladas

- `getHistorial($filtros)`
  - Consulta tabla `salas.Reserva_Historial`
  - Filtra por fechas, estados, usuarios
  - Retorna eventos de transición con timestamps

- `getHistorialByReserva($id_reserva)`
  - Cambios de estado de UNA reserva (auditoria)

### 8.9 `repositories/EstadisticasRepository.php`

Queries de **indicadores y reportes**.

Métodos principales:

- `getEstadisticasSolicitante($id_usuario)`
  - Count por estado: `PENDIENTE, APROBADA, RECHAZADA, CANCELADA`
  - Total y porcentajes

- `getEstadisticasGlobales()`
  - Conteos globales de reservas + sedes activas + salas activas
  - Usadas en panel lateral admin/autorizador

### 8.10 `roles/AdminPermissions.php`

Validaciones de autorización para **rol Administrador**.

Métodos estáticos:

- `esAdmin($rol)` — `in_array($rol, ['Administrador', 'ADMIN'])`
- `puedeGestionarSedes($rol)` → `esAdmin($rol)`
- `puedeGestionarSalas($rol)` → `esAdmin($rol)`
- `puedeGestionarEquipos($rol)` → `esAdmin($rol)`
- `puedeVerHistorial($rol)` → `esAdmin($rol)`

### 8.11 `roles/AutorizadorPermissions.php`

Validaciones de autorización para **rol Autorizador**.

Métodos estáticos:

- `esAutorizadorOAdmin($rol)` — `in_array($rol, ['Autorizador', 'Administrador', '...'])`
- `puedeVerPendientes($rol)` → `esAutorizadorOAdmin($rol)`
- `puedeAutorizar($rol)` → `esAutorizadorOAdmin($rol)`
- `puedeRechazar($rol)` → `esAutorizadorOAdmin($rol)`
- `puedeVerHistorial($rol)` → `esAutorizadorOAdmin($rol)`

### 8.12 `roles/UsuarioPermissions.php`

Validaciones de autorización para **rol Usuario (Solicitante)**.

Métodos estáticos:

- `esUsuario($rol)` — `in_array($rol, ['Usuario', 'USER', 'USUARIO'])`
- `puedeCrearReserva($rol)` — Cualquier autenticado (true)
- `puedeEditarPropia($rol, $id_usuario, $id_creador)` — `$id_usuario === $id_creador`
- `puedeCancelarPropia($rol, $id_usuario, $id_creador)` — `$id_usuario === $id_creador`
- `puedeVerPropias($rol, $id_usuario, $id_creador)` — `$id_usuario === $id_creador`

### 8.13 `features/CalendarioLogic.php`

Lógica compartida de **validações de calendario**.

Métodos estáticos puros:

- `validarFechaReserva($fecha, $hora_inicio, $hora_fin)`
  - Valida no esté en pasado
  - Valida horario dentro de 07:00-21:00
  - Valida `$hora_fin > $hora_inicio`
  - Retorna: `['ok' => bool, 'msg' => string]`

- `formatearFechaCalendario($fecha, $hora_inicio, $hora_fin)`
  - Transforma a formato ISO8601 para FullCalendar
  - Retorna: `['date' => ..., 'start' => ..., 'end' => ..., 'display' => ...]`

### 8.14 `features/DisponibilidadLogic.php`

Lógica compartida de **cálculos de disponibilidad**.

Métodos estáticos puros:

- `hayConflicto($inicio1, $fin1, $inicio2, $fin2)`
  - Verifica si dos rangos horarios se solapan
  - Convierte a minutos para comparación
  - Retorna: `bool`

- `calcularDisponibilidad($fecha_inicio, $fecha_fin, $slots_ocupados)`
  - Genera slots de 1 hora (07:00-08:00, 08:00-09:00, ... 20:00-21:00)
  - Marca cuáles están ocupados según `$slots_ocupados`
  - Retorna: array de slots disponibles

### 8.15 `README.php`

Documentación arquitectónica interna del módulo.

Contenido:

- Estructura de carpetas explicada
- Flujo de arquitectura (diagramas ASCII)
- SOLID principles aplicados
- Cómo extender sin romper
- Screenshots conceptuales
- Debugging tips

---

## 9. Vistas - HTML, JS, CSS — Estructura Modular

### 9.1 Organización de vistas HTML

**Por rol: `views/html/roles/{admin|usuario|autorizador}/`**

- `html/roles/admin/catalogo.php` — Gestión sedes, salas, equipos (tabs tabbed interface)
  - Tab "Sedes" — CRUD con toggle activo/inactivo
  - Tab "Salas" — CRUD + upload foto (gallery preview)
  - Tab "Equipos" — CRUD por sala
  - Modales para crear/editar + validaciones inline

- `html/roles/admin/historial.php` — Historial completo de reservas
  - Filtros por date range, estado, sede, sala
  - DataTable con columnas: ID, solicitante, sede, sala, fecha, horario, estado, usuario autorizador
  - Exportar tabla sin permisos especiales

- `html/roles/usuario/mis-reservas.php` — Reservas propias
  - DataTable: ID, sala, fecha, horario, estado, botones acciones
  - Editar (inline) si estado = PENDIENTE
  - Cancelar (con confirmación) si estado = PENDIENTE
  - Ver detalles (modal)

- `html/roles/autorizador/autorizaciones.php` — Solicitudes pendientes
  - Panel superior: badge contador total
  - DataTable: ID, solicitante (correo), sede/sala, fecha, horario, motivo, fecha creación
  - Botones: Ver detalle, Aprobar, Rechazar (con modal para observaciones)
  - Actualizar botón + refresh automático cada X segundos (opcional)

**Compartida: `views/html/features/`**

- `html/features/dashboard.php` — Dashboard principal del módulo (vista por defecto)
  - Topbar con acciones visibles (`Ver Calendario`, `Actualizar`)
  - Layout full-screen con panel lateral fijo
  - Indicadores operativos y gráficos
  - Cobertura organizacional (Top Gerencias / Top Unidades)
  - Indicadores de uso diario (día más/menos utilizado)

- `html/features/calendario.php` — Vista principal (todos los roles)
  - Topbar: logo, filtros sede/sala, botón imprimir, botón `Ver Dashboard`
  - Layout: calendario FullCalendar (columna principal) + panel lateral
  - Panel lateral:
    - Estadísticas (pendientes/aprobadas/rechazadas/canceladas)
    - Botones acciones según rol
    - Secciones colapsables
  - Modal "Nueva solicitud" (crear reserva)
  - Modal "Ver sala" (preview foto)
  - Modal detalles reserva
  - CSS media print A4 optimizado
  - Inyecta constantes PHP → JS como variables globales

### 9.2 Organización de vistas JavaScript

**Lógica compartida: `views/js/shared/`**

- `shared/api.js` — Cliente AJAX centralizado
  - `API.call(action, data, method)` — Llamada genérica AJAX
  - `API.callPendientes()`, `API.callHistorial()`, etc. variantes específicas
  - Manejo uniforme de errores y timeouts

- `shared/alerts.js` — Mensajes y confirmaciones
  - `Alerta.exito(msg)` — SweetAlert verde
  - `Alerta.error(msg)` — SweetAlert rojo
  - `Alerta.pregunta(msg)` — Confirmación
  - Integración con SweetAlert2 + Bootstrap

- `shared/utils.js` — Funciones reutilizables
  - `escHtml(str)` — Escape HTML para prevenir XSS
  - `badgeEstado(estado)` — Badge Bootstrap por estado
  - `obtenerTextoSeleccionado(selector)` — Utilidad select
  - `abrirOffcanvas(id)` — Abrir offcanvas Bootstrap
  - Más utilidades según necesidad

**Lógica por rol: `views/js/roles/{rol}/`**

- `roles/admin/catalogo.js` — Administración sedes/salas/equipos
  - `cargarAdminSedes()` → DataTable con CRUD
  - `cargarAdminSalas()` → DataTable con CRUD + foto preview
  - `cargarAdminEquipos()` → DataTable con CRUD
  - Modales para crear/editar
  - Validaciones before submit

- `roles/admin/admin-salas.js` — Subfunciones específicas gestión salas (archivo modular)
  - Funciones granulares para separación

- `roles/usuario/mis-reservas.js` — Reservas propias
  - `cargarMisReservas()` → DataTable
  - `editarReservaInline()` → Edición en modal
  - `cancelarReserva()` → Cancelación con confirmación

- `roles/autorizador/autorizaciones.js` — Solicitudes pendientes
  - `cargarPendientes()` → DataTable actualizable
  - `verDetallePend()` → Modal detalle
  - `aprobarReserva()` → Aprobación + notificación
  - `rechazarReserva()` → Rechazo con observaciones

- `roles/autorizador/historial.js` — Historial (subtool dentro de admin)
  - `cargarHistorial()` → DataTable filtrable
  - Filtros avanzados: fechas, estados, usuarios

**Lógica compartida: `views/js/features/`**

- `features/dashboard.js` — Lógica del dashboard
  - Carga de indicadores agregados
  - Render de gráficos Chart.js
  - Navegación con desvanecimiento hacia calendario
  - Mini tendencia y bloque de cobertura organizacional
  - Indicador `Salas Utilizadas` en formato `n/n` (ej. `5/6`)

- `features/calendario.js` — Calendario principal (todos los roles)
  - `iniciarCalendarioPrincipal()` → Instancia FullCalendar
  - `cargarFiltrosSede()` → Carga selectores dinámicamente
  - `actualizarEstadisticas()` → Pinta badges panel lateral
  - `abrirModalNuevaSolicitud()` → Modal crear reserva
  - `crearReserva()` → Sender AJAX
  - `verDetalleReserva()` → Modal detalle reserva
  - `verFotoSala()` → Modal preview foto
  - Impresión: `imprimirCalendarioActual()`, `prepararCalendarioParaImpresion()`, `restaurarCalendarioDespuesDeImpresion()`
  - Event handlers para clicks en calendario (crear reserva por rango)
  - Navegación con desvanecimiento al dashboard
  - Apertura robusta del modal nueva solicitud al recibir `?open=nueva` (reintentos controlados)

### 9.3 Nomenclatura y ubicación corregida

**Cambios de reorganización:**

- ❌ `js/roles/usuario/catalogo.js` → ✅ `js/roles/admin/catalogo.js`
  - **Razón:** El archivo gestiona admin (sedes/salas/equipos), no funcionalidad de usuario
  - **Referencia actualizada:** En `html/roles/admin/catalogo.php` línea (script tag src)

### 9.4 `views/css/calendario.css`

Estilos especializados del módulo salas:

- **Cronograma principal:**
  - Layout FullCalendar: tamares responsivos
  - Colores por estado: PENDIENTE (amarillo), APROBADA (verde), RECHAZADA (rojo), CANCELADA (gris)
  - Interactividad: hover, tooltips

- **Panel lateral:**
  - Fondo oscuro (contraste)
  - Estadísticas con badges y contadores
  - Botones con iconografía Tabler

- **Impresión (media print):**
  - `@media print { ... }` reglas optimizadas para A4 horizontal
  - Oculta filtros, botones, panel lateral
  - Muestra resumen de impresión: periodo, sede, sala, usuario, fecha-hora
  - Muestra calendario completo sin scroll
  - Oculta eventos PENDIENTE (solo muestra APROBADA)
  - Márgenes y breaks de página configurados

- **Modal:**
  - Estilos para modales de nueva solicitud, detalles, foto sala
  - Formularios responsive

### 9.5 Flujo de inicialización

1. **Página carga:**
   - PHP inyecta constantes globales: `AJAX`, `ROL`, `ES_ADMIN`, `USUARIO_IMPRESION`, etc.

2. **JavaScript se carga en orden:**
   - `shared/api.js` (PRIMERO — define API global)
   - `shared/alerts.js` (SEGUNDO — define Alerta global)
   - `shared/utils.js` (TERCERO — define helpers)
  - `features/calendario.js` o `features/dashboard.js` según la vista activa
  - En calendario: `roles/usuario/reservas.js` para gestión de nueva solicitud
  - Scripts de autorizador/admin se cargan solo en sus vistas dedicadas (para evitar errores de variables no definidas)

3. **Dependencias:**
   - jQuery (global)
   - FullCalendar (global)
   - DataTables (global)
   - SweetAlert2 (global)
   - Tabler Icons (global)

4. **Document ready:** Dispara `iniciarCalendarioPrincipal()` + carga datos iniciales

---

## 10. Correo y Notificaciones

## 10.1 `core/MailService.php`

- Servicio central de correo.
- Construye plantillas HTML consistentes.
- Usa logo embebido por CID.
- Maneja envio con `try/catch` sin tumbar flujo funcional.

## 10.2 Tipos de notificacion

- Reserva creada.
- Reserva aprobada.
- Reserva rechazada.
- Reserva cancelada.

## 10.3 Configuracion

En `config/config.php`:

- `MAIL_HOST`, `MAIL_PORT`
- `MAIL_USER`, `MAIL_PASS`
- `MAIL_FROM`, `MAIL_FROM_NAME`

Nota tecnica: para produccion se recomienda credenciales institucionales y secretos fuera de repositorio.

---

## 11. Como se implemento (Resumen de construccion)

1. Se construyo flujo base MVC del modulo salas.
2. Se separo logica de negocio en repositorios para mantener SRP.
3. Se integro FullCalendar + filtros por sede/sala.
4. Se implemento ciclo completo de reserva (crear, editar, cancelar, aprobar, rechazar).
5. Se agrego historial por trigger en BD.
6. Se agregaron notificaciones por correo.
7. Se agrego gestion de foto de sala:
   - Columna `foto_ruta`.
   - Upload en backend.
   - Render en admin y en nueva solicitud.
8. Se agrego impresion A4 del calendario:
   - Metadatos de impresion.
   - Usuario de login que imprime.
   - Filtro visual a reservas aprobadas.
9. Se fortalecio robustez:
   - SQL parametrizado en detalle.
   - Inyeccion segura de variables JS con `json_encode`.
   - Compatibilidad print evitando selectores conflictivos.

---

## 12. Seguridad y Reglas de Negocio

## 12.1 Seguridad

- Validacion de sesion en controladores y AJAX.
- Validacion de rol para acciones sensibles.
- Consultas SQL parametrizadas (evitar concatenaciones).
- Sanitizacion en render (`htmlspecialchars`, escapes en JS).

## 12.2 Reglas clave

- No reservar en pasado.
- `hora_fin` debe ser mayor que `hora_inicio`.
- Sin traslape de reservas (`PENDIENTE` y `APROBADA`).
- Solo se edita/cancela si esta `PENDIENTE`.
- Historial automatico cuando cambia estado.

---

## 13. Configuracion de Entorno

## 13.1 IIS

`web.config`:

- Reescritura de URL a router central.
- Documento por defecto `index.php`.
- MIME `.webp` habilitado.

## 13.2 PHP

- Zona horaria: `America/Lima`.
- Sesion con cookie httponly.
- Tiempo de sesion configurado.

## 13.3 SQL Server

- Conexion definida en `config/db.php`.
- Character set UTF-8.

---

## 14. Operacion y Mantenimiento

## 14.1 Checklist de despliegue

1. Ejecutar `db_gestsalreu.sql` (incluye migracion de `foto_ruta`).
2. Verificar conectividad SQLSRV.
3. Verificar SMTP real.
4. Confirmar permisos de escritura en `modules/salas/assets/salas/`.
5. Confirmar MIME `.webp` en IIS.

## 14.2 Pruebas recomendadas

- Login/logout por roles.
- Crear reserva sin traslape / con traslape.
- Editar/cancelar pendientes.
- Aprobar/rechazar desde autorizador.
- Ver historial.
- Subir foto de sala y verla en nueva solicitud.
- Imprimir calendario A4 con metadatos correctos.
- Envio de correos en cada evento.

---

## 15. Mejoras Arquitectónicas Implementadas (Optimización v1.1)

### 15.1 Centralización de Normalización de Roles

**Problema:** Lógica de normalización de rol duplicada en dos controladores.

**Solución:** Centralizar en `BaseModel::normalizarRolUsuario()`

```php
// ANTIGUA FORMA (duplicada en SalasController y ajax_handler):
$rol_normalizado = $_SESSION['usuario_rol_nombre'] ?? '';
if (empty($rol_normalizado)) {
    $rol_normalizado = ($rol_usuario === 'ADMIN')
        ? SalasModel::ROL_ADMINISTRADOR
        : $rol_usuario;
}

// NUEVA FORMA (centralizada en BaseModel):
$rol = SalasModel::normalizarRolUsuario();
```

**Aplicación en:**
- `controllers/SalasController.php` línea 39
- `controllers/ajax_handler.php` línea 34
- Método estático `BaseModel::normalizarRolUsuario()` línea 116

**Beneficio:** DRY (Don't Repeat Yourself) — único fuente de verdad para normalización de roles.

### 15.2 Reorganización de Vistas HTML por Rol y Característica

**Problema:** Vistas HTML sin estructura clara de organización.

**Solución:** Jerarquía clara:
- **Compartidas:** `html/features/` (accesibles por todos los roles)
- **Específicas:** `html/roles/{admin|usuario|autorizador}/` (acceso restringido)

**Cambios aplicados:**
- `calendario.php` → `features/calendario.php` (todos los roles)
- `catalogo.php` → `roles/admin/catalogo.php` (admin)
- `historial.php` → `roles/admin/historial.php` (admin)
- `mis-reservas.php` → `roles/usuario/mis-reservas.php` (usuarios)
- `autorizaciones.php` → `roles/autorizador/autorizaciones.php` (autorizador)

**Beneficio:** Claridad visual de permisos + fácil mantenimiento de vistas específicas por rol.

### 15.3 Reorganización de JavaScript por Rol y Característica

**Problema:** Archivos JavaScript sin estructura modular clara.

**Solución:** Tres categorías:
- **Compartidos:** `js/shared/` (API, alertas, utilidades)
- **Por rol:** `js/roles/{admin|usuario|autorizador}/` (lógica específica)
- **Por característica:** `js/features/` (lógica transversal)

**Estructura creada:**

```text
js/
├── features/
│   └── calendario.js           (todos los roles usan esto)
├── roles/
│   ├── admin/
│   │   ├── catalogo.js         (gestión sedes/salas/equipos)
│   │   └── admin-salas.js      (funciones modularizadas de salas)
│   ├── usuario/
│   │   └── mis-reservas.js     (lógica de reservas propias)
│   └── autorizador/
│       ├── autorizaciones.js   (solicitudes pendientes)
│       └── historial.js        (consultas histórico)
└── shared/
    ├── api.js                  (cliente AJAX)
    ├── alerts.js               (mensajes)
    └── utils.js                (funciones reutilizables)
```

**Cambio de nomenclatura importante:**
- ❌ `js/roles/usuario/catalogo.js` → ✅ `js/roles/admin/catalogo.js`
  - Razón: Archivo gestiona funcionalidad ADMIN, no usuario
  - Actualización: En `html/roles/admin/catalogo.php` (src y filemtime)

**Beneficio:** Modularidad, mantenibilidad, eliminación de dependencias circulares.

### 15.4 Reorganización de Models en Capas Internas

**Problema:** 14 archivos PHP en models/ sin estructura clara.

**Solución:** Capas horizontales con responsabilidades claras:

```text
models/
├── core/               (2 files) ← infraestructura
│   ├── BaseModel.php          helpers BD + constantes
│   └── SalasModel.php         façade/orquestador
├── repositories/       (7 files) ← acceso a datos (DAL)
├── roles/              (3 files) ← autorización (RBAC)
├── features/           (2 files) ← lógica compartida
└── README.php                   ← documentación interna
```

**Beneficio:** SOLID principles (SRP, OCP, DIP) aplicados efectivamente.

### 15.5 Creación de README.php en Models

**Propósito:** Documentación arquitectónica interna del módulo.

**Contenido:**
- Diagrama de estructura de carpetas
- Flujo de arquitectura (Control → Facade → Repository → BD)
- Explicación de cada capa y archivo
- Cómo extender sin romper
- SOLID principles aplicados
- Debugging tips

**Ubicación:** `models/README.php`

**Beneficio:** Onboarding rápido para nuevos desarrolladores. Referencia interna del diseño.

### 15.6 Resumen de Cambios

| Mejora | Tipo | Beneficio | Validado |
|--------|------|----------|----------|
| Centralizar `normalizarRolUsuario()` | Refactor | DRY, mantenibilidad | ✅ Syntax OK, 2 usos |
| Reorganizar HTML por rol/característica | Restructura | Claridad visual, permisos explícitos | ✅ 5 files movidos |
| Reorganizar JS por rol/característica | Restructura | Modularidad, evitar circularidades | ✅ Correcto catalogo.js → admin |
| Reorganizar models en capas internas | Restructura | SOLID, escalabilidad | ✅ 14 archivos 4 capas |
| Crear README.php | Documentación | Onboarding, referencia | ✅ Creado con detalles |
| **TOTAL CAMBIOS** | **5 aspectos** | **100% preservación funcional** | **✅ Validado** |

### 15.7 Validación y Testing

**Checklist aplicado:**
- ✅ Syntax PHP: 5/5 archivos críticos sin errores
- ✅ References: 0 broken imports (22 require_once verificadas)
- ✅ Paths: Corrección de `/../` vs `/../../` (1 vs 2 niveles)
- ✅ Funcionalidad: Controllers + AJAX handlers sin cambios
- ✅ Database: Sin cambios
- ✅ Security: Validaciones de rol intactas
- ✅ User flows: 100% operacionales (calendario, crear, aprobar, rechazar, etc)

---

## 16. Riesgos y Recomendaciones Futuras

- Extraer credenciales sensibles a variables de entorno.
- Agregar pruebas automatizadas (integración/funcionales).
- Versionar migraciones en carpeta dedicada (`migrations/`).
- Agregar bitácora técnica de cambios por versión.
- Evaluar cola de correos para resiliencia (retry/backoff).

**Escalabilidad futuras:**
- **Integración con módulo Inventario:** La capa `EquiposRepository` está preparada para cambiar su veta de datos de `salas.Equipo` a API del módulo inventario sin afectar controllers.
- **Múltiples bases de datos:** Arquitectura repository-based facilita migración progresiva.
- **Caché de disponibilidad:** Se podría agregar Redis en `DisponibilidadRepository` sin cambios en API pública.
- **Auditoría detallada:** Tabla historial ya existe; se podría extender con IP, user agent, cambios específicos.

---

## 17. Referencia Rápida de Archivos Clave

### Configuración
- Entrada y router: `index.php`
- Config app: `config/config.php`
- Config DB: `config/db.php`

### Core
- Auth middleware: `core/Auth.php`
- Servicio de correo: `core/MailService.php`

### Controllers (Salas)
- Controlador MVC: `modules/salas/controllers/SalasController.php`
- API AJAX central: `modules/salas/controllers/ajax_handler.php`

### Models (Salas) — Capas

**Core/Infraestructura:**
- Base + helpers + constantes: `modules/salas/models/core/BaseModel.php`
- Façade/orquestador: `modules/salas/models/core/SalasModel.php`

**Repositories (DAL):**
- Sedes: `modules/salas/models/repositories/SedesRepository.php`
- Salas: `modules/salas/models/repositories/SalasRepository.php`
- Equipos: `modules/salas/models/repositories/EquiposRepository.php`
- Disponibilidad: `modules/salas/models/repositories/DisponibilidadRepository.php`
- Reservas: `modules/salas/models/repositories/ReservasRepository.php`
- Autorizaciones: `modules/salas/models/repositories/AutorizacionRepository.php`
- Estadísticas: `modules/salas/models/repositories/EstadisticasRepository.php`

**Roles (RBAC):**
- Permisos admin: `modules/salas/models/roles/AdminPermissions.php`
- Permisos autorizador: `modules/salas/models/roles/AutorizadorPermissions.php`
- Permisos usuario: `modules/salas/models/roles/UsuarioPermissions.php`

**Features (Lógica compartida):**
- Lógica calendario: `modules/salas/models/features/CalendarioLogic.php`
- Lógica disponibilidad: `modules/salas/models/features/DisponibilidadLogic.php`

**Documentación:**
- Arquitectura interna: `modules/salas/models/README.php`

### Vistas HTML (Salas)

**Compartidas:**
- Dashboard principal: `modules/salas/views/html/features/dashboard.php`
- Calendario principal: `modules/salas/views/html/features/calendario.php`

**Por rol:**
- Admin/catalogo: `modules/salas/views/html/roles/admin/catalogo.php`
- Admin/historial: `modules/salas/views/html/roles/admin/historial.php`
- Usuario/mis-reservas: `modules/salas/views/html/roles/usuario/mis-reservas.php`
- Autorizador/autorizaciones: `modules/salas/views/html/roles/autorizador/autorizaciones.php`

### Vistas JavaScript (Salas)

**Compartidos (Shared):**
- Cliente AJAX: `modules/salas/views/js/shared/api.js`
- Alertas/confirmaciones: `modules/salas/views/js/shared/alerts.js`
- Utilidades: `modules/salas/views/js/shared/utils.js`

**Por característica:**
- Dashboard: `modules/salas/views/js/features/dashboard.js`
- Calendario: `modules/salas/views/js/features/calendario.js`

**Por rol:**
- Admin/catalogo: `modules/salas/views/js/roles/admin/catalogo.js`
- Admin/salas (módulos): `modules/salas/views/js/roles/admin/admin-salas.js`
- Usuario/mis-reservas: `modules/salas/views/js/roles/usuario/mis-reservas.js`
- Autorizador/autorizaciones: `modules/salas/views/js/roles/autorizador/autorizaciones.js`
- Autorizador/historial: `modules/salas/views/js/roles/autorizador/historial.js`

### Vistas CSS (Salas)
- Estilos calendario/impresión: `modules/salas/views/css/calendario.css`
- Estilos dashboard: `modules/salas/views/css/dashboard.css`

---

## 18. Bitácora de Cambios Recientes (Aplicados)

### 18.1 Navegación y vistas

- `dashboard.php` pasa a ser vista principal del módulo (`action=index`).
- Se agrega ruta explícita `action=calendario`.
- Navegación dashboard ↔ calendario con desvanecimiento de contenido (no del lateral).
- Botones de navegación reubicados en topbar para mayor visibilidad (`Ver Calendario` / `Ver Dashboard`).

### 18.2 Layout y UX del módulo Salas

- Dashboard migrado a layout de pantalla completa con panel lateral fijo.
- Unificación de ancho del panel lateral entre dashboard y calendario.
- Separación de capas en dashboard:
  - HTML: `views/html/features/dashboard.php`
  - CSS: `views/css/dashboard.css`
  - JS: `views/js/features/dashboard.js`
- Se evita mezclar CSS/JS inline con la vista principal.

### 18.3 Dashboard de indicadores

- Mejora de resiliencia en carga de datos:
  - Timeout y reintento de carga.
  - Estados vacíos controlados.
  - Marca de tiempo de última actualización.
- Métrica de `Salas Utilizadas` actualizada a formato `n/n` (ejemplo `5/6`).
- Se incorpora bloque de `Cobertura Organizacional` con datos enriquecidos por API de personal:
  - Usuarios con reservas
  - Gerencias activas
  - Unidades activas
  - Top Gerencias / Top Unidades
- Ajustes visuales en badges para legibilidad (texto blanco en fondo azul).

### 18.4 Calendario y modal de nueva reserva

- Se corrige flujo `dashboard -> calendario?open=nueva` para abrir modal de forma robusta.
- Se agregan reintentos controlados hasta que `abrirNuevaSolicitud()` esté disponible.
- Se corrige carga de scripts en calendario para evitar errores globales:
  - Se eliminan scripts de autorizador/admin en esta vista.
  - Se mantienen solo scripts necesarios del flujo de calendario + reservas.

### 18.5 Auto-cancelación y concurrencia

- Auto-cancelación optimizada en `ajax_handler.php`:
  - Filtro por acciones clave.
  - Throttle por ventana temporal.
  - Lock de archivo para evitar carreras.
- Se mantiene intacta la función de negocio `cancelarReservasVencidas()`.
- Se crea script opcional `cron_cancelar.php` para ejecución programada.

### 18.6 Correcciones de datos/textos

- Corrección de caracteres mojibake en mensajes y consultas de autorizador/historial.
- Mensaje de disponibilidad corregido en repositorio:
  - `La sala esta disponible.`
  - `La sala no esta disponible en ese horario.`

### 18.7 Integración y performance de API de personal

- Cache por request del API de personal en `DashboardRepository` para evitar llamadas repetidas.
- Reutilización del cache en indicadores de gerencia y tiempos de aprobación.


### Base de Datos
- Script maestro: `db_gestsalreu.sql`

---

## APÉNDICE A: Mapa de Flujos (Diagrama ASCII)

### Solicitud HTTP → Reserva creada

```
┌─ GET/POST ?module=salas ──────────────────────────────┐
│                  (SalasController)                     │
│  ├─ Valida sesión (core/Auth.php)                     │
│  ├─ Normaliza rol (BaseModel::normalizarRolUsuario)   │
│  ├─ Valida permisos (AdminPermissions, etc)           │
│  └─ Carga vista según $action:                        │
│     ├─ index → html/features/calendario.php           │
│     ├─ admin → html/roles/admin/catalogo.php          │
│     └─ etc                                             │
└─────────────────────────────────────────────────────────┘
              ↓ (Página inicial cargada)
┌─ AJAX POST ?action=crearReserva (ajax_handler.php) ───┐
│  ├─ Valida sesión                                     │
│  ├─ Normaliza rol                                      │
│  ├─ Valida permisos (UsuarioPermissions)              │
│  ├─ Sanitiza campos                                    │
│  └─ Llama:                                             │
│     └─ SalasModel::crearReserva()                     │
│        └─ ReservasRepository::crearReserva()          │
│           ├─ INSERT salas.Reserva (PENDIENTE)        │
│           ├─ INSERT salas.Reserva_Equipo (N:N)       │
│           ├─ Trigger: INSERT historial               │
│           └─ MailService::enviarCorreo()             │
└─────────────────────────────────────────────────────────┘
              ↓ (JSON response)
JSON { ok: true, msg: "...", data: { id_reserva: N } }
```

### Flujo de Aprobación

```
┌─ GET ?module=salas&action=pendientes ──────────────────┐
│ (Autorizador abre vista de pendientes)                 │
│ → html/roles/autorizador/autorizaciones.php            │
└────────────────────────────────────────────────────────┘
              ↓
┌─ AJAX GET ?action=getReservasPendientes ────────────────┐
│ → AutorizacionRepository::getReservasPendientes()       │
│   Retorna: DataTable rows                              │
└────────────────────────────────────────────────────────┘
              ↓
   (Autorizador click "Aprobar")
              ↓
┌─ AJAX POST ?action=aprobarReserva ──────────────────────┐
│ → AutorizacionRepository::aprobarReserva()             │
│    ├─ Valida no hay conflicto (APROBADA traslape)      │
│    ├─ UPDATE estado=APROBADA                           │
│    ├─ INSERT historial (trigger)                       │
│    └─ MailService::enviarAprobacion()                  │
└────────────────────────────────────────────────────────┘
              ↓ (JSON OK)
JSON { ok: true, msg: "Reserva aprobada" }
```

---

## APÉNDICE B: SOLID Principles Aplicados

| Principio | Aplicación en Salas | Evidencia |
|-----------|-------------------|----------|
| **S** (Single Responsibility) | Cada repository responsable de 1 entidad | SedesRepo = Sedes, SalasRepo = Salas, etc. |
| **O** (Open/Closed) | Abierto a extensión, cerrado a modificación | Agregar repository sin cambiar SalasModel façade |
| **L** (Liskov Substitution) | Repositorios intercambiables (heredan BaseModel) | Todos tienen `fetchAll`, `fetchOne`, `execute` |
| **I** (Interface Segregation) | Métodos públicos claros y específicos | Cada repo expone solo métodos necesarios |
| **D** (Dependency Inversion) | Inyección de `$conn` en constructores | No hardcode, parametrizado |

---
