<?php
session_start();
ob_start();

require_once __DIR__ . "/../controllers/PapeletaVehicularController.php";
require_once __DIR__ . "/../models/PapeletaVehicularModel.php";

class tablaAminPapeleta
{

    public function mostrartablaPapeletasVehiculares()
    {


        $draw   = isset($_POST["draw"])   ? intval($_POST["draw"]) : 1;
        $start  = isset($_POST["start"])  ? intval($_POST["start"]) : 0;
        $length = isset($_POST["length"]) ? intval($_POST["length"]) : 10;
        $search = $_POST["search"]["value"] ?? null;
        $search = trim($search);
        $search = ($search === "") ? null : $search;

        $id_establecimiento = isset($_POST["id_establecimiento"]) ? intval($_POST["length"]) : null;
        $filtroFecha = $_POST["filtroFecha"] ?? null;
        $filtroFecha = trim($filtroFecha);
        $filtroFecha = ($filtroFecha === "") ? null : $filtroFecha;

        $filtroFirma = $_POST["filtroFirma"] ?? null;
        $filtroFirma = trim($filtroFirma);
        $filtroFirma = ($filtroFirma === "") ? null : $filtroFirma;


        $respuesta = ControladorPapeletaVehicular::ctrMostrarPapeletasVehiculares(
            $id_establecimiento,
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
                'ANUL' => [
                    'title' => 'Anulado',
                    'class' => 'btn-danger',
                    'disabled' => '',
                    'icon' => '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="estado-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2c5.523 0 10 4.477 10 10s-4.477 10 -10 10s-10 -4.477 -10 -10s4.477 -10 10 -10m3.6 5.2a1 1 0 0 0 -1.4 .2l-2.2 2.933l-2.2 -2.933a1 1 0 1 0 -1.6 1.2l2.55 3.4l-2.55 3.4a1 1 0 1 0 1.6 1.2l2.2 -2.933l2.2 2.933a1 1 0 0 0 1.6 -1.2l-2.55 -3.4l2.55 -3.4a1 1 0 0 0 -.2 -1.4" /></svg>',
                    'onclick' => 'return false;',

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

            return '<td>
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
    </td>';
        }

        // ✅ Función para generar el grupo de botones de estados
        function generateStatusGroup($papeleta)
        {
            $buttons = '';

            // Reutiliza tu función existente
            $buttons .= generateStatusButton($papeleta['estadoJI'], 'estadoJI', $papeleta['id_papeleta']);
            $buttons .= generateStatusButton($papeleta['estadoJP'], 'estadoJP', $papeleta['id_papeleta']);
            $buttons .= generateStatusButton($papeleta['estado_subgerencia'], 'estado_subgerencia', $papeleta['id_papeleta']);
            $buttons .= generateStatusButton($papeleta['estado_transportes'], 'estado_transportes', $papeleta['id_papeleta']);

            // Elimina los <td> internos de cada botón (ya que los vas a agrupar)
            $buttons = str_replace(['<td>', '</td>'], '', $buttons);

            // Devuelve todo dentro de un solo td con el grupo
            return '<td>
                <div class="btn-group btn-group-sm" role="group">
                    ' . $buttons . '
                </div>
            </td>';
        }




        // Generate buttons for each status
        $i = 1;

        foreach ($papeletas as $key => $papeleta) {
            //   echo "Estado ji " . $papeletas["estadoJI"];
            $id = '<td><h6>' . $start + $key + 1 . '</h6></td>';


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



            $nombres = '<td style="padding:4px 4px; max-width:30px,width:30px; text-align:center;"><h6>' . ($papeleta["apellidos"]) . '<br>' . ($papeleta["nombres"]) . '</h6></td>';

            $grupoEstados = generateStatusGroup($papeleta);


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
            $fecha_rango = '<td><h6><div>' .
                ($papeleta["fecha_inicio"] instanceof DateTime ? $papeleta["fecha_inicio"]->format('d/m/y') : date('d/m/y', strtotime($papeleta["fecha_inicio"]))) . '-' .
                '</div><div>' .
                ($papeleta["fecha_fin"] instanceof DateTime ? $papeleta["fecha_fin"]->format('d/m/y') : date('d/m/y', strtotime($papeleta["fecha_fin"]))) .
                '</div><h6></td>';
            $Id_Trabajador_Concepto_APP = '<td><h6 title="' . ($papeleta["Id_Trabajador_Concepto_APP"]) . '">' . (substr($papeleta["Id_Trabajador_Concepto_APP"], 0, 16)) . '...</h6></td>';
            $hora_rango = '<td><h6></div><div><div>' .
                ($papeleta["hora_salida"] instanceof DateTime ? $papeleta["hora_salida"]->format('H:i') : date('H:i', strtotime($papeleta["hora_salida"]))) . '-' .
                '</div><div>' .
                ($papeleta["hora_llegada"] instanceof DateTime ? $papeleta["hora_llegada"]->format('H:i') : date('H:i', strtotime($papeleta["hora_llegada"]))) .
                '</div></h6></td>';
            if ($papeleta["sinretorno"] === 'NO') {

                $sinretorno = '<a  target="_blank" class="btn btn-icon ">
                                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-square-x" style="
    width: 30px;
    height: 30px;
    fill: red   ;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 2h-14a3 3 0 0 0 -3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3 -3v-14a3 3 0 0 0 -3 -3zm-9.387 6.21l.094 .083l2.293 2.292l2.293 -2.292a1 1 0 0 1 1.497 1.32l-.083 .094l-2.292 2.293l2.292 2.293a1 1 0 0 1 -1.32 1.497l-.094 -.083l-2.293 -2.292l-2.293 2.292a1 1 0 0 1 -1.497 -1.32l.083 -.094l2.292 -2.293l-2.292 -2.293a1 1 0 0 1 1.32 -1.497z" /></svg></a>';
            } else {

                $sinretorno = '<a  target="_blank" class="btn btn-icon ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-square-check" style="
    width: 30px;
    height: 30px;
    fill: green;
"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M18.333 2c1.96 0 3.56 1.537 3.662 3.472l.005 .195v12.666c0 1.96 -1.537 3.56 -3.472 3.662l-.195 .005h-12.666a3.667 3.667 0 0 1 -3.662 -3.472l-.005 -.195v-12.666c0 -1.96 1.537 -3.56 3.472 -3.662l.195 -.005h12.666zm-2.626 7.293a1 1 0 0 0 -1.414 0l-3.293 3.292l-1.293 -1.292l-.094 -.083a1 1 0 0 0 -1.32 1.497l2 2l.094 .083a1 1 0 0 0 1.32 -.083l4 -4l.083 -.094a1 1 0 0 0 -.083 -1.32z"></path></svg>
                                    </a>';
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


            $establecimiento = '<h6 style="max-width:60px ;; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" 
             title="' . ($papeleta["establecimiento"]) . '">'
                . ($papeleta["establecimiento"])
                . '</h6>';

            $tieneEvidencias = ($papeleta["tiene_evidencias"] == 1);

            $acciones = '<td>
                <div class="btn-group" role="group">
                    <!-- Botón PDF -->
                    <!-- Botón que abre visor PDF en modal -->
                    <button type="button"
                        class="btn btn-icon btn-x"
                        data-bs-toggle="modal"
                        data-bs-target="#pdfModal"
                        data-pdf-url="pdf/pdf/papeleta-vehicular.php?id=' . htmlspecialchars($papeleta["id_papeleta"]) . '">
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

       

        <!-- Botón Evidencias -->
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

            // 👇 Solo mostrar botón "Editar" si es jefe de sede
            

            $acciones .= '
    </div>
</td>';

            $fila = array(
                $id,
                $foto,
                $id_papeleta,
                $nombres,
                $grupoEstados,
                $fecha_rango,
                $hora_rango,
                $sinretorno,
                $motivojunto,
                $establecimiento,
                $acciones,



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
$activarPapeleta->mostrartablaPapeletasVehiculares();
