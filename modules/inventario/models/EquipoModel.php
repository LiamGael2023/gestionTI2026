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
    EDITAR EQUIPO
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
    MOSTRAR EQUIPO(S) — solo activo = 1
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
                a.compuesto,
                STRING_AGG(tc.descripcion + ': ' + c.valor, ', ') AS caracteristicas
            FROM $tabla e
            INNER JOIN inventario.activos              a  ON e.idActivo             = a.idActivos
            LEFT  JOIN inventario.equipoCaracteristica ec ON e.idEquipo             = ec.idEquipo
            LEFT  JOIN inventario.caracteristicas      c  ON ec.idCaracteristica    = c.idCaracteristica
            LEFT  JOIN inventario.tipoCaracteristica   tc ON c.idTipoCaracteristica = tc.idTipoCaracteristica
            WHERE e.activo = 1
        ";

        $groupBy = "
            GROUP BY
                e.idEquipo, e.idActivo, e.idEquipoPadre, e.numeroSerie,
                e.codigoPatrimonial, e.fechaAdquisicion, e.fechaInicioGarantia,
                e.fechaFinGarantia, e.fechaCreacion, e.idUsuarioRegistro,
                e.idUsuarioModifica, e.fechaModificacion,
                a.descripcion, a.icono, a.compuesto
        ";

        if ($item != null) {
            $sql    = $selectBase . " AND e.$item = ? " . $groupBy;
            $params = [[$valor, SQLSRV_PARAM_IN]];
            $stmt   = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) { sqlsrv_close($conn); return null; }
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $sql  = $selectBase . $groupBy . " ORDER BY e.idEquipo ASC";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) { sqlsrv_close($conn); return []; }
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
    =============================================*/
    static public function mdlMostrarCaracteristicasEquipo($idEquipo)
    {
        $conn = Conexion::conectar();
        $sql  = "
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
        $stmt = sqlsrv_query($conn, $sql, [[$idEquipo, SQLSRV_PARAM_IN]]);
        if ($stmt === false) { sqlsrv_close($conn); return []; }
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

    /*=============================================
    COMPONENTES ACTUALES DE UN EQUIPO PADRE
    =============================================*/
    static public function mdlMostrarComponentes(int $idEquipoPadre)
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT e.idEquipo,
                   e.numeroSerie,
                   e.codigoPatrimonial,
                   e.idEquipoPadre,
                   a.descripcion AS nombreActivo,
                   a.icono       AS iconoActivo,
                   STUFF((
                       SELECT TOP 3 ', ' + tc2.descripcion + ': ' + c2.valor
                       FROM inventario.equipoCaracteristica ec2
                       INNER JOIN inventario.caracteristicas     c2  ON ec2.idCaracteristica    = c2.idCaracteristica
                       INNER JOIN inventario.tipoCaracteristica  tc2 ON c2.idTipoCaracteristica = tc2.idTipoCaracteristica
                       WHERE ec2.idEquipo = e.idEquipo
                       FOR XML PATH(''), TYPE
                   ).value('.','NVARCHAR(MAX)'), 1, 2, '') AS caracteristicas
            FROM inventario.equipo e
            INNER JOIN inventario.activos a ON e.idActivo = a.idActivos
            WHERE e.idEquipoPadre = ? AND e.activo = 1
            ORDER BY a.descripcion ASC
        ";
        $stmt = sqlsrv_query($conn, $sql, [[$idEquipoPadre, SQLSRV_PARAM_IN]]);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /*=============================================
    EQUIPOS DISPONIBLES PARA SER COMPONENTES
    =============================================*/
    static public function mdlEquiposDisponibles(int $idPadre)
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT e.idEquipo,
                   e.numeroSerie,
                   e.codigoPatrimonial,
                   a.descripcion AS nombreActivo,
                   a.icono       AS iconoActivo,
                   STUFF((
                       SELECT TOP 3 ', ' + tc2.descripcion + ': ' + c2.valor
                       FROM inventario.equipoCaracteristica ec2
                       INNER JOIN inventario.caracteristicas     c2  ON ec2.idCaracteristica    = c2.idCaracteristica
                       INNER JOIN inventario.tipoCaracteristica  tc2 ON c2.idTipoCaracteristica = tc2.idTipoCaracteristica
                       WHERE ec2.idEquipo = e.idEquipo
                       FOR XML PATH(''), TYPE
                   ).value('.','NVARCHAR(MAX)'), 1, 2, '') AS caracteristicas
            FROM inventario.equipo e
            INNER JOIN inventario.activos a ON e.idActivo = a.idActivos
            WHERE e.idEquipoPadre IS NULL
              AND e.idEquipo      <> ?
              AND a.compuesto      = 0
              AND e.activo         = 1
            ORDER BY a.descripcion ASC, e.idEquipo ASC
        ";
        $stmt = sqlsrv_query($conn, $sql, [[$idPadre, SQLSRV_PARAM_IN]]);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /*=============================================
    AGREGAR COMPONENTE
    =============================================*/
    static public function mdlAgregarComponente(int $idPadre, int $idHijo)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn,
            "{call inventario.sp_AgregarComponente(?, ?, ?)}",
            [
                [$idPadre,                SQLSRV_PARAM_IN],
                [$idHijo,                 SQLSRV_PARAM_IN],
                [$_SESSION["usuario_id"], SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar SP."]; }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $row ?? ["resultado" => "error", "mensaje" => "Sin respuesta del servidor."];
    }

    /*=============================================
    QUITAR COMPONENTE
    =============================================*/
    static public function mdlQuitarComponente(int $idHijo)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn,
            "{call inventario.sp_QuitarComponente(?, ?)}",
            [
                [$idHijo,                 SQLSRV_PARAM_IN],
                [$_SESSION["usuario_id"], SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar SP."]; }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $row ?? ["resultado" => "error", "mensaje" => "Sin respuesta del servidor."];
    }

    /*=============================================
    ELIMINAR EQUIPO (lógico via SP)
    =============================================*/
    static public function mdlEliminarEquipo($datos)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn,
            "{call inventario.sp_EliminarEquipo(?, ?)}",
            [
                [$datos["idEquipo"],          SQLSRV_PARAM_IN],
                [$datos["idUsuarioModifica"], SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error al ejecutar el SP."];
        }
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del SP."];
    }
}
