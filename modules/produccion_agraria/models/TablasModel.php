<?php
class TablasModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ========================================
    // CRUD TABLA: CLASE
    // ========================================
    
    public function listarClases() {
        $sql = "SELECT id_clase, nombre_clase FROM BD_PRODUCCIONDESARROLLO.dbo.clase ORDER BY id_clase";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            error_log('SQL Error listarClases: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerClase($id) {
        $sql = "SELECT id_clase, nombre_clase FROM BD_PRODUCCIONDESARROLLO.dbo.clase WHERE id_clase = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return null;
    }

    public function guardarClase($data) {
        if (!empty($data['id_clase'])) {
            // UPDATE
            $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.clase SET nombre_clase = ? WHERE id_clase = ?";
            $params = [$data['nombre_clase'], $data['id_clase']];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
            }
            return ['success' => true, 'id' => $data['id_clase']];
        } else {
            // INSERT
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.clase (nombre_clase) VALUES (?)";
            $params = [$data['nombre_clase']];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
            }
            return ['success' => true, 'id' => $this->getLastInsertId()];
        }
    }

    public function eliminarClase($id) {
        // DELETE físico (la tabla no tiene soft delete)
        $sql = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.clase WHERE id_clase = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        return $stmt !== false;
    }

    private function getLastInsertId() {
        $sql = "SELECT SCOPE_IDENTITY() as id";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return $row['id'];
        }
        return null;
    }

    // ========================================
    // CRUD TABLA: CENTRO_PRODUCCION
    // ========================================
    
    public function listarCentros() {
        $sql = "SELECT id_centro, nombre_centro, ubicacion, encargado FROM BD_PRODUCCIONDESARROLLO.dbo.centro_produccion ORDER BY id_centro";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            error_log('SQL Error listarCentros: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerCentro($id) {
        $sql = "SELECT id_centro, nombre_centro, ubicacion, encargado FROM BD_PRODUCCIONDESARROLLO.dbo.centro_produccion WHERE id_centro = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return null;
    }

    public function guardarCentro($data) {
        if (!empty($data['id_centro'])) {
            $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.centro_produccion SET nombre_centro = ?, ubicacion = ?, encargado = ? WHERE id_centro = ?";
            $params = [$data['nombre_centro'], $data['ubicacion'], $data['encargado'], $data['id_centro']];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
            }
            return ['success' => true, 'id' => $data['id_centro']];
        } else {
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.centro_produccion (nombre_centro, ubicacion, encargado) VALUES (?, ?, ?)";
            $params = [$data['nombre_centro'], $data['ubicacion'], $data['encargado']];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
            }
            return ['success' => true, 'id' => $this->getLastInsertId()];
        }
    }

    public function eliminarCentro($id) {
        $sql = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.centro_produccion WHERE id_centro = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        return $stmt !== false;
    }

    // ========================================
    // CRUD TABLA: UIT
    // ========================================
    
    public function listarUits() {
        $sql = "SELECT anio, valor FROM BD_PRODUCCIONDESARROLLO.dbo.uit ORDER BY anio DESC";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            error_log('SQL Error listarUits: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerUit($anio) {
        $sql = "SELECT anio, valor FROM BD_PRODUCCIONDESARROLLO.dbo.uit WHERE anio = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$anio]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return null;
    }

    public function guardarUit($data) {
        // UIT usa el año como PK, si existe se actualiza
        $sql_check = "SELECT anio FROM BD_PRODUCCIONDESARROLLO.dbo.uit WHERE anio = ?";
        $stmt_check = sqlsrv_query($this->db, $sql_check, [$data['anio']]);
        
        if ($stmt_check && sqlsrv_has_rows($stmt_check)) {
            // UPDATE
            $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.uit SET valor = ? WHERE anio = ?";
            $params = [$data['valor'], $data['anio']];
        } else {
            // INSERT
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.uit (anio, valor) VALUES (?, ?)";
            $params = [$data['anio'], $data['valor']];
        }
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
        }
        return ['success' => true, 'anio' => $data['anio']];
    }

    public function eliminarUit($anio) {
        $sql = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.uit WHERE anio = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$anio]);
        return $stmt !== false;
    }

    // ========================================
    // CRUD TABLA: CLIENTE
    // ========================================
    
    public function listarClientes() {
        $sql = "SELECT id_cliente, dni_ruc, nombre_rs, tipo_cliente FROM BD_PRODUCCIONDESARROLLO.dbo.cliente ORDER BY id_cliente";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            error_log('SQL Error listarClientes: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerCliente($id) {
        $sql = "SELECT id_cliente, dni_ruc, nombre_rs, tipo_cliente FROM BD_PRODUCCIONDESARROLLO.dbo.cliente WHERE id_cliente = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return null;
    }

    public function guardarCliente($data) {
        if (!empty($data['id_cliente'])) {
            $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.cliente SET dni_ruc = ?, nombre_rs = ?, tipo_cliente = ? WHERE id_cliente = ?";
            $params = [$data['dni_ruc'], $data['nombre_rs'], $data['tipo_cliente'], $data['id_cliente']];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
            }
            return ['success' => true, 'id' => $data['id_cliente']];
        } else {
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.cliente (dni_ruc, nombre_rs, tipo_cliente) VALUES (?, ?, ?)";
            $params = [$data['dni_ruc'], $data['nombre_rs'], $data['tipo_cliente']];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
            }
            return ['success' => true, 'id' => $this->getLastInsertId()];
        }
    }

    public function eliminarCliente($id) {
        $sql = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.cliente WHERE id_cliente = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        return $stmt !== false;
    }
}
?>
