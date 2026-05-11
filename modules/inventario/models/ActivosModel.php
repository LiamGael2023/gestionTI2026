<?php
require_once __DIR__ . "/../../../config/db.php";

class ActivosModel
{
    /*=============================================
    CREAR ACTIVO
    =============================================*/
    static public function mdlCrearActivo($datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_CrearActivo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
        $params = [
            [$datos["idActivo"],            SQLSRV_PARAM_IN],
            [$datos["idTipoActivo"],        SQLSRV_PARAM_IN],
            [$datos["idActivoPadre"],       SQLSRV_PARAM_IN],
            [$datos["codigoPatrimonial"],   SQLSRV_PARAM_IN],
            [$datos["codigoLicencia"],      SQLSRV_PARAM_IN],
            [$datos["numeroSerie"],         SQLSRV_PARAM_IN],
            [$datos["fechaInicioGarantia"], SQLSRV_PARAM_IN],
            [$datos["fechaFinGarantia"],    SQLSRV_PARAM_IN],
            [$datos["fechaAdquisicion"],    SQLSRV_PARAM_IN],
            [$datos["estado"],              SQLSRV_PARAM_IN],
            [$datos["idCaracteristicas"],   SQLSRV_PARAM_IN],
            [$datos["idUsuario"],           SQLSRV_PARAM_IN],
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar el procedimiento almacenado."]; }
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt); sqlsrv_close($conn);
        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del servidor."];
    }

    /*=============================================
    EDITAR ACTIVO
    =============================================*/
    static public function mdlEditarActivo($datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_EditarActivo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
        $params = [
            [$datos["idActivo"],            SQLSRV_PARAM_IN],
            [$datos["idTipoActivo"],        SQLSRV_PARAM_IN],
            [$datos["idActivoPadre"],       SQLSRV_PARAM_IN],
            [$datos["codigoPatrimonial"],   SQLSRV_PARAM_IN],
            [$datos["codigoLicencia"],      SQLSRV_PARAM_IN],
            [$datos["numeroSerie"],         SQLSRV_PARAM_IN],
            [$datos["fechaInicioGarantia"], SQLSRV_PARAM_IN],
            [$datos["fechaFinGarantia"],    SQLSRV_PARAM_IN],
            [$datos["fechaAdquisicion"],    SQLSRV_PARAM_IN],
            [$datos["estado"],              SQLSRV_PARAM_IN],
            [$datos["idCaracteristicas"],   SQLSRV_PARAM_IN],
            [$datos["idUsuario"],           SQLSRV_PARAM_IN],
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar el procedimiento almacenado."]; }
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt); sqlsrv_close($conn);
        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del servidor."];
    }

    /*=============================================
    MOSTRAR ACTIVO(S) — solo activo = 1
    =============================================*/
    static public function mdlMostrarActivo($tabla, $item, $valor)
    {
        $conn = Conexion::conectar();
        $selectBase = "
            SELECT e.idActivo, e.idTipoActivo, e.idActivoPadre, e.numeroSerie,
                   e.codigoPatrimonial, e.codigoLicencia, e.estado,
                   e.fechaAdquisicion, e.fechaInicioGarantia,
                   e.fechaFinGarantia, e.fechaCreacion, e.idUsuarioRegistro,
                   e.idUsuarioModifica, e.fechaModificacion,
                   a.descripcion AS nombreActivo, a.icono AS iconoActivo,
                   a.esCompuesto, a.esPeriferico, a.esComponente,
                   STRING_AGG(tc.descripcion + ': ' + c.valor, ', ') AS caracteristicas,
                   LTRIM(RTRIM(ISNULL(ur.nombres,'') + ' ' + ISNULL(ur.apellidos,''))) AS nombreUsuarioRegistro,
                   LTRIM(RTRIM(ISNULL(um.nombres,'') + ' ' + ISNULL(um.apellidos,''))) AS nombreUsuarioModifica
            FROM $tabla e
            INNER JOIN inventario.tipoActivo           a  ON e.idTipoActivo        = a.idTipoActivo
            LEFT  JOIN inventario.activoCaracteristica ec ON e.idActivo            = ec.idActivo
            LEFT  JOIN inventario.caracteristicas      c  ON ec.idCaracteristica   = c.idCaracteristica
            LEFT  JOIN inventario.tipoCaracteristica   tc ON c.idTipoCaracteristica= tc.idTipoCaracteristica
            LEFT  JOIN comun.Usuarios                  ur ON e.idUsuarioRegistro   = ur.id_usuario
            LEFT  JOIN comun.Usuarios                  um ON e.idUsuarioModifica   = um.id_usuario
            WHERE e.activo = 1";
        $groupBy = " GROUP BY e.idActivo, e.idTipoActivo, e.idActivoPadre, e.numeroSerie,
                   e.codigoPatrimonial, e.codigoLicencia, e.estado,
                   e.fechaAdquisicion, e.fechaInicioGarantia,
                   e.fechaFinGarantia, e.fechaCreacion, e.idUsuarioRegistro,
                   e.idUsuarioModifica, e.fechaModificacion,
                   a.descripcion, a.icono, a.esCompuesto, a.esPeriferico, a.esComponente,
                   ur.nombres, ur.apellidos, um.nombres, um.apellidos";
        if ($item != null) {
            $stmt = sqlsrv_query($conn, $selectBase . " AND e.$item = ? " . $groupBy, [[$valor, SQLSRV_PARAM_IN]]);
            if ($stmt === false) { sqlsrv_close($conn); return null; }
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $stmt = sqlsrv_query($conn, $selectBase . $groupBy . " ORDER BY e.idActivo ASC");
            if ($stmt === false) { sqlsrv_close($conn); return []; }
            $resultado = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $resultado[] = $row;
        }
        sqlsrv_free_stmt($stmt); sqlsrv_close($conn);
        return $resultado;
    }

    /*=============================================
    MOSTRAR CARACTERÍSTICAS DE UN ACTIVO
    =============================================*/
    static public function mdlMostrarCaracteristicasActivo($idActivo)
    {
        $conn = Conexion::conectar();
        $sql  = "SELECT c.idCaracteristica, tc.descripcion AS tipo, c.valor
                 FROM inventario.activoCaracteristica ec
                 INNER JOIN inventario.caracteristicas    c  ON ec.idCaracteristica    = c.idCaracteristica
                 INNER JOIN inventario.tipoCaracteristica tc ON c.idTipoCaracteristica = tc.idTipoCaracteristica
                 WHERE ec.idActivo = ? ORDER BY tc.descripcion, c.valor";
        $stmt = sqlsrv_query($conn, $sql, [[$idActivo, SQLSRV_PARAM_IN]]);
        if ($stmt === false) { sqlsrv_close($conn); return []; }
        $resultado = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultado[] = ["idCaracteristica" => intval($row["idCaracteristica"]), "tipo" => (string)$row["tipo"], "valor" => (string)$row["valor"]];
        }
        sqlsrv_free_stmt($stmt); sqlsrv_close($conn);
        return $resultado;
    }

    /*=============================================
    COMPONENTES ACTUALES DE UN ACTIVO PADRE
    =============================================*/
    static public function mdlMostrarComponentes(int $idActivoPadre)
    {
        $conn = Conexion::conectar();
        $sql  = "SELECT e.idActivo, e.numeroSerie, e.codigoPatrimonial, e.idActivoPadre,
                        a.descripcion AS nombreActivo, a.icono AS iconoActivo,
                        STUFF((SELECT TOP 3 ', ' + tc2.descripcion + ': ' + c2.valor
                               FROM inventario.activoCaracteristica ec2
                               INNER JOIN inventario.caracteristicas    c2  ON ec2.idCaracteristica    = c2.idCaracteristica
                               INNER JOIN inventario.tipoCaracteristica tc2 ON c2.idTipoCaracteristica = tc2.idTipoCaracteristica
                               WHERE ec2.idActivo = e.idActivo FOR XML PATH(''), TYPE).value('.','NVARCHAR(MAX)'), 1, 2, '') AS caracteristicas
                 FROM inventario.activo e
                 INNER JOIN inventario.tipoActivo a ON e.idTipoActivo = a.idTipoActivo
                 WHERE e.idActivoPadre = ? AND e.activo = 1 ORDER BY a.descripcion ASC";
        $stmt = sqlsrv_query($conn, $sql, [[$idActivoPadre, SQLSRV_PARAM_IN]]);
        $rows = [];
        if ($stmt !== false) { while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row; sqlsrv_free_stmt($stmt); }
        sqlsrv_close($conn);
        return $rows;
    }

    /*=============================================
    ACTIVOS DISPONIBLES PARA SER COMPONENTES
    =============================================*/
    static public function mdlActivosDisponibles(int $idPadre)
    {
        $conn = Conexion::conectar();
        $sql  = "SELECT e.idActivo, e.numeroSerie, e.codigoPatrimonial,
                        a.descripcion AS nombreActivo, a.icono AS iconoActivo,
                        STUFF((SELECT TOP 3 ', ' + tc2.descripcion + ': ' + c2.valor
                               FROM inventario.activoCaracteristica ec2
                               INNER JOIN inventario.caracteristicas    c2  ON ec2.idCaracteristica    = c2.idCaracteristica
                               INNER JOIN inventario.tipoCaracteristica tc2 ON c2.idTipoCaracteristica = tc2.idTipoCaracteristica
                               WHERE ec2.idActivo = e.idActivo FOR XML PATH(''), TYPE).value('.','NVARCHAR(MAX)'), 1, 2, '') AS caracteristicas
                 FROM inventario.activo e
                 INNER JOIN inventario.tipoActivo a ON e.idTipoActivo = a.idTipoActivo
                 WHERE e.idActivoPadre IS NULL AND e.idActivo <> ? AND a.esCompuesto = 0 AND e.activo = 1
                 ORDER BY a.descripcion ASC, e.idActivo ASC";
        $stmt = sqlsrv_query($conn, $sql, [[$idPadre, SQLSRV_PARAM_IN]]);
        $rows = [];
        if ($stmt !== false) { while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row; sqlsrv_free_stmt($stmt); }
        sqlsrv_close($conn);
        return $rows;
    }

    /*=============================================
    AGREGAR COMPONENTE
    =============================================*/
    static public function mdlAgregarComponente(int $idPadre, int $idHijo)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn, "{call inventario.sp_AgregarComponenteActivo(?, ?, ?)}",
            [[$idPadre, SQLSRV_PARAM_IN], [$idHijo, SQLSRV_PARAM_IN], [$_SESSION["usuario_id"], SQLSRV_PARAM_IN]]);
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar SP."]; }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC); sqlsrv_free_stmt($stmt); sqlsrv_close($conn);
        return $row ?? ["resultado" => "error", "mensaje" => "Sin respuesta del servidor."];
    }

    /*=============================================
    QUITAR COMPONENTE
    =============================================*/
    static public function mdlQuitarComponente(int $idHijo)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn, "{call inventario.sp_QuitarComponenteActivo(?, ?)}",
            [[$idHijo, SQLSRV_PARAM_IN], [$_SESSION["usuario_id"], SQLSRV_PARAM_IN]]);
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar SP."]; }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC); sqlsrv_free_stmt($stmt); sqlsrv_close($conn);
        return $row ?? ["resultado" => "error", "mensaje" => "Sin respuesta del servidor."];
    }

    /*=============================================
    ELIMINAR ACTIVO (lógico via SP)
    =============================================*/
    static public function mdlEliminarActivo($datos)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn, "{call inventario.sp_EliminarActivo(?, ?)}",
            [[$datos["idActivo"], SQLSRV_PARAM_IN], [$datos["idUsuarioModifica"], SQLSRV_PARAM_IN]]);
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar el SP."]; }
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC); sqlsrv_free_stmt($stmt); sqlsrv_close($conn);
        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del SP."];
    }
}