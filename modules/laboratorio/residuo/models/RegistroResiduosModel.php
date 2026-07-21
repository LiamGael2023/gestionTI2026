<?php
class RegistroResiduosModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT rrl.*, CONCAT(u.nombres, ' ', u.apellidos) AS Responsable_Nombre FROM laboratorio.Registro_Residuos_Log rrl JOIN comun.Usuarios u ON rrl.Usuario_Creacion = u.id_usuario WHERE rrl.Activo = 1 ORDER BY rrl.Anio DESC, rrl.Mes DESC";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Registros Residuos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT rrl.*, CONCAT(u.nombres, ' ', u.apellidos) AS Responsable_Nombre FROM laboratorio.Registro_Residuos_Log rrl JOIN comun.Usuarios u ON rrl.Usuario_Creacion = u.id_usuario WHERE rrl.Id_Registro_Res = ? AND rrl.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Registro Residuos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Registro_Res'])) {
            // INSERT
            $sql = "INSERT INTO laboratorio.Registro_Residuos_Log (Mes, Anio, Ubicacion, Codigo_SST, Id_Normativa_Aplicable, Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
            $params = array(
                $datos['Mes'],
                $datos['Anio'],
                $datos['Ubicacion'],
                $datos['Codigo_SST'] ?? 'SST-16',
                $datos['Id_Normativa_Aplicable'] ? $datos['Id_Normativa_Aplicable'] : null,
                $_SESSION['usuario_id'] ?? 1
            );
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT Registro Residuos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            // UPDATE
            $sql = "UPDATE laboratorio.Registro_Residuos_Log SET Mes=?, Anio=?, Ubicacion=?, Codigo_SST=?, Id_Normativa_Aplicable=?, Fecha_Modificacion=GETDATE() WHERE Id_Registro_Res=?";
            $params = array(
                $datos['Mes'],
                $datos['Anio'],
                $datos['Ubicacion'],
                $datos['Codigo_SST'],
                $datos['Id_Normativa_Aplicable'] ? $datos['Id_Normativa_Aplicable'] : null,
                $datos['Id_Registro_Res']
            );
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE Registro Residuos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Registro_Res'];
        }
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Registro_Residuos_Log SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Registro_Res = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    public function obtenerDetalles($idRegistro) {
        $sql = "SELECT drl.*, rc.Nombre_Item FROM laboratorio.Detalle_Residuos_Log drl JOIN laboratorio.Residuo_Catalogo rc ON drl.Id_Residuo_Cat = rc.Id_Residuo_Cat WHERE drl.Id_Registro_Res = ? AND drl.Activo = 1 ORDER BY drl.Fecha_Dia";
        $stmt = sqlsrv_query($this->db, $sql, array($idRegistro));
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardarDetalle($datos) {
        $sql = "INSERT INTO laboratorio.Detalle_Residuos_Log (Id_Registro_Res, Id_Residuo_Cat, Fecha_Dia, Peso_Valor, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, ?, ?, ?, 1, GETDATE())";
        $stmt = sqlsrv_query($this->db, $sql, array($datos['Id_Registro_Res'], $datos['Id_Residuo_Cat'], $datos['Fecha_Dia'], $datos['Peso_Valor'], $_SESSION['usuario_id'] ?? 1));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Detalle Residuos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }
}
