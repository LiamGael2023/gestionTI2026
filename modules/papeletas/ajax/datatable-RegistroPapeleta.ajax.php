<?php

session_start();
ob_start();

ini_set('display_errors', 1);
error_reporting(E_ERROR | E_PARSE);


require_once __DIR__ . "/../controllers/PapeletasController.php";
require_once __DIR__ . "/../models/PapeletasModel.php";

class tablaPapeleta
{

    public function mostrartablaPapeleta()
    {
        // $item = 'Id_Trabajador';




        $draw   = isset($_POST["draw"])   ? intval($_POST["draw"]) : 1;
        $start  = isset($_POST["start"])  ? intval($_POST["start"]) : 0;
        $length = isset($_POST["length"]) ? intval($_POST["length"]) : 10;
        $search = $_POST["search"]["value"]
            ?? $_POST["search"]
            ?? null;
        $id_trabajador = $_SESSION["id_Trabajador"];

        $respuesta = ControladorPapeleta::ctrMostrarPapeletasUsuario($id_trabajador, $start, $length, $search);

        function generateStatusButton($status, $field, $id, $titulo, $isClickable = false)
        {
            $status = ($status);

            $statusMap = [
                'APR' => [
                    'title' => $titulo . '-Aprobado',
                    'class' => 'btn-success',
                    'disabled' => '',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="estado-icon">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M17 3.34a10 10 0 1 1 -14.995 8.984l-.005 -.324l.005 -.324a10 10 0 0 1 14.995 -8.336zm-1.293 5.953a1 1 0 0 0 -1.32 -.083l-.094 .083l-3.293 3.292l-1.293 -1.292l-.094 -.083a1 1 0 0 0 -1.403 1.403l.083 .094l2 2l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l4 -4l.083 -.094a1 1 0 0 0 -.083 -1.32z"/>
                        </svg>'
                ],
                'PEN' => [
                    'title' => $titulo . ' -Pendiente',
                    'class' => 'btn-warning',
                    'disabled' => '',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="estado-icon">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M17 3.34a10 10 0 1 1 -14.995 8.984l-.005 -.324l.005 -.324a10 10 0 0 1 14.995 -8.336zm-5 2.66a1 1 0 0 0 -.993 .883l-.007 .117v5l.009 .131a1 1 0 0 0 .197 .477l.087 .1l3 3l.094 .082a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-2.707 -2.708v-4.585l-.007 -.117a1 1 0 0 0 -.993 -.883z"/>
                        </svg>'
                ],
                // 'ANUL' => [
                //     'title' => 'Anulado',
                //     'class' => 'btn-danger',
                //     'disabled' => '',
                //     'icon' => '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="estado-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2c5.523 0 10 4.477 10 10s-4.477 10 -10 10s-10 -4.477 -10 -10s4.477 -10 10 -10m3.6 5.2a1 1 0 0 0 -1.4 .2l-2.2 2.933l-2.2 -2.933a1 1 0 1 0 -1.6 1.2l2.55 3.4l-2.55 3.4a1 1 0 1 0 1.6 1.2l2.2 -2.933l2.2 2.933a1 1 0 0 0 1.6 -1.2l-2.55 -3.4l2.55 -3.4a1 1 0 0 0 -.2 -1.4" /></svg>',
                //     'onclick' => 'return false;',

                // ],
                '' => [
                    'title' => $titulo . ' -No disponible',
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
                        width: 33px;
                        height: 33px;
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
                <div class="btn-group " role="group">
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
            $buttons .= generateStatusButton($papeleta['estadoJI'], 'estadoJI', $papeleta['id_papeleta'], 'Jefe Inmediato');
            $buttons .= generateStatusButton($papeleta['estadoJP'], 'estadoJP', $papeleta['id_papeleta'], 'Jefe Personal');
            if ($_SESSION["EsConductor"] == 1) {
                $buttons .= generateStatusButton($papeleta['estado_subgerencia'], 'estado_subgerencia', $papeleta['id_papeleta'], 'Subgerencia');
                $buttons .= generateStatusButton($papeleta['estado_transportes'], 'estado_transportes', $papeleta['id_papeleta'], 'Jefe Transportes');
            }
            // Elimina los <td> internos de cada botón (ya que los vas a agrupar)
            $buttons = str_replace(['<td>', '</td>'], '', $buttons);

            // Devuelve todo dentro de un solo td con el grupo
            return '<td class="td-center">
                <div class="btn-group btn-group-lg" role="group">
                    ' . $buttons . '
                </div>
            </td>';
        }
        if (!$respuesta || !isset($respuesta["data"])) {
            $respuesta = ["total" => 0, "data" => []];
        }

        $totalFiltrado = intval($respuesta["total"]);
        $papeletas = $respuesta["data"];

        $datos = [];

        foreach ($papeletas as $key => $papeleta) {
            //   echo "Estado ji " . $papeletas["estadoJI"];

            $numeroFila = '<td><h6>' . $start + $key + 1 . '</h6></td>';

            if (!is_array($papeleta)) continue;

            // Jefe Inmediato
            $jefeinmediato = generateStatusButton($papeleta['estadoJI'], 'estadoJI', $papeleta['id_papeleta'], true);
            $jefepersonal = generateStatusButton($papeleta['estadoJP'], 'estadoJP', $papeleta['id_papeleta'], true);
            $subgerencia = generateStatusButton($papeleta['estado_subgerencia'], 'estado_subgerencia', $papeleta['id_papeleta'], false);
            $jefetransportes = generateStatusButton($papeleta['estado_transportes'], 'estado_transportes', $papeleta['id_papeleta'], false);

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

            $fecha_inicio = $papeleta["fecha_inicio"] instanceof DateTime ? $papeleta["fecha_inicio"]->format('d/m/Y') : $papeleta["fecha_inicio"];
            $fecha_fin = $papeleta["fecha_fin"] instanceof DateTime ? $papeleta["fecha_fin"]->format('d/m/Y') : $papeleta["fecha_fin"];
            $fechas = '<td><h6>De: ' . $fecha_inicio . ' <br>Hasta: ' . $fecha_fin . '</h6></td>';
            $hora_rango = '<td><h6>' .
                'De ' . ($papeleta["hora_salida"] instanceof DateTime
                    ? $papeleta["hora_salida"]->format('H:i')
                    : date('H:i', strtotime($papeleta["hora_salida"]))) .
                '<br>Hasta ' .
                ($papeleta["hora_llegada"] instanceof DateTime
                    ? $papeleta["hora_llegada"]->format('H:i')
                    : date('H:i', strtotime($papeleta["hora_llegada"]))) .
                '</h6></td>';

            if ($papeleta["sinretorno"] === 'NO') {
                $sinretorno = '<td><span class="btn btn-icon btn-red" >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48">
                    <path fill="#ffffff" fill-rule="evenodd" d="M8.14 45.956c3.271.266 8.462.544 15.864.544c7.401 0 12.593-.278 15.864-.544c3.288-.267 5.825-2.804 6.092-6.092c.266-3.271.544-8.463.544-15.864s-.278-12.593-.544-15.864c-.267-3.288-2.804-5.825-6.092-6.092c-3.271-.266-8.463-.544-15.864-.544s-12.593.278-15.864.544c-3.288.267-5.825 2.804-6.092 6.092c-.266 3.271-.544 8.463-.544 15.864s.278 12.593.544 15.864c.267 3.288 2.804 5.825 6.092 6.092m17.756-29.238c-1.414-.049-2.64-.922-2.696-2.335a12 12 0 0 1 0-.967c.057-1.414 1.283-2.284 2.697-2.336a131 131 0 0 1 6.108-.074a6.05 6.05 0 0 1 6 6.047l-.009 10.81c-.003 3.312-2.687 6.001-5.998 6.08c-4.872.117-10.272.328-13.704.473a63 63 0 0 1-.11 2.4c-.085 1.249-1.273 1.794-2.272 1.04a55 55 0 0 1-2.915-2.378c-1.713-1.487-2.693-2.602-3.246-3.348a1.844 1.844 0 0 1 0-2.247c.553-.745 1.534-1.861 3.246-3.348a55 55 0 0 1 2.915-2.38c1-.752 2.187-.207 2.271 1.041c.041.609.08 1.36.107 2.282c4.129.074 8.921.251 12.98.406a1 1 0 0 0 1.038-1v-9.133c0-.553-.448-1-1-.999c-.464.001-.882.004-1.27.006c-1.541.007-2.604.013-4.142-.04" clip-rule="evenodd"/></svg>            </span></td>';
            } else {
                $sinretorno = '<td><span class="btn btn-icon btn-green" >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48">
                    <path fill="#ffffff" fill-rule="evenodd" d="M8.14 45.956c3.271.266 8.462.544 15.864.544c7.401 0 12.593-.278 15.864-.544c3.288-.267 5.825-2.804 6.092-6.092c.266-3.271.544-8.463.544-15.864s-.278-12.593-.544-15.864c-.267-3.288-2.804-5.825-6.092-6.092c-3.271-.266-8.463-.544-15.864-.544s-12.593.278-15.864.544c-3.288.267-5.825 2.804-6.092 6.092c-.266 3.271-.544 8.463-.544 15.864s.278 12.593.544 15.864c.267 3.288 2.804 5.825 6.092 6.092m17.756-29.238c-1.414-.049-2.64-.922-2.696-2.335a12 12 0 0 1 0-.967c.057-1.414 1.283-2.284 2.697-2.336a131 131 0 0 1 6.108-.074a6.05 6.05 0 0 1 6 6.047l-.009 10.81c-.003 3.312-2.687 6.001-5.998 6.08c-4.872.117-10.272.328-13.704.473a63 63 0 0 1-.11 2.4c-.085 1.249-1.273 1.794-2.272 1.04a55 55 0 0 1-2.915-2.378c-1.713-1.487-2.693-2.602-3.246-3.348a1.844 1.844 0 0 1 0-2.247c.553-.745 1.534-1.861 3.246-3.348a55 55 0 0 1 2.915-2.38c1-.752 2.187-.207 2.271 1.041c.041.609.08 1.36.107 2.282c4.129.074 8.921.251 12.98.406a1 1 0 0 0 1.038-1v-9.133c0-.553-.448-1-1-.999c-.464.001-.882.004-1.27.006c-1.541.007-2.604.013-4.142-.04" clip-rule="evenodd"/></svg>            </span></td>';
            }

            $concepto = ($papeleta["Id_Trabajador_Concepto_APP"]);
            $motivo   = ($papeleta["Id_Trabajador_Motivo_APP"]);
            $lugar    = ($papeleta["Id_Trabajador_Lugar_APP"]);
            // Determinar si es conductor (puedes forzar cast a int por seguridad)
            $esConductor = isset($_SESSION["EsConductor"]) && intval($_SESSION["EsConductor"]) === 1;

            // Establecer límites dinámicos
            $limiteConceptoMotivo = $esConductor ? 30 : 60;
            $limiteLugar = $esConductor ? 15 : 30;
            $grupoEstados = generateStatusGroup($papeleta);

            // Obtener los datos ya codificados
            $concepto = ($papeleta["Id_Trabajador_Concepto_APP"]);
            $motivo   = ($papeleta["Id_Trabajador_Motivo_APP"]);
            $lugar    = ($papeleta["Id_Trabajador_Lugar_APP"]);

            // Aplicar truncamiento condicional
            $concepto_largo = strlen($concepto) > $limiteConceptoMotivo ? substr($concepto, 0, $limiteConceptoMotivo) . '...' : $concepto;
            $motivo_largo   = strlen($motivo) > $limiteConceptoMotivo ? substr($motivo, 0, $limiteConceptoMotivo) . '...' : $motivo;
            $lugar_largo    = strlen($lugar) > $limiteLugar ? substr($lugar, 0, $limiteLugar) . '...' : $lugar;

            // Crear la celda de concepto + motivo
            $concepto_motivo = '<td>' .
                '<h6 title="' . htmlspecialchars($concepto) . '">' .
                '<b><i><strong>' . htmlspecialchars($concepto_largo) . '</strong></i></b><br>' .
                htmlspecialchars($motivo_largo) .
                '</h6>' .
                '</td>';

            // Crear la celda de lugar
            $lugar_td = '<td><h6 title="' . htmlspecialchars($lugar) . '">' . htmlspecialchars($lugar_largo) . '</h6></td>';

            $JefeInmediato = '<td><h6>' . ($papeleta["JefeInmediato_apellidos"]) . '<br>' . ($papeleta["JefeInmediato_nombres"]) . '</h6></td>';
            $esNoPermitido = ($papeleta["no_autorizado"] == 1 || $papeleta["no_autorizadoJP"] == 1  || $papeleta["anulado"]);


            // Mostrar todos los botones normales
            $acciones = '<td>
    <div class="btn-group ' . ($esNoPermitido ? 'disabled-group' : '') . '" role="group">

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
        </button>';


            // 🔹 Botón Eliminar (Anular) — activo o deshabilitado según estadoJI
            $estadoJI = isset($papeleta["estadoJI"]) ? $papeleta["estadoJI"] : '';

            if ($estadoJI === 'PEN') {
                // Estado pendiente → botón activo y rojo
                $acciones .= '
        <button class="btn btn-icon btn-danger btn-anular" 
            data-id="' . $papeleta["id_papeleta"] . '"
            title="Anular papeleta">
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
        </button>';
            } else {
                // Estado distinto de PEN → deshabilitado y gris
                $acciones .= '
        <button class="btn btn-icon btn-secondary" disabled title="No puede anular">
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
        </button>';
            }

            $acciones .= '
        <!-- Botón Evidencias -->
        <a class="btn btn-icon btn-tabler"
            data-bs-toggle="modal"
            data-bs-target="#modal-evidencias"
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

            // 🔹 Mostrar botón Bitácora solo si Id_PapeletaVehicular no es NULL
            if (!empty($papeleta["Id_PapeletaVehicular"])) {
                $acciones .= '
        <a class="btn btn-icon btn-teal btn-bitacora"
            data-bs-toggle="modal"
            data-bs-target="#modal-bitacora"
            data-id="' . $papeleta["Id_PapeletaVehicular"] . '">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="icon icon-tabler icons-tabler-outline icon-tabler-list-letters">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M11 6h9" />
                <path d="M11 12h9" />
                <path d="M11 18h9" />
                <path d="M4 10v-4.5a1.5 1.5 0 0 1 3 0v4.5" />
                <path d="M4 8h3" />
                <path d="M4 20h1.5a1.5 1.5 0 0 0 0 -3h-1.5h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6z" />
            </svg>
        </a>';
            }

            $acciones .= '
    </div>
</td>';


            $claseFila = "";
            if ($papeleta["no_autorizado"] == 1 || $papeleta["no_autorizadoJP"] == 1 ||  $papeleta["anulado"]) {
                $claseFila = "table-danger";
            }



            $fila = array(
                "DT_RowClass" => $claseFila,   // ← ESTA LÍNEA AGREGA LA CLASE A TODA LA FILA
                $numeroFila,
                $id_papeleta,
                $grupoEstados,
                $concepto_motivo,
                $fechas,
                $hora_rango,
                $lugar_td,
                $sinretorno,
                $JefeInmediato,
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

$activarPapeleta = new tablaPapeleta();
$activarPapeleta->mostrartablaPapeleta();
