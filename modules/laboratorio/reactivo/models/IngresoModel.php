<?php
class IngresoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT i.*, r.Nombre AS Reactivo_Nombre, CONCAT(u.nombres, ' ', u.apellidos) AS Usuario_Nombre FROM laboratorio.Ingreso_Reactivo i JOIN laboratorio.Reactivo_Lab r ON i.Id_Reactivo = r.Id_Reactivo JOIN comun.Usuarios u ON i.Usuario_Creacion = u.id_usuario WHERE i.Activo = 1 ORDER BY i.Fecha_Ingreso DESC";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Ingresos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT i.*, r.Nombre AS Reactivo_Nombre, CONCAT(u.nombres, ' ', u.apellidos) AS Usuario_Nombre FROM laboratorio.Ingreso_Reactivo i JOIN laboratorio.Reactivo_Lab r ON i.Id_Reactivo = r.Id_Reactivo JOIN comun.Usuarios u ON i.Usuario_Creacion = u.id_usuario WHERE i.Id_Ingreso = ? AND i.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Ingreso: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        $sql = "INSERT INTO laboratorio.Ingreso_Reactivo (Id_Reactivo, Cantidad, Fecha_Ingreso, Factura_Referencia, Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
        $params = array(
            $datos['Id_Reactivo'],
            $datos['Cantidad'],
            date('Y-m-d H:i:s'),
            $datos['Factura_Referencia'],
            $_SESSION['usuario_id'] ?? 1
        );
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Ingreso: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        sqlsrv_next_result($stmt);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['id'];
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Ingreso_Reactivo SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Ingreso = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }
}
