# Guia de Componentes UI, Tabler y Plugins Frontend en CHAVIsystems

Esta guia detalla el uso y configuracion de la capa de presentacion visual (Frontend) en CHAVIsystems.

---

## 1. Sistema de Estilos y Layout Tabler UI

El proyecto utiliza **Tabler UI** (basado en Bootstrap 5). Toda vista debe estar contenida dentro de la estructura estandar:

```html
<!-- Encabezado de Pagina -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title text-primary">
                    <i class="ti ti-[nombre_icono] me-2"></i> Titulo del Modulo
                </h2>
                <div class="text-muted mt-1">Descripcion del modulo o subtitulo</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <!-- Botones de accion: Nuevo, Exportar Excel, Exportar PDF -->
            </div>
        </div>
    </div>
</div>

<!-- Cuerpo de Pagina -->
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <!-- Contenido (Tablas, Cards, Formularios) -->
            </div>
        </div>
    </div>
</div>
```

### Estructura Header/Footer Global

El `public/header.php` incluye automaticamente:
- CSS: `tabler.min.css`, `tabler-icons.min.css`, `dataTables.bootstrap5.min.css`, `select2.min.css`, `select2-bootstrap-5-theme.min.css`, `flatpickr.min.css`
- JS: `jquery-3.7.1.min.js`, `sweetalert2@10.js`, `qrious.min.js`, `select2.min.js`, `jquery.dataTables.min.js`, `dataTables.bootstrap5.min.js`, `dataTables.responsive.min.js`, `responsive.bootstrap5.min.js`, `flatpickr.js`, `es.js`

El `public/footer.php` incluye:
- `tabler.min.js`, `select2.min.js`
- CDN fallbacks de DataTables, Flatpickr, SweetAlert2

**IMPORTANTE:** Si tu modulo necesita JS o CSS adicional, colocalos en `modules/<modulo>/views/js/` y `modules/<modulo>/views/css/` y cargalos desde la vista del modulo.

### Clases CSS personalizadas del proyecto

```css
.bg-pech           /* Fondo verde institucional (#009540), texto blanco */
.navbar-pech-blue  /* Barra de navegacion azul institucional (#004d99) */
```

---

## 2. Badges e Indicadores de Estado

Usa las clases de Tabler para badges con opacidad suave (`bg-*-lt`):

```html
<span class="badge bg-green-lt">Activo</span>
<span class="badge bg-red-lt">Inactivo</span>
<span class="badge bg-yellow-lt">Pendiente</span>
<span class="badge bg-blue-lt">En Proceso</span>
<span class="badge bg-purple-lt">Archivado</span>
<span class="badge bg-orange-lt">Revision</span>
```

---

## 3. Configuracion Estandar de DataTables

Las tablas dinamicas deben ser inicializadas en DataTables consumiendo un archivo AJAX `datatable-*.ajax.php`:

```javascript
const tabla = $('#miTabla').DataTable({
    "ajax": "modules/mi_modulo/ajax/datatable-mi_modulo.ajax.php",
    "deferRender": true,
    "retrieve": true,
    "processing": true,
    "destroy": true,
    "responsive": true,
    "language": {
        "sProcessing":     "Procesando...",
        "sLengthMenu":     "Mostrar _MENU_ registros",
        "sZeroRecords":    "No se encontraron resultados",
        "sEmptyTable":     "Ningun dato disponible en esta tabla",
        "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
        "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0",
        "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
        "sSearch":         "Buscar:",
        "oPaginate": {
            "sFirst":    "Primero",
            "sLast":     "Ultimo",
            "sNext":     "Siguiente",
            "sPrevious": "Anterior"
        }
    }
});
```

### DataTables Responsive

El proyecto incluye la extension Responsive (`dataTables.responsive.min.js`, `responsive.bootstrap5.min.js`). Activala con `"responsive": true`. Esto permite que las tablas se adapten a pantallas pequenas colapsando columnas en un dropdown con icono `+`.

Tambien puedes cargar el idioma desde CDN:
```javascript
"language": {
    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
}
```

### Recargar tabla despues de operaciones AJAX

```javascript
tabla.ajax.reload(null, false);  // Recarga sin perder la pagina actual
```

### Botones de accion condicionales por permisos

En el endpoint `datatable-*.ajax.php`, usa `Auth::permisosModulo()` para mostrar/ocultar botones:

```php
$permisos = Auth::permisosModulo('mi_modulo');
$acciones = '<div class="btn-list flex-nowrap">';

if ($permisos['pueden_editar'] == 1) {
    $acciones .= '<button class="btn btn-icon btn-outline-primary btnEditar" idItem="'.$id.'" title="Editar">
        <i class="ti ti-edit"></i>
    </button>';
}

if ($permisos['pueden_eliminar'] == 1) {
    $acciones .= '<button class="btn btn-icon btn-outline-danger btnEliminar" idItem="'.$id.'" title="Eliminar">
        <i class="ti ti-trash"></i>
    </button>';
}

$acciones .= '</div>';
```

---

## 4. Integracion de Select2 con Carga AJAX

Para desplegar listas desplegables avanzadas (Busqueda de personal, vehiculos o activos):

```html
<select class="form-select select2-ajax" name="id_trabajador" id="id_trabajador" style="width:100%"></select>
```

```javascript
$('#id_trabajador').select2({
    theme: 'bootstrap-5',
    dropdownParent: $('#modalEjemplo'), // Necesario si esta dentro de un Modal
    placeholder: 'Buscar trabajador...',
    ajax: {
        url: 'modules/mi_modulo/ajax/mi_modulo.ajax.php?accion=getTrabajadores',
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return { q: params.term };
        },
        processResults: function (data) {
            return {
                results: $.map(data, function (item) {
                    return {
                        text: item.nombre_completo,
                        id: item.id_trabajador
                    }
                })
            };
        },
        cache: true
    }
});
```

**IMPORTANTE:** `dropdownParent` es obligatorio cuando el Select2 esta dentro de un modal, para que el dropdown se muestre correctamente sobre el modal.

### Select2 con datos locales (sin AJAX)

```javascript
$('#miSelect').select2({
    theme: 'bootstrap-5',
    dropdownParent: $('#modalRegistro'),
    data: [
        { id: '1', text: 'Opcion 1' },
        { id: '2', text: 'Opcion 2' }
    ]
});
```

---

## 5. Selector de Fecha Flatpickr

Para campos de fecha de alta fidelidad. El locale espanol ya esta incluido (`public/js/es.js`):

```javascript
flatpickr(".datepicker", {
    dateFormat: "Y-m-d",
    locale: "es",
    allowInput: true
});

// Con hora
flatpickr(".datetimepicker", {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    locale: "es",
    allowInput: true
});
```

En el HTML:
```html
<input type="text" class="form-control datepicker" name="fecha" placeholder="YYYY-MM-DD">
```

---

## 6. Retroalimentacion con SweetAlert2

Utiliza **SweetAlert2** para confirmar acciones destructivas o reportar respuestas del servidor:

```javascript
// Exito (toast automatico)
Swal.fire({
    icon: 'success',
    title: 'Operacion Exitosa!',
    text: 'El registro ha sido guardado correctamente.',
    timer: 2000,
    showConfirmButton: false
});

// Error
Swal.fire({
    icon: 'error',
    title: 'Ocurrio un error',
    text: respuesta.message || 'No se pudo procesar la solicitud.'
});

// Confirmacion de Eliminacion
Swal.fire({
    title: 'Esta seguro de eliminar este registro?',
    text: "Esta accion no se puede deshacer!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Si, eliminar',
    cancelButtonText: 'Cancelar'
}).then((result) => {
    if (result.isConfirmed) {
        // Enviar peticion AJAX de eliminacion
    }
});
```

---

## 7. Modales (modal-blur)

Estructura estandar de modal con cabecera institucional:

```html
<div class="modal modal-blur fade" id="modalEjemplo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="formEjemplo" autocomplete="off">
                <div class="modal-header bg-pech">
                    <h5 class="modal-title">Titulo del Modal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Campos del formulario -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary ms-auto">
                        <i class="ti ti-device-floppy me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

### Abrir modal para NUEVO registro vs EDICION

```javascript
// Nuevo: limpiar formulario y cambiar titulo
$('#btnNuevo').on('click', function () {
    $('#modalTitulo').text('Formulario de Registro');
    $('#accion').val('crear');
    $('#formRegistro')[0].reset();
});

// Editar: cargar datos existentes y cambiar titulo
$(document).on('click', '.btnEditar', function () {
    const id = $(this).attr('idItem');
    $.ajax({
        url: "modules/ejemplo/ajax/example.ajax.php",
        method: "POST",
        data: { accion: "obtener", id: id },
        dataType: "json",
        success: function (item) {
            $('#modalTitulo').text('Editar Registro');
            $('#accion').val('editar');
            $('#id').val(item.id);
            $('#codigo').val(item.codigo);
            $('#descripcion').val(item.descripcion);
            $('#modalRegistro').modal('show');
        }
    });
});
```

---

## 8. Fragments y Componentes Reutilizables

El proyecto incluye componentes compartidos en `fragments/`:

```
fragments/
  modals/
    contenedor-pdf.php       -- Modal contenedor para visualizar PDFs embebidos
    incidencia-qr.php        -- Modal para registrar incidencias via QR
    popup-inicio.php         -- Popup de bienvenida en el dashboard
    papeletas/               -- Modales especificos del modulo papeletas
  transportes/               -- Fragmentos del modulo transportes
```

Para incluir un fragment en una vista:

```php
<?php include __DIR__ . "/../../../fragments/modals/contenedor-pdf.php"; ?>
```

Si tu modulo requiere componentes reutilizables compartidos entre varias vistas, crealos en `fragments/<tu_modulo>/` para mantener la consistencia con el proyecto.

---

## 9. Generacion de Codigos QR con QRious

El proyecto incluye la libreria QRious (`public/js/qrious.min.js`). Util para generar QR en el frontend (ej: identificacion de activos, pases de vigilantes):

```html
<canvas id="qrCode"></canvas>
```

```javascript
const qr = new QRious({
    element: document.getElementById('qrCode'),
    value: 'https://ejemplo.com/verificar/12345',
    size: 200,
    backgroundAlpha: 0.8,
    foreground: '#004d99'
});
```

Tambien se puede generar desde datos dinamicos:
```javascript
function generarQR(texto) {
    new QRious({
        element: document.getElementById('qrCode'),
        value: texto,
        size: 200
    });
}
```

---

## 10. Formularios con Envio AJAX y Loader

Patron completo con spinner en boton, validacion y feedback:

```javascript
$('#formRegistro').on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    $.ajax({
        url: "modules/mi_modulo/ajax/mi_modulo.ajax.php",
        method: "POST",
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        beforeSend: function () {
            $('#btnGuardar').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');
        },
        success: function (respuesta) {
            $('#btnGuardar').prop('disabled', false)
                .html('<i class="ti ti-device-floppy me-1"></i> Guardar');
            if (respuesta.status === 'success') {
                $('#modalRegistro').modal('hide');
                $('#formRegistro')[0].reset();
                tabla.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Exito!', text: respuesta.message, timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: respuesta.message });
            }
        },
        error: function () {
            $('#btnGuardar').prop('disabled', false)
                .html('<i class="ti ti-device-floppy me-1"></i> Guardar');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrio un error en la solicitud.' });
        }
    });
});
```

---

## 11. Iconos de Tabler Disponibles

El proyecto usa Tabler Icons. Algunos iconos comunes:

| Icono | Clase | Uso tipico |
|-------|-------|------------|
| Plus | `ti ti-plus` | Boton nuevo registro |
| Edit | `ti ti-edit` | Boton editar |
| Trash | `ti ti-trash` | Boton eliminar |
| Save | `ti ti-device-floppy` | Boton guardar |
| Search | `ti ti-search` | Busqueda |
| Download | `ti ti-download` | Descargar/Exportar |
| File | `ti ti-file-text` | PDF |
| Excel | `ti ti-file-spreadsheet` | Excel |
| User | `ti ti-user` | Usuario/Perfil |
| Settings | `ti ti-settings` | Configuracion |
| Home | `ti ti-home` | Inicio/Dashboard |
| Box | `ti ti-box` | Modulo generico |
| Key | `ti ti-key` | Contrasena |
| Logout | `ti ti-logout` | Cerrar sesion |
| Alert | `ti ti-alert-triangle` | Advertencia |
| Check | `ti ti-check` | Confirmar |
| X | `ti ti-x` | Cancelar/Cerrar |
| Refresh | `ti ti-refresh` | Recargar |
| Eye | `ti ti-eye` | Ver/Visualizar |
| Camera | `ti ti-camera` | Capturar foto |
| QR | `ti ti-qrcode` | Codigo QR |
