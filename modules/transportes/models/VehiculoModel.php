<?php
class ModeloVehiculo
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function listar()
    {
        $sql = "SELECT * FROM transportes WHERE activo = 1";
        return sqlsrv_query($this->db, $sql);
    }




    static public function mdlCrearVehiculo($datos)
    {
        $conn = Conexion::conectar();

        $sql = "EXEC [BD_PERSONAL].[Transportes].[sp_Registrar_Vehiculo] ?, ?, ?, ?, ?, ?, ?, ?, ?";

        $params = array(
            $datos["codigo_patrimonial"],
            $datos["placa"],
            $datos["id_estado_vehiculo"],
            $datos["numero_chasis"],
            $datos["marca"],
            $datos["modelo"],
            $datos["anioFabricacion"],
            $datos["color"],
            $datos["id_tipo_vehiculo"]
        );

        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if (!$stmt) {
            $errors = sqlsrv_errors();
            error_log("❌ Error preparando la consulta SQL: " . print_r($errors, true));
            return array("status" => "error", "message" => "Error al preparar la consulta", "details" => $errors);
        }

        if (!sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            error_log("❌ Error ejecutando la consulta SQL: " . print_r($errors, true));
            return array("status" => "error", "message" => "Error al ejecutar la consulta", "details" => $errors);
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return array("status" => "success", "message" => "Vehículo registrado correctamente");
    }


    static public function mdlAsignarConductor($placa, $idConductor)
    {
        $conn = Conexion::conectar();

        $sql = "EXEC [BD_PERSONAL].[Transportes].[sp_Asignar_Vehiculo] ?, ?";

        $params = array(
            $placa,
            $idConductor

        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {

            $errors = sqlsrv_errors();
            error_log("Error en la consulta SQL: " . print_r($errors, true)); // Registrar el error en el log
            return "error: " . print_r($errors, true);  // Devolver el error detallado
        }

        // Liberar el recurso de la consulta
        sqlsrv_free_stmt($stmt);

        return "ok";
    }
    static public function MdlMostrarVehiculoReporte(

        $item,
        $valor
    ) {

        $conn = Conexion::conectar();
        // printf('Item: %s — Valor: %s', htmlspecialchars($item), htmlspecialchars($valor));
        if ($item != null) {
            // $sql = "select * from $tabla where Id_Trabajador=$valor  ORDER BY id_papeleta DESC";
            $sql = "EXEC [BD_PERSONAL].[Transportes].[VW_Detalle_Vehiculo_Por_Placa] ?";
            $params = array($valor);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return []; // Si hay error, retorna un array vacío
            } else {
                $result = array(); // Inicializa un array vacío para almacenar los registros
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $result[] = $row; // Agrega cada registro al array $result
                }
                sqlsrv_free_stmt($stmt);
                return $result; // Retorna todos los registros encontrados
            }
        } else {
            $sql = "EXEC [BD_PERSONAL].[Transportes].[VW_Detalle_Vehiculo_Por_Placa] ?";
            $params = array($valor);

            // $sql = "select *,cast(fecha_inicio as DATE) as fechaini,cast(fecha_fin as DATE) as fechafin from papeleta ORDER BY id_papeleta DESC";

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return [];
            } else {
                $result = array();
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

                    $result[] = $row;
                }
                sqlsrv_free_stmt($stmt);
                return $result;
            }
        }

        sqlsrv_close($conn);
    }
    static public function MdlMostrarVehiculoReporteHistorial(

        $item,
        $valor
    ) {

        $conn = Conexion::conectar();
        // printf('Item: %s — Valor: %s', htmlspecialchars($item), htmlspecialchars($valor));
        if ($item != null) {
            // $sql = "select * from $tabla where Id_Trabajador=$valor  ORDER BY id_papeleta DESC";
            $sql = "EXEC [BD_PERSONAL].[Transportes].[SP_HISTORIAL_ASIGNAMIENTO_VEHICULO] ?";
            $params = array($valor);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return []; // Si hay error, retorna un array vacío
            } else {
                $result = array(); // Inicializa un array vacío para almacenar los registros
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $result[] = $row; // Agrega cada registro al array $result
                }
                sqlsrv_free_stmt($stmt);
                return $result; // Retorna todos los registros encontrados
            }
        } else {
            $sql = "EXEC [BD_PERSONAL].[Transportes].[SP_HISTORIAL_ASIGNAMIENTO_VEHICULO] ?";
            $params = array($valor);

            // $sql = "select *,cast(fecha_inicio as DATE) as fechaini,cast(fecha_fin as DATE) as fechafin from papeleta ORDER BY id_papeleta DESC";

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return [];
            } else {
                $result = array();
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

                    $result[] = $row;
                }
                sqlsrv_free_stmt($stmt);
                return $result;
            }
        }

        sqlsrv_close($conn);
    }
    static public function mdlDesasignarConductor($placa)
    {
        $conn = Conexion::conectar();

        $sql = "EXEC [BD_PERSONAL].[Transportes].[sp_Desasignar_Vehiculo] ?";
        $params = array($placa);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log("Error en la consulta SQL: " . print_r($errors, true));
            return array(
                "status" => "error",
                "message" => "Error en la consulta SQL",
                "detalle" => $errors
            );
        }

        sqlsrv_free_stmt($stmt);

        return array(
            "status" => "success",
            "message" => "Vehículo desasignado correctamente"
        );
    }


    static public function mdlMostrarVehiculos($tabla, $item, $valor)
    {
        $conn = Conexion::conectar();

        if ($item != null && $valor != null) {
            $sql = "EXEC    [BD_PERSONAL].[Transportes].[VW_Listar_Vehiculos]";
            $params = array($valor);
            $stmt = sqlsrv_query($conn, $sql, $params);
        } else {
            $sql = "EXEC [BD_PERSONAL].[Transportes].[VW_Listar_Vehiculos]";
            $stmt = sqlsrv_query($conn, $sql);
        }

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $datos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = $row;
        }

        return $datos;
    }



    static public function mdlMostrarReporteVehiculos($tabla, $item, $valor)
    {

        $conn = Conexion::conectar();

        if ($item != null && $valor != null) {

            $sql = "EXEC [BD_PERSONAL].[Transportes].[VW_Reporte_Vehiculos_Asignaciones]";
            $params = array($valor);
            $stmt = sqlsrv_query($conn, $sql, $params);
        } else {
            $sql = "EXEC [BD_PERSONAL].[Transportes].[VW_Reporte_Vehiculos_Asignaciones]";
            $stmt = sqlsrv_query($conn, $sql);
        }

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $datos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = $row;
        }

        return $datos;
    }


    static public function MdlMostrarVehiculoReporteHistorialPapeleta($fk_vehiculo)
    {

        $conn = Conexion::conectar();

        if ($fk_vehiculo != null) {

            $sql = "EXEC [BD_PERSONAL].[Transportes].[VW_Papeleta_Historial_Por_Vehiculos] ?";
            $params = array($fk_vehiculo);
            $stmt = sqlsrv_query($conn, $sql, $params);
        } else {
            $sql = "EXEC [BD_PERSONAL].[Transportes].[VW_Papeleta_Historial_Por_Vehiculos]";
            $stmt = sqlsrv_query($conn, $sql);
        }

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $datos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = $row;
        }

        return $datos;
    }

    static public function mdlMostrarConductoresDisponibles($item, $valor)
    {
        $conn = Conexion::conectar();

        if ($item != null && $valor != null) {
            $sql = "EXEC [BD_PERSONAL].[Transportes].[VW_Llenar_Combo_Conductores_Activos]";
            $params = array($valor);
            $stmt = sqlsrv_query($conn, $sql, $params);
        } else {
            $sql = "EXEC [BD_PERSONAL].[Transportes].[VW_Llenar_Combo_Conductores_Activos]";
            $stmt = sqlsrv_query($conn, $sql);
        }

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $datos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $datos[] = $row;
        }

        return $datos;
    }


    static public function MdlMostrarTipoVehiculo($tabla, $item, $valor)
    {

        $conn = Conexion::conectar();

        if ($item != null) {
            $sql = "SELECT * from $tabla where $item = ?";
            $params = array($valor);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return [];
            } else {
                $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($result['nombre']) {
                    $result['nombre'] = ($result['nombre']);
                }
                sqlsrv_free_stmt($stmt);
                return $result ? [$result] : [];
            }
        } else {

            $sql = "SELECT DISTINCT id_tipo_vehiculo, nombre_tipo FROM [BD_PERSONAL].$tabla ORDER BY nombre_tipo ASC;";




            $stmt = sqlsrv_query($conn, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return [];
            } else {
                $result = array();
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $result[] = $row;
                }
                sqlsrv_free_stmt($stmt);
                return $result;
            }
        }

        sqlsrv_close($conn);
    }

    static public function MdlMostrarEstadoVehiculo($tabla, $item, $valor)
    {

        $conn = Conexion::conectar();

        if ($item != null) {
            $sql = "SELECT * from $tabla where $item = ?";
            $params = array($valor);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return [];
            } else {
                $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($result['nombre']) {
                    $result['nombre'] = ($result['nombre']);
                }
                sqlsrv_free_stmt($stmt);
                return $result ? [$result] : [];
            }
        } else {

            $sql = "SELECT DISTINCT id_estado_vehiculo, nombre_estado FROM [BD_PERSONAL].$tabla ORDER BY nombre_estado ASC;";



            $stmt = sqlsrv_query($conn, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return [];
            } else {
                $result = array();
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $result[] = $row;
                }
                sqlsrv_free_stmt($stmt);
                return $result;
            }
        }

        sqlsrv_close($conn);
    }

    static public function mdlAnularVehiculo($tabla, $placa)
    {
        $conn = Conexion::conectar();

        $sql = "UPDATE [BD_PERSONAL].[Transportes].[tbl_vehiculo] SET dado_de_baja = 1 WHERE placa = ?";
        $params = array($placa);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('❌ Error al anular vehículo: ' . print_r($errors, true));
            return ["status" => "error", "message" => "No se pudo anular el vehículo"];
        }

        sqlsrv_close($conn);

        return ["status" => "success", "message" => "Vehículo anulado correctamente"];
    }


    static public function MdlMostrarMarcaVehiculo($tabla, $item, $valor)
    {

        $conn = Conexion::conectar();

        if ($item != null) {
            $sql = "SELECT * from $tabla where $item = ?";
            $params = array($valor);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return [];
            } else {
                $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($result['nombre']) {
                    $result['nombre'] = ($result['nombre']);
                }
                sqlsrv_free_stmt($stmt);
                return $result ? [$result] : [];
            }
        } else {

            $sql = "SELECT DISTINCT marca, nombre_marca FROM [BD_PERSONAL].$tabla ORDER BY nombre_marca ASC;";


            $stmt = sqlsrv_query($conn, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return [];
            } else {
                $result = array();
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $result[] = $row;
                }
                sqlsrv_free_stmt($stmt);
                return $result;
            }
        }

        sqlsrv_close($conn);
    }


    static public function MdlMostrarColorVehiculo()
    {

        $conn = Conexion::conectar();



        $sql = "SELECT DISTINCT color FROM [BD_PERSONAL].[Transportes].[tbl_vehiculo];";


        $stmt = sqlsrv_query($conn, $sql);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error en la consulta: ' . print_r($errors, true));
            return [];
        } else {
            $result = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row;
            }
            sqlsrv_free_stmt($stmt);
            return $result;
        }


        sqlsrv_close($conn);
    }

    static public function mdlBuscarPlaca($id_trabajador)
    {
        $conn = Conexion::conectar();
        $sql = "EXEC [BD_PERSONAL].[Transportes].[sp_Consultar_Vehiculo_Asignado_ID] ?";
        $params = array($id_trabajador);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log("❌ Error en la consulta SQL: " . print_r($errors, true));
            return null;
        }

        // ✅ Obtener el resultado como array asociativo
        $vehiculo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        // Cerrar y liberar recursos
        sqlsrv_free_stmt($stmt);

        // Devolver el array completo (que debería tener 'placa')
        return $vehiculo ?: null;
    }
}