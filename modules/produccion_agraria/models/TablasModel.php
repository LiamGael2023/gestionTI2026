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
        $sql = "SELECT id_clase, nombre_clase FROM BD_PRODUCCIONDESARROLLO.dbo.clase WHERE activo = 1 ORDER BY id_clase";
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
        $sql = "SELECT id_clase, nombre_clase FROM BD_PRODUCCIONDESARROLLO.dbo.clase WHERE id_clase = ? AND activo = 1";
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
        $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.clase SET activo = 0 WHERE id_clase = ?";
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
        $sql = "SELECT id_centro, nombre_centro, ubicacion, encargado FROM BD_PRODUCCIONDESARROLLO.dbo.centro_produccion WHERE activo = 1 ORDER BY id_centro";
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
        $sql = "SELECT id_centro, nombre_centro, ubicacion, encargado FROM BD_PRODUCCIONDESARROLLO.dbo.centro_produccion WHERE id_centro = ? AND activo = 1";
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
        $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.centro_produccion SET activo = 0 WHERE id_centro = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        return $stmt !== false;
    }

    // ========================================
    // VINCULACION: CLASE <-> CENTRO DE PRODUCCION
    // ========================================

    public function listarCentrosPorClase($idClase) {
        $sql = "SELECT cc.id_centro, cp.nombre_centro
                FROM BD_PRODUCCIONDESARROLLO.dbo.clase_centro cc
                INNER JOIN BD_PRODUCCIONDESARROLLO.dbo.centro_produccion cp ON cc.id_centro = cp.id_centro
                WHERE cc.id_clase = ? AND cp.activo = 1
                ORDER BY cp.nombre_centro";
        $stmt = sqlsrv_query($this->db, $sql, [$idClase]);
        $result = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function obtenerVinculacion($idClase) {
        $sql = "SELECT id_centro FROM BD_PRODUCCIONDESARROLLO.dbo.clase_centro WHERE id_clase = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$idClase]);
        $ids = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $ids[] = (int)$row['id_centro'];
            }
        }
        return $ids;
    }

    public function guardarVinculacion($idClase, $idCentro) {
        $sql = "IF NOT EXISTS (SELECT 1 FROM BD_PRODUCCIONDESARROLLO.dbo.clase_centro WHERE id_clase = ? AND id_centro = ?)
                INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.clase_centro (id_clase, id_centro) VALUES (?, ?)";
        $stmt = sqlsrv_query($this->db, $sql, [$idClase, $idCentro, $idClase, $idCentro]);
        if ($stmt === false) {
            return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
        }
        return ['success' => true];
    }

    public function eliminarVinculacion($idClase, $idCentro) {
        $sql = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.clase_centro WHERE id_clase = ? AND id_centro = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$idClase, $idCentro]);
        if ($stmt === false) {
            return ['success' => false, 'message' => print_r(sqlsrv_errors(), true)];
        }
        return ['success' => true];
    }

    public function guardarVinculaciones($idClase, $centrosIds) {
        try {
            sqlsrv_begin_transaction($this->db);

            $sqlDel = "DELETE FROM BD_PRODUCCIONDESARROLLO.dbo.clase_centro WHERE id_clase = ?";
            $stmtDel = sqlsrv_query($this->db, $sqlDel, [$idClase]);
            if ($stmtDel === false) throw new Exception('Error al limpiar vinculaciones: ' . print_r(sqlsrv_errors(), true));

            foreach ($centrosIds as $idCentro) {
                $sqlIns = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.clase_centro (id_clase, id_centro) VALUES (?, ?)";
                $stmtIns = sqlsrv_query($this->db, $sqlIns, [$idClase, $idCentro]);
                if ($stmtIns === false) throw new Exception('Error al insertar vinculacion: ' . print_r(sqlsrv_errors(), true));
            }

            sqlsrv_commit($this->db);
            return ['success' => true];
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ========================================
    // CRUD TABLA: UIT
    // ========================================
    
    public function listarUits() {
        $sql = "SELECT anio, valor FROM BD_PRODUCCIONDESARROLLO.dbo.uit WHERE activo = 1 ORDER BY anio DESC";
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
        $sql = "SELECT anio, valor FROM BD_PRODUCCIONDESARROLLO.dbo.uit WHERE anio = ? AND activo = 1";
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
        $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.uit SET activo = 0 WHERE anio = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$anio]);
        return $stmt !== false;
    }

    // ========================================
    // CRUD TABLA: CLIENTE
    // ========================================
    
    public function listarClientes() {
        $sql = "SELECT id_cliente, dni_ruc, nombre_rs, tipo_cliente FROM BD_PRODUCCIONDESARROLLO.dbo.cliente WHERE activo = 1 ORDER BY id_cliente";
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
        $sql = "SELECT id_cliente, dni_ruc, nombre_rs, tipo_cliente FROM BD_PRODUCCIONDESARROLLO.dbo.cliente WHERE id_cliente = ? AND activo = 1";
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
        $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.cliente SET activo = 0 WHERE id_cliente = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        return $stmt !== false;
    }
}
?>
