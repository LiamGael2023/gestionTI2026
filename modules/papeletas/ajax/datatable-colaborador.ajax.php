<?php
session_start();
ob_start();



require_once __DIR__ . "/../controllers/ColaboradorController.php";
require_once __DIR__ . "/../models/ColaboradorModel.php";

class tablaColaborador
{

    public function mostrartablaColaborador()
    {
        $item = 'Id_Trabajador';
        $id_trabajador = $_SESSION["id_Trabajador"];
        $fecha = isset($_POST['fecha']) && !empty($_POST['fecha']) ? $_POST['fecha'] : null;

        // Convertir d/m/Y → Y-m-d
        if ($fecha) {
            $fecha = DateTime::createFromFormat('d/m/Y', $fecha)->format('Y-m-d');
        }
        $colaborador = ControladorColaborador::ctrMostrarColaborador($id_trabajador, $fecha);

        if (!is_array($colaborador)) {
            $colaborador = [];
        }

        $datos = array();

        foreach ($colaborador as $key => $colaboradores) {



            $numeroFila = '<td style="text-align: left !important;">' . ($key + 1) . '</td>'; // Aplicar estilo directamente
            $fotoArchivo = !empty($colaboradores["Trab_Fotocheck"])
                ? $colaboradores["Trab_Fotocheck"] . '.jpg'
                : '';

            $fotoArchivo = $colaboradores["Trab_Fotocheck"] . '.jpg';
            $fotoPath = '/gestionTI/public/fotos-trabajador/' . $fotoArchivo;
            $rutaSinFoto = '/gestionTI/public/fotos-trabajador/sinfoto.jpg';

            $imagen = '<a href="' . $fotoPath . '" 
             class="avatar-lightbox" 
             data-caption="Foto de ' . htmlspecialchars($colaboradores["Trabajador_apellidos"]) . '">
                <img src="' . $fotoPath . '" 
                     class="avatar avatar-1 rounded"
                     style="width:40px; height:40px; object-fit:cover;"
                     onerror="this.onerror=null;this.src=\'' . $rutaSinFoto . '\';">
           </a>';

            $nombres = '<td>' . utf8_encode($colaboradores["Trabajador_apellidos"]) . '<br>' . utf8_encode($colaboradores["Trabajador_nombres"]) . '</td>';
            $gerencia = '<td>' . ($colaboradores["gerencia"]) . '</td>';
            $oficina = '<td>' . ($colaboradores["oficina"]) . '</td>';


            if ($colaboradores["tiene_papeleta"] == 1) {
                // Formatear fechas
                $fecha_inicio = $colaboradores["fecha_inicio"] instanceof DateTime
                    ? $colaboradores["fecha_inicio"]->format('d/m/Y')
                    : $colaboradores["fecha_inicio"];

                $fecha_fin = $colaboradores["fecha_fin"] instanceof DateTime
                    ? $colaboradores["fecha_fin"]->format('d/m/Y')
                    : $colaboradores["fecha_fin"];

                // Color del card según aprobación
                $estado = strtoupper(trim($colaboradores["estado_aprobacion"]));
                $colorClass = ($estado === "APROBADO")
                    ? "border-success bg-success-subtle"
                    : "border-warning bg-warning-subtle";

                // Etiqueta de retorno
                $sinretorno = trim(strtoupper($colaboradores["sinretorno"]));
                if ($sinretorno === "SI" || $sinretorno === "1" || $sinretorno === "NO RETORNA") {
                    $retornoLabel = '<span class="badge bg-danger-subtle text-danger fw-semibold">No retorna</span>';
                } else {
                    $retornoLabel = '<span class="badge bg-primary-subtle text-primary fw-semibold">Retorna</span>';
                }

                // Card con mejor distribución visual
                $papeletaDelDia = '
    <a data-bs-toggle="modal" 
       data-bs-target="#modalQR" 
       data-id="' . $colaboradores["id_papeleta"] . '"  class="card card-link shadow-sm ' . $colorClass . ' text-dark mb-2" 
       style="text-decoration:none; border-width:1px;">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-column gap-1 small">
                <div><strong>Concepto:</strong> ' . (htmlspecialchars($colaboradores["Id_Trabajador_Concepto_APP"])) . '</div>
                <div><strong>Lugar:</strong> ' . (htmlspecialchars($colaboradores["Id_Trabajador_Lugar_APP"])) . '</div>
                <div class="d-flex justify-content-between align-items-center">
                    <div><strong>Fecha:</strong> ' . $fecha_inicio . ' - ' . $fecha_fin . '</div>
                    ' . $retornoLabel . '
                </div>
            </div>
        </div>
    </a>';
            } else {
                // Card si no tiene papeleta
                $papeletaDelDia = '
    <div class="card shadow-sm border-secondary text-center mb-2">
        <div class="card-body py-2 px-3 text-muted small">
            No tiene papeletas activas
        </div>
    </div>';
            }





            $fila = array(
                $numeroFila,
                $imagen,
                $nombres,
                $gerencia,
                $oficina,
                $papeletaDelDia,
            );

            $datos[] = $fila;
        }

        header('Content-Type: application/json');
        echo json_encode(array("data" => $datos));

        // Captura la salida y la imprime
        $output = ob_get_clean();
        echo $output;
    }

    private function formatDate($date)
    {
        if ($date instanceof DateTime) {
            return $date->format('d/m/Y');
        } else {
            return date('d/m/Y', strtotime($date));
        }
    }
}

$activarColaborador = new tablaColaborador();
$activarColaborador->mostrartablaColaborador();
