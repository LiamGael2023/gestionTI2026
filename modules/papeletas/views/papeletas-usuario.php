<style>
    @media (min-width: 1000px) {

        .tablaRegistroPapeleta {
            width: 100% !important;
            table-layout: fixed !important;
            /* obliga a respetar porcentajes */
        }

        .col-id {
            width: 1% !important;
        }

        .col-qr {
            width: 3% !important;
        }

        .col-firmas {
            width: 14% !important;
        }

        .col-concepto {
            width: 23% !important;
        }

        .col-fecha {
            width: 10% !important;
        }

        .col-hora {
            width: 8% !important;
        }

        .col-lugar {
            width: 9% !important;
        }

        .col-retorno {
            width: 4% !important;
        }


        .col-jefe {
            width: 12% !important;
        }


        .col-acciones {
            width: 16% !important;
        }


        /* Garantiza que las imágenes, SVG o avatares no rompan el ancho */
        .tablaRegistroPapeleta td img,
        .tablaRegistroPapeleta td svg,
        .tablaRegistroPapeleta td .avatar-lightbox {
            max-width: 100%;
            height: auto;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        td {
            max-width: 180px;
            /* Ajusta según tu diseño */
            white-space: nowrap;
            /* No permite salto de línea */
            overflow: hidden;
            /* Oculta lo que se desborda */
            text-overflow: ellipsis;
            /* Muestra los ... */
        }

        .tablaRegistroPapeleta td:nth-child(3),
        .tablaRegistroPapeleta th:nth-child(3) {
            text-align: center !important;
            vertical-align: middle !important;

        }

        .tablaRegistroPapeleta td:nth-child(2),
        .tablaRegistroPapeleta td:nth-child(3),
        .tablaRegistroPapeleta td:nth-child(8),
        .tablaRegistroPapeleta td:nth-child(10) {
            text-overflow: clip !important;
            /* No muestra los “…” */
        }



    }
</style>
<div class="row row-cards">
    <div class="col-12">
        <!-- Card Superior -->
        <div class="card">
            <div class="card-header d-flex justify-content-center py-1">
                <?php
                // Llamar al controlador
                $fotoJefe = ControladorPapeleta::ctrFotoJefe();

                $fotoArchivo = $fotoJefe . '.jpg';

                $rutaFisica = __DIR__ . '/../../../public/fotos-trabajador/' . $fotoArchivo;

                $fotoPath = '/gestionTI/public/fotos-trabajador/' . $fotoArchivo;
                $rutaSinFoto = '/gestionTI/public/fotos-trabajador/sinfoto.jpg';

                $fotoFinal = file_exists($rutaFisica) ? $fotoPath : $rutaSinFoto;
                ?>
                <script>
                    console.log("Ruta física:", "<?php echo $rutaFisica; ?>");
                    console.log("Ruta web:", "<?php echo $fotoPath; ?>");
                    console.log("Ruta final usada:", "<?php echo $fotoFinal; ?>");
                </script>

                <div class="d-inline-flex align-items-center gap-2">
                    <!-- Avatar -->
                    <div class="avatar rounded" style="
                                width:60px;
                                height:60px;
                                background-size:cover;
                                background-position:center;
                                background-image:url('<?php echo $fotoFinal; ?>');">
                    </div>

                    <!-- Texto en línea -->
                    <div class="d-flex">
                        <strong class="me-1" style="font-size: 0.9rem;">Jefe Inmediato:</strong>
                        <span style="font-size: 0.9rem;"><?php echo $_SESSION["JefeInmediato"]; ?> <br>
                            <strong class="me-1" style="font-size: 0.9rem;">
                                <?php
                                if (isset($_SESSION["Oficina"]) && $_SESSION["Oficina"] !== "") {
                                    $texto = $_SESSION["Oficina"];
                                    echo $texto;
                                } else {
                                    echo "No disponible";
                                }
                                ?>
                            </strong></span>
                    </div>
                </div>



            </div>
            <div class="card-table">
                <div id="advanced-table">
                    <div class="table-responsive">
                        <style>
                            td h6[title]:hover::after {
                                content: attr(title);
                                position: absolute;
                                background-color: #fff;
                                color: #000;
                                border: 1px solid #ccc;
                                padding: 5px;
                                box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
                                z-index: 9999;
                            }

                            .btn-tabler {
                                font-size: 12px;
                                /* Reduce el tamaño del texto si es necesario */
                                padding: 5px 10px;
                                /* Reduce el espaciado dentro del botón */
                            }

                            .btn-tabler svg {
                                width: 14px;
                                /* Ajusta el tamaño del icono */
                                height: 14px;
                                /* Ajusta el tamaño del icono */
                            }
                        </style>
                        <!-- Tabla de Papeletas Registradas -->
                        <table id="new-cons"
                            class="display table table-striped table-hover dt-responsive nowrap tablaRegistroPapeleta"
                            style="width: 100%">
                            <thead>
                                <tr>

                                    <th class="col-id">ID</th>
                                    <th class="col-qr">QR</th>
                                    <th class="col-firmas">Firmas</th>
                                    <th class="col-concepto">Concepto</th>
                                    <th class="col-fecha">Fecha</th>
                                    <th class="col-hora">Hora</th>
                                    <th class="col-lugar">Lugar</th>
                                    <th class="col-retorno">Retorno</th>
                                    <th class="col-jefe">Jefe Inmediato</th>
                                    <th class="col-acciones">Acciones</th>

                                </tr>
                            </thead>

                        </table>
                        <script>
                            function redirectToProgramacionDetail(id) {
                                document.cookie = "id=" + encodeURIComponent(id) + "; path=/"; // Establece una cookie con el ID
                                window.location.href = 'programacion-detalle'; // Redirige sin el ID en la URL
                            }
                        </script>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>