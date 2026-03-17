<?php
require_once __DIR__ . "/../../../config/db.php";

class EquipoModel
{
    /*=============================================
    CREAR EQUIPO  (llama a sp_CrearEquipo — INSERT)
    =============================================*/
    static public function mdlCrearEquipo($datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_CrearEquipo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";

        $params = [
            [$datos["idEquipo"],            SQLSRV_PARAM_IN],
            [$datos["idActivo"],            SQLSRV_PARAM_IN],
            [$datos["idEquipoPadre"],       SQLSRV_PARAM_IN],
            [$datos["codigoPatrimonial"],   SQLSRV_PARAM_IN],
            [$datos["numeroSerie"],         SQLSRV_PARAM_IN],
            [$datos["fechaInicioGarantia"], SQLSRV_PARAM_IN],
            [$datos["fechaFinGarantia"],    SQLSRV_PARAM_IN],
            [$datos["fechaAdquisicion"],    SQLSRV_PARAM_IN],
            [$datos["idCaracteristicas"],   SQLSRV_PARAM_IN],
            [$datos["idUsuario"],           SQLSRV_PARAM_IN],
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error al ejecutar el procedimiento almacenado."];
        }

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del servidor."];
    }

    /*=============================================
    EDITAR EQUIPO  (llama a sp_EditarEquipo — UPDATE)
    =============================================*/
    static public function mdlEditarEquipo($datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_EditarEquipo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";

        $params = [
            [$datos["idEquipo"],            SQLSRV_PARAM_IN],
            [$datos["idActivo"],            SQLSRV_PARAM_IN],
            [$datos["idEquipoPadre"],       SQLSRV_PARAM_IN],
            [$datos["codigoPatrimonial"],   SQLSRV_PARAM_IN],
            [$datos["numeroSerie"],         SQLSRV_PARAM_IN],
            [$datos["fechaInicioGarantia"], SQLSRV_PARAM_IN],
            [$datos["fechaFinGarantia"],    SQLSRV_PARAM_IN],
            [$datos["fechaAdquisicion"],    SQLSRV_PARAM_IN],
            [$datos["idCaracteristicas"],   SQLSRV_PARAM_IN],
            [$datos["idUsuario"],           SQLSRV_PARAM_IN],
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error al ejecutar el procedimiento almacenado."];
        }

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del servidor."];
    }

    /*=============================================
    MOSTRAR EQUIPO(S)
    =============================================*/
    static public function mdlMostrarEquipo($tabla, $item, $valor)
    {
        $conn = Conexion::conectar();

        $selectBase = "
            SELECT
                e.idEquipo,
                e.idActivo,
                e.idEquipoPadre,
                e.numeroSerie,
                e.codigoPatrimonial,
                e.fechaAdquisicion,
                e.fechaInicioGarantia,
                e.fechaFinGarantia,
                e.fechaCreacion,
                e.idUsuarioRegistro,
                e.idUsuarioModifica,
                e.fechaModificacion,
                a.descripcion AS nombreActivo,
                a.icono       AS iconoActivo,
                STRING_AGG(tc.descripcion + ': ' + c.valor, ', ') AS caracteristicas
            FROM $tabla e
            INNER JOIN inventario.activos              a  ON e.idActivo             = a.idActivos
            LEFT  JOIN inventario.equipoCaracteristica ec ON e.idEquipo             = ec.idEquipo
            LEFT  JOIN inventario.caracteristicas      c  ON ec.idCaracteristica    = c.idCaracteristica
            LEFT  JOIN inventario.tipoCaracteristica   tc ON c.idTipoCaracteristica = tc.idTipoCaracteristica
        ";

        $groupBy = "
            GROUP BY
                e.idEquipo, e.idActivo, e.idEquipoPadre, e.numeroSerie,
                e.codigoPatrimonial, e.fechaAdquisicion, e.fechaInicioGarantia,
                e.fechaFinGarantia, e.fechaCreacion, e.idUsuarioRegistro,
                e.idUsuarioModifica, e.fechaModificacion,
                a.descripcion, a.icono
        ";

        if ($item != null) {
            $sql    = $selectBase . " WHERE e.$item = ? " . $groupBy;
            $params = [[$valor, SQLSRV_PARAM_IN]];
            $stmt   = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) { sqlsrv_close($conn); return "error"; }
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $sql  = $selectBase . $groupBy . " ORDER BY e.idEquipo ASC";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) { sqlsrv_close($conn); return "error"; }
            $resultado = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $row;
            }
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado;
    }

    /*=============================================
    MOSTRAR CARACTERÍSTICAS DE UN EQUIPO
    Devuelve array con idCaracteristica, tipo, valor
    para reconstruir la lista editable en el modal.
    =============================================*/
    static public function mdlMostrarCaracteristicasEquipo($idEquipo)
    {
        $conn = Conexion::conectar();

        $sql = "
            SELECT
                c.idCaracteristica,
                tc.descripcion AS tipo,
                c.valor
            FROM inventario.equipoCaracteristica ec
            INNER JOIN inventario.caracteristicas    c  ON ec.idCaracteristica    = c.idCaracteristica
            INNER JOIN inventario.tipoCaracteristica tc ON c.idTipoCaracteristica = tc.idTipoCaracteristica
            WHERE ec.idEquipo = ?
            ORDER BY tc.descripcion, c.valor
        ";

        $params = [[$idEquipo, SQLSRV_PARAM_IN]];
        $stmt   = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            sqlsrv_close($conn);
            return [];
        }

        $resultado = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultado[] = [
                "idCaracteristica" => intval($row["idCaracteristica"]),
                "tipo"             => (string)$row["tipo"],
                "valor"            => (string)$row["valor"],
            ];
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado;
    }
}