<?php
class AjusteInventarioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT a.*, r.Nombre AS Reactivo_Nombre, CONCAT(u.nombres, ' ', u.apellidos) AS Usuario_Nombre FROM laboratorio.Ajuste_Inventario a JOIN laboratorio.Reactivo_Lab r ON a.Id_Reactivo = r.Id_Reactivo JOIN comun.Usuarios u ON a.Usuario_Creacion = u.id_usuario WHERE a.Activo = 1 ORDER BY a.Fecha_Ajuste DESC";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Ajustes: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT a.*, r.Nombre AS Reactivo_Nombre, CONCAT(u.nombres, ' ', u.apellidos) AS Usuario_Nombre FROM laboratorio.Ajuste_Inventario a JOIN laboratorio.Reactivo_Lab r ON a.Id_Reactivo = r.Id_Reactivo JOIN comun.Usuarios u ON a.Usuario_Creacion = u.id_usuario WHERE a.Id_Ajuste = ? AND a.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Ajuste: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        $sql = "INSERT INTO laboratorio.Ajuste_Inventario (Id_Reactivo, Tipo_Ajuste, Cantidad, Fecha_Ajuste, Notas, Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
        $params = array(
            $datos['Id_Reactivo'],
            $datos['Tipo_Ajuste'],
            $datos['Cantidad'],
            date('Y-m-d H:i:s'),
            $datos['Notas'],
            $_SESSION['usuario_id'] ?? 1
        );
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Ajuste: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        sqlsrv_next_result($stmt);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['id'];
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Ajuste_Inventario SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Ajuste = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }
}
