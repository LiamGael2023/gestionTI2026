<?php
session_start();
require_once __DIR__ . "/../controllers/PapeletasController.php";
require_once __DIR__ . "/../models/PapeletasModel.php";

class tablaPapeletaVigilantes
{
    public function mostrartablaPapeleta()
    {
        $draw   = isset($_POST["draw"])   ? intval($_POST["draw"]) : 1;
        $start  = isset($_POST["start"])  ? intval($_POST["start"]) : 0;
        $length = isset($_POST["length"]) ? intval($_POST["length"]) : 10;
        $search = $_POST['search']['value'] ?? null;

        // Filtros
        $id_establecimiento = $_POST["id_establecimiento"] ?? null;
        $filtroFecha        = $_POST["filtroFecha"] ?? null;
        $filtroCerrar        = $_POST["filtroCerrar"] ?? null;

        // ✅ Ejecutamos el controlador → modelo → SP
        $respuesta = ControladorPapeleta::ctrMostrarPapeletaVigilantes(
            $id_establecimiento,
            $start,
            $length,
            $search, // <-- agregamos el search aquí
            $filtroFecha,
            $filtroCerrar

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

        foreach ($papeletas as $key => $p) {

            // ✅ número correlativo real
            $numero = '<td><h6>' . $start + $key + 1 . '</h6></td>';

            // ✅ EVITAR ERROR: si no es array, se salta
            if (!is_array($p)) continue;

            $fotoArchivo = !empty($p["Trab_Fotocheck"])
                ? $p["Trab_Fotocheck"] . '.jpg'
                : '';

            $fotoArchivo = $p["Trab_Fotocheck"] . '.jpg';
            $fotoPath = '/gestionTI/public/fotos-trabajador/' . $fotoArchivo;
            $rutaSinFoto = '/gestionTI/public/fotos-trabajador/sinfoto.jpg';

            $foto = '<a href="' . $fotoPath . '" 
             class="avatar-lightbox" 
             data-caption="Foto de ' . htmlspecialchars($p["Trabajador_apellidos"]) . '">
                <img src="' . $fotoPath . '" 
                     class="avatar avatar-1 rounded"
                     style="width:40px; height:40px; object-fit:cover;"
                     onerror="this.onerror=null;this.src=\'' . $rutaSinFoto . '\';">
           </a>';

            // ✅ Nombres
            $nombres = '<td style="padding:4px 4px; max-width:30px,width:30px; text-align:center;"><h6>' . ($p["apellidos"]) . '<br>' . ($p["nombres"]) . '</h6></td>';

            $nombres_jefe = '<td style="padding:4px 4px; max-width:30px,width:30px; text-align:center;"><h6>' . ($p["jefe_apellidos"]) . '<br>' . ($p["jefe_nombres"]) . '</h6></td>';

            if ($p["salida_vehicular"] === 0) {

                $id_papeleta = '

             <a  href="#" 
                                class="btn btn-icon" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalQR" 
                                data-id="' . $p["id_papeleta"] . '">
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
            } else {
                $id_papeleta = '


            <a href="#" 

                class="btn btn-icon btn-vk " 
                data-bs-toggle="modal" 
                data-bs-target="#modalQR" 
                data-id="' . $p["id_papeleta"] . '">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                        viewBox="0 0 20 20" width="24px" height="24px" style="transform: scale(1.8); transform-origin: center; >
                        <g id="surface1">
                            <path style="stroke:none;fill-rule:nonzero;fill:rgb(0%,0%,0%);fill-opacity:1;" 
                                d="M 4.832031 9.339844 L 8.953125 9.339844 L 8.953125 5.222656 L 4.832031 5.222656 Z 
                                    M 5.863281 6.25 L 7.921875 6.25 L 7.921875 8.3125 L 5.863281 8.3125 Z 
                                    M 4.832031 14.492188 L 8.953125 14.492188 L 8.953125 10.371094 L 4.832031 10.371094 Z 
                                    M 5.863281 11.402344 L 7.921875 11.402344 L 7.921875 13.460938 L 5.863281 13.460938 Z 
                                    M 9.980469 5.222656 L 9.980469 9.339844 L 14.101562 9.339844 L 14.101562 5.222656 Z 
                                    M 13.070312 8.3125 L 11.011719 8.3125 L 11.011719 6.25 L 13.070312 6.25 Z 
                                    M 13.070312 8.3125"/>
                            <path style="stroke:none;fill-rule:evenodd;fill:rgb(0%,0%,0%);fill-opacity:1;" 
                                d="M 11.847656 8.59375 C 13.925781 8.59375 15.605469 10.273438 15.605469 12.347656 
                                    C 15.605469 14.425781 13.925781 16.105469 11.847656 16.105469 
                                    C 9.773438 16.105469 8.09375 14.425781 8.09375 12.347656 
                                    C 8.09375 10.273438 9.773438 8.59375 11.847656 8.59375 Z 
                                    M 8.921875 13.03125 C 9.191406 14.179688 10.109375 15.070312 11.269531 15.300781 
                                    C 11.199219 14.3125 10.875 13.722656 10.464844 13.394531 
                                    C 10.070312 13.082031 9.539062 12.960938 8.921875 13.027344 Z 
                                    M 13.234375 13.394531 C 12.824219 13.722656 12.5 14.3125 12.429688 15.300781 
                                    C 13.589844 15.070312 14.507812 14.179688 14.777344 13.027344 
                                    C 14.160156 12.960938 13.625 13.082031 13.234375 13.394531 Z 
                                    M 11.847656 9.34375 C 10.40625 9.34375 9.164062 10.371094 8.894531 11.789062 
                                    L 8.878906 11.882812 L 9.710938 11.71875 C 9.929688 11.675781 10.136719 11.558594 10.367188 11.402344 
                                    L 10.546875 11.277344 C 10.847656 11.066406 11.289062 10.847656 11.847656 10.847656 
                                    C 12.371094 10.847656 12.792969 11.035156 13.089844 11.234375 L 13.328125 11.402344 
                                    C 13.53125 11.539062 13.71875 11.644531 13.90625 11.699219 L 13.988281 11.71875 
                                    L 14.820312 11.882812 C 14.589844 10.421875 13.328125 9.34375 11.851562 9.34375 
                                    C 11.851562 9.34375 11.847656 9.34375 11.847656 9.34375 Z 
                                    M 11.847656 9.34375 Z 
                                    M 11.847656 9.34375"/>
                        </g>
                    </svg>
                </a>';
            }
            // ✅ Rango fechas
            $fechaIni = is_object($p["fecha_inicio"]) ? $p["fecha_inicio"]->format('d/m/y') : date('d/m/y', strtotime($p["fecha_inicio"]));
            $fechaFin = is_object($p["fecha_fin"]) ? $p["fecha_fin"]->format('d/m/y') : date('d/m/y', strtotime($p["fecha_fin"]));
            $fecha_rango = "<td><h6>$fechaIni -<br> $fechaFin</h6></td>";
            if ($p["sinretorno"] === 'NO') {
                $sinretorno = '<td><a class="btn btn-icon btn-red" >
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48"><path fill="#ffffff" fill-rule="evenodd" d="M8.14 45.956c3.271.266 8.462.544 15.864.544c7.401 0 12.593-.278 15.864-.544c3.288-.267 5.825-2.804 6.092-6.092c.266-3.271.544-8.463.544-15.864s-.278-12.593-.544-15.864c-.267-3.288-2.804-5.825-6.092-6.092c-3.271-.266-8.463-.544-15.864-.544s-12.593.278-15.864.544c-3.288.267-5.825 2.804-6.092 6.092c-.266 3.271-.544 8.463-.544 15.864s.278 12.593.544 15.864c.267 3.288 2.804 5.825 6.092 6.092m17.756-29.238c-1.414-.049-2.64-.922-2.696-2.335a12 12 0 0 1 0-.967c.057-1.414 1.283-2.284 2.697-2.336a131 131 0 0 1 6.108-.074a6.05 6.05 0 0 1 6 6.047l-.009 10.81c-.003 3.312-2.687 6.001-5.998 6.08c-4.872.117-10.272.328-13.704.473a63 63 0 0 1-.11 2.4c-.085 1.249-1.273 1.794-2.272 1.04a55 55 0 0 1-2.915-2.378c-1.713-1.487-2.693-2.602-3.246-3.348a1.844 1.844 0 0 1 0-2.247c.553-.745 1.534-1.861 3.246-3.348a55 55 0 0 1 2.915-2.38c1-.752 2.187-.207 2.271 1.041c.041.609.08 1.36.107 2.282c4.129.074 8.921.251 12.98.406a1 1 0 0 0 1.038-1v-9.133c0-.553-.448-1-1-.999c-.464.001-.882.004-1.27.006c-1.541.007-2.604.013-4.142-.04" clip-rule="evenodd"/></svg>            </a></td>';
            } else {
                $sinretorno = '<td><a class="btn btn-icon btn-green" >
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48"><path fill="#ffffff" fill-rule="evenodd" d="M8.14 45.956c3.271.266 8.462.544 15.864.544c7.401 0 12.593-.278 15.864-.544c3.288-.267 5.825-2.804 6.092-6.092c.266-3.271.544-8.463.544-15.864s-.278-12.593-.544-15.864c-.267-3.288-2.804-5.825-6.092-6.092c-3.271-.266-8.463-.544-15.864-.544s-12.593.278-15.864.544c-3.288.267-5.825 2.804-6.092 6.092c-.266 3.271-.544 8.463-.544 15.864s.278 12.593.544 15.864c.267 3.288 2.804 5.825 6.092 6.092m17.756-29.238c-1.414-.049-2.64-.922-2.696-2.335a12 12 0 0 1 0-.967c.057-1.414 1.283-2.284 2.697-2.336a131 131 0 0 1 6.108-.074a6.05 6.05 0 0 1 6 6.047l-.009 10.81c-.003 3.312-2.687 6.001-5.998 6.08c-4.872.117-10.272.328-13.704.473a63 63 0 0 1-.11 2.4c-.085 1.249-1.273 1.794-2.272 1.04a55 55 0 0 1-2.915-2.378c-1.713-1.487-2.693-2.602-3.246-3.348a1.844 1.844 0 0 1 0-2.247c.553-.745 1.534-1.861 3.246-3.348a55 55 0 0 1 2.915-2.38c1-.752 2.187-.207 2.271 1.041c.041.609.08 1.36.107 2.282c4.129.074 8.921.251 12.98.406a1 1 0 0 0 1.038-1v-9.133c0-.553-.448-1-1-.999c-.464.001-.882.004-1.27.006c-1.541.007-2.604.013-4.142-.04" clip-rule="evenodd"/></svg>            </a></td>';
            }

            $motivojunto = '<h6 style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'
                . '<span style="font-weight:bold; font-style:italic;">' . ($p["Id_Trabajador_Concepto_APP"]) . '</span>'
                . '<br>'
                . '<span title="' . ($p["Id_Trabajador_Motivo_APP"]) . '">'
                . ($p["Id_Trabajador_Motivo_APP"])
                . '</span>'
                . '</h6>';

            $horaIni = is_object($p["hora_salida"]) ? $p["hora_salida"]->format('H:i') : date('H:i', strtotime($p["hora_salida"]));
            $horaFin = is_object($p["hora_llegada"]) ? $p["hora_llegada"]->format('H:i') : date('H:i', strtotime($p["hora_llegada"]));

            $horaIniColor = ($horaIni !== "00:00") ? "chip-green" : "chip-white";
            $horaFinColor = ($horaFin !== "00:00") ? "chip-green" : "chip-white";

            $hora_rango = "
    <td>
        <h6 style='margin:0'>
            <span class='chip $horaIniColor'>$horaIni</span>
        </h6>
        <h6 style='margin:0'>
            <span class='chip $horaFinColor'>$horaFin</span>
        </h6>
    </td>
";



            // ✅ Sin retorno

            $Id_Trabajador_Lugar_APP = '<h6 style="max-width:60px ;; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" 
                                            title="' . ($p["Id_Trabajador_Lugar_APP"]) . '">'
                . ($p["Id_Trabajador_Lugar_APP"])
                . '</h6>';

            $disabledCerrar = ($p["cerrar"] == 1) ? 'disabled' : '';

            $acciones = '
    <td>
        <div class="btn-group" role="group">
            <!-- Botón que abre modal de Cerrar Papeleta -->
            <button type="button"
                class="btn btn-icon btn-azure btn-cerrar-papeleta"
                data-bs-toggle="modal"
                data-bs-target="#cerrarpapaletaModal"
                data-id="' . $p["id_papeleta"] . '"
                ' . $disabledCerrar . '>
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 2048 2048"><path fill="#ffffff" d="M1024 0q141 0 272 36t244 104t207 160t161 207t103 245t37 272q0 141-36 272t-104 244t-160 207t-207 161t-245 103t-272 37q-141 0-272-36t-244-104t-207-160t-161-207t-103-245t-37-272q0-141 36-272t104-244t160-207t207-161T752 37t272-37m603 685l-136-136l-659 659l-275-275l-136 136l411 411z"/></svg>
            </button>

            <!-- Botón No Salio -->
            <a class="btn btn-icon btn-red btn-nosalio" 
                data-id="' . $p["id_papeleta"] . '">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="#ffffff" d="M8 11a4 4 0 1 0 0-8a4 4 0 0 0 0 8m9 0a3 3 0 1 0 0-6a3 3 0 0 0 0 6M4.25 13A2.25 2.25 0 0 0 2 15.25v.25S2 20 8 20c1.39 0 2.458-.241 3.278-.613A6.5 6.5 0 0 1 11 17.5c0-1.657.62-3.169 1.64-4.317a2.2 2.2 0 0 0-.89-.183zM23 17.5a5.5 5.5 0 1 1-11 0a5.5 5.5 0 0 1 11 0m-9.5 0c0 .834.255 1.608.691 2.248l5.557-5.557A4 4 0 0 0 13.5 17.5m4 4a4 4 0 0 0 3.309-6.248l-5.557 5.557a4 4 0 0 0 2.248.691"/></svg>
            </a>
        </div>
    </td>';

            // Detectar si debe marcarse como peligro (fila roja)
            $claseFila = "";
            if ($p["no_autorizado"] == 1 || $p["no_autorizadoJP"] == 1) {
                $claseFila = "table-danger";
            }

            // ✅ Construcción final de fila
            $fila = array(
                "DT_RowClass" => $claseFila,   // ← ESTA LÍNEA AGREGA LA CLASE A TODA LA FILA
                $numero,
                $id_papeleta,
                $foto,
                $nombres,
                $motivojunto,
                $Id_Trabajador_Lugar_APP,
                $nombres_jefe,
                $fecha_rango,
                $hora_rango,
                $sinretorno,
                $acciones,
            );

            $datos[] = $fila;
        }

        // ✅ RESPUESTA FINAL JSON para DataTables serverSide
        $resultado = [
            "draw" => $draw,
            "recordsTotal" => $totalFiltrado,
            "recordsFiltered" => $totalFiltrado,
            "data" => $datos
        ];

        header('Content-Type: application/json');
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

$tabla = new tablaPapeletaVigilantes();
$tabla->mostrartablaPapeleta();
