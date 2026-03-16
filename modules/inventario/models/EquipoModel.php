<?php
require_once __DIR__ . "/../../../config/db.php";

class EquipoModel
{

    /*=============================================
    CREAR EQUIPO
    =============================================*/
    static public function mdlCrearEquipo($datos)
    {
        $conn = Conexion::conectar();
        $sql = "{call inventario.sp_CrearEquipo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";

        $params = array(
            array($datos["idEquipo"], SQLSRV_PARAM_IN),
            array($datos["idActivo"], SQLSRV_PARAM_IN),
            array($datos["idEquipoPadre"], SQLSRV_PARAM_IN),
            array($datos["codigoPatrimonial"], SQLSRV_PARAM_IN),
            array($datos["numeroSerie"], SQLSRV_PARAM_IN),
            array($datos["fechaInicioGarantia"], SQLSRV_PARAM_IN),
            array($datos["fechaFinGarantia"], SQLSRV_PARAM_IN),
            array($datos["fechaAdquisicion"], SQLSRV_PARAM_IN),
            array($datos["idCaracteristicas"], SQLSRV_PARAM_IN),
            array($datos["idUsuario"], SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) return ["resultado" => "error", "mensaje" => "Error al ejecutar SP"];

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado;
    }

    /*=============================================
    MOSTRAR EQUIPO(S) con activo e icono
    =============================================*/
    static public function mdlMostrarEquipo($tabla, $item, $valor)
    {
        $conn = Conexion::conectar();

        if ($item != null) {
            $sql = "
            SELECT e.idEquipo,
                   e.numeroSerie,
                   e.codigoPatrimonial,
                   e.fechaCreacion,
                   e.idUsuarioRegistro,
                   a.descripcion AS nombreActivo,
                   a.icono AS iconoActivo,
                   STRING_AGG(tc.descripcion + ': ' + c.valor, ', ') AS caracteristicas
            FROM $tabla e
            INNER JOIN inventario.activos a ON e.idActivo = a.idActivos
            LEFT JOIN inventario.equipoCaracteristica ec ON e.idEquipo = ec.idEquipo
            LEFT JOIN inventario.caracteristicas c ON ec.idCaracteristica = c.idCaracteristica
            LEFT JOIN inventario.tipoCaracteristica tc ON c.idTipoCaracteristica = tc.idTipoCaracteristica
            WHERE $item = ?
            GROUP BY e.idEquipo, e.numeroSerie, e.codigoPatrimonial, e.fechaCreacion, e.idUsuarioRegistro,
                     a.descripcion, a.icono
        ";
            $params = array($valor);
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) return "error";
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $sql = "
            SELECT e.idEquipo,
                   e.numeroSerie,
                   e.codigoPatrimonial,
                   e.fechaCreacion,
                   e.idUsuarioRegistro,
                   a.descripcion AS nombreActivo,
                   a.icono AS iconoActivo,
                   STRING_AGG(tc.descripcion + ': ' + c.valor, ', ') AS caracteristicas
            FROM $tabla e
            INNER JOIN inventario.activos a ON e.idActivo = a.idActivos
            LEFT JOIN inventario.equipoCaracteristica ec ON e.idEquipo = ec.idEquipo
            LEFT JOIN inventario.caracteristicas c ON ec.idCaracteristica = c.idCaracteristica
            LEFT JOIN inventario.tipoCaracteristica tc ON c.idTipoCaracteristica = tc.idTipoCaracteristica
            GROUP BY e.idEquipo, e.numeroSerie, e.codigoPatrimonial, e.fechaCreacion, e.idUsuarioRegistro,
                     a.descripcion, a.icono
            ORDER BY e.idEquipo ASC
        ";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) return "error";
            $resultado = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $row;
            }
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado;
    }
}
