<?php
session_start();
ob_start();


require_once __DIR__ . "/../controllers/VehiculoController.php";
require_once __DIR__ . "/../models/VehiculoModel.php";

class tablaConductor
{

    public function mostrartablaConductor()
    {
        $item = 'Id_Trabajador';
        $id_trabajador = $_SESSION["id_Trabajador"];
        $fecha = isset($_POST['fecha']) && !empty($_POST['fecha']) ? $_POST['fecha'] : null;

        // Convertir d/m/Y → Y-m-d
        if ($fecha) {
            $fecha = DateTime::createFromFormat('d/m/Y', $fecha)->format('Y-m-d');
        }
        $colaborador = ControladorConductor::ctrMostrarConductor($fecha);

        if (!is_array($colaborador)) {
            $colaborador = [];
        }

        $datos = array();
        function limitarTexto($texto, $limite = 27)
        {
            if (mb_strlen($texto, 'UTF-8') > $limite) {
                return mb_substr($texto, 0, $limite, 'UTF-8') . '...';
            }
            return $texto;
        }
        foreach ($colaborador as $key => $colaboradores) {



            $numeroFila = '<td style="text-align: left !important;">' . ($key + 1) . '</td>'; // Aplicar estilo directamente
            $fotoReal = !empty($colaboradores["Trab_Fotocheck"])
                ? __DIR__ . '/../../../public/fotos-trabajador/' . $colaboradores["Trab_Fotocheck"] . '.jpg'
                : '';

            echo "<pre>Ruta que busca PHP: " . $fotoReal . "</pre>";


            if (!empty($fotoReal) && file_exists($fotoReal)) {

                $fotoPath = '/gestionti/public/fotos-trabajador/' . $colaboradores["Trab_Fotocheck"] . '.jpg';
            } else {

                $fotoPath = '/gestionti/public/fotos-trabajador/sinfoto.jpg';
            }
            $imagen = '<a href="' . $fotoPath . '" 
             class="avatar-lightbox " 
             data-caption="Foto de ' . htmlspecialchars($colaboradores["Trabajador_apellidos"]) . '">
                <img src="' . $fotoPath . '" 
                     class="avatar avatar-1 rounded" 
                     style="width:40px; height:40px; object-fit:cover;">
           </a>';
            $nombres = '<td>' . ($colaboradores["Trabajador_apellidos"]) . '<br>' . ($colaboradores["Trabajador_nombres"]) . '</td>';


            $gerenciaTexto = $colaboradores["gerencia"];
            $oficinaTexto  = $colaboradores["oficina"];

            $gerencia = '
<td>
  <div title="' . htmlspecialchars(($gerenciaTexto), ENT_QUOTES, 'UTF-8') . '" 
       style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
    ' . htmlspecialchars(($gerenciaTexto), ENT_QUOTES, 'UTF-8') . '
  </div>
</td>';

            $oficina = '
<td>
  <div title="' . htmlspecialchars(conversionUTF($oficinaTexto), ENT_QUOTES, 'UTF-8') . '" 
       style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
    ' . htmlspecialchars(conversionUTF($oficinaTexto), ENT_QUOTES, 'UTF-8') . '
  </div>
</td>';

            $placa = '<td>' . conversionUTF($colaboradores["Placa"], "---") . '</td>';


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
                $conceptoOriginal = htmlspecialchars($colaboradores["Id_Trabajador_Concepto_APP"], ENT_QUOTES, 'UTF-8');
                $conceptoCorto = limitarTexto($conceptoOriginal);

                $papeletaDelDia = '
    <a data-bs-toggle="modal" 
       data-bs-target="#modalQR" 
       data-id="' . $colaboradores["id_papeleta"] . '" 
       class="card card-link shadow-sm ' . $colorClass . ' text-dark mb-2" 
       style="text-decoration:none; border-width:1px;">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-column gap-1 small">
                <div title="' . $conceptoOriginal . '">
                    <strong>Concepto:</strong> ' . $conceptoCorto . '
                </div>
                <div><strong>Lugar:</strong> ' . htmlspecialchars($colaboradores["Id_Trabajador_Lugar_APP"]) . '</div>
                <div class="d-flex justify-content-between align-items-center">
                    <div><strong>Fecha:</strong> ' . $fecha_inicio . ' - ' . $fecha_fin . '</div>
                    ' . $retornoLabel . '
                </div>
            </div>
        </div>
    </a>';
            } else {
                $papeletaDelDia = '
                    <div class="card shadow-sm border-secondary text-center mb-2">
                        <div class="card-body py-2 px-3 text-muted small">
                            No tiene papeletas activas
                        </div>
                    </div>';
            }


            $acciones = '<td>
                <div class="btn-group" role="group">
                    <a data-bs-toggle="modal"
                        data-bs-target="#pdfModal"
                        data-pdf-url="repositorio/pdf/reportehistorialconductor.php?id=' . $colaboradores["id_trabajador"] . '" target="_blank" class="btn btn-icon btn-x">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                            <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                            <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" />
                            <path d="M17 18h2" />
                            <path d="M20 15h-3v6" />
                            <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
                        </svg>
                    </a>
                </div>
            </td>';

            $fila = array(
                $numeroFila,
                $imagen,
                $nombres,
                $gerencia,
                $oficina,
                $placa,
                $papeletaDelDia,
                $acciones,
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

$activarColaborador = new tablaConductor();
$activarColaborador->mostrartablaConductor();
