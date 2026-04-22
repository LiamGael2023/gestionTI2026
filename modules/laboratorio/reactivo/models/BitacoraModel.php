<?php
class BitacoraModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT m.*, r.Nombre AS Reactivo_Nombre FROM laboratorio.Movimiento_Kardex m JOIN laboratorio.Reactivo_Lab r ON m.Id_Reactivo = r.Id_Reactivo WHERE m.Activo = 1 ORDER BY m.Fecha_Registro DESC";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Movimientos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT m.*, r.Nombre AS Reactivo_Nombre FROM laboratorio.Movimiento_Kardex m JOIN laboratorio.Reactivo_Lab r ON m.Id_Reactivo = r.Id_Reactivo WHERE m.Id_Movimiento = ? AND m.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Movimiento: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardarMovimiento($datos) {
        $sql = "INSERT INTO laboratorio.Movimiento_Kardex (Id_Reactivo, Fecha_Registro, Tipo_Movimiento, Cantidad, Concepto, Saldo_Resultante, Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
        $params = array(
            $datos['Id_Reactivo'],
            date('Y-m-d H:i:s'),
            $datos['Tipo_Movimiento'],
            $datos['Cantidad'],
            $datos['Concepto'],
            $datos['Saldo_Resultante'],
            $_SESSION['usuario_id'] ?? 1
        );
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Movimiento: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        sqlsrv_next_result($stmt);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['id'];
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Movimiento_Kardex SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Movimiento = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    public function obtenerConsumos($idMovimiento) {
        $sql = "SELECT c.*, mp.Id_Muestra FROM laboratorio.Consumo_Reaccion c JOIN laboratorio.Muestra_Producto mp ON c.Id_Muestra_Producto = mp.Id_Muestra_Producto WHERE c.Id_Movimiento = ? AND c.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idMovimiento));
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardarConsumo($idMovimiento, $idMuestraProducto) {
        $sql = "INSERT INTO laboratorio.Consumo_Reaccion (Id_Movimiento, Id_Muestra_Producto, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, ?, 1, GETDATE())";
        $stmt = sqlsrv_query($this->db, $sql, array($idMovimiento, $idMuestraProducto, $_SESSION['usuario_id'] ?? 1));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Consumo: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }
}