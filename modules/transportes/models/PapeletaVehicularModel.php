<?php
class ModeloPapeletaVehicular
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



    static public function mdlRegistrarBitacora($datos)
    {
        $conn = Conexion::conectar();



        $sql = "EXEC BDPersonal.Transportes.SP_InsertarBitacora ?, ?";

        $params = array(
            $datos["descripcion_bitacora"],
            $datos["id_papeleta_vehicular"],
        );

        // Ejecutar la consulta

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('❌ Error al registrar bitacora: ' . print_r($errors, true));
            return ["status" => "error", "message" => "No se pudo registrar bitacora"];
        }

        sqlsrv_close($conn);

        return ["status" => "success", "message" => "Bitacora registrada correctamente"];
    }
    //DATATABLE ------ Lista las Papeletas Vehiculares --- PANEL PAPELETAS VEHICULARES
    static public function MdlMostrarPapeletasVehiculares(
        $id_establecimiento,
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
            $sql = "EXEC [BDPersonal].[Transportes].[VW_Papeleta_Vehicular_Sede] ?, ?, ?, ?, ?, ?";
            $params = array(
                $id_establecimiento,
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

    // COMBOBOX ---- Poblar Combobox de Conceptos en la Papeleta -- PAPELETA USUARIO
    static public function MdlMostrarSedesSalidaVehicular()
    {
        $conn = Conexion::conectar();

        $sql = "select id_sede as id, sede_nombre as sede, sede_abrev as abreviatura from [BDPersonal].[Transportes].[tbl_sede_salida_vehicular]";
        $stmt = sqlsrv_query($conn, $sql);

        if ($stmt === false) {
            error_log('Error en la consulta: ' . print_r(sqlsrv_errors(), true));
            sqlsrv_close($conn);
            return []; // siempre devuelve array
        }

        $sedes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['sede'])) {
                $row['sede'] = ($row['sede']);
            }
            $sedes[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $sedes;
    }


    static public function MdlMostrarPlacaVehicular()
    {
        $conn = Conexion::conectar();

        $sql = "select Id_Vehiculo as id, placa as placaseleccionada from [BDPersonal].[Transportes].[tbl_Vehiculo]  ";
        $stmt = sqlsrv_query($conn, $sql);

        if ($stmt === false) {
            error_log('Error en la consulta: ' . print_r(sqlsrv_errors(), true));
            sqlsrv_close($conn);
            return [];
        }

        $placas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['placa'])) {
                $row['placa'] = ($row['placa']);
            }
            $placas[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $placas;
    }
}
