<?php
session_start();
ob_start();


require_once __DIR__ . "/../controllers/VehiculoController.php";
require_once __DIR__ . "/../models/VehiculoModel.php";
class tablaVehiculo
{

    public function mostrartablaVehiculo()
    {
        $item = null;
        $valor = null;

        $vehiculo = ControladorVehiculo::ctrMostrarVehiculo($item, $valor);
        error_log("Número de registros encontrados: " . count($vehiculo));

        if (count($vehiculo) == 0) {
            echo json_encode(["data" => []]);
            return;
        }

        $datos = [];

        for ($i = 0; $i < count($vehiculo); $i++) {
            $estado = $vehiculo[$i]["estado_asignado"];
            $idVehiculo = isset($vehiculo[$i]["Id_Vehiculo"]) ? $vehiculo[$i]["Id_Vehiculo"] : "";

            // Selecciona el modal correcto
            if ($estado === "LIBRE") {
                $modalTarget = "#modal-estado_asignado";
            } else {
                $modalTarget = "#modal-desasignar";
            }

            $btnClass = ($estado === "LIBRE") ? "btn-secondary" : "btn-success";
            // Ruta física en el servidor (para verificar existencia)
            $rutaBaseProyecto = dirname(__DIR__, 3); // sube hasta gestionTI
            $rutaFotosServidor = $rutaBaseProyecto . '/public/fotos-trabajador/';

            $fotoReal = !empty($vehiculo[$i]["Trab_Fotocheck"])
                ? $rutaFotosServidor . $vehiculo[$i]["Trab_Fotocheck"] . '.jpg'
                : '';

            if (!empty($fotoReal) && file_exists($fotoReal)) {
                $fotoPath = '/gestionTI/public/fotos-trabajador/' . $vehiculo[$i]["Trab_Fotocheck"] . '.jpg';
            } else {
                $fotoPath = '/gestionTI/public/fotos-trabajador/sinfoto.jpg';
            }


            $foto = '<a href="' . $fotoPath . '" 
            class="avatar-lightbox d-inline-block" 
            style="width:40px; height:40px; border-radius:0.25rem; overflow:hidden;"
            >
            <img src="' . $fotoPath . '" 
                 style="width:100%; height:100%; object-fit:cover; display:block;">
         </a>';

            $datos[] = [
                '<td><h6>' . ($i + 1) . '</h6></td>',
                $foto,
                '<td><h6>' . $vehiculo[$i]["marca"] . '</h6></td>',

                '<td><h6>' . $vehiculo[$i]["placa"] . '</h6></td>',
                $estado_vehiculo = '
                    <a href="#"
                    class="btn ' . $btnClass . ' btn-estado_vehiculo btn-cuadrado" 
                    data-id="' . $idVehiculo . '" 
                    data-placa="' . $vehiculo[$i]["placa"] . '"
                    data-marca="' . $vehiculo[$i]["marca"] . '"
                    data-bs-toggle="modal"
                    data-bs-target="' . $modalTarget . '"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="estado-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 14a3 3 0 1 1 -3 3l.005 -.176a3 3 0 0 1 2.995 -2.824m11 0a3 3 0 1 1 -3 3l.005 -.176a3 3 0 0 1 2.995 -2.824m-11 2a1 1 0 1 0 0 2a1 1 0 0 0 0 -2m11 0a1 1 0 1 0 0 2a1 1 0 0 0 0 -2m-3.562 -12a3 3 0 0 1 2.91 2.272l.433 1.728h2.219a3 3 0 0 1 2.995 2.824l.005 .176v3.02l-.01 .117a1 1 0 0 1 -.286 .575l-.107 .091l-.07 .049l-.076 .042l-.106 .046l-.017 .005l-.047 .016l-.108 .025l-.118 .013l-.08 .002l-.122 -.012l-.148 -.033l-.063 -.022a1 1 0 0 1 -.362 -.24l-.08 -.094a4 4 0 0 0 -3.2 -1.6a4 4 0 0 0 -3.2 1.6a1 1 0 0 1 -.8 .4h-3a1 1 0 0 1 -.8 -.4a3.998 3.998 0 0 0 -6.402 .002a1 1 0 1 1 -1.602 -1.198c.493 -.66 1.11 -1.2 1.804 -1.602v-2.792a1 1 0 0 1 .06 -.35l.042 -.1l2.004 -4.007a1 1 0 0 1 .894 -.553zm-12.438 2a1 1 0 0 1 1 1v4a1 1 0 0 1 -2 0v-4a1 1 0 0 1 1 -1m12.438 0h-3.438v2h4.718l-.31 -1.243a1 1 0 0 0 -.97 -.757m-5.438 0h-1.382l-1.001 2h2.383z" /></svg></a>
                    <style>
                    .btn-cuadrado {
                        width: 40px;
                        height: 40px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 0;
                    }
                        .btn-cuadrado .estado-icon {
                width: 80%;
                height: 80%;
                display: block;
            }
                </style>
                    ',

                '<td><h6>' . 
                    $vehiculo[$i]["conductor"]
                    ?: 'LIBRE'
                 . '</h6></td>',
                '<td><h6>' . ($vehiculo[$i]["nombre_tipo"]) . '</h6></td>',
                // Botón de estado con data-id agregado


                // 👇 Conductor en renglones distintos



                '<td><h6>' . $vehiculo[$i]["modelo"] . '</h6></td>',
                '<td><h6>' . $vehiculo[$i]["codigo_patrimonial"] . '</h6></td>',

                // 👇 Jefe inmediato en renglones distintos
                '<td><h6>' . $vehiculo[$i]["jefe_inmediato"]
                    ?: "" . '</h6></td>',

                // Acciones
                $acciones = '
                
                <td> <div class="btn-group" role="group">

        <!-- Botón PDF -->
         <!-- Botón que abre visor PDF en modal -->
        <button type="button"
            class="btn btn-icon btn-blue"
            data-bs-toggle="modal"
            data-bs-target="#pdfModal"
            data-pdf-url="pdf/pdf/reportevehiculo.php?placa=' . $vehiculo[$i]["placa"] . '">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" 
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" 
                                stroke-linecap="round" stroke-linejoin="round" 
                                class="icon icon-tabler icons-tabler-outline icon-tabler-car">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M3 13l1 -5a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2l1 5" />
                                <path d="M5 13h14v6a1 1 0 0 1 -1 1h-1a1 1 0 0 1 -1 -1v-1h-10v1a1 1 0 0 1 -1 1h-1a1 1 0 0 1 -1 -1v-6z" />
                                <path d="M7 16v.01" />
                                <path d="M17 16v.01" />
                            </svg> 
        </button>
                 

        <button type="button"
            class="btn btn-icon btn-x"
            data-bs-toggle="modal"
            data-bs-target="#pdfModal"
            data-pdf-url="pdf/pdf/reportehistorialvehiculo-papeleta.php?placa=' . $vehiculo[$i]["Id_Vehiculo"] . '">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                                <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" />
                                <path d="M17 18h2" />
                                <path d="M20 15h-3v6" />
                                <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
                            </svg>
        </button>
                   

                    
                                     <!-- Botón Eliminar -->
                                    <a class="btn btn-icon btn-youtube btn-anular" 
                                    data-id="' . $vehiculo[$i]["placa"] . '">

                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-trash">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M4 7l16 0" />
                                            <path d="M10 11l0 6" />
                                            <path d="M14 11l0 6" />
                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                        </svg>
                                    </a>
                <!-- <a class="btn  btn-icon btn-teal"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-search"> <path stroke="none" d="M0 0h24v24H0z" fill="none"/> <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /> <path d="M21 21l-6 -6" /> </svg> </a> </div> </td> -->'

            ];
        }

        echo json_encode(["data" => $datos], JSON_UNESCAPED_UNICODE);
    }
}

$activarVehiculo = new tablaVehiculo();
$activarVehiculo->mostrartablaVehiculo();
