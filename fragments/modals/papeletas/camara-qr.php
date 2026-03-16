<style>
  /* Modal full screen */
  #modalScanner {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
  }

  /* Contenido */
  #modalScanner .modal-content {
    position: relative;
    width: 90vw;
    max-width: 400px;
    aspect-ratio: 1/1;
    background: #000;
    border-radius: 10px;
    overflow: hidden;
  }

  /* cerrar */
  #btnCerrarScanner {
    position: absolute;
    top: 5px;
    right: 5px;
    z-index: 10;
    border: none;
    background: rgba(255, 255, 255, 0.3);
    color: white;
    font-size: 24px;
    cursor: pointer;
    border-radius: 50%;
    width: 35px;
    height: 35px;
  }

  /* cambiar camara */
  #btnCambiarCamara {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
  }

  /* lector */
  #qr-reader {
    width: 100%;
    height: 100%;
  }

  #qr-reader video {
    object-fit: cover !important;
    width: 100% !important;
    height: 100% !important;
  }
</style>





<!-- MODAL -->
<div id="modalScanner">

  <div class="modal-content">

    <button id="btnCerrarScanner">&times;</button>

    <button id="btnCambiarCamara" class="btn btn-light btn-sm">
      Cambiar cámara
    </button>

    <div id="qr-reader"></div>

  </div>

</div>


<!-- LIBRERIA -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>


<script>
  document.addEventListener("DOMContentLoaded", function() {

    let html5QrCode;
    let cameras = [];
    let currentCamera = 0;

    const modalScanner = document.getElementById("modalScanner");
    const btnOpenScanner = document.getElementById("btnOpenScanner");
    const btnCerrarScanner = document.getElementById("btnCerrarScanner");
    const btnCambiarCamara = document.getElementById("btnCambiarCamara");
    const modalId = document.getElementById("modalId");
    const idText = document.getElementById("idPapeletaText");




    /* ABRIR SCANNER */
    btnOpenScanner.addEventListener("click", function() {

      modalScanner.style.display = "flex";

      html5QrCode = new Html5Qrcode("qr-reader");

      const qrReader = document.getElementById("qr-reader");
      const qrBoxSize = Math.floor(qrReader.offsetWidth * 0.8);


      Html5Qrcode.getCameras().then(devices => {

        cameras = devices;

        if (cameras.length) {

          /* intenta usar camara trasera */
          currentCamera = cameras.length > 1 ? 1 : 0;

          iniciarCamara(cameras[currentCamera].id, qrBoxSize);

        }

      }).catch(err => {
        console.error(err);
        alert("No se pudo acceder a la cámara");
      });

    });



    /* INICIAR CAMARA */
    function iniciarCamara(cameraId, qrBoxSize) {

      html5QrCode.start(
        cameraId, {
          fps: 10,
          qrbox: qrBoxSize,
          aspectRatio: 1.0,
          disableFlip: false
        },

        qrCodeMessage => {

          console.log("QR detectado:", qrCodeMessage);

          /* detener scanner */
          html5QrCode.stop().then(() => {

            modalScanner.style.display = "none";

            /* aqui usas el valor del QR */
            // alert("QR leído: " + qrCodeMessage);
            modalId.style.display = "flex";
            idText.textContent = qrCodeMessage;



          });

        },

        errorMessage => {
          /* ignorar errores menores */
        });

    }



    /* CAMBIAR CAMARA */
    btnCambiarCamara.addEventListener("click", function() {

      if (cameras.length > 1) {

        html5QrCode.stop().then(() => {

          currentCamera++;

          if (currentCamera >= cameras.length) {
            currentCamera = 0;
          }

          const qrReader = document.getElementById("qr-reader");
          const qrBoxSize = Math.floor(qrReader.offsetWidth * 0.8);

          iniciarCamara(cameras[currentCamera].id, qrBoxSize);

        });

      }

    });



    /* CERRAR SCANNER */
    btnCerrarScanner.addEventListener("click", function() {

      if (html5QrCode) {

        html5QrCode.stop().finally(() => {
          modalScanner.style.display = "none";
        });

      } else {
        modalScanner.style.display = "none";
      }

    });


  });
</script>