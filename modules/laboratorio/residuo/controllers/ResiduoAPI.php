<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';
require_once $base_path . '/core/Auth.php';
require_once __DIR__ . '/../models/ResiduoModel.php';
require_once __DIR__ . '/../models/NormativaSST.php';

Auth::check();

header('Content-Type: application/json');

$conn = Conexion::conectar();
$action = $_GET['action'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// ── Control de permisos (roles de laboratorio) ─────────────────────
require_once __DIR__ . '/../models/LaboratorioModel.php';
$labAuth        = new LaboratorioModel($conn);
$urlSubmodulo   = '?module=laboratorio&action=residuo';
$permActionMap  = [
    'crear_residuo'               => 'crear',
    'editar_residuo'              => 'editar',
    'eliminar_residuo'            => 'eliminar',
    'reactivar_residuo'           => 'editar',
    'crear_normativa'             => 'crear',
    'editar_normativa'            => 'editar',
    'eliminar_normativa'          => 'eliminar',
    'reactivar_normativa'         => 'editar',
    'crear_informe'               => 'crear',
    'editar_informe'              => 'editar',
    'eliminar_informe'            => 'eliminar',
    'reactivar_informe'           => 'editar',
    'agregar_ingreso_manual'      => 'crear',
    'editar_ingreso_manual'       => 'editar',
    'simular_cierre_diario'       => 'editar',
];
if (isset($permActionMap[$action])) {
    $labAuth->denegarSiSinPermiso($usuario_id, $urlSubmodulo, $permActionMap[$action]);
}

try {
    switch ($action) {
        // ==================== RESIDUOS ====================
        case 'crear_residuo':
            crearResiduo($conn, $usuario_id);
            break;
        
        case 'editar_residuo':
            editarResiduo($conn, $usuario_id);
            break;
        
        case 'eliminar_residuo':
            eliminarResiduo($conn);
            break;
        
        case 'reactivar_residuo':
            reactivarResiduo($conn);
            break;
        
        case 'obtener_residuo':
            obtenerResiduo($conn);
            break;
        
        case 'obtener_servicios_residuo':
            obtenerServiciosResiduo($conn);
            break;
        
        // ==================== NORMATIVAS ====================
        case 'crear_normativa':
            crearNormativa($conn, $usuario_id);
            break;
        
        case 'editar_normativa':
            editarNormativa($conn, $usuario_id);
            break;
        
        case 'eliminar_normativa':
            eliminarNormativa($conn);
            break;
        
        case 'reactivar_normativa':
            reactivarNormativa($conn);
            break;
        
        case 'obtener_normativa':
            obtenerNormativa($conn);
            break;
        
        case 'obtener_normativas':
            obtenerNormativas($conn);
            break;
        
        // ==================== INFORME ====================
        case 'crear_informe':
            crearInforme($conn, $usuario_id);
            break;

        case 'obtener_informe':
            obtenerInforme($conn);
            break;

        case 'editar_informe':
            editarInforme($conn, $usuario_id);
            break;

        case 'listar_ingresos_residuo':
            listarIngresosResiduo($conn);
            break;

        case 'agregar_ingreso_manual':
            agregarIngresoManual($conn, $usuario_id);
            break;

        case 'editar_ingreso_manual':
            editarIngresoManual($conn, $usuario_id);
            break;
        
        // ==================== SIMULACIÓN CIERRE ====================
        case 'simular_cierre_diario':
            simularCierreDiario($conn, $usuario_id);
            break;
        
        // ==================== ELIMINAR INFORME ====================
        case 'eliminar_informe':
            eliminarInforme($conn);
            break;

        case 'reactivar_informe':
            reactivarInforme($conn);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action no válida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ==================== FUNCIONES RESIDUO ====================

function crearResiduo($conn, $usuario_id) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['Nombre_Item'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    $modelo = new ResiduoModel($conn);
    $datos = [
        'Codigo_Item' => $data['Codigo_Item'] ?? null,
        'Nombre_Item' => $data['Nombre_Item'],
        'Tipo_Principal' => $data['Tipo_Principal'],
        'Subcategoria' => $data['Subcategoria'],
        'Unidad_Referencia' => $data['Unidad_Referencia'] ?? null,
        'Usuario_Creacion' => $usuario_id
    ];
    
    try {
        $id_residuo = $modelo->crearResiduo($datos);
        
        // Guardar servicios asociados
        if (!empty($data['Servicios']) && is_array($data['Servicios'])) {
            foreach ($data['Servicios'] as $servicio) {
                if (isset($servicio['id']) && isset($servicio['cantidad'])) {
                    $modelo->guardarDefinicion([
                        'Id_Servicio' => $servicio['id'],
                        'Id_Residuo_Cat' => $id_residuo,
                        'Cantidad_Estimada_Por_Muestra' => floatval($servicio['cantidad'])
                    ]);
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Residuo creado exitosamente']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear residuo: ' . $e->getMessage()]);
    }
}

function editarResiduo($conn, $usuario_id) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validaciones básicas
    if (!$data || !isset($data['Id_Residuo_Cat'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de residuo requerido']);
        return;
    }
    
    if (empty($data['Nombre_Item'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre del residuo es requerido']);
        return;
    }
    
    if (empty($data['Tipo_Principal'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El tipo de residuo es requerido']);
        return;
    }
    
    if (empty($data['Subcategoria'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La subcategoría es requerida']);
        return;
    }
    
    $id_residuo = intval($data['Id_Residuo_Cat']);
    
    try {
        // Verificar si el código ya existe en otro residuo
        if (!empty($data['Codigo_Item'])) {
            $sql_check_code = "SELECT Id_Residuo_Cat FROM laboratorio.Residuo_Catalogo 
                              WHERE Codigo_Item = ? AND Id_Residuo_Cat != ?";
            $stmt_check = sqlsrv_query($conn, $sql_check_code, [$data['Codigo_Item'], $id_residuo]);
            
            if ($stmt_check !== false) {
                $exists = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                if ($exists) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'El código de ítem "' . htmlspecialchars($data['Codigo_Item']) . '" ya existe en otro residuo']);
                    return;
                }
            }
        }
        
        // Actualizar datos básicos
        $sql_update = "UPDATE laboratorio.Residuo_Catalogo 
                       SET Nombre_Item = ?, 
                           Tipo_Principal = ?, 
                           Subcategoria = ?, 
                           Unidad_Referencia = ?, 
                           Codigo_Item = ?
                       WHERE Id_Residuo_Cat = ?";
        
        $stmt_update = sqlsrv_query($conn, $sql_update, [
            $data['Nombre_Item'],
            $data['Tipo_Principal'],
            $data['Subcategoria'],
            $data['Unidad_Referencia'] ?? null,
            $data['Codigo_Item'] ?? null,
            $id_residuo
        ]);
        
        if ($stmt_update === false) {
            $errors = sqlsrv_errors();
            $error_msg = $errors[0]['message'] ?? 'Error desconocido';
            
            // Detectar tipo de error
            if (strpos($error_msg, 'UNIQUE KEY') !== false || strpos($error_msg, 'duplicate') !== false) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Código de ítem duplicado. Este código ya está en uso por otro residuo']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $error_msg]);
            }
            return;
        }
        
        // Actualizar servicios
        if (!empty($data['Servicios']) && is_array($data['Servicios'])) {
            // Marcar todos como inactivos
            $sqlDelete = "UPDATE laboratorio.Servicio_Residuo_Def 
                         SET Activo = 0 
                         WHERE Id_Residuo_Cat = ?";
            $stmtDelete = sqlsrv_query($conn, $sqlDelete, [$id_residuo]);
            if ($stmtDelete === false) {
                $errors = sqlsrv_errors();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al desactivar servicios: ' . ($errors[0]['message'] ?? 'Error desconocido')]);
                return;
            }
            
            // Guardar nuevos
            foreach ($data['Servicios'] as $servicio) {
                if (isset($servicio['id']) && isset($servicio['cantidad'])) {
                    $id_srv = intval($servicio['id']);
                    $cant = floatval($servicio['cantidad']);
                    
                    // Validar que Id_Servicio existe
                    if ($id_srv <= 0) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'ID de servicio inválido']);
                        return;
                    }
                    
                    $sqlCheck = "SELECT COUNT(*) as cnt FROM laboratorio.Servicio_Residuo_Def 
                               WHERE Id_Residuo_Cat = ? AND Id_Servicio = ?";
                    $stmtCheck = sqlsrv_query($conn, $sqlCheck, [$id_residuo, $id_srv]);
                    
                    if ($stmtCheck === false) {
                        $errors = sqlsrv_errors();
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Error en consulta: ' . ($errors[0]['message'] ?? 'Error desconocido')]);
                        return;
                    }
                    
                    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
                    $exists = ($rowCheck['cnt'] > 0);
                    
                    if ($exists) {
                        $sqlUpdate = "UPDATE laboratorio.Servicio_Residuo_Def 
                                    SET Cantidad_Estimada_Por_Muestra = ?, Activo = 1
                                    WHERE Id_Residuo_Cat = ? AND Id_Servicio = ?";
                        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, [$cant, $id_residuo, $id_srv]);
                        if ($stmtUpdate === false) {
                            $errors = sqlsrv_errors();
                            http_response_code(500);
                            echo json_encode(['success' => false, 'message' => 'Error al actualizar servicio: ' . ($errors[0]['message'] ?? 'Error desconocido')]);
                            return;
                        }
                    } else {
                        $sqlInsert = "INSERT INTO laboratorio.Servicio_Residuo_Def 
                                    (Id_Servicio, Id_Residuo_Cat, Cantidad_Estimada_Por_Muestra, Usuario_Creacion, Activo)
                                    VALUES (?, ?, ?, ?, 1)";
                        $stmtInsert = sqlsrv_query($conn, $sqlInsert, [$id_srv, $id_residuo, $cant, $usuario_id]);
                        if ($stmtInsert === false) {
                            $errors = sqlsrv_errors();
                            http_response_code(500);
                            echo json_encode(['success' => false, 'message' => 'Error al insertar servicio: ' . ($errors[0]['message'] ?? 'Error desconocido')]);
                            return;
                        }
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Residuo actualizado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Excepción: ' . $e->getMessage()]);
    }
}

function eliminarResiduo($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['Id_Residuo_Cat'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de residuo requerido']);
        return;
    }
    
    $id_residuo = intval($data['Id_Residuo_Cat']);
    
    // Verificar si el residuo está ligado a algún informe
    $sql_check = "SELECT COUNT(*) as total FROM laboratorio.Detalle_Residuos_Log 
                  WHERE Id_Residuo_Cat = ? AND Activo = 1";
    $stmt_check = sqlsrv_query($conn, $sql_check, [$id_residuo]);
    
    if ($stmt_check === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al validar residuo']);
        return;
    }
    
    $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
    $total = intval($row['total'] ?? 0);
    
    if ($total > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Este residuo está ligado a ' . $total . ' informe(s) y no puede ser eliminado']);
        return;
    }
    
    // Si no está ligado, proceder con la eliminación (soft delete)
    try {
        $sql_delete = "UPDATE laboratorio.Residuo_Catalogo 
                       SET Activo = 0 
                       WHERE Id_Residuo_Cat = ?";
        $stmt_delete = sqlsrv_query($conn, $sql_delete, [$id_residuo]);
        
        if ($stmt_delete === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error SQL: ' . ($errors[0]['message'] ?? 'Error en la actualización'));
        }
        
        echo json_encode(['success' => true, 'message' => 'Residuo eliminado exitosamente']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerResiduo($conn) {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    $modelo = new ResiduoModel($conn);
    $residuo = $modelo->obtenerResiduoPorId($id);
    
    if ($residuo) {
        echo json_encode(['success' => true, 'data' => $residuo]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Residuo no encontrado']);
    }
}

function obtenerServiciosResiduo($conn) {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    try {
        // Obtener servicios ligados a este residuo
        $sql = "SELECT 
                srd.Id_Servicio,
                s.Nombre,
                srd.Cantidad_Estimada_Por_Muestra
                FROM laboratorio.Servicio_Residuo_Def srd
                JOIN laboratorio.Servicio_Tecnico s ON srd.Id_Servicio = s.Id_Servicio
                WHERE srd.Id_Residuo_Cat = ? AND srd.Activo = 1";
        
        $stmt = sqlsrv_query($conn, $sql, [$id]);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error en consulta: ' . ($errors[0]['message'] ?? 'Error desconocido')]);
            return;
        }
        
        $servicios = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $servicios[] = [
                'id' => intval($row['Id_Servicio']),
                'nombre' => $row['Nombre'],
                'cantidad' => floatval($row['Cantidad_Estimada_Por_Muestra'] ?? 0)
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $servicios]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Excepción: ' . $e->getMessage()]);
    }
}

// ==================== FUNCIONES NORMATIVA ====================

function crearNormativa($conn, $usuario_id) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['Nombre_Ley'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    $modelo = new NormativaSST($conn);
    $datos = [
        'Nombre_Ley' => $data['Nombre_Ley'],
        'Descripcion' => $data['Descripcion'] ?? '',
        'Usuario_Creacion' => intval($data['Usuario_Creacion'] ?? $usuario_id)
    ];
    
    try {
        $modelo->crearNormativa($datos);
        echo json_encode(['success' => true, 'message' => 'Normativa creada exitosamente']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear normativa: ' . $e->getMessage()]);
    }
}

function editarNormativa($conn, $usuario_id) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['Id_Normativa_SST'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de normativa requerido']);
        return;
    }
    
    $modelo = new NormativaSST($conn);
    $datos = [
        'Nombre_Ley' => $data['Nombre_Ley'],
        'Descripcion' => $data['Descripcion']
    ];
    
    if ($modelo->actualizarNormativa($data['Id_Normativa_SST'], $datos)) {
        echo json_encode(['success' => true, 'message' => 'Normativa actualizada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar normativa']);
    }
}

function eliminarNormativa($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['Id_Normativa_SST'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de normativa requerido']);
        return;
    }
    
    $id_normativa = intval($data['Id_Normativa_SST']);
    
    // Verificar si la normativa está ligada a algún informe
    $sql_check = "SELECT COUNT(*) as total FROM laboratorio.Registro_Residuos_Log 
                  WHERE Id_Normativa_Aplicable = ? AND Activo = 1";
    $stmt_check = sqlsrv_query($conn, $sql_check, [$id_normativa]);
    
    if ($stmt_check === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al validar normativa']);
        return;
    }
    
    $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
    $total = intval($row['total'] ?? 0);
    
    if ($total > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Esta normativa está ligada a ' . $total . ' informe(s) y no puede ser eliminada']);
        return;
    }
    
    // Si no está ligada, proceder con la eliminación (soft delete)
    try {
        $sql_delete = "UPDATE laboratorio.Normativa_SST 
                       SET Activo = 0 
                       WHERE Id_Normativa_SST = ?";
        $stmt_delete = sqlsrv_query($conn, $sql_delete, [$id_normativa]);
        
        if ($stmt_delete === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error SQL: ' . ($errors[0]['message'] ?? 'Error en la actualización'));
        }
        
        echo json_encode(['success' => true, 'message' => 'Normativa eliminada exitosamente']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerNormativa($conn) {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    $modelo = new NormativaSST($conn);
    $normativa = $modelo->obtenerPorId($id);
    
    if ($normativa) {
        echo json_encode(['success' => true, 'data' => $normativa]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Normativa no encontrada']);
    }
}

function obtenerNormativas($conn) {
    $modelo = new NormativaSST($conn);
    $normativas = $modelo->obtenerTodos();
    
    if ($normativas) {
        echo json_encode(['success' => true, 'data' => $normativas]);
    } else {
        echo json_encode(['success' => true, 'data' => []]);
    }
}

// ==================== FUNCIONES INFORME ====================

function crearInforme($conn, $usuario_id) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['Mes']) || !isset($data['Anio'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    $idsNormativas = [];
    if (!empty($data['Ids_Normativas']) && is_array($data['Ids_Normativas'])) {
        foreach ($data['Ids_Normativas'] as $idN) {
            $idNorm = intval($idN);
            if ($idNorm > 0) {
                $idsNormativas[] = $idNorm;
            }
        }
    }

    if (empty($idsNormativas) && !empty($data['Id_Normativa_Aplicable'])) {
        $idsNormativas[] = intval($data['Id_Normativa_Aplicable']);
    }

    $modelo = new ResiduoModel($conn);
    $datos = [
        'Mes' => intval($data['Mes']),
        'Anio' => intval($data['Anio']),
        'Ubicacion' => $data['Ubicacion'] ?? '',
        'Codigo_SST' => $data['Codigo_SST'] ?? 'SST-16',
        'Id_Responsable' => intval($data['Id_Responsable'] ?? $usuario_id),
        'Observacion' => $data['Observacion'] ?? null,
        'Ids_Normativas' => $idsNormativas,
        'Usuario_Creacion' => intval($usuario_id)
    ];
    
    $idRegistro = $modelo->crearRegistroResiduo($datos);
    if ($idRegistro) {
        echo json_encode(['success' => true, 'message' => 'Informe guardado exitosamente', 'id_registro' => intval($idRegistro)]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear informe']);
    }
}

function obtenerInforme($conn) {
    $idRegistro = intval($_GET['id'] ?? 0);
    if ($idRegistro <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de informe inválido']);
        return;
    }

    $modelo = new ResiduoModel($conn);
    $informe = $modelo->obtenerInformePorId($idRegistro);
    if (!$informe) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Informe no encontrado']);
        return;
    }

    echo json_encode(['success' => true, 'data' => $informe]);
}

function editarInforme($conn, $usuario_id) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['Id_Registro_Res'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de informe requerido']);
        return;
    }

    $idsNormativas = [];
    if (!empty($data['Ids_Normativas']) && is_array($data['Ids_Normativas'])) {
        foreach ($data['Ids_Normativas'] as $idN) {
            $idNorm = intval($idN);
            if ($idNorm > 0) {
                $idsNormativas[] = $idNorm;
            }
        }
    }

    if (empty($idsNormativas)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Seleccione al menos una normativa']);
        return;
    }

    $modelo = new ResiduoModel($conn);
    $ok = $modelo->actualizarInforme(intval($data['Id_Registro_Res']), [
        'Mes' => intval($data['Mes'] ?? 0),
        'Anio' => intval($data['Anio'] ?? 0),
        'Ubicacion' => $data['Ubicacion'] ?? '',
        'Codigo_SST' => $data['Codigo_SST'] ?? 'SST-16',
        'Id_Responsable' => intval($data['Id_Responsable'] ?? $usuario_id),
        'Observacion' => $data['Observacion'] ?? null,
        'Ids_Normativas' => $idsNormativas,
        'Usuario_Creacion' => intval($usuario_id)
    ]);

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Informe actualizado exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el informe']);
    }
}

function listarIngresosResiduo($conn) {
    $idRegistro = intval($_GET['id_registro'] ?? 0);
    $fechaDiaRaw = trim((string)($_GET['fecha_dia'] ?? ''));
    $idResiduo = intval($_GET['id_residuo_cat'] ?? 0);

    if ($idRegistro <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de informe inválido']);
        return;
    }

    if ($fechaDiaRaw === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Fecha requerida']);
        return;
    }

    $fechaDia = null;
    $fechaObj = DateTime::createFromFormat('Y-m-d', $fechaDiaRaw);
    if ($fechaObj instanceof DateTime) {
        $fechaDia = $fechaObj->format('Y-m-d');
    } else {
        $fechaObjAlt = DateTime::createFromFormat('d-m-Y', $fechaDiaRaw);
        if ($fechaObjAlt instanceof DateTime) {
            $fechaDia = $fechaObjAlt->format('Y-m-d');
        }
    }

    if ($fechaDia === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
        return;
    }

    $sql = "SELECT drl.Id_Detalle_Res,
                   drl.Id_Residuo_Cat,
                   drl.Fecha_Dia,
                   drl.Peso_Valor,
                   drl.Fecha_Creacion,
                   drl.Usuario_Creacion,
                   rc.Codigo_Item,
                   rc.Nombre_Item,
                   rc.Unidad_Referencia,
                   CONCAT(ISNULL(u.nombres, ''),
                          CASE WHEN ISNULL(u.apellidos, '') = '' THEN '' ELSE ' ' + u.apellidos END) AS Usuario_Nombre
            FROM laboratorio.Detalle_Residuos_Log drl
            INNER JOIN laboratorio.Residuo_Catalogo rc ON rc.Id_Residuo_Cat = drl.Id_Residuo_Cat
            LEFT JOIN comun.Usuarios u ON u.id_usuario = drl.Usuario_Creacion
            WHERE drl.Id_Registro_Res = ?
              AND drl.Activo = 1
              AND CAST(drl.Fecha_Dia AS DATE) = ?";

    $params = [$idRegistro, $fechaDia];

    if ($idResiduo > 0) {
        $sql .= " AND drl.Id_Residuo_Cat = ?";
        $params[] = $idResiduo;
    }

    $sql .= " ORDER BY drl.Fecha_Creacion ASC, drl.Id_Detalle_Res ASC";

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        throw new Exception('Error al listar ingresos de residuos: ' . print_r(sqlsrv_errors(), true));
    }

    $ingresos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $fechaCreacion = '';
        if (!empty($row['Fecha_Creacion']) && $row['Fecha_Creacion'] instanceof DateTime) {
            $fechaCreacion = $row['Fecha_Creacion']->format('Y-m-d H:i:s');
        }

        $ingresos[] = [
            'id_detalle' => intval($row['Id_Detalle_Res'] ?? 0),
            'id_residuo_cat' => intval($row['Id_Residuo_Cat'] ?? 0),
            'codigo_item' => trim((string)($row['Codigo_Item'] ?? '')),
            'nombre_item' => trim((string)($row['Nombre_Item'] ?? '')),
            'unidad' => trim((string)($row['Unidad_Referencia'] ?? '')),
            'fecha_dia' => $fechaDia,
            'peso_valor' => round(floatval($row['Peso_Valor'] ?? 0), 4),
            'usuario_creacion' => intval($row['Usuario_Creacion'] ?? 0),
            'usuario_nombre' => trim((string)($row['Usuario_Nombre'] ?? '')),
            'fecha_creacion' => $fechaCreacion
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $ingresos
    ]);
}

function agregarIngresoManual($conn, $usuario_id) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos requeridos']);
        return;
    }

    $idRegistro = intval($data['Id_Registro_Res'] ?? 0);
    $idResiduo = intval($data['Id_Residuo_Cat'] ?? 0);
    $fechaDiaRaw = trim((string)($data['Fecha_Dia'] ?? ''));
    $pesoValor = floatval($data['Peso_Valor'] ?? 0);

    if ($idRegistro <= 0 || $idResiduo <= 0 || $fechaDiaRaw === '' || $pesoValor <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Complete los datos requeridos del ingreso manual']);
        return;
    }

    $fechaObj = DateTime::createFromFormat('Y-m-d', $fechaDiaRaw);
    if (!($fechaObj instanceof DateTime)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Fecha inválida']);
        return;
    }

    $fechaDia = $fechaObj->format('Y-m-d');

    $stmtRegistro = sqlsrv_query(
        $conn,
        "SELECT COUNT(*) AS total FROM laboratorio.Registro_Residuos_Log WHERE Id_Registro_Res = ? AND Activo = 1",
        [$idRegistro]
    );
    if ($stmtRegistro === false) {
        throw new Exception('Error al validar informe: ' . print_r(sqlsrv_errors(), true));
    }
    $rowRegistro = sqlsrv_fetch_array($stmtRegistro, SQLSRV_FETCH_ASSOC);
    if (intval($rowRegistro['total'] ?? 0) <= 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Informe de residuos no encontrado']);
        return;
    }

    $stmtResiduo = sqlsrv_query(
        $conn,
        "SELECT COUNT(*) AS total FROM laboratorio.Residuo_Catalogo WHERE Id_Residuo_Cat = ? AND Activo = 1",
        [$idResiduo]
    );
    if ($stmtResiduo === false) {
        throw new Exception('Error al validar residuo: ' . print_r(sqlsrv_errors(), true));
    }
    $rowResiduo = sqlsrv_fetch_array($stmtResiduo, SQLSRV_FETCH_ASSOC);
    if (intval($rowResiduo['total'] ?? 0) <= 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Residuo no encontrado o inactivo']);
        return;
    }

    $modelo = new ResiduoModel($conn);
    $ok = $modelo->agregarDetalleResiduo([
        'Id_Registro_Res' => $idRegistro,
        'Id_Residuo_Cat' => $idResiduo,
        'Fecha_Dia' => $fechaDia,
        'Peso_Valor' => $pesoValor,
        'Usuario_Creacion' => intval($usuario_id)
    ]);

    if ($ok === false) {
        throw new Exception('No se pudo registrar el ingreso manual de residuo');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Ingreso manual de residuo registrado correctamente'
    ]);
}

function editarIngresoManual($conn, $usuario_id) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos requeridos']);
        return;
    }

    $idDetalle = intval($data['Id_Detalle_Res'] ?? 0);
    $idResiduo = intval($data['Id_Residuo_Cat'] ?? 0);
    $fechaDiaRaw = trim((string)($data['Fecha_Dia'] ?? ''));
    $pesoValor = floatval($data['Peso_Valor'] ?? 0);

    if ($idDetalle <= 0 || $idResiduo <= 0 || $fechaDiaRaw === '' || $pesoValor <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Complete los datos requeridos para editar el ingreso']);
        return;
    }

    $fechaObj = DateTime::createFromFormat('Y-m-d', $fechaDiaRaw);
    if (!($fechaObj instanceof DateTime)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Fecha inválida']);
        return;
    }
    $fechaDia = $fechaObj->format('Y-m-d');

    $stmtDetalle = sqlsrv_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM laboratorio.Detalle_Residuos_Log
         WHERE Id_Detalle_Res = ? AND Activo = 1",
        [$idDetalle]
    );
    if ($stmtDetalle === false) {
        throw new Exception('Error al validar detalle de residuo: ' . print_r(sqlsrv_errors(), true));
    }
    $rowDetalle = sqlsrv_fetch_array($stmtDetalle, SQLSRV_FETCH_ASSOC);
    if (intval($rowDetalle['total'] ?? 0) <= 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Detalle de residuo no encontrado']);
        return;
    }

    $stmtResiduo = sqlsrv_query(
        $conn,
        "SELECT COUNT(*) AS total FROM laboratorio.Residuo_Catalogo WHERE Id_Residuo_Cat = ? AND Activo = 1",
        [$idResiduo]
    );
    if ($stmtResiduo === false) {
        throw new Exception('Error al validar residuo: ' . print_r(sqlsrv_errors(), true));
    }
    $rowResiduo = sqlsrv_fetch_array($stmtResiduo, SQLSRV_FETCH_ASSOC);
    if (intval($rowResiduo['total'] ?? 0) <= 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Residuo no encontrado o inactivo']);
        return;
    }

    $sql = "UPDATE laboratorio.Detalle_Residuos_Log
            SET Id_Residuo_Cat = ?,
                Fecha_Dia = ?,
                Peso_Valor = ?,
                Fecha_Modificacion = GETDATE(),
                Usuario_Creacion = ?
            WHERE Id_Detalle_Res = ? AND Activo = 1";
    $stmt = sqlsrv_query($conn, $sql, [$idResiduo, $fechaDia, $pesoValor, intval($usuario_id), $idDetalle]);
    if ($stmt === false) {
        throw new Exception('Error al editar ingreso manual de residuo: ' . print_r(sqlsrv_errors(), true));
    }

    echo json_encode([
        'success' => true,
        'message' => 'Ingreso de residuo actualizado correctamente'
    ]);
}

// ==================== SIMULACIÓN CIERRE DIARIO ====================

function simularCierreDiario($conn, $usuario_id) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['Id_Solicitud_Analisis'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de solicitud requerido']);
        return;
    }
    
    try {
        $id_solicitud = intval($data['Id_Solicitud_Analisis']);
        
        // PASO 1: Obtener información de la solicitud
        $sqlObtener = "SELECT Id_Solicitud_Analisis, Id_Servicio, Id_Analista, Estado 
                       FROM laboratorio.Solicitud_Analisis 
                       WHERE Id_Solicitud_Analisis = ? AND Activo = 1";
        
        $stmtObtener = sqlsrv_query($conn, $sqlObtener, [$id_solicitud]);
        if ($stmtObtener === false) {
            throw new Exception('Error al obtener solicitud: ' . print_r(sqlsrv_errors(), true));
        }
        
        $solicitud = sqlsrv_fetch_array($stmtObtener, SQLSRV_FETCH_ASSOC);
        if (!$solicitud) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            return;
        }
        
        // PASO 2: Cambiar estado a 'Finalizado' (esto dispara el TRIGGER)
        $sqlActualizar = "UPDATE laboratorio.Solicitud_Analisis 
                          SET Estado = 'Finalizado', 
                              Fecha_Modificacion = GETDATE() 
                          WHERE Id_Solicitud_Analisis = ?";
        
        $stmtActualizar = sqlsrv_query($conn, $sqlActualizar, [$id_solicitud]);
        if ($stmtActualizar === false) {
            throw new Exception('Error al actualizar solicitud: ' . print_r(sqlsrv_errors(), true));
        }
        
        // PASO 3: Verificar que se insertó en Detalle_Residuos_Log
        $sqlVerificar = "SELECT COUNT(*) as total 
                         FROM laboratorio.Detalle_Residuos_Log 
                         WHERE Fecha_Dia = CAST(GETDATE() AS DATE) AND Activo = 1";
        
        $stmtVerificar = sqlsrv_query($conn, $sqlVerificar);
        $resultado = sqlsrv_fetch_array($stmtVerificar, SQLSRV_FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cierre simulado exitosamente. TRIGGER ejecutado.',
            'datos' => [
                'solicitud_id' => $solicitud['Id_Solicitud_Analisis'],
                'servicio_id' => $solicitud['Id_Servicio'],
                'estado_nuevo' => 'Finalizado',
                'residuos_registrados_hoy' => $resultado['total']
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==================== ELIMINAR INFORME ====================

function eliminarInforme($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['Id_Registro_Res'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de informe requerido']);
        return;
    }
    
    try {
        $id_registro = intval($data['Id_Registro_Res']);
        
        // Actualizar Activo = 0 (soft delete)
        $sql = "UPDATE laboratorio.Registro_Residuos_Log 
                SET Activo = 0, 
                    Fecha_Modificacion = GETDATE() 
                WHERE Id_Registro_Res = ?";
        
        $stmt = sqlsrv_query($conn, $sql, [$id_registro]);
        if ($stmt === false) {
            throw new Exception('Error al eliminar informe: ' . print_r(sqlsrv_errors(), true));
        }
        
        echo json_encode(['success' => true, 'message' => 'Informe eliminado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function reactivarInforme($conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data['Id_Registro_Res'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de informe requerido']);
        return;
    }

    try {
        $id_registro = intval($data['Id_Registro_Res']);

        $sql = "UPDATE laboratorio.Registro_Residuos_Log
                SET Activo = 1,
                    Fecha_Modificacion = GETDATE()
                WHERE Id_Registro_Res = ?";

        $stmt = sqlsrv_query($conn, $sql, [$id_registro]);
        if ($stmt === false) {
            throw new Exception('Error al reactivar informe: ' . print_r(sqlsrv_errors(), true));
        }

        echo json_encode(['success' => true, 'message' => 'Informe reactivado exitosamente']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==================== FUNCIONES DE REACTIVACIÓN ====================

function reactivarResiduo($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de residuo requerido']);
        return;
    }
    
    $id = intval($data['id']);
    
    try {
        $sql = "UPDATE laboratorio.Residuo_Catalogo 
                SET Activo = 1 
                WHERE Id_Residuo_Cat = ?";
        
        $stmt = sqlsrv_query($conn, $sql, [$id]);
        
        // Verificar si hay errores en la consulta
        $errors = sqlsrv_errors();
        if (!empty($errors)) {
            throw new Exception('Error SQL: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        echo json_encode(['success' => true, 'message' => 'Residuo reactivado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function reactivarNormativa($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de normativa requerido']);
        return;
    }
    
    $id = intval($data['id']);
    
    try {
        $sql = "UPDATE laboratorio.Normativa_SST 
                SET Activo = 1
                WHERE Id_Normativa_SST = ?";
        
        $stmt = sqlsrv_query($conn, $sql, [$id]);
        
        // Verificar si hay errores en la consulta
        $errors = sqlsrv_errors();
        if (!empty($errors)) {
            throw new Exception('Error SQL: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        
        echo json_encode(['success' => true, 'message' => 'Normativa reactivada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
