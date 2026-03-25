<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class ModeloPapeleta
{
    private $conn;

    public function __construct($conn2)
    {
        $this->conn = $conn2;
    }

    public function listar()
    {
        $sql = "SELECT * FROM inicio WHERE activo = 1";
        return sqlsrv_query($this->conn, $sql);
    }

   

    /*
        DATATABLES
    */

    //DATATABLE ------- Lista las Papeletas -- PANEL PAPELETAS USUARIO
    static public function MdlMostrarPapeletasUsuarios($id_trabajador, $start, $length, $search = null)
    {
        $conn = Conexion::conectar();

        try {

            // ✅ Llamamos al SP con parámetros directos
            $sql = "EXEC [BDPERSONAL].[Aplicativo].[VW_Papeleta_Personal_Vehicular] ?, ?, ?,?";
            $params = array($id_trabajador, $start, $length, $search);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                error_log('Error SQL: ' . print_r(sqlsrv_errors(), true));
                return [];
            }

            // ==========================================
            // ✅ 1er resultset → total filtrado
            // ==========================================
            $rowTotal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $totalFiltrado = $rowTotal['TotalFiltrado'];

            // ✅ avanzar al 2do result set
            sqlsrv_next_result($stmt);

            // ==========================================
            // ✅ 2do resultset → filas paginadas
            // ==========================================
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }

            sqlsrv_free_stmt($stmt);

            // ✅ devolver datos en formato estándar
            return [
                "total" => $totalFiltrado,
                "data" => $data
            ];
        } finally {
            sqlsrv_close($conn);
        }
    }

    //DATATABLE ------ Lista las Papeletas --- PANEL PAPELETAS PENDIENTES
    static public function MdlMostrarPapeletasPendientesJefe(
        $id_jefe,
        $start,
        $length,
        $search = null,
        $filtro = null,      // ← HOY, AYER, MES, ESTE AÑO...
        $firmas = null       // ← estado_subgerencia, estado_transportes, etc.
    ) {
        $conn = Conexion::conectar();

        try {

            if ($filtro === "null" || $filtro === "") {
                $filtroFecha = null;
            }
            if ($firmas === "null" || $firmas === "") {
                $filtroCerrar = null;
            }
            // ============================================
            // ✅ Ejecutar SP con TODOS los parámetros
            // ============================================
            $sql = "EXEC [BDPERSONAL].[Aplicativo].[VW_Papeleta_Admin_Personal_Vehicular] ?, ?, ?, ?, ?, ?";
            $params = array(
                $id_jefe,
                $start,
                $length,
                $search,
                $filtro,
                $firmas
            );

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                error_log('Error SQL: ' . print_r(sqlsrv_errors(), true));
                return [];
            }

            // =====================================================
            // ✅ 1er resultset → TOTAL GENERAL
            // =====================================================
            $rowTotalGeneral = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $totalGeneral = $rowTotalGeneral["recordsTotal"];

            sqlsrv_next_result($stmt);

            // =====================================================
            // ✅ 2do resultset → TOTAL FILTRADO
            // =====================================================
            $rowTotalFiltrado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $totalFiltrado = $rowTotalFiltrado["recordsFiltered"];

            sqlsrv_next_result($stmt);

            // =====================================================
            // ✅ 3er resultset → REGISTROS PAGINADOS
            // =====================================================
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }

            sqlsrv_free_stmt($stmt);

            // =====================================================
            // ✅ RETORNO AL CONTROLADOR
            // =====================================================
            return [
                //"total"    => $totalGeneral,
                "total" => $totalFiltrado,
                "data"     => $data
            ];
        } finally {
            sqlsrv_close($conn);
        }
    }


    //DATATABLE ------ Lista las Papeletas --- PANEL PAPELETAS VIGILANTES
    static public function MdlMostrarPapeletasVigilantes($id_establecimiento = null, $start = 0, $length = 10, $search = null, $filtroFecha = null, $filtroCerrar = null)
    {
        $conn = Conexion::conectar();

        try {


            if ($filtroFecha === "null" || $filtroFecha === "") {
                $filtroFecha = null;
            }
            if ($filtroCerrar === "null" || $filtroCerrar === "") {
                $filtroCerrar = null;
            }

            // ✅ Llamamos al SP con parámetros directos
            $sql = "EXEC [BDPERSONAL].[Aplicativo].[VW_Papeleta_Vigilantes] ?, ?, ?,?,?,?";
            $params = array($id_establecimiento, $start, $length, $search, $filtroFecha, $filtroCerrar);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                error_log('Error SQL: ' . print_r(sqlsrv_errors(), true));
                return [];
            }

            // ==========================================
            // ✅ 1er resultset → total filtrado
            // ==========================================
            $rowTotal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $totalFiltrado = $rowTotal['TotalFiltrado'];

            // ✅ avanzar al 2do result set
            sqlsrv_next_result($stmt);

            // ==========================================
            // ✅ 2do resultset → filas paginadas
            // ==========================================
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }

            sqlsrv_free_stmt($stmt);

            // ✅ devolver datos en formato estándar
            return [
                "total" => $totalFiltrado,
                "data" => $data
            ];
        } finally {
            sqlsrv_close($conn);
        }
    }


    //DATATABLE ------ Lista las Papeletas --- PANEL PAPELETAS UPER
    static public function MdlMostrarPapeletasUPER($id_establecimiento = null, $start = 0, $length = 10, $search = null, $filtroFecha = null, $filtroCerrar = null)
    {
        $conn = Conexion::conectar();

        try {


            if ($filtroFecha === "null" || $filtroFecha === "") {
                $filtroFecha = null;
            }
            if ($filtroCerrar === "null" || $filtroCerrar === "") {
                $filtroCerrar = null;
            }

            // ✅ Llamamos al SP con parámetros directos
            $sql = "EXEC [BDPERSONAL].[Aplicativo].[VW_Papeleta_UPER] ?, ?, ?,?,?,?";
            $params = array($id_establecimiento, $start, $length, $search, $filtroFecha, $filtroCerrar);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                error_log('Error SQL: ' . print_r(sqlsrv_errors(), true));
                return [];
            }

            // ==========================================
            // ✅ 1er resultset → total filtrado
            // ==========================================
            $rowTotal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $totalFiltrado = $rowTotal['TotalFiltrado'];

            // ✅ avanzar al 2do result set
            sqlsrv_next_result($stmt);

            // ==========================================
            // ✅ 2do resultset → filas paginadas
            // ==========================================
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }

            sqlsrv_free_stmt($stmt);

            // ✅ devolver datos en formato estándar
            return [
                "total" => $totalFiltrado,
                "data" => $data
            ];
        } finally {
            sqlsrv_close($conn);
        }
    }


    //DATATABLE ------ Lista las Papeletas --- PANEL PAPELETAS UPER
    static public function MdlMostrarPapeletasPorTrabajador($id_trabajador, $start = 0, $length = 10, $search = null, $filtroFecha = null, $filtroCerrar = null)
    {
        $conn = Conexion::conectar();

        try {


            if ($filtroFecha === "null" || $filtroFecha === "") {
                $filtroFecha = null;
            }
            if ($filtroCerrar === "null" || $filtroCerrar === "") {
                $filtroCerrar = null;
            }

            // ✅ Llamamos al SP con parámetros directos
            $sql = "EXEC [BDPERSONAL].[Aplicativo].[SP_Listar_Papeletas_Por_Trabajador] ?, ?, ?,?";
            $params = array($id_trabajador, $start, $length, $search);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                error_log('Error SQL: ' . print_r(sqlsrv_errors(), true));
                return [];
            }

            // ==========================================
            // ✅ 1er resultset → total filtrado
            // ==========================================
            $rowTotal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $totalFiltrado = $rowTotal['TotalFiltrado'];

            // ✅ avanzar al 2do result set
            sqlsrv_next_result($stmt);

            // ==========================================
            // ✅ 2do resultset → filas paginadas
            // ==========================================
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }

            sqlsrv_free_stmt($stmt);

            // ✅ devolver datos en formato estándar
            return [
                "total" => $totalFiltrado,
                "data" => $data
            ];
        } finally {
            sqlsrv_close($conn);
        }
    }
    /*
        ACCIONES INSERT-UPDATE-DELETE
    */



    //ACCION ----- Anular Papeleta  -- PAPELETAS USUARIO
    static public function mdlAnularPapeleta($tabla, $idPapeleta)
    {

        $conn = Conexion::conectar();

        $sql = "UPDATE [BDPERSONAL].$tabla SET anulado = 1 WHERE id_papeleta = ?";
        $params = array($idPapeleta);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error al anular papeleta: ' . print_r($errors, true));
            return ["status" => "error", "message" => "No se pudo anular la papeleta"];
        } else {
            return ["status" => "success", "message" => "Papeleta anulada correctamente"];
        }

        sqlsrv_close($conn);
    }

    //ACCION ----- Actualizar Jefe Inmediato de una Papeleta -- PAPELETAS PENDIENTES
    static public function mdlActualizarJefeInmediato($idPapeleta, $codJefe)
    {

        $conn = Conexion::conectar();

        $sql = "[BDPERSONAL].[Aplicativo].[SP_ActualizarJefePapeleta] ?,?";
        $params = array($idPapeleta, $codJefe);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error al cambiar jefe: ' . print_r($errors, true));
            return ["status" => "error", "message" => "No se pudo cambiar el jefe inmediato"];
        } else {
            return ["status" => "success", "message" => "Jefe inmediato actualizado correctamente"];
        }

        sqlsrv_close($conn);
    }

    //ACCION ----- Insertar Evidencia -- PAPELETA USUARIO
    public static function mdlInsertarEvidencia($id_papeleta, $nombre, $tipo, $contenido)
    {
        $conn = Conexion::conectar();

        // Preparar la consulta con parámetros
        $sql = "INSERT INTO [BDPERSONAL].[Aplicativo].[evidencias] (id_papeletaFK, nombre, tipo, evidencia) VALUES (?, ?, ?, ?)";
        $params = [
            [$id_papeleta, SQLSRV_PARAM_IN],
            [$nombre, SQLSRV_PARAM_IN],
            [$tipo, SQLSRV_PARAM_IN],
            [$contenido, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY)]
        ];
        // Ejecutar la consulta usando sqlsrv_query
        $stmt = sqlsrv_query($conn, $sql, $params);

        if (!$stmt) {
            error_log("Error al insertar evidencia: " . print_r(sqlsrv_errors(), true));
            return false;
        }

        return true;
    }

    //ACCION ----- Crear Papeleta desde el panel de Usuarios -- PAPELETA USUARIO
    static public function mdlCrearPapeleta($datos)
    {
        $conn = Conexion::conectar();




        $sql = "EXEC [BDPERSONAL].[Aplicativo].[InsertarPapeleta] ?, ?, ?, ?, ?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?";

        $params = array(
            $datos["Id_Trabajador"],
            $datos["nombres"],
            $datos["oficina"],
            $datos["gerencia"],
            $datos["cod_jefe"],
            $datos["FirmaPersonal"],
            $datos["FirmaJefe"],
            $datos["FirmaJefeSede"],
            $datos["Id_Trabajador_Concepto_APP"],
            $datos["Id_Trabajador_Motivo_APP"],
            $datos["Id_Trabajador_Lugar_APP"],
            $datos["destinatario"],
            $datos["fecha_inicio"],
            $datos["fecha_fin"],
            $datos["observacion"],
            $datos["Id_Establecimiento"],
            $datos["sinretorno"],
            $datos["JefeInmediato"],
            $datos["Cerrar"],
            $datos["placaseleccionada"],
            $datos["kilometraje_inicial"],
            $datos["salida_trujillo"],
            $datos["salida_vehiculo"],
            $datos["solicitar_combustible"],
            $datos["sede"]
        );

        // Ejecutar la consulta
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            // Si hay un error, lo registramos en los logs y lo devolvemos
            $errors = sqlsrv_errors();
            error_log("Error en la consulta SQL: " . print_r($errors, true)); // Registrar el error en el log
            return "error: " . print_r($errors, true);  // Devolver el error detallado
        }

        // Liberar el recurso de la consulta

        sqlsrv_free_stmt($stmt);

        return "ok";
    }

    //ACCION ----- Intercambiar Estado de Aprobacion de una Papeleta --PAPELETAS PENDIENTES
    static public function mdlIntercambiarEstado($id_papeleta, $campo, $id_jefe_aprobacion)
    {

        $conn = Conexion::conectar();

        if (!in_array($campo, ['estadoJP', 'estadoJI', 'estado_subgerencia', 'estado_transportes'])) {
            return ["status" => "error", "message" => "Campo no válido"];
        }

        $sql = "EXEC [BDPERSONAL].[Aplicativo].[sp_Intercambiar_Estados_Papeleta] @id_papeleta = ?, @campo = ?, @id_jefe_aprobacion = ?";
        $params = [$id_papeleta, $campo, $id_jefe_aprobacion];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            return ["status" => "error", "message" => print_r($errors, true)];
        }

        // Obtener el valor devuelto
        $valor_actualizado = null;
        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $valor_actualizado = $row['valor_actualizado'];
        }

        sqlsrv_free_stmt($stmt);

        // Si no devolvió nada, manejar el caso
        if ($valor_actualizado === null) {
            return ["status" => "warning", "message" => "No se pudo determinar el valor actual", "valor" => null];
        }

        return [
            "status" => "success",
            "message" => "Estado procesado correctamente",
            "valor" => (int)$valor_actualizado
        ];
    }
    //ACCION ----- Eliminar Evidencia de una Papeleta --PAPELETA USUARIO
    public static function mdlEliminarEvidencia($id_evidencia)
    {
        $conn = Conexion::conectar();

        $sql = "DELETE FROM [BDPERSONAL].[Aplicativo].[evidencias] WHERE id_evidencia = ?";
        $params = [$id_evidencia];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error al eliminar evidencia: ' . print_r($errors, true));
            return ["status" => "error", "message" => "No se pudo eliminar la evidencia"];
        } else {
            return ["status" => "success", "message" => "Evidencia eliminada correctamente"];
        }

        sqlsrv_close($conn);
    }
    //ACCION ----- Marcar Papeleta como No Autorizada --PAPELETAS PENDIENTES
    public static function mdlNoAutorizado($id_papeleta, $codigo_jefe)
    {
        $conn = Conexion::conectar();

        $sql = "EXEC [BDPERSONAL].[Aplicativo].[SP_MarcarNoAutorizado] ?, ?";
        $params = [$id_papeleta, $codigo_jefe];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('❌ Error al ejecutar SP_MarcarNoAutorizado: ' . print_r($errors, true));

            $respuesta = [
                "status" => "error",
                "message" => "No se pudo actualizar la papeleta"
            ];
        } else {
            // Opcional: capturar resultados devueltos por el SP
            $resultado = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $row;
            }

            $respuesta = [
                "status" => "success",
                "message" => "Papeleta marcada como no autorizada correctamente",
                "data" => $resultado
            ];
        }

        if ($stmt) sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $respuesta;
    }


    public static function mdlTienePapeletaPendiente($id_trabajador)
    {
        $conn = Conexion::conectar();

        $sql = "EXEC [BDPERSONAL].[Aplicativo].[SP_TienePapeletaPendiente] ?";
        $params = [$id_trabajador];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('❌ Error al ejecutar SP_TienePapeletaPendiente: ' . print_r($errors, true));

            $respuesta = [
                "status" => "error",
                "message" => "No se pudo recibir la informacion"
            ];
        } else {
            // Opcional: capturar resultados devueltos por el SP
            $resultado = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $row;
            }

            $respuesta = [
                "status" => "success",
                "message" => "Estado de papeleta",
                "data" => $resultado
            ];
        }

        if ($stmt) sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $respuesta;
    }


    public static function mdlMarcarHoraSalida($id_papeleta)
    {
        $conn = Conexion::conectar();

        $sql = "UPDATE  [BDPERSONAL].[Aplicativo].[papeleta]
        SET hora_salida=CONVERT(time, GETDATE())
         WHERE id_papeleta = ?";
        $params = [$id_papeleta];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error al marcar: ' . print_r($errors, true));
            return ["status" => "error", "message" => "No se pudo marcar hora de salida"];
        } else {
            return ["status" => "success", "message" => "Retorno marcado correctamente"];
        }

        sqlsrv_close($conn);
    }


    public static function mdlMarcarHoraRetorno($id_papeleta)
    {
        $conn = Conexion::conectar();

        $sql = "UPDATE      [BDPERSONAL].[Aplicativo].[papeleta]
        SET hora_llegada=CONVERT(time, GETDATE())
         WHERE id_papeleta = ?";
        $params = [$id_papeleta];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error al marcar: ' . print_r($errors, true));
            return ["status" => "error", "message" => "No se pudo marcar hora de retorno"];
        } else {
            return ["status" => "success", "message" => "Retorno marcado correctamente"];
        }

        sqlsrv_close($conn);
    }

    /*
        RECURSOS (Poblar Combobox, Obtener Evidencias, Reportes PDF)
    */

    // COMBOBOX ---- Poblar Combobox de Conceptos en la Papeleta -- PAPELETA USUARIO
    static public function MdlMostrarConceptos($tabla, $item, $valor)
    {

        $conn = Conexion::conectar();

        if ($item != null) {
            $sql = "SELECT * from $tabla where $item = ?";
            $params = array($valor);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return []; // Asegúrate de que se devuelve un array vacío en caso de error
            } else {
                $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($result['nombre']) {
                    $result['nombre'] = ($result['nombre']);
                }
                sqlsrv_free_stmt($stmt);
                return $result ? [$result] : [];
            }
        } else {
            $sql = "SELECT distinct * from [BDPERSONAL].$tabla  where trab_estado= 1 order by concepto asc";

            $stmt = sqlsrv_query($conn, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log('Error en la consulta: ' . print_r($errors, true));
                return []; // Asegúrate de que se devuelve un array vacío en caso de error
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

    // COMBOBOX ---- Poblar Combobox cambiar Jefe Inmediato -- PAPELETAS PENDIENTES
    static public function mdlMostrarJefesUnidad()
    {
        $conn = Conexion::conectar();

        $sql = "EXEC [BDPERSONAL].[Aplicativo].[VW_Listar_Jefes_Divisiones]";

        $stmt = sqlsrv_query($conn, $sql);

        $resultados = array();

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }

        sqlsrv_close($conn);
        return $resultados;
    }

    // PDF------Detalle de Papeleta para Reporte por ID -- PAPELETA USUARIO-PENDIENTES
    static public function MdlMostrarPapeletaReporte($item, $valor)
    {

        $conn = Conexion::conectar();
        // printf('Item: %s — Valor: %s', htmlspecialchars($item), htmlspecialchars($valor));
        if ($item != null) {
            // $sql = "select * from $tabla where Id_Trabajador=$valor  ORDER BY id_papeleta DESC";
            $sql = "EXEC [BDPERSONAL].[Aplicativo].[VW_Papeleta_Personal_Vehicular_Por_Id] ?";
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
            $sql = "EXEC [BDPERSONAL].[Aplicativo].[VW_Papeleta_Personal_Vehicular_Por_Id] ?";
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

    //MODAL ------ Evidencias de una Papeleta por ID -- PAPELETA USUARIO-PENDIENTES
    static public function mdlObtenerEvidencias($id_papeleta)
    {
        $conn = Conexion::conectar();

        $sql = "SELECT id_evidencia as id,nombre, tipo, evidencia FROM [BDPERSONAL].[Aplicativo].[evidencias] WHERE id_papeletaFK = ?";
        $params = [$id_papeleta];

        $stmt = sqlsrv_query($conn, $sql, $params);
        if (!$stmt) {
            error_log("Error al obtener evidencias: " . print_r(sqlsrv_errors(), true));
            return [];
        }

        $resultados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Convertir a base64
            $base64 = base64_encode($row['evidencia']);
            $resultados[] = [
                "id" => $row['id'],
                "nombre" => $row['nombre'],
                "tipo" => $row['tipo'],
                "base64" => $base64
            ];
        }

        return $resultados;
    }

    static public function mdlFotoJefe()
    {
        $conn = Conexion::conectar();
        $sql = "EXEC [BDPERSONAL].[Aplicativo].[SP_FotoJefe] ?";
        $params = array($_SESSION["id_Trabajador"]);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error en la consulta: ' . print_r($errors, true));
            sqlsrv_close($conn);
            return null; // Devuelve null si hubo error
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $row ?: null; // Retorna un único registro o null si no encontró nada
    }
}
