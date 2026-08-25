# Carga de Archivos y Media en CHAVIsystems

Esta guia explica como manejar carga de archivos, imagenes y procesamiento de media en los modulos de CHAVIsystems.

---

## 1. Directorios de Uploads

Cada modulo que requiera almacenar archivos debe tener su propio directorio:

```
modules/<modulo>/uploads/
  fotos/          -- Fotos de trabajadores, activos, etc.
  documentos/     -- PDFs, documentos escaneados
  temporales/     -- Archivos temporales
```

Ejemplo existente: `modules/usuarios/uploads/` para fotos de perfil.

El directorio `storage/` en la raiz del proyecto esta disponible para almacenamiento general (actualmente vacio).

---

## 2. Carga de Imagenes via Formulario

### HTML del formulario

```html
<form id="formConFoto" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="accion" value="crear">

    <div class="mb-3">
        <label class="form-label">Foto</label>
        <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
        <small class="form-hint">Formatos permitidos: JPG, PNG. Maximo 5MB.</small>
    </div>
</form>
```

### Envio AJAX con archivo

```javascript
$('#formConFoto').on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    $.ajax({
        url: "modules/mi_modulo/ajax/mi_modulo.ajax.php",
        method: "POST",
        data: formData,
        cache: false,
        contentType: false,    // Necesario para archivos
        processData: false,    // Necesario para archivos
        dataType: "json",
        beforeSend: function () {
            $('#btnGuardar').prop('disabled', true);
        },
        success: function (respuesta) {
            $('#btnGuardar').prop('disabled', false);
            // Manejar respuesta...
        }
    });
});
```

---

## 3. Procesamiento de Archivos en PHP

### Validacion y guardado

```php
<?php
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['foto'];

    // Validar tipo
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($archivo['type'], $tiposPermitidos)) {
        echo json_encode(['status' => 'error', 'message' => 'Formato de imagen no permitido.']);
        return;
    }

    // Validar tamano (5MB max)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'La imagen no debe superar los 5MB.']);
        return;
    }

    // Generar nombre unico
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreUnico = uniqid('foto_') . '.' . $extension;

    // Ruta de destino
    $rutaDestino = __DIR__ . '/../../../modules/mi_modulo/uploads/fotos/' . $nombreUnico;

    // Crear directorio si no existe
    $dirDestino = dirname($rutaDestino);
    if (!is_dir($dirDestino)) {
        mkdir($dirDestino, 0755, true);
    }

    // Mover archivo
    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        // Guardar ruta relativa en BD
        $rutaRelativa = 'modules/mi_modulo/uploads/fotos/' . $nombreUnico;
        // Insertar en base de datos...
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar la imagen.']);
        return;
    }
}
```

---

## 4. Visualizacion de Imagenes

### Mostrar imagen guardada

```html
<img src="<?php echo BASE_URL . '/' . $registro['foto_ruta']; ?>"
     class="avatar avatar-lg"
     alt="Foto">
```

### Preview antes de subir (JavaScript)

```javascript
document.getElementById('foto').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (ev) {
            document.getElementById('previewFoto').src = ev.target.result;
            document.getElementById('previewFoto').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
```

```html
<img id="previewFoto" src="#" alt="Preview" style="display:none; max-width: 200px;" class="mb-3 rounded">
```

---

## 5. Captura de Foto con Camara (QR / Vigilantes)

El modulo `vigilantes` usa la camara para escanear QR. Patron util para otros modulos:

```html
<div id="camaraContainer" style="display:none;">
    <video id="videoPreview" width="100%" autoplay playsinline></video>
    <canvas id="canvasFoto" style="display:none;"></canvas>
    <button type="button" class="btn btn-primary mt-2" id="btnCapturar">
        <i class="ti ti-camera me-1"></i> Capturar
    </button>
</div>
```

```javascript
let stream;

async function iniciarCamara() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });
        document.getElementById('videoPreview').srcObject = stream;
        document.getElementById('camaraContainer').style.display = 'block';
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo acceder a la camara.' });
    }
}

document.getElementById('btnCapturar').addEventListener('click', function () {
    const video = document.getElementById('videoPreview');
    const canvas = document.getElementById('canvasFoto');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    // Convertir a blob y subir
    canvas.toBlob(function (blob) {
        const formData = new FormData();
        formData.append('foto', blob, 'captura.jpg');
        formData.append('accion', 'crear');

        $.ajax({
            url: "modules/mi_modulo/ajax/mi_modulo.ajax.php",
            method: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (r) {
                if (r.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Foto capturada!' });
                }
            }
        });
    }, 'image/jpeg', 0.8);

    // Detener camara
    stream.getTracks().forEach(track => track.stop());
    document.getElementById('camaraContainer').style.display = 'none';
});
```

---

## 6. Procesamiento de Video/Audio (FFmpeg)

El proyecto incluye `libs/ffmpeg/ffmpeg.exe` para procesamiento de video y audio en Windows.

### Uso basico desde PHP

```php
$ffmpegPath = __DIR__ . '/../../../libs/ffmpeg/ffmpeg.exe';
$inputFile  = __DIR__ . '/../../../modules/mi_modulo/uploads/videos/original.mp4';
$outputFile = __DIR__ . '/../../../modules/mi_modulo/uploads/videos/procesado.mp4';

// Redimensionar video
$cmd = "\"$ffmpegPath\" -i \"$inputFile\" -vf scale=640:480 \"$outputFile\" 2>&1";
exec($cmd, $output, $returnCode);

if ($returnCode === 0) {
    echo "Video procesado exitosamente.";
} else {
    error_log("FFmpeg error: " . implode("\n", $output));
    echo "Error al procesar el video.";
}
```

---

## 7. Buenas Practicas

- **Nombres unicos:** Usar `uniqid()` + timestamp para evitar colisiones.
- **Validacion de tipos:** Siempre verificar MIME type y extension.
- **Limite de tamano:** Configurar en `web.config` (100MB max request) y validar en PHP.
- **Directorios por modulo:** No mezclar archivos de diferentes modulos.
- **Rutas relativas en BD:** Guardar rutas relativas, no absolutas, para portabilidad.
- **Limpieza:** Eliminar archivos temporales despues de procesarlos.
- **Permisos de directorio:** `0755` para directorios, `0644` para archivos.
