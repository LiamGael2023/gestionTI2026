<?php
class VentaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    private function tieneTipoVistaProducto() {
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Producto_Venta' AND COLUMN_NAME = 'Tipo_Vista'";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            return false;
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) !== null;
    }

    public function obtenerTodos($scope = 'interno_general') {
        $scope = strtoupper(trim((string)$scope));

        $sql = "SELECT * FROM laboratorio.Producto_Venta WHERE Activo = 1";
        $params = array();

        if ($this->tieneTipoVistaProducto()) {
            if ($scope === 'GENERAL' || $scope === 'EXTERNO') {
                $sql .= " AND Tipo_Vista = ?";
                $params[] = 'GENERAL';
            } elseif ($scope === 'INTERNO') {
                $sql .= " AND Tipo_Vista = ?";
                $params[] = 'INTERNO';
            } else {
                // Usuario interno: ver productos internos y generales
                $sql .= " AND Tipo_Vista IN (?, ?)";
                $params[] = 'INTERNO';
                $params[] = 'GENERAL';
            }
        }

        $sql .= " ORDER BY Nombre_Comercial";
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Ventas: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM laboratorio.Producto_Venta WHERE Id_Producto = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Venta: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        $tieneTipoVista = $this->tieneTipoVistaProducto();
        $tipoVista = strtoupper(trim((string)($datos['Tipo_Vista'] ?? 'GENERAL')));
        if ($tipoVista !== 'INTERNO' && $tipoVista !== 'GENERAL') {
            $tipoVista = 'GENERAL';
        }

        if (empty($datos['Id_Producto'])) {
            // INSERT
            if ($tieneTipoVista) {
                $sql = "INSERT INTO laboratorio.Producto_Venta (Nombre_Comercial, Descripcion, Precio_Venta, Tipo, Tipo_Vista, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                $params = array(
                    $datos['Nombre_Comercial'],
                    $datos['Descripcion'] ?? '',
                    $datos['Precio_Venta'],
                    $datos['Tipo'],
                    $tipoVista,
                    $_SESSION['usuario_id'] ?? 1
                );
            } else {
                $sql = "INSERT INTO laboratorio.Producto_Venta (Nombre_Comercial, Descripcion, Precio_Venta, Tipo, Usuario_Creacion, Activo, Fecha_Creacion)
                        VALUES (?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
                $params = array(
                    $datos['Nombre_Comercial'],
                    $datos['Descripcion'] ?? '',
                    $datos['Precio_Venta'],
                    $datos['Tipo'],
                    $_SESSION['usuario_id'] ?? 1
                );
            }
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT Producto: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            // UPDATE
            if ($tieneTipoVista) {
                $sql = "UPDATE laboratorio.Producto_Venta SET Nombre_Comercial=?, Descripcion=?, Precio_Venta=?, Tipo=?, Tipo_Vista=?, Fecha_Modificacion=GETDATE() WHERE Id_Producto=?";
                $params = array(
                    $datos['Nombre_Comercial'],
                    $datos['Descripcion'] ?? '',
                    $datos['Precio_Venta'],
                    $datos['Tipo'],
                    $tipoVista,
                    $datos['Id_Producto']
                );
            } else {
                $sql = "UPDATE laboratorio.Producto_Venta SET Nombre_Comercial=?, Descripcion=?, Precio_Venta=?, Tipo=?, Fecha_Modificacion=GETDATE() WHERE Id_Producto=?";
                $params = array(
                    $datos['Nombre_Comercial'],
                    $datos['Descripcion'] ?? '',
                    $datos['Precio_Venta'],
                    $datos['Tipo'],
                    $datos['Id_Producto']
                );
            }
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE Producto: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Producto'];
        }
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Producto_Venta SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Producto = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    public function obtenerServicios($idProducto) {
        $sql = "SELECT ps.*, st.Nombre FROM laboratorio.Producto_Servicio ps JOIN laboratorio.Servicio_Tecnico st ON ps.Id_Servicio = st.Id_Servicio WHERE ps.Id_Producto = ? AND ps.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idProducto));
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardarProductoServicio($datos) {
        $sql = "INSERT INTO laboratorio.Producto_Servicio (Id_Producto, Id_Servicio, Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, ?, ?, 1, GETDATE())";
        $params = array(
            $datos['Id_Producto'],
            $datos['Id_Servicio'],
            $_SESSION['usuario_id'] ?? 1
        );
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Producto_Servicio: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function reactivar($id) {
        $sql = "UPDATE laboratorio.Producto_Venta SET Activo = 1, Fecha_Modificacion = GETDATE() WHERE Id_Producto = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en reactivar: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function eliminarProductoServicio($idProducto, $idServicio) {
        $sql = "UPDATE laboratorio.Producto_Servicio SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Producto = ? AND Id_Servicio = ?";
        $stmt = sqlsrv_query($this->db, $sql, array($idProducto, $idServicio));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al eliminar servicio del producto: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }
}