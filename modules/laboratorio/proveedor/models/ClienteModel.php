<?php
class ClienteModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT Id_Cliente, Nombres, Apellido_Paterno, Apellido_Materno, Activo
                FROM laboratorio.Cliente
                WHERE Activo = 1
                ORDER BY Nombres, Apellido_Paterno";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Cliente: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerActivos() {
        $sql = "SELECT Id_Cliente,
                       LTRIM(RTRIM(CONCAT(
                           ISNULL(Nombres, ''),
                           ' ',
                           ISNULL(Apellido_Paterno, ''),
                           ' ',
                           ISNULL(Apellido_Materno, '')
                       ))) AS Nombre
                FROM laboratorio.Cliente
                WHERE Activo = 1
                ORDER BY Nombres, Apellido_Paterno";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en obtenerActivos Cliente: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = ['id' => intval($row['Id_Cliente']), 'nombre' => trim((string)$row['Nombre'])];
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT Id_Cliente, Nombres, Apellido_Paterno, Apellido_Materno, Activo
                FROM laboratorio.Cliente WHERE Id_Cliente = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error buscando cliente: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Cliente'])) {
            $sql = "INSERT INTO laboratorio.Cliente
                        (Dni, Nombres, Apellido_Paterno, Apellido_Materno, Activo)
                    OUTPUT INSERTED.Id_Cliente AS id
                    VALUES (?, ?, ?, ?, 1)";
            $params = [
                !empty(trim($datos['Dni'] ?? '')) ? trim($datos['Dni']) : null,
                trim($datos['Nombres']),
                trim($datos['Apellido_Paterno']),
                !empty(trim($datos['Apellido_Materno'] ?? '')) ? trim($datos['Apellido_Materno']) : null,
            ];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT Cliente: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            $sql = "UPDATE laboratorio.Cliente SET
                        Dni=?, Nombres=?, Apellido_Paterno=?, Apellido_Materno=?
                    WHERE Id_Cliente=?";
            $params = [
                !empty(trim($datos['Dni'] ?? '')) ? trim($datos['Dni']) : null,
                trim($datos['Nombres']),
                trim($datos['Apellido_Paterno']),
                !empty(trim($datos['Apellido_Materno'] ?? '')) ? trim($datos['Apellido_Materno']) : null,
                $datos['Id_Cliente']
            ];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE Cliente: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Cliente'];
        }
    }

    public function eliminar($id) {
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Cliente SET Activo=0, Fecha_Modificacion=GETDATE() WHERE Id_Cliente=?",
            [$id]
        );
    }

    public function reactivar($id) {
        sqlsrv_query($this->db,
            "UPDATE laboratorio.Cliente SET Activo=1, Fecha_Modificacion=GETDATE() WHERE Id_Cliente=?",
            [$id]
        );
    }
}
