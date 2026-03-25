<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ARQUITECTURA DEL MÓDULO SALAS — Capa Models
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * DOCUMENTACIÓN (no ejecutable — solo referencia)
 * 
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 * Módulo: Gestión de Reservas de Sala de Reunión
 */

/**
 * ESTRUCTURA DE CARPETAS
 * ═════════════════════
 * 
 * models/
 * ├── core/                          ← INFRAESTRUCTURA BASE
 * │   ├── BaseModel.php              Clase base: conexión BD + helpers + constantes
 * │   └── SalasModel.php             FACADE: orquestador + API pública
 * │
 * ├── repositories/                  ← CAPA DE ACCESO A DATOS (7 archivos)
 * │   ├── SedesRepository.php        CRUD de Sedes (comun.Sedes)
 * │   ├── SalasRepository.php        CRUD de Salas (salas.Sala)
 * │   ├── EquiposRepository.php      CRUD de Equipos AV (salas.Equipo)
 * │   ├── DisponibilidadRepository.php  Queries de disponibilidad/calendario
 * │   ├── ReservasRepository.php     CRUD Reservas + notificaciones mail
 * │   ├── AutorizacionRepository.php CRUD autorizaciones + notificaciones
 * │   └── EstadisticasRepository.php Queries de estadísticas/reportes
 * │
 * ├── roles/                         ← CAPA DE AUTORIZACIÓN (3 archivos)
 * │   ├── AdminPermissions.php       Validaciones para rol Administrador
 * │   ├── AutorizadorPermissions.php Validaciones para rol Autorizador
 * │   └── UsuarioPermissions.php     Validaciones para rol Usuario/Solicitante
 * │
 * ├── features/                      ← LÓGICA COMPARTIDA (2 archivos)
 * │   ├── CalendarioLogic.php        Validaciones y transformaciones de calendario
 * │   └── DisponibilidadLogic.php    Cálculos de disponibilidad/conflictos
 * │
 * └── README.php                     ← Este archivo (documentación)
 */

/**
 * FLUJO DE ARQUITECTURA
 * ═════════════════════
 * 
 *  SalasController (entrada)
 *         ↓
 *    SalasModel (FACADE — punto único de entrada)
 *         ↓
 *  Repository específico (SedesRepository, SalasRepository, etc.)
 *         ↓
 *  BaseModel helpers (fetchAll, fetchOne, execute, insertAndGetId)
 *         ↓
 *  SQL Server vía sqlsrv_
 * 
 * Validación de roles:
 *  BaseModel::normalizarRolUsuario() → sesión normalizada
 *  AdminPermissions, AutorizadorPermissions, UsuarioPermissions → validaciones
 */

/**
 * CAPA 1: CORE/INFRAESTRUCTURA
 * ═════════════════════════════
 * 
 * BaseModel.php
 * ─────────────
 * Clase base para todos los repositorios. Proporciona:
 * 
 *  - Conexión a BD (inyectada en constructor)
 *  - Helpers de consulta:
 *      • fetchAll()       → Array de registros
 *      • fetchOne()       → Primer registro o null
 *      • execute()        → DML sin retorno
 *      • insertAndGetId() → INSERT + SCOPE_IDENTITY
 *  
 *  - Constantes compartidas:
 *      • ROL_SOLICITANTE = 'Solicitante' (Usuario con sesión)
 *      • ROL_AUTORIZADOR = 'Autorizador' (Autoriza reservas)
 *      • ROL_ADMINISTRADOR = 'Administrador' (Gestiona todo)
 *      • ESTADO_PENDIENTE, APROBADA, RECHAZADA, CANCELADA
 *  
 *  - Métodos estáticos:
 *      • normalizarRolUsuario()   → sesión normalizada (fallback legacy)
 *      • esAutorizadorOAdmin()    → boolean
 *      • esAdmin()                → boolean
 * 
 * 
 * SalasModel.php (FACADE PATTERN)
 * ────────────────────────────────
 * PUNTO ÚNICO DE ENTRADA para el controlador.
 * 
 * Responsabilidades:
 *  - Orquestar todas las operaciones del módulo
 *  - Mantener API pública estable (controllers nunca cambian)
 *  - Delegar al repositorio especializado
 *  - Inyectar repositorios en constructor
 * 
 * Métodos públicos (ejemplo):
 *  • getSedes()                      → delega a SedesRepository
 *  • crearReserva()                  → delega a ReservasRepository
 *  • getReservasPendientes()         → delega a AutorizacionRepository
 *  • getEstadisticasGlobales()       → delega a EstadisticasRepository
 * 
 * Ventaja: Si mañana cambia BD o lógica, SOLO se modifica el repositorio.
 * Los controllers siguen igual.
 */

/**
 * CAPA 2: REPOSITORIES (Data Access Layer)
 * ═════════════════════════════════════════
 * 
 * Patrón Repository: separa ACCESO A DATOS de LÓGICA DE NEGOCIO
 * 
 * Cada repository extiende BaseModel y tiene responsabilidad única (SRP):
 * 
 *  SedesRepository
 *  ├─ getSedes()        → sedes activas para selectores
 *  ├─ getAllSedes()     → todas (admin)
 *  ├─ getSedeById()
 *  ├─ guardarSede()
 *  └─ toggleSede()      → activar/desactivar
 * 
 *  SalasRepository       (similar para salas)
 *  EquiposRepository     (similar para equipos AV)
 * 
 *  DisponibilidadRepository
 *  ├─ verificarDisponibilidad()  → ¿Está libre la sala en rango horario?
 *  ├─ getEventosCalendar()       → reservas aprobadas/pendientes
 *  └─ getEventosCronograma()     → eventos multi-sala
 * 
 *  ReservasRepository (ROL: Usuario/Solicitante)
 *  ├─ crearReserva()       → INSERT + equipos asociados
 *  ├─ getMisReservas()     → reservas del usuario
 *  ├─ getReservaDetalle()  → detalle 1 reserva
 *  ├─ editarReserva()      → UPDATE (validar propiedad)
 *  └─ cancelarReserva()    → UPDATE estado = CANCELADA
 * 
 *  AutorizacionRepository (ROL: Autorizador/Admin)
 *  ├─ getReservasPendientes()    → listado pendientes
 *  ├─ aprobarReserva()           → UPDATE estado = APROBADA + mail
 *  ├─ rechazarReserva()          → UPDATE estado = RECHAZADA + mail
 *  ├─ cancelarReservasVencidas() → auto-cancelar si hora ya pasó
 *  ├─ getHistorial()             → filtrado por fechas/estados
 *  └─ getHistorialByReserva()    → cambios en 1 reserva
 * 
 *  EstadisticasRepository
 *  ├─ getEstadisticasSolicitante()  → pendientes/aprobadas/canceladas del usuario
 *  └─ getEstadisticasGlobales()     → conteos totales + sedes + salas activas
 * 
 * REGLA DE ORO: Los repositories SOLO leen/escriben en BD.
 *               NO procesan JSON, NO validan roles, NO envían emails (excepto trigger).
 */

/**
 * CAPA 3: ROLES (Autorización)
 * ════════════════════════════
 * 
 * Cada clase valida permisos para su rol. Son HELPERS ESTÁTICOS, sin estado.
 * 
 *  AdminPermissions
 *  ├─ esAdmin()
 *  ├─ puedeGestionarSedes()
 *  ├─ puedeGestionarSalas()
 *  ├─ puedeGestionarEquipos()
 *  └─ puedeVerHistorial()
 * 
 *  AutorizadorPermissions
 *  ├─ esAutorizadorOAdmin()
 *  ├─ puedeVerPendientes()
 *  ├─ puedeAutorizar()
 *  ├─ puedeRechazar()
 *  └─ puedeVerHistorial()
 * 
 *  UsuarioPermissions
 *  ├─ esUsuario()
 *  ├─ puedeCrearReserva()
 *  ├─ puedeEditarPropia()
 *  ├─ puedeCancelarPropia()
 *  └─ puedeVerPropias()
 * 
 * USO EN CONTROLLERS:
 *  if (!AdminPermissions::puedeGestionarSalas($rol)) {
 *      echo 'Acceso denegado';
 *      return;
 *  }
 */

/**
 * CAPA 4: FEATURES (Lógica Compartida)
 * ════════════════════════════════════
 * 
 * Funciones auxiliares REUTILIZABLES, sin dependencia de BD (generalmente).
 * 
 *  CalendarioLogic
 *  ├─ validarFechaReserva($fecha, $hora_inicio, $hora_fin)
 *  │  → check: no pasada, dentro de 07:00-21:00, fin > inicio
 *  └─ formatearFechaCalendario($fecha, $hora_inicio, $hora_fin)
 *     → devuelve array con start, end, display para FullCalendar
 * 
 *  DisponibilidadLogic
 *  ├─ hayConflicto($inicio1, $fin1, $inicio2, $fin2)
 *  │  → verifica si 2 rangos horarios se solapan
 *  └─ calcularDisponibilidad($fecha_inicio, $fecha_fin, $slots_ocupados)
 *     → genera slots de 1h (07:00-08:00, 08:00-09:00, etc)
 *     → marca cuáles están ocupados
 */

/**
 * PRINCIPLES APLICADOS
 * ════════════════════
 * 
 * SOLID:
 *  S (Single Responsibility) — Cada clase/file hace UNA cosa
 *  O (Open/Closed)          — Abierto a extensión, cerrado a modificación
 *  L (Liskov)               — Los repositories son intercambiables
 *  I (Interface Segregation)— Métodos públicos claros y específicos
 *  D (Dependency Inversion) — Inyección de $conn en constructor
 * 
 * DRY (Don't Repeat Yourself)
 *  → Lógica de normalización de rol centralizada en BaseModel::normalizarRolUsuario()
 *  → Helpers de BD en BaseModel
 *  → Funciones compartidas en features/
 * 
 * PATTERN: FACADE (SalasModel orquesta)
 *  → Controllers hablan SOLO con SalasModel
 *  → SalasModel delega a repositorios
 *  → Cambios internos = sin impacto en controllers
 */

/**
 * CÓMO EXTENDER
 * ═════════════
 * 
 * AGREGAR UN NUEVO REPOSITORIO:
 * ──────────────────────────────
 * 
 *  1. Crear: models/repositories/NuevoRepository.php
 *  2. Extender BaseModel
 *  3. Agregar métodos públicos
 *  4. En SalasModel::__construct(), instanciar: $this->nuevoRepo = new NuevoRepository($db);
 *  5. En SalasModel, agregar delegadores públicos
 *  6. Controller continúa igual ✓
 * 
 * 
 * AGREGAR UN NUEVO MÉTODO DE VALIDACIÓN:
 * ───────────────────────────────────────
 * 
 *  1. En models/roles/MiRolPermissions.php:
 *     public static function miMetodo($rol) { ... }
 *  2. En SalasController/ajax_handler:
 *     if (!MiRolPermissions::miMetodo($rol)) { ... }
 * 
 * 
 * AGREGAR LÓGICA COMPARTIDA:
 * ──────────────────────────
 * 
 *  1. Crear: models/features/MiLogic.php
 *  2. Métodos static o funciones puras
 *  3. Importar con require_once
 *  4. Usar desde cualquier lado (controller, repository, etc)
 */

/**
 * DEBUGGING & TROUBLESHOOTING
 * ═══════════════════════════
 * 
 * "Query devuelve null": 
 *  → Revisar BaseModel::fetchOne() — retorna null si sin resultados
 *  → Validar parámetros SQL (? placeholders coinciden con array)
 * 
 * "Método no encontrado en repository":
 *  → ¿Existe en repository correcto?
 *  → ¿SalasModel delegador público existe?
 * 
 * "Permiso denegado inesperadamente":
 *  → Verificar rol con: $rol = BaseModel::normalizarRolUsuario();
 *  → Revisar clase de permisos correspondiente
 * 
 * "Ruta include rota":
 *  → repositories/: require_once __DIR__ . '/../core/BaseModel.php';
 *  → features/, roles/: require_once __DIR__ . '/...';
 */

/**
 * VERSIÓN DE ARQUITECTURA
 * ═══════════════════════
 * 
 * v1.0 — Arquitectura base con Facade + Repositories + Roles
 *        (Proyecto Especial Chavimochic — GestionTI)
 * 
 * CARACTERÍSTICAS:
 *  ✓ Centralización de acceso a datos (repositories)
 *  ✓ Validación de roles modular
 *  ✓ Lógica compartida extraída
 *  ✓ API pública estable (controllers invariantes)
 *  ✓ SOLID principles
 *  ✓ Fácil de extender sin breaking changes
 */

?>