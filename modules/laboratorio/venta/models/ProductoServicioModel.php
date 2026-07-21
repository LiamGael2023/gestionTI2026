<?php
class ProductoServicioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT ps.*, pv.Nombre_Comercial, st.Nombre AS Servicio_Nombre FROM laboratorio.Producto_Servicio ps JOIN laboratorio.Producto_Venta pv ON ps.Id_Producto = pv.Id_Producto JOIN laboratorio.Servicio_Tecnico st ON ps.Id_Servicio = st.Id_Servicio WHERE ps.Activo = 1 ORDER BY pv.Nombre_Comercial";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Producto-Servicios: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardar($idProducto, $idServicio) {
        $sql = "INSERT INTO laboratorio.Producto_Servicio (Id_Producto, Id_Servicio, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, ?, 1, GETDATE())";
        $stmt = sqlsrv_query($this->db, $sql, array($idProducto, $idServicio, $_SESSION['usuario_id'] ?? 1));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Producto-Servicio: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function eliminar($idProducto, $idServicio) {
        $sql = "UPDATE laboratorio.Producto_Servicio SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Producto = ? AND Id_Servicio = ?";
        sqlsrv_query($this->db, $sql, array($idProducto, $idServicio));
    }
}
