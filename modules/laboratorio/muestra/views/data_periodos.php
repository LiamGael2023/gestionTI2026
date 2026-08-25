<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../../../config/db.php';
require_once '../models/ProyectoModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = Conexion::conectar();
    if ($conn === false) {
        throw new Exception('No se pudo conectar a la BD');
    }
    
    $model = new ProyectoModel($conn);
    
    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $filtro_control_calidad = isset($_POST['es_control_calidad']) ? intval($_POST['es_control_calidad']) : -1;
    $filtro_drene = isset($_POST['es_drene']) ? intval($_POST['es_drene']) : -1;

    // Obtener todos los proyectos (nota: el modelo obtenerTodos no tiene paginación)
    $proyectos = $model->obtenerTodos(true);

    // Filtrar por tipo cuando la tabla lo solicita
    if ($filtro_drene === 1) {
        $proyectos = array_values(array_filter($proyectos, function ($row) {
            return intval($row['Es_Drene'] ?? 0) === 1;
        }));
    } elseif ($filtro_control_calidad === 1) {
        $proyectos = array_values(array_filter($proyectos, function ($row) {
            return intval($row['Es_Control_Calidad'] ?? 0) === 1;
        }));
    } elseif ($filtro_control_calidad === 0) {
        // Monitoreo: excluir tanto calidad de agua como drenes
        $proyectos = array_values(array_filter($proyectos, function ($row) {
            return intval($row['Es_Control_Calidad'] ?? 0) === 0 && intval($row['Es_Drene'] ?? 0) === 0;
        }));
    }
    
    // Aplicar paginación en PHP
    $total = count($proyectos);
    $proyectos = array_slice($proyectos, $start, $length);

    $data = [];
    $contador = $start + 1;
    foreach ($proyectos as $row) {
        $id = isset($row['Id_Proyecto']) ? intval($row['Id_Proyecto']) : 0;
        $proyecto = isset($row['Nombre_Proyecto']) ? strval($row['Nombre_Proyecto']) : '-';
        $valle = isset($row['Valle']) ? strval($row['Valle']) : '-';
        $temporada = isset($row['Temporada']) ? strval($row['Temporada']) : '-';
        $fecha = isset($row['Fecha_Inicio']) ? (is_object($row['Fecha_Inicio']) ? $row['Fecha_Inicio']->format('d-m-Y') : strval($row['Fecha_Inicio'])) : '-';
        $estado = isset($row['Estado']) ? strval($row['Estado']) : 'Planificado';
        
        // Badge para estado
        $estado_badge = '<span class="badge ';
        if ($estado === 'En Progreso') {
            $estado_badge .= 'bg-info';
        } elseif ($estado === 'Terminado') {
            $estado_badge .= 'bg-success';
        } else {
            $estado_badge .= 'bg-warning'; // Planificado
        }
        $estado_badge .= '">' . htmlspecialchars($estado) . '</span>';
        
        // Botones según estado
        $esCalidadAgua = intval($row['Es_Control_Calidad'] ?? 0) === 1;
        $esDrene = intval($row['Es_Drene'] ?? 0) === 1;
        $fnExportar = $esCalidadAgua
            ? 'exportarCalidadAgua(' . $id . ')'
            : ($esDrene ? 'exportarDrenes(' . $id . ')' : 'exportarProyectoMonitoreo(' . $id . ')');  

        $accion = '';
        if ($estado === 'Planificado') {
            $accion = '<button type="button" class="btn btn-sm btn-success me-1" onclick="iniciarEjecucion(' . $id . ')" title="Iniciar Ejecución"><i class="ti ti-flash"></i> Iniciar</button> ';
        } else if ($estado === 'En Progreso') {
            $accion = '<button type="button" class="btn btn-sm btn-info me-1" onclick="abrirAnalisis(' . $id . ')" title="Registrar Análisis"><i class="ti ti-microscope"></i> Análisis</button> ';
            $accion .= '<button type="button" class="btn btn-sm btn-success me-1" onclick="' . $fnExportar . '" title="Exportar Excel"><i class="ti ti-file-spreadsheet"></i></button> ';
        } else if ($estado === 'Finalizado' || $estado === 'Terminado') {
            $accion = '<button type="button" class="btn btn-sm btn-secondary me-1" onclick="verResultados(' . $id . ')" title="Ver Resultados"><i class="ti ti-eye"></i> Resultados</button> ';
            $accion .= '<button type="button" class="btn btn-sm btn-success me-1" onclick="' . $fnExportar . '" title="Exportar Excel"><i class="ti ti-file-spreadsheet"></i></button> ';
        }
        
        $accion .= '<button type="button" class="btn btn-sm btn-primary me-1" onclick="editarProyecto(' . $id . ')" title="Editar"><i class="ti ti-edit"></i></button> ';
        $accion .= '<button type="button" class="btn btn-sm btn-danger" onclick="eliminarProyecto(' . $id . ')" title="Eliminar"><i class="ti ti-trash"></i></button>';
        
        $data[] = [$contador++, $proyecto, $valle, $temporada, $fecha, $estado_badge, $accion];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => intval($total),
        'recordsFiltered' => intval($total),
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
}
?>
