<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- =======================
      MODAL DE INICIO
======================= -->
<div class="modal fade" id="inicioModal" tabindex="-1" aria-labelledby="inicioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm mt-4" style="max-width: 360px;">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">

            <div class="modal-header bg-primary text-white border-0 py-2">
                <h6 class="modal-title fw-semibold" id="inicioModalLabel">Bienvenido</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>

            <div class="modal-body text-center p-3">

                <!-- NOMBRE DEL USUARIO -->
                <p class="mb-2 fw-semibold">
                    ¡Hola <?php echo conversionUTF(isset($_SESSION["Trab_Nombres"]) ? $_SESSION["Trab_Nombres"] : ''); ?>! 👋
                </p>

                <!-- LOADER DE 3 PUNTOS -->
                <div id="loaderAnuncioModal" class="d-flex justify-content-center my-3">
                    <div class="spinner-grow text-primary mx-1" role="status" style="width: 0.8rem; height: 0.8rem;"></div>
                    <div class="spinner-grow text-primary mx-1" role="status" style="width: 0.8rem; height: 0.8rem;"></div>
                    <div class="spinner-grow text-primary mx-1" role="status" style="width: 0.8rem; height: 0.8rem;"></div>
                </div>

                <!-- Aquí se renderizará la imagen cargada desde la BD -->
                <div id="contenedorImagenAnuncio"></div>

            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    cargarAnuncioEnModal();
});

function cargarAnuncioEnModal() {

    $.ajax({
        url: "ajax/ajax/anuncio.ajax.php",
        type: "POST",
        data: { accion: "mostrar_anuncio_hoy" },

        success: function (res) {

            const loader = document.getElementById("loaderAnuncioModal");
            const cont = document.getElementById("contenedorImagenAnuncio");
            const modalEl = document.getElementById("inicioModal");

            let r;

            try {
                r = (typeof res === "object") ? res : JSON.parse(res.trim());
            } catch (e) {
                loader.remove();
                return; // No mostrar modal si la respuesta es inválida
            }

            // ➤ SI NO HAY ANUNCIO → NO MOSTRAR EL MODAL
            if (!r || r.status !== "success" || !r.data) {
                if (loader) loader.remove();
                return;
            }

            // ➤ SI HAY ANUNCIO → MOSTRARLO EN EL MODAL
            let a = r.data;
            let imgBase64 = `data:${a.tipo};base64,${a.anuncio}`;

            let link = document.createElement("a");
            link.href = a.enlace ?? "#";
            link.target = "_blank";

            let img = new Image();
            img.src = imgBase64;
            img.alt = a.nombre;
            img.className = "img-fluid rounded shadow-sm";
            img.style = "width:100%; max-width:380px; height:auto; cursor:pointer;";

            img.onload = function () {
                loader.remove();
                cont.innerHTML = "";

                link.appendChild(img);
                cont.appendChild(link);

                // ➤ MOSTRAR MODAL SOLO CUANDO SÍ HAY ANUNCIO
                new bootstrap.Modal(modalEl).show();
            };

            img.onerror = function () {
                loader.remove();
                return; // No mostrar modal si la imagen falla
            };
        },

        error: function () {
            const loader = document.getElementById("loaderAnuncioModal");
            if (loader) loader.remove();
            // No mostrar modal si falla el AJAX
        }
    });
}
</script>

<!-- =======================
      ESTILOS
======================= -->
<style>
    .modal-dialog {
        margin-top: 1.5rem !important;
    }

    #contenedorImagenAnuncio img:hover {
        transform: scale(1.03);
        transition: 0.3s ease;
    }
</style>
