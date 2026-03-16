
    <style>
      /* Modal full screen */
      #modalScanner {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
      }

      /* Contenido del modal */
      #modalScanner .modal-content {
        position: relative;
        width: 90vw;
        max-width: 400px;
        aspect-ratio: 1 / 1;
        /* cuadrado perfecto */
        padding: 0;
        background: #000;
        /* fondo negro para cámara */
        border-radius: 10px;
        overflow: hidden;
        /* evita que se salga el video */
      }

      /* Botón X */
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
        line-height: 28px;
        text-align: center;
      }

      /* Contenedor del QR */
      #qr-reader {
        width: 100%;
        height: 100%;
      }

      /* Forzar que el video ocupe todo el contenedor sin deformarse */
      #qr-reader video {
        object-fit: cover !important;
        /* cubre todo el cuadrado */
        width: 100% !important;
        height: 100% !important;
      }
    </style>

    <!-- MODAL DEL SCANNER -->
    <div id="modalScanner" class="modal" style="display:none;">
      <div class="modal-content" style="max-width: 400px; margin: auto; padding: 20px; position: relative;">
        <button id="btnCerrarScanner" style="position: absolute; top: 10px; right: 10px; border: none; background: transparent; font-size: 18px; cursor: pointer;">&times;</button>
        <h5>Escanea el QR</h5>
        <div id="qr-reader" style="width: 100%; height: 300px;"></div>
      </div>
    </div>


    
    <!-- Librería Html5Qrcode -->
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
      document.addEventListener("DOMContentLoaded", function() {

        let html5QrCode;

        const modalScanner = document.getElementById("modalScanner");
        const modalId = document.getElementById("modalId");
        const idText = document.getElementById("idPapeletaText");

        const btnOpenScanner = document.getElementById("btnOpenScanner");
        const btnCerrarScanner = document.getElementById("btnCerrarScanner");
        const btnCerrarId = document.getElementById("btnCerrarId");

        // Abrir modal scanner
        btnOpenScanner.addEventListener("click", function() {
          modalScanner.style.display = "flex";

          html5QrCode = new Html5Qrcode("qr-reader");
          const qrReader = $("#qr-reader");
          const width = qrReader.width();
          const qrBoxSize = Math.floor(width * 0.8); // 80% del contenedor

          Html5Qrcode.getCameras().then(cameras => {
            if (cameras && cameras.length) {
              const cameraId = cameras[1].id; // trasera por defecto
              html5QrCode.start(
                cameraId, {
                  fps: 10,
                  qrbox: qrBoxSize // ahora se ve claramente el cuadro
                },
                qrCodeMessage => {
                  console.log("QR leído:", qrCodeMessage);

                  // Cerrar scanner
                  html5QrCode.stop().then(() => {
                    modalScanner.style.display = "none";

                    // Mostrar modal del ID
                    idText.textContent = qrCodeMessage;
                    modalId.style.display = "flex";
                  }).catch(err => {
                    console.error("Error deteniendo la cámara", err);
                  });
                },
                errorMessage => {
                  // errores menores mientras escanea
                  console.warn("Error QR:", errorMessage);
                }
              ).catch(err => {
                console.error("Error iniciando cámara:", err);
              });
            }
          }).catch(err => {
            console.error("No se pudo obtener la cámara:", err);
            alert("No se pudo acceder a la cámara.");
          });
        });

        // Cerrar modal scanner manual
        btnCerrarScanner.addEventListener("click", function() {
          if (html5QrCode) {
            html5QrCode.stop().finally(() => {
              modalScanner.style.display = "none";
            });
          } else {
            modalScanner.style.display = "none";
          }
        });

        // Cerrar modal ID
        btnCerrarId.addEventListener("click", function() {
          modalId.style.display = "none";
        });

      });
    </script>