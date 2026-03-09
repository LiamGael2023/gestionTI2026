 <div class="row row-cards">
                <div class="col-12">
                    <!-- Card Superior -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-center py-1">
                            <?php
                            // Llamar al controlador
                            $fotoJefe = ControladorPapeleta::ctrFotoJefe(); // "trab_fotocheck" o "default"
                            ?>
                            <div class="d-inline-flex align-items-center gap-2">
                                <!-- Avatar -->
                                <div class="avatar rounded" style="
                                        width: 60px;
                                        height: 60px;
                                        background-size: cover;
                                        background-position: center;
                                        background-image: url('/personal/fotosIndividuales/<?php echo $fotoJefe; ?>.jpg');">
                                </div>
                                <!-- Texto en línea -->
                                <div class="d-flex">
                                    <strong class="me-1" style="font-size: 0.9rem;">Jefe Inmediato:</strong>
                                    <span style="font-size: 0.9rem;"><?php echo $_SESSION["JefeInmediato"]; ?> <br>
                                        <strong class="me-1" style="font-size: 0.9rem;">
                                            <?php
                                            if (isset($_SESSION["Oficina"]) && $_SESSION["Oficina"] !== "") {
                                                $texto = $_SESSION["Oficina"];
                                                $texto_utf8 = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
                                                echo htmlspecialchars($texto_utf8, ENT_QUOTES, 'UTF-8');
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