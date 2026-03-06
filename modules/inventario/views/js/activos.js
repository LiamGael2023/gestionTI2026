document.addEventListener("DOMContentLoaded", function () {

  const iconos = {
    equipos: ["ti-device-desktop", "ti-device-laptop", "ti-device-tablet", "ti-server"],
    componentes: ["ti-cpu", "ti-device-hdd", "ti-device-ssd", "ti-device-usb", "ti-device-sd-card"],
    perifericos: ["ti-mouse", "ti-keyboard", "ti-headphones", "ti-microphone", "ti-speaker"],
    pantallas: ["ti-device-desktop", "ti-presentation", "ti-projector"],
    impresion: ["ti-printer", "ti-printer-3d", "ti-copy"],
    red: ["ti-router", "ti-network", "ti-wifi", "ti-antenna"]
  };

  /* ============================
     FUNCION GENERAR ICONOS
  ============================ */
  function generarIconos(tipo, contenedor, preview, inputHidden, iconoActual = null) {
    contenedor.innerHTML = "";
    if (!iconos[tipo]) return;

    iconos[tipo].forEach(icono => {
      // Si el icono es el que ya estaba seleccionado, le ponemos estilo activo
      let seleccionado = (icono === iconoActual) ? "border-primary bg-primary-lt" : "";

      let html = `
      <div class="col-4 col-sm-3">
        <div class="card card-sm text-center icono-item ${seleccionado}" 
             data-icon="${icono}" 
             style="cursor:pointer">
          <div class="card-body p-2">
            <i class="ti ${icono} fs-1 text-primary"></i>
            <div class="text-muted" style="font-size: 0.65rem;">
              ${icono.replace("ti-","")}
            </div>
          </div>
        </div>
      </div>`;
      contenedor.insertAdjacentHTML("beforeend", html);
    });

    // Si hay un icono actual (caso editar), actualizar la vista previa
    if (iconoActual) {
      preview.innerHTML = `<i class="ti ${iconoActual}"></i>`;
      inputHidden.value = iconoActual;
    }
  }

  /* ============================
     EVENTOS: AGREGAR ACTIVO
  ============================ */
  const tipoIcono = document.getElementById("tipoIcono");
  const listaIconos = document.getElementById("listaIconos");
  const previewIcon = document.getElementById("previewIcon"); // El avatar del preview
  const iconoActivo = document.getElementById("iconoActivo"); // El input hidden

  if (tipoIcono) {
    tipoIcono.addEventListener("change", function () {
      generarIconos(this.value, listaIconos, previewIcon, iconoActivo);
    });
  }

  /* ============================
     EVENTOS: EDITAR ACTIVO
  ============================ */
  const editarTipoIcono = document.getElementById("editarTipoIcono");
  const editarListaIconos = document.getElementById("editarListaIconos");
  const editarPreviewIcon = document.getElementById("editarPreviewIcon");
  const editarIconoActivo = document.getElementById("editarIconoActivo");

  if (editarTipoIcono) {
    editarTipoIcono.addEventListener("change", function () {
      generarIconos(
        this.value, 
        editarListaIconos, 
        editarPreviewIcon, 
        editarIconoActivo, 
        editarIconoActivo.value
      );
    });
  }

  /* ============================
     SELECCIÓN DE ICONO (CLICK)
  ============================ */
  document.addEventListener("click", function (e) {
    let item = e.target.closest(".icono-item");
    if (!item) return;

    // Quitar selección a los demás en el mismo contenedor
    let contenedor = item.closest(".row");
    contenedor.querySelectorAll(".icono-item").forEach(el => {
      el.classList.remove("border-primary", "bg-primary-lt");
    });

    // Marcar el seleccionado
    item.classList.add("border-primary", "bg-primary-lt");
    let icono = item.getAttribute("data-icon");

    // Identificar en qué modal estamos para actualizar el preview correcto
    let modal = item.closest(".modal");
    
    if (modal && modal.id === "modalAgregarActivo") {
      previewIcon.innerHTML = `<i class="ti ${icono}"></i>`;
      iconoActivo.value = icono;
    }

    if (modal && modal.id === "modalEditarActivo") {
      editarPreviewIcon.innerHTML = `<i class="ti ${icono}"></i>`;
      editarIconoActivo.value = icono;
    }
  });

  // ENVÍO AJAX AL SERVIDOR
    $("#formNuevoActivo").on("submit", function(e) {
        e.preventDefault();
        
        var datos = new FormData(this);

        $.ajax({
            url: "modules/inventario/ajax/activos.ajax.php", // Ruta corregida
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            success: function(respuesta) {
                var res = JSON.parse(respuesta);
                if (res == "ok") {
                    $("#modalAgregarActivo").modal("hide");
                    swal({
                        icon: "success",
                        title: "¡Éxito!",
                        text: "Activo guardado correctamente"
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    swal("Error", "No se pudo guardar en la base de datos", "error");
                }
            }
        });
    });

  /* ============================
     CARGAR DATOS EN MODAL EDITAR
  ============================ */
  window.cargarEditarActivo = function (data) {
    // data debe ser un objeto con los nombres de las columnas de tu tabla
    if(document.getElementById("editarIdActivo")) document.getElementById("editarIdActivo").value = data.idActivos;
    if(document.getElementById("editarDescripcion")) document.getElementById("editarDescripcion").value = data.descripcion;
    if(document.getElementById("editarIconoActivo")) document.getElementById("editarIconoActivo").value = data.icono;

    // Auditoría (Labels/Spans)
    if(document.getElementById("editarUsuarioCreacion")) document.getElementById("editarUsuarioCreacion").textContent = data.usuarioCreacion;
    if(document.getElementById("editarFechaCreacion")) document.getElementById("editarFechaCreacion").textContent = data.fechaCreacion;

    // Checkbox Compuesto
    if(document.getElementById("editarCompuesto")) {
        document.getElementById("editarCompuesto").checked = (data.compuesto == 1);
    }

    // Actualizar Preview inicial del modal editar
    if(document.getElementById("editarPreviewIcon")) {
        document.getElementById("editarPreviewIcon").innerHTML = `<i class="ti ${data.icono}"></i>`;
    }
    
    // Limpiar lista de iconos previa
    if(editarListaIconos) editarListaIconos.innerHTML = "";
    if(editarTipoIcono) editarTipoIcono.value = "";
  };

});