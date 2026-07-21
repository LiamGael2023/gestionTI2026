<?php
class ProveedorModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM laboratorio.Proveedor WHERE Activo = 1 ORDER BY Razon_Social";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Proveedores: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM laboratorio.Proveedor WHERE Id_Proveedor = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error buscando proveedor: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Proveedor'])) {
            $sql = "INSERT INTO laboratorio.Proveedor 
                        (Razon_Social, Ruc, Nombre_Contacto, Telefono, Email, Direccion, Usuario_Creacion, Activo, Fecha_Creacion)
                    OUTPUT INSERTED.Id_Proveedor AS id
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, GETDATE())";
            $params = [
                trim($datos['Razon_Social']),
                !empty($datos['Ruc'])           ? trim($datos['Ruc'])           : null,
                !empty($datos['Nombre_Contacto'])? trim($datos['Nombre_Contacto']): null,
                !empty($datos['Telefono'])       ? trim($datos['Telefono'])       : null,
                !empty($datos['Email'])          ? trim($datos['Email'])          : null,
                !empty($datos['Direccion'])      ? trim($datos['Direccion'])      : null,
                $_SESSION['usuario_id'] ?? 1
            ];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT Proveedor: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            $sql = "UPDATE laboratorio.Proveedor SET
                        Razon_Social=?, Ruc=?, Nombre_Contacto=?, Telefono=?, Email=?, Direccion=?,
                        Fecha_Modificacion=GETDATE()
                    WHERE Id_Proveedor=?";
            $params = [
                trim($datos['Razon_Social']),
                !empty($datos['Ruc'])           ? trim($datos['Ruc'])           : null,
                !empty($datos['Nombre_Contacto'])? trim($datos['Nombre_Contacto']): null,
                !empty($datos['Telefono'])       ? trim($datos['Telefono'])       : null,
                !empty($datos['Email'])          ? trim($datos['Email'])          : null,
                !empty($datos['Direccion'])      ? trim($datos['Direccion'])      : null,
                $datos['Id_Proveedor']
            ];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE Proveedor: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Proveedor'];
        }
    }

    public function eliminar($id) {
        $check = sqlsrv_query($this->db,
            "SELECT COUNT(*) AS c FROM laboratorio.Equipo_Lab WHERE Id_Proveedor = ? AND Activo = 1",
            [$id]
        );
        $row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
        if ($row['c'] > 0) {
            throw new Exception('No se puede desactivar este proveedor porque está asignado a ' . $row['c'] . ' equipo(s) activo(s).');
        }
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Proveedor SET Activo=0, Fecha_Modificacion=GETDATE() WHERE Id_Proveedor=?",
            [$id]
        );
    }

    public function reactivar($id) {
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Proveedor SET Activo=1, Fecha_Modificacion=GETDATE() WHERE Id_Proveedor=?",
            [$id]
        );
    }
}
