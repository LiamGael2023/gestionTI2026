<?php
session_start();
ob_start();


require_once __DIR__ . "/../controllers/PapeletasController.php";
require_once __DIR__ . "/../models/PapeletasModel.php";

class tablaAminPapeleta
{

    public function mostrartablaPapeleta()
    {


        $draw   = isset($_POST["draw"])   ? intval($_POST["draw"]) : 1;
        $start  = isset($_POST["start"])  ? intval($_POST["start"]) : 0;
        $length = isset($_POST["length"]) ? intval($_POST["length"]) : 10;
        $search = $_POST["search"]["value"] ?? null;
        $search = trim($search);
        $search = ($search === "") ? null : $search;

        $id_trabajador = $_SESSION["id_Trabajador"];
        $filtroFecha = $_POST["filtroFecha"] ?? null;
        $filtroFecha = trim($filtroFecha);
        $filtroFecha = ($filtroFecha === "") ? null : $filtroFecha;

        $filtroFirma = $_POST["filtroFirma"] ?? null;
        $filtroFirma = trim($filtroFirma);
        $filtroFirma = ($filtroFirma === "") ? null : $filtroFirma;


        $respuesta = ControladorPapeleta::ctrMostrarPapeletasPendientesJefe(
            $id_trabajador,
            $start,
            $length,
            $search,
            $filtroFecha,      // ← HOY, AYER, MES, ESTE AÑO...
            $filtroFirma

        );
        if (!$respuesta || !isset($respuesta["data"])) {
            $respuesta = ["total" => 0, "data" => []];
        }

        $totalFiltrado = intval($respuesta["total"]);
        $papeletas = $respuesta["data"];

        $datos = [];


        function generateStatusButton($status, $field, $id)
        {
            $status = ($status);

            // Mapeo de estados con título, clase, ícono y deshabilitado
            $statusMap = [
                'APR' => [
                    'title' => 'Aprobado',
                    'class' => 'btn-success',
                    'disabled' => '',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="estado-icon">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M17 3.34a10 10 0 1 1 -14.995 8.984l-.005 -.324l.005 -.324a10 10 0 0 1 14.995 -8.336zm-1.293 5.953a1 1 0 0 0 -1.32 -.083l-.094 .083l-3.293 3.292l-1.293 -1.292l-.094 -.083a1 1 0 0 0 -1.403 1.403l.083 .094l2 2l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l4 -4l.083 -.094a1 1 0 0 0 -.083 -1.32z"/>
                        </svg>'
                ],
                'PEN' => [
                    'title' => 'Pendiente',
                    'class' => 'btn-warning',
                    'disabled' => '',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="estado-icon">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M17 3.34a10 10 0 1 1 -14.995 8.984l-.005 -.324l.005 -.324a10 10 0 0 1 14.995 -8.336zm-5 2.66a1 1 0 0 0 -.993 .883l-.007 .117v5l.009 .131a1 1 0 0 0 .197 .477l.087 .1l3 3l.094 .082a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-2.707 -2.708v-4.585l-.007 -.117a1 1 0 0 0 -.993 -.883z"/>
                        </svg>'
                ],
                '' => [
                    'title' => 'No disponible',
                    'class' => 'btn-secondary',
                    'disabled' => 'disabled',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="estado-icon">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M12 2l.324 .001l.318 .004l.616 .017l.299 .013l.579 .034l.553 .046c4.785 .464 6.732 2.411 7.196 7.196l.046 .553l.034 .579c.005 .098 .01 .198 .013 .299l.017 .616l.005 .642l-.005 .642l-.017 .616l-.013 .299l-.034 .579l-.046 .553c-.464 4.785 -2.411 6.732 -7.196 7.196l-.553 .046l-.579 .034c-.098 .005 -.198 .01 -.299 .013l-.616 .017l-.642 .005l-.642 -.005l-.616 -.017l-.299 -.013l-.579 -.034l-.553 -.046c-4.785 -.464 -6.732 -2.411 -7.196 -7.196l-.046 -.553l-.034 -.579a28.058 28.058 0 0 1 -.013 -.299l-.017 -.616c-.003 -.21 -.005 -.424 -.005 -.642l.001 -.324l.004 -.318l.017 -.616l.013 -.299l.034 -.579l.046 -.553c.464 -4.785 2.411 -6.732 7.196 -7.196l.553 -.046l.579 -.034c.098 -.005 .198 -.01 .299 -.013l.616 -.017c.21 -.003 .424 -.005 .642 -.005zm3 9h-6l-.117 .007a1 1 0 0 0 .117 1.993h6l.117 -.007a1 1 0 0 0 -.117 -1.993z"/>
                        </svg>'
                ]
            ];

            $config = $statusMap[$status] ?? $statusMap[''];

            return '
        <style>
            .btn-cuadrado {
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0;
            }
            .btn-cuadrado .estado-icon {
                width: 80%;
                height: 80%;
            }
        </style>
        <div class="btn-group btn-group-sm" role="group">
            <button class="btn ' . $config['class'] . ' btn-cambiar-estado btn-cuadrado"
                    data-campo="' . $field . '" 
                    data-id="' . $id . '"
                    title="' . $config['title'] . '" ' . $config['disabled'] . '>' . $config['icon'] . '</button>
        </div>
    ';
        }



        // Generate buttons for each status
        $i = 1;

        foreach ($papeletas as $key => $papeleta) {
            //   echo "Estado ji " . $papeletas["estadoJI"];
            $id = '<h6>' . $start + $key + 1 . '</h6>';


            if (!is_array($papeleta)) continue;

            $rutaBaseProyecto = dirname(__DIR__, 3); // sube hasta gestionTI
            $rutaFotosServidor = $rutaBaseProyecto . '/public/fotos-trabajador/';

            $fotoReal = !empty($papeleta["Trab_Fotocheck"])
                ? $rutaFotosServidor . $papeleta["Trab_Fotocheck"] . '.jpg'
                : '';

            if (!empty($fotoReal) && file_exists($fotoReal)) {
                $fotoPath = '/gestionTI/public/fotos-trabajador/' . $papeleta["Trab_Fotocheck"] . '.jpg';
            } else {
                $fotoPath = '/gestionTI/public/fotos-trabajador/sinfoto.jpg';
            }


            $foto = '<a href="' . $fotoPath . '" 
            class="avatar-lightbox d-inline-block" 
            style="width:40px; height:40px; border-radius:0.25rem; overflow:hidden;"
            data-caption="Foto de ' . htmlspecialchars($papeleta["nombres"]) . '">
            <img src="' . $fotoPath . '" 
                 style="width:100%; height:100%; object-fit:cover; display:block;">
         </a>';







            $nombres = '<h6>' . ($papeleta["apellidos"]) . '<br>' . ($papeleta["nombres"]) . '</h6>';

            $jefeinmediato = generateStatusButton($papeleta['estadoJI'], 'estadoJI', $papeleta['id_papeleta']);
            $jefepersonal = generateStatusButton($papeleta['estadoJP'], 'estadoJP', $papeleta['id_papeleta']);
            $subgerencia = generateStatusButton($papeleta['estado_subgerencia'], 'estado_subgerencia', $papeleta['id_papeleta']);
            $jefetransportes = generateStatusButton($papeleta['estado_transportes'], 'estado_transportes', $papeleta['id_papeleta']);


            $id_papeleta = '
                            <a  href="#" 
                                class="btn btn-icon" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalQR" 
                                data-id="' . $papeleta["id_papeleta"] . '">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" 
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-qrcode" 
                                    style="width: 30px; height: 30px;">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 
                                    1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                    <path d="M7 17l0 .01" />
                                    <path d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 
                                    1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                    <path d="M7 7l0 .01" />
                                    <path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 
                                    1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                    <path d="M17 7l0 .01" />
                                    <path d="M14 14l3 0" />
                                    <path d="M20 14l0 .01" />
                                    <path d="M14 14l0 3" />
                                    <path d="M14 20l3 0" />
                                    <path d="M17 17l3 0" />
                                    <path d="M20 17l0 3" />
                                </svg>
                            </a>';
            $fecha_rango = '<h6><div>' .
                ($papeleta["fecha_inicio"] instanceof DateTime ? $papeleta["fecha_inicio"]->format('d/m/y') : date('d/m/y', strtotime($papeleta["fecha_inicio"]))) . '-' .
                '</div><div>' .
                ($papeleta["fecha_fin"] instanceof DateTime ? $papeleta["fecha_fin"]->format('d/m/y') : date('d/m/y', strtotime($papeleta["fecha_fin"]))) .
                '</div><h6>';
            $Id_Trabajador_Concepto_APP = '<h6 title="' . ($papeleta["Id_Trabajador_Concepto_APP"]) . '">' . (substr($papeleta["Id_Trabajador_Concepto_APP"], 0, 16)) . '...</h6>';
            $horaIni = is_object($papeleta["hora_salida"]) ? $papeleta["hora_salida"]->format('H:i') : date('H:i', strtotime($papeleta["hora_salida"]));
            $horaFin = is_object($papeleta["hora_llegada"]) ? $papeleta["hora_llegada"]->format('H:i') : date('H:i', strtotime($papeleta["hora_llegada"]));

            $horaIniColor = ($horaIni !== "00:00") ? "chip-green" : "chip-white";
            $horaFinColor = ($horaFin !== "00:00") ? "chip-green" : "chip-white";

            $hora_rango = "
    
        <h6 style='margin:0'>
            <span class='chip $horaIniColor'>$horaIni</span>
        </h6>
        <h6 style='margin:0'>
            <span class='chip $horaFinColor'>$horaFin</span>
        </h6>
    
";
            if ($papeleta["sinretorno"] === 'NO') {
                $sinretorno = '<span class="btn btn-icon btn-red" >
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48"><path fill="#ffffff" fill-rule="evenodd" d="M8.14 45.956c3.271.266 8.462.544 15.864.544c7.401 0 12.593-.278 15.864-.544c3.288-.267 5.825-2.804 6.092-6.092c.266-3.271.544-8.463.544-15.864s-.278-12.593-.544-15.864c-.267-3.288-2.804-5.825-6.092-6.092c-3.271-.266-8.463-.544-15.864-.544s-12.593.278-15.864.544c-3.288.267-5.825 2.804-6.092 6.092c-.266 3.271-.544 8.463-.544 15.864s.278 12.593.544 15.864c.267 3.288 2.804 5.825 6.092 6.092m17.756-29.238c-1.414-.049-2.64-.922-2.696-2.335a12 12 0 0 1 0-.967c.057-1.414 1.283-2.284 2.697-2.336a131 131 0 0 1 6.108-.074a6.05 6.05 0 0 1 6 6.047l-.009 10.81c-.003 3.312-2.687 6.001-5.998 6.08c-4.872.117-10.272.328-13.704.473a63 63 0 0 1-.11 2.4c-.085 1.249-1.273 1.794-2.272 1.04a55 55 0 0 1-2.915-2.378c-1.713-1.487-2.693-2.602-3.246-3.348a1.844 1.844 0 0 1 0-2.247c.553-.745 1.534-1.861 3.246-3.348a55 55 0 0 1 2.915-2.38c1-.752 2.187-.207 2.271 1.041c.041.609.08 1.36.107 2.282c4.129.074 8.921.251 12.98.406a1 1 0 0 0 1.038-1v-9.133c0-.553-.448-1-1-.999c-.464.001-.882.004-1.27.006c-1.541.007-2.604.013-4.142-.04" clip-rule="evenodd"/></svg>            </span>';
            } else {
                $sinretorno = '<span class="btn btn-icon btn-green" >
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48"><path fill="#ffffff" fill-rule="evenodd" d="M8.14 45.956c3.271.266 8.462.544 15.864.544c7.401 0 12.593-.278 15.864-.544c3.288-.267 5.825-2.804 6.092-6.092c.266-3.271.544-8.463.544-15.864s-.278-12.593-.544-15.864c-.267-3.288-2.804-5.825-6.092-6.092c-3.271-.266-8.463-.544-15.864-.544s-12.593.278-15.864.544c-3.288.267-5.825 2.804-6.092 6.092c-.266 3.271-.544 8.463-.544 15.864s.278 12.593.544 15.864c.267 3.288 2.804 5.825 6.092 6.092m17.756-29.238c-1.414-.049-2.64-.922-2.696-2.335a12 12 0 0 1 0-.967c.057-1.414 1.283-2.284 2.697-2.336a131 131 0 0 1 6.108-.074a6.05 6.05 0 0 1 6 6.047l-.009 10.81c-.003 3.312-2.687 6.001-5.998 6.08c-4.872.117-10.272.328-13.704.473a63 63 0 0 1-.11 2.4c-.085 1.249-1.273 1.794-2.272 1.04a55 55 0 0 1-2.915-2.378c-1.713-1.487-2.693-2.602-3.246-3.348a1.844 1.844 0 0 1 0-2.247c.553-.745 1.534-1.861 3.246-3.348a55 55 0 0 1 2.915-2.38c1-.752 2.187-.207 2.271 1.041c.041.609.08 1.36.107 2.282c4.129.074 8.921.251 12.98.406a1 1 0 0 0 1.038-1v-9.133c0-.553-.448-1-1-.999c-.464.001-.882.004-1.27.006c-1.541.007-2.604.013-4.142-.04" clip-rule="evenodd"/></svg>            </span>';
            }
            $motivojunto = '<h6 style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'
                . '<span style="font-weight:bold; font-style:italic;">' . ($papeleta["Id_Trabajador_Concepto_APP"]) . '</span>'
                . '<br>'
                . '<span title="' . ($papeleta["Id_Trabajador_Motivo_APP"]) . '">'
                . ($papeleta["Id_Trabajador_Motivo_APP"])
                . '</span>'
                . '</h6>';

            $Id_Trabajador_Lugar_APP = '<h6 style="max-width:60px ;; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" 
    title="' . ($papeleta["Id_Trabajador_Lugar_APP"]) . '">'
                . ($papeleta["Id_Trabajador_Lugar_APP"])
                . '</h6>';

            $tieneEvidencias = ($papeleta["tiene_evidencias"] == 1);

            $botonEvidencias = '
<a class="btn btn-icon btn-tabler ' . ($tieneEvidencias ? '' : 'disabled opacity-20') . '"
    ' . ($tieneEvidencias ? 'data-bs-toggle="modal" data-bs-target="#modal-evidencias"' : '') . '
    data-id="' . $papeleta["id_papeleta"] . '">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
        viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        class="icon icon-tabler icons-tabler-outline icon-tabler-photo">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M15 8h.01" />
        <path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z" />
        <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" />
        <path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3" />
    </svg>
</a>';


            $esNoPermitido = ($papeleta["no_autorizado"] == 1 || $papeleta["no_autorizadoJP"] == 1);

            $acciones = '
    <div class="btn-group ' . ($esNoPermitido ? 'disabled-group' : '') . '" role="group">
        <!-- Botón PDF -->
         <!-- Botón que abre visor PDF en modal -->
        <button type="button"
            class="btn btn-icon btn-x"
            data-bs-toggle="modal"
            data-bs-target="#pdfModal"
            data-pdf-url="pdf/pdf/papeleta.php?id=' . htmlspecialchars($papeleta["id_papeleta"]) . '">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" />
                <path d="M17 18h2" />
                <path d="M20 15h-3v6" />
                <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
            </svg>
        </button>

        <!-- Botón No Permitido -->
        <a class="btn btn-icon btn-youtube btn-nopermitido" 
            data-id="' . $papeleta["id_papeleta"] . '">
<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 16 16"><path fill="#ffffff" d="M10.5 15a4.5 4.5 0 1 1 0-9a4.5 4.5 0 0 1 0 9m-2.803-2.404l4.9-4.9a3.5 3.5 0 0 0-4.9 4.9m.707.707a3.5 3.5 0 0 0 4.9-4.9zM9.626 5.07a5.5 5.5 0 0 0-3.299 1.848A2.751 2.751 0 1 1 9.626 5.07M5.6 8c-.384.75-.6 1.6-.6 2.5c0 1.31.458 2.512 1.222 3.457C3.555 13.653 2 11.803 2 10v-.5A1.5 1.5 0 0 1 3.5 8z"/></svg>
            </a>

        ' . $botonEvidencias . '
';

            // 👇 Solo mostrar botón "Editar" si es jefe de sede
            if (!empty($_SESSION["esJefeSede"]) && $_SESSION["esJefeSede"] == 1) {
                $acciones .= '
            <a class="btn btn-lime btn-icon btn-editar-jefe" 
                data-bs-toggle="modal"
                data-bs-target="#modal-cambiar_jefe"
            
                data-id="' . $papeleta["id_papeleta"] . '"
                data-jefeinmediato="' . $papeleta["JefeInmediato"] . '">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-edit">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                    <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                    <path d="M16 5l3 3" />
                </svg>
            </a>';
            }

            $acciones .= '
    </div>
';

            $claseFila = "";
            if ($papeleta["no_autorizado"] == 1 || $papeleta["no_autorizadoJP"] == 1) {
                $claseFila = "table-danger";
            }
            $fila = array(
                "DT_RowClass" => $claseFila,   // ← ESTA LÍNEA AGREGA LA CLASE A TODA LA FILA
                $id,
                $foto,
                $id_papeleta,
                $nombres,
                $jefeinmediato,
                $jefepersonal,
                $subgerencia,
                $jefetransportes,
                $fecha_rango,
                $hora_rango,
                $sinretorno,
                $motivojunto,
                $acciones,
                $Id_Trabajador_Lugar_APP,


            );

            $datos[] = $fila;
        }


        $respuestaFinal = [
            "draw" => $draw,
            "recordsTotal" => $totalFiltrado,
            "recordsFiltered" => $totalFiltrado,
            "data" => $datos
        ];

        // ✅ Limpia cualquier salida previa del buffer
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        // ✅ ENVÍA SOLO UN JSON, SIN DATA EXTRA
        echo json_encode($respuestaFinal, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
        exit;
    }
}

$activarPapeleta = new tablaAminPapeleta();
$activarPapeleta->mostrartablaPapeleta();
