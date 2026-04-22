<?php
class RequisitoEquipoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT re.*, st.Nombre AS Servicio_Nombre, el.Nombre AS Equipo_Nombre FROM laboratorio.Requisito_Equipo re JOIN laboratorio.Servicio_Tecnico st ON re.Id_Servicio = st.Id_Servicio JOIN laboratorio.Equipo_Lab el ON re.Id_Equipo = el.Id_Equipo WHERE re.Activo = 1 ORDER BY st.Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Requisitos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT re.*, st.Nombre AS Servicio_Nombre, el.Nombre AS Equipo_Nombre FROM laboratorio.Requisito_Equipo re JOIN laboratorio.Servicio_Tecnico st ON re.Id_Servicio = st.Id_Servicio JOIN laboratorio.Equipo_Lab el ON re.Id_Equipo = el.Id_Equipo WHERE re.Id_Requisito_Equipo = ? AND re.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Requisito: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        $sql = "INSERT INTO laboratorio.Requisito_Equipo (Id_Servicio, Id_Equipo, Es_Bloqueante, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
        $params = array($datos['Id_Servicio'], $datos['Id_Equipo'], $datos['Es_Bloqueante'] ?? 1, $_SESSION['usuario_id'] ?? 1);
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Requisito: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        sqlsrv_next_result($stmt);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['id'];
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Requisito_Equipo SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Requisito_Equipo = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }
}