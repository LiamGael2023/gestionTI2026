---
name: chavisystems-module-builder
description: Guía y conjunto de reglas para desarrollar nuevos módulos en CHAVIsystems siguiendo la arquitectura MVC, PHP, SQL Server, Tabler UI, DataTables, Select2 y TCPDF.
---

# CHAVIsystems Module Builder Skill

Esta skill proporciona las instrucciones, estándares, componentes UI y plantillas para construir nuevos módulos en la plataforma **CHAVIsystems** de forma coherente y sin errores.

## Principios Clave de Desarrollo

### 1. Arquitectura MVC Modular

Todo nuevo módulo debe crearse dentro de `modules/<nombre_modulo>/` con la siguiente estructura:

```
modules/<nombre_modulo>/
  controllers/        -- Lógica de negocio (procesa peticiones, valida datos)
  models/             -- Acceso a datos (consultas SQL, stored procedures)
  views/              -- Vistas HTML/JS/CSS del módulo
    js/               -- JavaScript específico del módulo (opcional)
    css/              -- CSS específico del módulo (opcional)
  ajax/               -- Endpoints AJAX (CRUD, DataTables, Select2, etc.)
  reportes/           -- Exportaciones PDF y Excel
    tcpdf/            -- Reportes con TCPDF
    dompdf/           -- Reportes con dompdf
  sql/                -- Scripts SQL de migración/esquema para el módulo
  api/                -- Endpoints REST API (opcional, ej: usuarios/api/)
  uploads/            -- Archivos subidos por el módulo (opcional)
```

### 2. Conexion y Seguridad BD

- Utilizar el driver nativo `sqlsrv` de SQL Server mediante `Conexion::conectar()` definida en `config/db.php`.
- Consultas preparadas con parametros sanitizados (`?`) y Stored Procedures (`EXEC usp_*`).
- Liberar statements con `sqlsrv_free_stmt()` despues de cada consulta.
- Usar `error_log()` para registrar errores de base de datos.

### 3. Mapeo de Permisos

- Validar permisos con `Auth::permisosModulo('<modulo>')` que retorna un array asociativo con 5 niveles:
  - `pueden_ver` — acceso al modulo
  - `pueden_crear` — boton de nuevo registro
  - `pueden_editar` — boton de edicion en tabla
  - `pueden_eliminar` — boton de eliminacion en tabla
  - `pueden_exportar` — boton de exportacion a PDF/Excel
- Los permisos se almacenan en las tablas `comun.Modulos` y `comun.Permisos`.
- Usar estos flags para condicionar la visibilidad de botones en vistas y DataTables.

### 4. Diseno Visual y UX (Tabler UI)

- Interfaz construida con **Tabler UI** (Bootstrap 5), iconos de Tabler (`ti ti-*`), formularios en modales (`modal-blur`), tablas dinamicas con **DataTables** (incluyendo extension Responsive), combos dinamicos **Select2**, pickers **Flatpickr**, alertas **SweetAlert2** y generacion de codigos QR con **QRious**.
- Estructura de pagina estandar: `page-header` > `page-body` > `container-xl` > `card` > `card-body`.
- Modales con clase `modal-blur` y cabecera `bg-pech`.
- Componentes reutilizables en `fragments/` (modales de PDF, QR, formularios compartidos).

### 5. Formato JSON Estandar en Respuestas AJAX

- Salidas JSON limpias precedidas por `if (ob_get_length()) ob_clean();` y cabecera `Content-Type: application/json; charset=utf-8`.
- Respuestas con estructura `{"status": "success"|"error", "message": "...", "data": ...}`.
- Endpoints DataTables retornan `{"data": [[...], [...]]}`.

### 6. Registro de Nuevo Modulo en el Sistema

Para que un nuevo modulo sea accesible, se requieren dos pasos:

**a) Registro en Base de Datos:**
```sql
-- Insertar el modulo en la tabla comun.Modulos
INSERT INTO comun.Modulos (nombre, etiqueta, icono, orden)
VALUES ('mi_modulo', 'Mi Modulo', 'box', 10);

-- Asignar permisos a usuarios (ej: admin)
INSERT INTO comun.Permisos (id_usuario, id_modulo, pueden_ver, pueden_crear, pueden_editar, pueden_eliminar, pueden_exportar)
SELECT u.id_usuario, m.id_modulo, 1, 1, 1, 1, 1
FROM comun.Usuarios u, comun.Modulos m
WHERE u.rol = 'ADMIN' AND m.nombre = 'mi_modulo';
```

**b) Registro en el Router (`index.php`):**
- Los modulos con controlador estandar (`modules/<nombre>/controllers/<Nombre>Controller.php`) se cargan automaticamente mediante la logica dinamica del router (linea ~150 de `index.php`).
- No se requiere modificacion adicional en `index.php` si se sigue la convencion de nombres.
- Para modulos con logica especial (acciones adicionales, vistas condicionales), agregar el caso en el `switch` correspondiente.

### 7. Librerias Disponibles en el Proyecto

| Libreria | Ubicacion | Proposito |
|----------|-----------|-----------|
| **TCPDF** | `libs/tcpdf/` | Generacion de PDF (tablas, reportes) |
| **dompdf** | `libs/dompdf/` | Generacion de PDF desde HTML (alternativa a TCPDF) |
| **PhpSpreadsheet** | `libs/vendor/phpoffice/phpspreadsheet/` | Lectura/escritura de Excel (XLSX, XLS, CSV) |
| **OpenSpout** | `libs/OpenSpout/` | Exportacion streaming de Excel/CSV/ODS (bajo consumo de memoria) |
| **PHPMailer** | `utils/PHPMailer/` | Envio de correos via SMTP (Gmail) |
| **QRious** | `public/js/qrious.min.js` | Generacion de codigos QR en frontend |
| **FFmpeg** | `libs/ffmpeg/ffmpeg.exe` | Procesamiento de video/audio (Windows) |

---

## Documentos de Referencia Adicionales

Para profundizar en areas especificas durante el desarrollo, consulta los siguientes archivos de referencia:

- **Componentes UI y Frontend:** [`references/ui_components_and_tabler.md`](file:///.agents/skills/chavisystems-module-builder/references/ui_components_and_tabler.md)
- **Generacion de Reportes PDF y Excel:** [`references/pdf_excel_reports.md`](file:///.agents/skills/chavisystems-module-builder/references/pdf_excel_reports.md)
- **Autenticacion y Permisos:** [`references/authentication_and_permissions.md`](file:///.agents/skills/chavisystems-module-builder/references/authentication_and_permissions.md)
- **Ruteo y Navegacion:** [`references/routing_and_navigation.md`](file:///.agents/skills/chavisystems-module-builder/references/routing_and_navigation.md)
- **Base de Datos y Migraciones:** [`references/database_and_migrations.md`](file:///.agents/skills/chavisystems-module-builder/references/database_and_migrations.md)
- **Envio de Correos (PHPMailer):** [`references/email_and_notifications.md`](file:///.agents/skills/chavisystems-module-builder/references/email_and_notifications.md)
- **Carga de Archivos y Media:** [`references/file_uploads_and_media.md`](file:///.agents/skills/chavisystems-module-builder/references/file_uploads_and_media.md)

---

## Plantillas y Ejemplos Completos (Boilerplates)

En la carpeta `examples/full_crud_module/` encontraras un modulo funcional completo de referencia:

- **Controlador:** [`examples/full_crud_module/ExampleController.php`](file:///.agents/skills/chavisystems-module-builder/examples/full_crud_module/ExampleController.php) — CRUD completo (crear, listar, obtener, editar, eliminar)
- **Modelo:** [`examples/full_crud_module/ExampleModel.php`](file:///.agents/skills/chavisystems-module-builder/examples/full_crud_module/ExampleModel.php) — Stored procedures para todas las operaciones
- **Endpoint AJAX CRUD:** [`examples/full_crud_module/example.ajax.php`](file:///.agents/skills/chavisystems-module-builder/examples/full_crud_module/example.ajax.php) — Router de acciones AJAX (crear, editar, eliminar, obtener)
- **Endpoint AJAX DataTables:** [`examples/full_crud_module/datatable-example.ajax.php`](file:///.agents/skills/chavisystems-module-builder/examples/full_crud_module/datatable-example.ajax.php) — Proveedor de datos para DataTables con badges de estado y botones condicionales por permisos
- **Vista HTML/JS:** [`examples/full_crud_module/index.php`](file:///.agents/skills/chavisystems-module-builder/examples/full_crud_module/index.php) — Pagina completa con DataTables, modal de creacion, modal de edicion, confirmacion de eliminacion y boton de exportacion
